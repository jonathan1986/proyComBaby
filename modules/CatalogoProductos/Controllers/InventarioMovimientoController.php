<?php
// filepath: modules/CatalogoProductos/Controllers/InventarioMovimientoController.php
namespace Modules\CatalogoProductos\Controllers;

use Modules\CatalogoProductos\Models\InventarioMovimiento;
use PDO;

class InventarioMovimientoController
{
    private InventarioMovimiento $model;

    public function __construct(PDO $db)
    {
        $this->model = new InventarioMovimiento($db);
    }

    public function crear(array $data): bool
    {
        return $this->model->crearMovimiento($data);
    }

    public function listar(array $filtros = [], int $limit = 50, int $offset = 0): array
    {
        return $this->model->listar($filtros, $limit, $offset);
    }

    public function ultimosPorProducto(int $idProducto, int $limit = 10): array
    {
        return $this->model->obtenerUltimosPorProducto($idProducto, $limit);
    }
}
