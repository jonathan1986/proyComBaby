<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../vendor/autoload.php';

use Modules\CatalogoProductos\Controllers\CarritoController;

header('Content-Type: application/json; charset=utf-8');

try {
    $config = require __DIR__ . '/../../../config/database.php';
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['user'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $ctrl = new CarritoController($pdo);

    // Utilidad: lee cuerpo JSON si content-type es application/json
    $rawBody = file_get_contents('php://input');
    $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
    $isJson = stripos($contentType, 'application/json') !== false;
    $jsonBody = $isJson && $rawBody ? json_decode($rawBody, true) : null;

    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            if (isset($_GET['id'])) {
                $id = (int)$_GET['id'];
                $car = $ctrl->obtener($id);
                if (!$car) { http_response_code(404); echo json_encode(['success'=>false,'error'=>'Carrito no encontrado']); exit; }
                // Chequeo opcional de pertenencia
                $idUsuario = isset($_GET['usuario']) ? (int)$_GET['usuario'] : null;
                $token = isset($_GET['session_token']) ? (string)$_GET['session_token'] : null;
                if (($idUsuario || $token) && !$ctrl->perteneceA($id, $idUsuario, $token)) {
                    http_response_code(403); echo json_encode(['success'=>false,'error'=>'Acceso denegado']); exit;
                }
                echo json_encode(['success'=>true,'carrito'=>$car]);
                exit;
            }
            $idUsuario = isset($_GET['usuario']) ? (int)$_GET['usuario'] : null;
            $token = isset($_GET['session_token']) ? (string)$_GET['session_token'] : null;
            $car = $ctrl->obtenerPorUsuarioOToken($idUsuario, $token);
            if (!$car) { http_response_code(404); echo json_encode(['success'=>false,'error'=>'Carrito no encontrado']); exit; }
            echo json_encode(['success'=>true,'carrito'=>$car]);
            exit;

        case 'POST':
            $src = $jsonBody ?? $_POST;
            $payload = [
                'id_usuario' => $src['id_usuario'] ?? null,
                'session_token' => $src['session_token'] ?? null,
                'moneda' => $src['moneda'] ?? 'USD',
                'impuesto_pct' => $src['impuesto_pct'] ?? 0,
                'descuento_pct' => $src['descuento_pct'] ?? 0,
                'descuento_monto' => $src['descuento_monto'] ?? 0,
            ];
            $id = $ctrl->crear($payload);
            $car = $ctrl->obtener($id);
            echo json_encode(['success'=>true,'carrito'=>$car]);
            exit;

        case 'PATCH':
        case 'PUT':
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            if ($id <= 0) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'ID inválido']); exit; }
            $src = $jsonBody ?? [];
            if (!$jsonBody) { parse_str($rawBody, $src); }
            // Chequeo opcional de pertenencia
            $idUsuario = isset($_GET['usuario']) ? (int)$_GET['usuario'] : (isset($src['usuario']) ? (int)$src['usuario'] : null);
            $token = $_GET['session_token'] ?? ($src['session_token'] ?? null);
            if (($idUsuario || $token) && !$ctrl->perteneceA($id, $idUsuario, $token)) {
                http_response_code(403); echo json_encode(['success'=>false,'error'=>'Acceso denegado']); exit;
            }
            $ok = $ctrl->actualizarCabecera($id, $src);
            echo json_encode(['success'=>$ok]);
            exit;

        case 'DELETE':
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            if ($id <= 0) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'ID inválido']); exit; }
            // Chequeo opcional de pertenencia
            $idUsuario = isset($_GET['usuario']) ? (int)$_GET['usuario'] : null;
            $token = isset($_GET['session_token']) ? (string)$_GET['session_token'] : null;
            if (($idUsuario || $token) && !$ctrl->perteneceA($id, $idUsuario, $token)) {
                http_response_code(403); echo json_encode(['success'=>false,'error'=>'Acceso denegado']); exit;
            }
            $ok = $ctrl->eliminar($id);
            echo json_encode(['success'=>$ok]);
            exit;

        default:
            http_response_code(405);
            echo json_encode(['success'=>false,'error'=>'Método no permitido']);
            exit;
    }
} catch (Throwable $e) {
    error_log('[carrito_api] ' . $e->getMessage());
    if ($e instanceof InvalidArgumentException || $e instanceof \InvalidArgumentException) {
        http_response_code(400);
        echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
    } else {
        http_response_code(500);
        echo json_encode(['success'=>false,'error'=>'Error del servidor']);
    }
}
