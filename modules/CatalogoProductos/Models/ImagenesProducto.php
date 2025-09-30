<?php
namespace Modules\CatalogoProductos\Models;

use PDO;

class ImagenesProducto
{
    private PDO $db;
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function crear(array $data): int
    {
        $sql = "INSERT INTO imagenes_productos (id_producto, archivo_imagen, principal, estado) VALUES (:id_producto, :archivo_imagen, :principal, :estado)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id_producto' => $data['id_producto'],
            ':archivo_imagen' => htmlspecialchars($data['archivo_imagen'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            ':principal' => !empty($data['principal']) ? 1 : 0,
            ':estado' => $data['estado'] ?? 1
        ]);
        return (int)$this->db->lastInsertId();
    }

    // ...otros métodos CRUD seguros...
}
