<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../vendor/autoload.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $dbConf = require __DIR__ . '/../../../config/database.php';
    $appConf = require __DIR__ . '/../../../config/app.php';

    // Seguridad: usar el mismo token de mantenimiento que expiración
    $provided = $_GET['token'] ?? ($_SERVER['HTTP_X_MAINT_TOKEN'] ?? '');
    $expected = (string)($appConf['carrito']['mantenimiento_token'] ?? '');
    if ($expected === '' || $provided !== $expected) {
        http_response_code(403);
        echo json_encode(['success'=>false,'error'=>'Token inválido']);
        exit;
    }

    $dsn = "mysql:host={$dbConf['host']};dbname={$dbConf['dbname']};charset={$dbConf['charset']}";
    $pdo = new PDO($dsn, $dbConf['user'], $dbConf['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $idCarrito = isset($_GET['id_carrito']) ? (int)$_GET['id_carrito'] : 0;
    $accion = isset($_GET['accion']) ? trim((string)$_GET['accion']) : '';
    $desde = isset($_GET['desde']) ? trim((string)$_GET['desde']) : '';
    $hasta = isset($_GET['hasta']) ? trim((string)$_GET['hasta']) : '';
    $format = strtolower((string)($_GET['format'] ?? 'json'));
    $page = max(1, (int)($_GET['page'] ?? 1));
    $pageSize = min(200, max(1, (int)($_GET['pageSize'] ?? 50)));
    $offset = ($page - 1) * $pageSize;

    $where = [];
    $params = [];
    if ($idCarrito > 0) { $where[] = 'id_carrito = :id'; $params[':id'] = $idCarrito; }
    if ($accion !== '') { $where[] = 'accion = :ac'; $params[':ac'] = $accion; }
    if ($desde !== '') { $where[] = 'fecha >= :desde'; $params[':desde'] = $desde; }
    if ($hasta !== '') { $where[] = 'fecha <= :hasta'; $params[':hasta'] = $hasta; }
    $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    if ($format === 'csv') {
        // Export completo en CSV sin paginación (cuidado con volúmenes muy grandes)
        $st = $pdo->prepare("SELECT id_log, id_carrito, accion, detalles, usuario_id, session_token, ip, user_agent, fecha
                             FROM carrito_logs $whereSql
                             ORDER BY fecha DESC, id_log DESC");
        foreach ($params as $k=>$v) { $st->bindValue($k, $v); }
        $st->execute();
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="carrito_logs.csv"');
        $out = fopen('php://output', 'w');
        // Header
        fputcsv($out, ['id_log','id_carrito','accion','detalles','usuario_id','session_token','ip','user_agent','fecha']);
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['id_log'],
                $r['id_carrito'],
                $r['accion'],
                is_string($r['detalles']) ? $r['detalles'] : json_encode($r['detalles'], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
                $r['usuario_id'],
                $r['session_token'],
                $r['ip'],
                $r['user_agent'],
                $r['fecha'],
            ]);
        }
        fclose($out);
        exit;
    } else {
        // Total y paginado JSON
        $stCount = $pdo->prepare("SELECT COUNT(*) FROM carrito_logs $whereSql");
        foreach ($params as $k=>$v) { $stCount->bindValue($k, $v); }
        $stCount->execute();
        $total = (int)$stCount->fetchColumn();

        $st = $pdo->prepare("SELECT id_log, id_carrito, accion, detalles, usuario_id, session_token, ip, user_agent, fecha
                             FROM carrito_logs $whereSql
                             ORDER BY fecha DESC, id_log DESC
                             LIMIT :lim OFFSET :off");
        foreach ($params as $k=>$v) { $st->bindValue($k, $v); }
        $st->bindValue(':lim', $pageSize, PDO::PARAM_INT);
        $st->bindValue(':off', $offset, PDO::PARAM_INT);
        $st->execute();
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

        echo json_encode([
            'success'=>true,
            'page'=>$page,
            'pageSize'=>$pageSize,
            'total'=>$total,
            'rows'=>$rows
        ]);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>'Error consultando logs: '.$e->getMessage()]);
}
