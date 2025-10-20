<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../vendor/autoload.php';

use Modules\CatalogoProductos\Models\Carrito;
use Modules\CatalogoProductos\Controllers\CarritoController;

header('Content-Type: application/json; charset=utf-8');

try {
	$dbConf = require __DIR__ . '/../../../config/database.php';
	$appConf = require __DIR__ . '/../../../config/app.php';
	$dsn = "mysql:host={$dbConf['host']};dbname={$dbConf['dbname']};charset={$dbConf['charset']}";
	$pdo = new PDO($dsn, $dbConf['user'], $dbConf['password'], [
		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
	]);

	$carritos = new Carrito($pdo);
	$ctrl = new CarritoController($pdo);

	$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
	$raw = file_get_contents('php://input');
	$ct = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
	$isJson = stripos($ct, 'application/json') !== false;
	$body = $isJson && $raw ? json_decode($raw, true) : null;

	$idCarrito = isset($_GET['id_carrito']) ? (int)$_GET['id_carrito'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);
	$idUsuario = isset($_GET['usuario']) ? (int)$_GET['usuario'] : null;
	$token = isset($_GET['session_token']) ? (string)$_GET['session_token'] : null;

	$recalc = function(int $id) use ($pdo): void {
		try {
			$st = $pdo->prepare('SELECT impuestos_modo FROM carritos WHERE id_carrito = :id');
			$st->execute([':id'=>$id]);
			$modo = (string)$st->fetchColumn();
			if ($modo === 'multi') {
				$call = $pdo->prepare('CALL sp_recalcular_impuestos_carrito(:id)');
				$call->execute([':id'=>$id]);
				while ($call->nextRowset()) { /* flush */ }
			}
		} catch (Throwable $e) {
			error_log('[carrito_api][recalc] '.$e->getMessage());
		}
	};

	switch ($method) {
		case 'GET': {
			$attachBreakdown = function(array $row) use ($pdo) {
				if (($row['impuestos_modo'] ?? 'simple') === 'multi') {
					$st = $pdo->prepare("SELECT ci.id_impuesto, i.codigo, i.nombre, ci.monto FROM carritos_impuestos ci JOIN impuestos i ON i.id_impuesto = ci.id_impuesto WHERE ci.id_carrito = :id ORDER BY i.codigo");
					$st->execute([':id'=>(int)$row['id_carrito']]);
					$row['impuestos_desglose'] = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
				}
				return $row;
			};
			if ($idCarrito > 0) {
				$row = $carritos->obtenerPorId($idCarrito);
				if (!$row) { http_response_code(404); echo json_encode(['success'=>false,'error'=>'No encontrado']); break; }
				$row = $attachBreakdown($row);
				echo json_encode(['success'=>true,'carrito'=>$row]);
				break;
			}
			// Obtener por usuario/token
			$row = $carritos->obtenerPorUsuarioOToken($idUsuario, $token);
			if (!$row) { http_response_code(404); echo json_encode(['success'=>false,'error'=>'No encontrado']); break; }
			$row = $attachBreakdown($row);
			echo json_encode(['success'=>true,'carrito'=>$row]);
			break;
		}
		case 'POST': {
			$src = $body ?? $_POST;
			$data = [
				'id_usuario' => isset($src['id_usuario']) && $src['id_usuario'] !== '' ? (int)$src['id_usuario'] : null,
				'session_token' => $src['session_token'] ?? null,
				'moneda' => $src['moneda'] ?? 'USD',
				'impuestos_modo' => $src['impuestos_modo'] ?? 'simple',
				'descuento_pct' => isset($src['descuento_pct']) ? (float)$src['descuento_pct'] : 0,
				'descuento_monto' => isset($src['descuento_monto']) ? (float)$src['descuento_monto'] : 0,
				'estado' => $src['estado'] ?? 'abierto',
			];
			$id = $carritos->crear($data);
			$recalc($id);
			$row = $carritos->obtenerPorId($id) ?: ['id_carrito'=>$id] ;
			// Adjuntar desglose si corresponde
			if (($row['impuestos_modo'] ?? 'simple') === 'multi') {
				$st = $pdo->prepare("SELECT ci.id_impuesto, i.codigo, i.nombre, ci.monto FROM carritos_impuestos ci JOIN impuestos i ON i.id_impuesto = ci.id_impuesto WHERE ci.id_carrito = :id ORDER BY i.codigo");
				$st->execute([':id'=>$id]);
				$row['impuestos_desglose'] = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
			}
			echo json_encode(['success'=>true,'carrito'=>$row]);
			break;
		}
		case 'PUT':
		case 'PATCH': {
			if ($idCarrito <= 0) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'id_carrito requerido']); break; }
			if (($idUsuario || $token) && !$ctrl->perteneceA($idCarrito, $idUsuario, $token)) {
				http_response_code(403); echo json_encode(['success'=>false,'error'=>'Acceso denegado']); break;
			}
			$src = $body ?? [];
			if (!$body) { parse_str($raw, $src); }
			$ok = $carritos->actualizarCabecera($idCarrito, $src);
			$recalc($idCarrito);
			echo json_encode(['success'=>$ok]);
			break;
		}
		case 'DELETE': {
			if ($idCarrito <= 0) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'id_carrito requerido']); break; }
			if (($idUsuario || $token) && !$ctrl->perteneceA($idCarrito, $idUsuario, $token)) {
				http_response_code(403); echo json_encode(['success'=>false,'error'=>'Acceso denegado']); break;
			}
			$ok = $carritos->eliminar($idCarrito);
			echo json_encode(['success'=>$ok]);
			break;
		}
		default:
			http_response_code(405); echo json_encode(['success'=>false,'error'=>'Método no permitido']);
	}
} catch (Throwable $e) {
	http_response_code(500);
	echo json_encode(['success'=>false,'error'=>'Error en carrito_api: '.$e->getMessage()]);
}

