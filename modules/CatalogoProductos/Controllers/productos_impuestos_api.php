<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../vendor/autoload.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $dbConf = require __DIR__ . '/../../../config/database.php';
    $appConf = require __DIR__ . '/../../../config/app.php';

    // Seguridad: sólo mantenimiento
    $provided = $_GET['token'] ?? ($_SERVER['HTTP_X_MAINT_TOKEN'] ?? '');
    $expected = (string)($appConf['carrito']['mantenimiento_token'] ?? '');
    if ($expected === '' || $provided !== $expected) {
        http_response_code(403);
        echo json_encode(['success'=>false,'error'=>'Token inválido']);
        exit;
    }

    $dsn = "mysql:host={$dbConf['host']};dbname={$dbConf['dbname']};charset={$dbConf['charset']}";
    $pdo = new PDO($dsn, $dbConf['user'], $dbConf['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $raw = file_get_contents('php://input');
    $ct = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
    $isJson = stripos($ct, 'application/json') !== false;
    $body = $isJson && $raw ? json_decode($raw, true) : null;

    switch ($method) {
        case 'GET': {
            $idProducto = isset($_GET['id_producto']) ? (int)$_GET['id_producto'] : 0;
            if ($idProducto > 0) {
                $st = $pdo->prepare('SELECT pi.id_impuesto, i.codigo, i.nombre, i.tipo, i.valor FROM productos_impuestos pi JOIN impuestos i ON i.id_impuesto = pi.id_impuesto WHERE pi.id_producto = :p ORDER BY i.codigo');
                $st->execute([':p'=>$idProducto]);
                echo json_encode(['success'=>true,'impuestos'=>$st->fetchAll()]);
            } else {
                // listado simple
                $st = $pdo->query('SELECT * FROM productos_impuestos ORDER BY id_producto, id_impuesto');
                echo json_encode(['success'=>true,'rows'=>$st->fetchAll()]);
            }
            exit;
        }
        case 'POST': {
            $src = $body ?? $_POST;
            $idProducto = (int)($src['id_producto'] ?? 0);
            $idImpuesto = (int)($src['id_impuesto'] ?? 0);
            if ($idProducto<=0 || $idImpuesto<=0) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'id_producto e id_impuesto requeridos']); exit; }
            $st = $pdo->prepare('INSERT IGNORE INTO productos_impuestos (id_producto, id_impuesto) VALUES (:p,:i)');
            $st->execute([':p'=>$idProducto,':i'=>$idImpuesto]);
            echo json_encode(['success'=>true,'created'=>($st->rowCount()>0)]);
            exit;
        }
        case 'DELETE': {
            $idProducto = isset($_GET['id_producto']) ? (int)$_GET['id_producto'] : 0;
            $idImpuesto = isset($_GET['id_impuesto']) ? (int)$_GET['id_impuesto'] : 0;
            if ($idProducto<=0 || $idImpuesto<=0) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'id_producto e id_impuesto requeridos']); exit; }
            $st = $pdo->prepare('DELETE FROM productos_impuestos WHERE id_producto=:p AND id_impuesto=:i');
            $st->execute([':p'=>$idProducto,':i'=>$idImpuesto]);
            echo json_encode(['success'=>true,'deleted'=>$st->rowCount()]);
            exit;
        }
        default:
            http_response_code(405); echo json_encode(['success'=>false,'error'=>'Método no permitido']); exit;
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>'Error en productos_impuestos_api: '.$e->getMessage()]);
}
