<?php
/**
 * Portada de printikatools.com.
 *
 * Mientras el sitio esta en acceso anticipado (COM_PREVIEW_ACTIVO en
 * comunidad/inc/bootstrap.php) muestra el "Proximamente"; con la clave de
 * acceso se ve la landing real y se habilita el ingreso a la comunidad.
 */
require_once __DIR__ . '/comunidad/inc/bootstrap.php';
require_once __DIR__ . '/comunidad/inc/ui.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    com_preview_activar($_POST['clave'] ?? '');
    header('Location: /');
    exit;
}

if (!com_preview_ok()): ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Printika Tools — Próximamente</title>
  <meta name="description" content="Comunidad de impresión 3D. Muy pronto.">
  <style>
    *{margin:0;padding:0;box-sizing:border-box}
    body{font-family:'Segoe UI',system-ui,-apple-system,sans-serif;background:#192844;
         color:#f2f6fc;min-height:100vh;display:flex;align-items:center;justify-content:center;
         text-align:center;padding:24px}
    .contenido{display:flex;flex-direction:column;align-items:center;gap:32px}
    .logo{width:min(480px,80vw);height:auto}
    .pronto{font-size:clamp(1.2rem,3vw,1.8rem);letter-spacing:.35em;text-transform:uppercase;
            font-weight:300;opacity:.9}
    .acceso{position:fixed;bottom:16px;right:16px;background:none;border:none;cursor:pointer;
            color:#f2f6fc;opacity:.25;padding:10px;transition:opacity .2s ease}
    .acceso:hover{opacity:.7}
  </style>
</head>
<body>
  <main class="contenido">
    <img src="assets/img/printika-tools-dark.svg" alt="Printika Tools" class="logo">
    <p class="pronto">Próximamente</p>
  </main>
  <form method="post" id="f-acceso"><input type="hidden" name="clave" id="clave"></form>
  <button class="acceso" title="Acceso anticipado" aria-label="Acceso anticipado" onclick="pedirClave()">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"
         stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
      <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
    </svg>
  </button>
  <script>
    function pedirClave(){
      var c = prompt('Ingresá la clave de acceso:');
      if (c === null || c === '') return; // cancelo: se queda en proximamente
      document.getElementById('clave').value = c;
      document.getElementById('f-acceso').submit();
    }
  </script>
</body>
</html>
<?php exit; endif; ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Printika Tools — Herramientas para tu taller de impresión 3D</title>
  <meta name="description" content="Calculadora de costos, presupuestos, clientes y stock: la comunidad con las herramientas que tu emprendimiento 3D necesita.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root{
      --bg:#0e131c; --surface:#141b28; --surface-2:#1a2334; --bd:#243048; --bd-suave:#1c2638;
      --txt:#e8edf5; --txt-2:#9aa6bc; --txt-3:#5f6b82;
      --accent:#2db7fa; --accent-hover:#54c5fb; --accent-tinte:rgba(45,183,250,.10); --accent-ink:#06202f;
      --ok:#3ecf8e; --radio:8px; --radio-g:14px;
    }
    *{box-sizing:border-box;margin:0;padding:0}
    html{scroll-behavior:smooth}
    body{font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:var(--bg);
         color:var(--txt);line-height:1.6;font-size:16px;-webkit-font-smoothing:antialiased}
    a{color:var(--accent);text-decoration:none}
    .ico{flex-shrink:0}
    .cont{max-width:1080px;margin:0 auto;padding:0 24px}

    .nav{position:sticky;top:0;z-index:10;background:rgba(14,19,28,.85);backdrop-filter:blur(10px);
         border-bottom:1px solid var(--bd-suave)}
    .nav .cont{display:flex;align-items:center;justify-content:space-between;height:64px}
    .nav img{height:40px;width:auto;display:block}
    .nav nav{display:flex;align-items:center;gap:26px}
    .nav nav a{color:var(--txt-2);font-size:14px;font-weight:500;transition:color .15s ease}
    .nav nav a:hover{color:var(--txt)}
    .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;background:var(--accent);
         color:var(--accent-ink);border:1px solid transparent;border-radius:var(--radio);padding:0 18px;
         height:42px;font-weight:600;font-size:14.5px;cursor:pointer;white-space:nowrap;
         transition:background-color .15s ease}
    .btn:hover{background:var(--accent-hover);color:var(--accent-ink)}
    .btn.sec{background:transparent;color:var(--txt);border-color:var(--bd)}
    .btn.sec:hover{background:var(--surface-2)}
    .nav .btn{height:36px;padding:0 14px;font-size:13.5px}
    .nav nav a.btn{color:var(--accent-ink)}
    .nav nav a.btn:hover{color:var(--accent-ink)}

    .hero{padding:96px 0 72px;text-align:center}
    .hero h1{font-size:clamp(30px,5vw,46px);font-weight:800;letter-spacing:-.025em;line-height:1.15;
             max-width:760px;margin:0 auto 18px}
    .hero h1 em{font-style:normal;color:var(--accent)}
    .hero p{font-size:clamp(15px,2vw,18px);color:var(--txt-2);max-width:600px;margin:0 auto 32px}
    .hero .ctas{display:flex;gap:12px;justify-content:center;flex-wrap:wrap}

    section{padding:64px 0}
    .titulo-seccion{text-align:center;margin-bottom:40px}
    .titulo-seccion h2{font-size:clamp(22px,3vw,30px);font-weight:700;letter-spacing:-.02em}
    .titulo-seccion p{color:var(--txt-2);font-size:15px;margin-top:8px}

    .grilla{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:14px}
    .celda{background:var(--surface);border:1px solid var(--bd-suave);border-radius:var(--radio-g);padding:24px}
    .celda .ico-caja{width:40px;height:40px;border-radius:10px;background:var(--accent-tinte);color:var(--accent);
           display:flex;align-items:center;justify-content:center;margin-bottom:16px}
    .celda h3{font-size:16px;font-weight:600;margin-bottom:5px;display:flex;align-items:center;gap:8px}
    .celda p{font-size:14px;color:var(--txt-2)}
    .chip{display:inline-block;font-size:10px;font-weight:600;letter-spacing:.07em;padding:2px 8px;
          border-radius:99px;border:1px solid var(--bd);color:var(--txt-3);text-transform:uppercase}
    .chip.activo{border-color:rgba(62,207,142,.4);color:var(--ok)}

    .planes{background:var(--surface);border-top:1px solid var(--bd-suave);border-bottom:1px solid var(--bd-suave)}
    .planes-grilla{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,340px));gap:16px;justify-content:center}
    .plan{background:var(--bg);border:1px solid var(--bd);border-radius:var(--radio-g);padding:28px;
          display:flex;flex-direction:column;gap:0}
    .plan.destacado{border-color:var(--accent)}
    .plan .etiqueta{font-size:11px;font-weight:600;letter-spacing:.07em;text-transform:uppercase;
          color:var(--accent);margin-bottom:8px}
    .plan h3{font-size:19px;font-weight:700}
    .plan .precio{font-size:15px;color:var(--txt-2);margin:6px 0 18px}
    .plan ul{list-style:none;display:flex;flex-direction:column;gap:10px;margin-bottom:24px}
    .plan li{display:flex;gap:10px;align-items:flex-start;font-size:14px;color:var(--txt-2)}
    .plan li .ico{color:var(--ok);margin-top:3px}
    .plan li.no .ico{color:var(--txt-3)}
    .plan .btn{margin-top:auto;width:100%}

    .cierre{text-align:center}
    .cierre .caja{background:var(--surface);border:1px solid var(--bd-suave);border-radius:var(--radio-g);
          padding:48px 24px}
    .cierre h2{font-size:clamp(20px,3vw,26px);font-weight:700;letter-spacing:-.02em;margin-bottom:10px}
    .cierre p{color:var(--txt-2);font-size:15px;max-width:520px;margin:0 auto 26px}

    footer{border-top:1px solid var(--bd-suave);padding:28px 0}
    footer .cont{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap}
    footer img{height:34px;width:auto}
    footer p{font-size:13px;color:var(--txt-3)}
    footer a{font-size:13px;color:var(--txt-2)}

    @media (max-width:640px){
      .nav nav a.link-seccion{display:none}
      .hero{padding:64px 0 48px}
      section{padding:48px 0}
    }
    @media (prefers-reduced-motion: reduce){ html{scroll-behavior:auto} }
  </style>
</head>
<body>
  <header class="nav">
    <div class="cont">
      <a href="/"><img src="assets/img/printika-tools-dark.svg" alt="Printika Tools"></a>
      <nav>
        <a class="link-seccion" href="#herramientas">Herramientas</a>
        <a class="link-seccion" href="#planes">Planes</a>
        <a class="btn" href="comunidad/">Ingresar</a>
      </nav>
    </div>
  </header>

  <main>
    <div class="hero cont">
      <h1>Las herramientas para tu <em>taller de impresión 3D</em></h1>
      <p>Calculadora de costos, presupuestos, clientes y stock de materiales.
         Todo en un solo lugar, pensado para makers y emprendedores 3D.</p>
      <div class="ctas">
        <a class="btn" href="comunidad/registro.php">Sumate a la comunidad</a>
        <a class="btn sec" href="comunidad/cotizador/">Probar la calculadora</a>
      </div>
    </div>

    <section id="herramientas">
      <div class="cont">
        <div class="titulo-seccion">
          <h2>Todo tu taller, organizado</h2>
          <p>Las herramientas que vamos construyendo junto a la comunidad.</p>
        </div>
        <div class="grilla">
          <div class="celda">
            <span class="ico-caja"><?php echo ui_icono('calculadora', 20); ?></span>
            <h3>Calculadora de costos <span class="chip activo">Disponible</span></h3>
            <p>Calculá el precio justo de cada impresión: material, tiempo de máquina, desgaste, mano de obra y ganancia.</p>
          </div>
          <div class="celda">
            <span class="ico-caja"><?php echo ui_icono('presupuestos', 20); ?></span>
            <h3>Presupuestos <span class="chip">Pronto</span></h3>
            <p>Generá presupuestos profesionales, guardalos y marcá los vendidos.</p>
          </div>
          <div class="celda">
            <span class="ico-caja"><?php echo ui_icono('clientes', 20); ?></span>
            <h3>Clientes <span class="chip">Pronto</span></h3>
            <p>Tu cartera de clientes con historial de trabajos, siempre a mano.</p>
          </div>
          <div class="celda">
            <span class="ico-caja"><?php echo ui_icono('stock', 20); ?></span>
            <h3>Stock de materiales <span class="chip">Pronto</span></h3>
            <p>Llevá el control de tus rollos de filamento e insumos, con descuento automático al vender.</p>
          </div>
          <div class="celda">
            <span class="ico-caja"><?php echo ui_icono('libreria', 20); ?></span>
            <h3>Librería STL <span class="chip">Pronto</span></h3>
            <p>Modelos seleccionados y listos para imprimir, exclusivos de la comunidad.</p>
          </div>
          <div class="celda">
            <span class="ico-caja"><?php echo ui_icono('estadisticas', 20); ?></span>
            <h3>Estadísticas <span class="chip">Pronto</span></h3>
            <p>Mirá cuánto imprimís, cuánto vendés y cuánto ganás, mes a mes.</p>
          </div>
        </div>
      </div>
    </section>

    <section class="planes" id="planes">
      <div class="cont">
        <div class="titulo-seccion">
          <h2>Planes</h2>
          <p>Empezá gratis y sumate a la comunidad cuando quieras más.</p>
        </div>
        <div class="planes-grilla">
          <div class="plan">
            <h3>Gratis</h3>
            <p class="precio">Sin registro</p>
            <ul>
              <li><?php echo ui_icono('check', 15); ?>Calculadora de costos online</li>
              <li><?php echo ui_icono('check', 15); ?>Cálculo en ARS, USD y EUR</li>
              <li class="no"><?php echo ui_icono('check', 15); ?>Herramientas del taller</li>
              <li class="no"><?php echo ui_icono('check', 15); ?>Datos guardados en tu cuenta</li>
            </ul>
            <a class="btn sec" href="comunidad/cotizador/">Usar la calculadora</a>
          </div>
          <div class="plan destacado">
            <span class="etiqueta">Recomendado</span>
            <h3>Comunidad</h3>
            <p class="precio">Suscripción mensual · consultanos</p>
            <ul>
              <li><?php echo ui_icono('check', 15); ?>Calculadora completa (versión PRO)</li>
              <li><?php echo ui_icono('check', 15); ?>Presupuestos, clientes y stock</li>
              <li><?php echo ui_icono('check', 15); ?>Librería STL y estadísticas</li>
              <li><?php echo ui_icono('check', 15); ?>Tus datos guardados en tu cuenta</li>
              <li><?php echo ui_icono('check', 15); ?>Soporte directo por WhatsApp</li>
            </ul>
            <a class="btn" href="<?php echo COMUNIDAD_WHATSAPP; ?>" target="_blank" rel="noopener">Activar por WhatsApp</a>
          </div>
        </div>
      </div>
    </section>

    <section class="cierre">
      <div class="cont">
        <div class="caja">
          <h2>Dejá de cobrar a ojo</h2>
          <p>Sumate a la comunidad de impresión 3D y manejá tu taller como un negocio.</p>
          <a class="btn" href="comunidad/registro.php">Crear mi cuenta</a>
        </div>
      </div>
    </section>
  </main>

  <footer>
    <div class="cont">
      <img src="assets/img/printika-tools-dark.svg" alt="Printika Tools">
      <p>© <?php echo date('Y'); ?> Printika Tools · Comunidad 3D</p>
      <a href="<?php echo COMUNIDAD_WHATSAPP; ?>" target="_blank" rel="noopener">WhatsApp</a>
    </div>
  </footer>
</body>
</html>
