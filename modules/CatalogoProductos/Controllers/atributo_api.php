<?php
// modules/CatalogoProductos/Controllers/atributo_api.php
require_once __DIR__ . '/../../../vendor/autoload.php';
use Modules\CatalogoProductos\Controllers\AtributoController;

header('Content-Type: application/json');

$config = require __DIR__ . '/../../../config/database.php';
$dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
$pdo = new \PDO($dsn, $config['user'], $config['password'], [
    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
]);

$controller = new AtributoController($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'nombre' => $_POST['nombre'] ?? '',
        'tipo' => $_POST['tipo'] ?? '',
        'estado' => isset($_POST['estado']) ? (int)$_POST['estado'] : 1
    ];
    try {
        $id = $controller->crear($data);
        echo json_encode(['success' => true, 'id' => $id]);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['id'])) {
        $atributo = $controller->obtener((int)$_GET['id']);
        echo json_encode(['success' => true, 'atributo' => $atributo]);
    } else {
        $atributos = $controller->listar();
        echo json_encode(['success' => true, 'atributos' => $atributos]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    parse_str(file_get_contents('php://input'), $_PUT);
    $id = $_PUT['id'] ?? null;
    if ($id) {
        $data = [
            'nombre' => $_PUT['nombre'] ?? '',
            'tipo' => $_PUT['tipo'] ?? '',
            'estado' => isset($_PUT['estado']) ? (int)$_PUT['estado'] : 1
        ];
        try {
            $ok = $controller->actualizar((int)$id, $data);
            echo json_encode(['success' => $ok]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID requerido']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    parse_str(file_get_contents('php://input'), $_DELETE);
    $id = $_DELETE['id'] ?? ($_GET['id'] ?? null);
    if ($id) {
        $ok = $controller->eliminar((int)$id);
        echo json_encode(['success' => $ok]);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID requerido']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Método no permitido']);
