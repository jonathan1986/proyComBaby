<?php
namespace Modules\GestionUsuarios\Utils;

/**
 * Clase de Utilidades
 * 
 * Funciones auxiliares para todo el módulo
 */
class Utilidades {

    /**
     * Genera un token seguro aleatorio
     */
    public static function generarToken($length = 64) {
        return bin2hex(random_bytes($length / 2));
    }

    /**
     * Hash de contraseña con bcrypt
     */
    public static function hashearPassword($password, $cost = 12) {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => $cost]);
    }

    /**
     * Verifica contraseña contra hash
     */
    public static function verificarPassword($password, $hash) {
        return password_verify($password, $hash);
    }

    /**
     * Valida formato de email
     */
    public static function esEmailValido($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Valida fortaleza de contraseña
     * 
     * Requisitos:
     * - Mínimo 8 caracteres
     * - Al menos una mayúscula
     * - Al menos una minúscula
     * - Al menos un número
     * 
     * @return array ['valido' => bool, 'errores' => array]
     */
    public static function validarFortalezaPassword($password) {
        $errores = [];
        
        if (strlen($password) < 8) {
            $errores[] = 'Mínimo 8 caracteres';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errores[] = 'Debe contener al menos una mayúscula';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errores[] = 'Debe contener al menos una minúscula';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errores[] = 'Debe contener al menos un número';
        }
        
        return [
            'valido' => empty($errores),
            'errores' => $errores
        ];
    }

    /**
     * Sanitiza input para evitar XSS
     */
    public static function sanitizar($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Valida que un campo no esté vacío
     */
    public static function validarRequerido($valor, $nombre = null) {
        if (empty($valor) || trim($valor) === '') {
            throw new \Exception($nombre ? "$nombre es requerido" : "Campo requerido");
        }
        return true;
    }

    /**
     * Formatea fecha a string legible
     */
    public static function formatearFecha($fecha, $formato = 'd/m/Y H:i') {
        if (empty($fecha)) return 'N/A';
        
        try {
            $date = new \DateTime($fecha);
            return $date->format($formato);
        } catch (\Exception $e) {
            return $fecha;
        }
    }

    /**
     * Formatea moneda
     */
    public static function formatearMoneda($cantidad, $simbolo = '$') {
        return $simbolo . number_format($cantidad, 2, ',', '.');
    }

    /**
     * Obtiene dirección IP del cliente
     */
    public static function obtenerIpCliente() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : 'desconocida';
    }

    /**
     * Obtiene el User Agent del cliente
     */
    public static function obtenerUserAgent() {
        return $_SERVER['HTTP_USER_AGENT'] ?? 'desconocido';
    }

    /**
     * Verifica si es una solicitud AJAX
     */
    public static function esAjax() {
        return (
            !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
        ) || !empty($_SERVER['HTTP_AUTHORIZATION']);
    }

    /**
     * Obtiene el método HTTP
     */
    public static function obtenerMetodo() {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    /**
     * Obtiene los datos JSON del cuerpo de la solicitud
     */
    public static function obtenerDatosJson() {
        $input = file_get_contents('php://input');
        return json_decode($input, true) ?? [];
    }

    /**
     * Pagina un array
     */
    public static function paginar(array $items, $pagina = 1, $porPagina = 20) {
        $total = count($items);
        $pagina = max(1, (int)$pagina);
        $porPagina = max(1, (int)$porPagina);
        
        $offset = ($pagina - 1) * $porPagina;
        $paginados = array_slice($items, $offset, $porPagina);
        
        return [
            'items' => $paginados,
            'pagina' => $pagina,
            'por_pagina' => $porPagina,
            'total' => $total,
            'paginas_totales' => ceil($total / $porPagina),
        ];
    }

    /**
     * Genera UUID v4
     */
    public static function generarUuid() {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    /**
     * Calcula diferencia de fechas en español
     */
    public static function tiempoTranscurrido($fecha) {
        $ahora = new \DateTime();
        $fecha = new \DateTime($fecha);
        $diff = $ahora->diff($fecha);

        if ($diff->days == 0) {
            if ($diff->h == 0) {
                return "hace {$diff->i} minutos";
            }
            return "hace {$diff->h} horas";
        }

        if ($diff->days == 1) {
            return "ayer";
        }

        if ($diff->days < 7) {
            return "hace {$diff->days} días";
        }

        if ($diff->days < 30) {
            $semanas = floor($diff->days / 7);
            return "hace {$semanas} semana" . ($semanas > 1 ? 's' : '');
        }

        if ($diff->days < 365) {
            $meses = floor($diff->days / 30);
            return "hace {$meses} mes" . ($meses > 1 ? 'es' : '');
        }

        $años = floor($diff->days / 365);
        return "hace {$años} año" . ($años > 1 ? 's' : '');
    }

    /**
     * Valida documento de identidad (cédula, pasaporte, etc)
     */
    public static function esDocumentoValido($documento, $tipo = 'CC') {
        // Remover espacios y caracteres especiales
        $documento = preg_replace('/[^0-9A-Z]/', '', strtoupper($documento));

        switch ($tipo) {
            case 'CC':  // Cédula de Ciudadanía
            case 'CE':  // Cédula de Extranjería
                return strlen($documento) >= 6 && strlen($documento) <= 20;
            
            case 'TI':  // Tarjeta de Identidad
                return strlen($documento) >= 6 && strlen($documento) <= 20;
            
            case 'PP':  // Pasaporte
                return strlen($documento) >= 6 && strlen($documento) <= 20;
            
            default:
                return strlen($documento) >= 6;
        }
    }

    /**
     * Valida número de teléfono
     */
    public static function esTelefonoValido($telefono) {
        // Formato: +57 3XX XXX XXXX o +57XXXXXXXXXX o 3XX XXXX XXX, etc.
        $patron = '/^(\+\d{1,3}[- ]?)?\d{10,}$/';
        return preg_match($patron, preg_replace('/[^0-9+]/', '', $telefono)) ? true : false;
    }

    /**
     * Obtiene la URL actual
     */
    public static function obtenerUrlActual() {
        $protocolo = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $uri = $_SERVER['REQUEST_URI'];
        return $protocolo . '://' . $host . $uri;
    }

    /**
     * Redirecciona a una URL
     */
    public static function redirigir($url) {
        header('Location: ' . $url);
        exit;
    }

    /**
     * Retorna respuesta JSON
     */
    public static function respuestaJson($codigo = 200, $mensaje = 'OK', $datos = null) {
        header('Content-Type: application/json');
        http_response_code($codigo);
        
        $respuesta = [
            'codigo' => $codigo,
            'mensaje' => $mensaje,
        ];
        
        if ($datos !== null) {
            $respuesta['datos'] = $datos;
        }
        
        return json_encode($respuesta);
    }

    /**
     * Escribe en log
     */
    public static function escribirLog($mensaje, $nivel = 'INFO', $archivo = null) {
        if (!defined('LOG_PATH')) {
            define('LOG_PATH', __DIR__ . '/../../logs/');
        }

        if (!is_dir(LOG_PATH)) {
            mkdir(LOG_PATH, 0755, true);
        }

        $archivo = $archivo ?? 'modulo_usuarios.log';
        $path = LOG_PATH . $archivo;
        
        $fecha = date('Y-m-d H:i:s');
        $linea = "[$fecha] [$nivel] - $mensaje" . PHP_EOL;
        
        file_put_contents($path, $linea, FILE_APPEND);
    }

    /**
     * Comprueba si un string está vacío
     */
    public static function estaVacio($valor) {
        return $valor === null || $valor === '' || (is_array($valor) && count($valor) === 0);
    }

    /**
     * Obtiene un valor de un array sin errores
     */
    public static function obtener(array $array, $clave, $defecto = null) {
        return isset($array[$clave]) ? $array[$clave] : $defecto;
    }
}
?>
