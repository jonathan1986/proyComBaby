<?php
// filepath: modules/CatalogoProductos/Models/StockRepository.php
namespace Modules\CatalogoProductos\Models;

use PDO;

class StockRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getStockDisponible(int $idProducto): int
    {
        $stmt = $this->db->prepare('SELECT COALESCE(SUM(cantidad),0) AS stock FROM inventario_movimientos WHERE id_producto = :id');
        $stmt->execute([':id' => $idProducto]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['stock'] ?? 0);
    }

    public function listarStock(array $filtros = [], int $limit = 50, int $offset = 0): array
    {
        $where = [];
        $params = [];
        if (!empty($filtros['texto'])) {
            $safe = '%' . str_replace(['\\','%','_'], ['\\\\','\\%','\\_'], (string)$filtros['texto']) . '%';
            $where[] = '(p.nombre LIKE :texto OR p.id_producto = :idexacto)';
            $params[':texto'] = $safe;
            $params[':idexacto'] = (int)$filtros['texto'];
        }
        if (isset($filtros['estado'])) {
            $where[] = 'p.estado = :estado';
            $params[':estado'] = (int)$filtros['estado'];
        }
        if (isset($filtros['bajo_stock'])) {
            if ((int)$filtros['bajo_stock'] === 1) {
                $where[] = 'COALESCE(SUM(m.cantidad),0) <= p.stock_minimo';
            }
        }
        $sql = "SELECT p.id_producto, p.nombre, p.stock_minimo, p.estado, COALESCE(SUM(m.cantidad),0) AS stock_disponible
                FROM productos p
                LEFT JOIN inventario_movimientos m ON m.id_producto = p.id_producto
                " . (count($where) ? ('WHERE ' . implode(' AND ', $where)) : '') . "
                GROUP BY p.id_producto, p.nombre, p.stock_minimo, p.estado
                ORDER BY p.nombre ASC
                LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
        $stmt->bindValue(':limit', max(1, min(200, (int)$limit)), PDO::PARAM_INT);
        $stmt->bindValue(':offset', max(0, (int)$offset), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
