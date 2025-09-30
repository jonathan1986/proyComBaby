<?php
namespace Modules\CatalogoProductos\Models;

use PDO;

class Categoria
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function crear(array $data): int
    {
        $sql = "INSERT INTO categorias (nombre, descripcion, id_categoria_padre) VALUES (:nombre, :descripcion, :id_categoria_padre)";
        $stmt = $this->db->prepare($sql);
        $idPadre = $data['id_categoria_padre'] ?? null;
        if ($idPadre === '' || $idPadre === null) {
            $idPadre = null;
        }
        $stmt->bindValue(':nombre', htmlspecialchars($data['nombre'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), PDO::PARAM_STR);
        $stmt->bindValue(':descripcion', htmlspecialchars($data['descripcion'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), PDO::PARAM_STR);
        $stmt->bindValue(':id_categoria_padre', $idPadre, $idPadre === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->execute();
        return (int)$this->db->lastInsertId();
    }

    public function obtenerPorId(int $id): ?array
    {
        $sql = "SELECT * FROM categorias WHERE id_categoria = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $categoria = $stmt->fetch(PDO::FETCH_ASSOC);
        return $categoria ?: null;
    }

    public function actualizar(int $id, array $data): bool
    {
        $sql = "UPDATE categorias SET nombre = :nombre, descripcion = :descripcion, id_categoria_padre = :id_categoria_padre WHERE id_categoria = :id";
        $stmt = $this->db->prepare($sql);
        $idPadre = $data['id_categoria_padre'] ?? null;
        if ($idPadre === '' || $idPadre === null) {
            $idPadre = null;
        }
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':nombre', htmlspecialchars($data['nombre'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), PDO::PARAM_STR);
        $stmt->bindValue(':descripcion', htmlspecialchars($data['descripcion'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), PDO::PARAM_STR);
        $stmt->bindValue(':id_categoria_padre', $idPadre, $idPadre === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function eliminar(int $id): bool
    {
        $sql = "DELETE FROM categorias WHERE id_categoria = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    public function obtenerJerarquia(): array
    {
        $sql = "SELECT * FROM categorias ORDER BY id_categoria_padre, nombre";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
