<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../vendor/autoload.php';
use Modules\CatalogoProductos\Models\CarritoLog;

header('Content-Type: application/json; charset=utf-8');

try {
    // Cargar configs
    $dbConf = require __DIR__ . '/../../../config/database.php';
    $appConf = require __DIR__ . '/../../../config/app.php';

    // Validar token de mantenimiento
    $provided = $_GET['token'] ?? ($_SERVER['HTTP_X_MAINT_TOKEN'] ?? '');
    $expected = (string)($appConf['carrito']['mantenimiento_token'] ?? '');
    if ($expected === '' || $provided !== $expected) {
        http_response_code(403);
        echo json_encode(['success'=>false,'error'=>'Token de mantenimiento inválido']);
        exit;
    }

    $dsn = "mysql:host={$dbConf['host']};dbname={$dbConf['dbname']};charset={$dbConf['charset']}";
    $pdo = new PDO($dsn, $dbConf['user'], $dbConf['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $dias = (int)($appConf['carrito']['expiracion_dias'] ?? 30);
    if ($dias <= 0) { $dias = 30; }

    // Seleccionar carritos 'abierto' sin cambios recientes
    $sqlSel = "SELECT id_carrito FROM carritos WHERE estado = 'abierto' AND fecha_actualizacion < (NOW() - INTERVAL :dias DAY)";
    $stSel = $pdo->prepare($sqlSel);
    $stSel->bindValue(':dias', $dias, PDO::PARAM_INT);
    $stSel->execute();
    $ids = array_map(fn($r)=> (int)$r['id_carrito'], $stSel->fetchAll(PDO::FETCH_ASSOC) ?: []);

    // Marcar como expirados
    $stUpd = $pdo->prepare("UPDATE carritos SET estado = 'expirado' WHERE id_carrito = :id");
    $count = 0;
    $logger = new CarritoLog($pdo);
    foreach ($ids as $id) {
        $stUpd->execute([':id'=>$id]);
        $count++;
        // Log expirar por carrito
        $logger->registrar($id, 'expirar', ['dias'=>$dias], null, null, $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null);
    }

    echo json_encode([
        'success'=>true,
        'expirados'=>$count,
        'ids'=>$ids,
        'dias'=>$dias
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>'Error en tarea de expiración: '.$e->getMessage()]);
}
