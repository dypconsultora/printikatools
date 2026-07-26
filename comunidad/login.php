<?php
/** Ingreso a la plataforma Comunidad. */
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/ui.php';

// Sitio en acceso anticipado: sin la clave, todo lleva al "proximamente".
if (!com_preview_ok() && usuario_actual() === null) {
    header('Location: /');
    exit;
}

if (usuario_actual() !== null) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!com_csrf_ok($_POST['csrf'] ?? '')) {
        $error = 'La sesión expiró, probá de nuevo.';
    } elseif (!com_db_ok()) {
        $error = 'La plataforma está en mantenimiento. Probá en unos minutos.';
    } elseif (com_login_bloqueado()) {
        $error = 'Demasiados intentos fallidos. Esperá 15 minutos y probá de nuevo.';
    } elseif (($paso = com_login($_POST['email'] ?? '', $_POST['password'] ?? '')) !== false) {
        header('Location: ' . ($paso === '2fa' ? 'codigo.php' : 'index.php'));
        exit;
    } else {
        com_login_fallo($_POST['email'] ?? '');
        $error = 'Email o contraseña incorrectos.';
    }
}

ui_tarjeta_inicio('Ingresar');
?>
    <h1>Comunidad</h1>
    <p class="sub">Ingresá con tu cuenta de miembro</p>

    <?php if (!com_db_ok()): ?>
      <div class="msg warn">La plataforma está en preparación. Muy pronto vas a poder ingresar.</div>
    <?php else: ?>
      <?php if ($error): ?><div class="msg bad"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
      <form method="post" autocomplete="on">
        <input type="hidden" name="csrf" value="<?php echo com_csrf(); ?>">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required autofocus>
        <label for="password">Contraseña</label>
        <?php ui_campo_password('password', 'password', 'required autocomplete="current-password"'); ?>
        <button class="btn" type="submit">Ingresar</button>
      </form>
      <p class="pie" style="margin-top:16px"><a href="recuperar.php">¿Olvidaste tu contraseña?</a></p>
      <p class="pie" style="margin-top:8px">¿No tenés cuenta? <a href="registro.php">Registrate</a></p>
    <?php endif; ?>
<?php ui_tarjeta_fin(); ?>
