<?php
namespace Modules\CatalogoProductos\Models;

use PDO;

class Producto
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function crear(array $data): int
    {
        $sql = "INSERT INTO productos (nombre, descripcion, precio, stock, stock_minimo, estado) VALUES (:nombre, :descripcion, :precio, :stock, :stock_minimo, :estado)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nombre' => htmlspecialchars($data['nombre'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            ':descripcion' => htmlspecialchars($data['descripcion'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            ':precio' => $data['precio'],
            ':stock' => $data['stock'],
            ':stock_minimo' => $data['stock_minimo'] ?? 0,
            ':estado' => $data['estado']
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function obtenerPorId(int $id): ?array
    {
        $sql = "SELECT * FROM productos WHERE id_producto = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $producto = $stmt->fetch(PDO::FETCH_ASSOC);
        return $producto ?: null;
    }

    public function actualizar(int $id, array $data): bool
    {
        $sql = "UPDATE productos SET nombre = :nombre, descripcion = :descripcion, precio = :precio, stock = :stock, stock_minimo = :stock_minimo, estado = :estado WHERE id_producto = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':nombre' => htmlspecialchars($data['nombre'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            ':descripcion' => htmlspecialchars($data['descripcion'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            ':precio' => $data['precio'],
            ':stock' => $data['stock'],
            ':stock_minimo' => $data['stock_minimo'] ?? 0,
            ':estado' => $data['estado']
        ]);
    }

    public function eliminar(int $id): bool
    {
        $sql = "DELETE FROM productos WHERE id_producto = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    public function buscar(array $filtros): array
    {
        $sql = "SELECT p.* FROM productos p WHERE 1=1";
        $params = [];
        if (!empty($filtros['nombre'])) {
            $sql .= " AND p.nombre LIKE :nombre";
            $params[':nombre'] = '%' . $filtros['nombre'] . '%';
        }
        if (!empty($filtros['categoria'])) {
            $sql .= " AND EXISTS (SELECT 1 FROM productos_categorias pc WHERE pc.id_producto = p.id_producto AND pc.id_categoria = :categoria)";
            $params[':categoria'] = $filtros['categoria'];
        }
        if (!empty($filtros['precio_min'])) {
            $sql .= " AND p.precio >= :precio_min";
            $params[':precio_min'] = $filtros['precio_min'];
        }
        if (!empty($filtros['precio_max'])) {
            $sql .= " AND p.precio <= :precio_max";
            $params[':precio_max'] = $filtros['precio_max'];
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
