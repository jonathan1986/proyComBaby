<?php
// filepath: modules/CatalogoProductos/Controllers/ProductoProveedorController.php
namespace Modules\CatalogoProductos\Controllers;

use Modules\CatalogoProductos\Models\ProductoProveedor;
use PDO;

class ProductoProveedorController
{
    private ProductoProveedor $model;

    public function __construct(PDO $db)
    {
        $this->model = new ProductoProveedor($db);
    }

    public function listarPorProducto(int $idProducto): array
    {
        return $this->model->obtenerProveedoresDeProducto($idProducto);
    }

    public function guardar(int $idProducto, array $ids): bool
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        return $this->model->setProveedoresDeProducto($idProducto, $ids);
    }
}
