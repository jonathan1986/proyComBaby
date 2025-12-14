<?php
/**
 * Controlador Usuario
 * Maneja lógica de autenticación, registro y perfil
 */

namespace Modules\GestionUsuarios\Controllers;

use Modules\GestionUsuarios\Models\Usuario;
use Modules\GestionUsuarios\Models\Perfil;
use Exception;

class UsuarioController {
    private $modeloUsuario;
    private $modeloPerfil;
    
    public function __construct($pdo) {
        $this->modeloUsuario = new Usuario($pdo);
        $this->modeloPerfil = new Perfil($pdo);
    }
    
    /**
     * Registrar nuevo usuario
     * POST /api/usuarios/registro
     */
    public function registro() {
        try {
            $data = $this->obtenerJSON();
            
            // Validar entrada
            $this->validarRegistro($data);
            
            // Crear usuario
            $usuario_id = $this->modeloUsuario->crear(
                $data['email'],
                $data['nombre_completo'],
                $data['password'],
                $data['apellido'] ?? null,
                'CLIENTE' // Rol por defecto
            );
            
            return $this->respuesta(201, 'Usuario registrado exitosamente', [
                'usuario_id' => $usuario_id,
                'email' => $data['email']
            ]);
        } catch (Exception $e) {
            return $this->respuesta(400, $e->getMessage());
        }
    }
    
    /**
     * Login
     * POST /api/usuarios/login
     */
    public function login() {
        try {
            $data = $this->obtenerJSON();
            
            if (empty($data['email']) || empty($data['password'])) {
                return $this->respuesta(400, 'Email y contraseña requeridos');
            }
            
            // Autenticar
            $usuario = $this->modeloUsuario->autenticar($data['email'], $data['password']);
            
            // Crear sesión
            $token = $this->modeloUsuario->crearSesion($usuario['id']);
            
            // Obtener roles
            $roles = $this->modeloUsuario->obtenerRoles($usuario['id']);
            
            return $this->respuesta(200, 'Login exitoso', [
                'usuario_id' => $usuario['id'],
                'uuid' => $usuario['uuid_usuario'],
                'email' => $usuario['email'],
                'nombre' => $usuario['nombre_completo'],
                'token' => $token,
                'roles' => array_column($roles, 'codigo')
            ]);
        } catch (Exception $e) {
            return $this->respuesta(401, $e->getMessage());
        }
    }
    
    /**
     * Logout
     * POST /api/usuarios/logout
     */
    public function logout() {
        try {
            $data = $this->obtenerJSON();
            
            if (empty($data['token'])) {
                return $this->respuesta(400, 'Token requerido');
            }
            
            $this->modeloUsuario->cerrarSesion($data['token']);
            
            return $this->respuesta(200, 'Sesión cerrada');
        } catch (Exception $e) {
            return $this->respuesta(500, $e->getMessage());
        }
    }
    
    /**
     * Obtener datos de usuario
     * GET /api/usuarios/:id
     */
    public function obtener($usuario_id) {
        try {
            $usuario = $this->modeloUsuario->obtenerPorId($usuario_id);
            
            if (!$usuario) {
                return $this->respuesta(404, 'Usuario no encontrado');
            }
            
            // Obtener perfil
            $perfil = $this->modeloPerfil->obtener($usuario_id);
            $roles = $this->modeloUsuario->obtenerRoles($usuario_id);
            
            $usuario['perfil'] = $perfil;
            $usuario['roles'] = $roles;
            
            return $this->respuesta(200, 'OK', $usuario);
        } catch (Exception $e) {
            return $this->respuesta(500, $e->getMessage());
        }
    }
    
    /**
     * Actualizar perfil de usuario
     * PUT /api/usuarios/:id/perfil
     */
    public function actualizarPerfil($usuario_id) {
        try {
            $data = $this->obtenerJSON();
            
            // Actualizar datos de usuario
            $datos_usuario = [];
            if (!empty($data['nombre_completo'])) $datos_usuario['nombre_completo'] = $data['nombre_completo'];
            if (!empty($data['apellido'])) $datos_usuario['apellido'] = $data['apellido'];
            if (!empty($data['numero_documento'])) $datos_usuario['numero_documento'] = $data['numero_documento'];
            if (!empty($data['tipo_documento'])) $datos_usuario['tipo_documento'] = $data['tipo_documento'];
            if (!empty($data['fecha_nacimiento'])) $datos_usuario['fecha_nacimiento'] = $data['fecha_nacimiento'];
            if (!empty($data['genero'])) $datos_usuario['genero'] = $data['genero'];
            if (!empty($data['telefono'])) $datos_usuario['telefono'] = $data['telefono'];
            if (!empty($data['celular'])) $datos_usuario['celular'] = $data['celular'];
            
            if (!empty($datos_usuario)) {
                $this->modeloUsuario->actualizar($usuario_id, $datos_usuario);
            }
            
            // Actualizar perfil
            $datos_perfil = [];
            if (!empty($data['ciudad'])) $datos_perfil['ciudad'] = $data['ciudad'];
            if (!empty($data['pais'])) $datos_perfil['pais'] = $data['pais'];
            if (!empty($data['departamento'])) $datos_perfil['departamento'] = $data['departamento'];
            if (!empty($data['biografia'])) $datos_perfil['biografia'] = $data['biografia'];
            if (!empty($data['direccion_principal'])) $datos_perfil['direccion_principal'] = $data['direccion_principal'];
            if (!empty($data['foto_perfil_url'])) $datos_perfil['foto_perfil_url'] = $data['foto_perfil_url'];
            
            if (!empty($datos_perfil)) {
                $this->modeloPerfil->actualizar($usuario_id, $datos_perfil);
            }
            
            return $this->respuesta(200, 'Perfil actualizado');
        } catch (Exception $e) {
            return $this->respuesta(400, $e->getMessage());
        }
    }
    
    /**
     * Cambiar contraseña
     * POST /api/usuarios/:id/cambiar-contrasena
     */
    public function cambiarContrasena($usuario_id) {
        try {
            $data = $this->obtenerJSON();
            
            if (empty($data['password_antigua']) || empty($data['password_nueva']) || empty($data['confirmar_password'])) {
                return $this->respuesta(400, 'Todos los campos son requeridos');
            }
            
            if ($data['password_nueva'] !== $data['confirmar_password']) {
                return $this->respuesta(400, 'Las contraseñas no coinciden');
            }
            
            if (strlen($data['password_nueva']) < 8) {
                return $this->respuesta(400, 'La contraseña debe tener al menos 8 caracteres');
            }
            
            $this->modeloUsuario->cambiarContrasena(
                $usuario_id,
                $data['password_antigua'],
                $data['password_nueva']
            );
            
            return $this->respuesta(200, 'Contraseña cambiada exitosamente');
        } catch (Exception $e) {
            return $this->respuesta(400, $e->getMessage());
        }
    }
    
    /**
     * Solicitar recuperación de contraseña
     * POST /api/usuarios/recuperar-contrasena
     */
    public function solicitarRecuperacion() {
        try {
            $data = $this->obtenerJSON();
            
            if (empty($data['email'])) {
                return $this->respuesta(400, 'Email requerido');
            }
            
            $recuperacion = $this->modeloUsuario->generarTokenRecuperacion($data['email']);
            
            if (!$recuperacion) {
                // No revelar si el email existe o no
                return $this->respuesta(200, 'Si el email existe, recibirá un link de recuperación');
            }
            
            // TODO: Enviar email con token
            // En producción, aquí enviarías un email con el link
            // $this->enviarEmailRecuperacion($recuperacion['email'], $recuperacion['token']);
            
            return $this->respuesta(200, 'Si el email existe, recibirá un link de recuperación', [
                'token' => $recuperacion['token'] // En desarrollo solamente
            ]);
        } catch (Exception $e) {
            return $this->respuesta(500, $e->getMessage());
        }
    }
    
    /**
     * Validar token de recuperación
     * GET /api/usuarios/validar-token-recuperacion?token=...
     */
    public function validarTokenRecuperacion() {
        try {
            $token = $_GET['token'] ?? null;
            
            if (!$token) {
                return $this->respuesta(400, 'Token requerido');
            }
            
            $recuperacion = $this->modeloUsuario->validarTokenRecuperacion($token);
            
            return $this->respuesta(200, 'Token válido');
        } catch (Exception $e) {
            return $this->respuesta(400, 'Token inválido o expirado');
        }
    }
    
    /**
     * Resetear contraseña con token
     * POST /api/usuarios/resetear-contrasena
     */
    public function resetearContrasena() {
        try {
            $data = $this->obtenerJSON();
            
            if (empty($data['token']) || empty($data['password']) || empty($data['confirmar_password'])) {
                return $this->respuesta(400, 'Token, contraseña y confirmación requeridos');
            }
            
            if ($data['password'] !== $data['confirmar_password']) {
                return $this->respuesta(400, 'Las contraseñas no coinciden');
            }
            
            if (strlen($data['password']) < 8) {
                return $this->respuesta(400, 'La contraseña debe tener al menos 8 caracteres');
            }
            
            $this->modeloUsuario->resetearContrasena($data['token'], $data['password']);
            
            return $this->respuesta(200, 'Contraseña actualizada exitosamente');
        } catch (Exception $e) {
            return $this->respuesta(400, $e->getMessage());
        }
    }
    
    /**
     * Obtener permisos de usuario
     * GET /api/usuarios/:id/permisos
     */
    public function obtenerPermisos($usuario_id) {
        try {
            $permisos = $this->modeloUsuario->obtenerPermisos($usuario_id);
            $roles = $this->modeloUsuario->obtenerRoles($usuario_id);
            
            return $this->respuesta(200, 'OK', [
                'permisos' => $permisos,
                'roles' => array_column($roles, 'codigo')
            ]);
        } catch (Exception $e) {
            return $this->respuesta(500, $e->getMessage());
        }
    }
    
    /**
     * Validar sesión
     * GET /api/usuarios/validar-sesion?token=...
     */
    public function validarSesion() {
        try {
            $token = $_GET['token'] ?? null;
            
            if (!$token) {
                return $this->respuesta(401, 'Token no proporcionado');
            }
            
            $usuario = $this->modeloUsuario->validarSesion($token);
            
            if (!$usuario) {
                return $this->respuesta(401, 'Sesión inválida o expirada');
            }
            
            return $this->respuesta(200, 'Sesión válida', [
                'usuario_id' => $usuario['id'],
                'email' => $usuario['email'],
                'nombre' => $usuario['nombre_completo']
            ]);
        } catch (Exception $e) {
            return $this->respuesta(401, $e->getMessage());
        }
    }
    
    /**
     * Listar usuarios (solo admin)
     * GET /api/usuarios?limit=50&offset=0
     */
    public function listar() {
        try {
            $limit = (int)($_GET['limit'] ?? 50);
            $offset = (int)($_GET['offset'] ?? 0);
            
            if ($limit > 100) $limit = 100;
            if ($offset < 0) $offset = 0;
            
            $usuarios = $this->modeloUsuario->listarActivos($limit, $offset);
            $total = $this->modeloUsuario->contar();
            
            return $this->respuesta(200, 'OK', [
                'usuarios' => $usuarios,
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset
            ]);
        } catch (Exception $e) {
            return $this->respuesta(500, $e->getMessage());
        }
    }
    
    /**
     * Obtener perfil del usuario autenticado
     * GET /api/index.php?action=perfil
     * Header: Authorization: Bearer <token>
     */
    public function obtenerPerfilAutenticado() {
        try {
            // Obtener token del header Authorization
            $token = null;
            
            // Soporte para getallheaders() y Apache
            if (function_exists('getallheaders')) {
                $headers = getallheaders();
                if (isset($headers['Authorization'])) {
                    if (preg_match('/Bearer\s+(.*)$/i', $headers['Authorization'], $matches)) {
                        $token = $matches[1];
                    }
                }
            } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
                if (preg_match('/Bearer\s+(.*)$/i', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
                    $token = $matches[1];
                }
            }
            
            if (!$token) {
                return $this->respuesta(401, 'Token no proporcionado');
            }
            
            // Validar sesión
            $usuario = $this->modeloUsuario->validarSesion($token);
            
            if (!$usuario) {
                return $this->respuesta(401, 'Sesión inválida o expirada');
            }
            
            // Obtener roles del usuario
            $roles = $this->modeloUsuario->obtenerRoles($usuario['id']);
            $codigosRoles = array_column($roles, 'codigo');
            
            // Retornar perfil completo con roles
            return $this->respuesta(200, 'OK', [
                'usuario' => [
                    'id' => $usuario['id'],
                    'uuid_usuario' => $usuario['uuid_usuario'],
                    'email' => $usuario['email'],
                    'nombre_completo' => $usuario['nombre_completo'],
                    'apellido' => $usuario['apellido'] ?? '',
                    'telefono' => $usuario['telefono'] ?? '',
                    'celular' => $usuario['celular'] ?? '',
                    'estado_id' => $usuario['estado_id'],
                    'activo' => $usuario['activo'],
                    'roles' => implode(', ', $codigosRoles),
                    'fecha_creacion' => $usuario['fecha_creacion']
                ]
            ]);
        } catch (Exception $e) {
            return $this->respuesta(401, $e->getMessage());
        }
    }
    
    // ==================== Métodos auxiliares ====================
    
    /**
     * Obtener JSON del request
     */
    private function obtenerJSON() {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        return $data ?? [];
    }
    
    /**
     * Validar datos de registro
     */
    private function validarRegistro($data) {
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Email inválido');
        }
        
        if (empty($data['nombre_completo']) || strlen($data['nombre_completo']) < 3) {
            throw new Exception('Nombre completo debe tener al menos 3 caracteres');
        }
        
        if (empty($data['password']) || strlen($data['password']) < 8) {
            throw new Exception('Contraseña debe tener al menos 8 caracteres');
        }
        
        if (empty($data['confirmar_password']) || $data['password'] !== $data['confirmar_password']) {
            throw new Exception('Las contraseñas no coinciden');
        }
    }
    
    /**
     * Respuesta JSON estándar
     */
    private function respuesta($codigo, $mensaje, $datos = null) {
        header('Content-Type: application/json');
        http_response_code($codigo);
        
        $respuesta = [
            'codigo' => $codigo,
            'mensaje' => $mensaje
        ];
        
        if ($datos !== null) {
            $respuesta['datos'] = $datos;
        }
        
        echo json_encode($respuesta);
        exit;
    }
}
?>
