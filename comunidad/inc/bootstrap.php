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
define('COMUNIDAD_PRECIO_MENSUAL', 18000);
define('COMUNIDAD_PRECIO_ANUAL', 170000);
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

/** Lee un valor de la tabla config (null si no existe). */
function cfg_get($clave) {
    if (!com_db_ok()) return null;
    try {
        $stmt = com_db()->prepare('SELECT valor FROM config WHERE clave = ? LIMIT 1');
        $stmt->execute([$clave]);
        $row = $stmt->fetch();
        return $row ? $row['valor'] : null;
    } catch (Throwable $e) {
        return null;
    }
}

/** Guarda un valor en la tabla config. */
function cfg_set($clave, $valor) {
    com_db()->prepare('INSERT INTO config (clave, valor) VALUES (?, ?)
                       ON DUPLICATE KEY UPDATE valor = VALUES(valor)')
        ->execute([$clave, $valor]);
}

/** true si la plataforma puede operar (hay config y la base responde). */
function com_db_ok() {
    return com_db() !== null;
}

/**
 * Porton de acceso anticipado: mientras el sitio no se lanza, la landing y el
 * ingreso a la comunidad requieren una clave. Se guarda solo el hash.
 * Para lanzar el sitio al publico, poner COM_PREVIEW_ACTIVO en false.
 */
define('COM_PREVIEW_ACTIVO', true);
define('COM_PREVIEW_CLAVE_HASH', 'bc803cf09c73d136d64df1625c46ce48ced7c604d8614cad1e72e7e3ca9efb18');
define('COM_PREVIEW_COOKIE', 'pt_preview');

function com_preview_cookie_valor() {
    return hash('sha256', COM_PREVIEW_CLAVE_HASH . 'ptools-preview-2026');
}

function com_preview_ok() {
    if (!COM_PREVIEW_ACTIVO) return true;
    return hash_equals(com_preview_cookie_valor(), (string) ($_COOKIE[COM_PREVIEW_COOKIE] ?? ''));
}

/** Valida la clave ingresada y deja la cookie de acceso por 30 dias. */
function com_preview_activar($clave) {
    if (!hash_equals(COM_PREVIEW_CLAVE_HASH, hash('sha256', (string) $clave))) return false;
    setcookie(COM_PREVIEW_COOKIE, com_preview_cookie_valor(), [
        'expires'  => time() + 60 * 60 * 24 * 30,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']),
        'httponly' => false,
        'samesite' => 'Lax',
    ]);
    return true;
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
