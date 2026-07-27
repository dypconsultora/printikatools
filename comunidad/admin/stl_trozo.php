<?php
/**
 * Recibe un pedazo de un archivo grande.
 *
 * El servidor corta las subidas largas (el famoso "Request Timeout"): un STL
 * de 40 MB por una conexion casera tarda mas de lo que el hosting espera. La
 * solucion es no mandarlo de una: el navegador lo parte en pedazos chicos y
 * cada pedazo viaja en su propio pedido, que tarda segundos. Aca se van
 * pegando uno atras de otro hasta rearmar el archivo.
 *
 * Responde JSON. Solo para administradores y con token de sesion.
 */
require_once __DIR__ . '/../inc/auth.php';

header('Content-Type: application/json; charset=utf-8');

$u = usuario_actual();
if (!$u || ($u['rol'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Sin permiso']);
    exit;
}
if (!com_csrf_ok($_POST['csrf'] ?? '')) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'La sesión expiró, recargá la página']);
    exit;
}

/** Carpeta de los archivos a medio subir. */
function stl_tmp_dir() {
    $d = dirname(__DIR__) . '/uploads/tmp';
    if (!is_dir($d)) mkdir($d, 0755, true);
    return $d;
}

/** Borra los pedazos que quedaron colgados hace mas de 6 horas. */
function stl_tmp_limpiar() {
    foreach (glob(stl_tmp_dir() . '/sub-*.part') ?: [] as $f) {
        if (filemtime($f) < time() - 6 * 3600) @unlink($f);
    }
}

// El identificador lo arma el navegador; se acepta solo si es hexadecimal
$sid = strtolower((string) ($_POST['sid'] ?? ''));
if (!preg_match('/^[a-f0-9]{16,40}$/', $sid)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Identificador inválido']);
    exit;
}

$indice = (int) ($_POST['i'] ?? -1);
$destino = stl_tmp_dir() . "/sub-$sid.part";

if ($indice === 0) {
    stl_tmp_limpiar();
    @unlink($destino);                       // arranca de cero
}

if (empty($_FILES['trozo']['tmp_name']) || !is_uploaded_file($_FILES['trozo']['tmp_name'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'No llegó el pedazo']);
    exit;
}

// Tope de seguridad: 80 MB armados. Sin esto, alguien podria llenar el disco.
$yaHay = is_file($destino) ? filesize($destino) : 0;
if ($yaHay + $_FILES['trozo']['size'] > 80 * 1024 * 1024) {
    @unlink($destino);
    http_response_code(413);
    echo json_encode(['ok' => false, 'error' => 'El archivo supera los 80 MB']);
    exit;
}

$ok = file_put_contents($destino, file_get_contents($_FILES['trozo']['tmp_name']), FILE_APPEND | LOCK_EX);
if ($ok === false) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'No se pudo guardar el pedazo']);
    exit;
}

echo json_encode(['ok' => true, 'bytes' => filesize($destino)]);
