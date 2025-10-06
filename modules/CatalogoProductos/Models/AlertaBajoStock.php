<?php
// filepath: modules/CatalogoProductos/Models/AlertaBajoStock.php
namespace Modules\CatalogoProductos\Models;

use PDO;

class AlertaBajoStock
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function listarPendientes(array $filtros = [], int $limit = 50, int $offset = 0): array
    {
        $where = ['a.atendida = 0'];
        $params = [];
        if (!empty($filtros['id_producto'])) { $where[] = 'a.id_producto = :id_producto'; $params[':id_producto'] = (int)$filtros['id_producto']; }
        $sql = "SELECT a.*, p.nombre AS producto
                FROM alertas_bajo_stock a
                INNER JOIN productos p ON p.id_producto = a.id_producto
                WHERE " . implode(' AND ', $where) . "
                ORDER BY a.fecha DESC, a.id_alerta DESC
                LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
        $stmt->bindValue(':limit', max(1, min(200, (int)$limit)), PDO::PARAM_INT);
        $stmt->bindValue(':offset', max(0, (int)$offset), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function marcarAtendida(int $idAlerta): bool
    {
        $stmt = $this->db->prepare('UPDATE alertas_bajo_stock SET atendida = 1 WHERE id_alerta = :id');
        return $stmt->execute([':id' => $idAlerta]);
    }

    public function regenerarParaProducto(int $idProducto): void
    {
        // Recalcula y si corresponde crea una alerta (si no existe activa)
        $stmt = $this->db->prepare('SELECT COALESCE(SUM(cantidad),0) AS stock FROM inventario_movimientos WHERE id_producto = :id');
        $stmt->execute([':id' => $idProducto]);
        $stock = (int)($stmt->fetch(PDO::FETCH_ASSOC)['stock'] ?? 0);
        $row = $this->db->prepare('SELECT stock_minimo FROM productos WHERE id_producto = :id');
        $row->execute([':id' => $idProducto]);
        $min = (int)($row->fetch(PDO::FETCH_ASSOC)['stock_minimo'] ?? 0);
        if ($stock <= $min) {
            $exists = $this->db->prepare('SELECT 1 FROM alertas_bajo_stock WHERE id_producto = :id AND atendida = 0 LIMIT 1');
            $exists->execute([':id' => $idProducto]);
            if (!$exists->fetchColumn()) {
                $ins = $this->db->prepare('INSERT INTO alertas_bajo_stock (id_producto, stock_actual, stock_minimo) VALUES (:id, :stock, :min)');
                $ins->execute([':id' => $idProducto, ':stock' => $stock, ':min' => $min]);
            }
        }
    }
}
