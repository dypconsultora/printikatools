<?php
/**
 * API de cotizaciones (JSON).
 *  GET  ?action=list            → lista las cotizaciones
 *  POST ?action=save  {datos}   → guarda una cotizacion
 *  POST ?action=delete {id}     → elimina una cotizacion
 * Todas requieren sesion iniciada; save/delete requieren token CSRF.
 */
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json; charset=utf-8');
// Guardar/listar/borrar cotizaciones es solo para la sesion PRO
// (incluso durante la prueba, las Acciones muestran el cartel).
requerir_login_api();

$action = $_GET['action'] ?? '';

try {
    // -------- Listar (lectura) --------
    if ($action === 'list') {
        $stmt = db()->query('SELECT id, datos_json FROM cotizaciones ORDER BY creado_en DESC, id DESC LIMIT 200');
        $out = [];
        foreach ($stmt as $row) {
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
    if (!verificar_csrf($csrf)) {
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

        $stmt = db()->prepare(
            'INSERT INTO cotizaciones (nombre, precio, moneda, datos_json, creado_en) VALUES (?, ?, ?, ?, NOW())'
        );
        $stmt->execute([$nombre, $precio, $moneda, $datos]);
        echo json_encode(['ok' => true, 'id' => (int) db()->lastInsertId()]);
        exit;
    }

    if ($action === 'delete') {
        $id = (int) ($body['id'] ?? 0);
        $stmt = db()->prepare('DELETE FROM cotizaciones WHERE id = ?');
        $stmt->execute([$id]);
        echo json_encode(['ok' => true]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Accion desconocida']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error del servidor']);
}
