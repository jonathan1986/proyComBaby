<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../vendor/autoload.php';

header('Content-Type: application/json; charset=utf-8');

try {
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

    // Días de retención configurable (por default 90). Se puede override por query ?dias=NN
    $diasConf = (int)($appConf['carrito']['logs_retencion_dias'] ?? 90);
    $dias = isset($_GET['dias']) ? (int)$_GET['dias'] : $diasConf;
    if ($dias <= 0) { $dias = $diasConf > 0 ? $diasConf : 90; }

    // Contar registros a purgar (previo)
    $sqlCount = "SELECT COUNT(*) FROM carrito_logs WHERE fecha < (NOW() - INTERVAL :dias DAY)";
    $stCount = $pdo->prepare($sqlCount);
    $stCount->bindValue(':dias', $dias, PDO::PARAM_INT);
    $stCount->execute();
    $preCount = (int)$stCount->fetchColumn();

    // Purgar
    $sqlDel = "DELETE FROM carrito_logs WHERE fecha < (NOW() - INTERVAL :dias DAY)";
    $stDel = $pdo->prepare($sqlDel);
    $stDel->bindValue(':dias', $dias, PDO::PARAM_INT);
    $stDel->execute();
    $purged = $stDel->rowCount();

    echo json_encode([
        'success' => true,
        'dias' => $dias,
        'antes' => $preCount,
        'eliminados' => $purged,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>'Error en purga de logs: '.$e->getMessage()]);
}
