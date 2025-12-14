<?php
/**
 * Middleware de Autenticación para Módulo de Catálogo de Productos
 * Valida tokens de sesión del módulo de Gestión de Usuarios
 * Verifica roles y permisos requeridos
 */

class AuthMiddleware {
    private $pdo;
    private $rolesPermitidos;
    
    public function __construct($pdo, $rolesPermitidos = ['ADMINISTRADOR', 'GESTOR_CONTENIDOS']) {
        $this->pdo = $pdo;
        $this->rolesPermitidos = $rolesPermitidos;
    }
    
    /**
     * Valida el token Bearer y retorna los datos del usuario
     * @return array Datos del usuario autenticado
     * @throws Exception Si el token es inválido o el usuario no tiene permisos
     */
    public function validarAcceso() {
        // 1. Obtener token del header Authorization
        $token = $this->obtenerToken();
        
        if (!$token) {
            $this->respuestaError(401, 'Token no proporcionado');
        }
        
        // 2. Buscar sesión activa en la base de datos
        $sql = "
            SELECT 
                s.id AS sesion_id,
                s.usuario_id,
                s.fecha_expiracion,
                u.email,
                u.nombre_completo,
                u.estado_id,
                u.activo
            FROM sesiones_usuario s
            INNER JOIN usuarios u ON s.usuario_id = u.id
            WHERE s.token_sesion = :token
              AND s.activo = 1
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['token' => $token]);
        $sesion = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$sesion) {
            $this->respuestaError(401, 'Token inválido o sesión no encontrada');
        }
        
        // 3. Verificar que la sesión no haya expirado
        $ahora = new DateTime();
        $expiracion = new DateTime($sesion['fecha_expiracion']);
        
        if ($ahora > $expiracion) {
            // Marcar sesión como inactiva
            $this->pdo->prepare("UPDATE sesiones_usuario SET activo = 0 WHERE id = :id")
                      ->execute(['id' => $sesion['sesion_id']]);
            
            $this->respuestaError(401, 'Token expirado');
        }
        
        // 4. Verificar que el usuario esté ACTIVO
        if ($sesion['activo'] != 1 || $sesion['estado_id'] != 1) {
            $this->respuestaError(403, 'Usuario no está activo');
        }
        
        // 5. Obtener roles del usuario
        $sqlRoles = "
            SELECT r.codigo, r.nombre
            FROM usuarios_roles ur
            INNER JOIN roles r ON ur.rol_id = r.id
            WHERE ur.usuario_id = :usuario_id
              AND ur.activo = 1
              AND r.activo = 1
              AND (ur.fecha_vencimiento IS NULL OR ur.fecha_vencimiento > NOW())
        ";
        
        $stmtRoles = $this->pdo->prepare($sqlRoles);
        $stmtRoles->execute(['usuario_id' => $sesion['usuario_id']]);
        $roles = $stmtRoles->fetchAll(PDO::FETCH_ASSOC);
        
        $codigosRoles = array_column($roles, 'codigo');
        
        // 6. Verificar que el usuario tenga al menos uno de los roles permitidos
        $tieneAcceso = false;
        foreach ($this->rolesPermitidos as $rolPermitido) {
            if (in_array($rolPermitido, $codigosRoles)) {
                $tieneAcceso = true;
                break;
            }
        }
        
        if (!$tieneAcceso) {
            $this->respuestaError(403, 'Acceso denegado. Roles requeridos: ' . implode(', ', $this->rolesPermitidos));
        }
        
        // 7. Actualizar fecha_ultima_actividad
        $this->pdo->prepare("UPDATE sesiones_usuario SET fecha_ultima_actividad = NOW() WHERE id = :id")
                  ->execute(['id' => $sesion['sesion_id']]);
        
        // 8. Retornar datos del usuario autenticado
        return [
            'usuario_id' => $sesion['usuario_id'],
            'email' => $sesion['email'],
            'nombre_completo' => $sesion['nombre_completo'],
            'roles' => $codigosRoles,
            'sesion_id' => $sesion['sesion_id']
        ];
    }
    
    /**
     * Extrae el token del header Authorization
     * @return string|null Token sin el prefijo "Bearer "
     */
    private function obtenerToken() {
        $headers = getallheaders();
        
        if (isset($headers['Authorization'])) {
            $auth = $headers['Authorization'];
            
            // Formato esperado: "Bearer <token>"
            if (preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
                return $matches[1];
            }
        }
        
        return null;
    }
    
    /**
     * Envía respuesta de error y termina la ejecución
     * @param int $codigo Código HTTP (401, 403, etc.)
     * @param string $mensaje Mensaje de error
     */
    private function respuestaError($codigo, $mensaje) {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error' => $mensaje,
            'codigo' => $codigo
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Verifica si el usuario tiene un permiso específico
     * @param int $usuario_id ID del usuario
     * @param string $permiso_codigo Código del permiso (ej: 'PRODUCTOS_EDITAR')
     * @return bool
     */
    public function tienePermiso($usuario_id, $permiso_codigo) {
        $sql = "
            SELECT COUNT(*) as tiene
            FROM usuarios_roles ur
            INNER JOIN roles_permisos rp ON ur.rol_id = rp.rol_id
            INNER JOIN permisos p ON rp.permiso_id = p.id
            WHERE ur.usuario_id = :usuario_id
              AND ur.activo = 1
              AND p.codigo = :permiso_codigo
              AND p.activo = 1
              AND (ur.fecha_vencimiento IS NULL OR ur.fecha_vencimiento > NOW())
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'usuario_id' => $usuario_id,
            'permiso_codigo' => $permiso_codigo
        ]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['tiene'] > 0;
    }
}
