<?php
/**
 * Pantalla para elegir la contraseña nueva, a la que se llega desde el
 * enlace del correo. El token vale una sola vez y vence en 2 horas.
 */
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/ui.php';

com_sesion();   // antes de imprimir nada: com_csrf() la necesita
$token = trim($_GET['t'] ?? ($_POST['t'] ?? ''));
$u = com_reset_usuario($token);
$listo = false;
$error = '';

if ($u && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $pass  = $_POST['password'] ?? '';
    $pass2 = $_POST['password2'] ?? '';
    if (!com_csrf_ok($_POST['csrf'] ?? '')) {
        $error = 'La sesión expiró, probá de nuevo.';
    } elseif (strlen($pass) < 8) {
        $error = 'La contraseña debe tener al menos 8 caracteres.';
    } elseif ($pass !== $pass2) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        com_reset_aplicar($u['id'], $pass);
        // Entra directo con la contraseña nueva
        com_sesion();
        session_regenerate_id(true);
        $_SESSION['uid'] = (int) $u['id'];
        $listo = true;
    }
}

ui_tarjeta_inicio('Nueva contraseña');
?>
<?php if ($listo): ?>
    <h1>¡Contraseña cambiada!</h1>
    <p class="sub">Ya podés usar tu cuenta con la contraseña nueva.</p>
    <div class="msg ok"><?php echo ui_icono('check', 16); ?>
      <span>Te dejamos la sesión iniciada.</span></div>
    <a class="btn" href="index.php" style="width:100%;margin-top:20px">Entrar a la plataforma</a>

<?php elseif (!$u): ?>
    <h1>Enlace no válido</h1>
    <p class="sub">El enlace venció, ya se usó o está incompleto.</p>
    <div class="msg warn"><?php echo ui_icono('alerta', 16); ?>
      <span>Los enlaces duran 2 horas y sirven una sola vez. Pedí uno nuevo.</span></div>
    <a class="btn" href="recuperar.php" style="width:100%;margin-top:20px">Pedir un enlace nuevo</a>

<?php else: ?>
    <h1>Elegí tu contraseña nueva</h1>
    <p class="sub">Para la cuenta <strong><?php echo htmlspecialchars($u['email']); ?></strong></p>

    <?php if ($error): ?><div class="msg bad"><?php echo ui_icono('alerta', 16); ?><span><?php echo htmlspecialchars($error); ?></span></div><?php endif; ?>

    <form method="post" autocomplete="on">
      <input type="hidden" name="csrf" value="<?php echo com_csrf(); ?>">
      <input type="hidden" name="t" value="<?php echo htmlspecialchars($token); ?>">
      <label for="password">Contraseña nueva (mínimo 8 caracteres)</label>
      <?php ui_campo_password('password', 'password', 'minlength="8" required autofocus autocomplete="new-password"'); ?>
      <label for="password2">Repetir contraseña</label>
      <?php ui_campo_password('password2', 'password2', 'minlength="8" required autocomplete="new-password"'); ?>
      <button class="btn" type="submit">Guardar contraseña</button>
    </form>
<?php endif; ?>
<?php ui_tarjeta_fin(); ?>
