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
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root{
      --fondo:#07090f; --bg:#0b0e16; --surface:#10141f; --surface-2:#161b29; --raised:#1d2434;
      --bd:#222a3d; --bd-suave:#181f2e;
      --txt:#eef2fa; --txt-2:#98a3b8; --txt-3:#5e6a82;
      --accent:#2db7fa; --accent-2:#7fd4ff; --accent-hover:#54c5fb;
      --accent-tinte:rgba(45,183,250,.10); --accent-ink:#06202f;
      --violeta:#6f7cf5; --ok:#3ecf8e;
      --radio:10px; --radio-g:16px;
      --titulos:'Space Grotesk','Inter',sans-serif;
    }
    *{box-sizing:border-box;margin:0;padding:0}
    html{scroll-behavior:smooth}
    body{font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
         background:var(--fondo);color:var(--txt);line-height:1.6;font-size:16px;
         -webkit-font-smoothing:antialiased;overflow-x:hidden}
    a{color:var(--accent);text-decoration:none}
    .ico{flex-shrink:0}
    .cont{max-width:1120px;margin:0 auto;padding:0 24px}
    h1,h2,h3{font-family:var(--titulos)}
    .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;background:var(--accent);
         color:var(--accent-ink);border:1px solid transparent;border-radius:var(--radio);padding:0 20px;
         height:46px;font-weight:600;font-size:14.5px;font-family:'Inter',sans-serif;cursor:pointer;
         white-space:nowrap;transition:background-color .15s ease,transform .15s ease,box-shadow .15s ease}
    .btn:hover{background:var(--accent-hover);color:var(--accent-ink);
         box-shadow:0 0 32px -8px rgba(45,183,250,.55)}
    .btn:active{transform:scale(.97)}
    .btn.sec{background:rgba(255,255,255,.03);color:var(--txt);border-color:var(--bd)}
    .btn.sec:hover{background:var(--surface-2);box-shadow:none}
    .chip{display:inline-flex;align-items:center;font-size:10px;font-weight:600;letter-spacing:.08em;
          padding:3px 9px;border-radius:99px;border:1px solid var(--bd);color:var(--txt-3);
          text-transform:uppercase;line-height:1.5}
    .chip.activo{border-color:rgba(62,207,142,.4);color:var(--ok);background:rgba(62,207,142,.07)}
    section{padding:88px 0;position:relative}
    .cabeza{margin-bottom:48px;max-width:560px}
    .cabeza.centrada{text-align:center;margin-left:auto;margin-right:auto}
    .ceja{display:inline-flex;align-items:center;gap:8px;font-size:11.5px;font-weight:600;
          letter-spacing:.14em;text-transform:uppercase;color:var(--accent);margin-bottom:14px}
    .ceja::before{content:'';width:22px;height:1px;background:var(--accent)}
    .cabeza.centrada .ceja::after{content:'';width:22px;height:1px;background:var(--accent)}
    .cabeza h2{font-size:clamp(26px,3.4vw,38px);font-weight:700;letter-spacing:-.02em;line-height:1.15}
    .cabeza p{color:var(--txt-2);font-size:15.5px;margin-top:12px}

    /* ---- Navegacion (vidrio) ---- */
    .nav{position:sticky;top:0;z-index:10;background:rgba(7,9,15,.72);backdrop-filter:blur(14px);
         -webkit-backdrop-filter:blur(14px);border-bottom:1px solid rgba(255,255,255,.05)}
    .nav .cont{display:flex;align-items:center;justify-content:space-between;height:68px;gap:16px}
    .nav img{height:42px;width:auto;display:block}
    .nav nav{display:flex;align-items:center;gap:24px}
    .nav nav a{color:var(--txt-2);font-size:14px;font-weight:500;white-space:nowrap;transition:color .15s ease}
    .nav nav a:hover{color:var(--txt)}
    .nav .btn{height:38px;padding:0 16px;font-size:13.5px}
    .nav nav a.btn{color:var(--accent-ink)}
    .nav nav a.btn:hover{color:var(--accent-ink)}
    .nav nav a.entrar{color:var(--txt)}

    /* ---- Hero ---- */
    .hero{position:relative;padding:96px 0 72px;overflow:hidden}
    .hero::before{content:'';position:absolute;inset:0;pointer-events:none;
        background:
          radial-gradient(52% 42% at 18% 8%, rgba(45,183,250,.13), transparent 60%),
          radial-gradient(40% 36% at 88% 30%, rgba(111,124,245,.10), transparent 65%);}
    .hero::after{content:'';position:absolute;inset:0;pointer-events:none;opacity:.5;
        background-image:linear-gradient(rgba(255,255,255,.025) 1px,transparent 1px),
                         linear-gradient(90deg,rgba(255,255,255,.025) 1px,transparent 1px);
        background-size:56px 56px;
        -webkit-mask-image:radial-gradient(60% 55% at 50% 30%,#000 30%,transparent 100%);
                mask-image:radial-gradient(60% 55% at 50% 30%,#000 30%,transparent 100%)}
    .hero .cont{position:relative;display:grid;grid-template-columns:1.05fr .95fr;gap:56px;align-items:center}
    .insignia{display:inline-flex;align-items:center;gap:8px;background:rgba(45,183,250,.08);
        border:1px solid rgba(45,183,250,.25);color:var(--accent-2);font-size:12.5px;font-weight:500;
        border-radius:99px;padding:6px 14px;margin-bottom:22px}
    .insignia .punto{width:7px;height:7px;border-radius:99px;background:var(--ok);
        box-shadow:0 0 8px rgba(62,207,142,.8)}
    .hero h1{font-size:clamp(32px,4.6vw,54px);font-weight:700;letter-spacing:-.025em;line-height:1.08;
        margin-bottom:20px}
    .hero h1 em{font-style:normal;background:linear-gradient(92deg,var(--accent),var(--accent-2));
        -webkit-background-clip:text;background-clip:text;color:transparent}
    .hero .sub{font-size:clamp(15px,1.6vw,17.5px);color:var(--txt-2);max-width:480px;margin-bottom:32px}
    .hero .ctas{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:48px}
    .stats{display:flex;flex-wrap:wrap;border-top:1px solid var(--bd-suave);padding-top:24px;gap:0}
    .stat{padding:0 28px;border-left:1px solid var(--bd-suave)}
    .stat:first-child{padding-left:0;border-left:none}
    .stat b{display:block;font-family:var(--titulos);font-size:24px;font-weight:700;
        letter-spacing:-.02em;color:var(--txt)}
    .stat span{font-size:12px;color:var(--txt-3)}

    /* ---- Mockup del producto (CSS puro) ---- */
    .mock{position:relative}
    .mock::before{content:'';position:absolute;inset:-8%;pointer-events:none;
        background:radial-gradient(50% 50% at 50% 50%, rgba(45,183,250,.16), transparent 70%)}
    .ventana{position:relative;background:var(--surface);border:1px solid var(--raised);
        border-radius:14px;overflow:hidden;box-shadow:0 24px 80px -24px rgba(0,0,0,.8);
        transform:rotate(-1deg)}
    .ventana .barra{display:flex;align-items:center;gap:6px;padding:11px 14px;
        border-bottom:1px solid var(--bd-suave);background:var(--surface-2)}
    .ventana .barra i{width:10px;height:10px;border-radius:99px;background:var(--raised);display:block}
    .ventana .barra i:first-child{background:#e0655f}
    .ventana .barra i:nth-child(2){background:#e0a34f}
    .ventana .barra i:nth-child(3){background:#59b96b}
    .ventana .cuerpo{display:grid;grid-template-columns:132px 1fr;min-height:320px}
    .m-lado{border-right:1px solid var(--bd-suave);padding:14px 10px;display:flex;
        flex-direction:column;gap:4px;background:var(--surface)}
    .m-item{display:flex;align-items:center;gap:7px;font-size:10.5px;color:var(--txt-3);
        padding:6px 8px;border-radius:6px}
    .m-item .ico{width:12px;height:12px}
    .m-item.on{background:var(--accent-tinte);color:var(--accent)}
    .m-main{padding:18px;background:var(--bg)}
    .m-titulo{font-family:var(--titulos);font-size:13px;font-weight:700;margin-bottom:12px}
    .m-campos{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:14px}
    .m-campo{background:var(--surface-2);border:1px solid var(--bd);border-radius:7px;padding:7px 9px}
    .m-campo small{display:block;font-size:8px;letter-spacing:.06em;text-transform:uppercase;
        color:var(--txt-3);margin-bottom:2px}
    .m-campo span{font-size:11px;font-weight:600;color:var(--txt-2)}
    .m-total{background:linear-gradient(135deg,rgba(45,183,250,.14),rgba(111,124,245,.10));
        border:1px solid rgba(45,183,250,.35);border-radius:9px;padding:12px 14px;
        display:flex;align-items:center;justify-content:space-between}
    .m-total small{font-size:9px;letter-spacing:.08em;text-transform:uppercase;color:var(--accent-2)}
    .m-total b{font-family:var(--titulos);font-size:19px;letter-spacing:-.01em;color:var(--txt)}
    .m-barras{display:flex;gap:6px;margin-top:14px;align-items:flex-end;height:44px}
    .m-barras i{flex:1;background:var(--raised);border-radius:3px 3px 0 0;display:block}
    .m-barras i.az{background:linear-gradient(180deg,var(--accent),rgba(45,183,250,.35))}

    /* ---- Bento herramientas ---- */
    .bento{display:grid;grid-template-columns:repeat(3,1fr);gap:14px}
    .caja{background:var(--surface);border:1px solid var(--bd-suave);border-radius:var(--radio-g);
        padding:26px;position:relative;overflow:hidden;transition:border-color .15s ease}
    .caja:hover{border-color:var(--bd)}
    .caja.grande{grid-column:span 2;grid-row:span 2;display:flex;flex-direction:column}
    .caja.grande::after{content:'';position:absolute;right:-30%;top:-40%;width:80%;height:90%;
        background:radial-gradient(50% 50% at 50% 50%, rgba(45,183,250,.10), transparent 70%);
        pointer-events:none}
    .caja .ico-caja{width:40px;height:40px;border-radius:10px;background:var(--accent-tinte);
        color:var(--accent);display:flex;align-items:center;justify-content:center;margin-bottom:18px}
    .caja h3{font-size:17px;font-weight:700;letter-spacing:-.01em;margin-bottom:6px;
        display:flex;align-items:center;gap:10px;flex-wrap:wrap}
    .caja p{font-size:14px;color:var(--txt-2);max-width:420px}
    .caja .mini{margin-top:auto;padding-top:24px}
    .mini-filas{display:flex;flex-direction:column;gap:8px}
    .mini-fila{display:flex;align-items:center;justify-content:space-between;background:var(--surface-2);
        border:1px solid var(--bd-suave);border-radius:8px;padding:9px 12px;font-size:12px;color:var(--txt-2)}
    .mini-fila b{color:var(--txt);font-weight:600;font-variant-numeric:tabular-nums}
    .mini-fila.total{border-color:rgba(45,183,250,.35);background:rgba(45,183,250,.07)}
    .mini-fila.total b{color:var(--accent-2)}

    /* ---- Comunidad ---- */
    .comunidad{background:linear-gradient(180deg,var(--bg),var(--fondo));
        border-top:1px solid var(--bd-suave);border-bottom:1px solid var(--bd-suave)}
    .beneficios{display:grid;grid-template-columns:repeat(3,1fr);gap:14px}
    .beneficio{display:flex;gap:14px;align-items:flex-start;background:rgba(255,255,255,.02);
        border:1px solid var(--bd-suave);border-radius:var(--radio-g);padding:20px;
        transition:border-color .15s ease,background-color .15s ease}
    .beneficio:hover{border-color:var(--bd);background:var(--surface)}
    .beneficio .ico{color:var(--accent);margin-top:2px}
    .beneficio h3{font-size:15px;font-weight:700;letter-spacing:-.01em}
    .beneficio p{font-size:13px;color:var(--txt-2);margin-top:3px}

    /* ---- Planes ---- */
    .planes-grilla{display:grid;grid-template-columns:repeat(auto-fit,minmax(290px,360px));
        gap:18px;justify-content:center;align-items:stretch}
    .plan{background:var(--surface);border:1px solid var(--bd);border-radius:var(--radio-g);
        padding:30px;display:flex;flex-direction:column}
    .plan.destacado{position:relative;border:1px solid transparent;
        background:linear-gradient(var(--surface),var(--surface)) padding-box,
                   linear-gradient(135deg,var(--accent),var(--violeta)) border-box;
        box-shadow:0 0 60px -18px rgba(45,183,250,.35)}
    .plan .etiqueta{position:absolute;top:-12px;left:50%;transform:translateX(-50%);
        background:linear-gradient(92deg,var(--accent),var(--accent-2));color:var(--accent-ink);
        font-size:10.5px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;
        padding:4px 14px;border-radius:99px;white-space:nowrap}
    .plan h3{font-size:20px;font-weight:700;letter-spacing:-.01em}
    .plan .precio{margin:10px 0 4px;font-family:var(--titulos);font-size:32px;font-weight:700;
        letter-spacing:-.02em}
    .plan .precio small{font-family:'Inter',sans-serif;font-size:13.5px;font-weight:500;color:var(--txt-2)}
    .plan .nota{font-size:13px;color:var(--txt-2);margin-bottom:20px}
    .plan ul{list-style:none;display:flex;flex-direction:column;gap:11px;margin-bottom:28px}
    .plan li{display:flex;gap:10px;align-items:flex-start;font-size:14px;color:var(--txt-2)}
    .plan li .ico{color:var(--ok);margin-top:3px}
    .plan li.no{opacity:.4}
    .plan .btn{margin-top:auto;width:100%}

    /* ---- FAQ ---- */
    .faq{max-width:720px;margin:0 auto;display:flex;flex-direction:column;gap:10px}
    .faq details{background:var(--surface);border:1px solid var(--bd-suave);border-radius:var(--radio-g);
        padding:0 22px;transition:border-color .15s ease}
    .faq details[open]{border-color:var(--bd)}
    .faq summary{cursor:pointer;list-style:none;display:flex;align-items:center;
        justify-content:space-between;gap:14px;padding:18px 0;font-size:15px;font-weight:600}
    .faq summary::-webkit-details-marker{display:none}
    .faq summary::after{content:'+';font-family:var(--titulos);font-size:20px;font-weight:500;
        color:var(--txt-3);flex-shrink:0;transition:transform .15s ease}
    .faq details[open] summary::after{transform:rotate(45deg)}
    .faq .resp{padding:0 0 20px;font-size:14px;color:var(--txt-2)}

    /* ---- Cierre ---- */
    .cierre{text-align:center}
    .cierre .caja-cta{position:relative;overflow:hidden;background:var(--surface);
        border:1px solid var(--bd);border-radius:20px;padding:64px 24px}
    .cierre .caja-cta::before{content:'';position:absolute;inset:0;pointer-events:none;
        background:radial-gradient(55% 80% at 50% 0%, rgba(45,183,250,.14), transparent 70%)}
    .cierre h2{position:relative;font-size:clamp(24px,3.2vw,34px);font-weight:700;
        letter-spacing:-.02em;margin-bottom:12px}
    .cierre p{position:relative;color:var(--txt-2);font-size:15.5px;max-width:520px;margin:0 auto 30px}
    .cierre .btn{position:relative}

    /* ---- Footer ---- */
    footer{border-top:1px solid var(--bd-suave);padding:52px 0 32px;background:var(--fondo)}
    .footer-grilla{display:grid;grid-template-columns:2fr 1fr 1fr;gap:32px;margin-bottom:40px}
    .footer-grilla img{height:46px;width:auto;margin-bottom:14px}
    .footer-grilla .desc{font-size:13.5px;color:var(--txt-2);max-width:290px}
    .footer-grilla h4{font-family:var(--titulos);font-size:12px;font-weight:700;text-transform:uppercase;
        letter-spacing:.1em;color:var(--txt-3);margin-bottom:14px}
    .footer-grilla ul{list-style:none;display:flex;flex-direction:column;gap:9px}
    .footer-grilla ul a{color:var(--txt-2);font-size:14px}
    .footer-grilla ul a:hover{color:var(--txt)}
    .footer-pie{border-top:1px solid var(--bd-suave);padding-top:22px;display:flex;align-items:center;
        justify-content:space-between;gap:12px;flex-wrap:wrap}
    .footer-pie p{font-size:13px;color:var(--txt-3)}

    :is(a,button,input,summary):focus-visible{outline:2px solid var(--accent);outline-offset:2px}

    @media (max-width:960px){
      .hero .cont{grid-template-columns:1fr;gap:44px}
      .mock{max-width:560px}
      .bento{grid-template-columns:1fr 1fr}
      .caja.grande{grid-column:span 2;grid-row:auto}
      .beneficios{grid-template-columns:1fr 1fr}
    }
    @media (max-width:680px){
      .nav nav a.link-seccion{display:none}
      .hero{padding:60px 0 48px}
      section{padding:56px 0}
      .bento{grid-template-columns:1fr}
      .caja.grande{grid-column:auto}
      .beneficios{grid-template-columns:1fr}
      .stats{gap:16px}
      .stat{padding:0;border-left:none;min-width:44%}
      .footer-grilla{grid-template-columns:1fr;gap:26px}
      .ventana{transform:none}
    }
    @media (prefers-reduced-motion: reduce){
      html{scroll-behavior:auto}
      *,*::before,*::after{transition-duration:.01ms !important}
    }
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
    <div class="hero">
      <div class="cont">
        <div>
          <span class="insignia"><span class="punto"></span>Comunidad 3D en español</span>
          <h1>Manejá tu taller de impresión 3D <em>como un negocio</em></h1>
          <p class="sub">Calculadora de costos, presupuestos, clientes y stock de materiales.
             Las herramientas de una comunidad de makers, en un mismo lugar.</p>
          <div class="ctas">
            <a class="btn" href="comunidad/registro.php">Comenzar gratis</a>
            <a class="btn sec" href="#planes">Ver planes</a>
          </div>
          <div class="stats">
            <div class="stat"><b>+13</b><span>materiales soportados</span></div>
            <div class="stat"><b>3</b><span>monedas · ARS USD EUR</span></div>
            <div class="stat"><b>+6</b><span>herramientas en camino</span></div>
          </div>
        </div>
        <div class="mock" aria-hidden="true">
          <div class="ventana">
            <div class="barra"><i></i><i></i><i></i></div>
            <div class="cuerpo">
              <div class="m-lado">
                <div class="m-item on"><?php echo ui_icono('calculadora', 12); ?>Calculadora</div>
                <div class="m-item"><?php echo ui_icono('presupuestos', 12); ?>Presupuestos</div>
                <div class="m-item"><?php echo ui_icono('clientes', 12); ?>Clientes</div>
                <div class="m-item"><?php echo ui_icono('stock', 12); ?>Stock</div>
                <div class="m-item"><?php echo ui_icono('estadisticas', 12); ?>Estadísticas</div>
              </div>
              <div class="m-main">
                <div class="m-titulo">Cotización · Soporte celular</div>
                <div class="m-campos">
                  <div class="m-campo"><small>Material</small><span>PLA · 86 g</span></div>
                  <div class="m-campo"><small>Impresión</small><span>5 h 20 m</span></div>
                  <div class="m-campo"><small>Desgaste</small><span>$ 410</span></div>
                  <div class="m-campo"><small>Ganancia</small><span>35 %</span></div>
                </div>
                <div class="m-total"><small>Precio sugerido</small><b>$ 12.450</b></div>
                <div class="m-barras">
                  <i style="height:34%"></i><i class="az" style="height:58%"></i><i style="height:42%"></i>
                  <i class="az" style="height:74%"></i><i style="height:52%"></i><i class="az" style="height:100%"></i>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <section id="herramientas">
      <div class="cont">
        <div class="cabeza">
          <span class="ceja">Herramientas</span>
          <h2>¿Qué necesita tu taller?</h2>
          <p>Construidas junto a la comunidad, pensadas para makers y emprendedores 3D.</p>
        </div>
        <div class="bento">
          <div class="caja grande">
            <span class="ico-caja"><?php echo ui_icono('calculadora', 20); ?></span>
            <h3>Calculadora de costos <span class="chip activo">Disponible</span></h3>
            <p>Material, tiempo de máquina, desgaste, electricidad, mano de obra y ganancia.
               El precio justo de cada impresión, en tres monedas.</p>
            <div class="mini">
              <div class="mini-filas">
                <div class="mini-fila"><span>Material (PLA, 86 g)</span><b>$ 1.290</b></div>
                <div class="mini-fila"><span>Máquina (5 h 20 m)</span><b>$ 2.140</b></div>
                <div class="mini-fila"><span>Mano de obra + ganancia</span><b>$ 9.020</b></div>
                <div class="mini-fila total"><span>Precio final sugerido</span><b>$ 12.450</b></div>
              </div>
            </div>
          </div>
          <div class="caja">
            <span class="ico-caja"><?php echo ui_icono('presupuestos', 20); ?></span>
            <h3>Presupuestos <span class="chip">Pronto</span></h3>
            <p>Generá presupuestos profesionales, guardalos y marcá los vendidos.</p>
          </div>
          <div class="caja">
            <span class="ico-caja"><?php echo ui_icono('clientes', 20); ?></span>
            <h3>Clientes <span class="chip">Pronto</span></h3>
            <p>Tu cartera de clientes con su historial de trabajos.</p>
          </div>
          <div class="caja">
            <span class="ico-caja"><?php echo ui_icono('stock', 20); ?></span>
            <h3>Stock <span class="chip">Pronto</span></h3>
            <p>Rollos e insumos controlados, con descuento automático al vender.</p>
          </div>
          <div class="caja">
            <span class="ico-caja"><?php echo ui_icono('libreria', 20); ?></span>
            <h3>Librería STL <span class="chip">Pronto</span></h3>
            <p>Modelos listos para imprimir, exclusivos para suscriptores.</p>
          </div>
          <div class="caja">
            <span class="ico-caja"><?php echo ui_icono('estadisticas', 20); ?></span>
            <h3>Estadísticas <span class="chip">Pronto</span></h3>
            <p>Cuánto imprimís, vendés y ganás, mes a mes.</p>
          </div>
        </div>
        <div style="text-align:center;margin-top:36px">
          <a class="btn sec" href="comunidad/cotizador/">Probar la calculadora</a>
        </div>
      </div>
    </section>

    <section class="comunidad" id="comunidad">
      <div class="cont">
        <div class="cabeza centrada">
          <span class="ceja">Comunidad</span>
          <h2>Todo lo que necesitás, en un mismo lugar</h2>
          <p>Ser parte es más que usar herramientas.</p>
        </div>
        <div class="beneficios">
          <div class="beneficio"><?php echo ui_icono('soporte', 20); ?>
            <div><h3>Soporte directo</h3><p>Te ayudamos por WhatsApp cuando lo necesitás.</p></div>
          </div>
          <div class="beneficio"><?php echo ui_icono('clientes', 20); ?>
            <div><h3>Comunidad de makers</h3><p>Emprendedores 3D que comparten precios, consejos y experiencia.</p></div>
          </div>
          <div class="beneficio"><?php echo ui_icono('nube', 20); ?>
            <div><h3>Tus datos en tu cuenta</h3><p>Presupuestos, clientes y stock accesibles desde cualquier lado.</p></div>
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
        <div class="cabeza centrada">
          <span class="ceja">Precios</span>
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
        <div class="cabeza centrada">
          <span class="ceja">FAQ</span>
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
        <div class="caja-cta">
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
