<?php
/**
 * Modelo Perfil Usuario
 * Maneja datos extendidos del perfil de usuario
 */

namespace Modules\GestionUsuarios\Models;

use PDO;
use Exception;

class Perfil {
    private $pdo;
    private $tabla = 'perfiles_usuario';
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Obtener perfil de usuario
     */
    public function obtener($usuario_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM {$this->tabla} WHERE usuario_id = ?");
            $stmt->execute([$usuario_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Actualizar perfil de usuario
     */
    public function actualizar($usuario_id, $datos) {
        try {
            $campos_permitidos = [
                'foto_perfil_url', 'biografia', 'pais', 'departamento', 'ciudad',
                'direccion_principal', 'direccion_alternativa', 'codigo_postal',
                'idioma_preferido', 'zona_horaria', 'notificaciones_email',
                'notificaciones_sms', 'notificaciones_push'
            ];
            
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
            $sql = "UPDATE {$this->tabla} SET " . implode(', ', $updates) . " WHERE usuario_id = ?";
            
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($valores);
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Obtener redes sociales del usuario
     */
    public function obtenerRedesSociales($usuario_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT redes_sociales FROM {$this->tabla} WHERE usuario_id = ?");
            $stmt->execute([$usuario_id]);
            $resultado = $stmt->fetch()['redes_sociales'];
            return $resultado ? json_decode($resultado, true) : [];
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Actualizar redes sociales
     */
    public function actualizarRedesSociales($usuario_id, $redes_sociales) {
        try {
            $json = json_encode($redes_sociales);
            $stmt = $this->pdo->prepare("UPDATE {$this->tabla} SET redes_sociales = ? WHERE usuario_id = ?");
            return $stmt->execute([$json, $usuario_id]);
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Obtener preferencias de notificación
     */
    public function obtenerPreferenciaNotificacion($usuario_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT preferencias_notificacion FROM {$this->tabla} WHERE usuario_id = ?");
            $stmt->execute([$usuario_id]);
            $resultado = $stmt->fetch()['preferencias_notificacion'];
            return $resultado ? json_decode($resultado, true) : [];
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Actualizar preferencias de notificación
     */
    public function actualizarPreferenciaNotificacion($usuario_id, $preferencias) {
        try {
            $json = json_encode($preferencias);
            $stmt = $this->pdo->prepare("UPDATE {$this->tabla} SET preferencias_notificacion = ? WHERE usuario_id = ?");
            return $stmt->execute([$json, $usuario_id]);
        } catch (Exception $e) {
            throw $e;
        }
    }
}
?>
