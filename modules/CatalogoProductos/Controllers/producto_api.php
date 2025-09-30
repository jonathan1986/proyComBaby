<?php
// modules/CatalogoProductos/Controllers/producto_api.php
declare(strict_types=1);

require_once __DIR__ . '/../../../vendor/autoload.php';
use Modules\CatalogoProductos\Controllers\ProductoController;

header('Content-Type: application/json; charset=utf-8');

try {
    $config = require __DIR__ . '/../../../config/database.php';
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new \PDO($dsn, $config['user'], $config['password'], [
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
    ]);

    $controller = new ProductoController($pdo);

    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            // GET ?id=123 -> un producto
            if (isset($_GET['id'])) {
                $id = (int)$_GET['id'];
                $prod = $controller->obtener($id);
                if (!$prod) {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'error' => 'Producto no encontrado']);
                    exit;
                }
                echo json_encode(['success' => true, 'producto' => $prod]);
                exit;
            }
            // GET ?buscar=term -> lista filtrada
            if (isset($_GET['buscar'])) {
                $term = (string)$_GET['buscar'];
                $result = $controller->buscar($term);
                echo json_encode(['success' => true, 'productos' => $result]);
                exit;
            }
            // GET ?listar=1 -> lista (máx 200)
            if (isset($_GET['listar'])) {
                $result = $controller->listar();
                echo json_encode(['success' => true, 'productos' => $result]);
                exit;
            }
            // Por defecto lista
            $result = $controller->listar();
            echo json_encode(['success' => true, 'productos' => $result]);
            exit;

        case 'POST':
            // Crear producto
            $data = [
                'nombre'        => $_POST['nombre']        ?? '',
                'descripcion'   => $_POST['descripcion']   ?? '',
                'precio'        => $_POST['precio']        ?? 0,
                'stock'         => $_POST['stock']         ?? 0,
                'stock_minimo'  => $_POST['stock_minimo']  ?? 0,
                'estado'        => $_POST['estado']        ?? 'inactivo',
            ];
            $id = $controller->crear($data);
            echo json_encode(['success' => true, 'id' => $id]);
            exit;

        case 'PUT':
            // Actualizar producto (?id=)
            parse_str(file_get_contents('php://input'), $put);
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'ID inválido']);
                exit;
            }
            $data = [
                'nombre'        => $put['nombre']        ?? '',
                'descripcion'   => $put['descripcion']   ?? '',
                'precio'        => $put['precio']        ?? 0,
                'stock'         => $put['stock']         ?? 0,
                'stock_minimo'  => $put['stock_minimo']  ?? 0,
                'estado'        => $put['estado']        ?? 'inactivo',
            ];
            $ok = $controller->actualizar($id, $data);
            echo json_encode(['success' => $ok]);
            exit;

        case 'DELETE':
            // Eliminar producto (?id=)
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'ID inválido']);
                exit;
            }
            $ok = $controller->eliminar($id);
            echo json_encode(['success' => $ok]);
            exit;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            exit;
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error del servidor']);
}
