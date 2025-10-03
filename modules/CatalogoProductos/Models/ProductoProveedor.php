<?php
// filepath: modules/CatalogoProductos/Models/ProductoProveedor.php
namespace Modules\CatalogoProductos\Models;

use PDO;

class ProductoProveedor
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function obtenerProveedoresDeProducto(int $idProducto): array
    {
        $sql = "SELECT p.id_proveedor, p.nombre
                FROM productos_proveedores pp
                INNER JOIN proveedores p ON p.id_proveedor = pp.id_proveedor
                WHERE pp.id_producto = :id
                ORDER BY p.nombre";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $idProducto]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function setProveedoresDeProducto(int $idProducto, array $ids): bool
    {
        $this->db->beginTransaction();
        try {
            $del = $this->db->prepare('DELETE FROM productos_proveedores WHERE id_producto = :id');
            $del->execute([':id' => $idProducto]);
            if (!empty($ids)) {
                $ins = $this->db->prepare('INSERT INTO productos_proveedores (id_producto, id_proveedor) VALUES (:id_producto, :id_proveedor)');
                foreach ($ids as $idProv) {
                    $ins->execute([
                        ':id_producto' => $idProducto,
                        ':id_proveedor' => (int)$idProv
                    ]);
                }
            }
            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            error_log('setProveedoresDeProducto error: ' . $e->getMessage());
            return false;
        }
    }
}
