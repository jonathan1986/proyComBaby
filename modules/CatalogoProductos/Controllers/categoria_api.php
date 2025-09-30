<?php
// modules/CatalogoProductos/Controllers/categoria_api.php
require_once __DIR__ . '/../../../vendor/autoload.php';
use Modules\CatalogoProductos\Controllers\CategoriaController;

header('Content-Type: application/json');

$config = require __DIR__ . '/../../../config/database.php';
$dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
$pdo = new \PDO($dsn, $config['user'], $config['password'], [
    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
]);

$controller = new CategoriaController($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'nombre' => $_POST['nombre'] ?? '',
        'descripcion' => $_POST['descripcion'] ?? '',
        'id_categoria_padre' => $_POST['id_categoria_padre'] ?? null
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
        $cat = $controller->obtener((int)$_GET['id']);
        echo json_encode(['success' => true, 'categoria' => $cat]);
    } else {
        $cats = $controller->jerarquia();
        echo json_encode(['success' => true, 'categorias' => $cats]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    parse_str(file_get_contents('php://input'), $_PUT);
    $id = $_PUT['id'] ?? null;

    if ($id) {
        $data = [
            'nombre' => $_PUT['nombre'] ?? '',
            'descripcion' => $_PUT['descripcion'] ?? '',
            'id_categoria_padre' => $_PUT['id_categoria_padre'] ?? null
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
    $id = $_DELETE['id'] ?? null;
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
