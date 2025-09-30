<?php
namespace Modules\CatalogoProductos\Models;

use PDO;

class Proveedor
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function crear(array $data): int
    {
        $sql = "INSERT INTO proveedores (nombre, contacto, telefono, email, direccion, ciudad, ruc, estado, fecha_creacion, usuario_creacion) VALUES (:nombre, :contacto, :telefono, :email, :direccion, :ciudad, :ruc, :estado, NOW(), :usuario_creacion)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nombre' => htmlspecialchars($data['nombre'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            ':contacto' => htmlspecialchars($data['contacto'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            ':telefono' => htmlspecialchars($data['telefono'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            ':email' => htmlspecialchars($data['email'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            ':direccion' => htmlspecialchars($data['direccion'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            ':ciudad' => htmlspecialchars($data['ciudad'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            ':ruc' => htmlspecialchars($data['ruc'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            ':estado' => $data['estado'] ?? 1,
            ':usuario_creacion' => htmlspecialchars($data['usuario_creacion'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function obtenerPorId(int $id): ?array
    {
        $sql = "SELECT * FROM proveedores WHERE id_proveedor = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $proveedor = $stmt->fetch(PDO::FETCH_ASSOC);
        return $proveedor ?: null;
    }

    public function actualizar(int $id, array $data): bool
    {
        $sql = "UPDATE proveedores SET nombre = :nombre, contacto = :contacto, telefono = :telefono, email = :email, direccion = :direccion, ciudad = :ciudad, ruc = :ruc, estado = :estado, fecha_actualizacion = NOW(), usuario_actualizacion = :usuario_actualizacion WHERE id_proveedor = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':nombre' => htmlspecialchars($data['nombre'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            ':contacto' => htmlspecialchars($data['contacto'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            ':telefono' => htmlspecialchars($data['telefono'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            ':email' => htmlspecialchars($data['email'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            ':direccion' => htmlspecialchars($data['direccion'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            ':ciudad' => htmlspecialchars($data['ciudad'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            ':ruc' => htmlspecialchars($data['ruc'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            ':estado' => $data['estado'] ?? 1,
            ':usuario_actualizacion' => htmlspecialchars($data['usuario_actualizacion'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
        ]);
    }

    public function eliminar(int $id): bool
    {
        $sql = "DELETE FROM proveedores WHERE id_proveedor = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    public function listar(): array
    {
        $sql = "SELECT * FROM proveedores ORDER BY nombre";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
