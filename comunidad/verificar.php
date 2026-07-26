<?php
/**
 * Valida el enlace de confirmación del correo. Si el token es válido,
 * activa la cuenta, deja la sesión iniciada y entra a la plataforma.
 */
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/ui.php';

com_verif_migrar();
$token = trim($_GET['t'] ?? '');
$estado = 'invalido';   // invalido | vencido | ok | yaestaba

if (preg_match('/^[a-f0-9]{64}$/', $token) && com_db_ok()) {
    $stmt = com_db()->prepare('SELECT * FROM usuarios WHERE verif_token = ? LIMIT 1');
    $stmt->execute([$token]);
    $u = $stmt->fetch();

    if ($u) {
        if (!empty($u['verif_expira']) && strtotime($u['verif_expira']) < time()) {
            $estado = 'vencido';
            $usuario_vencido = $u;
        } else {
            com_db()->prepare("UPDATE usuarios SET email_verificado = 1, verif_token = '', verif_expira = NULL
                               WHERE id = ?")->execute([(int) $u['id']]);
            // Dejamos la sesión iniciada: entra directo
            com_sesion();
            session_regenerate_id(true);
            $_SESSION['uid'] = (int) $u['id'];
            $estado = 'ok';
            $nombre = trim($u['nombre'] ?? '');
        }
    } elseif (usuario_actual() !== null && com_email_verificado()) {
        $estado = 'yaestaba';
    }
} elseif (usuario_actual() !== null && com_email_verificado()) {
    $estado = 'yaestaba';
}

// Reenviar desde la pantalla de vencido
$aviso = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'reenviar' && !empty($usuario_vencido)) {
    if (!com_csrf_ok($_POST['csrf'] ?? '')) {
        $error = 'La sesión expiró, probá de nuevo.';
    } elseif (com_verif_enviar($usuario_vencido, $motivo)) {
        $aviso = 'Listo, te enviamos un enlace nuevo.';
    } else {
        $error = $motivo ?: 'No pudimos enviar el correo. Probá de nuevo en unos minutos.';
    }
}

ui_tarjeta_inicio('Confirmación de correo');
?>
<?php if ($estado === 'ok'): ?>
    <h1>¡Correo confirmado!</h1>
    <p class="sub"><?php echo $nombre !== '' ? htmlspecialchars(explode(' ', $nombre)[0]) . ', tu' : 'Tu'; ?>
       cuenta ya está activa. Bienvenida/o a la comunidad.</p>
    <div class="msg ok"><?php echo ui_icono('check', 16); ?>
      <span>Ya podés usar la calculadora, la librería STL y los recursos.</span></div>
    <?php $plan_pendiente = $_SESSION['plan_elegido'] ?? ''; unset($_SESSION['plan_elegido']); ?>
    <a class="btn" href="<?php echo $plan_pendiente ? 'suscripcion.php?plan=' . htmlspecialchars($plan_pendiente) : 'index.php'; ?>"
       style="width:100%;margin-top:20px"><?php echo $plan_pendiente ? 'Continuar con mi plan' : 'Entrar a la plataforma'; ?></a>

<?php elseif ($estado === 'yaestaba'): ?>
    <h1>Tu correo ya estaba confirmado</h1>
    <p class="sub">No hace falta hacer nada más.</p>
    <a class="btn" href="index.php" style="width:100%;margin-top:20px">Ir a la plataforma</a>

<?php elseif ($estado === 'vencido'): ?>
    <h1>El enlace venció</h1>
    <p class="sub">Los enlaces de confirmación duran 48 horas. Te mandamos uno nuevo y listo.</p>
    <?php if ($aviso): ?><div class="msg ok"><?php echo ui_icono('check', 16); ?><span><?php echo htmlspecialchars($aviso); ?></span></div><?php endif; ?>
    <?php if ($error): ?><div class="msg bad"><?php echo ui_icono('alerta', 16); ?><span><?php echo htmlspecialchars($error); ?></span></div><?php endif; ?>
    <?php if (!$aviso): ?>
    <form method="post">
      <input type="hidden" name="csrf" value="<?php echo com_csrf(); ?>">
      <input type="hidden" name="accion" value="reenviar">
      <button class="btn" type="submit" style="width:100%;margin-top:16px">Enviarme un enlace nuevo</button>
    </form>
    <?php endif; ?>

<?php else: ?>
    <h1>Enlace no válido</h1>
    <p class="sub">Puede que ya lo hayas usado o que el enlace esté incompleto.</p>
    <div class="msg warn"><?php echo ui_icono('alerta', 16); ?>
      <span>Ingresá con tu cuenta y desde ahí podés pedir un correo nuevo.</span></div>
    <a class="btn" href="login.php" style="width:100%;margin-top:20px">Ingresar</a>
<?php endif; ?>
<?php ui_tarjeta_fin(); ?>
