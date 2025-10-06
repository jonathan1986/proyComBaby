<?php
// filepath: modules/CatalogoProductos/Controllers/AlertaBajoStockController.php
namespace Modules\CatalogoProductos\Controllers;

use Modules\CatalogoProductos\Models\AlertaBajoStock;
use PDO;

class AlertaBajoStockController
{
    private AlertaBajoStock $model;

    public function __construct(PDO $db)
    {
        $this->model = new AlertaBajoStock($db);
    }

    public function listar(array $filtros = [], int $limit = 50, int $offset = 0): array
    {
        return $this->model->listarPendientes($filtros, $limit, $offset);
    }

    public function marcarAtendida(int $idAlerta): bool
    {
        return $this->model->marcarAtendida($idAlerta);
    }

    public function regenerar(int $idProducto): void
    {
        $this->model->regenerarParaProducto($idProducto);
    }
}
