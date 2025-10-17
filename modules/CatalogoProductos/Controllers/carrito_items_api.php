<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../vendor/autoload.php';

use Modules\CatalogoProductos\Controllers\CarritoItemController;
use Modules\CatalogoProductos\Controllers\CarritoController;
use Modules\CatalogoProductos\Models\CarritoLog;

header('Content-Type: application/json; charset=utf-8');

try {
    $dbConf = require __DIR__ . '/../../../config/database.php';
    $appConf = require __DIR__ . '/../../../config/app.php';
    $dsn = "mysql:host={$dbConf['host']};dbname={$dbConf['dbname']};charset={$dbConf['charset']}";
    $pdo = new PDO($dsn, $dbConf['user'], $dbConf['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Límite máximo centralizado en config/app.php
    $MAX_LINEAS = (int)($appConf['carrito']['max_lineas'] ?? 200);
    $ctrl = new CarritoItemController($pdo, $MAX_LINEAS);
    $logger = new CarritoLog($pdo);
    $carCtrl = new CarritoController($pdo);

    // Recalcular impuestos en modo multi
    $recalcImpuestos = function(int $idCar) use ($pdo): void {
        try {
            $stModo = $pdo->prepare("SELECT impuestos_modo FROM carritos WHERE id_carrito = :id");
            $stModo->execute([':id'=>$idCar]);
            $modo = (string)$stModo->fetchColumn();
            if ($modo === 'multi') {
                $call = $pdo->prepare("CALL sp_recalcular_impuestos_carrito(:id)");
                $call->execute([':id'=>$idCar]);
                // Consumir posibles resultados adicionales de CALL
                while ($call->nextRowset()) { /* no-op */ }
            }
        } catch (Throwable $e) {
            error_log('[carrito_items_api][recalcImpuestos] '.$e->getMessage());
        }
    };

    $idCarrito = isset($_GET['id_carrito']) ? (int)$_GET['id_carrito'] : 0;
    if ($idCarrito <= 0) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'id_carrito requerido']); exit; }

    // Utilidad: cuerpo JSON opcional
    $rawBody = file_get_contents('php://input');
    $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
    $isJson = stripos($contentType, 'application/json') !== false;
    $jsonBody = $isJson && $rawBody ? json_decode($rawBody, true) : null;

    // Chequeo opcional de pertenencia en todas las operaciones salvo GET sin filtros
    $idUsuario = isset($_GET['usuario']) ? (int)$_GET['usuario'] : null;
    $token = isset($_GET['session_token']) ? (string)$_GET['session_token'] : null;

    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            if (isset($_GET['count'])) {
                // Consulta agregada directa para optimizar (evita traer todas las filas)
                $st = $pdo->prepare("SELECT COUNT(*) AS lineas, COALESCE(SUM(cantidad),0) AS cantidad_total FROM carrito_items WHERE id_carrito = :c");
                $st->bindValue(':c', $idCarrito, PDO::PARAM_INT);
                $st->execute();
                $row = $st->fetch(PDO::FETCH_ASSOC) ?: ['lineas'=>0,'cantidad_total'=>0];
                echo json_encode(['success'=>true,'lineas'=>(int)$row['lineas'],'cantidad_total'=>(int)$row['cantidad_total']]);
                exit;
            }
            $items = $ctrl->listar($idCarrito);
            echo json_encode(['success'=>true,'items'=>$items]);
            exit;

        case 'POST':
            if (($idUsuario || $token) && !$carCtrl->perteneceA($idCarrito, $idUsuario, $token)) {
                http_response_code(403); echo json_encode(['success'=>false,'error'=>'Acceso denegado']); exit;
            }
            $src = $jsonBody ?? $_POST;
            $idProducto = isset($src['id_producto']) ? (int)$src['id_producto'] : 0;
            $cantidad = isset($src['cantidad']) ? (int)$src['cantidad'] : 0;
            $precioUnit = isset($src['precio_unit']) && $src['precio_unit'] !== '' ? (float)$src['precio_unit'] : null;
            $idItem = $ctrl->agregar($idCarrito, $idProducto, $cantidad, $precioUnit);
            // Log
            $logger->registrar($idCarrito, 'agregar_item', [
                'id_producto'=>$idProducto,
                'cantidad'=>$cantidad,
                'precio_unit'=>$precioUnit,
                'id_item'=>$idItem
            ], $idUsuario, $token, $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null);
            $recalcImpuestos($idCarrito);
            echo json_encode(['success'=>true,'id_item'=>$idItem]);
            exit;

        case 'PUT':
        case 'PATCH':
            if (($idUsuario || $token) && !$carCtrl->perteneceA($idCarrito, $idUsuario, $token)) {
                http_response_code(403); echo json_encode(['success'=>false,'error'=>'Acceso denegado']); exit;
            }
            $src = $jsonBody ?? [];
            if (!$jsonBody) { parse_str($rawBody, $src); }
            $idProducto = isset($_GET['id_producto']) ? (int)$_GET['id_producto'] : (int)($src['id_producto'] ?? 0);
            $cantidad = isset($src['cantidad']) ? (int)$src['cantidad'] : 0;
            $precioUnit = isset($src['precio_unit']) && $src['precio_unit'] !== '' ? (float)$src['precio_unit'] : null;
            $ok = $ctrl->actualizar($idCarrito, $idProducto, $cantidad, $precioUnit);
            // Log
            $logger->registrar($idCarrito, 'actualizar_item', [
                'id_producto'=>$idProducto,
                'cantidad'=>$cantidad,
                'precio_unit'=>$precioUnit
            ], $idUsuario, $token, $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null);
            $recalcImpuestos($idCarrito);
            echo json_encode(['success'=>$ok]);
            exit;

        case 'DELETE':
            if (($idUsuario || $token) && !$carCtrl->perteneceA($idCarrito, $idUsuario, $token)) {
                http_response_code(403); echo json_encode(['success'=>false,'error'=>'Acceso denegado']); exit;
            }
            if (isset($_GET['empty'])) {
                // Vaciar el carrito completo: elimina todas las filas en una sola operación
                $st = $pdo->prepare("DELETE FROM carrito_items WHERE id_carrito = :c");
                $st->bindValue(':c', $idCarrito, PDO::PARAM_INT);
                $ok = $st->execute();
                // Recalcular totales del carrito (poner en cero)
                $pdo->prepare("UPDATE carritos SET subtotal=0, descuento_total=0, impuesto_total=0, total=0 WHERE id_carrito=:c")
                    ->execute([':c'=>$idCarrito]);
                // Log
                $logger->registrar($idCarrito, 'vaciar', null, $idUsuario, $token, $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null);
                $recalcImpuestos($idCarrito);
                echo json_encode(['success'=>$ok,'emptied'=>true]);
                exit;
            } else {
                $idProducto = isset($_GET['id_producto']) ? (int)$_GET['id_producto'] : 0;
                $ok = $ctrl->eliminar($idCarrito, $idProducto);
                // Log
                $logger->registrar($idCarrito, 'eliminar_item', [
                    'id_producto'=>$idProducto
                ], $idUsuario, $token, $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null);
                $recalcImpuestos($idCarrito);
                echo json_encode(['success'=>$ok]);
                exit;
            }

        default:
            http_response_code(405);
            echo json_encode(['success'=>false,'error'=>'Método no permitido']);
            exit;
    }
} catch (Throwable $e) {
    error_log('[carrito_items_api] ' . $e->getMessage());
    if ($e instanceof InvalidArgumentException || $e instanceof \InvalidArgumentException) {
        http_response_code(400);
        echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
    } else {
        http_response_code(500);
        echo json_encode(['success'=>false,'error'=>'Error del servidor']);
    }
}
