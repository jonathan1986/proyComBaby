<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../vendor/autoload.php';

use PDO;

header('Content-Type: application/json');

// Config: token de mantenimiento (desde env o default)
$MAINT_TOKEN = getenv('CARRITO_MAINT_TOKEN') ?: 'changeme-strong-token';

// Helper: leer JSON
function jsonBody(): array {
  $raw = file_get_contents('php://input');
  if (!$raw) return [];
  $data = json_decode($raw, true);
  return is_array($data) ? $data : [];
}

// Helper: requerir token para métodos que modifican
function ensureMaintToken(string $method, string $provided, string $expected) {
  if (in_array($method, ['POST','PUT','PATCH','DELETE'], true)) {
    if (!$provided || $provided !== $expected) {
      http_response_code(403);
      echo json_encode(['success'=>false,'error'=>'forbidden']); exit;
    }
  }
}

try {
  // Conexión usando la configuración del proyecto (evita sockets locales en Docker)
  $dbConf = require __DIR__ . '/../../../config/database.php';
  $dsn = "mysql:host={$dbConf['host']};dbname={$dbConf['dbname']};charset={$dbConf['charset']}";
  $pdo = new PDO($dsn, $dbConf['user'], $dbConf['password'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);

  $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
  $tokenHdr = $_SERVER['HTTP_X_MAINT_TOKEN'] ?? '';
  ensureMaintToken($method, $tokenHdr, $MAINT_TOKEN);

  if ($method === 'GET') {
    $rows = $pdo->query("SELECT id_impuesto, codigo, nombre, tipo, valor, aplica_sobre, activo FROM impuestos ORDER BY nombre")->fetchAll();
    echo json_encode(['success'=>true,'impuestos'=>$rows]); exit;
  }

  if ($method === 'POST') {
    $b = jsonBody();
    $sql = "INSERT INTO impuestos(codigo,nombre,tipo,valor,aplica_sobre,activo) VALUES(?,?,?,?,?,?)";
    $st = $pdo->prepare($sql);
    $st->execute([
      $b['codigo'] ?? null,
      $b['nombre'] ?? null,
      $b['tipo'] ?? 'porcentaje',
      isset($b['valor']) ? (float)$b['valor'] : 0,
      $b['aplica_sobre'] ?? 'base_descuento',
      isset($b['activo']) ? (int)$b['activo'] : 1
    ]);
    echo json_encode(['success'=>true,'id'=>$pdo->lastInsertId()]); exit;
  }

  if ($method === 'PATCH' || $method === 'PUT') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'id requerido']); exit; }
    $b = jsonBody();
    $fields = [];
    $vals = [];
    foreach (['codigo','nombre','tipo','aplica_sobre'] as $k) {
      if (array_key_exists($k, $b)) { $fields[] = "$k=?"; $vals[] = $b[$k]; }
    }
    if (array_key_exists('valor',$b)) { $fields[] = "valor=?"; $vals[] = (float)$b['valor']; }
    if (array_key_exists('activo',$b)) { $fields[] = "activo=?"; $vals[] = (int)$b['activo']; }
    if (!$fields) { echo json_encode(['success'=>true]); exit; }
    $vals[] = $id;
    $sql = "UPDATE impuestos SET ".implode(',', $fields)." WHERE id_impuesto=?";
    $st = $pdo->prepare($sql); $st->execute($vals);
    echo json_encode(['success'=>true]); exit;
  }

  if ($method === 'DELETE') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'id requerido']); exit; }
    // Evita borrar si está referenciado
    $pdo->prepare("DELETE FROM impuestos WHERE id_impuesto=?")->execute([$id]);
    echo json_encode(['success'=>true]); exit;
  }

  http_response_code(405);
  echo json_encode(['success'=>false,'error'=>'método no soportado']);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}
