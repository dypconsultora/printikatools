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
    com_exigir_email_verificado();
    if (!acceso_total()) {
        // Se avisa QUE seccion quiso abrir, para poder mostrarle un adelanto de
        // esa y no un cartel generico que no le dice nada
        $clave = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''), '.php');
        header('Location: suscripcion.php?bloqueado=' . urlencode($clave));
        exit;
    }
}

/**
 * El plan que la persona venia a contratar, si venia a contratar uno.
 *
 * La intencion de pagar se pierde muy facil: alguien elige "Pro anual" en la
 * portada, descubre que ya tenia cuenta, ingresa... y termina en el panel sin
 * haber pagado nunca, sin entender que le falto. Por eso el plan se anota en la
 * sesion apenas aparece un ?plan= en cualquier pantalla del ingreso, y se usa
 * recien cuando la persona quedo adentro de verdad.
 *
 * Con $plan guarda; sin argumentos, lee. Nunca crea una cuenta nueva: el pago
 * se le suma a la cuenta que ya tiene.
 */
function com_plan_pendiente($plan = null) {
    com_sesion();
    // El mensual solo vale si esta a la venta: si no, un enlace guardado de
    // antes llevaria a la persona a un checkout que la va a rebotar igual
    $validos = COMUNIDAD_MENSUAL_VISIBLE ? ['mensual', 'anual'] : ['anual'];
    if ($plan !== null) {
        if (in_array($plan, $validos, true)) $_SESSION['plan_elegido'] = $plan;
        else unset($_SESSION['plan_elegido']);
    }
    return $_SESSION['plan_elegido'] ?? '';
}

/**
 * A donde mandar a alguien que acaba de entrar: al pago si venia a pagar, y si
 * no al panel de siempre. El plan pendiente se consume aca (una sola vez).
 */
function com_destino_ingreso() {
    $plan = com_plan_pendiente();
    if ($plan === '') return 'index.php';
    unset($_SESSION['plan_elegido']);
    return 'mp_checkout.php?plan=' . $plan;
}

/** Secciones del plan gratis (calculadora, libreria STL): solo exige login. */
function requerir_usuario() {
    if (usuario_actual() === null) {
        header('Location: login.php');
        exit;
    }
    com_exigir_email_verificado();
}

/** Si la cuenta no confirmó el correo, no entra a ninguna pantalla. */
function com_exigir_email_verificado($raiz = '') {
    if (com_email_verificado()) return;
    header('Location: ' . $raiz . 'confirmar.php');
    exit;
}

/** Paginas de administracion: exige rol admin. */
function requerir_admin() {
    if (usuario_actual() === null) {
        header('Location: ../login.php');
        exit;
    }
    com_exigir_email_verificado('../');
    if (!es_admin()) {
        http_response_code(403);
        exit('Acceso solo para administradores.');
    }
}

/**
 * Verificación de email: agrega las columnas la primera vez. Los usuarios
 * que YA existían quedan verificados para no dejar a nadie afuera.
 */
function com_verif_migrar() {
    static $listo = false;
    if ($listo || !com_db_ok()) return;
    try {
        $col = com_db()->query("SELECT COUNT(*) c FROM information_schema.columns
                                WHERE table_schema = DATABASE() AND table_name = 'usuarios'
                                  AND column_name = 'email_verificado'")->fetch();
        if ((int) $col['c'] === 0) {
            com_db()->exec("ALTER TABLE usuarios
                ADD COLUMN email_verificado TINYINT(1) NOT NULL DEFAULT 1,
                ADD COLUMN verif_token VARCHAR(64) NOT NULL DEFAULT '',
                ADD COLUMN verif_expira DATETIME NULL,
                ADD COLUMN verif_enviado DATETIME NULL");
            // Las cuentas anteriores a esta funcion quedan verificadas
            com_db()->exec("UPDATE usuarios SET email_verificado = 1");
        }
        $listo = true;
    } catch (Throwable $e) { /* sin columnas, la verificacion no aplica */ }
}

/** true si el usuario ya confirmó su correo (o si la columna no existe todavía). */
function com_email_verificado($u = null) {
    if ($u === null) $u = usuario_actual();
    if ($u === null) return false;
    if (!array_key_exists('email_verificado', $u)) return true;
    return (int) $u['email_verificado'] === 1;
}

/** Genera un token nuevo de verificación y lo guarda (vence en 48 horas). */
function com_verif_token_nuevo($usuario_id) {
    com_verif_migrar();
    $token = bin2hex(random_bytes(32));
    com_db()->prepare("UPDATE usuarios SET verif_token = ?, verif_expira = DATE_ADD(NOW(), INTERVAL 48 HOUR),
                       verif_enviado = NOW() WHERE id = ?")
        ->execute([$token, (int) $usuario_id]);
    return $token;
}

/** URL absoluta del enlace de confirmación. */
function com_verif_url($token) {
    $esquema = !empty($_SERVER['HTTPS']) ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'printikatools.com';
    return $esquema . '://' . $host . '/comunidad/verificar.php?t=' . $token;
}

/**
 * Manda (o reenvía) el correo de confirmación. Devuelve true si salió.
 * Espera 2 minutos entre envíos para que no se pueda usar como spam.
 */
function com_verif_enviar($usuario, &$error = null) {
    require_once __DIR__ . '/correo.php';
    // El token se crea siempre: si el correo falla, el enlace sigue siendo
    // valido y se puede reintentar sin dejar la cuenta sin salida.
    $token = com_verif_token_nuevo($usuario['id']);
    if (!correo_disponible()) { $error = 'El envío de correos no está configurado.'; return false; }

    $url = com_verif_url($token);
    $nombre = trim($usuario['nombre'] ?? '');
    $primer = $nombre !== '' ? explode(' ', $nombre)[0] : '';

    $html = correo_plantilla(
        'Confirmá tu correo',
        [
            ($primer !== '' ? '¡Hola, ' . htmlspecialchars($primer, ENT_QUOTES, 'UTF-8') . '! ' : '¡Hola! ') .
            'Gracias por sumarte a <strong>Printika Tools</strong>, la comunidad con las herramientas ' .
            'que tu taller de impresión 3D necesita.',
            'Para activar tu cuenta y empezar a usar la calculadora, la librería STL y los recursos, ' .
            'confirmá que este correo es tuyo:',
        ],
        ['texto' => 'Confirmar mi correo', 'url' => $url],
        'El enlace vence en 48 horas. Si el botón no funciona, copiá y pegá esta dirección en tu navegador:<br>' .
        '<span style="color:#3d4759;word-break:break-all">' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '</span>' .
        '<br><br>Si no creaste ninguna cuenta, podés ignorar este mensaje.'
    );
    $texto = "Confirmá tu correo

Gracias por sumarte a Printika Tools.
"
           . "Activá tu cuenta entrando a este enlace (vence en 48 horas):
$url

"
           . "Si no creaste ninguna cuenta, ignorá este mensaje.";

    return correo_enviar($usuario['email'], $nombre, 'Confirmá tu correo · Printika Tools', $html, $texto, $error);
}

/** true si se puede reenviar (pasaron al menos 2 minutos del último envío). */
function com_verif_puede_reenviar($usuario) {
    if (empty($usuario['verif_enviado'])) return true;
    $stmt = com_db()->prepare('SELECT verif_enviado <= DATE_SUB(NOW(), INTERVAL 2 MINUTE) FROM usuarios WHERE id = ?');
    $stmt->execute([(int) $usuario['id']]);
    return (int) $stmt->fetchColumn() === 1;
}

/**
 * Doble factor por correo (2FA) para las cuentas de administración.
 * Al entrar se manda un codigo de 6 digitos al correo elegido.
 */
function com_2fa_migrar() {
    static $listo = false;
    if ($listo || !com_db_ok()) return;
    try {
        $col = com_db()->query("SELECT COUNT(*) c FROM information_schema.columns
                                WHERE table_schema = DATABASE() AND table_name = 'usuarios'
                                  AND column_name = 'dosfa_activo'")->fetch();
        if ((int) $col['c'] === 0) {
            com_db()->exec("ALTER TABLE usuarios
                ADD COLUMN dosfa_activo TINYINT(1) NOT NULL DEFAULT 0,
                ADD COLUMN dosfa_email VARCHAR(190) NOT NULL DEFAULT '',
                ADD COLUMN dosfa_codigo VARCHAR(255) NOT NULL DEFAULT '',
                ADD COLUMN dosfa_expira DATETIME NULL,
                ADD COLUMN dosfa_intentos TINYINT UNSIGNED NOT NULL DEFAULT 0");
        }
        $listo = true;
    } catch (Throwable $e) { /* sin columnas, el 2FA no aplica */ }
}

/** true si el usuario tiene el doble factor activado. */
function com_2fa_activo($u) {
    return !empty($u['dosfa_activo']) && (int) $u['dosfa_activo'] === 1;
}

/** Correo al que se manda el código (el elegido, o el de la cuenta). */
function com_2fa_destino($u) {
    $e = trim($u['dosfa_email'] ?? '');
    return $e !== '' ? $e : $u['email'];
}

/** Genera el código de 6 dígitos, lo guarda cifrado y lo manda por correo. */
function com_2fa_enviar($u, &$error = null) {
    com_2fa_migrar();
    require_once __DIR__ . '/correo.php';
    $codigo = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    com_db()->prepare("UPDATE usuarios SET dosfa_codigo = ?, dosfa_expira = DATE_ADD(NOW(), INTERVAL 10 MINUTE),
                       dosfa_intentos = 0 WHERE id = ?")
        ->execute([password_hash($codigo, PASSWORD_DEFAULT), (int) $u['id']]);

    $destino = com_2fa_destino($u);
    $nombre = trim($u['nombre'] ?? '');
    $html = correo_plantilla(
        'Tu código para entrar',
        [
            'Alguien está entrando a la administración de <strong>Printika Tools</strong>. ' .
            'Si sos vos, usá este código:',
            '<div style="margin:8px 0 4px;padding:18px 24px;background:#f2f7fd;border:1px solid #d5e4f5;' .
            'border-radius:12px;text-align:center;font-size:34px;font-weight:bold;letter-spacing:10px;' .
            'color:#131a27">' . $codigo . '</div>',
        ],
        null,
        'El código vence en 10 minutos.<br><br><strong>¿No fuiste vos?</strong> Alguien tiene tu contraseña: ' .
        'cambiala cuanto antes desde "¿Olvidaste tu contraseña?".'
    );
    $texto = "Tu código para entrar a Printika Tools: $codigo

Vence en 10 minutos.
"
           . "Si no fuiste vos, cambiá tu contraseña cuanto antes.";
    return correo_enviar($destino, $nombre, 'Código de acceso: ' . $codigo . ' · Printika Tools', $html, $texto, $error);
}

/** Valida el código escrito. Devuelve 'ok', 'malo', 'vencido' o 'bloqueado'. */
function com_2fa_validar($usuario_id, $codigo) {
    com_2fa_migrar();
    $stmt = com_db()->prepare('SELECT dosfa_codigo, dosfa_intentos,
                                      (dosfa_expira IS NOT NULL AND dosfa_expira > NOW()) AS vigente
                               FROM usuarios WHERE id = ?');
    $stmt->execute([(int) $usuario_id]);
    $f = $stmt->fetch();
    if (!$f || $f['dosfa_codigo'] === '') return 'vencido';
    if ((int) $f['dosfa_intentos'] >= 5) return 'bloqueado';
    if ((int) $f['vigente'] !== 1) return 'vencido';

    if (password_verify(trim($codigo), $f['dosfa_codigo'])) {
        com_db()->prepare("UPDATE usuarios SET dosfa_codigo = '', dosfa_expira = NULL, dosfa_intentos = 0
                           WHERE id = ?")->execute([(int) $usuario_id]);
        return 'ok';
    }
    com_db()->prepare('UPDATE usuarios SET dosfa_intentos = dosfa_intentos + 1 WHERE id = ?')
        ->execute([(int) $usuario_id]);
    return 'malo';
}

/**
 * Recuperación de contraseña: agrega las columnas la primera vez.
 */
function com_reset_migrar() {
    static $listo = false;
    if ($listo || !com_db_ok()) return;
    try {
        $col = com_db()->query("SELECT COUNT(*) c FROM information_schema.columns
                                WHERE table_schema = DATABASE() AND table_name = 'usuarios'
                                  AND column_name = 'reset_token'")->fetch();
        if ((int) $col['c'] === 0) {
            com_db()->exec("ALTER TABLE usuarios
                ADD COLUMN reset_token VARCHAR(64) NOT NULL DEFAULT '',
                ADD COLUMN reset_expira DATETIME NULL,
                ADD COLUMN reset_enviado DATETIME NULL");
        }
        $listo = true;
    } catch (Throwable $e) { /* sin columnas, la recuperacion no aplica */ }
}

/**
 * Manda el correo para restablecer la contraseña.
 * Siempre devuelve true hacia afuera (para no revelar qué emails existen);
 * internamente solo envía si la cuenta existe.
 */
function com_reset_enviar($email) {
    com_reset_migrar();
    require_once __DIR__ . '/correo.php';
    if (!com_db_ok()) return;

    $stmt = com_db()->prepare('SELECT * FROM usuarios WHERE email = ? LIMIT 1');
    $stmt->execute([mb_strtolower(trim($email))]);
    $u = $stmt->fetch();
    if (!$u) return;                       // no existe: silencio
    // No reenviar antes de 2 minutos (comparado en SQL por la zona horaria)
    if (!empty($u['reset_enviado'])) {
        $reciente = com_db()->prepare('SELECT reset_enviado > DATE_SUB(NOW(), INTERVAL 2 MINUTE) FROM usuarios WHERE id = ?');
        $reciente->execute([(int) $u['id']]);
        if ((int) $reciente->fetchColumn() === 1) return;
    }

    $token = bin2hex(random_bytes(32));
    com_db()->prepare("UPDATE usuarios SET reset_token = ?, reset_expira = DATE_ADD(NOW(), INTERVAL 2 HOUR),
                       reset_enviado = NOW() WHERE id = ?")
        ->execute([$token, (int) $u['id']]);

    $esquema = !empty($_SERVER['HTTPS']) ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'printikatools.com';
    $url = $esquema . '://' . $host . '/comunidad/restablecer.php?t=' . $token;

    $nombre = trim($u['nombre'] ?? '');
    $primer = $nombre !== '' ? explode(' ', $nombre)[0] : '';
    $html = correo_plantilla(
        'Restablecé tu contraseña',
        [
            ($primer !== '' ? '¡Hola, ' . htmlspecialchars($primer, ENT_QUOTES, 'UTF-8') . '! ' : '¡Hola! ') .
            'Pediste crear una contraseña nueva para tu cuenta de <strong>Printika Tools</strong>.',
            'Hacé clic en el botón y elegí la contraseña que quieras:',
        ],
        ['texto' => 'Crear contraseña nueva', 'url' => $url],
        'El enlace vence en 2 horas y sirve una sola vez. Si el botón no funciona, copiá y pegá esta dirección:<br>' .
        '<span style="color:#3d4759;word-break:break-all">' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '</span>' .
        '<br><br><strong>¿No pediste esto?</strong> Ignorá el mensaje: tu contraseña actual sigue funcionando y nadie la vio.'
    );
    $texto = "Restablecé tu contraseña

Pediste crear una contraseña nueva para Printika Tools.
"
           . "Entrá a este enlace (vence en 2 horas y sirve una sola vez):
$url

"
           . "Si no lo pediste, ignorá el mensaje: tu contraseña actual sigue funcionando.";

    correo_enviar($u['email'], $nombre, 'Restablecé tu contraseña · Printika Tools', $html, $texto);
}

/** Devuelve el usuario dueño de un token de reseteo válido, o null. */
function com_reset_usuario($token) {
    com_reset_migrar();
    if (!preg_match('/^[a-f0-9]{64}$/', (string) $token) || !com_db_ok()) return null;
    // La comparacion va en SQL: PHP y MySQL pueden estar en zonas distintas
    $stmt = com_db()->prepare('SELECT * FROM usuarios
                                WHERE reset_token = ? AND reset_expira IS NOT NULL
                                  AND reset_expira > NOW() LIMIT 1');
    $stmt->execute([$token]);
    return $stmt->fetch() ?: null;
}

/** Guarda la contraseña nueva, quema el token y deja la cuenta verificada. */
function com_reset_aplicar($usuario_id, $password) {
    com_db()->prepare("UPDATE usuarios SET pass_hash = ?, reset_token = '', reset_expira = NULL,
                       email_verificado = 1, verif_token = '' WHERE id = ?")
        ->execute([password_hash($password, PASSWORD_DEFAULT), (int) $usuario_id]);
    // Cerrar la puerta a quien estuviera intentando entrar por fuerza bruta
    com_login_ok_limpiar();
}

/**
 * Freno de fuerza bruta: cuenta los intentos fallidos por IP en los ultimos
 * 15 minutos. La tabla se crea sola la primera vez.
 */
function com_login_intentos_migrar() {
    static $listo = false;
    if ($listo || !com_db_ok()) return;
    try {
        com_db()->exec("CREATE TABLE IF NOT EXISTS login_intentos (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            ip VARCHAR(45) NOT NULL,
            email VARCHAR(190) NOT NULL DEFAULT '',
            momento DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY idx_ip_momento (ip, momento)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $listo = true;
    } catch (Throwable $e) { /* sin tabla, el freno no aplica */ }
}

/** IP del visitante (considera proxys del hosting). */
function com_ip() {
    foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $k) {
        if (!empty($_SERVER[$k])) {
            $ip = trim(explode(',', $_SERVER[$k])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) return mb_substr($ip, 0, 45);
        }
    }
    return '0.0.0.0';
}

/** true si esta IP supero el limite de intentos fallidos (10 en 15 minutos). */
function com_login_bloqueado() {
    com_login_intentos_migrar();
    if (!com_db_ok()) return false;
    try {
        $stmt = com_db()->prepare("SELECT COUNT(*) c FROM login_intentos
                                    WHERE ip = ? AND momento > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
        $stmt->execute([com_ip()]);
        return (int) $stmt->fetch()['c'] >= 10;
    } catch (Throwable $e) {
        return false;
    }
}

/** Registra un intento fallido y limpia los viejos. */
function com_login_fallo($email) {
    com_login_intentos_migrar();
    if (!com_db_ok()) return;
    try {
        com_db()->prepare('INSERT INTO login_intentos (ip, email, momento) VALUES (?, ?, NOW())')
            ->execute([com_ip(), mb_substr((string) $email, 0, 190)]);
        if (random_int(1, 20) === 1) {
            com_db()->exec("DELETE FROM login_intentos WHERE momento < DATE_SUB(NOW(), INTERVAL 1 DAY)");
        }
    } catch (Throwable $e) { /* no bloquear el login por esto */ }
}

/** Borra los intentos de la IP tras un login correcto. */
function com_login_ok_limpiar() {
    if (!com_db_ok()) return;
    try {
        com_db()->prepare('DELETE FROM login_intentos WHERE ip = ?')->execute([com_ip()]);
    } catch (Throwable $e) { /* nada */ }
}

function com_login($email, $password) {
    if (!com_db_ok()) return false;
    $stmt = com_db()->prepare('SELECT * FROM usuarios WHERE email = ? LIMIT 1');
    $stmt->execute([mb_strtolower(trim($email))]);
    $u = $stmt->fetch();
    if (!$u || !password_verify($password, $u['pass_hash'])) return false;
    com_sesion();
    session_regenerate_id(true);

    // Con doble factor: la sesion queda a la espera del codigo
    com_2fa_migrar();
    if (com_2fa_activo($u)) {
        $_SESSION['2fa_pendiente'] = (int) $u['id'];
        unset($_SESSION['uid']);
        com_2fa_enviar($u);
        com_login_ok_limpiar();
        return '2fa';
    }

    $_SESSION['uid'] = (int) $u['id'];
    com_db()->prepare('UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?')->execute([(int) $u['id']]);
    com_login_ok_limpiar();
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
