<?php
/**
 * Arranque comun de la plataforma Comunidad.
 * Carga configuracion, abre la conexion PDO y la sesion.
 *
 * Configuracion: usa comunidad/config.php si existe (plantilla en
 * config.example.php); si no, cae a la del cotizador (misma base de datos).
 */

$__cfg_propio    = __DIR__ . '/../config.php';
$__cfg_cotizador = __DIR__ . '/../cotizador/config.php';
if (is_readable($__cfg_propio)) {
    require_once $__cfg_propio;
} elseif (is_readable($__cfg_cotizador)) {
    require_once $__cfg_cotizador;
}

if (!defined('COMUNIDAD_NOMBRE')) {
    define('COMUNIDAD_NOMBRE', 'Printika Tools · Comunidad');
}
define('COMUNIDAD_WHATSAPP', 'https://wa.me/5491131373425?text=' . rawurlencode('Hola! Quiero activar mi suscripción de Printika Tools.'));

/** Conexion PDO compartida. Devuelve null si no hay config o no conecta. */
function com_db() {
    static $pdo = null, $fallo = false;
    if ($pdo !== null || $fallo) return $pdo;
    if (!defined('DB_HOST')) { $fallo = true; return null; }
    try {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . (defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4');
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (Throwable $e) {
        $fallo = true;
        $pdo = null;
    }
    return $pdo;
}

/** true si la plataforma puede operar (hay config y la base responde). */
function com_db_ok() {
    return com_db() !== null;
}

function com_sesion() {
    if (session_status() === PHP_SESSION_NONE) {
        session_name('ptools');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => !empty($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}
