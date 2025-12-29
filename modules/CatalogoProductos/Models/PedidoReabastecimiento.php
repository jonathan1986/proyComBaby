<?php
// filepath: modules/CatalogoProductos/Models/PedidoReabastecimiento.php
namespace Modules\CatalogoProductos\Models;

use PDO;

class PedidoReabastecimiento
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function crearPedido(array $cabecera, array $detalle): int
    {
        if (empty($cabecera['id_proveedor'])) { throw new \InvalidArgumentException('id_proveedor requerido'); }
        if (empty($detalle)) { throw new \InvalidArgumentException('detalle vacío'); }
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("INSERT INTO pedidos_reabastecimiento (fecha, id_proveedor, estado, observaciones) VALUES (NOW(), :id_proveedor, 'pendiente', :observaciones)");
            $stmt->execute([
                ':id_proveedor' => (int)$cabecera['id_proveedor'],
                ':observaciones' => (string)($cabecera['observaciones'] ?? ''),
            ]);
            $id = (int)$this->db->lastInsertId();
            $ins = $this->db->prepare("INSERT INTO pedidos_reabastecimiento_detalle (id_pedido, id_producto, cantidad, precio_unitario, precio_venta) VALUES (:id_pedido, :id_producto, :cantidad, :precio, :precio_venta)");
            foreach ($detalle as $item) {
                $ins->execute([
                    ':id_pedido' => $id,
                    ':id_producto' => (int)$item['id_producto'],
                    ':cantidad' => (int)$item['cantidad'],
                    ':precio' => (float)($item['precio_unitario'] ?? 0),
                    ':precio_venta' => (float)($item['precio_venta'] ?? 0),
                ]);
            }
            $this->db->commit();
            return $id;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function obtener(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM pedidos_reabastecimiento WHERE id_pedido = :id');
        $stmt->execute([':id' => $id]);
        $cab = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$cab) return null;
        $det = $this->listarDetalle($id);
        $cab['detalle'] = $det;
        // Flag de completitud basado en recepciones registradas
        $cab['completo'] = true;
        foreach ($det as $row) {
            $cant = (int)($row['cantidad'] ?? 0);
            $rec = (int)($row['recibido'] ?? 0);
            if ($rec < $cant) { $cab['completo'] = false; break; }
        }
        return $cab;
    }

    public function listar(array $filtros = [], int $limit = 50, int $offset = 0): array
    {
        $where = [];
        $params = [];
        if (!empty($filtros['estado'])) { $where[] = 'p.estado = :estado'; $params[':estado'] = (string)$filtros['estado']; }
        if (!empty($filtros['id_proveedor'])) { $where[] = 'p.id_proveedor = :prov'; $params[':prov'] = (int)$filtros['id_proveedor']; }
        $sql = "SELECT p.*, pr.nombre AS proveedor
                FROM pedidos_reabastecimiento p
                INNER JOIN proveedores pr ON pr.id_proveedor = p.id_proveedor
                " . (count($where) ? ('WHERE ' . implode(' AND ', $where)) : '') . "
                ORDER BY p.fecha DESC, p.id_pedido DESC
                LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
        $stmt->bindValue(':limit', max(1, min(200, (int)$limit)), PDO::PARAM_INT);
        $stmt->bindValue(':offset', max(0, (int)$offset), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarDetalle(int $idPedido): array
    {
        // Incluye cantidad recibida acumulada desde inventario_movimientos vinculados a este pedido
        $sql = 'SELECT d.id_producto, d.cantidad, d.precio_unitario, d.precio_venta, p.nombre AS producto, COALESCE(r.recibido, 0) AS recibido
                FROM pedidos_reabastecimiento_detalle d
                INNER JOIN productos p ON p.id_producto = d.id_producto
                LEFT JOIN (
                    SELECT id_producto, SUM(cantidad) AS recibido
                    FROM inventario_movimientos
                    WHERE id_pedido = :id AND tipo = "entrada"
                    GROUP BY id_producto
                ) r ON r.id_producto = d.id_producto
                WHERE d.id_pedido = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $idPedido]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function actualizarEstado(int $id, string $estado): bool
    {
        if (!in_array($estado, ['pendiente','recibido','cancelado'], true)) { throw new \InvalidArgumentException('Estado inválido'); }
        $stmt = $this->db->prepare('UPDATE pedidos_reabastecimiento SET estado = :estado WHERE id_pedido = :id');
        return $stmt->execute([':estado' => $estado, ':id' => $id]);
    }

    public function recibir(int $idPedido): bool
    {
        // Genera entradas por lo pendiente en cada detalle y marca pedido como recibido
        $this->db->beginTransaction();
        try {
            $cab = $this->obtener($idPedido);
            if (!$cab) { throw new \RuntimeException('Pedido no existe'); }
            if ($cab['estado'] !== 'pendiente') { throw new \RuntimeException('Solo pedidos pendientes pueden recibirse'); }
            $det = $cab['detalle'] ?? [];
            $ins = $this->db->prepare("INSERT INTO inventario_movimientos (id_producto, fecha, tipo, cantidad, motivo, id_pedido, usuario) VALUES (:id_producto, NOW(), 'entrada', :cantidad, :motivo, :id_pedido, :usuario)");
            $algoInsertado = false;
            foreach ($det as $row) {
                $cant = (int)($row['cantidad'] ?? 0);
                $rec = (int)($row['recibido'] ?? 0);
                $pend = max(0, $cant - $rec);
                if ($pend > 0) {
                    $ins->execute([
                        ':id_producto' => (int)$row['id_producto'],
                        ':cantidad' => $pend,
                        ':motivo' => 'recepción pedido',
                        ':id_pedido' => $idPedido,
                        ':usuario' => (string)($cab['usuario_actualizacion'] ?? 'system'),
                    ]);
                    $algoInsertado = true;
                }
            }
            $this->actualizarEstado($idPedido, 'recibido');
            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
