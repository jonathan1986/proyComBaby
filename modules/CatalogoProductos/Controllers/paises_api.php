<?php
// filepath: modules/CatalogoProductos/Controllers/paises_api.php
header('Content-Type: application/json');

try {
    $config = require __DIR__ . '/../../../config/database.php';
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new \PDO($dsn, $config['user'], $config['password'], [
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
    ]);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $sql = "SELECT id_pais, nombre, codigo_iso_2, codigo_iso_3, codigo_telefono 
                FROM paises 
                WHERE activo = 1 
                ORDER BY nombre";
        $stmt = $pdo->query($sql);
        $paises = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'paises' => $paises]);
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    }
} catch (\Throwable $e) {
    error_log('paises_api error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error del servidor']);
}
