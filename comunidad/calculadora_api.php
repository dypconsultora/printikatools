<?php
/**
 * API de la Calculadora del panel (JSON). La usa el cotizador embebido
 * cuando corre dentro de /comunidad: cotizaciones guardadas POR USUARIO
 * y conexión con el catálogo de productos del taller.
 *
 *  GET  ?action=list               → cotizaciones del usuario logueado
 *  POST ?action=save {datos}       → guarda una cotización
 *  POST ?action=delete {id}        → elimina una cotización propia
 *  POST ?action=producto {n,c,p}   → crea o actualiza un producto del catálogo
 *
 * Mismo contrato que cotizador/api.php para que el JS funcione sin cambios.
 */
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/taller.php';

header('Content-Type: application/json; charset=utf-8');

$u = usuario_actual();
if ($u === null) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'No autenticado']);
    exit;
}
taller_migrar();
$uid = (int) $u['id'];
$db  = com_db();
$action = $_GET['action'] ?? '';

try {
    // -------- Listar (lectura) --------
    if ($action === 'list') {
        $stmt = $db->prepare('SELECT id, datos_json FROM calc_cotizaciones
                              WHERE usuario_id=? ORDER BY creado_en DESC, id DESC LIMIT 200');
        $stmt->execute([$uid]);
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $d = json_decode($row['datos_json'], true);
            if (!is_array($d)) $d = [];
            $d['id'] = (int) $row['id'];
            $out[] = $d;
        }
        echo json_encode($out);
        exit;
    }

    // -------- Acciones de escritura: POST + CSRF --------
    $body = json_decode(file_get_contents('php://input'), true);
    if (!is_array($body)) $body = [];

    $csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($body['csrf'] ?? '');
    if (!com_csrf_ok($csrf)) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Token de seguridad invalido']);
        exit;
    }

    if ($action === 'save') {
        $nombre = trim((string) ($body['name'] ?? 'Sin nombre'));
        $nombre = mb_substr($nombre === '' ? 'Sin nombre' : $nombre, 0, 255);
        $precio = mb_substr((string) ($body['price'] ?? ''), 0, 64);
        $moneda = mb_substr((string) ($body['currency'] ?? 'ARS'), 0, 8);
        $datos  = json_encode($body, JSON_UNESCAPED_UNICODE);

        $stmt = $db->prepare('INSERT INTO calc_cotizaciones (usuario_id, nombre, precio, moneda, datos_json, creado_en)
                              VALUES (?, ?, ?, ?, ?, NOW())');
        $stmt->execute([$uid, $nombre, $precio, $moneda, $datos]);
        echo json_encode(['ok' => true, 'id' => (int) $db->lastInsertId()]);
        exit;
    }

    if ($action === 'delete') {
        $stmt = $db->prepare('DELETE FROM calc_cotizaciones WHERE id=? AND usuario_id=?');
        $stmt->execute([(int) ($body['id'] ?? 0), $uid]);
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($action === 'producto') {
        $nombre = mb_substr(trim((string) ($body['nombre'] ?? '')), 0, 150);
        $costo  = round(max(0, (float) ($body['costo'] ?? 0)), 2);
        $precio = round(max(0, (float) ($body['precio'] ?? 0)), 2);
        $datos  = json_encode(is_array($body['datos'] ?? null) ? $body['datos'] : [], JSON_UNESCAPED_UNICODE);

        if ($nombre === '') {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'Falta el nombre del producto']);
            exit;
        }

        $stmt = $db->prepare('SELECT id FROM productos WHERE usuario_id=? AND nombre=? LIMIT 1');
        $stmt->execute([$uid, $nombre]);
        $fila = $stmt->fetch();

        if ($fila) {
            $db->prepare('UPDATE productos SET costo=?, precio=?, datos_json=?, actualizado_en=NOW() WHERE id=? AND usuario_id=?')
               ->execute([$costo, $precio, $datos, (int) $fila['id'], $uid]);
            echo json_encode(['ok' => true, 'actualizado' => true, 'id' => (int) $fila['id']]);
        } else {
            $db->prepare('INSERT INTO productos (usuario_id, nombre, descripcion, costo, precio, datos_json, creado_en, actualizado_en)
                          VALUES (?, ?, "", ?, ?, ?, NOW(), NOW())')
               ->execute([$uid, $nombre, $costo, $precio, $datos]);
            echo json_encode(['ok' => true, 'actualizado' => false, 'id' => (int) $db->lastInsertId()]);
        }
        exit;
    }

    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Accion desconocida']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error del servidor']);
}
