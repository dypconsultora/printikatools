<?php
/**
 * Autenticacion por contraseña + ayudas de sesion y CSRF.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

/** Cabeceras de seguridad (el cotizador no pasa por el bootstrap del panel). */
function cot_cabeceras_seguridad() {
    if (headers_sent()) return;
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    if (!empty($_SERVER['HTTPS'])) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
    header("Content-Security-Policy: "
        . "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; "
        . "img-src 'self' data: blob:; font-src 'self'; connect-src 'self'; "
        . "object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'");
}
cot_cabeceras_seguridad();

function iniciar_sesion() {
    if (session_status() === PHP_SESSION_NONE) {
        session_name('calc3d');
        session_start();
    }
}

/**
 * El ingreso "PRO" propio del cotizador se retiro el 2026-07-26: nunca se le
 * fijo contrasena y el acceso pago real vive en /comunidad. Queda en false a
 * proposito, para que ninguna sesion vieja siga valiendo.
 */
function esta_logueado() {
    return false;
}

/** Para la API: si no esta logueado, responde 401 JSON. */
function requerir_login_api() {
    if (!esta_logueado()) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'No autenticado']);
        exit;
    }
}

/**
 * Prueba PRO por tiempo limitado: todo lo PRO habilitado (sin login)
 * hasta esta fecha inclusive. Despues vuelven los candados solos.
 */
define('PRO_TRIAL_HASTA', strtotime('2026-09-02 23:59:59 -03:00'));

function trial_pro_activo() {
    return time() < PRO_TRIAL_HASTA;
}

function token_csrf() {
    iniciar_sesion();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function verificar_csrf($token) {
    iniciar_sesion();
    return !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string) $token);
}
