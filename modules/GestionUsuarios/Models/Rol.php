<?php
/**
 * Modelo Rol
 * Maneja roles y permisos
 */

namespace Modules\GestionUsuarios\Models;

use PDO;
use Exception;

class Rol {
    private $pdo;
    private $tabla_roles = 'roles';
    private $tabla_permisos = 'permisos';
    private $tabla_relacion = 'roles_permisos';
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Obtener rol por ID
     */
    public function obtenerPorId($rol_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM {$this->tabla_roles} WHERE id = ? AND activo = 1");
            $stmt->execute([$rol_id]);
            $rol = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($rol) {
                $rol['permisos'] = $this->obtenerPermisosDelRol($rol_id);
            }
            
            return $rol;
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Obtener rol por código
     */
    public function obtenerPorCodigo($codigo) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM {$this->tabla_roles} WHERE codigo = ? AND activo = 1");
            $stmt->execute([$codigo]);
            $rol = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($rol) {
                $rol['permisos'] = $this->obtenerPermisosDelRol($rol['id']);
            }
            
            return $rol;
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Listar todos los roles
     */
    public function listar() {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM {$this->tabla_roles} WHERE activo = 1 ORDER BY nivel_acceso DESC");
            $stmt->execute();
            $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($roles as &$rol) {
                $rol['permisos'] = $this->obtenerPermisosDelRol($rol['id']);
            }
            
            return $roles;
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Obtener permisos de un rol
     */
    public function obtenerPermisosDelRol($rol_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT p.* FROM {$this->tabla_permisos} p
                JOIN {$this->tabla_relacion} rp ON p.id = rp.permiso_id
                WHERE rp.rol_id = ? AND p.activo = 1
            ");
            $stmt->execute([$rol_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Listar todos los permisos
     */
    public function listarPermisos() {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM {$this->tabla_permisos} WHERE activo = 1 ORDER BY modulo, accion");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Obtener permisos por módulo
     */
    public function obtenerPermisosPorModulo($modulo) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM {$this->tabla_permisos} 
                WHERE modulo = ? AND activo = 1 
                ORDER BY accion
            ");
            $stmt->execute([$modulo]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Crear rol
     */
    public function crear($codigo, $nombre, $nivel_acceso = 10, $descripcion = null) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO {$this->tabla_roles} (codigo, nombre, nivel_acceso, descripcion, activo)
                VALUES (?, ?, ?, ?, 1)
            ");
            $stmt->execute([$codigo, $nombre, $nivel_acceso, $descripcion]);
            return $this->pdo->lastInsertId();
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Asignar permiso a rol
     */
    public function asignarPermiso($rol_id, $permiso_id) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT IGNORE INTO {$this->tabla_relacion} (rol_id, permiso_id)
                VALUES (?, ?)
            ");
            return $stmt->execute([$rol_id, $permiso_id]);
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Remover permiso de rol
     */
    public function removerPermiso($rol_id, $permiso_id) {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM {$this->tabla_relacion}
                WHERE rol_id = ? AND permiso_id = ?
            ");
            return $stmt->execute([$rol_id, $permiso_id]);
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Crear permiso
     */
    public function crearPermiso($codigo, $nombre, $modulo, $accion, $descripcion = null) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO {$this->tabla_permisos} (codigo, nombre, modulo, accion, descripcion, activo)
                VALUES (?, ?, ?, ?, ?, 1)
            ");
            $stmt->execute([$codigo, $nombre, $modulo, $accion, $descripcion]);
            return $this->pdo->lastInsertId();
        } catch (Exception $e) {
            throw $e;
        }
    }
}
?>
