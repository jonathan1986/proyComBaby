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
        $sql = "INSERT INTO proveedores (nombre, contacto, telefono, email, direccion, ciudad, ruc, id_pais, descripcion, pagina_web, tipo_proveedor, regimen_iva, es_sin_animo_lucro, representante_legal, estado, fecha_creacion, usuario_creacion) VALUES (:nombre, :contacto, :telefono, :email, :direccion, :ciudad, :ruc, :id_pais, :descripcion, :pagina_web, :tipo_proveedor, :regimen_iva, :es_sin_animo_lucro, :representante_legal, :estado, NOW(), :usuario_creacion)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nombre' => htmlspecialchars($data['nombre'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            ':contacto' => htmlspecialchars($data['contacto'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            ':telefono' => htmlspecialchars($data['telefono'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            ':email' => htmlspecialchars($data['email'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            ':direccion' => htmlspecialchars($data['direccion'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            ':ciudad' => htmlspecialchars($data['ciudad'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            ':ruc' => htmlspecialchars($data['ruc'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            ':id_pais' => !empty($data['id_pais']) ? (int)$data['id_pais'] : null,
            ':descripcion' => htmlspecialchars($data['descripcion'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            ':pagina_web' => htmlspecialchars($data['pagina_web'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            ':tipo_proveedor' => $data['tipo_proveedor'] ?? 'Distribuidor',
            ':regimen_iva' => !empty($data['regimen_iva']) ? $data['regimen_iva'] : null,
            ':es_sin_animo_lucro' => isset($data['es_sin_animo_lucro']) ? (int)$data['es_sin_animo_lucro'] : 0,
            ':representante_legal' => htmlspecialchars($data['representante_legal'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            ':estado' => $data['estado'] ?? 1,
            ':usuario_creacion' => htmlspecialchars($data['usuario_creacion'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function obtenerPorId(int $id): ?array
    {
        $sql = "SELECT p.*, pa.nombre AS nombre_pais FROM proveedores p LEFT JOIN paises pa ON p.id_pais = pa.id_pais WHERE p.id_proveedor = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $proveedor = $stmt->fetch(PDO::FETCH_ASSOC);
        return $proveedor ?: null;
    }

    public function actualizar(int $id, array $data): bool
    {
        $sql = "UPDATE proveedores SET nombre = :nombre, contacto = :contacto, telefono = :telefono, email = :email, direccion = :direccion, ciudad = :ciudad, ruc = :ruc, id_pais = :id_pais, descripcion = :descripcion, pagina_web = :pagina_web, tipo_proveedor = :tipo_proveedor, regimen_iva = :regimen_iva, es_sin_animo_lucro = :es_sin_animo_lucro, representante_legal = :representante_legal, estado = :estado, fecha_actualizacion = NOW(), usuario_actualizacion = :usuario_actualizacion WHERE id_proveedor = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':nombre' => htmlspecialchars($data['nombre'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            ':contacto' => htmlspecialchars($data['contacto'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            ':telefono' => htmlspecialchars($data['telefono'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            ':email' => htmlspecialchars($data['email'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            ':direccion' => htmlspecialchars($data['direccion'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            ':ciudad' => htmlspecialchars($data['ciudad'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            ':ruc' => htmlspecialchars($data['ruc'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            ':id_pais' => !empty($data['id_pais']) ? (int)$data['id_pais'] : null,
            ':descripcion' => htmlspecialchars($data['descripcion'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            ':pagina_web' => htmlspecialchars($data['pagina_web'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            ':tipo_proveedor' => $data['tipo_proveedor'] ?? 'Distribuidor',
            ':regimen_iva' => !empty($data['regimen_iva']) ? $data['regimen_iva'] : null,
            ':es_sin_animo_lucro' => isset($data['es_sin_animo_lucro']) ? (int)$data['es_sin_animo_lucro'] : 0,
            ':representante_legal' => htmlspecialchars($data['representante_legal'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
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
        $sql = "SELECT p.id_proveedor, p.nombre, p.contacto, p.telefono, p.email, p.direccion, p.ciudad, p.ruc, p.id_pais, p.estado, pa.nombre AS nombre_pais FROM proveedores p LEFT JOIN paises pa ON p.id_pais = pa.id_pais ORDER BY p.nombre LIMIT 200";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscar(string $term): array
    {
        $term = trim($term);
        if ($term === '') return [];
        // Escapar comodines % y _ y barras invertidas
        $safe = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term);
        $like = "%{$safe}%";
        $sql = "SELECT p.id_proveedor, p.nombre, p.contacto, p.telefono, p.email, p.direccion, p.ciudad, p.ruc, p.id_pais, p.estado, pa.nombre AS nombre_pais
                FROM proveedores p
                LEFT JOIN paises pa ON p.id_pais = pa.id_pais
                WHERE p.nombre LIKE :q
                ORDER BY p.nombre
                LIMIT 200";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':q', $like, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
