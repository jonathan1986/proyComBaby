<?php
declare(strict_types=1);

namespace Modules\CatalogoProductos\Models;

use PDO;
use PDOException;

class Carrito
{
    private PDO $db;

    public function __construct(PDO $pdo)
    {
        $this->db = $pdo;
    }

    public function obtenerPorId(int $id): ?array
    {
        $st = $this->db->prepare("SELECT * FROM carritos WHERE id_carrito = :id LIMIT 1");
        $st->bindValue(':id', $id, PDO::PARAM_INT);
        $st->execute();
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function obtenerPorSessionToken(string $token): ?array
    {
        $st = $this->db->prepare("SELECT * FROM carritos WHERE session_token = :t AND estado = 'activo' ORDER BY id_carrito DESC LIMIT 1");
        $st->bindValue(':t', $token, PDO::PARAM_STR);
        $st->execute();
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function crear(array $data): int
    {
        $sql = "INSERT INTO carritos (session_token, id_cliente, nombre_contacto, telefono_contacto, email_contacto, moneda, observaciones)
                VALUES (:session_token, :id_cliente, :nombre_contacto, :telefono_contacto, :email_contacto, :moneda, :observaciones)";
        $st = $this->db->prepare($sql);
        $st->execute([
            ':session_token'     => (string)($data['session_token'] ?? ''),
            ':id_cliente'        => $data['id_cliente'] ?? null,
            ':nombre_contacto'   => $data['nombre_contacto'] ?? null,
            ':telefono_contacto' => $data['telefono_contacto'] ?? null,
            ':email_contacto'    => $data['email_contacto'] ?? null,
            ':moneda'            => $data['moneda'] ?? 'PYG',
            ':observaciones'     => $data['observaciones'] ?? null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function asegurarActivoPorToken(string $token): int
    {
        $car = $this->obtenerPorSessionToken($token);
        if ($car) return (int)$car['id_carrito'];
        return $this->crear(['session_token' => $token]);
    }

    public function actualizarCabecera(int $idCarrito, array $data): bool
    {
        // Permitir actualizar campos seleccionados
        $sql = "UPDATE carritos
                   SET nombre_contacto = :nombre_contacto,
                       telefono_contacto = :telefono_contacto,
                       email_contacto = :email_contacto,
                       moneda = :moneda,
                       observaciones = :observaciones,
                       cupon_codigo = :cupon_codigo,
                       cupon_descuento = :cupon_descuento,
                       envio_monto = :envio_monto
                 WHERE id_carrito = :id AND estado = 'activo'";
        $st = $this->db->prepare($sql);
        return $st->execute([
            ':id'                => $idCarrito,
            ':nombre_contacto'   => $data['nombre_contacto'] ?? null,
            ':telefono_contacto' => $data['telefono_contacto'] ?? null,
            ':email_contacto'    => $data['email_contacto'] ?? null,
            ':moneda'            => $data['moneda'] ?? 'PYG',
            ':observaciones'     => $data['observaciones'] ?? null,
            ':cupon_codigo'      => $data['cupon_codigo'] ?? null,
            ':cupon_descuento'   => (float)($data['cupon_descuento'] ?? 0),
            ':envio_monto'       => (float)($data['envio_monto'] ?? 0),
        ]);
    }

    public function agregarOActualizarItem(int $idCarrito, array $item, string $modo = 'sumar'): bool
    {
        // Verificar estado del carrito
        $st = $this->db->prepare("SELECT estado FROM carritos WHERE id_carrito = :id");
        $st->execute([':id' => $idCarrito]);
        $estado = $st->fetchColumn();
        if ($estado !== 'activo') {
            throw new PDOException('Carrito no activo');
        }

        $idProducto = (int)($item['id_producto'] ?? 0);
        $cantidad = max(1, (int)($item['cantidad'] ?? 1));
        // Si no se provee precio, traer desde productos
        $precioUnit = isset($item['precio_unitario']) ? (float)$item['precio_unitario'] : $this->precioProducto($idProducto);
        $desc = (float)($item['descuento_monto'] ?? 0);
        $tasa = (float)($item['tasa_impuesto'] ?? 0);

        // Upsert: si ya existe la línea, sumar o setear cantidad
        if ($modo === 'sumar') {
            // Intentar insertar, si ya existe, sumar cantidad
            $sql = "INSERT INTO carritos_detalle (id_carrito, id_producto, cantidad, precio_unitario, descuento_monto, tasa_impuesto)
                    VALUES (:id_carrito, :id_producto, :cantidad, :precio_unitario, :descuento_monto, :tasa_impuesto)
                    ON DUPLICATE KEY UPDATE cantidad = cantidad + VALUES(cantidad),
                                            precio_unitario = VALUES(precio_unitario),
                                            descuento_monto = VALUES(descuento_monto),
                                            tasa_impuesto = VALUES(tasa_impuesto)";
        } else { // 'set'
            $sql = "INSERT INTO carritos_detalle (id_carrito, id_producto, cantidad, precio_unitario, descuento_monto, tasa_impuesto)
                    VALUES (:id_carrito, :id_producto, :cantidad, :precio_unitario, :descuento_monto, :tasa_impuesto)
                    ON DUPLICATE KEY UPDATE cantidad = VALUES(cantidad),
                                            precio_unitario = VALUES(precio_unitario),
                                            descuento_monto = VALUES(descuento_monto),
                                            tasa_impuesto = VALUES(tasa_impuesto)";
        }
        $st = $this->db->prepare($sql);
        return $st->execute([
            ':id_carrito'     => $idCarrito,
            ':id_producto'    => $idProducto,
            ':cantidad'       => $cantidad,
            ':precio_unitario'=> $precioUnit,
            ':descuento_monto'=> $desc,
            ':tasa_impuesto'  => $tasa,
        ]);
    }

    public function actualizarCantidad(int $idCarrito, int $idProducto, int $cantidad): bool
    {
        $cantidad = max(0, $cantidad);
        if ($cantidad === 0) return $this->eliminarItem($idCarrito, $idProducto);

        $sql = "UPDATE carritos_detalle cd
                   JOIN carritos c ON c.id_carrito = cd.id_carrito AND c.estado = 'activo'
                   SET cd.cantidad = :cant
                 WHERE cd.id_carrito = :id AND cd.id_producto = :p";
        $st = $this->db->prepare($sql);
        return $st->execute([':cant' => $cantidad, ':id' => $idCarrito, ':p' => $idProducto]);
    }

    public function eliminarItem(int $idCarrito, int $idProducto): bool
    {
        $st = $this->db->prepare("DELETE cd FROM carritos_detalle cd JOIN carritos c ON c.id_carrito = cd.id_carrito AND c.estado='activo' WHERE cd.id_carrito = :id AND cd.id_producto = :p");
        return $st->execute([':id' => $idCarrito, ':p' => $idProducto]);
    }

    public function vaciar(int $idCarrito): bool
    {
        $st = $this->db->prepare("DELETE cd FROM carritos_detalle cd JOIN carritos c ON c.id_carrito = cd.id_carrito AND c.estado='activo' WHERE cd.id_carrito = :id");
        return $st->execute([':id' => $idCarrito]);
    }

    public function obtenerDetalleImagen(int $idCarrito): array
    {
        $st = $this->db->prepare("SELECT id_carrito, id_producto, nombre, cantidad, precio_unitario, descuento_monto, tasa_impuesto, total_linea, archivo_imagen
                                     FROM vista_carrito_detalle_imagen WHERE id_carrito = :id");
        $st->bindValue(':id', $idCarrito, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function resumen(int $idCarrito): ?array
    {
        $st = $this->db->prepare("SELECT * FROM carritos WHERE id_carrito = :id LIMIT 1");
        $st->bindValue(':id', $idCarrito, PDO::PARAM_INT);
        $st->execute();
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function precioProducto(int $idProducto): float
    {
        $st = $this->db->prepare("SELECT precio FROM productos WHERE id_producto = :p");
        $st->execute([':p' => $idProducto]);
        $precio = $st->fetchColumn();
        return $precio !== false ? (float)$precio : 0.0;
    }
}
