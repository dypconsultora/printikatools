<?php
/**
 * Registro de nuevos usuarios. La cuenta se crea sin suscripción:
 * un administrador la habilita desde /comunidad/admin/.
 */
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
$creado = false;
// Plan elegido en la landing: gratis entra directo; mensual/anual va al pago
$plan = $_POST['plan'] ?? ($_GET['plan'] ?? 'gratis');
if (!in_array($plan, ['gratis', 'mensual', 'anual'], true)) $plan = 'gratis';
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
    header('Location: ' . ($plan === 'gratis' ? 'index.php' : 'suscripcion.php?plan=' . $plan));
    exit;
}

$PLANES_TXT = ['gratis' => 'Gratuito · $0', 'mensual' => 'Mensual · $18.000/mes', 'anual' => 'Anual · $170.000/año'];

ui_tarjeta_inicio('Crear cuenta');
?>
    <h1>Crear cuenta</h1>
    <p class="sub">Sumate a la comunidad de impresión 3D</p>
    <p style="font-size:13px;margin:-6px 0 14px;text-align:center">
      <span style="display:inline-block;background:var(--accent-tinte,rgba(45,183,250,.12));color:var(--accent,#2db7fa);
            font-weight:600;padding:4px 12px;border-radius:999px">Plan elegido: <?php echo $PLANES_TXT[$plan]; ?></span>
      <?php if ($plan !== 'gratis'): ?><a href="registro.php" style="font-size:12px;margin-left:6px">cambiar</a><?php endif; ?>
    </p>

    <?php if (!com_db_ok()): ?>
      <div class="msg warn">La plataforma está en preparación. Muy pronto vas a poder registrarte.</div>
    <?php else: ?>
      <?php if ($error): ?><div class="msg bad"><?php echo $error; ?></div><?php endif; ?>
      <form method="post" autocomplete="on">
        <input type="hidden" name="csrf" value="<?php echo com_csrf(); ?>">
        <input type="hidden" name="plan" value="<?php echo htmlspecialchars($plan); ?>">
        <label for="nombre">Nombre</label>
        <input type="text" id="nombre" name="nombre" maxlength="120" required autofocus
               value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required
               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
        <label for="password">Contraseña (mínimo 8 caracteres)</label>
        <?php ui_campo_password('password', 'password', 'minlength="8" required autocomplete="new-password"'); ?>
        <label for="password2">Repetir contraseña</label>
        <?php ui_campo_password('password2', 'password2', 'minlength="8" required autocomplete="new-password"'); ?>
        <button class="btn" type="submit">Crear cuenta</button>
      </form>
      <p class="pie">¿Ya tenés cuenta? <a href="login.php">Ingresá</a></p>
    <?php endif; ?>
<?php ui_tarjeta_fin(); ?>
