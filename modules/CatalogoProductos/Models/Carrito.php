<?php
declare(strict_types=1);

namespace Modules\CatalogoProductos\Models;

use PDO;

class Carrito
{
    private PDO $db;

    public function __construct(PDO $pdo)
    {
        $this->db = $pdo;
    }

    public function crear(array $data): int
    {
        $sql = "INSERT INTO carritos (id_usuario, session_token, estado, moneda, impuesto_pct, descuento_pct, descuento_monto)
                VALUES (:id_usuario, :session_token, 'abierto', :moneda, :impuesto_pct, :descuento_pct, :descuento_monto)";
        $st = $this->db->prepare($sql);
        $st->bindValue(':id_usuario', $data['id_usuario'] ?? null, $data['id_usuario'] ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $st->bindValue(':session_token', $data['session_token'] ?? null, $data['session_token'] ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $st->bindValue(':moneda', $data['moneda']);
        $st->bindValue(':impuesto_pct', $data['impuesto_pct']);
        $st->bindValue(':descuento_pct', $data['descuento_pct']);
        $st->bindValue(':descuento_monto', $data['descuento_monto']);
        $st->execute();
        return (int)$this->db->lastInsertId();
    }

    public function obtenerPorId(int $id): ?array
    {
        $st = $this->db->prepare("SELECT * FROM carritos WHERE id_carrito = :id LIMIT 1");
        $st->bindValue(':id', $id, PDO::PARAM_INT);
        $st->execute();
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function obtenerPorUsuarioOToken(?int $idUsuario, ?string $token): ?array
    {
        if ($idUsuario) {
            $st = $this->db->prepare("SELECT * FROM carritos WHERE id_usuario = :u AND estado = 'abierto' ORDER BY id_carrito DESC LIMIT 1");
            $st->bindValue(':u', $idUsuario, PDO::PARAM_INT);
            $st->execute();
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if ($row) return $row;
        }
        if ($token) {
            $st = $this->db->prepare("SELECT * FROM carritos WHERE session_token = :t AND estado = 'abierto' ORDER BY id_carrito DESC LIMIT 1");
            $st->bindValue(':t', $token, PDO::PARAM_STR);
            $st->execute();
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if ($row) return $row;
        }
        return null;
    }

    public function actualizarCabecera(int $id, array $data): bool
    {
        // Actualiza cabecera
        $sql = "UPDATE carritos
                   SET impuesto_pct = :impuesto_pct,
                       descuento_pct = :descuento_pct,
                       descuento_monto = :descuento_monto,
                       estado = :estado
                 WHERE id_carrito = :id";
        $st = $this->db->prepare($sql);
        $st->bindValue(':impuesto_pct', $data['impuesto_pct']);
        $st->bindValue(':descuento_pct', $data['descuento_pct']);
        $st->bindValue(':descuento_monto', $data['descuento_monto']);
        $st->bindValue(':estado', $data['estado']);
        $st->bindValue(':id', $id, PDO::PARAM_INT);
        $ok = $st->execute();
        if (!$ok) return false;

        // Recalcula totales inmediatamente con los nuevos parámetros
        $subtotal = 0.0;
        $qSub = $this->db->prepare("SELECT COALESCE(SUM(subtotal_linea),0) AS s FROM carrito_items WHERE id_carrito = :id");
        $qSub->bindValue(':id', $id, PDO::PARAM_INT);
        $qSub->execute();
        $row = $qSub->fetch(PDO::FETCH_ASSOC);
        if ($row && isset($row['s'])) {
            $subtotal = (float)$row['s'];
        }

        $descPct = (float)$data['descuento_pct'];
        $descMto = (float)$data['descuento_monto'];
        $impPct  = (float)$data['impuesto_pct'];

        $descTotal = $descMto > 0 ? $descMto : round($subtotal * ($descPct/100), 2);
        $base = max($subtotal - $descTotal, 0);
        $imp = round($base * ($impPct/100), 2);
        $total = $base + $imp;

        $u = $this->db->prepare("UPDATE carritos
                                     SET subtotal = :subtotal,
                                         descuento_total = :desc_total,
                                         impuesto_total = :imp,
                                         total = :total
                                   WHERE id_carrito = :id");
        $u->bindValue(':subtotal', $subtotal);
        $u->bindValue(':desc_total', $descTotal);
        $u->bindValue(':imp', $imp);
        $u->bindValue(':total', $total);
        $u->bindValue(':id', $id, PDO::PARAM_INT);
        return $u->execute();
    }

    public function eliminar(int $id): bool
    {
        // Eliminación dura; si prefieres cancelado lógico, usa actualizarCabecera
        $st = $this->db->prepare("DELETE FROM carritos WHERE id_carrito = :id");
        $st->bindValue(':id', $id, PDO::PARAM_INT);
        return $st->execute();
    }

    /**
     * Verifica si un carrito pertenece a un usuario o token de sesión proporcionado.
     * Ambos filtros son opcionales; si ninguno se provee, retorna false.
     */
    public function perteneceA(int $idCarrito, ?int $idUsuario, ?string $token): bool
    {
        if (!$idUsuario && !$token) {
            return false;
        }
        $sql = "SELECT 1
                  FROM carritos
                 WHERE id_carrito = :id
                   AND ( (id_usuario = :u AND :u IS NOT NULL)
                      OR (session_token = :t AND :t IS NOT NULL) )
                 LIMIT 1";
        $st = $this->db->prepare($sql);
        if ($idUsuario) {
            $st->bindValue(':u', $idUsuario, PDO::PARAM_INT);
        } else {
            $st->bindValue(':u', null, PDO::PARAM_NULL);
        }
        if ($token) {
            $st->bindValue(':t', $token, PDO::PARAM_STR);
        } else {
            $st->bindValue(':t', null, PDO::PARAM_NULL);
        }
        $st->bindValue(':id', $idCarrito, PDO::PARAM_INT);
        $st->execute();
        return (bool)$st->fetchColumn();
    }
}
