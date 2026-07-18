<?php
// === Ingreso a la versión PRO (usuario + clave) ===
require_once __DIR__ . '/auth.php';
iniciar_sesion();

if (esta_logueado()) {
    header('Location: index.php');
    exit;
}

$error = '';
$sin_instalar = (obtener_hash_password() === null);

// Freno anti fuerza bruta: tras 6 intentos fallidos, 10 minutos de espera.
$bloqueado_hasta = $_SESSION['login_lock'] ?? 0;
$bloqueado = time() < $bloqueado_hasta;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$sin_instalar && !$bloqueado) {
    $usuario = $_POST['usuario'] ?? '';
    $pass    = $_POST['password'] ?? '';
    if (verificar_credenciales($usuario, $pass)) {
        session_regenerate_id(true);
        $_SESSION['auth'] = true;
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
        unset($_SESSION['login_fails'], $_SESSION['login_lock']);
        header('Location: index.php');
        exit;
    } else {
        usleep(700000); // pequeña demora anti fuerza bruta
        $_SESSION['login_fails'] = ($_SESSION['login_fails'] ?? 0) + 1;
        if ($_SESSION['login_fails'] >= 6) {
            $_SESSION['login_lock'] = time() + 600;
            $_SESSION['login_fails'] = 0;
            $bloqueado = true;
        }
        $error = 'Usuario o contraseña incorrectos';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Acceso PRO · Calculadora de Costos 3D</title>
<style>
  :root {
    --bg-primary:#0a0a0f; --bg-card:#1a1a26; --bg-input:#14141e;
    --border-color:#2a2a3a; --accent:#00D4FF; --accent-dim:rgba(0,212,255,.15);
    --text-primary:#e8e8f0; --text-secondary:#8888a0; --danger:#ff5252; --radius:12px;
    --grad-start:#fff;
  }
  [data-theme="light"] {
    --bg-primary:#f4f7fb; --bg-card:#ffffff; --bg-input:#eff3f9;
    --border-color:#d6dee9; --accent:#0090c9; --accent-dim:rgba(0,144,201,.12);
    --text-primary:#16202e; --text-secondary:#51617a;
    --grad-start:#16202e;
  }
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:'Inter',-apple-system,BlinkMacSystemFont,sans-serif;background:var(--bg-primary);
       color:var(--text-primary);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:1.5rem}
  .login-card{background:var(--bg-card);border:1px solid var(--border-color);border-radius:var(--radius);
       padding:2.5rem 2rem;width:100%;max-width:380px;box-shadow:0 4px 40px rgba(0,0,0,.35)}
  .login-logo{display:block;margin:0 auto 1.2rem;height:64px;width:auto}
  .logo-light{display:none}
  [data-theme="light"] .logo-light{display:block}
  [data-theme="light"] .logo-dark{display:none}
  .login-card h1{font-size:1.25rem;font-weight:800;text-align:center;letter-spacing:-.02em;margin-bottom:.4rem;
       background:linear-gradient(135deg,var(--grad-start) 30%,var(--accent));-webkit-background-clip:text;
       -webkit-text-fill-color:transparent;background-clip:text}
  .login-card > p{font-size:.8rem;color:var(--text-secondary);text-align:center;margin-bottom:1.75rem}
  .badge{display:inline-block;font-size:.6rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;
       background:linear-gradient(135deg,var(--accent),#7c4dff);color:#000;padding:.15rem .5rem;border-radius:4px;
       vertical-align:middle;margin-left:4px}
  label{display:block;font-size:.72rem;font-weight:600;color:var(--text-secondary);
       text-transform:uppercase;letter-spacing:.05em;margin-bottom:.4rem;margin-top:1rem}
  input[type=text],input[type=password]{width:100%;background:var(--bg-input);border:1px solid var(--border-color);
       border-radius:8px;padding:.7rem .85rem;color:var(--text-primary);font-family:inherit;font-size:.9rem;outline:none}
  input:focus{border-color:var(--accent);box-shadow:0 0 0 3px var(--accent-dim)}
  button{width:100%;margin-top:1.4rem;background:var(--accent);color:#fff;border:none;border-radius:8px;
       padding:.8rem;font-family:inherit;font-size:.9rem;font-weight:700;cursor:pointer;transition:opacity .2s}
  [data-theme="light"] button{color:#fff}
  button:hover{opacity:.88}
  .error{background:rgba(255,82,82,.12);border:1px solid rgba(255,82,82,.3);color:var(--danger);
       font-size:.8rem;text-align:center;padding:.6rem;border-radius:8px;margin-bottom:.4rem}
  .warn{background:rgba(255,171,64,.12);border:1px solid rgba(255,171,64,.35);color:#ffab40;
       font-size:.8rem;text-align:center;padding:.8rem;border-radius:8px;line-height:1.5}
  .warn a{color:#ffab40;font-weight:700}
  .volver{display:block;text-align:center;margin-top:1.1rem;font-size:.8rem;color:var(--text-secondary);text-decoration:none}
  .volver:hover{color:var(--accent)}
</style>
<script>
  // Mismo tema elegido en la calculadora
  (function () {
    try {
      if (localStorage.getItem('calc3d-theme') === 'light') {
        document.documentElement.setAttribute('data-theme', 'light');
      }
    } catch (e) {}
  })();
</script>
</head>
<body>
  <form class="login-card" method="post" autocomplete="off">
    <a href="https://printika3d.com">
      <img src="../../assets/img/Innovacion-en-3D.svg" alt="Printika 3D" class="login-logo logo-light">
      <img src="../../assets/img/Innovacion-en-3D-dark.svg" alt="Printika 3D" class="login-logo logo-dark">
    </a>
    <h1>Acceso versi&oacute;n PRO <span class="badge">PRO</span></h1>
    <p>Ingres&aacute; tu usuario y contrase&ntilde;a de suscriptor</p>

    <?php if ($sin_instalar): ?>
      <div class="warn">
        Todavía no está instalada.<br>
        Abrí <a href="install.php">install.php</a> para crear la base de datos y la contraseña.
      </div>
    <?php elseif ($bloqueado): ?>
      <div class="error">Demasiados intentos fallidos. Probá de nuevo en unos minutos.</div>
    <?php else: ?>
      <?php if ($error): ?><div class="error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
      <label for="usuario">Usuario</label>
      <input type="text" id="usuario" name="usuario" autofocus required>
      <label for="password">Contraseña</label>
      <input type="password" id="password" name="password" required>
      <button type="submit">Entrar a la versi&oacute;n PRO</button>
    <?php endif; ?>

    <a class="volver" href="index.php">&larr; Volver a la calculadora</a>
  </form>
</body>
</html>
