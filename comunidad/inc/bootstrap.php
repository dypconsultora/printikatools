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

/**
 * Cabeceras de seguridad. Se mandan desde PHP y no desde .htaccess porque
 * el hosting no siempre aplica las reglas de Apache.
 *  - SAMEORIGIN: nadie puede embeber el sitio en otro dominio (clickjacking).
 *    El panel embebe el cotizador desde el MISMO dominio, asi que sigue andando.
 *  - nosniff: el navegador respeta el tipo declarado y no adivina.
 *  - HSTS: obliga HTTPS por un año.
 */
function com_cabeceras_seguridad() {
    if (headers_sent()) return;
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(), usb=()');
    if (!empty($_SERVER['HTTPS'])) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
    // Politica de contenido: define de donde puede cargar cosas la pagina.
    // Todo lo nuestro es local (fuentes, GSAP, jsPDF), asi que solo hay que
    // permitir ademas las miniaturas y los videos de YouTube.
    // 'unsafe-inline' sigue siendo necesario porque el sitio usa scripts y
    // estilos escritos dentro del HTML; aun asi, esto bloquea que alguien
    // inyecte un script de otro dominio, que es el ataque que importa.
    header("Content-Security-Policy: "
        . "default-src 'self'; "
        . "script-src 'self' 'unsafe-inline'; "
        . "style-src 'self' 'unsafe-inline'; "
        . "img-src 'self' data: blob: https://img.youtube.com https://i.ytimg.com; "
        . "font-src 'self'; "
        . "connect-src 'self'; "
        . "frame-src 'self' https://www.youtube-nocookie.com https://www.youtube.com; "
        . "media-src 'self' blob:; "
        . "object-src 'none'; "
        . "base-uri 'self'; "
        . "form-action 'self'; "
        . "frame-ancestors 'self'");
}
com_cabeceras_seguridad();

if (!defined('COMUNIDAD_NOMBRE')) {
    define('COMUNIDAD_NOMBRE', 'Printika Tools · Comunidad');
}
define('COMUNIDAD_PRECIO_MENSUAL', 18000);
define('COMUNIDAD_PRECIO_ANUAL', 180000);

/**
 * ¿Se ofrece el plan mensual?
 *
 * En false queda a la venta solo el anual (y el gratuito): desaparece de la
 * portada, de "Tu plan", del correo de bienvenida y de las preguntas
 * frecuentes, y el checkout deja de aceptarlo aunque alguien tenga guardado el
 * enlace viejo.
 *
 * PARA VOLVER A MOSTRARLO: cambiar false por true en la linea de abajo. Eso
 * alcanza para TODO el sitio. Las dos unicas excepciones son los archivos que
 * leen los buscadores con IA, que son de texto y no pasan por PHP: hay que
 * reponer a mano el plan mensual en llms.txt y en pricing.md (los dos tienen
 * una nota adentro recordandolo).
 *
 * A quien YA tiene el plan mensual no le pasa nada: le sigue funcionando, lo ve
 * en "Tu plan" y se le renueva igual. Esto solo saca el cartel de la vidriera.
 */
define('COMUNIDAD_MENSUAL_VISIBLE', false);

/**
 * true si al usuario hay que mostrarle el plan mensual: o porque esta a la
 * venta, o porque es el plan que ya tiene contratado.
 */
function com_mostrar_mensual($plan_actual = '') {
    return COMUNIDAD_MENSUAL_VISIBLE || $plan_actual === 'mensual';
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

/**
 * Limpieza unica: borra de la base los restos del acceso "PRO" del cotizador
 * viejo (el usuario y su contrasena). Ese ingreso se retiro el 2026-07-26;
 * el acceso pago real vive en la plataforma.
 *
 * La llama el panel de administracion, que ya esta conectado a la base, para
 * no agregarle ni una consulta a la calculadora publica. Corre una sola vez.
 * Devuelve el texto a mostrarle a la administradora, o '' si no hay nada que
 * decir (ya se hizo antes, o el cotizador viejo usa otra base).
 */
function cot_retirar_acceso_pro() {
    if (!com_db_ok() || cfg_get('cot_pro_retirado') !== null) return '';
    try {
        $db = com_db();
        if (!$db->query("SHOW TABLES LIKE 'app_config'")->fetch()) {
            return ''; // el cotizador viejo no vive en esta base: nada que hacer
        }
        $stmt = $db->prepare("DELETE FROM app_config WHERE clave IN ('password_hash', 'usuario_pro')");
        $stmt->execute();
        $borrados = $stmt->rowCount();
        cfg_set('cot_pro_retirado', (string) $borrados);
        return $borrados > 0
            ? 'Se retiró el acceso PRO viejo del cotizador: se borraron el usuario y la contraseña que habían quedado guardados.'
            : 'Se revisó el cotizador viejo: no había ningún usuario ni contraseña guardados.';
    } catch (Throwable $e) {
        return '';
    }
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
define('COM_PREVIEW_ACTIVO', false);
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
