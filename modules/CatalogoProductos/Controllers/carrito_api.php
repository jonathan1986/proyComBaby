<?php
// modules/CatalogoProductos/Controllers/carrito_api.php
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

    // Resolver carrito por token de sesión o por id
    $sessionToken = $_GET['session_token'] ?? ($_POST['session_token'] ?? '');
    $idCarrito = isset($_GET['id_carrito']) ? (int)$_GET['id_carrito'] : 0;

    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            if ($idCarrito <= 0 && $sessionToken) {
                $car = $ctrl->obtenerPorToken($sessionToken);
                if (!$car) { echo json_encode(['success'=>true,'carrito'=>null,'detalle'=>[]]); exit; }
                $idCarrito = (int)$car['id_carrito'];
            }
            if ($idCarrito > 0) {
                $car = $ctrl->obtenerPorId($idCarrito);
                if (!$car) { http_response_code(404); echo json_encode(['success'=>false,'error'=>'Carrito no encontrado']); exit; }
                $detalle = $ctrl->detalleConImagen($idCarrito);
                echo json_encode(['success'=>true,'carrito'=>$car,'detalle'=>$detalle]);
                exit;
            }
            echo json_encode(['success'=>false,'error'=>'Parámetros insuficientes']);
            exit;

        case 'POST':
            // Crear o asegurar carrito activo por session_token, y agregar item
            $input = $_POST;
            if (empty($input)) {
                $raw = file_get_contents('php://input');
                $json = json_decode($raw, true);
                if (is_array($json)) $input = $json;
            }
            $sessionToken = (string)($input['session_token'] ?? $sessionToken);
            if (!$sessionToken) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'session_token requerido']); exit; }
            $idCarrito = $ctrl->asegurarActivo($sessionToken);

            if (isset($input['accion']) && $input['accion'] === 'actualizar_cabecera') {
                $ok = $ctrl->actualizarCabecera($idCarrito, $input);
                echo json_encode(['success'=>$ok,'id_carrito'=>$idCarrito]);
                exit;
            }

            // Agregar ítem
            $modo = in_array(($input['modo'] ?? 'sumar'), ['sumar','set'], true) ? $input['modo'] : 'sumar';
            $ok = $ctrl->agregarItem($idCarrito, [
                'id_producto'     => (int)($input['id_producto'] ?? 0),
                'cantidad'        => (int)($input['cantidad'] ?? 1),
                'precio_unitario' => $input['precio_unitario'] ?? null,
                'descuento_monto' => $input['descuento_monto'] ?? 0,
                'tasa_impuesto'   => $input['tasa_impuesto'] ?? 0,
            ], $modo);
            $detalle = $ctrl->detalleConImagen($idCarrito);
            $resumen = $ctrl->resumen($idCarrito);
            echo json_encode(['success'=>$ok,'id_carrito'=>$idCarrito,'carrito'=>$resumen,'detalle'=>$detalle]);
            exit;

        case 'PUT':
            // Actualizar cantidad o cabecera del carrito
            parse_str(file_get_contents('php://input'), $put);
            $sessionToken = (string)($put['session_token'] ?? $sessionToken);
            if ($idCarrito <= 0 && $sessionToken) { $car = $ctrl->obtenerPorToken($sessionToken); $idCarrito = (int)($car['id_carrito'] ?? 0); }
            if ($idCarrito <= 0) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'Carrito no encontrado']); exit; }

            if (isset($put['accion']) && $put['accion']==='actualizar_cabecera'){
                $ok = $ctrl->actualizarCabecera($idCarrito, $put);
                echo json_encode(['success'=>$ok]);
                exit;
            }

            $idProducto = (int)($put['id_producto'] ?? 0);
            $cantidad = (int)($put['cantidad'] ?? -1);
            if ($idProducto <= 0 || $cantidad < 0) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'Parámetros inválidos']); exit; }
            $ok = $ctrl->actualizarCantidad($idCarrito, $idProducto, $cantidad);
            echo json_encode(['success'=>$ok]);
            exit;

        case 'DELETE':
            // Eliminar ítem o vaciar
            $idProducto = isset($_GET['id_producto']) ? (int)$_GET['id_producto'] : 0;
            if ($idProducto > 0) {
                $ok = $ctrl->eliminarItem($idCarrito, $idProducto);
                echo json_encode(['success'=>$ok]);
                exit;
            }
            if (isset($_GET['vaciar']) && (int)$_GET['vaciar']===1) {
                $ok = $ctrl->vaciar($idCarrito);
                echo json_encode(['success'=>$ok]);
                exit;
            }
            http_response_code(400);
            echo json_encode(['success'=>false,'error'=>'Parámetros inválidos']);
            exit;

        default:
            http_response_code(405);
            echo json_encode(['success'=>false,'error'=>'Método no permitido']);
            exit;
    }
} catch (Throwable $e) {
    error_log('[carrito_api] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error del servidor']);
}
