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
		$sql = "INSERT INTO carritos (id_usuario, session_token, moneda, impuestos_modo, descuento_pct, descuento_monto, estado)
				VALUES (:id_usuario, :session_token, :moneda, :impuestos_modo, :descuento_pct, :descuento_monto, :estado)";
		$st = $this->db->prepare($sql);
		$st->bindValue(':id_usuario', $data['id_usuario'] ?? null, isset($data['id_usuario']) ? PDO::PARAM_INT : PDO::PARAM_NULL);
		$st->bindValue(':session_token', $data['session_token'] ?? null, isset($data['session_token']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
		$st->bindValue(':moneda', $data['moneda'] ?? 'USD');
		$st->bindValue(':impuestos_modo', $data['impuestos_modo'] ?? 'simple');
		$st->bindValue(':descuento_pct', (float)($data['descuento_pct'] ?? 0));
		$st->bindValue(':descuento_monto', (float)($data['descuento_monto'] ?? 0));
		$st->bindValue(':estado', $data['estado'] ?? 'abierto');
		$st->execute();
		return (int)$this->db->lastInsertId();
	}

	public function obtenerPorId(int $id): ?array
	{
		$st = $this->db->prepare('SELECT * FROM carritos WHERE id_carrito = :id');
		$st->bindValue(':id', $id, PDO::PARAM_INT);
		$st->execute();
		$row = $st->fetch(PDO::FETCH_ASSOC);
		return $row ?: null;
	}

	public function obtenerPorUsuarioOToken(?int $idUsuario, ?string $sessionToken): ?array
	{
		if ($idUsuario) {
			$st = $this->db->prepare("SELECT * FROM carritos WHERE id_usuario = :u AND estado = 'abierto' ORDER BY fecha_actualizacion DESC LIMIT 1");
			$st->execute([':u'=>$idUsuario]);
			$row = $st->fetch(PDO::FETCH_ASSOC);
			if ($row) return $row;
		}
		if ($sessionToken) {
			$st = $this->db->prepare("SELECT * FROM carritos WHERE session_token = :t AND estado = 'abierto' ORDER BY fecha_actualizacion DESC LIMIT 1");
			$st->execute([':t'=>$sessionToken]);
			$row = $st->fetch(PDO::FETCH_ASSOC);
			if ($row) return $row;
		}
		return null;
	}

	public function actualizarCabecera(int $id, array $data): bool
	{
		$fields = [];$params = [':id'=>$id];
		foreach (['id_usuario','session_token','moneda','impuestos_modo','descuento_pct','descuento_monto','estado'] as $f) {
			if (array_key_exists($f, $data)) {
				$fields[] = "$f = :$f"; $params[":$f"] = $data[$f];
			}
		}
		if (!$fields) return true;
		$sql = 'UPDATE carritos SET '.implode(',', $fields).', fecha_actualizacion = NOW() WHERE id_carrito = :id';
		$st = $this->db->prepare($sql);
		return $st->execute($params);
	}

	public function eliminar(int $id): bool
	{
		$st = $this->db->prepare('DELETE FROM carritos WHERE id_carrito = :id');
		return $st->execute([':id'=>$id]);
	}
}

