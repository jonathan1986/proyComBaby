<?php
// API para asignar categorías a un producto (N:M)
require_once __DIR__ . '/../../../vendor/autoload.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Método no permitido']);
        exit;
    }

    $config = require __DIR__ . '/../../../config/database.php';
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['user'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    $idProducto = isset($_POST['id_producto']) ? (int)$_POST['id_producto'] : 0;
    $categorias = $_POST['categorias'] ?? [];

    if ($idProducto <= 0) {
        throw new InvalidArgumentException('id_producto inválido');
    }
    if (!is_array($categorias) || !count($categorias)) {
        throw new InvalidArgumentException('Debe enviar al menos una categoría');
    }

    $cats = [];
    foreach ($categorias as $c) {
        $cid = (int)$c;
        if ($cid > 0) { $cats[] = $cid; }
    }
    if (!count($cats)) {
        throw new InvalidArgumentException('Categorías inválidas');
    }

    $pdo->beginTransaction();
    $del = $pdo->prepare('DELETE FROM productos_categorias WHERE id_producto = :idp');
    $del->execute([':idp' => $idProducto]);

    $ins = $pdo->prepare('INSERT INTO productos_categorias (id_producto, id_categoria, estado) VALUES (:idp, :idc, 1)');
    $count = 0;
    foreach ($cats as $cid) {
        $ins->execute([':idp' => $idProducto, ':idc' => $cid]);
        $count++;
    }
    $pdo->commit();

    echo json_encode(['success' => true, 'insertados' => $count]);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
