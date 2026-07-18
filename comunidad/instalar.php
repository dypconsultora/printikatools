<?php
/**
 * ===========================================================
 *  INSTALADOR · Plataforma Comunidad Printika Tools
 * ===========================================================
 *  Crea las tablas `usuarios` y `suscripciones` y el primer
 *  administrador. Abrilo una vez en el navegador y despues
 *  BORRALO del servidor (o dejalo: si ya hay un admin, exige
 *  su contraseña para volver a ejecutarse).
 * ===========================================================
 */
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/ui.php';

$conecto   = com_db_ok();
$pdo_ok    = extension_loaded('pdo_mysql');
$hay_admin = false;
$paso_ok   = false;
$mensaje   = '';

function com_crear_tablas() {
    com_db()->exec("CREATE TABLE IF NOT EXISTS usuarios (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        nombre VARCHAR(120) NOT NULL,
        email VARCHAR(190) NOT NULL,
        pass_hash VARCHAR(255) NOT NULL,
        rol ENUM('admin','miembro') NOT NULL DEFAULT 'miembro',
        creado_en DATETIME NOT NULL,
        ultimo_login DATETIME NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uq_email (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    com_db()->exec("CREATE TABLE IF NOT EXISTS suscripciones (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        usuario_id BIGINT UNSIGNED NOT NULL,
        estado ENUM('activa','cancelada') NOT NULL DEFAULT 'activa',
        desde DATE NOT NULL,
        hasta DATE NULL,
        notas VARCHAR(255) NOT NULL DEFAULT '',
        creado_en DATETIME NOT NULL,
        PRIMARY KEY (id),
        KEY idx_usuario (usuario_id),
        CONSTRAINT fk_susc_usuario FOREIGN KEY (usuario_id)
            REFERENCES usuarios(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

if ($conecto) {
    try {
        $hay_admin = (bool) com_db()->query("SELECT 1 FROM usuarios WHERE rol = 'admin' LIMIT 1")->fetch();
    } catch (Throwable $e) {
        $hay_admin = false; // tablas aun no creadas
    }
}

if ($conecto && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email  = mb_strtolower(trim($_POST['email'] ?? ''));
    $pass   = $_POST['password'] ?? '';
    $pass2  = $_POST['password2'] ?? '';
    $actual = $_POST['actual'] ?? '';

    if ($hay_admin && !com_login_admin_valido($actual)) {
        $mensaje = 'La contraseña del administrador actual es incorrecta.';
    } elseif ($nombre === '') {
        $mensaje = 'Ingresá el nombre del administrador.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensaje = 'El email no es válido.';
    } elseif (strlen($pass) < 8) {
        $mensaje = 'La contraseña debe tener al menos 8 caracteres.';
    } elseif ($pass !== $pass2) {
        $mensaje = 'Las contraseñas no coinciden.';
    } else {
        try {
            com_crear_tablas();
            $stmt = com_db()->prepare(
                "INSERT INTO usuarios (nombre, email, pass_hash, rol, creado_en) VALUES (?, ?, ?, 'admin', NOW())
                 ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), pass_hash = VALUES(pass_hash), rol = 'admin'"
            );
            $stmt->execute([$nombre, $email, password_hash($pass, PASSWORD_DEFAULT)]);
            $paso_ok = true;
        } catch (Throwable $e) {
            $mensaje = 'Error al instalar: ' . $e->getMessage();
        }
    }
}

/** Valida la contraseña de CUALQUIER admin existente (para reejecutar el instalador). */
function com_login_admin_valido($password) {
    foreach (com_db()->query("SELECT pass_hash FROM usuarios WHERE rol = 'admin'")->fetchAll() as $fila) {
        if (password_verify($password, $fila['pass_hash'])) return true;
    }
    return false;
}

ui_tarjeta_inicio('Instalador');
?>
    <h1>⚙️ Instalador · Comunidad</h1>
    <ul style="list-style:none;font-size:.82rem;margin:.9rem 0;color:var(--txt2)">
      <li><?php echo $pdo_ok ? '✅' : '❌'; ?> Extensión MySQL (pdo_mysql)</li>
      <li><?php echo $conecto ? '✅' : '❌'; ?> Conexión a la base de datos</li>
    </ul>

    <?php if (!$conecto): ?>
      <div class="msg bad">
        No hay conexión a la base. Revisá las credenciales en
        <code>comunidad/config.php</code> (o <code>comunidad/cotizador/config.php</code>).
      </div>

    <?php elseif ($paso_ok): ?>
      <div class="msg ok">✓ Instalación completada. Tablas creadas y administrador guardado.</div>
      <div class="msg warn">🔒 Por seguridad, borrá <code>instalar.php</code> del servidor.</div>
      <a class="btn" style="display:block;text-align:center" href="login.php">Ir al login</a>

    <?php else: ?>
      <?php if ($mensaje): ?><div class="msg bad"><?php echo htmlspecialchars($mensaje); ?></div><?php endif; ?>
      <?php if ($hay_admin): ?>
        <div class="msg warn">Ya está instalada. Para crear/actualizar un administrador,
        confirmá con la contraseña de un admin actual.</div>
      <?php else: ?>
        <p class="sub">Creá el primer usuario administrador de la plataforma.</p>
      <?php endif; ?>
      <form method="post" autocomplete="off">
        <?php if ($hay_admin): ?>
          <label for="actual">Contraseña de un admin actual</label>
          <input type="password" id="actual" name="actual" required>
        <?php endif; ?>
        <label for="nombre">Nombre</label>
        <input type="text" id="nombre" name="nombre" required>
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required>
        <label for="password">Contraseña (mínimo 8)</label>
        <input type="password" id="password" name="password" minlength="8" required>
        <label for="password2">Repetir contraseña</label>
        <input type="password" id="password2" name="password2" minlength="8" required>
        <button class="btn" type="submit"><?php echo $hay_admin ? 'Actualizar admin' : 'Instalar'; ?></button>
      </form>
    <?php endif; ?>
<?php ui_tarjeta_fin(); ?>
