<?php
/**
 * Controlador Roles
 * Maneja gestión de roles y permisos
 */

namespace Modules\GestionUsuarios\Controllers;

use Modules\GestionUsuarios\Models\Rol;
use Exception;

class RolController {
    private $modeloRol;
    
    public function __construct($pdo) {
        $this->modeloRol = new Rol($pdo);
    }
    
    /**
     * Listar todos los roles
     * GET /api/roles
     */
    public function listar() {
        try {
            $roles = $this->modeloRol->listar();
            
            return $this->respuesta(200, 'OK', [
                'roles' => $roles,
                'total' => count($roles)
            ]);
        } catch (Exception $e) {
            return $this->respuesta(500, $e->getMessage());
        }
    }
    
    /**
     * Obtener rol por ID
     * GET /api/roles/:id
     */
    public function obtener($rol_id) {
        try {
            $rol = $this->modeloRol->obtenerPorId($rol_id);
            
            if (!$rol) {
                return $this->respuesta(404, 'Rol no encontrado');
            }
            
            return $this->respuesta(200, 'OK', $rol);
        } catch (Exception $e) {
            return $this->respuesta(500, $e->getMessage());
        }
    }
    
    /**
     * Obtener rol por código
     * GET /api/roles/codigo/:codigo
     */
    public function obtenerPorCodigo($codigo) {
        try {
            $rol = $this->modeloRol->obtenerPorCodigo($codigo);
            
            if (!$rol) {
                return $this->respuesta(404, 'Rol no encontrado');
            }
            
            return $this->respuesta(200, 'OK', $rol);
        } catch (Exception $e) {
            return $this->respuesta(500, $e->getMessage());
        }
    }
    
    /**
     * Listar todos los permisos
     * GET /api/permisos
     */
    public function listarPermisos() {
        try {
            $permisos = $this->modeloRol->listarPermisos();
            
            // Agrupar por módulo
            $por_modulo = [];
            foreach ($permisos as $permiso) {
                $modulo = $permiso['modulo'] ?? 'General';
                if (!isset($por_modulo[$modulo])) {
                    $por_modulo[$modulo] = [];
                }
                $por_modulo[$modulo][] = $permiso;
            }
            
            return $this->respuesta(200, 'OK', [
                'permisos' => $permisos,
                'por_modulo' => $por_modulo,
                'total' => count($permisos)
            ]);
        } catch (Exception $e) {
            return $this->respuesta(500, $e->getMessage());
        }
    }
    
    /**
     * Obtener permisos de un módulo
     * GET /api/permisos?modulo=usuarios
     */
    public function obtenerPermisosPorModulo() {
        try {
            $modulo = $_GET['modulo'] ?? null;
            
            if (!$modulo) {
                return $this->respuesta(400, 'Módulo requerido');
            }
            
            $permisos = $this->modeloRol->obtenerPermisosPorModulo($modulo);
            
            return $this->respuesta(200, 'OK', [
                'modulo' => $modulo,
                'permisos' => $permisos,
                'total' => count($permisos)
            ]);
        } catch (Exception $e) {
            return $this->respuesta(500, $e->getMessage());
        }
    }
    
    /**
     * Crear nuevo rol (admin)
     * POST /api/roles
     */
    public function crear() {
        try {
            $data = $this->obtenerJSON();
            
            if (empty($data['codigo']) || empty($data['nombre'])) {
                return $this->respuesta(400, 'Código y nombre son requeridos');
            }
            
            $rol_id = $this->modeloRol->crear(
                $data['codigo'],
                $data['nombre'],
                $data['nivel_acceso'] ?? 10,
                $data['descripcion'] ?? null
            );
            
            return $this->respuesta(201, 'Rol creado', ['rol_id' => $rol_id]);
        } catch (Exception $e) {
            return $this->respuesta(400, $e->getMessage());
        }
    }
    
    /**
     * Asignar permiso a rol (admin)
     * POST /api/roles/:id/permisos
     */
    public function asignarPermiso($rol_id) {
        try {
            $data = $this->obtenerJSON();
            
            if (empty($data['permiso_id'])) {
                return $this->respuesta(400, 'Permiso ID requerido');
            }
            
            $this->modeloRol->asignarPermiso($rol_id, $data['permiso_id']);
            
            return $this->respuesta(200, 'Permiso asignado');
        } catch (Exception $e) {
            return $this->respuesta(400, $e->getMessage());
        }
    }
    
    /**
     * Remover permiso de rol (admin)
     * DELETE /api/roles/:id/permisos/:permiso_id
     */
    public function removerPermiso($rol_id, $permiso_id) {
        try {
            $this->modeloRol->removerPermiso($rol_id, $permiso_id);
            
            return $this->respuesta(200, 'Permiso removido');
        } catch (Exception $e) {
            return $this->respuesta(400, $e->getMessage());
        }
    }
    
    /**
     * Crear nuevo permiso (admin)
     * POST /api/permisos
     */
    public function crearPermiso() {
        try {
            $data = $this->obtenerJSON();
            
            if (empty($data['codigo']) || empty($data['nombre'])) {
                return $this->respuesta(400, 'Código y nombre son requeridos');
            }
            
            $permiso_id = $this->modeloRol->crearPermiso(
                $data['codigo'],
                $data['nombre'],
                $data['modulo'] ?? 'General',
                $data['accion'] ?? 'ver',
                $data['descripcion'] ?? null
            );
            
            return $this->respuesta(201, 'Permiso creado', ['permiso_id' => $permiso_id]);
        } catch (Exception $e) {
            return $this->respuesta(400, $e->getMessage());
        }
    }
    
    /**
     * Obtener JSON del request
     */
    private function obtenerJSON() {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        return $data ?? [];
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
