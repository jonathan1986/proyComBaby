<?php
/**
 * Configuración de la aplicación.
 * Puedes sobreescribir valores vía variables de entorno.
 */
return [
    'carrito' => [
        // Límite máximo de líneas distintas permitidas en un carrito
        // Prioriza variable de entorno CARRITO_MAX_LINEAS (si es numérica >0), de lo contrario usa 200.
        'max_lineas' => (function(){
            $env = getenv('CARRITO_MAX_LINEAS');
            if ($env !== false && ctype_digit($env) && (int)$env > 0) {
                return (int)$env;
            }
            return 200; // default
        })(),
        // Días de inactividad tras los cuales un carrito 'abierto' se marca como 'expirado'
        // Variable de entorno: CARRITO_EXP_DIAS
        'expiracion_dias' => (function(){
            $env = getenv('CARRITO_EXP_DIAS');
            if ($env !== false && ctype_digit($env) && (int)$env > 0) {
                return (int)$env;
            }
            return 30; // default 30 días
        })(),
        // Token de mantenimiento para ejecutar jobs (como expiración). Recomendado establecer por ENV.
        // Variable de entorno: CARRITO_MAINT_TOKEN
        'mantenimiento_token' => (function(){
            $env = getenv('CARRITO_MAINT_TOKEN');
            return $env !== false ? (string)$env : '';
        })(),
        // Días de retención para registros de auditoría (carrito_logs)
        // Variable de entorno: CARRITO_LOGS_RET_DIAS
        'logs_retencion_dias' => (function(){
            $env = getenv('CARRITO_LOGS_RET_DIAS');
            if ($env !== false && ctype_digit($env) && (int)$env > 0) {
                return (int)$env;
            }
            return 90; // default 90 días
        })(),
    ],
];
