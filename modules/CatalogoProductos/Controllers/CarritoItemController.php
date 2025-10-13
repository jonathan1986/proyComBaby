<?php
declare(strict_types=1);

namespace Modules\CatalogoProductos\Controllers;

use Modules\CatalogoProductos\Models\CarritoItem;
use PDO;

class CarritoItemController
{
    private CarritoItem $model;

    public function __construct(PDO $db)
    {
        $this->model = new CarritoItem($db);
    }

    public function listar(int $idCarrito): array
    {
        return $this->model->listarPorCarrito($idCarrito);
    }

    public function agregar(int $idCarrito, int $idProducto, int $cantidad, ?float $precioUnit = null): int
    {
        $idCarrito = $this->sanitizeId($idCarrito);
        $idProducto = $this->sanitizeId($idProducto);
        $cantidad = $this->sanitizeCantidad($cantidad);
        $precioUnit = $precioUnit === null ? null : $this->sanitizePrecio($precioUnit);
        return $this->model->agregar($idCarrito, $idProducto, $cantidad, $precioUnit);
    }

    public function actualizar(int $idCarrito, int $idProducto, int $cantidad, ?float $precioUnit = null): bool
    {
        $idCarrito = $this->sanitizeId($idCarrito);
        $idProducto = $this->sanitizeId($idProducto);
        $cantidad = $this->sanitizeCantidad($cantidad);
        $precioUnit = $precioUnit === null ? null : $this->sanitizePrecio($precioUnit);
        return $this->model->actualizarCantidad($idCarrito, $idProducto, $cantidad, $precioUnit);
    }

    public function eliminar(int $idCarrito, int $idProducto): bool
    {
        $idCarrito = $this->sanitizeId($idCarrito);
        $idProducto = $this->sanitizeId($idProducto);
        return $this->model->eliminar($idCarrito, $idProducto);
    }

    private function sanitizeId($v): int
    {
        if (!is_numeric($v)) throw new \InvalidArgumentException('ID inválido');
        $n = (int)$v;
        if ($n <= 0) throw new \InvalidArgumentException('ID inválido');
        return $n;
    }

    private function sanitizeCantidad($c): int
    {
        if (!is_numeric($c)) throw new \InvalidArgumentException('Cantidad inválida');
        $n = (int)$c;
        if ($n < 1 || $n > 999) throw new \InvalidArgumentException('Cantidad fuera de rango');
        return $n;
    }

    private function sanitizePrecio($p): float
    {
        if (!is_numeric($p)) throw new \InvalidArgumentException('Precio inválido');
        $f = (float)$p;
        if ($f < 0) throw new \InvalidArgumentException('Precio inválido');
        return round($f, 2);
    }
}
