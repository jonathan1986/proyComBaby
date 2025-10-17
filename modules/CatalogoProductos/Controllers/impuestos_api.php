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
            $codigo = isset($_GET['codigo']) ? trim((string)$_GET['codigo']) : '';
            if ($codigo !== '') {
                $st = $pdo->prepare('SELECT * FROM impuestos WHERE codigo = :c');
                $st->execute([':c'=>$codigo]);
                $row = $st->fetch() ?: null;
                if (!$row) { http_response_code(404); echo json_encode(['success'=>false,'error'=>'No encontrado']); exit; }
                echo json_encode(['success'=>true,'impuesto'=>$row]);
            } else {
                $st = $pdo->query('SELECT * FROM impuestos ORDER BY codigo');
                echo json_encode(['success'=>true,'impuestos'=>$st->fetchAll()]);
            }
            exit;
        }
        case 'POST': {
            $src = $body ?? $_POST;
            $codigo = trim((string)($src['codigo'] ?? ''));
            $nombre = trim((string)($src['nombre'] ?? ''));
            $tipo = (string)($src['tipo'] ?? 'porcentaje');
            $valor = (float)($src['valor'] ?? 0);
            $aplicaSobre = (string)($src['aplica_sobre'] ?? 'base_descuento');
            $activo = isset($src['activo']) ? (int)$src['activo'] : 1;
            if ($codigo === '' || $nombre === '') { http_response_code(400); echo json_encode(['success'=>false,'error'=>'codigo y nombre requeridos']); exit; }
            $st = $pdo->prepare('INSERT INTO impuestos (codigo,nombre,tipo,valor,aplica_sobre,activo) VALUES (:c,:n,:t,:v,:a,:ac)');
            $st->execute([':c'=>$codigo,':n'=>$nombre,':t'=>$tipo,':v'=>$valor,':a'=>$aplicaSobre,':ac'=>$activo]);
            echo json_encode(['success'=>true,'id_impuesto'=>(int)$pdo->lastInsertId()]);
            exit;
        }
        case 'PUT':
        case 'PATCH': {
            $src = $body ?? [];
            if (!$body) { parse_str($raw, $src); }
            $id = isset($_GET['id']) ? (int)$_GET['id'] : (int)($src['id_impuesto'] ?? 0);
            if ($id <= 0) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'id requerido']); exit; }
            $fields = [];$params=[':id'=>$id];
            foreach (['codigo','nombre','tipo','valor','aplica_sobre','activo'] as $f) {
                if (array_key_exists($f,$src)) { $fields[] = "$f = :$f"; $params[":$f"] = $src[$f]; }
            }
            if (!$fields) { echo json_encode(['success'=>true,'updated'=>0]); exit; }
            $sql = 'UPDATE impuestos SET '.implode(',', $fields).' WHERE id_impuesto = :id';
            $st = $pdo->prepare($sql); $st->execute($params);
            echo json_encode(['success'=>true,'updated'=>$st->rowCount()]);
            exit;
        }
        case 'DELETE': {
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            if ($id <= 0) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'id requerido']); exit; }
            $st = $pdo->prepare('DELETE FROM impuestos WHERE id_impuesto = :id');
            $st->execute([':id'=>$id]);
            echo json_encode(['success'=>true,'deleted'=>$st->rowCount()]);
            exit;
        }
        default:
            http_response_code(405); echo json_encode(['success'=>false,'error'=>'Método no permitido']); exit;
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>'Error en impuestos_api: '.$e->getMessage()]);
}
