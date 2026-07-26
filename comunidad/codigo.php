<?php
/**
 * Segundo paso del ingreso cuando la cuenta tiene doble factor:
 * pide el código de 6 dígitos que llegó por correo.
 */
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/ui.php';

com_sesion();
$pendiente = (int) ($_SESSION['2fa_pendiente'] ?? 0);
if (!$pendiente) {
    header('Location: ' . (usuario_actual() !== null ? 'index.php' : 'login.php'));
    exit;
}

$stmt = com_db()->prepare('SELECT * FROM usuarios WHERE id = ? LIMIT 1');
$stmt->execute([$pendiente]);
$u = $stmt->fetch();
if (!$u) {
    unset($_SESSION['2fa_pendiente']);
    header('Location: login.php');
    exit;
}

$error = '';
$aviso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!com_csrf_ok($_POST['csrf'] ?? '')) {
        $error = 'La sesión expiró, probá de nuevo.';
    } elseif (($_POST['accion'] ?? '') === 'reenviar') {
        com_2fa_enviar($u, $motivo);
        $aviso = $motivo ? $motivo : 'Te enviamos un código nuevo.';
    } else {
        switch (com_2fa_validar($pendiente, $_POST['codigo'] ?? '')) {
            case 'ok':
                unset($_SESSION['2fa_pendiente']);
                session_regenerate_id(true);
                $_SESSION['uid'] = $pendiente;
                com_db()->prepare('UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?')->execute([$pendiente]);
                header('Location: index.php');
                exit;
            case 'vencido':
                $error = 'El código venció. Pedí uno nuevo.';
                break;
            case 'bloqueado':
                $error = 'Demasiados intentos. Pedí un código nuevo.';
                break;
            default:
                $error = 'El código no es correcto.';
        }
    }
}

// Muestra el correo a medias: ad****@gmail.com
$destino = com_2fa_destino($u);
[$antes, $dominio] = array_pad(explode('@', $destino, 2), 2, '');
$oculto = mb_substr($antes, 0, 2) . str_repeat('*', max(3, mb_strlen($antes) - 2)) . '@' . $dominio;

ui_tarjeta_inicio('Código de acceso');
?>
    <h1>Revisá tu correo</h1>
    <p class="sub">Te enviamos un código de 6 dígitos a <strong><?php echo htmlspecialchars($oculto); ?></strong></p>

    <?php if ($aviso): ?><div class="msg ok"><?php echo ui_icono('check', 16); ?><span><?php echo htmlspecialchars($aviso); ?></span></div><?php endif; ?>
    <?php if ($error): ?><div class="msg bad"><?php echo ui_icono('alerta', 16); ?><span><?php echo htmlspecialchars($error); ?></span></div><?php endif; ?>

    <style>
      #codigo{text-align:center;font-size:30px;letter-spacing:12px;font-weight:700;
              font-variant-numeric:tabular-nums;padding-left:12px}
    </style>

    <form method="post" autocomplete="off">
      <input type="hidden" name="csrf" value="<?php echo com_csrf(); ?>">
      <label for="codigo">Código de 6 dígitos</label>
      <input id="codigo" type="text" name="codigo" inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
             required autofocus autocomplete="one-time-code" placeholder="······">
      <button class="btn" type="submit">Entrar</button>
    </form>

    <form method="post" style="margin-top:10px">
      <input type="hidden" name="csrf" value="<?php echo com_csrf(); ?>">
      <input type="hidden" name="accion" value="reenviar">
      <button class="btn sec" type="submit" style="width:100%">Enviarme un código nuevo</button>
    </form>

    <p class="pie">El código vence en 10 minutos · <a href="logout.php">Cancelar</a></p>
<?php ui_tarjeta_fin(); ?>
