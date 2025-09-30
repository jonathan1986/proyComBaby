<?php
namespace Modules\CatalogoProductos\Models;

use PDO;

class Atributo
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function crear(array $data): int
    {
        // Sanitizar y validar en el controlador
        $sql = "INSERT INTO atributos (nombre, tipo, estado) VALUES (:nombre, :tipo, :estado)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nombre' => htmlspecialchars($data['nombre'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            ':tipo' => $data['tipo'],
            ':estado' => $data['estado'] ?? 1
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function obtenerPorId(int $id): ?array
    {
        $sql = "SELECT * FROM atributos WHERE id_atributo = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $atributo = $stmt->fetch(PDO::FETCH_ASSOC);
        return $atributo ?: null;
    }

    public function actualizar(int $id, array $data): bool
    {
        $sql = "UPDATE atributos SET nombre = :nombre, tipo = :tipo, estado = :estado WHERE id_atributo = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':nombre' => htmlspecialchars($data['nombre'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            ':tipo' => $data['tipo'],
            ':estado' => $data['estado'] ?? 1
        ]);
    }

    public function eliminar(int $id): bool
    {
        $sql = "DELETE FROM atributos WHERE id_atributo = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    public function listar(): array
    {
        $sql = "SELECT * FROM atributos ORDER BY nombre";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
