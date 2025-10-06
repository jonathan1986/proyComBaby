<?php
// filepath: modules/CatalogoProductos/Controllers/PedidoReabastecimientoController.php
namespace Modules\CatalogoProductos\Controllers;

use Modules\CatalogoProductos\Models\PedidoReabastecimiento;
use PDO;

class PedidoReabastecimientoController
{
    private PedidoReabastecimiento $model;

    public function __construct(PDO $db)
    {
        $this->model = new PedidoReabastecimiento($db);
    }

    public function crear(array $cabecera, array $detalle): int
    {
        return $this->model->crearPedido($cabecera, $detalle);
    }

    public function obtener(int $id): ?array
    {
        return $this->model->obtener($id);
    }

    public function listar(array $filtros = [], int $limit = 50, int $offset = 0): array
    {
        return $this->model->listar($filtros, $limit, $offset);
    }

    public function actualizarEstado(int $id, string $estado): bool
    {
        return $this->model->actualizarEstado($id, $estado);
    }

    public function recibir(int $idPedido): bool
    {
        return $this->model->recibir($idPedido);
    }
}
