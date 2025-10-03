<?php
declare(strict_types=1);

namespace Modules\CatalogoProductos\Models;

use PDO;

class Producto
{
    private PDO $db;

    public function __construct(PDO $pdo)
    {
        $this->db = $pdo;
    }

    public function listar(): array
    {
        $sql = "SELECT id_producto, nombre, precio, stock, stock_minimo, estado
                  FROM productos
              ORDER BY id_producto DESC
                 LIMIT 200";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscar(string $term): array
    {
        $isNumericId = ctype_digit($term);
        $idExact = $isNumericId ? (int)$term : 0;

        // Escapa comodines para LIKE
        $q = $this->escapeLike($term);

        $sql = "SELECT id_producto, nombre, precio, stock, stock_minimo, estado
                  FROM productos
                 WHERE (nombre LIKE :q
                        OR (:idExact > 0 AND id_producto = :idExact))
              ORDER BY nombre ASC
                 LIMIT 200";

        $st = $this->db->prepare($sql);
        $st->bindValue(':q', '%' . $q . '%', PDO::PARAM_STR);
        $st->bindValue(':idExact', $idExact, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId(int $id): ?array
    {
        $st = $this->db->prepare("SELECT * FROM productos WHERE id_producto = :id LIMIT 1");
        $st->bindValue(':id', $id, PDO::PARAM_INT);
        $st->execute();
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function crear(array $data): int
    {
        $sql = "INSERT INTO productos (nombre, descripcion, precio, stock, stock_minimo, estado)
                VALUES (:nombre, :descripcion, :precio, :stock, :stock_minimo, :estado)";
        $st = $this->db->prepare($sql);
        $st->execute([
            ':nombre'        => htmlspecialchars($data['nombre'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            ':descripcion'   => htmlspecialchars($data['descripcion'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            ':precio'        => (float)($data['precio'] ?? 0),
            ':stock'         => (int)($data['stock'] ?? 0),
            ':stock_minimo'  => (int)($data['stock_minimo'] ?? 0),
            ':estado'        => (int)($data['estado'] ?? 0),
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function actualizar(int $id, array $data): bool
    {
        $sql = "UPDATE productos
                   SET nombre=:nombre, descripcion=:descripcion, precio=:precio,
                       stock=:stock, stock_minimo=:stock_minimo, estado=:estado
                 WHERE id_producto=:id";
        $st = $this->db->prepare($sql);
        return $st->execute([
            ':id'            => $id,
            ':nombre'        => htmlspecialchars($data['nombre'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            ':descripcion'   => htmlspecialchars($data['descripcion'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            ':precio'        => (float)($data['precio'] ?? 0),
            ':stock'         => (int)($data['stock'] ?? 0),
            ':stock_minimo'  => (int)($data['stock_minimo'] ?? 0),
            ':estado'        => (int)($data['estado'] ?? 0),
        ]);
    }

    public function eliminar(int $id): bool
    {
        $st = $this->db->prepare("DELETE FROM productos WHERE id_producto = :id");
        return $st->execute([':id' => $id]);
    }

    private function escapeLike(string $s): string
    {
        // Escapa \ % _
        return strtr($s, [
            '\\' => '\\\\',
            '%'  => '\%',
            '_'  => '\_',
        ]);
    }
}
