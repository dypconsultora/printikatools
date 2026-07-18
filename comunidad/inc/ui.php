<?php
/**
 * Piezas de interfaz compartidas de la plataforma (estilos, sidebar, layout).
 */

/** Estilos base compartidos por todas las pantallas de la plataforma. */
function ui_css() { ?>
<style>
  :root{
    --bg:#0b0e16; --panel:#111624; --panel2:#161c2e; --bd:#232b40;
    --txt:#eef2fa; --txt2:#8b94ab; --accent:#2db7fa; --accent-dim:rgba(45,183,250,.14);
    --ok:#00e676; --bad:#ff5252; --warn:#ffab40; --navy:#192844;
  }
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:'Segoe UI',-apple-system,BlinkMacSystemFont,'Inter',sans-serif;
       background:var(--bg);color:var(--txt);min-height:100vh;line-height:1.55}
  a{color:var(--accent);text-decoration:none}
  .badge{font-size:.62rem;font-weight:700;letter-spacing:.06em;padding:.14rem .45rem;
         border-radius:99px;background:rgba(255,171,64,.15);color:var(--warn);text-transform:uppercase}
  .btn{display:inline-block;background:var(--accent);color:#04121d;border:none;border-radius:8px;
       padding:.7rem 1.1rem;font-weight:700;font-family:inherit;font-size:.88rem;cursor:pointer}
  .btn:hover{opacity:.88}
  .btn.sec{background:var(--panel2);color:var(--txt);border:1px solid var(--bd)}
  .btn.peligro{background:rgba(255,82,82,.13);color:var(--bad);border:1px solid rgba(255,82,82,.35)}
  input,select{background:var(--panel2);border:1px solid var(--bd);border-radius:8px;
       padding:.62rem .75rem;color:var(--txt);font-family:inherit;font-size:.9rem;outline:none;width:100%}
  input:focus,select:focus{border-color:var(--accent);box-shadow:0 0 0 3px var(--accent-dim)}
  label{display:block;font-size:.7rem;font-weight:600;color:var(--txt2);text-transform:uppercase;
        letter-spacing:.05em;margin:.85rem 0 .3rem}
  .msg{font-size:.84rem;padding:.7rem .85rem;border-radius:8px;margin:.9rem 0}
  .msg.bad{background:rgba(255,82,82,.12);border:1px solid rgba(255,82,82,.3);color:var(--bad)}
  .msg.ok{background:rgba(0,230,118,.1);border:1px solid rgba(0,230,118,.3);color:var(--ok)}
  .msg.warn{background:rgba(255,171,64,.12);border:1px solid rgba(255,171,64,.35);color:var(--warn)}
</style>
<?php }

/** Layout centrado tipo tarjeta (login, registro, avisos). Cerrar con ui_tarjeta_fin(). */
function ui_tarjeta_inicio($titulo) { ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($titulo); ?> · Printika Tools</title>
<?php ui_css(); ?>
<style>
  body{display:flex;align-items:center;justify-content:center;padding:1.5rem;background:var(--navy)}
  .tarjeta{background:var(--panel);border:1px solid var(--bd);border-radius:14px;
           padding:2.25rem 2rem;width:100%;max-width:430px;box-shadow:0 4px 40px rgba(0,0,0,.45)}
  .logo{display:block;margin:0 auto 1.4rem;height:72px;width:auto}
  h1{font-size:1.15rem;font-weight:800;text-align:center;margin-bottom:.35rem}
  .sub{font-size:.85rem;color:var(--txt2);text-align:center;margin-bottom:1rem}
  .pie{font-size:.8rem;color:var(--txt2);text-align:center;margin-top:1.2rem}
  form .btn{width:100%;margin-top:1.3rem}
</style>
</head>
<body>
  <div class="tarjeta">
    <img class="logo" src="<?php echo ui_base(); ?>/assets/img/printika-tools-dark.svg" alt="Printika Tools">
<?php }

function ui_tarjeta_fin() { ?>
  </div>
</body>
</html>
<?php }

/** Ruta base del sitio (raiz donde viven /assets y /comunidad). */
function ui_base() {
    // /comunidad/... -> ''  (asume instalacion en la raiz del dominio)
    return '';
}

/**
 * Secciones del menu lateral. Cada item: [icono, titulo, href, disponible].
 * Las que aun no existen quedan con badge PROX. — ir habilitando a medida que se construyan.
 */
function ui_menu() {
    return [
        'Plataforma' => [
            ['🧮', 'Calculadora',  'cotizador/', true],
            ['📦', 'Librería STL', null, false],
            ['🎓', 'Cursos',       null, false],
        ],
        'Mi taller' => [
            ['📋', 'Presupuestos', null, false],
            ['👥', 'Clientes',     null, false],
            ['🧵', 'Stock',        null, false],
            ['💰', 'Ventas',       null, false],
            ['📈', 'Estadísticas', null, false],
            ['⚙️', 'Configuración', null, false],
        ],
        'Soporte' => [
            ['💬', 'WhatsApp', COMUNIDAD_WHATSAPP, true],
        ],
    ];
}

/** Layout con sidebar para las pantallas internas. Cerrar con ui_panel_fin(). */
function ui_panel_inicio($titulo, $usuario, $activo = '', $raiz = '') { ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($titulo); ?> · Printika Tools</title>
<?php ui_css(); ?>
<style>
  .app{display:flex;min-height:100vh}
  .lateral{width:250px;flex-shrink:0;background:var(--panel);border-right:1px solid var(--bd);
           display:flex;flex-direction:column;padding:1.2rem .9rem;position:sticky;top:0;height:100vh;overflow-y:auto}
  .lateral .marca img{height:52px;width:auto;display:block;margin:.2rem auto 1.2rem}
  .grupo{font-size:.62rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;
         color:var(--txt2);margin:1rem .55rem .35rem}
  .item{display:flex;align-items:center;gap:.6rem;padding:.55rem .65rem;border-radius:9px;
        color:var(--txt);font-size:.88rem;margin-bottom:.12rem}
  .item:hover{background:var(--panel2)}
  .item.activo{background:var(--panel2);border:1px solid var(--bd);font-weight:600}
  .item.prox{color:var(--txt2);cursor:default}
  .item.prox:hover{background:none}
  .item .badge{margin-left:auto}
  .perfil{margin-top:auto;border-top:1px solid var(--bd);padding-top:.9rem;font-size:.85rem;
          display:flex;align-items:center;justify-content:space-between;gap:.5rem}
  .perfil .quien{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  .perfil .rol{display:block;font-size:.68rem;color:var(--txt2)}
  .contenido{flex:1;padding:2.2rem 2.4rem;min-width:0}
  .contenido h1{font-size:1.7rem;font-weight:800;letter-spacing:-.02em}
  .contenido .bajada{color:var(--txt2);font-size:.92rem;margin:.3rem 0 1.6rem}
  @media (max-width: 820px){
    .app{flex-direction:column}
    .lateral{width:100%;height:auto;position:static;flex-direction:column}
    .contenido{padding:1.4rem 1.1rem}
  }
</style>
</head>
<body>
<div class="app">
  <aside class="lateral">
    <a class="marca" href="<?php echo $raiz; ?>index.php"><img src="<?php echo ui_base(); ?>/assets/img/printika-tools-dark.svg" alt="Printika Tools"></a>
    <?php foreach (ui_menu() as $grupo => $items): ?>
      <div class="grupo"><?php echo htmlspecialchars($grupo); ?></div>
      <?php foreach ($items as [$icono, $nombre, $href, $ok]): ?>
        <?php if ($ok): ?>
          <a class="item<?php echo $nombre === $activo ? ' activo' : ''; ?>" href="<?php echo str_starts_with((string) $href, 'http') ? htmlspecialchars($href) : $raiz . htmlspecialchars($href); ?>"<?php
            echo str_starts_with((string) $href, 'http') ? ' target="_blank" rel="noopener"' : ''; ?>>
            <span><?php echo $icono; ?></span><?php echo htmlspecialchars($nombre); ?>
          </a>
        <?php else: ?>
          <span class="item prox"><span><?php echo $icono; ?></span><?php echo htmlspecialchars($nombre); ?>
            <span class="badge">Próx.</span></span>
        <?php endif; ?>
      <?php endforeach; ?>
    <?php endforeach; ?>
    <?php if ($usuario['rol'] === 'admin'): ?>
      <div class="grupo">Administración</div>
      <a class="item<?php echo $activo === 'Suscripciones' ? ' activo' : ''; ?>" href="<?php echo $raiz; ?>admin/">
        <span>🛡️</span>Suscripciones
      </a>
    <?php endif; ?>
    <div class="perfil">
      <span class="quien">👤 <?php echo htmlspecialchars($usuario['nombre']); ?>
        <span class="rol"><?php echo $usuario['rol'] === 'admin' ? 'Administrador' : 'Miembro'; ?></span>
      </span>
      <a href="<?php echo $raiz; ?>logout.php" title="Cerrar sesión">Salir</a>
    </div>
  </aside>
  <main class="contenido">
<?php }

function ui_panel_fin() { ?>
  </main>
</div>
</body>
</html>
<?php }
