<?php
/**
 * Configuración de la aplicación.
 * Puedes sobreescribir valores vía variables de entorno.
 */
return [
    'carrito' => [
        // Límite máximo de líneas distintas permitidas en un carrito
        // Prioriza variable de entorno CARRITO_MAX_LINEAS (si es numérica >0), de lo contrario usa 200.
        'max_lineas' => (function () {
            $env = getenv('CARRITO_MAX_LINEAS');
            return ($env !== false && ctype_digit($env) && (int)$env > 0) ? (int)$env : 200;
        })(),
        // Días de inactividad tras los cuales un carrito 'abierto' se marca como 'expirado'
        // Variable de entorno: CARRITO_EXP_DIAS
        'expiracion_dias' => (function () {
            $env = getenv('CARRITO_EXP_DIAS');
            return ($env !== false && ctype_digit($env) && (int)$env > 0) ? (int)$env : 30;
        })(),
        // Token de mantenimiento para ejecutar jobs (como expiración). Recomendado establecer por ENV.
        // Variable de entorno: CARRITO_MAINT_TOKEN
        'mantenimiento_token' => (function () {
            $env = getenv('CARRITO_MAINT_TOKEN');
            return $env !== false ? (string)$env : ''; // si queda vacío, cualquier llamada fallará
        })(),
        // Días de retención para registros de auditoría (carrito_logs)
        // Variable de entorno: CARRITO_LOGS_RET_DIAS
        'logs_retencion_dias' => (function () {
            $env = getenv('CARRITO_LOGS_RET_DIAS');
            return ($env !== false && ctype_digit($env) && (int)$env > 0) ? (int)$env : 90;
        })(),
        // Rate limiting (opcional)
        'rate_limit' => [
            'enabled' => (getenv('CARRITO_RL_ENABLED') ? in_array(strtolower(getenv('CARRITO_RL_ENABLED')), ['1','true','yes'], true) : true),
            'limit' => (getenv('CARRITO_RL_LIMIT') && ctype_digit(getenv('CARRITO_RL_LIMIT')) ? (int)getenv('CARRITO_RL_LIMIT') : 30),
            'window_sec' => (getenv('CARRITO_RL_WINDOW') && ctype_digit(getenv('CARRITO_RL_WINDOW')) ? (int)getenv('CARRITO_RL_WINDOW') : 60),
        ],
    ],
];
