<?php
declare(strict_types=1);

use Modules\CatalogoProductos\Models\Carrito;
use Modules\CatalogoProductos\Models\CarritoItem;
use Modules\CatalogoProductos\Models\CarritoLog;

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../bootstrap.php';
// Cargar config app para límite de líneas
$appConf = require __DIR__ . '/../../../config/app.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

// Parse body (JSON o form)
$raw = file_get_contents('php://input');
$data = [];
if ($raw) {
    $tmp = json_decode($raw, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($tmp)) {
        $data = $tmp;
    }
}
if (!$data) {
    $data = $_POST; // fallback form
}

$sessionToken = isset($data['session_token']) ? trim((string)$data['session_token']) : '';
$idUsuario = isset($data['id_usuario']) ? (int)$data['id_usuario'] : 0;

if ($idUsuario <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'id_usuario requerido (>0)']);
    exit;
}
if ($sessionToken === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'session_token requerido']);
    exit;
}

try {
    $pdo->beginTransaction();

    $carritoModel = new Carrito($pdo);
    $logger = new CarritoLog($pdo);
    $MAX_LINEAS = (int)($appConf['carrito']['max_lineas'] ?? 200); // centralizado
    $itemModel = new CarritoItem($pdo, $MAX_LINEAS);

    // 1. Obtener carrito anónimo por token
    $carritoAnon = $carritoModel->obtenerPorUsuarioOToken(null, $sessionToken);
    if (!$carritoAnon) {
        // Nada que fusionar: devolver carrito usuario existente o crear nuevo si no hay
        $carritoUsuario = $carritoModel->obtenerPorUsuarioOToken($idUsuario, null);
        if (!$carritoUsuario) {
            $idNuevo = $carritoModel->crear([
                'id_usuario' => $idUsuario,
                'session_token' => null,
                'moneda' => 'USD',
                'impuesto_pct' => 0,
                'descuento_pct' => 0,
                'descuento_monto' => 0,
            ]);
            $carritoUsuario = $carritoModel->obtenerPorId($idNuevo);
        }
        // Log: no-op merge (no fuente)
        // No se registran cambios dado que no hubo fusión ni reasignación
        $pdo->commit();
        echo json_encode([
            'success' => true,
            'message' => 'No había carrito anónimo que fusionar',
            'carrito' => $carritoUsuario,
            'stats' => ['items_fusionados' => 0, 'items_agregados' => 0, 'items_omitidos' => 0]
        ]);
        exit;
    }

    // 2. Obtener carrito abierto existente del usuario (si lo hay)
    $carritoUsuario = $carritoModel->obtenerPorUsuarioOToken($idUsuario, null);

    if (!$carritoUsuario) {
        // Reasignar carrito anónimo directamente al usuario (simple)
        $stReassign = $pdo->prepare("UPDATE carritos SET id_usuario = :u, session_token = NULL WHERE id_carrito = :id");
        $stReassign->bindValue(':u', $idUsuario, PDO::PARAM_INT);
        $stReassign->bindValue(':id', $carritoAnon['id_carrito'], PDO::PARAM_INT);
        $stReassign->execute();
        $carritoUsuario = $carritoModel->obtenerPorId((int)$carritoAnon['id_carrito']);
        // Log: reasignación simple
        $logger->registrar((int)$carritoAnon['id_carrito'], 'merge', [
            'tipo' => 'reasignar',
            'id_destino' => (int)$carritoUsuario['id_carrito'],
            'id_fuente' => (int)$carritoAnon['id_carrito'],
            'stats' => ['items_fusionados'=>0,'items_agregados'=>0,'items_omitidos'=>0]
        ], $idUsuario, $sessionToken, $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null);
        $pdo->commit();
        echo json_encode([
            'success' => true,
            'message' => 'Carrito anónimo reasignado al usuario',
            'carrito' => $carritoUsuario,
            'stats' => ['items_fusionados' => 0, 'items_agregados' => 0, 'items_omitidos' => 0]
        ]);
        exit;
    }

    // 3. Fusionar: usuario ya tiene carrito y existe uno anónimo -> combinar líneas en carritoUsuario
    $idDestino = (int)$carritoUsuario['id_carrito'];
    $idFuente = (int)$carritoAnon['id_carrito'];

    // Obtener líneas fuente
    $stFuente = $pdo->prepare("SELECT id_producto, cantidad, precio_unit FROM carrito_items WHERE id_carrito = :c");
    $stFuente->bindValue(':c', $idFuente, PDO::PARAM_INT);
    $stFuente->execute();
    $lineasFuente = $stFuente->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // Contar líneas actuales destino
    $stCountDestino = $pdo->prepare("SELECT COUNT(*) FROM carrito_items WHERE id_carrito = :c");
    $stCountDestino->bindValue(':c', $idDestino, PDO::PARAM_INT);
    $stCountDestino->execute();
    $lineasDestino = (int)$stCountDestino->fetchColumn();

    $itemsFusionados = 0; // mismos productos: sumamos cantidad
    $itemsAgregados = 0;  // productos nuevos añadidos
    $itemsOmitidos   = 0; // no añadidos por límite
    $warnings = [];

    foreach ($lineasFuente as $lf) {
        $idProd = (int)$lf['id_producto'];
        $cantFuente = (int)$lf['cantidad'];
        $precioUnit = (float)$lf['precio_unit'];

        // ¿Existe ya en destino?
        $stExist = $pdo->prepare("SELECT cantidad FROM carrito_items WHERE id_carrito = :c AND id_producto = :p");
        $stExist->bindValue(':c', $idDestino, PDO::PARAM_INT);
        $stExist->bindValue(':p', $idProd, PDO::PARAM_INT);
        $stExist->execute();
        $rowExist = $stExist->fetch(PDO::FETCH_ASSOC);

        if ($rowExist) {
            $cantNueva = (int)$rowExist['cantidad'] + $cantFuente;
            $stUpd = $pdo->prepare("UPDATE carrito_items SET cantidad = :cant WHERE id_carrito = :c AND id_producto = :p");
            $stUpd->bindValue(':cant', $cantNueva, PDO::PARAM_INT);
            $stUpd->bindValue(':c', $idDestino, PDO::PARAM_INT);
            $stUpd->bindValue(':p', $idProd, PDO::PARAM_INT);
            $stUpd->execute();
            $itemsFusionados++;
        } else {
            // Producto nuevo -> verificar límite de líneas
            if ($lineasDestino >= $MAX_LINEAS) {
                $itemsOmitidos++;
                if (count($warnings) < 10) {
                    $warnings[] = 'Se omitió producto '.$idProd.' por alcanzar límite de líneas';
                }
                continue;
            }
            // Insertar nueva línea (usamos el modelo para respetar lógica y triggers)
            try {
                $itemModel->agregar($idDestino, $idProd, $cantFuente, $precioUnit);
                $itemsAgregados++;
                $lineasDestino++;
            } catch (RuntimeException $e) {
                $itemsOmitidos++;
                if (count($warnings) < 10) {
                    $warnings[] = 'Producto '.$idProd.' omitido: '.$e->getMessage();
                }
            }
        }
    }

    // Cerrar carrito fuente (podríamos eliminarlo también). Preferimos marcar cancelado para auditoría.
    $stClose = $pdo->prepare("UPDATE carritos SET estado = 'cancelado' WHERE id_carrito = :id");
    $stClose->bindValue(':id', $idFuente, PDO::PARAM_INT);
    $stClose->execute();

    // Eliminar líneas fuente para liberar espacio (opcional). Si se conserva para auditoría, omitir.
    $stDel = $pdo->prepare("DELETE FROM carrito_items WHERE id_carrito = :c");
    $stDel->bindValue(':c', $idFuente, PDO::PARAM_INT);
    $stDel->execute();

    // Recalcular totales destino (trigger ya actualizó en cada operación, pero por seguridad podemos recalcular cabecera manteniendo descuentos/impuestos actuales)
    $carritoDestino = $carritoModel->obtenerPorId($idDestino);
    $carritoModel->actualizarCabecera($idDestino, [
        'impuesto_pct' => (float)$carritoDestino['impuesto_pct'],
        'descuento_pct' => (float)$carritoDestino['descuento_pct'],
        'descuento_monto' => (float)$carritoDestino['descuento_monto'],
        'estado' => $carritoDestino['estado'],
    ]);
    $carritoDestino = $carritoModel->obtenerPorId($idDestino);

    // Log: fusión completa
    $logger->registrar($idDestino, 'merge', [
        'tipo' => 'fusionar',
        'id_destino' => $idDestino,
        'id_fuente' => $idFuente,
        'stats' => [
            'items_fusionados' => $itemsFusionados,
            'items_agregados' => $itemsAgregados,
            'items_omitidos' => $itemsOmitidos,
            'warnings' => $warnings
        ]
    ], $idUsuario, $sessionToken, $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null);

    $pdo->commit();
    echo json_encode([
        'success' => true,
        'message' => 'Carritos fusionados',
        'carrito' => $carritoDestino,
        'stats' => [
            'items_fusionados' => $itemsFusionados,
            'items_agregados' => $itemsAgregados,
            'items_omitidos' => $itemsOmitidos,
            'warnings' => $warnings
        ]
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error fusionando carritos: '.$e->getMessage()]);
}
