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
  <title>Printika Tools — Herramientas y comunidad de impresión 3D</title>
  <meta name="description" content="Calculadora de costos, presupuestos, clientes y stock. La comunidad con las herramientas que tu taller de impresión 3D necesita, en español.">
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
    .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;background:var(--accent);
         color:var(--accent-ink);border:1px solid transparent;border-radius:var(--radio);padding:0 18px;
         height:44px;font-weight:600;font-size:14.5px;cursor:pointer;white-space:nowrap;
         transition:background-color .15s ease}
    .btn:hover{background:var(--accent-hover);color:var(--accent-ink)}
    .btn.sec{background:transparent;color:var(--txt);border-color:var(--bd)}
    .btn.sec:hover{background:var(--surface-2)}
    .chip{display:inline-block;font-size:10px;font-weight:600;letter-spacing:.07em;padding:2px 8px;
          border-radius:99px;border:1px solid var(--bd);color:var(--txt-3);text-transform:uppercase}
    .chip.activo{border-color:rgba(62,207,142,.4);color:var(--ok)}
    section{padding:72px 0}
    .titulo-seccion{text-align:center;margin-bottom:44px}
    .titulo-seccion h2{font-size:clamp(23px,3vw,32px);font-weight:800;letter-spacing:-.02em}
    .titulo-seccion p{color:var(--txt-2);font-size:15.5px;margin-top:10px;max-width:560px;
                      margin-left:auto;margin-right:auto}

    /* ---- Navegacion ---- */
    .nav{position:sticky;top:0;z-index:10;background:rgba(14,19,28,.88);backdrop-filter:blur(10px);
         border-bottom:1px solid var(--bd-suave)}
    .nav .cont{display:flex;align-items:center;justify-content:space-between;height:66px;gap:16px}
    .nav img{height:42px;width:auto;display:block}
    .nav nav{display:flex;align-items:center;gap:22px}
    .nav nav a{color:var(--txt-2);font-size:14px;font-weight:500;transition:color .15s ease}
    .nav nav a:hover{color:var(--txt)}
    .nav .btn{height:36px;padding:0 14px;font-size:13.5px}
    .nav nav a.btn{color:var(--accent-ink)}
    .nav nav a.btn:hover{color:var(--accent-ink)}
    .nav nav a.entrar{color:var(--txt)}

    /* ---- Hero ---- */
    .hero{padding:88px 0 64px;text-align:center}
    .hero h1{font-size:clamp(31px,5vw,48px);font-weight:800;letter-spacing:-.025em;line-height:1.12;
             max-width:820px;margin:0 auto 18px}
    .hero h1 em{font-style:normal;color:var(--accent)}
    .hero .sub{font-size:clamp(15px,2vw,18px);color:var(--txt-2);max-width:620px;margin:0 auto 34px}
    .hero .ctas{display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-bottom:56px}
    .stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;max-width:820px;margin:0 auto}
    .stat{background:var(--surface);border:1px solid var(--bd-suave);border-radius:var(--radio-g);padding:18px 12px}
    .stat b{display:block;font-size:22px;font-weight:800;letter-spacing:-.02em;color:var(--accent)}
    .stat span{font-size:12.5px;color:var(--txt-2)}

    /* ---- Herramientas ---- */
    .grilla{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:14px}
    .celda{background:var(--surface);border:1px solid var(--bd-suave);border-radius:var(--radio-g);padding:24px}
    .celda .ico-caja{width:40px;height:40px;border-radius:10px;background:var(--accent-tinte);color:var(--accent);
           display:flex;align-items:center;justify-content:center;margin-bottom:16px}
    .celda h3{font-size:16px;font-weight:600;margin-bottom:5px;display:flex;align-items:center;gap:8px;flex-wrap:wrap}
    .celda p{font-size:14px;color:var(--txt-2)}
    .centro-cta{text-align:center;margin-top:32px}

    /* ---- Comunidad ---- */
    .comunidad{background:var(--surface);border-top:1px solid var(--bd-suave);border-bottom:1px solid var(--bd-suave)}
    .beneficios{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:14px}
    .beneficio{display:flex;gap:14px;align-items:flex-start;background:var(--bg);border:1px solid var(--bd);
               border-radius:var(--radio-g);padding:18px}
    .beneficio .ico{color:var(--accent);margin-top:2px}
    .beneficio h3{font-size:14.5px;font-weight:600}
    .beneficio p{font-size:13px;color:var(--txt-2);margin-top:2px}

    /* ---- Planes ---- */
    .planes-grilla{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,340px));gap:16px;justify-content:center}
    .plan{background:var(--surface);border:1px solid var(--bd);border-radius:var(--radio-g);padding:28px;
          display:flex;flex-direction:column}
    .plan.destacado{border-color:var(--accent);position:relative}
    .plan .etiqueta{position:absolute;top:-11px;left:50%;transform:translateX(-50%);
          background:var(--accent);color:var(--accent-ink);font-size:10.5px;font-weight:700;
          letter-spacing:.07em;text-transform:uppercase;padding:3px 12px;border-radius:99px}
    .plan h3{font-size:19px;font-weight:700}
    .plan .precio{margin:8px 0 4px;font-size:30px;font-weight:800;letter-spacing:-.02em}
    .plan .precio small{font-size:14px;font-weight:500;color:var(--txt-2)}
    .plan .nota{font-size:13px;color:var(--txt-2);margin-bottom:18px}
    .plan ul{list-style:none;display:flex;flex-direction:column;gap:10px;margin-bottom:26px}
    .plan li{display:flex;gap:10px;align-items:flex-start;font-size:14px;color:var(--txt-2)}
    .plan li .ico{color:var(--ok);margin-top:3px}
    .plan li.no{opacity:.45}
    .plan .btn{margin-top:auto;width:100%}

    /* ---- FAQ ---- */
    .faq{max-width:720px;margin:0 auto;display:flex;flex-direction:column;gap:10px}
    .faq details{background:var(--surface);border:1px solid var(--bd-suave);border-radius:var(--radio-g);
          padding:0 20px;transition:border-color .15s ease}
    .faq details[open]{border-color:var(--bd)}
    .faq summary{cursor:pointer;list-style:none;display:flex;align-items:center;justify-content:space-between;
          gap:14px;padding:17px 0;font-size:15px;font-weight:600}
    .faq summary::-webkit-details-marker{display:none}
    .faq summary::after{content:'+';font-size:20px;font-weight:400;color:var(--txt-3);flex-shrink:0;
          transition:transform .15s ease}
    .faq details[open] summary::after{transform:rotate(45deg)}
    .faq .resp{padding:0 0 18px;font-size:14px;color:var(--txt-2)}

    /* ---- Cierre ---- */
    .cierre{text-align:center}
    .cierre .caja{background:linear-gradient(180deg,var(--surface),var(--bg));border:1px solid var(--bd);
          border-radius:var(--radio-g);padding:56px 24px}
    .cierre h2{font-size:clamp(22px,3vw,30px);font-weight:800;letter-spacing:-.02em;margin-bottom:10px}
    .cierre p{color:var(--txt-2);font-size:15.5px;max-width:540px;margin:0 auto 28px}

    /* ---- Footer ---- */
    footer{border-top:1px solid var(--bd-suave);padding:48px 0 32px}
    .footer-grilla{display:grid;grid-template-columns:2fr 1fr 1fr;gap:32px;margin-bottom:36px}
    .footer-grilla img{height:44px;width:auto;margin-bottom:12px}
    .footer-grilla .desc{font-size:13.5px;color:var(--txt-2);max-width:280px}
    .footer-grilla h4{font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.08em;
          color:var(--txt-3);margin-bottom:12px}
    .footer-grilla ul{list-style:none;display:flex;flex-direction:column;gap:8px}
    .footer-grilla ul a{color:var(--txt-2);font-size:14px}
    .footer-grilla ul a:hover{color:var(--txt)}
    .footer-pie{border-top:1px solid var(--bd-suave);padding-top:20px;display:flex;align-items:center;
          justify-content:space-between;gap:12px;flex-wrap:wrap}
    .footer-pie p{font-size:13px;color:var(--txt-3)}

    @media (max-width:720px){
      .nav nav a.link-seccion{display:none}
      .hero{padding:56px 0 44px}
      section{padding:52px 0}
      .footer-grilla{grid-template-columns:1fr;gap:24px}
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
        <a class="link-seccion" href="#comunidad">Comunidad</a>
        <a class="link-seccion" href="#planes">Precios</a>
        <a class="link-seccion" href="#faq">FAQ</a>
        <a class="link-seccion" href="comunidad/cotizador/">Calculadora</a>
        <a class="entrar" href="comunidad/login.php">Iniciar sesión</a>
        <a class="btn" href="comunidad/registro.php">Registrarse</a>
      </nav>
    </div>
  </header>

  <main>
    <div class="hero cont">
      <h1>Manejá tu taller de impresión 3D <em>de principio a fin</em></h1>
      <p class="sub">Calculadora de costos, presupuestos, clientes y stock de materiales.
         Las herramientas de una comunidad de makers, todo en un mismo lugar y en español.</p>
      <div class="ctas">
        <a class="btn" href="comunidad/registro.php">Comenzar gratis</a>
        <a class="btn sec" href="#planes">Ver planes</a>
      </div>
      <div class="stats">
        <div class="stat"><b>+13</b><span>materiales en la calculadora</span></div>
        <div class="stat"><b>3</b><span>monedas: ARS · USD · EUR</span></div>
        <div class="stat"><b>+6</b><span>herramientas en camino</span></div>
        <div class="stat"><b>100%</b><span>en español, hecho por makers</span></div>
      </div>
    </div>

    <section id="herramientas">
      <div class="cont">
        <div class="titulo-seccion">
          <h2>¿Qué necesita tu taller?</h2>
          <p>Herramientas pensadas para makers y emprendedores 3D, construidas junto a la comunidad.</p>
        </div>
        <div class="grilla">
          <div class="celda">
            <span class="ico-caja"><?php echo ui_icono('calculadora', 20); ?></span>
            <h3>Calculadora de costos <span class="chip activo">Disponible</span></h3>
            <p>Material, tiempo de máquina, desgaste, mano de obra y ganancia: el precio justo de cada impresión.</p>
          </div>
          <div class="celda">
            <span class="ico-caja"><?php echo ui_icono('presupuestos', 20); ?></span>
            <h3>Presupuestos <span class="chip">Pronto</span></h3>
            <p>Generá presupuestos profesionales, guardalos y marcá los vendidos.</p>
          </div>
          <div class="celda">
            <span class="ico-caja"><?php echo ui_icono('clientes', 20); ?></span>
            <h3>Clientes <span class="chip">Pronto</span></h3>
            <p>Tu cartera de clientes con su historial de trabajos, siempre a mano.</p>
          </div>
          <div class="celda">
            <span class="ico-caja"><?php echo ui_icono('stock', 20); ?></span>
            <h3>Stock de materiales <span class="chip">Pronto</span></h3>
            <p>Controlá tus rollos de filamento e insumos, con descuento automático al vender.</p>
          </div>
          <div class="celda">
            <span class="ico-caja"><?php echo ui_icono('libreria', 20); ?></span>
            <h3>Librería STL <span class="chip">Pronto</span></h3>
            <p>Modelos seleccionados y listos para imprimir, exclusivos para suscriptores.</p>
          </div>
          <div class="celda">
            <span class="ico-caja"><?php echo ui_icono('estadisticas', 20); ?></span>
            <h3>Estadísticas <span class="chip">Pronto</span></h3>
            <p>Cuánto imprimís, cuánto vendés y cuánto ganás, mes a mes.</p>
          </div>
        </div>
        <div class="centro-cta">
          <a class="btn sec" href="comunidad/cotizador/">Probar la calculadora</a>
        </div>
      </div>
    </section>

    <section class="comunidad" id="comunidad">
      <div class="cont">
        <div class="titulo-seccion">
          <h2>Todo lo que necesitás, en un mismo lugar</h2>
          <p>Ser parte de la comunidad es más que usar herramientas.</p>
        </div>
        <div class="beneficios">
          <div class="beneficio"><?php echo ui_icono('soporte', 20); ?>
            <div><h3>Soporte directo</h3><p>Te ayudamos por WhatsApp cuando lo necesitás.</p></div>
          </div>
          <div class="beneficio"><?php echo ui_icono('clientes', 20); ?>
            <div><h3>Comunidad de makers</h3><p>Emprendedores 3D que comparten precios, consejos y experiencia.</p></div>
          </div>
          <div class="beneficio"><?php echo ui_icono('nube', 20); ?>
            <div><h3>Tus datos en tu cuenta</h3><p>Presupuestos, clientes y stock guardados y accesibles desde cualquier lado.</p></div>
          </div>
          <div class="beneficio"><?php echo ui_icono('rayo', 20); ?>
            <div><h3>Mejoras constantes</h3><p>Herramientas nuevas y actualizaciones todos los meses.</p></div>
          </div>
          <div class="beneficio"><?php echo ui_icono('libreria', 20); ?>
            <div><h3>Contenido exclusivo</h3><p>Archivos y recursos seleccionados, solo para suscriptores.</p></div>
          </div>
          <div class="beneficio"><?php echo ui_icono('admin', 20); ?>
            <div><h3>Sin permanencia</h3><p>Entrás y salís cuando quieras, sin compromiso.</p></div>
          </div>
        </div>
      </div>
    </section>

    <section id="planes">
      <div class="cont">
        <div class="titulo-seccion">
          <h2>Planes simples, sin sorpresas</h2>
          <p>Empezá gratis y pasate a la suscripción cuando tu taller lo pida.</p>
        </div>
        <div class="planes-grilla">
          <div class="plan">
            <h3>Gratuito</h3>
            <p class="precio">$0</p>
            <p class="nota">Para probar y empezar</p>
            <ul>
              <li><?php echo ui_icono('check', 15); ?>Calculadora de costos online</li>
              <li><?php echo ui_icono('check', 15); ?>Cálculo en ARS, USD y EUR</li>
              <li><?php echo ui_icono('check', 15); ?>Cuenta gratuita en la comunidad</li>
              <li class="no"><?php echo ui_icono('check', 15); ?>Herramientas del taller</li>
              <li class="no"><?php echo ui_icono('check', 15); ?>Datos guardados en tu cuenta</li>
            </ul>
            <a class="btn sec" href="comunidad/registro.php">Empezar gratis</a>
          </div>
          <div class="plan destacado">
            <span class="etiqueta">Recomendado</span>
            <h3>Comunidad</h3>
            <p class="precio">Mensual <small>· precio de lanzamiento</small></p>
            <p class="nota">Consultanos por WhatsApp y activamos tu cuenta</p>
            <ul>
              <li><?php echo ui_icono('check', 15); ?>Calculadora completa (versión PRO)</li>
              <li><?php echo ui_icono('check', 15); ?>Mi Taller: presupuestos, clientes y stock</li>
              <li><?php echo ui_icono('check', 15); ?>Librería STL y estadísticas</li>
              <li><?php echo ui_icono('check', 15); ?>Tus datos guardados en tu cuenta</li>
              <li><?php echo ui_icono('check', 15); ?>Soporte técnico prioritario</li>
              <li><?php echo ui_icono('check', 15); ?>Herramientas nuevas cada mes</li>
            </ul>
            <a class="btn" href="<?php echo COMUNIDAD_WHATSAPP; ?>" target="_blank" rel="noopener">Suscribirme</a>
          </div>
        </div>
      </div>
    </section>

    <section id="faq">
      <div class="cont">
        <div class="titulo-seccion">
          <h2>Preguntas frecuentes</h2>
        </div>
        <div class="faq">
          <details>
            <summary>¿Cómo me uno a la comunidad?</summary>
            <p class="resp">Creás tu cuenta gratis con el botón "Registrarse" y después activás tu suscripción
            escribiéndonos por WhatsApp. En minutos tenés acceso a todas las herramientas.</p>
          </details>
          <details>
            <summary>¿El pago es mensual?</summary>
            <p class="resp">Sí, la suscripción es mensual y se renueva mes a mes. No hay permanencia mínima
            ni letra chica.</p>
          </details>
          <details>
            <summary>¿Puedo cancelar cuando quiera?</summary>
            <p class="resp">Sí. Si cancelás, mantenés el acceso hasta el vencimiento de tu suscripción
            y no se te cobra nada más.</p>
          </details>
          <details>
            <summary>¿Qué incluye el plan gratuito?</summary>
            <p class="resp">La calculadora de costos online completa, sin necesidad de registrarte,
            y una cuenta gratuita para conocer la plataforma por dentro.</p>
          </details>
          <details>
            <summary>¿Mis datos quedan guardados?</summary>
            <p class="resp">Sí. Cada suscriptor tiene su propia cuenta: tus presupuestos, clientes y stock
            se guardan y podés consultarlos desde cualquier dispositivo.</p>
          </details>
          <details>
            <summary>¿Van a agregar más herramientas?</summary>
            <p class="resp">Todos los meses sumamos mejoras y herramientas nuevas: presupuestos, clientes,
            stock, librería STL y estadísticas son las próximas en llegar.</p>
          </details>
        </div>
      </div>
    </section>

    <section class="cierre">
      <div class="cont">
        <div class="caja">
          <h2>Empezá hoy — es gratis</h2>
          <p>Creá tu cuenta, probá la calculadora y descubrí por qué cada vez más makers
             manejan su taller con Printika Tools.</p>
          <a class="btn" href="comunidad/registro.php">Crear mi cuenta</a>
        </div>
      </div>
    </section>
  </main>

  <footer>
    <div class="cont">
      <div class="footer-grilla">
        <div>
          <img src="assets/img/printika-tools-dark.svg" alt="Printika Tools">
          <p class="desc">Las herramientas y la comunidad para manejar tu taller de impresión 3D como un negocio.</p>
        </div>
        <div>
          <h4>Plataforma</h4>
          <ul>
            <li><a href="comunidad/cotizador/">Calculadora</a></li>
            <li><a href="#planes">Precios</a></li>
            <li><a href="comunidad/login.php">Iniciar sesión</a></li>
            <li><a href="comunidad/registro.php">Registrarse</a></li>
          </ul>
        </div>
        <div>
          <h4>Comunidad</h4>
          <ul>
            <li><a href="<?php echo COMUNIDAD_WHATSAPP; ?>" target="_blank" rel="noopener">WhatsApp</a></li>
            <li><a href="https://printika3d.com" target="_blank" rel="noopener">Printika 3D</a></li>
          </ul>
        </div>
      </div>
      <div class="footer-pie">
        <p>© <?php echo date('Y'); ?> Printika Tools. Todos los derechos reservados.</p>
        <p>Comunidad 3D</p>
      </div>
    </div>
  </footer>
</body>
</html>
