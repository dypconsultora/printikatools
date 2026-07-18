<?php
/**
 * Registro de nuevos usuarios. La cuenta se crea sin suscripción:
 * un administrador la habilita desde /comunidad/admin/.
 */
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/ui.php';

if (usuario_actual() !== null) {
    header('Location: index.php');
    exit;
}

$error = '';
$creado = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email  = mb_strtolower(trim($_POST['email'] ?? ''));
    $pass   = $_POST['password'] ?? '';
    $pass2  = $_POST['password2'] ?? '';

    if (!com_csrf_ok($_POST['csrf'] ?? '')) {
        $error = 'La sesión expiró, probá de nuevo.';
    } elseif (!com_db_ok()) {
        $error = 'La plataforma está en mantenimiento. Probá en unos minutos.';
    } elseif ($nombre === '' || mb_strlen($nombre) > 120) {
        $error = 'Ingresá tu nombre.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El email no es válido.';
    } elseif (strlen($pass) < 8) {
        $error = 'La contraseña debe tener al menos 8 caracteres.';
    } elseif ($pass !== $pass2) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        try {
            $stmt = com_db()->prepare(
                'INSERT INTO usuarios (nombre, email, pass_hash, rol, creado_en) VALUES (?, ?, ?, ?, NOW())'
            );
            $stmt->execute([$nombre, $email, password_hash($pass, PASSWORD_DEFAULT), 'miembro']);
            com_login($email, $pass);
            $creado = true;
        } catch (PDOException $e) {
            $error = ($e->errorInfo[1] ?? 0) == 1062
                ? 'Ya existe una cuenta con ese email. <a href="login.php">Ingresá acá</a>.'
                : 'No se pudo crear la cuenta. Probá de nuevo.';
        }
    }
}

if ($creado) {
    header('Location: suscripcion.php');
    exit;
}

ui_tarjeta_inicio('Crear cuenta');
?>
    <h1>Crear cuenta</h1>
    <p class="sub">Sumate a la comunidad de impresión 3D</p>

    <?php if (!com_db_ok()): ?>
      <div class="msg warn">La plataforma está en preparación. Muy pronto vas a poder registrarte.</div>
    <?php else: ?>
      <?php if ($error): ?><div class="msg bad"><?php echo $error; ?></div><?php endif; ?>
      <form method="post" autocomplete="on">
        <input type="hidden" name="csrf" value="<?php echo com_csrf(); ?>">
        <label for="nombre">Nombre</label>
        <input type="text" id="nombre" name="nombre" maxlength="120" required autofocus
               value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required
               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
        <label for="password">Contraseña (mínimo 8 caracteres)</label>
        <input type="password" id="password" name="password" minlength="8" required>
        <label for="password2">Repetir contraseña</label>
        <input type="password" id="password2" name="password2" minlength="8" required>
        <button class="btn" type="submit">Crear cuenta</button>
      </form>
      <p class="pie">¿Ya tenés cuenta? <a href="login.php">Ingresá</a></p>
    <?php endif; ?>
<?php ui_tarjeta_fin(); ?>
