<?php
/**
 * API Entry Point - Módulo de Gestión de Usuarios
 * 
 * Este archivo es el punto de entrada para todas las peticiones API REST
 * Ruta: /modules/GestionUsuarios/Api/index.php
 * 
 * Configuración:
 * - Headers CORS habilitados
 * - Enrutamiento de solicitudes
 * - Manejo de errores
 * - Logging de operaciones
 */

// ============================================================
// 1. CONFIGURACIÓN INICIAL
// ============================================================

// Headers CORS (permitir solicitudes desde cualquier origen)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

// Manejo de preflight requests (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ============================================================
// 2. AUTOLOAD Y BOOTSTRAP
// ============================================================

// Incluir autoloader de Composer
$bootstrapPath = __DIR__ . '/../../../bootstrap.php';

if (!file_exists($bootstrapPath)) {
    http_response_code(500);
    die(json_encode([
        'codigo' => 500,
        'mensaje' => 'Configuración no encontrada',
        'error' => 'No se encuentra bootstrap.php'
    ]));
}

try {
    require_once $bootstrapPath;
} catch (Exception $e) {
    http_response_code(500);
    die(json_encode([
        'codigo' => 500,
        'mensaje' => 'Error al cargar configuración',
        'error' => $e->getMessage()
    ]));
}

// ============================================================
// 3. VALIDAR VARIABLES DE ENTORNO
// ============================================================

if (!defined('DB_HOST') || !defined('DB_NAME') || !defined('DB_USER')) {
    http_response_code(500);
    die(json_encode([
        'codigo' => 500,
        'mensaje' => 'Base de datos no configurada',
        'error' => 'Verifica bootstrap.php para DB_HOST, DB_NAME, DB_USER'
    ]));
}

// ============================================================
// 4. CONECTAR A BASE DE DATOS
// ============================================================

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . 
        ';dbname=' . DB_NAME . 
        ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    die(json_encode([
        'codigo' => 500,
        'mensaje' => 'No se pudo conectar a la base de datos',
        'error' => $e->getMessage()
    ]));
}

// ============================================================
// 5. INCLUIR CLASES NECESARIAS
// ============================================================

// PSR-4 Autoloader para el módulo
spl_autoload_register(function ($class) {
    $prefix = 'Modules\\GestionUsuarios\\';
    if (strpos($class, $prefix) !== 0) {
        return;
    }
    
    $relativePath = substr($class, strlen($prefix));
    $file = __DIR__ . '/../' . str_replace('\\', '/', $relativePath) . '.php';
    
    if (file_exists($file)) {
        require_once $file;
    }
});

// ============================================================
// 6. EJECUTAR ROUTER
// ============================================================

try {
    $router = new \Modules\GestionUsuarios\Api\Router($pdo);
    $router->ejecutar();
} catch (Exception $e) {
    http_response_code(500);
    
    echo json_encode([
        'codigo' => 500,
        'mensaje' => 'Error interno del servidor',
        'error' => $e->getMessage()
    ]);
    
    // Logging (opcional)
    if (defined('LOG_PATH')) {
        $logMessage = date('Y-m-d H:i:s') . ' [ERROR] ' . $e->getMessage() . PHP_EOL;
        @file_put_contents(LOG_PATH . 'api_errors.log', $logMessage, FILE_APPEND);
    }
}

?>
