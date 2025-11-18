<?php
/**
 * Modelo Pedidos
 * Maneja historial de pedidos de usuarios
 */

namespace Modules\GestionUsuarios\Models;

use PDO;
use Exception;

class Pedido {
    private $pdo;
    private $tabla_pedidos = 'pedidos';
    private $tabla_detalles = 'detalles_pedido';
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Obtener pedido por ID
     */
    public function obtenerPorId($pedido_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM {$this->tabla_pedidos} WHERE id = ?");
            $stmt->execute([$pedido_id]);
            $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($pedido) {
                $pedido['detalles'] = $this->obtenerDetalles($pedido_id);
            }
            
            return $pedido;
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Obtener pedidos de usuario
     */
    public function obtenerPorUsuario($usuario_id, $limit = 20, $offset = 0) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM {$this->tabla_pedidos}
                WHERE usuario_id = ?
                ORDER BY fecha_pedido DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$usuario_id, $limit, $offset]);
            $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($pedidos as &$pedido) {
                $pedido['detalles'] = $this->obtenerDetalles($pedido['id']);
            }
            
            return $pedidos;
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Obtener detalles de pedido
     */
    public function obtenerDetalles($pedido_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM {$this->tabla_detalles} WHERE pedido_id = ?");
            $stmt->execute([$pedido_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Contar pedidos de usuario
     */
    public function contarPorUsuario($usuario_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as total FROM {$this->tabla_pedidos} WHERE usuario_id = ?");
            $stmt->execute([$usuario_id]);
            return $stmt->fetch()['total'];
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Obtener resumen estadístico de pedidos
     */
    public function obtenerResumen($usuario_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    COUNT(*) as total_pedidos,
                    SUM(total) as gasto_total,
                    AVG(total) as ticket_promedio,
                    MAX(fecha_pedido) as ultimo_pedido
                FROM {$this->tabla_pedidos}
                WHERE usuario_id = ?
            ");
            $stmt->execute([$usuario_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Obtener pedidos recientes (últimos 30 días)
     */
    public function obtenerRecientes($usuario_id, $dias = 30) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM {$this->tabla_pedidos}
                WHERE usuario_id = ? 
                AND fecha_pedido >= DATE_SUB(NOW(), INTERVAL ? DAY)
                ORDER BY fecha_pedido DESC
            ");
            $stmt->execute([$usuario_id, $dias]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Obtener estadísticas por estado
     */
    public function estadisticasPorEstado($usuario_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    estado,
                    COUNT(*) as cantidad,
                    SUM(total) as monto_total
                FROM {$this->tabla_pedidos}
                WHERE usuario_id = ?
                GROUP BY estado
            ");
            $stmt->execute([$usuario_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
}
?>
