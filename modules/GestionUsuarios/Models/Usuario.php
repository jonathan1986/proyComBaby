<?php
/**
 * Modelo Usuario - Gestión de Usuarios
 * Maneja todas las operaciones CRUD y lógica de autenticación
 */

namespace Modules\GestionUsuarios\Models;

use PDO;
use Exception;

class Usuario {
    private $pdo;
    private $tabla = 'usuarios';
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Obtener usuario por ID
     */
    public function obtenerPorId($usuario_id) {
        $stmt = $this->pdo->prepare("CALL sp_obtener_usuario(?)");
        $stmt->execute([$usuario_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener usuario por email
     */
    public function obtenerPorEmail($email) {
        $stmt = $this->pdo->prepare("
            SELECT id, uuid_usuario, email, nombre_completo, apellido, 
                   contrasena_hash, estado_id, activo, fecha_creacion 
            FROM {$this->tabla} 
            WHERE email = ? AND activo = 1
        ");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener usuario por UUID
     */
    public function obtenerPorUUID($uuid) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM {$this->tabla} 
            WHERE uuid_usuario = ? AND activo = 1
        ");
        $stmt->execute([$uuid]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Crear nuevo usuario (Registro)
     */
    public function crear($email, $nombre_completo, $password, $apellido = null, $rol = 'CLIENTE') {
        try {
            // Verificar si email existe
            if ($this->obtenerPorEmail($email)) {
                throw new Exception('El email ya está registrado');
            }
            
            // Hash de contraseña
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            
            // Generar UUID v4
            $uuid = $this->generarUUID();
            
            // Usar stored procedure con UUID
            $stmt = $this->pdo->prepare("CALL sp_crear_usuario_nuevo(?, ?, ?, ?, ?, @id, @msg)");
            $stmt->execute([$uuid, $email, $nombre_completo, $hash, $rol]);
            
            $resultado = $this->pdo->query("SELECT @id as id, @msg as msg")->fetch();
            
            if (!$resultado['id']) {
                throw new Exception($resultado['msg'] ?? 'Error al crear usuario');
            }
            
            // Si hay apellido, actualizar
            if ($apellido) {
                $this->actualizar($resultado['id'], ['apellido' => $apellido]);
            }
            
            return $resultado['id'];
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Autenticar usuario (Login)
     */
    public function autenticar($email, $password) {
        try {
            $usuario = $this->obtenerPorEmail($email);
            
            if (!$usuario) {
                $this->registrarIntento(null, $email, 0, 'Email no encontrado');
                throw new Exception('Email o contraseña incorrectos');
            }
            
            if (!password_verify($password, $usuario['contrasena_hash'])) {
                $this->registrarIntento($usuario['id'], $email, 0, 'Contraseña incorrecta');
                throw new Exception('Email o contraseña incorrectos');
            }
            
            // Verificar estado
            if ($usuario['estado_id'] != 1) {
                throw new Exception('Usuario no está activo');
            }
            
            // Registrar intento exitoso
            $this->registrarIntento($usuario['id'], $email, 1, null);
            
            // Actualizar última conexión
            $stmt = $this->pdo->prepare("UPDATE {$this->tabla} SET fecha_ultima_conexion = NOW() WHERE id = ?");
            $stmt->execute([$usuario['id']]);
            
            return $usuario;
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Registrar intento de acceso
     */
    public function registrarIntento($usuario_id, $email, $exitoso, $razon = null) {
        try {
            $stmt = $this->pdo->prepare("
                CALL sp_registrar_intento_acceso(?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $usuario_id,
                $email,
                $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                $_SERVER['HTTP_USER_AGENT'] ?? '',
                $exitoso,
                $razon
            ]);
        } catch (Exception $e) {
            // No fallar si no se puede registrar intento
            error_log("Error registrando intento: " . $e->getMessage());
        }
    }
    
    /**
     * Actualizar datos de usuario
     */
    public function actualizar($usuario_id, $datos) {
        try {
            $campos_permitidos = ['nombre_completo', 'apellido', 'numero_documento', 
                                 'tipo_documento', 'fecha_nacimiento', 'genero', 
                                 'telefono', 'celular'];
            
            $updates = [];
            $valores = [];
            
            foreach ($datos as $campo => $valor) {
                if (in_array($campo, $campos_permitidos)) {
                    $updates[] = "`$campo` = ?";
                    $valores[] = $valor;
                }
            }
            
            if (empty($updates)) {
                return true;
            }
            
            $valores[] = $usuario_id;
            $sql = "UPDATE {$this->tabla} SET " . implode(', ', $updates) . " WHERE id = ?";
            
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($valores);
        } catch (Exception $e) {
            throw new Exception('Error al actualizar usuario: ' . $e->getMessage());
        }
    }
    
    /**
     * Cambiar contraseña
     */
    public function cambiarContrasena($usuario_id, $password_antigua, $password_nueva) {
        try {
            $usuario = $this->obtenerPorId($usuario_id);
            
            if (!$usuario) {
                throw new Exception('Usuario no encontrado');
            }
            
            if (!password_verify($password_antigua, $usuario['contrasena_hash'])) {
                throw new Exception('Contraseña actual es incorrecta');
            }
            
            $hash_anterior = $usuario['contrasena_hash'];
            $hash_nueva = password_hash($password_nueva, PASSWORD_BCRYPT, ['cost' => 12]);
            
            // Usar stored procedure
            $stmt = $this->pdo->prepare("CALL sp_cambiar_contrasena(?, ?, ?, @exito, @msg)");
            $stmt->execute([$usuario_id, $hash_anterior, $hash_nueva]);
            
            $resultado = $this->pdo->query("SELECT @exito as exito, @msg as msg")->fetch();
            
            if (!$resultado['exito']) {
                throw new Exception($resultado['msg'] ?? 'Error al cambiar contraseña');
            }
            
            return true;
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Recuperar contraseña - Generar token
     */
    public function generarTokenRecuperacion($email) {
        try {
            $usuario = $this->obtenerPorEmail($email);
            
            if (!$usuario) {
                // No revelar si existe o no el email
                return null;
            }
            
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+30 minutes'));
            
            $stmt = $this->pdo->prepare("
                INSERT INTO recuperacion_contrasena 
                (usuario_id, token_recuperacion, email_destino, fecha_expiracion) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$usuario['id'], $token, $email, $expires]);
            
            return [
                'token' => $token,
                'usuario_id' => $usuario['id'],
                'email' => $email
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Validar token de recuperación
     */
    public function validarTokenRecuperacion($token) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM recuperacion_contrasena 
                WHERE token_recuperacion = ? 
                AND usado = 0 
                AND fecha_expiracion > NOW()
            ");
            $stmt->execute([$token]);
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$resultado) {
                throw new Exception('Token inválido o expirado');
            }
            
            return $resultado;
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Resetear contraseña con token
     */
    public function resetearContrasena($token, $password_nueva) {
        try {
            $recuperacion = $this->validarTokenRecuperacion($token);
            
            $hash_nueva = password_hash($password_nueva, PASSWORD_BCRYPT, ['cost' => 12]);
            
            // Actualizar contraseña
            $stmt = $this->pdo->prepare("UPDATE {$this->tabla} SET contrasena_hash = ? WHERE id = ?");
            $stmt->execute([$hash_nueva, $recuperacion['usuario_id']]);
            
            // Marcar token como usado
            $stmt = $this->pdo->prepare("
                UPDATE recuperacion_contrasena 
                SET usado = 1, fecha_utilizacion = NOW(), ip_utilizacion = ? 
                WHERE id = ?
            ");
            $stmt->execute([$_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', $recuperacion['id']]);
            
            return true;
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Obtener permisos de usuario
     */
    public function obtenerPermisos($usuario_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT fn_obtener_permisos_usuario(?) as permisos");
            $stmt->execute([$usuario_id]);
            $resultado = $stmt->fetch()['permisos'];
            return $resultado ? explode(',', $resultado) : [];
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Verificar si usuario tiene permiso
     */
    public function tienePermiso($usuario_id, $permiso_codigo) {
        try {
            $stmt = $this->pdo->prepare("SELECT fn_usuario_tiene_permiso(?, ?) as tiene");
            $stmt->execute([$usuario_id, $permiso_codigo]);
            return (bool)$stmt->fetch()['tiene'];
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Obtener roles de usuario
     */
    public function obtenerRoles($usuario_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT r.id, r.codigo, r.nombre, r.descripcion, r.nivel_acceso
                FROM usuarios_roles ur
                JOIN roles r ON ur.rol_id = r.id
                WHERE ur.usuario_id = ? AND ur.activo = 1 AND r.activo = 1
            ");
            $stmt->execute([$usuario_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Listar usuarios activos con paginación
     */
    public function listarActivos($limit = 50, $offset = 0) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM v_usuarios_activos 
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$limit, $offset]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Contar total de usuarios activos
     */
    public function contar() {
        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as total FROM {$this->tabla} WHERE activo = 1");
            $stmt->execute();
            return $stmt->fetch()['total'];
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Bloquear usuario
     */
    public function bloquear($usuario_id, $razon = null, $admin_id = null) {
        try {
            $stmt = $this->pdo->prepare("CALL sp_bloquear_usuario(?, ?, ?)");
            $stmt->execute([$usuario_id, $razon, $admin_id]);
            return true;
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Crear sesión
     */
    public function crearSesion($usuario_id) {
        try {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+7 days'));
            
            $stmt = $this->pdo->prepare("
                INSERT INTO sesiones_usuario 
                (usuario_id, token_sesion, fecha_expiracion, fecha_inicio, activo) 
                VALUES (?, ?, ?, NOW(), 1)
            ");
            $stmt->execute([$usuario_id, $token, $expires]);
            
            return $token;
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Validar sesión
     */
    public function validarSesion($token) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT u.*, s.token_sesion, s.id as sesion_id
                FROM sesiones_usuario s
                JOIN usuarios u ON s.usuario_id = u.id
                WHERE s.token_sesion = ? 
                AND s.activo = 1 
                AND s.fecha_expiracion > NOW()
            ");
            $stmt->execute([$token]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Cerrar sesión
     */
    public function cerrarSesion($token) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE sesiones_usuario 
                SET activo = 0, fecha_ultima_actividad = NOW() 
                WHERE token_sesion = ?
            ");
            return $stmt->execute([$token]);
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Generar UUID v4
     * Seguro y compatible con MySQL 5.7
     */
    private function generarUUID() {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}
?>
