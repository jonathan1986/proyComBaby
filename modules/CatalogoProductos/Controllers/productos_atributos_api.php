<?php
// API para asignar atributos a un producto (N:M)
require_once __DIR__ . '/../../../vendor/autoload.php';

header('Content-Type: application/json');

try {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    $config = require __DIR__ . '/../../../config/database.php';
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['user'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    if ($method === 'GET') {
        $idProducto = isset($_GET['id_producto']) ? (int)$_GET['id_producto'] : 0;
        if ($idProducto <= 0) { http_response_code(400); echo json_encode(['success' => false, 'error' => 'id_producto requerido']); exit; }
        $st = $pdo->prepare('SELECT id_atributo, valor FROM productos_atributos WHERE id_producto = :idp AND estado = 1');
        $st->execute([':idp' => $idProducto]);
        $rows = $st->fetchAll();
        echo json_encode(['success' => true, 'atributos' => $rows]);
        exit;
    }

    if ($method === 'POST') {
        $idProducto = isset($_POST['id_producto']) ? (int)$_POST['id_producto'] : 0;
        $atributos = $_POST['atributos'] ?? [];

        if ($idProducto <= 0) {
            throw new InvalidArgumentException('id_producto inválido');
        }
        if (!is_array($atributos) || !count($atributos)) {
            throw new InvalidArgumentException('Debe enviar al menos un atributo');
        }

        $items = [];
        foreach ($atributos as $raw) {
            if (is_string($raw)) {
                $obj = json_decode($raw, true);
            } else {
                $obj = $raw;
            }
            if (!is_array($obj)) continue;
            $idAttr = isset($obj['id_atributo']) ? (int)$obj['id_atributo'] : 0;
            $valor = isset($obj['valor']) ? trim((string)$obj['valor']) : '';
            if ($idAttr > 0 && $valor !== '') {
                if (mb_strlen($valor) > 255) { $valor = mb_substr($valor, 0, 255); }
                $items[] = ['id_atributo' => $idAttr, 'valor' => $valor];
            }
        }

        if (!count($items)) { throw new InvalidArgumentException('Atributos inválidos'); }

        $pdo->beginTransaction();
        $del = $pdo->prepare('DELETE FROM productos_atributos WHERE id_producto = :idp');
        $del->execute([':idp' => $idProducto]);

        $ins = $pdo->prepare('INSERT INTO productos_atributos (id_producto, id_atributo, valor, estado) VALUES (:idp, :ida, :val, 1)');
        $count = 0;
        foreach ($items as $it) {
            $ins->execute([':idp' => $idProducto, ':ida' => $it['id_atributo'], ':val' => $it['valor']]);
            $count++;
        }
        $pdo->commit();

        echo json_encode(['success' => true, 'insertados' => $count]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
