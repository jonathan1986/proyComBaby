<?php
declare(strict_types=1);

namespace Modules\CatalogoProductos\Controllers;

use PDO;

class CarritoController
{
	private PDO $db;

	public function __construct(PDO $pdo)
	{
		$this->db = $pdo;
	}

	/**
	 * Verifica si el carrito pertenece al usuario o al session_token provisto.
	 * Si se pasan ambos, cualquiera de los dos que coincida valida pertenencia.
	 */
	public function perteneceA(int $idCarrito, ?int $idUsuario = null, ?string $sessionToken = null): bool
	{
		if ($idCarrito <= 0) return false;
		$conds = ['id_carrito = :id'];
		$params = [':id' => $idCarrito];
		if ($idUsuario) { $conds[] = 'id_usuario = :u'; $params[':u'] = $idUsuario; }
		if ($sessionToken) { $conds[] = 'session_token = :t'; $params[':t'] = $sessionToken; }
		if (count($conds) === 1) {
			// No hay usuario ni token para validar, por seguridad negar
			return false;
		}
		$sql = 'SELECT 1 FROM carritos WHERE ' . implode(' OR ', $conds) . ' LIMIT 1';
		$st = $this->db->prepare($sql);
		foreach ($params as $k=>$v) { $st->bindValue($k, $v); }
		$st->execute();
		return (bool)$st->fetchColumn();
	}
}

