<?php
// API REST segura para imágenes de producto
require_once __DIR__ . '/../../../vendor/autoload.php';
use Modules\CatalogoProductos\Controllers\ImagenesProductoController;

header('Content-Type: application/json');

$config = require __DIR__ . '/../../../config/database.php';
$dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
$pdo = new PDO($dsn, $config['user'], $config['password'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);

$controller = new ImagenesProductoController($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_producto = isset($_POST['id_producto']) ? (int)$_POST['id_producto'] : 0;
    $principal = isset($_POST['principal']) ? (int)$_POST['principal'] : 0;
    $estado = isset($_POST['estado']) ? (int)$_POST['estado'] : 1;
    $uploadDir = __DIR__ . '/../../../uploads/productos/';
    if (!is_dir($uploadDir)) {
        if (!@mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'No se pudo crear el directorio de subida']);
            exit;
        }
    }
    if (!is_writable($uploadDir)) {
        // Intento de ajustar permisos (puede no tener efecto en bind mounts Windows)
        @chmod($uploadDir, 0777);
    }
    if (!is_writable($uploadDir)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Directorio de subida no escribible: ' . $uploadDir]);
        exit;
    }
    if ($id_producto <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'id_producto inválido o faltante']);
        exit;
    }
    if (!isset($_FILES['archivo_imagen'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'No se envió el campo archivo_imagen']);
        exit;
    }
    $file = $_FILES['archivo_imagen'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errMap = [
            UPLOAD_ERR_INI_SIZE => 'El archivo excede upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'El archivo excede MAX_FILE_SIZE del formulario',
            UPLOAD_ERR_PARTIAL => 'Archivo subido parcialmente',
            UPLOAD_ERR_NO_FILE => 'No se subió ningún archivo',
            UPLOAD_ERR_NO_TMP_DIR => 'Falta el directorio temporal',
            UPLOAD_ERR_CANT_WRITE => 'No se pudo escribir en disco',
            UPLOAD_ERR_EXTENSION => 'Una extensión de PHP detuvo la subida'
        ];
        $msg = $errMap[$file['error']] ?? ('Error desconocido de subida: ' . $file['error']);
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $msg]);
        exit;
    }
    if (!is_uploaded_file($file['tmp_name'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Archivo temporal inválido']);
        exit;
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','gif','webp'];
    if (!in_array($ext, $allowed)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Tipo de archivo no permitido']);
        exit;
    }
    $safeName = uniqid('img_') . '.' . $ext;
    $destPath = $uploadDir . $safeName;
    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        http_response_code(500);
        // Log para diagnóstico en contenedor
        error_log('move_uploaded_file fallo hacia: ' . $destPath);
        echo json_encode(['success' => false, 'error' => 'Error al guardar el archivo (ver logs)']);
        exit;
    }
    @chmod($destPath, 0644);
    $data = [
        'id_producto' => $id_producto,
        'archivo_imagen' => 'uploads/productos/' . $safeName,
        'principal' => $principal,
        'estado' => $estado
    ];
    try {
        $id = $controller->crear($data);
        echo json_encode(['success' => true, 'id' => $id, 'archivo' => $data['archivo_imagen']]);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Método no permitido']);
