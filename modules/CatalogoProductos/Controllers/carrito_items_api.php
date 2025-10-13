<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../vendor/autoload.php';

use Modules\CatalogoProductos\Controllers\CarritoItemController;
use Modules\CatalogoProductos\Controllers\CarritoController;

header('Content-Type: application/json; charset=utf-8');

try {
    $config = require __DIR__ . '/../../../config/database.php';
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['user'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $ctrl = new CarritoItemController($pdo);
    $carCtrl = new CarritoController($pdo);

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
            echo json_encode(['success'=>$ok]);
            exit;

        case 'DELETE':
            if (($idUsuario || $token) && !$carCtrl->perteneceA($idCarrito, $idUsuario, $token)) {
                http_response_code(403); echo json_encode(['success'=>false,'error'=>'Acceso denegado']); exit;
            }
            $idProducto = isset($_GET['id_producto']) ? (int)$_GET['id_producto'] : 0;
            $ok = $ctrl->eliminar($idCarrito, $idProducto);
            echo json_encode(['success'=>$ok]);
            exit;

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
