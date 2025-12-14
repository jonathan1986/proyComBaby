<?php
/**
 * EJEMPLO DE INTEGRACIÓN DEL MIDDLEWARE DE AUTENTICACIÓN
 * 
 * Este archivo muestra cómo modificar los controladores existentes
 * para usar el AuthMiddleware y validar tokens de sesión.
 * 
 * INSTRUCCIONES:
 * 1. Agregar estas líneas al inicio de cada archivo API:
 *    - impuestos_api.php
 *    - productos_impuestos_api.php
 *    - producto_api.php
 * 
 * 2. Remover completamente la validación de X-Maint-Token
 * 
 * 3. Usar $usuarioAutenticado para auditoría y logs
 */

// ============================================================================
// PASO 1: Incluir el middleware al inicio del archivo
// ============================================================================

require_once __DIR__ . '/AuthMiddleware.php';
require_once __DIR__ . '/../../config/database.php'; // Ajustar según tu configuración

// ============================================================================
// PASO 2: Validar acceso ANTES de procesar cualquier request
// ============================================================================

try {
    // Conectar a la base de datos
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Roles permitidos para este controlador
    $rolesPermitidos = ['ADMINISTRADOR', 'GESTOR_CONTENIDOS'];
    
    // Crear instancia del middleware
    $auth = new AuthMiddleware($pdo, $rolesPermitidos);
    
    // Validar token y obtener usuario autenticado
    $usuarioAutenticado = $auth->validarAcceso();
    
    // Si llegamos aquí, el usuario está autenticado y autorizado
    // $usuarioAutenticado contiene:
    // - usuario_id
    // - email
    // - nombre_completo
    // - roles (array)
    // - sesion_id
    
} catch (Exception $e) {
    // El middleware ya envió la respuesta de error
    // No es necesario hacer nada más aquí
    exit;
}

// ============================================================================
// PASO 3: Procesar la request normalmente
// ============================================================================

// A partir de aquí, todo el código existente continúa igual
// EXCEPTO que debes REMOVER cualquier validación de X-Maint-Token

// ANTES (REMOVER ESTO):
/*
if (!isset($_SERVER['HTTP_X_MAINT_TOKEN']) || $_SERVER['HTTP_X_MAINT_TOKEN'] !== MAINT_TOKEN) {
    http_response_code(403);
    echo json_encode(['error' => 'Token inválido']);
    exit;
}
*/

// AHORA: El middleware ya validó el acceso, no se necesita nada más

// Ejemplo de uso del usuario autenticado para auditoría:
/*
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ... código para crear impuesto ...
    
    // Registrar en auditoría quién creó el registro
    $sqlAudit = "INSERT INTO auditoria_impuestos (usuario_id, accion, datos) VALUES (?, ?, ?)";
    $stmtAudit = $pdo->prepare($sqlAudit);
    $stmtAudit->execute([
        $usuarioAutenticado['usuario_id'],
        'CREAR_IMPUESTO',
        json_encode($nuevoImpuesto)
    ]);
}
*/

// ============================================================================
// EJEMPLO COMPLETO: impuestos_api.php con autenticación
// ============================================================================

/*
<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/AuthMiddleware.php';
require_once __DIR__ . '/../../config/database.php';

try {
    // Conexión a BD
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Validar autenticación
    $auth = new AuthMiddleware($pdo, ['ADMINISTRADOR', 'GESTOR_CONTENIDOS']);
    $usuarioAutenticado = $auth->validarAcceso();
    
} catch (Exception $e) {
    exit; // Middleware ya envió error
}

// Procesar request
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Listar impuestos
    $sql = "SELECT * FROM impuestos WHERE activo = 1 ORDER BY id_impuesto";
    $stmt = $pdo->query($sql);
    $impuestos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'impuestos' => $impuestos,
        'usuario' => $usuarioAutenticado['email'] // Para referencia
    ]);
    
} elseif ($method === 'POST') {
    // Crear impuesto
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validar permisos específicos (opcional)
    if (!$auth->tienePermiso($usuarioAutenticado['usuario_id'], 'PRODUCTOS_CREAR')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Sin permiso para crear impuestos']);
        exit;
    }
    
    $sql = "INSERT INTO impuestos (codigo, nombre, tipo, valor, aplica_sobre, activo) 
            VALUES (:codigo, :nombre, :tipo, :valor, :aplica_sobre, :activo)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'codigo' => $data['codigo'],
        'nombre' => $data['nombre'],
        'tipo' => $data['tipo'],
        'valor' => $data['valor'],
        'aplica_sobre' => $data['aplica_sobre'],
        'activo' => $data['activo'] ?? 1
    ]);
    
    // Auditoría
    $nuevoId = $pdo->lastInsertId();
    // ... registrar en auditoría con $usuarioAutenticado['usuario_id'] ...
    
    echo json_encode([
        'success' => true,
        'id_impuesto' => $nuevoId,
        'creado_por' => $usuarioAutenticado['nombre_completo']
    ]);
    
} elseif ($method === 'PATCH') {
    // Actualizar impuesto
    // ... similar con validación de permisos ...
    
} elseif ($method === 'DELETE') {
    // Eliminar impuesto
    // ... similar con validación de permisos ...
}
?>
*/

// ============================================================================
// RESUMEN DE CAMBIOS POR ARCHIVO
// ============================================================================

/*
ARCHIVO: impuestos_api.php
- AGREGAR: require_once AuthMiddleware.php
- AGREGAR: Validación de acceso al inicio
- REMOVER: Validación de X-Maint-Token
- OPCIONAL: Usar $auth->tienePermiso() para permisos granulares

ARCHIVO: productos_impuestos_api.php
- AGREGAR: require_once AuthMiddleware.php
- AGREGAR: Validación de acceso al inicio
- REMOVER: Validación de X-Maint-Token

ARCHIVO: producto_api.php
- AGREGAR: require_once AuthMiddleware.php
- AGREGAR: Validación de acceso al inicio
- REMOVER: Validación de X-Maint-Token

ARCHIVO: config/database.php (si no existe)
- CREAR con constantes DB_HOST, DB_NAME, DB_USER, DB_PASS
*/
