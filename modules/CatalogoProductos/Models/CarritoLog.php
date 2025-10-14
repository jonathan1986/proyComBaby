<?php
declare(strict_types=1);

namespace Modules\CatalogoProductos\Models;

use PDO;

class CarritoLog
{
    private PDO $db;

    public function __construct(PDO $pdo)
    {
        $this->db = $pdo;
    }

    /**
     * Registra un evento en carrito_logs
     * @param int $idCarrito
     * @param string $accion One of crear, actualizar_cabecera, agregar_item, actualizar_item, eliminar_item, vaciar, eliminar_carrito, merge, expirar
     * @param array|null $detalles Datos adicionales serializados como JSON
     * @param int|null $usuarioId
     * @param string|null $sessionToken
     * @param string|null $ip
     * @param string|null $userAgent
     */
    public function registrar(int $idCarrito, string $accion, ?array $detalles = null, ?int $usuarioId = null, ?string $sessionToken = null, ?string $ip = null, ?string $userAgent = null): void
    {
        $sql = "INSERT INTO carrito_logs (id_carrito, accion, detalles, usuario_id, session_token, ip, user_agent)
                VALUES (:id_carrito, :accion, :detalles, :usuario_id, :session_token, :ip, :ua)";
        $st = $this->db->prepare($sql);
        $st->bindValue(':id_carrito', $idCarrito, PDO::PARAM_INT);
        $st->bindValue(':accion', $accion);
        $st->bindValue(':detalles', $detalles ? json_encode($detalles, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) : null, $detalles ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $st->bindValue(':usuario_id', $usuarioId, $usuarioId ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $st->bindValue(':session_token', $sessionToken, $sessionToken ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $st->bindValue(':ip', $ip, $ip ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $st->bindValue(':ua', $userAgent, $userAgent ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $st->execute();
    }
}
