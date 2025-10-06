<?php
// filepath: modules/CatalogoProductos/Controllers/alertas_bajo_stock_api.php
require_once __DIR__ . '/../../../vendor/autoload.php';

use Modules\CatalogoProductos\Controllers\AlertaBajoStockController;

header('Content-Type: application/json');

try {
    $config = require __DIR__ . '/../../../config/database.php';
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new \PDO($dsn, $config['user'], $config['password'], [
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
    ]);
    $controller = new AlertaBajoStockController($pdo);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $filtros = ['id_producto' => $_GET['id_producto'] ?? null];
        $rows = $controller->listar($filtros, (int)($_GET['limit'] ?? 50), (int)($_GET['offset'] ?? 0));
        echo json_encode(['success' => true, 'alertas' => $rows]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_GET['atender'])) {
            $id = (int)($_POST['id_alerta'] ?? $_GET['id_alerta'] ?? 0);
            if ($id <= 0) { http_response_code(400); echo json_encode(['success' => false, 'error' => 'id_alerta requerido']); exit; }
            $ok = $controller->marcarAtendida($id);
            echo json_encode(['success' => (bool)$ok]);
            exit;
        }
        if (isset($_GET['regenerar'])) {
            $idp = (int)($_POST['id_producto'] ?? $_GET['id_producto'] ?? 0);
            if ($idp <= 0) { http_response_code(400); echo json_encode(['success' => false, 'error' => 'id_producto requerido']); exit; }
            $controller->regenerar($idp);
            echo json_encode(['success' => true]);
            exit;
        }
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
} catch (\Throwable $e) {
    error_log('alertas_bajo_stock_api error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error del servidor']);
}
