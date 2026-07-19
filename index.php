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
  <script>(function(){if(localStorage.getItem('ptools_tema')==='light'){document.documentElement.setAttribute('data-theme','light');}})();
  function ptTema(t){document.documentElement.setAttribute('data-theme',t==='light'?'light':'dark');localStorage.setItem('ptools_tema',t);}</script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root{
      color-scheme:dark;
      --fondo:#07090f; --bg:#0b0e16; --surface:#10141f; --surface-2:#161b29; --raised:#1d2434;
      --bd:#222a3d; --bd-suave:#181f2e;
      --txt:#eef2fa; --txt-2:#98a3b8; --txt-3:#5e6a82;
      --accent:#2db7fa; --accent-2:#7fd4ff; --accent-hover:#54c5fb;
      --accent-tinte:rgba(45,183,250,.10); --accent-ink:#06202f;
      --violeta:#6f7cf5; --ok:#3ecf8e;
      --nav-bg:rgba(7,9,15,.72); --nav-bd:rgba(255,255,255,.05);
      --grilla:rgba(255,255,255,.025); --vidrio:rgba(255,255,255,.02);
      --sombra-img:0 30px 90px -30px rgba(0,0,0,.85);
      --radio:10px; --radio-g:16px;
      --titulos:'Space Grotesk','Inter',sans-serif;
    }
    :root[data-theme="light"]{
      color-scheme:light;
      --fondo:#f2f4f9; --bg:#f7f8fc; --surface:#ffffff; --surface-2:#eef1f6; --raised:#e2e7f0;
      --bd:#d5dce8; --bd-suave:#e5eaf2;
      --txt:#16203a; --txt-2:#57627a; --txt-3:#939cb0;
      --accent:#1194d6; --accent-2:#2db7fa; --accent-hover:#0d81bd;
      --accent-tinte:rgba(17,148,214,.10); --accent-ink:#ffffff;
      --violeta:#6f7cf5; --ok:#14915f;
      --nav-bg:rgba(247,248,252,.8); --nav-bd:rgba(22,32,58,.08);
      --grilla:rgba(22,32,58,.05); --vidrio:rgba(22,32,58,.02);
      --sombra-img:0 30px 80px -32px rgba(22,32,58,.35);
    }
    .logo-claro{display:none !important}
    :root[data-theme="light"] .logo-claro{display:block !important}
    :root[data-theme="light"] .logo-oscuro{display:none !important}

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
    .btn.sec{background:var(--vidrio);color:var(--txt);border-color:var(--bd)}
    .btn.sec:hover{background:var(--surface-2);box-shadow:none}
    .chip{display:inline-flex;align-items:center;font-size:10px;font-weight:600;letter-spacing:.08em;
          padding:3px 9px;border-radius:99px;border:1px solid var(--bd);color:var(--txt-3);
          text-transform:uppercase;line-height:1.5}
    .chip.activo{border-color:rgba(62,207,142,.45);color:var(--ok);background:rgba(62,207,142,.07)}
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
    .nav{position:sticky;top:0;z-index:10;background:var(--nav-bg);backdrop-filter:blur(14px);
         -webkit-backdrop-filter:blur(14px);border-bottom:1px solid var(--nav-bd)}
    .nav .cont{display:flex;align-items:center;justify-content:space-between;height:104px;gap:14px}
    .nav .marca img{height:84px;width:auto;display:block}
    .nav nav{display:flex;align-items:center;gap:20px}
    .nav nav a{color:var(--txt-2);font-size:14px;font-weight:500;white-space:nowrap;transition:color .15s ease}
    .nav nav a:hover{color:var(--txt)}
    .nav .btn{height:38px;padding:0 16px;font-size:13.5px}
    .nav nav a.btn{color:var(--accent-ink)}
    .nav nav a.btn:hover{color:var(--accent-ink)}
    .nav nav a.entrar{color:var(--txt)}
    .tema{display:inline-flex;gap:3px;background:var(--surface-2);border:1px solid var(--bd-suave);
          border-radius:99px;padding:3px}
    .tema-btn{display:flex;align-items:center;justify-content:center;width:32px;height:28px;
          background:none;border:none;border-radius:99px;color:var(--txt-3);cursor:pointer;
          transition:background-color .15s ease,color .15s ease}
    .tema-btn:hover{color:var(--txt-2)}
    :root:not([data-theme="light"]) .tema-btn[data-tema="dark"],
    :root[data-theme="light"] .tema-btn[data-tema="light"]{
          background:var(--surface);color:var(--accent);box-shadow:0 1px 3px rgba(0,0,0,.25)}

    /* ---- Hero ---- */
    .hero{position:relative;padding:88px 0 72px;overflow:hidden}
    .hero::before{content:'';position:absolute;inset:0;pointer-events:none;
        background:
          radial-gradient(52% 42% at 16% 8%, rgba(45,183,250,.13), transparent 60%),
          radial-gradient(40% 36% at 90% 34%, rgba(111,124,245,.10), transparent 65%);}
    .hero::after{content:'';position:absolute;inset:0;pointer-events:none;opacity:.5;
        background-image:linear-gradient(var(--grilla) 1px,transparent 1px),
                         linear-gradient(90deg,var(--grilla) 1px,transparent 1px);
        background-size:56px 56px;
        -webkit-mask-image:radial-gradient(60% 55% at 50% 30%,#000 30%,transparent 100%);
                mask-image:radial-gradient(60% 55% at 50% 30%,#000 30%,transparent 100%)}
    .hero .cont{position:relative;display:grid;grid-template-columns:1.02fr .98fr;gap:52px;align-items:center}
    .insignia{display:inline-flex;align-items:center;gap:8px;background:var(--accent-tinte);
        border:1px solid rgba(45,183,250,.3);color:var(--accent);font-size:12.5px;font-weight:500;
        border-radius:99px;padding:6px 14px;margin-bottom:22px}
    .insignia .punto{width:7px;height:7px;border-radius:99px;background:var(--ok);
        box-shadow:0 0 8px rgba(62,207,142,.8)}
    .hero h1{font-size:clamp(32px,4.4vw,52px);font-weight:700;letter-spacing:-.025em;line-height:1.08;
        margin-bottom:20px}
    .hero h1 em{font-style:normal;background:linear-gradient(92deg,var(--accent),var(--accent-2));
        -webkit-background-clip:text;background-clip:text;color:transparent}
    .hero .sub{font-size:clamp(15px,1.6vw,17.5px);color:var(--txt-2);max-width:480px;margin-bottom:32px}
    .hero .ctas{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:44px}
    .stats{display:flex;flex-wrap:nowrap;border-top:1px solid var(--bd-suave);padding-top:24px}
    .stat{padding:0 24px;border-left:1px solid var(--bd-suave);min-width:0}
    .stat:first-child{padding-left:0;border-left:none}
    .stat b{display:block;font-family:var(--titulos);font-size:24px;font-weight:700;
        letter-spacing:-.02em;color:var(--txt)}
    .stat span{font-size:12px;color:var(--txt-3)}
    .hero-visual{position:relative}
    .hero-visual::before{content:'';position:absolute;inset:-10%;pointer-events:none;
        background:radial-gradient(50% 50% at 50% 50%, rgba(45,183,250,.18), transparent 70%)}
    .hero-visual img{position:relative;width:100%;height:auto;display:block;border-radius:18px;
        border:1px solid var(--raised);box-shadow:var(--sombra-img)}

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
    .mini-fila.total{border-color:rgba(45,183,250,.4);background:var(--accent-tinte)}
    .mini-fila.total b{color:var(--accent)}

    /* ---- Comunidad (foto + beneficios) ---- */
    .comunidad{background:linear-gradient(180deg,var(--bg),var(--fondo));
        border-top:1px solid var(--bd-suave);border-bottom:1px solid var(--bd-suave)}
    .comunidad .dos{display:grid;grid-template-columns:.95fr 1.05fr;gap:48px;align-items:center}
    .foto-taller{position:relative}
    .foto-taller img{width:100%;height:auto;display:block;border-radius:18px;
        border:1px solid var(--raised);box-shadow:var(--sombra-img)}
    .foto-taller .flotante{position:absolute;right:-14px;top:26px;display:flex;align-items:center;
        gap:10px;background:var(--surface);border:1px solid var(--bd);border-radius:12px;
        padding:11px 15px;box-shadow:0 12px 40px -12px rgba(0,0,0,.6)}
    .foto-taller .flotante .punto{width:8px;height:8px;border-radius:99px;background:var(--ok);
        box-shadow:0 0 8px rgba(62,207,142,.8)}
    .foto-taller .flotante span{font-size:12.5px;font-weight:600}
    .beneficios{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    .beneficio{display:flex;gap:12px;align-items:flex-start;background:var(--vidrio);
        border:1px solid var(--bd-suave);border-radius:var(--radio-g);padding:16px;
        transition:border-color .15s ease,background-color .15s ease}
    .beneficio:hover{border-color:var(--bd);background:var(--surface)}
    .beneficio .ico{color:var(--accent);margin-top:2px}
    .beneficio h3{font-size:14px;font-weight:700;letter-spacing:-.01em}
    .beneficio p{font-size:12.5px;color:var(--txt-2);margin-top:2px}

    /* ---- Planes ---- */
    .planes-grilla{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,340px));
        gap:18px;justify-content:center;align-items:stretch}
    .plan .ahorro{display:inline-block;font-size:11.5px;font-weight:600;color:var(--ok);
        background:rgba(62,207,142,.09);border:1px solid rgba(62,207,142,.3);
        border-radius:99px;padding:3px 10px;margin-top:6px}
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

    /* ---- Cierre (ancho completo) ---- */
    .cierre{text-align:center;position:relative;overflow:hidden;background:var(--surface);
        border-top:1px solid var(--bd-suave);border-bottom:1px solid var(--bd-suave);
        padding:104px 0}
    .cierre::before{content:'';position:absolute;inset:0;pointer-events:none;
        background:radial-gradient(55% 90% at 50% 0%, rgba(45,183,250,.14), transparent 70%)}
    .cierre .cont{position:relative}
    .cierre h2{font-size:clamp(24px,3.2vw,34px);font-weight:700;
        letter-spacing:-.02em;margin-bottom:12px}
    .cierre p{color:var(--txt-2);font-size:15.5px;max-width:520px;margin:0 auto 30px}

    /* ---- Footer ---- */
    footer{border-top:1px solid var(--bd-suave);padding:52px 0 32px;background:var(--fondo)}
    .footer-grilla{display:grid;grid-template-columns:2fr 1fr 1fr;gap:32px;margin-bottom:40px}
    .footer-grilla img{height:92px;width:auto;margin-bottom:14px}
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

    @media (max-width:1100px){
      .nav nav a.link-seccion{display:none}
    }
    @media (max-width:960px){
      .nav .cont{height:84px}
      .nav .marca img{height:64px}
      .hero .cont{grid-template-columns:1fr;gap:40px}
      .hero-visual{max-width:600px}
      .bento{grid-template-columns:1fr 1fr}
      .caja.grande{grid-column:span 2;grid-row:auto}
      .comunidad .dos{grid-template-columns:1fr;gap:36px}
      .nav nav{gap:14px}
    }
    @media (max-width:680px){
      .nav nav a.link-seccion{display:none}
      .hero{padding:56px 0 44px}
      section{padding:56px 0}
      .bento{grid-template-columns:1fr}
      .caja.grande{grid-column:auto}
      .beneficios{grid-template-columns:1fr}
      .stat{padding:0 12px}
      .stat b{font-size:19px}
      .stat span{font-size:11px}
      .footer-grilla{grid-template-columns:1fr;gap:26px}
      .foto-taller .flotante{right:10px}
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
      <a class="marca" href="/">
        <img class="logo-oscuro" src="assets/img/printika-tools-dark.svg" alt="Printika Tools">
        <img class="logo-claro" src="assets/img/printika-tools.svg" alt="Printika Tools">
      </a>
      <nav>
        <a class="link-seccion" href="#herramientas">Herramientas</a>
        <a class="link-seccion" href="#comunidad">Comunidad</a>
        <a class="link-seccion" href="#planes">Precios</a>
        <a class="link-seccion" href="#faq">FAQ</a>
        <a class="link-seccion" href="comunidad/cotizador/">Calculadora</a>
        <span class="tema" role="group" aria-label="Tema de la página">
          <button type="button" class="tema-btn" data-tema="light" onclick="ptTema('light')"
                  title="Modo día" aria-label="Modo día"><?php echo ui_icono('sol', 15); ?></button>
          <button type="button" class="tema-btn" data-tema="dark" onclick="ptTema('dark')"
                  title="Modo noche" aria-label="Modo noche"><?php echo ui_icono('luna', 15); ?></button>
        </span>
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
        <div class="hero-visual">
          <img src="assets/img/landing/hero-impresora.webp" alt="Impresora 3D imprimiendo una pieza"
               width="1376" height="768" fetchpriority="high">
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
        <div class="dos">
          <div class="foto-taller">
            <img src="assets/img/landing/taller-maker.webp" alt="Taller de impresión 3D con piezas impresas y rollos de filamento"
                 width="1376" height="768" loading="lazy">
            <div class="flotante" aria-hidden="true"><span class="punto"></span><span>Comunidad activa</span></div>
          </div>
          <div class="beneficios">
            <div class="beneficio"><?php echo ui_icono('soporte', 18); ?>
              <div><h3>Soporte directo</h3><p>Te ayudamos por WhatsApp cuando lo necesitás.</p></div>
            </div>
            <div class="beneficio"><?php echo ui_icono('clientes', 18); ?>
              <div><h3>Comunidad de makers</h3><p>Precios, consejos y experiencia compartida.</p></div>
            </div>
            <div class="beneficio"><?php echo ui_icono('nube', 18); ?>
              <div><h3>Tus datos en tu cuenta</h3><p>Accesibles desde cualquier dispositivo.</p></div>
            </div>
            <div class="beneficio"><?php echo ui_icono('rayo', 18); ?>
              <div><h3>Mejoras constantes</h3><p>Herramientas nuevas todos los meses.</p></div>
            </div>
            <div class="beneficio"><?php echo ui_icono('libreria', 18); ?>
              <div><h3>Contenido exclusivo</h3><p>Archivos y recursos para suscriptores.</p></div>
            </div>
            <div class="beneficio"><?php echo ui_icono('admin', 18); ?>
              <div><h3>Sin permanencia</h3><p>Entrás y salís cuando quieras.</p></div>
            </div>
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
          <div class="plan">
            <h3>Comunidad Mensual</h3>
            <p class="precio">$18.000 <small>/mes</small></p>
            <p class="nota">Renovación mes a mes, sin permanencia</p>
            <ul>
              <li><?php echo ui_icono('check', 15); ?>Calculadora completa (versión PRO)</li>
              <li><?php echo ui_icono('check', 15); ?>Mi Taller: presupuestos, clientes y stock</li>
              <li><?php echo ui_icono('check', 15); ?>Librería STL y estadísticas</li>
              <li><?php echo ui_icono('check', 15); ?>Tus datos guardados en tu cuenta</li>
              <li><?php echo ui_icono('check', 15); ?>Soporte técnico prioritario</li>
              <li><?php echo ui_icono('check', 15); ?>Herramientas nuevas cada mes</li>
            </ul>
            <a class="btn sec" href="<?php echo COMUNIDAD_WHATSAPP; ?>" target="_blank" rel="noopener">Suscribirme</a>
          </div>
          <div class="plan destacado">
            <span class="etiqueta">Más de 2 meses gratis</span>
            <h3>Comunidad Anual</h3>
            <p class="precio">$170.000 <small>/año</small></p>
            <span class="ahorro">Equivale a $14.167 por mes · ahorrás $46.000</span>
            <p class="nota" style="margin-top:12px">Un solo pago y te olvidás todo el año</p>
            <ul>
              <li><?php echo ui_icono('check', 15); ?>Todo lo del plan mensual</li>
              <li><?php echo ui_icono('check', 15); ?>Más de 2 meses sin cargo ($46.000 de ahorro)</li>
              <li><?php echo ui_icono('check', 15); ?>Precio congelado por 12 meses</li>
              <li><?php echo ui_icono('check', 15); ?>Acceso anticipado a herramientas nuevas</li>
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
            <summary>¿El pago es mensual o anual?</summary>
            <p class="resp">Como prefieras: el plan mensual cuesta $18.000 y se renueva mes a mes sin permanencia,
            y el plan anual cuesta $170.000 — ahorrás $46.000 (más de 2 meses gratis) y el precio queda congelado todo el año.</p>
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
        <h2>Empezá hoy — es gratis</h2>
        <p>Creá tu cuenta, probá la calculadora y descubrí por qué cada vez más makers
           manejan su taller con Printika Tools.</p>
        <a class="btn" href="comunidad/registro.php">Crear mi cuenta</a>
      </div>
    </section>
  </main>

  <footer>
    <div class="cont">
      <div class="footer-grilla">
        <div>
          <img class="logo-oscuro" src="assets/img/printika-tools-dark.svg" alt="Printika Tools">
          <img class="logo-claro" src="assets/img/printika-tools.svg" alt="Printika Tools">
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
