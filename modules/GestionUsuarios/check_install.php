<?php
/**
 * Script de Verificación de Instalación
 * 
 * Uso: Coloca este archivo en la raíz del módulo y accede vía web:
 * http://localhost/modules/GestionUsuarios/check_install.php
 */

// Configuración
define('MODULE_PATH', __DIR__);
define('MODULE_NAME', 'GestionUsuarios');

// Colores para CLI
$colors = [
    'success' => "\033[92m",  // Green
    'error' => "\033[91m",    // Red
    'warning' => "\033[93m",  // Yellow
    'info' => "\033[94m",     // Blue
    'reset' => "\033[0m",
];

// Determinar si es CLI o Web
$is_cli = php_sapi_name() === 'cli';

// Función para imprimir con color
function print_check($status, $message, $details = '') {
    global $colors, $is_cli;
    
    if (!$is_cli) {
        echo '<div style="padding: 10px; margin: 5px 0; border-radius: 4px; ';
        if ($status === 'success') {
            echo 'background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb;';
        } elseif ($status === 'error') {
            echo 'background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;';
        } elseif ($status === 'warning') {
            echo 'background-color: #fff3cd; color: #856404; border: 1px solid #ffeaa7;';
        } else {
            echo 'background-color: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb;';
        }
        echo '">';
        
        $icon = $status === 'success' ? '✓' : ($status === 'error' ? '✗' : ($status === 'warning' ? '⚠' : 'ℹ'));
        echo "$icon <strong>$message</strong>";
        if ($details) echo "<br><small>$details</small>";
        echo '</div>';
    } else {
        $color = $colors[$status] ?? $colors['info'];
        $icon = $status === 'success' ? '✓' : ($status === 'error' ? '✗' : ($status === 'warning' ? '⚠' : 'ℹ'));
        echo "$color$icon $message{$colors['reset']}\n";
        if ($details) echo "  → $details\n";
    }
}

function print_title($title) {
    global $is_cli;
    if (!$is_cli) {
        echo "<h2 style='color: #333; border-bottom: 2px solid #0056b3; padding-bottom: 10px;'>$title</h2>";
    } else {
        echo "\n=== $title ===\n";
    }
}

// ============================================================
// INICIO DE VERIFICACIÓN
// ============================================================

if (!$is_cli) {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Verificación de Instalación - <?php echo MODULE_NAME; ?></title>
        <style>
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                max-width: 1000px;
                margin: 0 auto;
                padding: 20px;
                background-color: #f5f5f5;
            }
            .container {
                background-color: white;
                padding: 30px;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            h1 {
                color: #0056b3;
                text-align: center;
            }
            .status {
                text-align: center;
                font-size: 18px;
                margin: 20px 0;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🔍 Verificación de Instalación</h1>
            <p style="text-align: center; color: #666;">Módulo: <strong><?php echo MODULE_NAME; ?></strong></p>
    <?php
} else {
    echo "\n╔════════════════════════════════════════════════════════╗\n";
    echo "║  Verificación de Instalación - $MODULE_NAME\n";
    echo "╚════════════════════════════════════════════════════════╝\n";
}

// ============================================================
// 1. VERIFICAR ESTRUCTURA DE DIRECTORIOS
// ============================================================

print_title('1. Estructura de Directorios');

$directories = [
    'Models' => MODULE_PATH . '/Models',
    'Controllers' => MODULE_PATH . '/Controllers',
    'Views' => MODULE_PATH . '/Views',
    'Assets/css' => MODULE_PATH . '/Assets/css',
    'Assets/js' => MODULE_PATH . '/Assets/js',
    'Api' => MODULE_PATH . '/Api',
    'Utils' => MODULE_PATH . '/Utils',
    'logs' => MODULE_PATH . '/logs',
];

$all_dirs_ok = true;
foreach ($directories as $name => $path) {
    if (is_dir($path)) {
        print_check('success', "Directorio: $name", $path);
    } else {
        print_check('error', "Directorio faltante: $name", "Crea: mkdir -p '$path'");
        $all_dirs_ok = false;
    }
}

// ============================================================
// 2. VERIFICAR ARCHIVOS CRÍTICOS
// ============================================================

print_title('2. Archivos Críticos');

$critical_files = [
    'Models/Usuario.php' => MODULE_PATH . '/Models/Usuario.php',
    'Models/Perfil.php' => MODULE_PATH . '/Models/Perfil.php',
    'Models/Pedido.php' => MODULE_PATH . '/Models/Pedido.php',
    'Models/Rol.php' => MODULE_PATH . '/Models/Rol.php',
    'Controllers/UsuarioController.php' => MODULE_PATH . '/Controllers/UsuarioController.php',
    'Controllers/PedidoController.php' => MODULE_PATH . '/Controllers/PedidoController.php',
    'Controllers/RolController.php' => MODULE_PATH . '/Controllers/RolController.php',
    'Api/router.php' => MODULE_PATH . '/Api/router.php',
    'Views/login.html' => MODULE_PATH . '/Views/login.html',
    'Views/registro.html' => MODULE_PATH . '/Views/registro.html',
    'Views/perfil.html' => MODULE_PATH . '/Views/perfil.html',
    'Views/recuperar_contrasena.html' => MODULE_PATH . '/Views/recuperar_contrasena.html',
    'Assets/js/auth.js' => MODULE_PATH . '/Assets/js/auth.js',
    'Assets/js/perfil.js' => MODULE_PATH . '/Assets/js/perfil.js',
    'Assets/css/estilos.css' => MODULE_PATH . '/Assets/css/estilos.css',
];

$all_files_ok = true;
foreach ($critical_files as $name => $path) {
    if (file_exists($path)) {
        $size = filesize($path);
        print_check('success', "Archivo: $name", human_filesize($size));
    } else {
        print_check('error', "Archivo faltante: $name", $path);
        $all_files_ok = false;
    }
}

// ============================================================
// 3. VERIFICAR AMBIENTE PHP
// ============================================================

print_title('3. Ambiente PHP');

// Versión PHP
$php_version = phpversion();
$php_ok = version_compare($php_version, '7.4.0') >= 0;
print_check(
    $php_ok ? 'success' : 'warning',
    "Versión PHP: $php_version",
    $php_ok ? 'Compatible (7.4+)' : 'Recomendado: 7.4+'
);

// Extensiones requeridas
$extensions = [
    'PDO' => extension_loaded('pdo'),
    'PDO MySQL' => extension_loaded('pdo_mysql'),
    'JSON' => extension_loaded('json'),
    'OpenSSL' => extension_loaded('openssl'),
];

foreach ($extensions as $ext => $loaded) {
    print_check(
        $loaded ? 'success' : 'error',
        "Extensión: $ext",
        $loaded ? 'Habilitada' : 'No habilitada (REQUERIDA)'
    );
}

// Permisos de archivos
$writable = is_writable(MODULE_PATH . '/logs');
print_check(
    $writable ? 'success' : 'warning',
    'Permisos de escritura en logs/',
    $writable ? 'OK' : 'Recomendado: chmod 755'
);

// ============================================================
// 4. VERIFICAR CONFIGURACIÓN DE BD
// ============================================================

print_title('4. Conexión a Base de Datos');

// Buscar archivo de configuración
$bootstrap_paths = [
    MODULE_PATH . '/config.php',
    MODULE_PATH . '/../../bootstrap.php',
    $_SERVER['DOCUMENT_ROOT'] . '/bootstrap.php',
];

$config_found = false;
$pdo = null;

foreach ($bootstrap_paths as $path) {
    if (file_exists($path)) {
        print_check('info', "Archivo de configuración encontrado: $path");
        $config_found = true;
        
        try {
            require_once $path;
            
            if (defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER')) {
                $pdo = new PDO(
                    'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME,
                    DB_USER,
                    DB_PASS,
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
                print_check('success', 'Conexión a BD exitosa', DB_NAME . '@' . DB_HOST);
            } else {
                print_check('warning', 'Variables de BD no definidas en config');
            }
        } catch (Exception $e) {
            print_check('error', 'Error conectando a BD', $e->getMessage());
        }
        break;
    }
}

if (!$config_found) {
    print_check('warning', 'No se encontró archivo de configuración', 'Asegúrate que bootstrap.php esté configurado');
}

// Verificar tablas si hay conexión
if ($pdo) {
    print_title('5. Tablas de Base de Datos');
    
    $required_tables = [
        'usuarios',
        'perfiles_usuario',
        'sesiones_usuario',
        'recuperacion_contrasena',
        'historial_acceso',
        'historial_contrasenas',
        'roles',
        'permisos',
        'roles_permisos',
        'usuarios_roles',
        'pedidos',
        'detalles_pedido',
    ];
    
    $existing_tables = [];
    $result = $pdo->query("SHOW TABLES FROM " . DB_NAME);
    while ($row = $result->fetch(PDO::FETCH_NUM)) {
        $existing_tables[] = $row[0];
    }
    
    foreach ($required_tables as $table) {
        if (in_array($table, $existing_tables)) {
            print_check('success', "Tabla: $table");
        } else {
            print_check('error', "Tabla faltante: $table", 'Ejecuta: modulo_gestion_usuarios_mysql.sql');
        }
    }
    
    // Verificar procedimientos almacenados
    print_title('6. Procedimientos Almacenados');
    
    $procedures = [
        'sp_obtener_usuario',
        'sp_crear_usuario_nuevo',
        'sp_cambiar_contrasena',
        'sp_registrar_intento_acceso',
        'sp_bloquear_usuario',
    ];
    
    $result = $pdo->query("SELECT ROUTINE_NAME FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA = '" . DB_NAME . "' AND ROUTINE_TYPE = 'PROCEDURE'");
    $existing_procedures = [];
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $existing_procedures[] = $row['ROUTINE_NAME'];
    }
    
    foreach ($procedures as $proc) {
        if (in_array($proc, $existing_procedures)) {
            print_check('success', "Procedimiento: $proc");
        } else {
            print_check('warning', "Procedimiento faltante: $proc", 'Podría no ser crítico según tu versión');
        }
    }
}

// ============================================================
// 7. VERIFICAR ARCHIVO INDEX.PHP
// ============================================================

print_title('7. Archivo de Entrada API');

$index_path = MODULE_PATH . '/Api/index.php';
if (file_exists($index_path)) {
    print_check('success', 'Api/index.php existe');
    
    $content = file_get_contents($index_path);
    if (strpos($content, 'class Router') !== false || strpos($content, 'executeRouter') !== false) {
        print_check('success', 'Router configurado en index.php');
    } else {
        print_check('warning', 'Podría no tener router configurado', 'Revisa Api/index.php');
    }
} else {
    print_check('error', 'Api/index.php NO EXISTE', 'Crea este archivo para que la API funcione');
}

// ============================================================
// 8. RESUMEN Y RECOMENDACIONES
// ============================================================

print_title('8. Resumen y Próximos Pasos');

if ($all_dirs_ok && $all_files_ok) {
    print_check('success', 'Todos los archivos y directorios están en su lugar');
} else {
    print_check('error', 'Faltan algunos archivos o directorios');
}

if ($pdo) {
    print_check('success', 'Conexión a base de datos: ✓');
} else {
    print_check('warning', 'Conexión a base de datos: ✗', 'Configura el archivo bootstrap.php');
}

echo "\n";
if (!$is_cli) {
    echo '<h3>📋 Checklist de Instalación:</h3><ul>';
    echo '<li>✓ Carpetas creadas</li>';
    echo '<li>✓ Archivos en su lugar</li>';
    echo '<li>✓ Base de datos importada</li>';
    echo '<li>✓ Api/index.php creado</li>';
    echo '<li>✓ URLs de API configuradas en frontend</li>';
    echo '<li>✓ Pruebas iniciales ejecutadas</li>';
    echo '</ul>';
    echo '<h3>🚀 Ahora puedes:</h3><ol>';
    echo '<li>Acceder a: <code>' . MODULE_NAME . '/Views/login.html</code></li>';
    echo '<li>Probar endpoints con cURL o Postman</li>';
    echo '<li>Leer la documentación en README.md</li>';
    echo '</ol>';
} else {
    echo "✓ Checklist de Instalación:\n";
    echo "  • Carpetas creadas\n";
    echo "  • Archivos en su lugar\n";
    echo "  • Base de datos importada\n";
    echo "  • Api/index.php creado\n";
    echo "  • URLs de API configuradas en frontend\n";
    echo "  • Pruebas iniciales ejecutadas\n";
    echo "\n🚀 Ahora puedes:\n";
    echo "  1. Acceder a: " . MODULE_NAME . "/Views/login.html\n";
    echo "  2. Probar endpoints con cURL o Postman\n";
    echo "  3. Leer la documentación en README.md\n";
}

if (!$is_cli) {
    ?>
        </div>
    </body>
    </html>
    <?php
}

// Función auxiliar para tamaño de archivo legible
function human_filesize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}
?>
