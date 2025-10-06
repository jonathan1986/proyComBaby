<?php
// filepath: modules/CatalogoProductos/Models/InventarioMovimiento.php
namespace Modules\CatalogoProductos\Models;

use PDO;

class InventarioMovimiento
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function crearMovimiento(array $data): bool
    {
        $idProducto = (int)($data['id_producto'] ?? 0);
        $tipo = (string)($data['tipo'] ?? '');
        $cantidad = (int)($data['cantidad'] ?? 0);
        $motivo = trim((string)($data['motivo'] ?? ''));
        $idPedido = isset($data['id_pedido']) ? (int)$data['id_pedido'] : null;
        $usuario = trim((string)($data['usuario'] ?? ''));
        $fecha = $data['fecha'] ?? null; // opcional

        if ($idProducto <= 0) { throw new \InvalidArgumentException('id_producto inválido'); }
        if (!in_array($tipo, ['entrada','salida','ajuste'], true)) { throw new \InvalidArgumentException('tipo inválido'); }
        if ($tipo !== 'ajuste' && $cantidad <= 0) { throw new \InvalidArgumentException('cantidad debe ser > 0'); }
        if ($tipo === 'ajuste' && $cantidad === 0) { throw new \InvalidArgumentException('cantidad de ajuste no puede ser 0'); }

        // Normaliza signo
        if ($tipo === 'salida' && $cantidad > 0) $cantidad = -$cantidad;

        // Valida stock negativo si aplica
        $allowNegative = (int)(getenv('APP_STOCK_ALLOW_NEGATIVE') ?: 0) === 1;
        if (!$allowNegative) {
            $curr = $this->getStockDisponible($idProducto);
            $next = $curr + $cantidad;
            if ($next < 0) {
                throw new \RuntimeException('La salida dejaría el stock negativo');
            }
        }

        $sql = "INSERT INTO inventario_movimientos (id_producto, fecha, tipo, cantidad, motivo, id_pedido, usuario)
                VALUES (:id_producto, :fecha, :tipo, :cantidad, :motivo, :id_pedido, :usuario)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id_producto' => $idProducto,
            ':fecha' => $fecha ?: date('Y-m-d H:i:s'),
            ':tipo' => $tipo,
            ':cantidad' => $cantidad,
            ':motivo' => $motivo,
            ':id_pedido' => $idPedido,
            ':usuario' => $usuario,
        ]);
    }

    public function listar(array $filtros = [], int $limit = 50, int $offset = 0): array
    {
        $where = [];
        $params = [];
        if (!empty($filtros['id_producto'])) { $where[] = 'm.id_producto = :id_producto'; $params[':id_producto'] = (int)$filtros['id_producto']; }
        if (!empty($filtros['id_pedido'])) { $where[] = 'm.id_pedido = :id_pedido'; $params[':id_pedido'] = (int)$filtros['id_pedido']; }
        if (!empty($filtros['tipo'])) { $where[] = 'm.tipo = :tipo'; $params[':tipo'] = (string)$filtros['tipo']; }
        if (!empty($filtros['fecha_desde'])) { $where[] = 'm.fecha >= :fdesde'; $params[':fdesde'] = (string)$filtros['fecha_desde']; }
        if (!empty($filtros['fecha_hasta'])) { $where[] = 'm.fecha <= :fhasta'; $params[':fhasta'] = (string)$filtros['fecha_hasta']; }
        if (!empty($filtros['motivo'])) { $where[] = 'm.motivo LIKE :motivo'; $params[':motivo'] = '%' . str_replace(['\\','%','_'], ['\\\\','\\%','\\_'], (string)$filtros['motivo']) . '%'; }
        $sql = "SELECT m.*, p.nombre AS producto
                FROM inventario_movimientos m
                INNER JOIN productos p ON p.id_producto = m.id_producto
                " . (count($where) ? ('WHERE ' . implode(' AND ', $where)) : '') . "
                ORDER BY m.fecha DESC, m.id_movimiento DESC
                LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
        $stmt->bindValue(':limit', max(1, min(200, (int)$limit)), PDO::PARAM_INT);
        $stmt->bindValue(':offset', max(0, (int)$offset), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerUltimosPorProducto(int $idProducto, int $limit = 10): array
    {
        $stmt = $this->db->prepare("SELECT * FROM inventario_movimientos WHERE id_producto = :id ORDER BY fecha DESC, id_movimiento DESC LIMIT :limit");
        $stmt->bindValue(':id', $idProducto, PDO::PARAM_INT);
        $stmt->bindValue(':limit', max(1, min(50, (int)$limit)), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getStockDisponible(int $idProducto): int
    {
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(cantidad),0) AS stock FROM inventario_movimientos WHERE id_producto = :id");
        $stmt->execute([':id' => $idProducto]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['stock'] ?? 0);
    }
}
