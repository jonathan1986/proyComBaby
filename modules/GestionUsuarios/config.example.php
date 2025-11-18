<?php
/**
 * Configuración del Módulo de Gestión de Usuarios
 * 
 * Copiar este archivo a config.php y ajustar según tu entorno
 */

return [
    // Base de datos
    'database' => [
        'host' => getenv('DB_HOST') ?? 'localhost',
        'name' => getenv('DB_NAME') ?? 'babylovec',
        'user' => getenv('DB_USER') ?? 'root',
        'pass' => getenv('DB_PASS') ?? 'root',
        'charset' => 'utf8mb4',
    ],

    // Seguridad
    'security' => [
        'password_min_length' => 8,
        'password_hash_cost' => 12,  // bcrypt cost
        'token_length' => 64,         // bytes para token
    ],

    // Sesiones
    'session' => [
        'expiry_days' => 7,
        'expiry_seconds' => 7 * 24 * 60 * 60,
    ],

    // Recuperación de contraseña
    'password_recovery' => [
        'expiry_minutes' => 30,
        'max_attempts' => 5,           // Máximos intentos por hora
    ],

    // Email (para recuperación de contraseña)
    'email' => [
        'enabled' => false,  // Activar cuando tengas SMTP configurado
        'from' => 'noreply@tudominio.com',
        'from_name' => 'Tu E-Commerce',
        'smtp' => [
            'host' => 'smtp.tuproveedor.com',
            'port' => 587,
            'username' => 'tu@email.com',
            'password' => 'tu_contraseña',
            'encryption' => 'TLS',
        ],
    ],

    // API
    'api' => [
        'base_url' => '/modules/GestionUsuarios/Api',
        'version' => '1.0',
        'debug' => true,  // Cambiar a false en producción
    ],

    // CORS
    'cors' => [
        'allowed_origins' => [
            'http://localhost',
            'http://localhost:3000',
            'https://tudominio.com',
        ],
        'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
        'allowed_headers' => ['Content-Type', 'Authorization'],
    ],

    // Rate Limiting
    'rate_limit' => [
        'enabled' => true,
        'login_attempts' => 5,           // Máximos intentos de login
        'login_window_minutes' => 15,    // En X minutos
        'recovery_attempts' => 3,        // Para recuperación
        'recovery_window_minutes' => 60,
    ],

    // Roles predeterminados
    'default_roles' => [
        'CLIENTE' => 'Cliente del sistema',
        'VENDEDOR' => 'Vendedor autorizado',
        'ADMIN' => 'Administrador del sistema',
    ],

    // Permisos predeterminados
    'default_permissions' => [
        'usuarios' => [
            'ver_usuarios' => 'Ver lista de usuarios',
            'crear_usuario' => 'Crear nuevo usuario',
            'editar_usuario' => 'Editar usuario',
            'eliminar_usuario' => 'Eliminar usuario',
        ],
        'pedidos' => [
            'ver_pedidos' => 'Ver pedidos',
            'crear_pedido' => 'Crear pedido',
            'editar_pedido' => 'Editar pedido',
            'eliminar_pedido' => 'Eliminar pedido',
        ],
        'roles' => [
            'ver_roles' => 'Ver roles',
            'crear_rol' => 'Crear rol',
            'editar_rol' => 'Editar rol',
            'eliminar_rol' => 'Eliminar rol',
        ],
    ],

    // Mensajes personalizados
    'messages' => [
        'success' => 'Operación completada exitosamente',
        'error' => 'Ocurrió un error procesando tu solicitud',
        'unauthorized' => 'No tienes permisos para acceder a esto',
        'invalid_credentials' => 'Email o contraseña incorrectos',
        'user_exists' => 'El email ya está registrado',
        'invalid_token' => 'Token inválido o expirado',
        'password_too_weak' => 'La contraseña es muy débil',
    ],

    // Logging
    'logging' => [
        'enabled' => true,
        'path' => __DIR__ . '/../../logs/usuarios/',
        'level' => 'INFO',  // DEBUG, INFO, WARNING, ERROR
    ],

    // Features
    'features' => [
        'two_factor_auth' => false,     // TODO: Implementar
        'social_login' => false,        // TODO: Implementar
        'email_verification' => false,  // TODO: Implementar
        'password_reset_email' => false, // TODO: Implementar
        'user_profiles' => true,
        'order_history' => true,
        'role_permissions' => true,
    ],
];
?>
