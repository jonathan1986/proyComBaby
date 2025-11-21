<?php
// bootstrap.php
$config = require __DIR__ . '/config/database.php';

// Definir constantes para la API
define('DB_HOST', $config['host']);
define('DB_NAME', $config['dbname']);
define('DB_USER', $config['user']);
define('DB_PASS', $config['password']);

try {
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['user'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die('Error de conexión: ' . $e->getMessage());
}

// $pdo ahora está listo para usarse en los modelos
