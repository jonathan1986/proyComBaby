<?php
// filepath: modules/CatalogoProductos/Controllers/StockController.php
namespace Modules\CatalogoProductos\Controllers;

use Modules\CatalogoProductos\Models\StockRepository;
use PDO;

class StockController
{
    private StockRepository $repo;

    public function __construct(PDO $db)
    {
        $this->repo = new StockRepository($db);
    }

    public function disponiblePorProducto(int $idProducto): int
    {
        return $this->repo->getStockDisponible($idProducto);
    }

    public function listarStock(array $filtros = [], int $limit = 50, int $offset = 0): array
    {
        return $this->repo->listarStock($filtros, $limit, $offset);
    }
}
