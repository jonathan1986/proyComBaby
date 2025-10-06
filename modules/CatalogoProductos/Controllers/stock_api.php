<?php
// filepath: modules/CatalogoProductos/Controllers/stock_api.php
require_once __DIR__ . '/../../../vendor/autoload.php';

use Modules\CatalogoProductos\Controllers\StockController;

header('Content-Type: application/json');

try {
    $config = require __DIR__ . '/../../../config/database.php';
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new \PDO($dsn, $config['user'], $config['password'], [
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
    ]);
    $controller = new StockController($pdo);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if (isset($_GET['disponible']) && isset($_GET['id_producto'])) {
            $v = $controller->disponiblePorProducto((int)$_GET['id_producto']);
            echo json_encode(['success' => true, 'id_producto' => (int)$_GET['id_producto'], 'stock_disponible' => (int)$v]);
            exit;
        }
        $filtros = [
            'texto' => $_GET['texto'] ?? null,
            'estado' => isset($_GET['estado']) ? (int)$_GET['estado'] : null,
            'bajo_stock' => isset($_GET['bajo_stock']) ? (int)$_GET['bajo_stock'] : null,
        ];
        $rows = $controller->listarStock($filtros, (int)($_GET['limit'] ?? 50), (int)($_GET['offset'] ?? 0));
        echo json_encode(['success' => true, 'stock' => $rows]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
} catch (\Throwable $e) {
    error_log('stock_api error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error del servidor']);
}
