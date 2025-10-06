<?php
// filepath: modules/CatalogoProductos/Controllers/inventario_movimientos_api.php
require_once __DIR__ . '/../../../vendor/autoload.php';

use Modules\CatalogoProductos\Controllers\InventarioMovimientoController;

header('Content-Type: application/json');

try {
    $config = require __DIR__ . '/../../../config/database.php';
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new \PDO($dsn, $config['user'], $config['password'], [
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
    ]);
    $controller = new InventarioMovimientoController($pdo);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = $_POST;
        // Si viene JSON
        if (empty($input)) {
            $raw = file_get_contents('php://input');
            $json = json_decode($raw, true);
            if (is_array($json)) $input = $json;
        }
        $ok = $controller->crear($input);
        echo json_encode(['success' => (bool)$ok]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if (isset($_GET['ultimos']) && isset($_GET['id_producto'])) {
            $rows = $controller->ultimosPorProducto((int)$_GET['id_producto'], (int)($_GET['limit'] ?? 10));
            echo json_encode(['success' => true, 'movimientos' => $rows]);
            exit;
        }
        $filtros = [
            'id_producto' => $_GET['id_producto'] ?? null,
            'id_pedido' => $_GET['id_pedido'] ?? null,
            'tipo' => $_GET['tipo'] ?? null,
            'fecha_desde' => $_GET['fecha_desde'] ?? null,
            'fecha_hasta' => $_GET['fecha_hasta'] ?? null,
            'motivo' => $_GET['motivo'] ?? null,
        ];
        $rows = $controller->listar($filtros, (int)($_GET['limit'] ?? 50), (int)($_GET['offset'] ?? 0));
        echo json_encode(['success' => true, 'movimientos' => $rows]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
} catch (\Throwable $e) {
    error_log('inventario_movimientos_api error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error del servidor']);
}
