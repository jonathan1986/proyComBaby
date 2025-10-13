<?php
declare(strict_types=1);

namespace Modules\CatalogoProductos\Models;

use PDO;

class CarritoItem
{
    private PDO $db;

    public function __construct(PDO $pdo)
    {
        $this->db = $pdo;
    }

    public function agregar(int $idCarrito, int $idProducto, int $cantidad, ?float $precioUnit = null): int
    {
        $sql = "INSERT INTO carrito_items (id_carrito, id_producto, cantidad, precio_unit, subtotal_linea)
                VALUES (:id_carrito, :id_producto, :cantidad, :precio_unit, 0)";
        $st = $this->db->prepare($sql);
        $st->bindValue(':id_carrito', $idCarrito, PDO::PARAM_INT);
        $st->bindValue(':id_producto', $idProducto, PDO::PARAM_INT);
        $st->bindValue(':cantidad', $cantidad, PDO::PARAM_INT);
        if ($precioUnit === null) {
            $st->bindValue(':precio_unit', null, PDO::PARAM_NULL);
        } else {
            $st->bindValue(':precio_unit', $precioUnit);
        }
        $st->execute();
        return (int)$this->db->lastInsertId();
    }

    public function actualizarCantidad(int $idCarrito, int $idProducto, int $cantidad, ?float $precioUnit = null): bool
    {
        $sql = "UPDATE carrito_items
                   SET cantidad = :cantidad, precio_unit = COALESCE(:precio_unit, precio_unit)
                 WHERE id_carrito = :id_carrito AND id_producto = :id_producto";
        $st = $this->db->prepare($sql);
        $st->bindValue(':cantidad', $cantidad, PDO::PARAM_INT);
        if ($precioUnit === null) {
            $st->bindValue(':precio_unit', null, PDO::PARAM_NULL);
        } else {
            $st->bindValue(':precio_unit', $precioUnit);
        }
        $st->bindValue(':id_carrito', $idCarrito, PDO::PARAM_INT);
        $st->bindValue(':id_producto', $idProducto, PDO::PARAM_INT);
        return $st->execute();
    }

    public function eliminar(int $idCarrito, int $idProducto): bool
    {
        $st = $this->db->prepare("DELETE FROM carrito_items WHERE id_carrito = :c AND id_producto = :p");
        $st->bindValue(':c', $idCarrito, PDO::PARAM_INT);
        $st->bindValue(':p', $idProducto, PDO::PARAM_INT);
        return $st->execute();
    }

    public function listarPorCarrito(int $idCarrito): array
    {
        $st = $this->db->prepare("SELECT * FROM vista_carrito_items WHERE id_carrito = :c ORDER BY id_item ASC");
        $st->bindValue(':c', $idCarrito, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }
}
