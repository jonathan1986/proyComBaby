<?php
/**
 * Controlador Pedidos
 * Maneja historial y consulta de pedidos de usuarios
 */

namespace Modules\GestionUsuarios\Controllers;

use Modules\GestionUsuarios\Models\Pedido;
use Exception;

class PedidoController {
    private $modeloPedido;
    
    public function __construct($pdo) {
        $this->modeloPedido = new Pedido($pdo);
    }
    
    /**
     * Obtener pedidos del usuario
     * GET /api/usuarios/:id/pedidos?limit=20&offset=0
     */
    public function listar($usuario_id) {
        try {
            $limit = (int)($_GET['limit'] ?? 20);
            $offset = (int)($_GET['offset'] ?? 0);
            
            if ($limit > 100) $limit = 100;
            if ($offset < 0) $offset = 0;
            
            $pedidos = $this->modeloPedido->obtenerPorUsuario($usuario_id, $limit, $offset);
            $total = $this->modeloPedido->contarPorUsuario($usuario_id);
            $resumen = $this->modeloPedido->obtenerResumen($usuario_id);
            
            return $this->respuesta(200, 'OK', [
                'pedidos' => $pedidos,
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset,
                'resumen' => $resumen
            ]);
        } catch (Exception $e) {
            return $this->respuesta(500, $e->getMessage());
        }
    }
    
    /**
     * Obtener detalle de un pedido
     * GET /api/pedidos/:id
     */
    public function obtener($pedido_id) {
        try {
            $pedido = $this->modeloPedido->obtenerPorId($pedido_id);
            
            if (!$pedido) {
                return $this->respuesta(404, 'Pedido no encontrado');
            }
            
            return $this->respuesta(200, 'OK', $pedido);
        } catch (Exception $e) {
            return $this->respuesta(500, $e->getMessage());
        }
    }
    
    /**
     * Obtener estadísticas de pedidos
     * GET /api/usuarios/:id/pedidos/estadisticas
     */
    public function estadisticas($usuario_id) {
        try {
            $resumen = $this->modeloPedido->obtenerResumen($usuario_id);
            $por_estado = $this->modeloPedido->estadisticasPorEstado($usuario_id);
            $recientes = $this->modeloPedido->obtenerRecientes($usuario_id, 30);
            
            return $this->respuesta(200, 'OK', [
                'resumen' => $resumen,
                'por_estado' => $por_estado,
                'recientes_30_dias' => count($recientes)
            ]);
        } catch (Exception $e) {
            return $this->respuesta(500, $e->getMessage());
        }
    }
    
    /**
     * Obtener pedidos recientes
     * GET /api/usuarios/:id/pedidos/recientes
     */
    public function recientes($usuario_id) {
        try {
            $dias = (int)($_GET['dias'] ?? 30);
            
            $pedidos = $this->modeloPedido->obtenerRecientes($usuario_id, $dias);
            
            return $this->respuesta(200, 'OK', [
                'pedidos' => $pedidos,
                'total' => count($pedidos),
                'dias' => $dias
            ]);
        } catch (Exception $e) {
            return $this->respuesta(500, $e->getMessage());
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
