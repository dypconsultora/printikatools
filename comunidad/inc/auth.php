<?php
/**
 * Autenticacion de usuarios, suscripciones y CSRF de la plataforma Comunidad.
 */
require_once __DIR__ . '/bootstrap.php';

/** Datos del usuario logueado (fila de `usuarios`) o null. */
function usuario_actual() {
    static $usuario = false;
    if ($usuario !== false) return $usuario;
    com_sesion();
    $usuario = null;
    if (!empty($_SESSION['uid']) && com_db_ok()) {
        $stmt = com_db()->prepare('SELECT * FROM usuarios WHERE id = ? LIMIT 1');
        $stmt->execute([(int) $_SESSION['uid']]);
        $usuario = $stmt->fetch() ?: null;
        if ($usuario === null) {
            unset($_SESSION['uid']); // usuario borrado: limpiar sesion
        }
    }
    return $usuario;
}

function es_admin() {
    $u = usuario_actual();
    return $u !== null && $u['rol'] === 'admin';
}

/** true si el usuario tiene una suscripcion vigente (los admin siempre entran). */
function suscripcion_activa($usuario_id = null) {
    if ($usuario_id === null) {
        $u = usuario_actual();
        if ($u === null) return false;
        if ($u['rol'] === 'admin') return true;
        $usuario_id = (int) $u['id'];
    }
    if (!com_db_ok()) return false;
    $stmt = com_db()->prepare(
        "SELECT 1 FROM suscripciones
          WHERE usuario_id = ? AND estado = 'activa'
            AND (hasta IS NULL OR hasta >= CURDATE())
          LIMIT 1"
    );
    $stmt->execute([$usuario_id]);
    return (bool) $stmt->fetch();
}

/** Vencimiento de la suscripcion vigente (YYYY-MM-DD, null = sin limite, false = sin suscripcion). */
function suscripcion_hasta($usuario_id) {
    if (!com_db_ok()) return false;
    $stmt = com_db()->prepare(
        "SELECT hasta FROM suscripciones
          WHERE usuario_id = ? AND estado = 'activa'
            AND (hasta IS NULL OR hasta >= CURDATE())
          ORDER BY (hasta IS NULL) DESC, hasta DESC LIMIT 1"
    );
    $stmt->execute([(int) $usuario_id]);
    $row = $stmt->fetch();
    return $row ? $row['hasta'] : false;
}

/**
 * Plan vigente del usuario: 'admin', 'anual', 'mensual' o 'gratis'.
 * Todo usuario logueado tiene al menos el plan gratis (calculadora + STL).
 */
function plan_usuario($usuario_id = null) {
    if ($usuario_id === null) {
        $u = usuario_actual();
        if ($u === null) return 'gratis';
        if ($u['rol'] === 'admin') return 'admin';
        $usuario_id = (int) $u['id'];
    }
    if (!com_db_ok()) return 'gratis';
    try {
        $stmt = com_db()->prepare(
            "SELECT plan FROM suscripciones
              WHERE usuario_id = ? AND estado = 'activa'
                AND (hasta IS NULL OR hasta >= CURDATE())
              ORDER BY (hasta IS NULL) DESC, hasta DESC LIMIT 1"
        );
        $stmt->execute([(int) $usuario_id]);
        $row = $stmt->fetch();
    } catch (Throwable $e) {
        return 'gratis'; // columna plan aun no migrada
    }
    if (!$row) return 'gratis';
    return $row['plan'] === 'anual' ? 'anual' : 'mensual';
}

/** true si el usuario ve TODO (plan pago o admin). El plan gratis solo ve calculadora y STL. */
function acceso_total() {
    return in_array(plan_usuario(), ['admin', 'mensual', 'anual'], true);
}

/** Secciones completas del taller: exige login + plan pago; el plan gratis va a elegir plan. */
function requerir_miembro() {
    if (usuario_actual() === null) {
        header('Location: login.php');
        exit;
    }
    if (!acceso_total()) {
        header('Location: suscripcion.php');
        exit;
    }
}

/** Secciones del plan gratis (calculadora, libreria STL): solo exige login. */
function requerir_usuario() {
    if (usuario_actual() === null) {
        header('Location: login.php');
        exit;
    }
}

/** Paginas de administracion: exige rol admin. */
function requerir_admin() {
    if (usuario_actual() === null) {
        header('Location: ../login.php');
        exit;
    }
    if (!es_admin()) {
        http_response_code(403);
        exit('Acceso solo para administradores.');
    }
}

function com_login($email, $password) {
    if (!com_db_ok()) return false;
    $stmt = com_db()->prepare('SELECT * FROM usuarios WHERE email = ? LIMIT 1');
    $stmt->execute([mb_strtolower(trim($email))]);
    $u = $stmt->fetch();
    if (!$u || !password_verify($password, $u['pass_hash'])) return false;
    com_sesion();
    session_regenerate_id(true);
    $_SESSION['uid'] = (int) $u['id'];
    com_db()->prepare('UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?')->execute([(int) $u['id']]);
    return true;
}

function com_logout() {
    com_sesion();
    $_SESSION = [];
    session_destroy();
}

function com_csrf() {
    com_sesion();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function com_csrf_ok($token) {
    com_sesion();
    return !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string) $token);
}
