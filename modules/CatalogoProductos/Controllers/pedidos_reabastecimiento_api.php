<?php
// filepath: modules/CatalogoProductos/Controllers/pedidos_reabastecimiento_api.php
require_once __DIR__ . '/../../../vendor/autoload.php';

use Modules\CatalogoProductos\Controllers\PedidoReabastecimientoController;

header('Content-Type: application/json');

try {
    $config = require __DIR__ . '/../../../config/database.php';
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new \PDO($dsn, $config['user'], $config['password'], [
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
    ]);
    $controller = new PedidoReabastecimientoController($pdo);

    switch ($_SERVER['REQUEST_METHOD']) {
        case 'POST':
            if (isset($_GET['recibir'])) {
                $id = (int)($_GET['id'] ?? 0);
                if ($id <= 0) { http_response_code(400); echo json_encode(['success' => false, 'error' => 'id requerido']); break; }
                $ok = $controller->recibir($id);
                echo json_encode(['success' => (bool)$ok]);
                break;
            }
            $input = $_POST;
            if (empty($input)) {
                $raw = file_get_contents('php://input');
                $json = json_decode($raw, true);
                if (is_array($json)) $input = $json;
            }
            $cabecera = $input['cabecera'] ?? [];
            $detalle = $input['detalle'] ?? [];
            $id = $controller->crear($cabecera, $detalle);
            echo json_encode(['success' => true, 'id' => $id]);
            break;

        case 'GET':
            if (isset($_GET['id'])) {
                $data = $controller->obtener((int)$_GET['id']);
                echo json_encode(['success' => true, 'pedido' => $data]);
                break;
            }
            $filtros = [
                'estado' => $_GET['estado'] ?? null,
                'id_proveedor' => $_GET['id_proveedor'] ?? null,
            ];
            $rows = $controller->listar($filtros, (int)($_GET['limit'] ?? 50), (int)($_GET['offset'] ?? 0));
            echo json_encode(['success' => true, 'pedidos' => $rows]);
            break;

        case 'PUT':
            parse_str(file_get_contents('php://input'), $_PUT);
            $id = isset($_PUT['id']) ? (int)$_PUT['id'] : 0;
            $estado = $_PUT['estado'] ?? '';
            if ($id <= 0 || !$estado) { http_response_code(400); echo json_encode(['success' => false, 'error' => 'id y estado requeridos']); break; }
            $ok = $controller->actualizarEstado($id, $estado);
            echo json_encode(['success' => (bool)$ok]);
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    }
} catch (\Throwable $e) {
    error_log('pedidos_reabastecimiento_api error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error del servidor']);
}
