<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../vendor/autoload.php';

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

    $ctrl = new CarritoController($pdo);
    $logger = new CarritoLog($pdo);

    $fetchDesgloseImpuestos = function(int $id) use ($pdo) {
        $st = $pdo->prepare("SELECT ci.id_impuesto, i.codigo, i.nombre, ci.monto FROM carritos_impuestos ci JOIN impuestos i ON i.id_impuesto = ci.id_impuesto WHERE ci.id_carrito = :id ORDER BY i.codigo");
        $st->execute([':id'=>$id]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    };

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
                // Auto-expirar si aplica
                $dias = (int)($appConf['carrito']['expiracion_dias'] ?? 30);
                if ($dias > 0) {
                    $stExp = $pdo->prepare("UPDATE carritos SET estado = 'expirado' WHERE id_carrito = :id AND estado = 'abierto' AND fecha_actualizacion < (NOW() - INTERVAL :dias DAY)");
                    $stExp->bindValue(':id', $id, PDO::PARAM_INT);
                    $stExp->bindValue(':dias', $dias, PDO::PARAM_INT);
                    $stExp->execute();
                    if ($stExp->rowCount() > 0) {
                        http_response_code(404); echo json_encode(['success'=>false,'error'=>'Carrito expirado']); exit;
                    }
                }
                // Si modo multi, adjuntar desglose
                if (($car['impuestos_modo'] ?? 'simple') === 'multi') {
                    $car['impuestos_desglose'] = $fetchDesgloseImpuestos((int)$car['id_carrito']);
                }
                echo json_encode(['success'=>true,'carrito'=>$car]);
                exit;
            }
            $idUsuario = isset($_GET['usuario']) ? (int)$_GET['usuario'] : null;
            $token = isset($_GET['session_token']) ? (string)$_GET['session_token'] : null;
            $car = $ctrl->obtenerPorUsuarioOToken($idUsuario, $token);
            if (!$car) { http_response_code(404); echo json_encode(['success'=>false,'error'=>'Carrito no encontrado']); exit; }
            // Auto-expirar si aplica
            $dias = (int)($appConf['carrito']['expiracion_dias'] ?? 30);
            if ($dias > 0) {
                $id = (int)$car['id_carrito'];
                $stExp = $pdo->prepare("UPDATE carritos SET estado = 'expirado' WHERE id_carrito = :id AND estado = 'abierto' AND fecha_actualizacion < (NOW() - INTERVAL :dias DAY)");
                $stExp->bindValue(':id', $id, PDO::PARAM_INT);
                $stExp->bindValue(':dias', $dias, PDO::PARAM_INT);
                $stExp->execute();
                if ($stExp->rowCount() > 0) {
                    http_response_code(404); echo json_encode(['success'=>false,'error'=>'Carrito expirado']); exit;
                }
            }
            if (($car['impuestos_modo'] ?? 'simple') === 'multi') {
                $car['impuestos_desglose'] = $fetchDesgloseImpuestos((int)$car['id_carrito']);
            }
            echo json_encode(['success'=>true,'carrito'=>$car]);
            exit;

        case 'POST':
            // Alias: action=merge -> delega a carrito_merge_api.php para mantener un solo punto de entrada opcional
            if (isset($_GET['action']) && $_GET['action'] === 'merge') {
                // Reejecutar el script de merge y salir
                require __DIR__ . '/carrito_merge_api.php';
                exit; // el script termina por sí mismo
            }
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
            // Log crear
            $logger->registrar($id, 'crear', $payload, $payload['id_usuario'] ?? null, $payload['session_token'] ?? null, $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null);
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
            // Si modo multi, recalcular impuestos
            try {
                $stModo = $pdo->prepare("SELECT impuestos_modo FROM carritos WHERE id_carrito = :id");
                $stModo->execute([':id'=>$id]);
                $modo = (string)$stModo->fetchColumn();
                if ($modo === 'multi') {
                    $call = $pdo->prepare("CALL sp_recalcular_impuestos_carrito(:id)");
                    $call->execute([':id'=>$id]);
                    while ($call->nextRowset()) { /* no-op */ }
                }
            } catch (Throwable $e) { error_log('[carrito_api][recalcImpuestos] '.$e->getMessage()); }
            // Log actualizar cabecera
            $idUsuario = $idUsuario ?? ($src['usuario'] ?? null);
            $token = $token ?? ($src['session_token'] ?? null);
            $logger->registrar($id, 'actualizar_cabecera', $src, $idUsuario ? (int)$idUsuario : null, $token, $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null);
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
            // Log eliminar carrito
            $logger->registrar($id, 'eliminar_carrito', null, $idUsuario, $token, $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null);
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
