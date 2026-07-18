<?php
/**
 * Autenticacion por contraseña + ayudas de sesion y CSRF.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

function iniciar_sesion() {
    if (session_status() === PHP_SESSION_NONE) {
        session_name('calc3d');
        session_start();
    }
}

function esta_logueado() {
    iniciar_sesion();
    return !empty($_SESSION['auth']) && $_SESSION['auth'] === true;
}

/** Para paginas HTML: si no esta logueado, manda al login. */
function requerir_login() {
    if (!esta_logueado()) {
        header('Location: login.php');
        exit;
    }
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

function obtener_hash_password() {
    try {
        $stmt = db()->prepare('SELECT valor FROM app_config WHERE clave = ? LIMIT 1');
        $stmt->execute(['password_hash']);
        $row = $stmt->fetch();
        return $row ? $row['valor'] : null;
    } catch (Throwable $e) {
        return null;
    }
}

function verificar_password($password) {
    $hash = obtener_hash_password();
    if (!$hash) return false;
    return password_verify($password, $hash);
}

/** Usuario PRO: configurable en app_config (clave 'usuario_pro'); por defecto 'printika'. */
function obtener_usuario_pro() {
    try {
        $stmt = db()->prepare('SELECT valor FROM app_config WHERE clave = ? LIMIT 1');
        $stmt->execute(['usuario_pro']);
        $row = $stmt->fetch();
        return $row ? $row['valor'] : 'printika';
    } catch (Throwable $e) {
        return 'printika';
    }
}

function verificar_credenciales($usuario, $password) {
    return strcasecmp(trim((string) $usuario), obtener_usuario_pro()) === 0
        && verificar_password($password);
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
