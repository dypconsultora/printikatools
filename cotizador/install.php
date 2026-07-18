<?php
/**
 * ===========================================================
 *  INSTALADOR  ·  Calculadora de Costos 3D
 * ===========================================================
 *  Abrilo UNA vez en el navegador (tu-sitio.com/calculadora/install.php),
 *  completá la contraseña y listo. DESPUES borralo del servidor.
 * ===========================================================
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$paso_ok   = false;   // instalacion completada en esta visita
$mensaje   = '';      // mensaje de error
$conecto   = false;   // conexion a MySQL OK
$err_conex = '';
$ya_instalado = false;

// --- Diagnostico de entorno ---
$php_ok  = version_compare(PHP_VERSION, '7.0.0', '>=');
$pdo_ok  = extension_loaded('pdo_mysql');

// --- Probar conexion ---
try {
    db()->query('SELECT 1');
    $conecto = true;
} catch (Exception $e) {
    $err_conex = $e->getMessage();
}

function crear_tablas() {
    db()->exec("CREATE TABLE IF NOT EXISTS app_config (
        clave VARCHAR(64) NOT NULL PRIMARY KEY,
        valor TEXT NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    db()->exec("CREATE TABLE IF NOT EXISTS cotizaciones (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        nombre VARCHAR(255) NOT NULL DEFAULT 'Sin nombre',
        precio VARCHAR(64) NOT NULL DEFAULT '',
        moneda VARCHAR(8) NOT NULL DEFAULT 'ARS',
        datos_json LONGTEXT NOT NULL,
        creado_en DATETIME NOT NULL,
        PRIMARY KEY (id),
        KEY idx_creado (creado_en)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

if ($conecto) {
    $ya_instalado = (obtener_hash_password() !== null);
}

// --- Procesar formulario ---
if ($conecto && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nueva  = $_POST['password']  ?? '';
    $nueva2 = $_POST['password2'] ?? '';
    $actual = $_POST['actual']    ?? '';

    if ($ya_instalado && !verificar_password($actual)) {
        $mensaje = 'La contraseña actual es incorrecta.';
    } elseif (strlen($nueva) < 6) {
        $mensaje = 'La contraseña nueva debe tener al menos 6 caracteres.';
    } elseif ($nueva !== $nueva2) {
        $mensaje = 'Las dos contraseñas no coinciden.';
    } else {
        try {
            crear_tablas();
            $hash = password_hash($nueva, PASSWORD_DEFAULT);
            $stmt = db()->prepare(
                "INSERT INTO app_config (clave, valor) VALUES ('password_hash', ?)
                 ON DUPLICATE KEY UPDATE valor = VALUES(valor)"
            );
            $stmt->execute([$hash]);
            $paso_ok = true;
        } catch (Exception $e) {
            $mensaje = 'Error al crear las tablas: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Instalador · <?php echo htmlspecialchars(APP_NOMBRE); ?></title>
<style>
  :root{--bg:#0a0a0f;--card:#1a1a26;--input:#14141e;--bd:#2a2a3a;--accent:#00D4FF;
        --accent-dim:rgba(0,212,255,.15);--txt:#e8e8f0;--txt2:#8888a0;--ok:#00e676;--bad:#ff5252;--warn:#ffab40}
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:'Inter',-apple-system,BlinkMacSystemFont,sans-serif;background:var(--bg);color:var(--txt);
       min-height:100vh;display:flex;align-items:center;justify-content:center;padding:1.5rem;line-height:1.6}
  .card{background:var(--card);border:1px solid var(--bd);border-radius:14px;padding:2.25rem 2rem;
       width:100%;max-width:440px;box-shadow:0 4px 40px rgba(0,0,0,.5)}
  h1{font-size:1.25rem;font-weight:800;margin-bottom:1.25rem;letter-spacing:-.02em}
  .checks{list-style:none;font-size:.82rem;margin-bottom:1.25rem}
  .checks li{display:flex;gap:.5rem;padding:.25rem 0;color:var(--txt2)}
  .checks .ok{color:var(--ok)} .checks .bad{color:var(--bad)}
  label{display:block;font-size:.72rem;font-weight:600;color:var(--txt2);text-transform:uppercase;
        letter-spacing:.05em;margin:.9rem 0 .35rem}
  input[type=password]{width:100%;background:var(--input);border:1px solid var(--bd);border-radius:8px;
       padding:.65rem .8rem;color:var(--txt);font-family:inherit;font-size:.9rem;outline:none}
  input:focus{border-color:var(--accent);box-shadow:0 0 0 3px var(--accent-dim)}
  button{width:100%;margin-top:1.4rem;background:var(--accent);color:#000;border:none;border-radius:8px;
       padding:.8rem;font-weight:700;font-family:inherit;font-size:.9rem;cursor:pointer}
  button:hover{opacity:.88}
  .msg{font-size:.82rem;padding:.7rem .85rem;border-radius:8px;margin-bottom:1rem}
  .msg.bad{background:rgba(255,82,82,.12);border:1px solid rgba(255,82,82,.3);color:var(--bad)}
  .msg.ok{background:rgba(0,230,118,.1);border:1px solid rgba(0,230,118,.3);color:var(--ok)}
  .msg.warn{background:rgba(255,171,64,.12);border:1px solid rgba(255,171,64,.35);color:var(--warn)}
  code{background:#000;padding:.1rem .35rem;border-radius:4px;font-size:.85em}
  a.btn{display:block;text-align:center;margin-top:1rem;background:var(--accent);color:#000;
       text-decoration:none;border-radius:8px;padding:.8rem;font-weight:700}
  .small{font-size:.78rem;color:var(--txt2);margin-top:.75rem}
</style>
</head>
<body>
  <div class="card">
    <h1>⚙️ Instalador · Calculadora 3D</h1>

    <ul class="checks">
      <li class="<?php echo $php_ok ? 'ok':'bad'; ?>"><?php echo $php_ok?'✓':'✗'; ?> PHP <?php echo PHP_VERSION; ?> <?php echo $php_ok?'(ok)':'(se requiere 7.0+)'; ?></li>
      <li class="<?php echo $pdo_ok ? 'ok':'bad'; ?>"><?php echo $pdo_ok?'✓':'✗'; ?> Extensión MySQL (pdo_mysql) <?php echo $pdo_ok?'disponible':'NO disponible'; ?></li>
      <li class="<?php echo $conecto ? 'ok':'bad'; ?>"><?php echo $conecto?'✓':'✗'; ?> Conexión a la base <?php echo $conecto?'establecida':'fallida'; ?></li>
    </ul>

    <?php if (!$conecto): ?>
      <div class="msg bad">
        No me puedo conectar a la base de datos.<br>
        Revisá los datos en <code>config.php</code> (nombre de base, usuario y contraseña de MySQL).
        <?php if ($err_conex): ?><br><span class="small"><?php echo htmlspecialchars($err_conex); ?></span><?php endif; ?>
      </div>

    <?php elseif ($paso_ok): ?>
      <div class="msg ok">
        ✓ ¡Instalación completada! Las tablas se crearon y la contraseña quedó guardada.
      </div>
      <div class="msg warn">
        🔒 <strong>Importante:</strong> borrá ahora el archivo <code>install.php</code> del servidor por seguridad.
      </div>
      <a class="btn" href="index.php">Ir a la calculadora</a>

    <?php else: ?>
      <?php if ($mensaje): ?><div class="msg bad"><?php echo htmlspecialchars($mensaje); ?></div><?php endif; ?>

      <?php if ($ya_instalado): ?>
        <div class="msg warn">Ya está instalada. Para <strong>cambiar la contraseña</strong>, ingresá la actual y la nueva.</div>
      <?php else: ?>
        <p class="small">Definí la contraseña con la que vas a entrar a la calculadora.</p>
      <?php endif; ?>

      <form method="post" autocomplete="off">
        <?php if ($ya_instalado): ?>
          <label for="actual">Contraseña actual</label>
          <input type="password" id="actual" name="actual" required>
        <?php endif; ?>
        <label for="password">Contraseña <?php echo $ya_instalado ? 'nueva' : ''; ?></label>
        <input type="password" id="password" name="password" minlength="6" required>
        <label for="password2">Repetir contraseña</label>
        <input type="password" id="password2" name="password2" minlength="6" required>
        <button type="submit"><?php echo $ya_instalado ? 'Cambiar contraseña' : 'Instalar'; ?></button>
      </form>
    <?php endif; ?>
  </div>
</body>
</html>
