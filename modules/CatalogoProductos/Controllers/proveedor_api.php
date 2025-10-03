<?php
// filepath: modules/CatalogoProductos/Controllers/proveedor_api.php
require_once __DIR__ . '/../../../vendor/autoload.php';

use Modules\CatalogoProductos\Controllers\ProveedorController;

header('Content-Type: application/json');

try {
    $config = require __DIR__ . '/../../../config/database.php';
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new \PDO($dsn, $config['user'], $config['password'], [
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
    ]);
    $controller = new ProveedorController($pdo);

    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            if (isset($_GET['id'])) {
                $prov = $controller->obtener((int)$_GET['id']);
                echo json_encode(['success' => true, 'proveedor' => $prov]);
                break;
            }
            if (isset($_GET['buscar'])) {
                $term = (string)($_GET['buscar'] ?? '');
                $rows = $controller->buscar($term);
                echo json_encode(['success' => true, 'proveedores' => $rows]);
                break;
            }
            // default listar
            $rows = $controller->listar();
            echo json_encode(['success' => true, 'proveedores' => $rows]);
            break;

        case 'POST':
            $data = [
                'nombre' => $_POST['nombre'] ?? '',
                'contacto' => $_POST['contacto'] ?? '',
                'telefono' => $_POST['telefono'] ?? '',
                'email' => $_POST['email'] ?? '',
                'direccion' => $_POST['direccion'] ?? '',
                'ciudad' => $_POST['ciudad'] ?? '',
                'ruc' => $_POST['ruc'] ?? '',
                'estado' => isset($_POST['estado']) ? (int)$_POST['estado'] : 1,
                'usuario_creacion' => $_POST['usuario_creacion'] ?? ''
            ];
            $id = $controller->crear($data);
            echo json_encode(['success' => true, 'id' => $id]);
            break;

        case 'PUT':
            parse_str(file_get_contents('php://input'), $_PUT);
            $id = isset($_PUT['id']) ? (int)$_PUT['id'] : 0;
            if (!$id) { http_response_code(400); echo json_encode(['success' => false, 'error' => 'ID requerido']); break; }
            $data = [
                'nombre' => $_PUT['nombre'] ?? '',
                'contacto' => $_PUT['contacto'] ?? '',
                'telefono' => $_PUT['telefono'] ?? '',
                'email' => $_PUT['email'] ?? '',
                'direccion' => $_PUT['direccion'] ?? '',
                'ciudad' => $_PUT['ciudad'] ?? '',
                'ruc' => $_PUT['ruc'] ?? '',
                'estado' => isset($_PUT['estado']) ? (int)$_PUT['estado'] : 1,
                'usuario_actualizacion' => $_PUT['usuario_actualizacion'] ?? ''
            ];
            $ok = $controller->actualizar($id, $data);
            echo json_encode(['success' => (bool)$ok]);
            break;

        case 'DELETE':
            parse_str(file_get_contents('php://input'), $_DELETE);
            $id = isset($_DELETE['id']) ? (int)$_DELETE['id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);
            if (!$id) { http_response_code(400); echo json_encode(['success' => false, 'error' => 'ID requerido']); break; }
            $ok = $controller->eliminar($id);
            echo json_encode(['success' => (bool)$ok]);
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    }
} catch (\Throwable $e) {
    error_log('proveedor_api error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error del servidor']);
}
