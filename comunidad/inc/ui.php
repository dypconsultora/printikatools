<?php
/**
 * Piezas de interfaz compartidas de la plataforma.
 * Sistema de diseño: dark minimal, tipografía Inter, íconos SVG (Lucide),
 * espaciado en múltiplos de 4px, una sola acción primaria por pantalla.
 */

/** Íconos SVG (Lucide, 24x24, stroke). Uso: ui_icono('usuarios') */
function ui_icono($nombre, $tam = 18) {
    static $trazos = [
        'inicio'       => '<path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>',
        'calculadora'  => '<rect width="16" height="20" x="4" y="2" rx="2"/><line x1="8" x2="16" y1="6" y2="6"/><line x1="16" x2="16" y1="14" y2="18"/><path d="M16 10h.01"/><path d="M12 10h.01"/><path d="M8 10h.01"/><path d="M12 14h.01"/><path d="M8 14h.01"/><path d="M12 18h.01"/><path d="M8 18h.01"/>',
        'libreria'     => '<path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/>',
        'cursos'       => '<path d="M21.42 10.922a1 1 0 0 0-.019-1.838L12.83 5.18a2 2 0 0 0-1.66 0L2.6 9.08a1 1 0 0 0 0 1.832l8.57 3.908a2 2 0 0 0 1.66 0z"/><path d="M22 10v6"/><path d="M6 12.5V16a6 3 0 0 0 12 0v-3.5"/>',
        'presupuestos' => '<rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M12 11h4"/><path d="M12 16h4"/><path d="M8 11h.01"/><path d="M8 16h.01"/>',
        'clientes'     => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'stock'        => '<path d="M12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83Z"/><path d="m22 17.65-9.17 4.16a2 2 0 0 1-1.66 0L2 17.65"/><path d="m22 12.65-9.17 4.16a2 2 0 0 1-1.66 0L2 12.65"/>',
        'ventas'       => '<rect width="20" height="12" x="2" y="6" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01"/><path d="M18 12h.01"/>',
        'estadisticas' => '<path d="M3 3v16a2 2 0 0 0 2 2h16"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/>',
        'configuracion'=> '<path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/>',
        'whatsapp'     => '<path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/>',
        'admin'        => '<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1 1 0 0 1 1.52 0C14.5 3.8 17 5 19 5a1 1 0 0 1 1 1z"/>',
        'salir'        => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/>',
        'etiqueta'     => '<path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z"/><circle cx="7.5" cy="7.5" r=".5" fill="currentColor"/>',
        'flecha'       => '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>',
        'sol'          => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/>',
        'luna'         => '<path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/>',
        'alerta'       => '<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/><path d="M12 9v4"/><path d="M12 17h.01"/>',
        'soporte'      => '<path d="M3 14h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-7a9 9 0 0 1 18 0v7a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3"/>',
        'rayo'         => '<path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"/>',
        'nube'         => '<path d="M12 13v8"/><path d="M4 14.899A7 7 0 1 1 15.71 8h1.79a4.5 4.5 0 0 1 2.5 8.242"/><path d="m8 17 4-4 4 4"/>',
        'check'        => '<path d="M21.801 10A10 10 0 1 1 17 3.335"/><path d="m9 11 3 3L22 4"/>',
    ];
    $d = $trazos[$nombre] ?? $trazos['inicio'];
    return '<svg class="ico" width="' . $tam . '" height="' . $tam . '" viewBox="0 0 24 24" fill="none" '
         . 'stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
         . $d . '</svg>';
}

/** Estilos base compartidos por todas las pantallas de la plataforma. */
function ui_css() { ?>
<script>(function(){if(localStorage.getItem('ptools_tema')==='light'){document.documentElement.setAttribute('data-theme','light');}})();
function ptTema(t){document.documentElement.setAttribute('data-theme',t==='light'?'light':'dark');localStorage.setItem('ptools_tema',t);}</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  :root{
    color-scheme:dark;
    --bg:#0e131c; --surface:#141b28; --surface-2:#1a2334; --raised:#202b40;
    --bd:#243048; --bd-suave:#1c2638;
    --txt:#e8edf5; --txt-2:#9aa6bc; --txt-3:#5f6b82;
    --accent:#2db7fa; --accent-hover:#54c5fb; --accent-tinte:rgba(45,183,250,.10); --accent-ink:#06202f;
    --ok:#3ecf8e; --ok-tinte:rgba(62,207,142,.10);
    --bad:#f4747c; --bad-tinte:rgba(244,116,124,.10);
    --warn:#e8b04b; --warn-tinte:rgba(232,176,75,.10);
    --radio:8px; --radio-g:12px;
  }
  :root[data-theme="light"]{
    color-scheme:light;
    --bg:#f3f5f9; --surface:#ffffff; --surface-2:#eef1f6; --raised:#e2e7f0;
    --bd:#d5dce8; --bd-suave:#e5eaf2;
    --txt:#182136; --txt-2:#5b6579; --txt-3:#959eb1;
    --accent:#1194d6; --accent-hover:#0d81bd; --accent-tinte:rgba(17,148,214,.10); --accent-ink:#ffffff;
    --ok:#14915f; --ok-tinte:rgba(20,145,95,.10);
    --bad:#d63848; --bad-tinte:rgba(214,56,72,.09);
    --warn:#a26a08; --warn-tinte:rgba(226,168,60,.14);
  }
  .logo-claro{display:none !important}
  :root[data-theme="light"] .logo-claro{display:block !important}
  :root[data-theme="light"] .logo-oscuro{display:none !important}
  *{box-sizing:border-box;margin:0;padding:0}
  html{-webkit-text-size-adjust:100%}
  body{font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
       background:var(--bg);color:var(--txt);min-height:100vh;line-height:1.55;
       font-size:15px;-webkit-font-smoothing:antialiased}
  a{color:var(--accent);text-decoration:none}
  a:hover{color:var(--accent-hover)}
  .ico{flex-shrink:0;vertical-align:-3px}

  .badge{display:inline-block;font-size:8.5px;font-weight:600;letter-spacing:.04em;
         padding:2px 6px;border-radius:99px;border:1px solid var(--bd);
         color:var(--txt-3);text-transform:uppercase;line-height:1.5;white-space:nowrap}

  .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;
       background:var(--accent);color:var(--accent-ink);border:1px solid transparent;
       border-radius:var(--radio);padding:0 16px;height:40px;font-weight:600;
       font-family:inherit;font-size:14px;cursor:pointer;white-space:nowrap;
       transition:background-color .15s ease,border-color .15s ease,color .15s ease}
  .btn:hover{background:var(--accent-hover);color:var(--accent-ink)}
  .btn.sec{background:transparent;color:var(--txt);border-color:var(--bd)}
  .btn.sec:hover{background:var(--surface-2);border-color:var(--raised)}
  .btn.peligro{background:transparent;color:var(--bad);border-color:transparent}
  .btn.peligro:hover{background:var(--bad-tinte);color:var(--bad)}
  .btn.chico{height:32px;padding:0 12px;font-size:13px;border-radius:6px}

  input,select{background:var(--surface-2);border:1px solid var(--bd);border-radius:var(--radio);
       padding:0 12px;height:40px;color:var(--txt);font-family:inherit;font-size:14px;
       outline:none;width:100%;transition:border-color .15s ease,box-shadow .15s ease}
  input::placeholder{color:var(--txt-3)}
  input:focus,select:focus{border-color:var(--accent);box-shadow:0 0 0 3px var(--accent-tinte)}
  :is(a,button,input,select):focus-visible{outline:2px solid var(--accent);outline-offset:2px}
  label{display:block;font-size:12px;font-weight:500;color:var(--txt-2);margin:16px 0 6px}

  .msg{display:flex;gap:10px;align-items:flex-start;font-size:13.5px;line-height:1.5;
       padding:12px 14px;border-radius:var(--radio);margin:14px 0;border:1px solid}
  .msg .ico{margin-top:1px}
  .msg.bad{background:var(--bad-tinte);border-color:rgba(244,116,124,.25);color:var(--bad)}
  .msg.ok{background:var(--ok-tinte);border-color:rgba(62,207,142,.25);color:var(--ok)}
  .msg.warn{background:var(--warn-tinte);border-color:rgba(232,176,75,.25);color:var(--warn)}

  @media (prefers-reduced-motion: reduce){
    *,*::before,*::after{transition-duration:.01ms !important;animation-duration:.01ms !important}
  }
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
  body{display:flex;align-items:center;justify-content:center;padding:24px;background:var(--bg)}
  .tarjeta{background:var(--surface);border:1px solid var(--bd-suave);border-radius:var(--radio-g);
           padding:40px 36px;width:100%;max-width:400px}
  .logo{display:block;margin:0 auto 28px;height:112px;width:auto}
  h1{font-size:19px;font-weight:700;text-align:center;letter-spacing:-.01em;margin-bottom:6px}
  .sub{font-size:13.5px;color:var(--txt-2);text-align:center;margin-bottom:8px}
  .pie{font-size:13px;color:var(--txt-2);text-align:center;margin-top:24px}
  form .btn{width:100%;margin-top:24px}
  @media (max-width:480px){ .tarjeta{padding:32px 24px} }
</style>
</head>
<body>
  <main class="tarjeta">
    <img class="logo logo-oscuro" src="<?php echo ui_base(); ?>/assets/img/printika-tools-dark.svg" alt="Printika Tools">
    <img class="logo logo-claro" src="<?php echo ui_base(); ?>/assets/img/printika-tools.svg" alt="Printika Tools">
<?php }

function ui_tarjeta_fin() { ?>
  </main>
</body>
</html>
<?php }

/** Ruta base del sitio (raiz donde viven /assets y /comunidad). */
function ui_base() {
    return '';
}

/**
 * Secciones del menu lateral. Cada item: [icono, titulo, href, disponible].
 * Las que aun no existen quedan con badge Pronto — ir habilitando a medida que se construyan.
 */
function ui_menu() {
    return [
        'Plataforma' => [
            ['calculadora',  'Calculadora',   'cotizador/', true],
            ['libreria',     'Librería STL',  null, false],
        ],
        'Mi taller' => [
            ['presupuestos', 'Presupuestos',  'presupuestos.php', true],
            ['etiqueta',     'Productos',     'productos.php', true],
            ['clientes',     'Clientes',      'clientes.php', true],
            ['stock',        'Stock',         null, false],
            ['ventas',       'Ventas',        null, false],
            ['estadisticas', 'Estadísticas',  null, false],
            ['configuracion','Configuración', null, false],
        ],
        'Soporte' => [
            ['whatsapp', 'WhatsApp', COMUNIDAD_WHATSAPP, true],
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
  .lateral{width:258px;flex-shrink:0;background:var(--surface);border-right:1px solid var(--bd-suave);
           display:flex;flex-direction:column;padding:20px 12px 16px;
           position:sticky;top:0;height:100vh;overflow-y:auto}
  .lateral .marca{display:block;padding:0 10px;margin-bottom:24px}
  .lateral .marca img{width:100%;max-width:214px;height:auto;display:block}
  .grupo{font-size:10.5px;font-weight:600;letter-spacing:.09em;text-transform:uppercase;
         color:var(--txt-3);margin:18px 10px 6px}
  .item{display:flex;align-items:center;gap:10px;padding:8px 10px;border-radius:var(--radio);
        color:var(--txt-2);font-size:13.5px;font-weight:500;margin-bottom:1px;cursor:pointer;
        white-space:nowrap;transition:background-color .15s ease,color .15s ease}
  .item .ico{color:var(--txt-3);transition:color .15s ease}
  .item:hover{background:var(--surface-2);color:var(--txt)}
  .item:hover .ico{color:var(--txt-2)}
  .item.activo{background:var(--accent-tinte);color:var(--accent)}
  .item.activo .ico{color:var(--accent)}
  .item.prox{cursor:default;color:var(--txt-3)}
  .item.prox:hover{background:none;color:var(--txt-3)}
  .item.prox:hover .ico{color:var(--txt-3)}
  .item .badge{margin-left:auto}
  .tema{margin-top:auto;display:grid;grid-template-columns:1fr 1fr;gap:4px;
        background:var(--surface-2);border:1px solid var(--bd-suave);border-radius:var(--radio);
        padding:4px;margin-bottom:12px}
  .tema-btn{display:flex;align-items:center;justify-content:center;gap:7px;height:32px;
        background:none;border:none;border-radius:6px;color:var(--txt-3);font-family:inherit;
        font-size:12.5px;font-weight:500;cursor:pointer;
        transition:background-color .15s ease,color .15s ease}
  .tema-btn:hover{color:var(--txt-2)}
  :root:not([data-theme="light"]) .tema-btn[data-tema="dark"],
  :root[data-theme="light"] .tema-btn[data-tema="light"]{
        background:var(--surface);color:var(--txt);box-shadow:0 1px 2px rgba(0,0,0,.18)}
  .perfil{border-top:1px solid var(--bd-suave);padding:14px 10px 0;
          display:flex;align-items:center;gap:10px}
  .perfil .avatar{width:32px;height:32px;border-radius:99px;background:var(--surface-2);
          border:1px solid var(--bd);display:flex;align-items:center;justify-content:center;
          font-size:13px;font-weight:600;color:var(--accent);flex-shrink:0}
  .perfil .quien{min-width:0;flex:1}
  .perfil .nombre{font-size:13px;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  .perfil .rol{display:block;font-size:11px;color:var(--txt-3)}
  .perfil .salir{color:var(--txt-3);display:flex;padding:6px;border-radius:6px;
          transition:color .15s ease,background-color .15s ease}
  .perfil .salir:hover{color:var(--bad);background:var(--bad-tinte)}
  .contenido{flex:1;padding:40px 44px;min-width:0;max-width:1120px}
  .contenido h1{font-size:22px;font-weight:700;letter-spacing:-.015em}
  .contenido .bajada{color:var(--txt-2);font-size:14px;margin:4px 0 28px}
  @media (max-width: 820px){
    .app{flex-direction:column}
    .lateral{width:100%;height:auto;position:static}
    .contenido{padding:24px 16px}
  }
</style>
</head>
<body>
<div class="app">
  <aside class="lateral">
    <a class="marca" href="<?php echo $raiz; ?>index.php">
      <img class="logo-oscuro" src="<?php echo ui_base(); ?>/assets/img/printika-tools-dark.svg" alt="Printika Tools">
      <img class="logo-claro" src="<?php echo ui_base(); ?>/assets/img/printika-tools.svg" alt="Printika Tools">
    </a>
    <?php foreach (ui_menu() as $grupo => $items): ?>
      <div class="grupo"><?php echo htmlspecialchars($grupo); ?></div>
      <?php foreach ($items as [$icono, $nombre, $href, $ok]): ?>
        <?php if ($ok): ?>
          <a class="item<?php echo $nombre === $activo ? ' activo' : ''; ?>"
             href="<?php echo str_starts_with((string) $href, 'http') ? htmlspecialchars($href) : $raiz . htmlspecialchars($href); ?>"<?php
             echo str_starts_with((string) $href, 'http') ? ' target="_blank" rel="noopener"' : ''; ?>>
            <?php echo ui_icono($icono); ?><?php echo htmlspecialchars($nombre); ?>
          </a>
        <?php else: ?>
          <span class="item prox"><?php echo ui_icono($icono); ?><?php echo htmlspecialchars($nombre); ?>
            <span class="badge">Próximamente</span></span>
        <?php endif; ?>
      <?php endforeach; ?>
    <?php endforeach; ?>
    <?php if ($usuario['rol'] === 'admin'): ?>
      <div class="grupo">Administración</div>
      <a class="item<?php echo $activo === 'Suscripciones' ? ' activo' : ''; ?>" href="<?php echo $raiz; ?>admin/">
        <?php echo ui_icono('admin'); ?>Suscripciones
      </a>
    <?php endif; ?>
    <div class="tema" role="group" aria-label="Tema de la interfaz">
      <button type="button" class="tema-btn" data-tema="light" onclick="ptTema('light')"><?php echo ui_icono('sol', 15); ?>Día</button>
      <button type="button" class="tema-btn" data-tema="dark" onclick="ptTema('dark')"><?php echo ui_icono('luna', 15); ?>Noche</button>
    </div>
    <div class="perfil">
      <span class="avatar"><?php echo mb_strtoupper(mb_substr($usuario['nombre'], 0, 1)); ?></span>
      <span class="quien">
        <span class="nombre"><?php echo htmlspecialchars($usuario['nombre']); ?></span>
        <span class="rol"><?php echo $usuario['rol'] === 'admin' ? 'Administrador' : 'Suscriptor'; ?></span>
      </span>
      <a class="salir" href="<?php echo $raiz; ?>logout.php" title="Cerrar sesión" aria-label="Cerrar sesión"><?php echo ui_icono('salir', 16); ?></a>
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
