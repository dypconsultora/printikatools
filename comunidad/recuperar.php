<?php
/**
 * "Olvidé mi contraseña": pide el email y manda el enlace para restablecerla.
 * Nunca dice si el email existe o no (para no filtrar quién tiene cuenta).
 */
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/ui.php';

if (usuario_actual() !== null) {
    header('Location: index.php');
    exit;
}

$enviado = false;
$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = mb_strtolower(trim($_POST['email'] ?? ''));
    if (!com_csrf_ok($_POST['csrf'] ?? '')) {
        $error = 'La sesión expiró, probá de nuevo.';
    } elseif (!com_db_ok()) {
        $error = 'La plataforma está en mantenimiento. Probá en unos minutos.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Escribí un email válido.';
    } elseif (com_login_bloqueado()) {
        $error = 'Demasiados intentos. Esperá 15 minutos y probá de nuevo.';
    } else {
        com_reset_enviar($email);   // silencioso si no existe
        $enviado = true;
    }
}

ui_tarjeta_inicio('Recuperar contraseña');
?>
<?php if ($enviado): ?>
    <h1>Revisá tu correo</h1>
    <p class="sub">Si <strong><?php echo htmlspecialchars($email); ?></strong> tiene una cuenta,
       le enviamos un enlace para crear una contraseña nueva.</p>
    <div class="msg ok"><?php echo ui_icono('check', 16); ?>
      <span>El enlace vence en 2 horas.</span></div>
    <?php ui_aviso_spam(); ?>
    <p class="pie"><a href="login.php">Volver a ingresar</a></p>

<?php else: ?>
    <h1>Recuperar contraseña</h1>
    <p class="sub">Escribí tu email y te mandamos un enlace para crear una nueva.</p>

    <?php if ($error): ?><div class="msg bad"><?php echo ui_icono('alerta', 16); ?><span><?php echo htmlspecialchars($error); ?></span></div><?php endif; ?>

    <form method="post" autocomplete="on">
      <input type="hidden" name="csrf" value="<?php echo com_csrf(); ?>">
      <label for="email">Email</label>
      <input type="email" id="email" name="email" required autofocus autocomplete="email"
             value="<?php echo htmlspecialchars($email); ?>">
      <button class="btn" type="submit">Enviarme el enlace</button>
    </form>
    <p class="pie">¿Te acordaste? <a href="login.php">Ingresá</a></p>
<?php endif; ?>
<?php ui_tarjeta_fin(); ?>
