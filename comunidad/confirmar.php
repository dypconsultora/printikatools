<?php
/**
 * Pantalla de espera: la cuenta existe pero todavía no confirmó el correo.
 * Desde acá se puede reenviar el mail de confirmación.
 */
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/ui.php';

$u = usuario_actual();
if ($u === null) {
    header('Location: login.php');
    exit;
}
com_verif_migrar();
// Ya confirmó: adentro
if (com_email_verificado($u)) {
    header('Location: index.php');
    exit;
}

$aviso = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'reenviar') {
    if (!com_csrf_ok($_POST['csrf'] ?? '')) {
        $error = 'La sesión expiró, probá de nuevo.';
    } elseif (!com_verif_puede_reenviar($u)) {
        $error = 'Recién te enviamos un correo. Esperá un par de minutos antes de pedir otro.';
    } elseif (com_verif_enviar($u, $motivo)) {
        $aviso = 'Te reenviamos el correo de confirmación.';
    } else {
        $error = $motivo ?: 'No pudimos enviar el correo. Probá de nuevo en unos minutos.';
    }
}

ui_tarjeta_inicio('Confirmá tu correo');
?>
    <h1>Confirmá tu correo</h1>
    <p class="sub">Te enviamos un correo a <strong><?php echo htmlspecialchars($u['email']); ?></strong>
       con un enlace para activar tu cuenta.</p>

    <?php if ($aviso): ?><div class="msg ok"><?php echo ui_icono('check', 16); ?><span><?php echo htmlspecialchars($aviso); ?></span></div><?php endif; ?>
    <?php if ($error): ?><div class="msg bad"><?php echo ui_icono('alerta', 16); ?><span><?php echo htmlspecialchars($error); ?></span></div><?php endif; ?>

    <div class="msg warn" style="margin-top:14px">
      <?php echo ui_icono('alerta', 16); ?>
      <span>Hasta que confirmes, la cuenta queda en espera. Revisá también la carpeta de correo no deseado.</span>
    </div>

    <form method="post" style="margin-top:6px">
      <input type="hidden" name="csrf" value="<?php echo com_csrf(); ?>">
      <input type="hidden" name="accion" value="reenviar">
      <button class="btn" type="submit" style="width:100%">Reenviar el correo</button>
    </form>

    <p class="pie">¿Te equivocaste de correo? <a href="logout.php">Cerrá sesión</a> y creá la cuenta de nuevo.</p>
<?php ui_tarjeta_fin(); ?>
