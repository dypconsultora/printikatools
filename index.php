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
  <style>.idioma button.activo{opacity:1 !important;background:var(--surface,rgba(255,255,255,.12)) !important}</style>
  <script src="assets/js/landing-en.js" defer></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js" defer></script>
  <style>
    #cargador{position:fixed;inset:0;z-index:200;background:var(--bg,#0b0f17);display:flex;
      flex-direction:column;align-items:center;justify-content:center;gap:26px}
    #cargador img{width:min(300px,60vw);height:auto}
    #cargador .num{font-family:'Space Grotesk',Inter,sans-serif;font-size:clamp(38px,6vw,64px);
      font-weight:700;letter-spacing:-.02em;color:var(--txt,#e8edf5);font-variant-numeric:tabular-nums}
    #cargador .barra{width:min(300px,60vw);height:3px;border-radius:99px;background:rgba(128,148,180,.18);overflow:hidden}
    #cargador .barra i{display:block;height:100%;width:0;background:var(--accent,#2db7fa)}
    /* Que los anclajes frenen debajo de la barra fija */
    #herramientas,#comunidad,#planes,#faq{scroll-margin-top:36px}
    .moneda-sel button.activo{opacity:1 !important;background:var(--accent) !important;color:var(--accent-ink,#06202f) !important}
    .anim-oculto{opacity:0}
    .h1-serena .palabra{display:inline-block;opacity:0;transition:color .3s ease,transform .3s ease}
    .h1-serena .palabra:hover{transform:translateY(-2px);color:var(--accent)}
    @keyframes palabra-aparece{0%{opacity:0;transform:translateY(30px) scale(.8);filter:blur(10px)}
      50%{opacity:.8;transform:translateY(10px) scale(.95);filter:blur(2px)}
      100%{opacity:1;transform:none;filter:none}}
    .linea-fx{stroke:rgba(148,163,184,.7);stroke-width:.5;opacity:0;stroke-dasharray:5 5;
      stroke-dashoffset:1000;animation:traza-fx 2.4s ease-out forwards}
    @keyframes traza-fx{0%{stroke-dashoffset:1000;opacity:0}50%{opacity:.28}100%{stroke-dashoffset:0;opacity:.14}}
    .punto-fx{fill:rgba(45,183,250,.9);opacity:0;animation:brilla-fx 3s ease-in-out infinite}
    @keyframes brilla-fx{0%,100%{opacity:.1;transform:scale(1)}50%{opacity:.4;transform:scale(1.15)}}
    .flota-fx{position:absolute;width:3px;height:3px;background:var(--accent,#2db7fa);border-radius:50%;
      opacity:0;animation:flota 5s ease-in-out infinite}
    @keyframes flota{0%,100%{transform:none;opacity:.15}25%{transform:translate(5px,-10px);opacity:.6}
      50%{transform:translate(-3px,-5px);opacity:.35}75%{transform:translate(7px,-15px);opacity:.7}}
    #halo-mouse{position:fixed;width:380px;height:380px;border-radius:50%;pointer-events:none;z-index:5;
      background:radial-gradient(circle,rgba(45,183,250,.06),rgba(45,183,250,.03),transparent 70%);
      transform:translate(-50%,-50%);filter:blur(30px);opacity:0;
      transition:left 70ms linear,top 70ms linear,opacity .3s ease-out}
    .onda-fx{position:fixed;width:5px;height:5px;background:rgba(45,183,250,.55);border-radius:50%;
      transform:translate(-50%,-50%);pointer-events:none;z-index:9999;animation:brilla-fx 1s ease-out forwards}
    @media (prefers-reduced-motion: reduce){ .h1-serena .palabra{opacity:1} .linea-fx,.punto-fx,.flota-fx{animation:none} }
    @media (prefers-reduced-motion: reduce){ #cargador{display:none} .anim-oculto{opacity:1} }
  </style>
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
    .cont{max-width:1560px;margin:0 auto;padding:0 clamp(24px,4vw,64px)}
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
    .nav .cont{max-width:none;padding:0 36px;display:flex;align-items:center;
         justify-content:space-between;height:104px;gap:14px}
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
    .hero h1 em{font-style:normal}
    .hero h1 em .palabra{background:linear-gradient(92deg,var(--accent),var(--accent-2));
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
    .comunidad .dos{display:grid;grid-template-columns:.95fr 1.05fr;gap:48px;align-items:stretch}
    .foto-taller{position:relative;min-height:360px}
    .foto-taller > img{width:100%;height:100%;object-fit:cover}
    .foto-taller img{width:100%;height:auto;display:block;border-radius:18px;
        border:1px solid var(--raised);box-shadow:var(--sombra-img)}
    .foto-taller .flotante{position:absolute;right:-14px;top:26px;display:flex;align-items:center;
        gap:10px;background:var(--surface);border:1px solid var(--bd);border-radius:12px;
        padding:11px 15px;box-shadow:0 12px 40px -12px rgba(0,0,0,.6)}
    .foto-taller .flotante .punto{width:8px;height:8px;border-radius:99px;background:var(--ok);
        box-shadow:0 0 8px rgba(62,207,142,.8)}
    .foto-taller .flotante span{font-size:12.5px;font-weight:600}
    .beneficios{display:grid;grid-template-columns:1fr 1fr;gap:14px;align-content:stretch;height:100%}
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
    .cierre .cont{position:relative;z-index:2}
    #fondoCierre{position:absolute;inset:0;width:100%;height:100%;pointer-events:none;
        color:var(--txt-3);opacity:.35}
    #fondoCierre g{opacity:0;transition:opacity 2s ease}
    #fondoCierre g.activa{opacity:1}
    #fondoCierre .traza{fill:none;stroke:currentColor;stroke-linecap:round;
        stroke-dasharray:240;stroke-dashoffset:240;animation:dibuja-c 9s ease-in-out infinite}
    #fondoCierre .nodo{fill:var(--accent);animation:late-c 4s ease-in-out infinite}
    @keyframes dibuja-c{0%{stroke-dashoffset:240;opacity:0}35%{opacity:.7}70%{stroke-dashoffset:0;opacity:.4}100%{stroke-dashoffset:0;opacity:0}}
    @keyframes late-c{0%,100%{opacity:.15;transform:scale(1)}50%{opacity:.7;transform:scale(1.25)}}
    .orbe{position:absolute;border-radius:50%;filter:blur(10px);pointer-events:none;z-index:1}
    .orbe.o1{width:22px;height:22px;background:rgba(45,183,250,.35);top:24%;left:18%;animation:orbita 7s ease-in-out infinite}
    .orbe.o2{width:30px;height:30px;background:rgba(147,97,255,.3);bottom:20%;right:22%;animation:orbita 9s ease-in-out infinite reverse}
    @keyframes orbita{0%,100%{transform:none;opacity:.35}50%{transform:translate(12px,-18px) scale(1.15);opacity:.75}}
    .patron-dots{position:absolute;top:22px;right:26px;display:flex;gap:7px;z-index:2}
    .patron-dots i{width:7px;height:7px;border-radius:50%;background:var(--bd);transition:all .3s ease}
    .patron-dots i.on{background:var(--accent);transform:scale(1.25)}
    .titulo-letras .letra{display:inline-block;opacity:0;transform:translateY(40px) rotateX(-80deg);
        transition:opacity .6s ease,transform .6s cubic-bezier(.2,.9,.3,1.2)}
    .titulo-letras.lista .letra{opacity:1;transform:none}
    .cta-borde{display:inline-block;padding:2px;border-radius:16px;
        background:linear-gradient(90deg,#2db7fa,#7c4dff,#ff5db1);background-size:200% 100%;
        animation:borde-vivo 6s linear infinite;transition:transform .25s ease,box-shadow .25s ease}
    .cta-borde:hover{transform:translateY(-3px);box-shadow:0 14px 40px rgba(45,183,250,.25)}
    @keyframes borde-vivo{0%{background-position:0% 0}100%{background-position:200% 0}}
    .cta-btn{display:inline-flex;align-items:center;gap:10px;padding:15px 34px;border-radius:14px;
        background:var(--surface);color:var(--txt);font-size:16px;font-weight:700}
    .cta-btn:hover{color:var(--txt)}
    .flecha-cta{display:inline-block;animation:va-viene 2s ease-in-out infinite}
    @keyframes va-viene{0%,100%{transform:none}50%{transform:translateX(5px)}}
    @media (prefers-reduced-motion: reduce){
      .titulo-letras .letra{opacity:1;transform:none}
      #fondoCierre .traza,#fondoCierre .nodo,.orbe,.flecha-cta,.cta-borde{animation:none}
    }
    .cierre h2{font-size:clamp(24px,3.2vw,34px);font-weight:700;
        letter-spacing:-.02em;margin-bottom:12px}
    .cierre p{color:var(--txt-2);font-size:15.5px;max-width:520px;margin:0 auto 30px}

    /* ---- Footer ---- */
    footer{border-top:1px solid var(--bd-suave);padding:52px 0 32px;background:var(--fondo)}
    footer .cont{max-width:none;padding:0 36px}
    .footer-grilla{display:grid;grid-template-columns:1.6fr 1fr 1fr 1fr;gap:40px;
      padding:56px 0 40px;border-top:1px solid var(--bd-suave)}
    .footer-marca img{height:92px;width:auto}
    .footer-cta{margin-top:18px;display:inline-flex}
    footer h4{font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;
      color:var(--txt-3);margin-bottom:14px}
    footer ul{list-style:none;margin:0;padding:0;display:grid;gap:9px}
    footer ul a{font-size:14px;color:var(--txt-2)}
    footer ul a:hover{color:var(--accent)}
    @media (max-width:900px){ .footer-grilla{grid-template-columns:1fr 1fr} }
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
      .nav .cont, footer .cont{padding:0 20px}
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
  <div id="cargador" aria-hidden="true">
    <img src="assets/img/printika-tools-dark.svg" alt="">
    <div class="num">0%</div>
    <div class="barra"><i></i></div>
  </div>
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
          <span class="idioma" role="group" aria-label="Idioma / Language" style="display:inline-flex;align-items:center;gap:2px;background:var(--surface-2,rgba(255,255,255,.06));border:1px solid var(--bd,rgba(255,255,255,.12));border-radius:999px;padding:2px;margin-right:10px">
            <span style="font-size:10px;font-weight:600;letter-spacing:.08em;color:var(--txt-3,#8a95a8);padding:0 6px 0 10px">IDIOMA</span>
            <button type="button" data-idi="es" style="background:none;border:none;border-radius:999px;padding:3px 10px;font-family:inherit;font-size:11px;font-weight:700;color:inherit;cursor:pointer;opacity:.55">ESP</button>
            <button type="button" data-idi="en" style="background:none;border:none;border-radius:999px;padding:3px 10px;font-family:inherit;font-size:11px;font-weight:700;color:inherit;cursor:pointer;opacity:.55">ENG</button>
          </span>
          <button type="button" class="tema-btn" data-tema="light" onclick="ptTema('light')"
                  title="Modo día" aria-label="Modo día"><?php echo ui_icono('sol', 15); ?></button>
          <button type="button" class="tema-btn" data-tema="dark" onclick="ptTema('dark')"
                  title="Modo noche" aria-label="Modo noche"><?php echo ui_icono('luna', 15); ?></button>
        </span>
        <a class="entrar" href="comunidad/login.php">Iniciar sesión</a>
        <a class="btn" href="#planes">Registrarse</a>
      </nav>
    </div>
  </header>

  <main>
    <div class="hero" style="position:relative;overflow:hidden">
      <svg class="hero-fx" style="position:absolute;inset:0;width:100%;height:100%;pointer-events:none" aria-hidden="true">
        <defs><pattern id="grillaPt" width="60" height="60" patternUnits="userSpaceOnUse">
          <path d="M 60 0 L 0 0 0 60" fill="none" stroke="rgba(100,116,139,.09)" stroke-width="0.5"/></pattern></defs>
        <rect width="100%" height="100%" fill="url(#grillaPt)"/>
        <line x1="0" y1="22%" x2="100%" y2="22%" class="linea-fx" style="animation-delay:.5s"/>
        <line x1="0" y1="82%" x2="100%" y2="82%" class="linea-fx" style="animation-delay:1s"/>
        <line x1="18%" y1="0" x2="18%" y2="100%" class="linea-fx" style="animation-delay:1.5s"/>
        <line x1="82%" y1="0" x2="82%" y2="100%" class="linea-fx" style="animation-delay:2s"/>
        <circle cx="18%" cy="22%" r="2" class="punto-fx" style="animation-delay:2.6s"/>
        <circle cx="82%" cy="22%" r="2" class="punto-fx" style="animation-delay:2.8s"/>
        <circle cx="18%" cy="82%" r="2" class="punto-fx" style="animation-delay:3s"/>
        <circle cx="82%" cy="82%" r="2" class="punto-fx" style="animation-delay:3.2s"/>
      </svg>
      <span class="flota-fx" style="top:25%;left:12%;animation-delay:.5s"></span>
      <span class="flota-fx" style="top:60%;left:88%;animation-delay:1s"></span>
      <span class="flota-fx" style="top:42%;left:7%;animation-delay:1.5s"></span>
      <span class="flota-fx" style="top:78%;left:93%;animation-delay:2s"></span>
      <div class="cont">
        <div>
          <span class="insignia"><span class="punto"></span>Comunidad 3D en español</span>
          <h1 class="h1-serena"><span class="palabra" data-delay="0">Manejá</span> <span class="palabra" data-delay="120">tu</span> <span class="palabra" data-delay="240">taller</span> <span class="palabra" data-delay="360">de</span> <span class="palabra" data-delay="480">impresión</span> <span class="palabra" data-delay="600">3D</span> <em><span class="palabra" data-delay="780">como</span> <span class="palabra" data-delay="900">un</span> <span class="palabra" data-delay="1020">negocio</span></em></h1>
          <p class="sub">Calculadora de costos, presupuestos, clientes y stock de materiales.
             Las herramientas de una comunidad de makers, en un mismo lugar.</p>
          <div class="ctas">
            <a class="btn" href="comunidad/registro.php?plan=gratis">Comenzar gratis</a>
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
              <div><h3>Soporte directo</h3><p>Te ayudamos por Telegram cuando lo necesitás.</p></div>
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
          <div class="moneda-sel" role="group" aria-label="Moneda de pago" style="display:inline-flex;align-items:center;gap:2px;margin-top:18px;background:var(--surface-2,rgba(255,255,255,.06));border:1px solid var(--bd,rgba(255,255,255,.12));border-radius:999px;padding:3px">
            <span style="font-size:10px;font-weight:600;letter-spacing:.08em;color:var(--txt-3,#8a95a8);padding:0 8px 0 12px">MONEDA</span>
            <button type="button" data-mon="ars" class="activo" style="background:none;border:none;border-radius:999px;padding:5px 14px;font-family:inherit;font-size:12px;font-weight:700;color:inherit;cursor:pointer;opacity:.55">ARS</button>
            <button type="button" data-mon="usd" style="background:none;border:none;border-radius:999px;padding:5px 14px;font-family:inherit;font-size:12px;font-weight:700;color:inherit;cursor:pointer;opacity:.55">USD</button>
          </div>
        </div>
        <div class="planes-grilla">
          <div class="plan">
            <h3>Printika Free</h3>
            <p class="precio">$0</p>
            <p class="nota">Para probar y empezar</p>
            <ul>
              <li><?php echo ui_icono('check', 15); ?>Calculadora de costos online</li>
              <li><?php echo ui_icono('check', 15); ?>Cálculo en ARS, USD y EUR</li>
              <li><?php echo ui_icono('check', 15); ?>Cuenta gratuita en la comunidad</li>
            </ul>
            <a class="btn sec" href="comunidad/registro.php?plan=gratis">Empezar gratis</a>
          </div>
          <div class="plan">
            <h3>Printika Pro</h3>
            <p class="precio"><span class="monto" data-ars="$18.000" data-usd="US$15">$18.000</span> <small>/mes</small></p>
            <p class="nota">Renovación mes a mes, sin permanencia</p>
            <ul>
              <li><?php echo ui_icono('check', 15); ?>Calculadora completa (versión PRO)</li>
              <li><?php echo ui_icono('check', 15); ?>Mi Taller: presupuestos, clientes y stock</li>
              <li><?php echo ui_icono('check', 15); ?>Librería STL y estadísticas</li>
              <li><?php echo ui_icono('check', 15); ?>Tus datos guardados en tu cuenta</li>
              <li><?php echo ui_icono('check', 15); ?>Soporte técnico prioritario</li>
              <li><?php echo ui_icono('check', 15); ?>Herramientas nuevas cada mes</li>
            </ul>
            <a class="btn sec btn-pago" target="_blank" rel="noopener"
               data-mp="https://mpago.la/118mn81" data-pp="https://www.paypal.com/CAMBIAR-mensual"
               href="https://mpago.la/118mn81">Suscribirme</a>
          </div>
          <div class="plan destacado">
            <span class="etiqueta swap-mon" data-ars="Más de 2 meses gratis" data-usd="1 mes gratis">Más de 2 meses gratis</span>
            <h3>Printika Pro Anual</h3>
            <p class="precio"><span class="monto" data-ars="$170.000" data-usd="US$165">$170.000</span> <small>/año</small></p>
            <span class="ahorro" data-ars="Equivale a $14.167 por mes · ahorrás $46.000" data-usd="Equivale a US$13,75 por mes · ahorrás US$15">Equivale a $14.167 por mes · ahorrás $46.000</span>
            <p class="nota" style="margin-top:12px">Un solo pago y te olvidás todo el año</p>
            <ul>
              <li><?php echo ui_icono('check', 15); ?>Todo lo del plan mensual</li>
              <li><?php echo ui_icono('check', 15); ?><span class="swap-mon" data-ars="Más de 2 meses sin cargo ($46.000 de ahorro)" data-usd="1 mes sin cargo (US$15 de ahorro)">Más de 2 meses sin cargo ($46.000 de ahorro)</span></li>
              <li><?php echo ui_icono('check', 15); ?>Precio congelado por 12 meses</li>
              <li><?php echo ui_icono('check', 15); ?>Acceso anticipado a herramientas nuevas</li>
            </ul>
            <a class="btn btn-pago" target="_blank" rel="noopener"
               data-mp="https://mpago.la/1vNcghS" data-pp="https://www.paypal.com/CAMBIAR-anual"
               href="https://mpago.la/1vNcghS">Suscribirme</a>
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
      <svg id="fondoCierre" viewBox="0 0 800 420" preserveAspectRatio="xMidYMid slice" aria-hidden="true"></svg>
      <span class="orbe o1"></span><span class="orbe o2"></span>
      <div class="patron-dots" aria-hidden="true"><i class="on"></i><i></i><i></i></div>
      <div class="cont">
        <h2 id="tituloCierre" class="titulo-letras">Empezá hoy — es gratis</h2>
        <p>Creá tu cuenta, probá la calculadora y descubrí por qué cada vez más makers
           manejan su taller con Printika Tools.</p>
        <span class="cta-borde">
          <a class="cta-btn" href="comunidad/registro.php?plan=gratis">Crear mi cuenta
            <span class="flecha-cta">→</span></a>
        </span>
      </div>
    </section>
  </main>

  <footer>
    <div class="cont">
      <div class="footer-grilla">
        <div class="footer-marca">
          <img class="logo-oscuro" src="assets/img/printika-tools-dark.svg" alt="Printika Tools">
          <img class="logo-claro" src="assets/img/printika-tools.svg" alt="Printika Tools">
          <p class="desc">Las herramientas y la comunidad para manejar tu taller de impresión 3D como un negocio.</p>
          <a class="btn sec footer-cta" href="comunidad/registro.php?plan=gratis">Comenzar gratis</a>
        </div>
        <div>
          <h4>Plataforma</h4>
          <ul>
            <li><a href="comunidad/cotizador/">Calculadora</a></li>
            <li><a href="#herramientas">Herramientas</a></li>
            <li><a href="#planes">Precios</a></li>
            <li><a href="#faq">FAQ</a></li>
          </ul>
        </div>
        <div>
          <h4>Tu cuenta</h4>
          <ul>
            <li><a href="comunidad/login.php">Iniciar sesión</a></li>
            <li><a href="comunidad/registro.php?plan=gratis">Registrarse</a></li>
            <li><a href="comunidad/suscripcion.php">Planes</a></li>
          </ul>
        </div>
        <div>
          <h4>Comunidad</h4>
          <ul>
            <li><a href="https://t.me/+N5f7IcWPXihhMWQx" target="_blank" rel="noopener">Telegram</a></li>
            <li><a href="<?php echo COMUNIDAD_WHATSAPP; ?>" target="_blank" rel="noopener">WhatsApp</a></li>
            <li><a href="https://printika3d.com" target="_blank" rel="noopener">Printika 3D</a></li>
          </ul>
        </div>
      </div>
      <div class="footer-pie">
        <p>© <?php echo date('Y'); ?> Printika Tools. Todos los derechos reservados.</p>
        <p>Hecho con impresoras 3D en Argentina</p>
      </div>
    </div>
  </footer>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var reducido = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var cargador = document.getElementById('cargador');
  if (reducido || !window.gsap) {
    if (cargador) cargador.remove();
    document.querySelectorAll('.h1-serena .palabra').forEach(function (w) { w.style.opacity = 1; });
    return;
  }

  // Elementos que entran animados
  var heroSel = ['.hero .insignia', '.hero .sub', '.hero .ctas', '.hero .stats', '.hero-visual'];
  var heroEls = heroSel.map(function (q) { return document.querySelector(q); }).filter(Boolean);
  gsap.set(heroEls, { opacity: 0, y: 26 });

  // Contador 0 -> 100%
  var st = { v: 0 };
  var num = cargador.querySelector('.num');
  var barra = cargador.querySelector('.barra i');
  var tl = gsap.timeline();
  tl.to(st, {
    v: 100, duration: 1.5, ease: 'power2.inOut',
    onUpdate: function () {
      num.textContent = Math.round(st.v) + '%';
      barra.style.width = st.v + '%';
    }
  })
  .to(cargador, { yPercent: -100, duration: 0.65, ease: 'power3.inOut' }, '+=0.15')
  .add(function () { cargador.remove(); }, '-=0.2')
  .to(heroEls, { opacity: 1, y: 0, duration: 0.7, ease: 'power3.out', stagger: 0.09 }, '-=0.35')
  .add(function () {
    document.querySelectorAll('.h1-serena .palabra').forEach(function (w) {
      setTimeout(function () { w.style.animation = 'palabra-aparece .8s ease-out forwards'; },
        parseInt(w.dataset.delay || 0, 10));
    });
  }, '-=0.55');

  // Halo que sigue al mouse + ondas al hacer click
  var halo = document.createElement('div');
  halo.id = 'halo-mouse';
  document.body.appendChild(halo);
  document.addEventListener('mousemove', function (e) {
    halo.style.left = e.clientX + 'px'; halo.style.top = e.clientY + 'px'; halo.style.opacity = 1;
  });
  document.addEventListener('mouseleave', function () { halo.style.opacity = 0; });
  document.addEventListener('click', function (e) {
    var o = document.createElement('div');
    o.className = 'onda-fx'; o.style.left = e.clientX + 'px'; o.style.top = e.clientY + 'px';
    document.body.appendChild(o);
    setTimeout(function () { o.remove(); }, 1000);
  });
  window.__tlCarga = tl;
  // Seguro: si la pestaña estuvo en segundo plano, terminar la carga igual
  setTimeout(function () { if (document.getElementById('cargador')) tl.progress(1); }, 7000);

  // Aparicion al scrollear: tarjetas, planes, faq y titulos de seccion
  var reveal = document.querySelectorAll('.caja, .plan, .faq details, .cabeza, .vent, .cierre .cont');
  reveal.forEach(function (el) { gsap.set(el, { opacity: 0, y: 30 }); });
  var io = new IntersectionObserver(function (entradas) {
    entradas.forEach(function (e) {
      if (!e.isIntersecting) return;
      gsap.to(e.target, { opacity: 1, y: 0, duration: 0.65, ease: 'power3.out' });
      io.unobserve(e.target);
    });
  }, { rootMargin: '0px 0px -60px 0px' });
  reveal.forEach(function (el) { io.observe(el); });

  // Micro-interaccion en los botones principales
  document.querySelectorAll('.btn').forEach(function (b) {
    b.addEventListener('mouseenter', function () { gsap.to(b, { scale: 1.03, duration: 0.18, ease: 'power2.out' }); });
    b.addEventListener('mouseleave', function () { gsap.to(b, { scale: 1, duration: 0.22, ease: 'power2.out' }); });
  });
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var botones = document.querySelectorAll('.moneda-sel button');
  botones.forEach(function (b) {
    b.addEventListener('click', function () {
      botones.forEach(function (x) { x.classList.toggle('activo', x === b); });
      var usd = b.dataset.mon === 'usd';
      document.querySelectorAll('.btn-pago').forEach(function (a) {
        a.href = usd ? a.dataset.pp : a.dataset.mp;
      });
      document.querySelectorAll('#planes .monto, #planes .ahorro, #planes .swap-mon').forEach(function (el) {
        el.textContent = usd ? el.dataset.usd : el.dataset.ars;
      });
    });
  });
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var svg = document.getElementById('fondoCierre');
  if (!svg) return;
  var NS = 'http://www.w3.org/2000/svg';
  var az = function (n) { return Math.random() * n; };

  // Patron 1: red neuronal (nodos + conexiones)
  var gRed = document.createElementNS(NS, 'g');
  var nodos = [];
  for (var i = 0; i < 34; i++) nodos.push([az(800), az(420)]);
  nodos.forEach(function (a, i) {
    nodos.forEach(function (b, j2) {
      if (j2 <= i) return;
      var d = Math.hypot(a[0] - b[0], a[1] - b[1]);
      if (d < 120 && Math.random() > .5) {
        var l = document.createElementNS(NS, 'path');
        l.setAttribute('d', 'M' + a[0] + ',' + a[1] + ' L' + b[0] + ',' + b[1]);
        l.setAttribute('class', 'traza'); l.setAttribute('stroke-width', '.6');
        l.style.animationDelay = az(8) + 's';
        gRed.appendChild(l);
      }
    });
    var c = document.createElementNS(NS, 'circle');
    c.setAttribute('cx', a[0]); c.setAttribute('cy', a[1]); c.setAttribute('r', '2');
    c.setAttribute('class', 'nodo'); c.style.animationDelay = az(4) + 's';
    gRed.appendChild(c);
  });

  // Patron 2: ondas fluidas
  var gOndas = document.createElementNS(NS, 'g');
  for (var o = 0; o < 10; o++) {
    var y = 40 + o * 40, amp = 34 + o * 6;
    var w = document.createElementNS(NS, 'path');
    w.setAttribute('d', 'M-60,' + y + ' Q200,' + (y - amp) + ' 400,' + y + ' T860,' + y);
    w.setAttribute('class', 'traza'); w.setAttribute('stroke-width', 1 + o * .18);
    w.style.animationDelay = (o * .7) + 's';
    gOndas.appendChild(w);
  }

  // Patron 3: cuadricula geometrica
  var gGeo = document.createElementNS(NS, 'g');
  for (var x = 0; x < 20; x++) for (var yy = 0; yy < 10; yy++) {
    if (Math.random() > .78) {
      var t = 42, r = document.createElementNS(NS, 'path');
      r.setAttribute('d', 'M' + (x * t) + ',' + (yy * t) + ' h' + t + ' v' + t + ' h-' + t + ' Z');
      r.setAttribute('class', 'traza'); r.setAttribute('stroke-width', '.8');
      r.style.animationDelay = az(6) + 's';
      gGeo.appendChild(r);
    }
  }

  var patrones = [gRed, gOndas, gGeo];
  patrones.forEach(function (g) { svg.appendChild(g); });
  var dots = document.querySelectorAll('.patron-dots i');
  var actual = 0;
  patrones[0].classList.add('activa');
  setInterval(function () {
    patrones[actual].classList.remove('activa');
    dots[actual].classList.remove('on');
    actual = (actual + 1) % patrones.length;
    patrones[actual].classList.add('activa');
    dots[actual].classList.add('on');
  }, 10000);

  // Titulo letra por letra al entrar en pantalla (despues de la traduccion)
  var h2 = document.getElementById('tituloCierre');
  var texto = h2.textContent;
  h2.textContent = '';
  texto.split('').forEach(function (ch, idx) {
    var sp = document.createElement('span');
    sp.className = 'letra';
    sp.textContent = ch === ' ' ? '\u00A0' : ch;
    sp.style.transitionDelay = (idx * 35) + 'ms';
    h2.appendChild(sp);
  });
  new IntersectionObserver(function (es, io) {
    es.forEach(function (e) {
      if (e.isIntersecting) { h2.classList.add('lista'); io.disconnect(); }
    });
  }, { rootMargin: '0px 0px -80px 0px' }).observe(h2);
});
</script>
</body>
</html>
