<?php
// filepath: modules/CatalogoProductos/Controllers/productos_proveedores_api.php
require_once __DIR__ . '/../../../vendor/autoload.php';

use Modules\CatalogoProductos\Controllers\ProductoProveedorController;

header('Content-Type: application/json');

try {
    $config = require __DIR__ . '/../../../config/database.php';
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new \PDO($dsn, $config['user'], $config['password'], [
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
    ]);
    $controller = new ProductoProveedorController($pdo);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $idProducto = isset($_GET['id_producto']) ? (int)$_GET['id_producto'] : 0;
        if ($idProducto <= 0) { http_response_code(400); echo json_encode(['success' => false, 'error' => 'id_producto requerido']); exit; }
        $rows = $controller->listarPorProducto($idProducto);
        echo json_encode(['success' => true, 'proveedores' => $rows]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $idProducto = isset($_POST['id_producto']) ? (int)$_POST['id_producto'] : 0;
        if ($idProducto <= 0) { http_response_code(400); echo json_encode(['success' => false, 'error' => 'id_producto requerido']); exit; }
        $ids = isset($_POST['proveedores']) && is_array($_POST['proveedores']) ? $_POST['proveedores'] : [];
        $ok = $controller->guardar($idProducto, $ids);
        if (!$ok) { http_response_code(500); echo json_encode(['success' => false, 'error' => 'No se pudo guardar la relación']); exit; }
        echo json_encode(['success' => true]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
} catch (\Throwable $e) {
    error_log('productos_proveedores_api error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error del servidor']);
}
