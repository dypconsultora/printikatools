<?php
/**
 * Recibe el correo que la gente deja en el banner de la portada.
 *
 * Guarda la direccion en la misma lista que el popup del cotizador
 * (novedades_emails, la que se ve en Emails captados) y le manda el correo de
 * bienvenida con los planes. Responde JSON.
 *
 * Lo primero es guardar: el correo puede fallar y se puede reenviar despues
 * desde el panel, pero una direccion perdida no se recupera.
 */
declare(strict_types=1);

require_once __DIR__ . '/comunidad/inc/auth.php';
require_once __DIR__ . '/comunidad/inc/taller.php';

com_sesion();
header('Content-Type: application/json; charset=utf-8');
header('X-Robots-Tag: noindex');

/** Corta la respuesta con un error legible. */
function nov_error(int $codigo, string $texto): void {
    http_response_code($codigo);
    echo json_encode(['ok' => false, 'error' => $texto]);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    nov_error(405, 'Método no permitido.');
}

$datos = json_decode((string) file_get_contents('php://input'), true) ?: [];
$email  = trim((string) ($datos['email'] ?? ''));
$idioma = (($datos['idioma'] ?? '') === 'en') ? 'en' : 'es';
$en     = $idioma === 'en';

// Trampa para robots: el campo esta escondido, una persona no lo completa.
// Se contesta que si para que el robot no insista con otra forma.
if (trim((string) ($datos['website'] ?? '')) !== '') {
    echo json_encode(['ok' => true]);
    exit;
}

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
    nov_error(400, $en ? 'That email address does not look right.' : 'Ese correo no parece válido.');
}

// Tope por sesion, para que nadie use el formulario como maquina de mandar mails
$_SESSION['nov_envios'] = ($_SESSION['nov_envios'] ?? 0) + 1;
if ($_SESSION['nov_envios'] > 5) {
    nov_error(429, $en ? 'Too many tries. Give it a few minutes.' : 'Demasiados intentos. Esperá unos minutos.');
}

if (!com_db_ok()) {
    nov_error(503, $en ? 'We cannot save it right now. Try again in a few minutes.'
                       : 'Ahora no podemos guardarlo. Probá en unos minutos.');
}

try {
    taller_migrar();   // crea la tabla y sus columnas si es la primera vez
    taller_captar_email($email, $idioma, 'banner');
} catch (Throwable $e) {
    error_log('[novedades] guardar: ' . $e->getMessage());
    nov_error(500, $en ? 'We could not save it. Try again.' : 'No pudimos guardarlo. Probá de nuevo.');
}

// Ya esta guardado: se le contesta al navegador antes de mandar el correo, que
// tarda unos segundos, para que el banner conteste al toque
echo json_encode(['ok' => true]);
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
} else {
    @ob_end_flush();
    @flush();
}

// La bienvenida sale una sola vez por direccion
try {
    $stmt = com_db()->prepare('SELECT id FROM novedades_emails
                               WHERE email = ? AND bienvenida_en IS NULL LIMIT 1');
    $stmt->execute([$email]);
    if ($fila = $stmt->fetch()) {
        require_once __DIR__ . '/comunidad/inc/correo.php';
        if (correo_bienvenida_novedades($email, $idioma)) {
            com_db()->prepare('UPDATE novedades_emails SET bienvenida_en = NOW() WHERE id = ?')
                    ->execute([$fila['id']]);
        }
    }
} catch (Throwable $e) {
    // La direccion ya quedo guardada: se puede reenviar desde el panel
    error_log('[novedades] bienvenida: ' . $e->getMessage());
}
