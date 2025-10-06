<?php
declare(strict_types=1);

namespace Modules\CatalogoProductos\Controllers;

use Modules\CatalogoProductos\Models\Carrito;
use PDO;

class CarritoController
{
    private Carrito $model;

    public function __construct(PDO $db)
    {
        $this->model = new Carrito($db);
    }

    public function asegurarActivo(string $sessionToken): int
    {
        return $this->model->asegurarActivoPorToken($sessionToken);
    }

    public function obtenerPorId(int $id): ?array { return $this->model->obtenerPorId($id); }
    public function obtenerPorToken(string $t): ?array { return $this->model->obtenerPorSessionToken($t); }

    public function actualizarCabecera(int $id, array $data): bool { return $this->model->actualizarCabecera($id, $data); }

    public function agregarItem(int $idCarrito, array $item, string $modo = 'sumar'): bool
    { return $this->model->agregarOActualizarItem($idCarrito, $item, $modo); }

    public function actualizarCantidad(int $idCarrito, int $idProducto, int $cantidad): bool
    { return $this->model->actualizarCantidad($idCarrito, $idProducto, $cantidad); }

    public function eliminarItem(int $idCarrito, int $idProducto): bool
    { return $this->model->eliminarItem($idCarrito, $idProducto); }

    public function vaciar(int $idCarrito): bool { return $this->model->vaciar($idCarrito); }

    public function detalleConImagen(int $idCarrito): array { return $this->model->obtenerDetalleImagen($idCarrito); }
    public function resumen(int $idCarrito): ?array { return $this->model->resumen($idCarrito); }
}
