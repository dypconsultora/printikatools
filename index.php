<?php
/**
 * Portada de printikatools.com.
 *
 * Sirve los dos idiomas desde este mismo archivo: la raiz en castellano y
 * /en/ en ingles (ver comunidad/inc/idioma.php). El texto se escribe una sola
 * vez, en castellano; la version inglesa se arma con el diccionario.
 *
 * Mientras el sitio esta en acceso anticipado (COM_PREVIEW_ACTIVO en
 * comunidad/inc/bootstrap.php) muestra el "Proximamente"; con la clave de
 * acceso se ve la landing real y se habilita el ingreso a la comunidad.
 */
require_once __DIR__ . '/comunidad/inc/bootstrap.php';
require_once __DIR__ . '/comunidad/inc/ui.php';
require_once __DIR__ . '/comunidad/inc/idioma.php';

$idi = landing_idioma();
$en  = $idi === 'en';

// El logo lleva el lema abajo a la derecha, y ese lema tambien va traducido.
$logo_oscuro = '/assets/img/printika-tools-dark' . ($en ? '-en' : '') . '.svg';
$logo_claro  = '/assets/img/printika-tools' . ($en ? '-en' : '') . '.svg';
$og_imagen   = 'https://printikatools.com/assets/img/og-printika' . ($en ? '-en' : '') . '.png';

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
    <img src="/assets/img/printika-tools-dark.svg" alt="Printika Tools" class="logo">
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
<?php exit; endif;

// A partir de aca se arma la pagina. En ingles el HTML pasa por el
// diccionario antes de salir (ver comunidad/inc/idioma.php).
ob_start();

$titulo = $en
    ? '3D Printing Cost Calculator and Quoting Software | Printika Tools'
    : 'Calculadora de costos y presupuestos para impresión 3D | Printika Tools';
$descripcion = $en
    ? 'Work out the real cost of every 3D print and build quotes in seconds. Free calculator in ARS, USD and EUR, plus clients, stock and sales for your workshop.'
    : 'Calculá el costo real de tus impresiones 3D y armá presupuestos en segundos. Calculadora gratis en ARS, USD y EUR, más clientes, stock y ventas para tu taller.';
$desc_corta = $en
    ? 'Work out the real cost of every 3D print and build quotes in seconds. Free, in ARS, USD and EUR.'
    : 'Calculá el costo real de tus impresiones 3D y armá presupuestos en segundos. Gratis, en ARS, USD y EUR.';
$og_alt = $en
    ? 'Printika Tools · 3D printing cost and quoting calculator'
    : 'Printika Tools · Calculadora de costos y presupuestos 3D';
?>
<!DOCTYPE html>
<html lang="<?php echo $idi; ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($titulo); ?></title>
  <meta name="description" content="<?php echo htmlspecialchars($descripcion); ?>">
  <link rel="canonical" href="<?php echo landing_url($idi); ?>">
  <link rel="alternate" hreflang="es" href="https://printikatools.com/">
  <link rel="alternate" hreflang="en" href="https://printikatools.com/en/">
  <link rel="alternate" hreflang="x-default" href="https://printikatools.com/">
  <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large">
  <meta name="author" content="Printika Tools">
  <meta name="theme-color" content="#0b0f17">
  <link rel="icon" type="image/svg+xml" href="/assets/img/favicon.svg">
  <link rel="icon" sizes="any" href="/favicon.ico">
  <link rel="apple-touch-icon" href="/assets/img/favicon-180.png">

  <meta property="og:type" content="website">
  <meta property="og:site_name" content="Printika Tools">
  <meta property="og:locale" content="<?php echo $en ? 'en_US' : 'es_AR'; ?>">
  <meta property="og:locale:alternate" content="<?php echo $en ? 'es_AR' : 'en_US'; ?>">
  <meta property="og:url" content="<?php echo landing_url($idi); ?>">
  <meta property="og:title" content="<?php echo htmlspecialchars($titulo); ?>">
  <meta property="og:description" content="<?php echo htmlspecialchars($descripcion); ?>">
  <meta property="og:image" content="<?php echo $og_imagen; ?>">
  <meta property="og:image:alt" content="<?php echo htmlspecialchars($og_alt); ?>">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="630">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?php echo htmlspecialchars($titulo); ?>">
  <meta name="twitter:description" content="<?php echo htmlspecialchars($desc_corta); ?>">
  <meta name="twitter:image" content="<?php echo $og_imagen; ?>">

<?php
  // Datos estructurados: lo mismo que ve el visitante, pero para Google y las IA.
  $faq = $en ? [
      ['How do I join the community?',
       'Create your free account with the Sign up button and then activate your subscription. You get access to every tool in minutes.'],
      ['Is billing monthly or yearly?',
       'Whichever you prefer: the monthly plan renews month to month with no lock-in, and the yearly plan gives you 2 months at no cost with the price locked for the whole year.'],
      ['Can I cancel anytime?',
       'Yes. If you cancel, you keep access until your subscription expires and you are never charged again.'],
      ['What does the free plan include?',
       'The full online cost calculator, the STL model library and the video and PDF resources, at no cost.'],
      ['Is my data saved?',
       'Yes. Every subscriber has their own account: your quotes, clients and stock are stored and available from any device.'],
      ['Will you add more tools?',
       'We ship improvements and new tools for the 3D printing workshop every month.'],
  ] : [
      ['¿Cómo me uno a la comunidad?',
       'Creás tu cuenta gratis con el botón Registrarse y después activás tu suscripción. En minutos tenés acceso a todas las herramientas.'],
      ['¿El pago es mensual o anual?',
       'Como prefieras: el plan mensual cuesta $18.000 y se renueva mes a mes sin permanencia; el plan anual cuesta $180.000, ahorrás $36.000 (2 meses gratis) y el precio queda congelado todo el año.'],
      ['¿Puedo cancelar cuando quiera?',
       'Sí. Si cancelás, mantenés el acceso hasta el vencimiento de tu suscripción y no se te cobra nada más.'],
      ['¿Qué incluye el plan gratuito?',
       'La calculadora de costos online completa, la librería de modelos STL y los recursos en videos y PDF, sin costo.'],
      ['¿Mis datos quedan guardados?',
       'Sí. Cada suscriptor tiene su propia cuenta: tus presupuestos, clientes y stock se guardan y podés consultarlos desde cualquier dispositivo.'],
      ['¿Van a agregar más herramientas?',
       'Todos los meses sumamos mejoras y herramientas nuevas para el taller de impresión 3D.'],
  ];

  $grafo = [
    [
      '@type' => 'Organization',
      '@id'   => 'https://printikatools.com/#organizacion',
      'name'  => 'Printika Tools',
      'url'   => 'https://printikatools.com/',
      'logo'  => 'https://printikatools.com' . $logo_oscuro,
      'description' => $en
        ? 'Community and management tools for 3D printing workshops.'
        : 'Comunidad y herramientas de gestión para talleres de impresión 3D en español.',
      'areaServed' => 'AR',
      'sameAs' => ['https://printika3d.com', 'https://t.me/+N5f7IcWPXihhMWQx'],
    ],
    [
      '@type' => 'WebSite',
      '@id'   => 'https://printikatools.com/#sitio',
      'url'   => 'https://printikatools.com/',
      'name'  => 'Printika Tools',
      'inLanguage' => $en ? 'en' : 'es-AR',
      'publisher'  => ['@id' => 'https://printikatools.com/#organizacion'],
    ],
    [
      '@type' => 'SoftwareApplication',
      '@id'   => 'https://printikatools.com/#app',
      'name'  => 'Printika Tools',
      'applicationCategory'    => 'BusinessApplication',
      'applicationSubCategory' => $en ? '3D printing cost calculator' : 'Calculadora de costos de impresión 3D',
      'operatingSystem' => 'Web',
      'url' => landing_url($idi),
      'inLanguage' => ['es-AR', 'en'],
      'description' => $en
        ? '3D printing cost calculator, quotes, clients, filament stock, sales and statistics for 3D printing workshops.'
        : 'Calculadora de costos de impresión 3D, presupuestos, clientes, stock de filamento, ventas y estadísticas para talleres de impresión 3D.',
      'featureList' => $en ? [
        '3D printing cost calculator in ARS, USD and EUR',
        'Professional PDF quotes',
        'Client management',
        'Filament stock with automatic deduction',
        'Workshop sales and statistics',
        'STL model library',
      ] : [
        'Calculadora de costos de impresión 3D en ARS, USD y EUR',
        'Presupuestos profesionales en PDF',
        'Gestión de clientes',
        'Stock de filamento con descuento automático',
        'Ventas y estadísticas del taller',
        'Librería de modelos STL',
      ],
      'publisher' => ['@id' => 'https://printikatools.com/#organizacion'],
      'offers' => [
        ['@type' => 'Offer', 'name' => 'Printika Free', 'price' => '0', 'priceCurrency' => 'ARS',
         'description' => $en ? 'Cost calculator, STL library and video and PDF resources.'
                              : 'Calculadora de costos, librería STL y recursos en videos y PDF.'],
        ['@type' => 'Offer', 'name' => 'Printika Pro', 'price' => '18000', 'priceCurrency' => 'ARS',
         'description' => $en ? 'Every workshop tool, renewed monthly.'
                              : 'Todas las herramientas del taller, renovación mensual.'],
        ['@type' => 'Offer', 'name' => $en ? 'Printika Pro Annual' : 'Printika Pro Anual', 'price' => '180000', 'priceCurrency' => 'ARS',
         'description' => $en ? 'Every workshop tool with 2 months at no cost.'
                              : 'Todas las herramientas del taller con 2 meses sin cargo.'],
      ],
    ],
    [
      '@type' => 'FAQPage',
      '@id'   => landing_url($idi) . '#faq',
      'inLanguage' => $en ? 'en' : 'es-AR',
      'mainEntity' => array_map(fn($f) => [
          '@type' => 'Question',
          'name'  => $f[0],
          'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]],
      ], $faq),
    ],
  ];
  ?>
  <script type="application/ld+json">
<?php echo json_encode(['@context' => 'https://schema.org', '@graph' => $grafo],
      JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT); ?>
  </script>
  <script>(function(){if(localStorage.getItem('ptools_tema')==='light'){document.documentElement.setAttribute('data-theme','light');}})();
  function ptTema(t){document.documentElement.setAttribute('data-theme',t==='light'?'light':'dark');localStorage.setItem('ptools_tema',t);}</script>
  <link rel="preload" href="/assets/fonts/Inter-400-latin.woff2" as="font" type="font/woff2" crossorigin>
  <link rel="preload" href="/assets/fonts/SpaceGrotesk-700-latin.woff2" as="font" type="font/woff2" crossorigin>
  <link rel="stylesheet" href="/assets/fonts/fuentes.css">
  <style>.idioma button.activo{opacity:1 !important;background:var(--surface,rgba(255,255,255,.12)) !important}</style>
  <script src="/assets/js/lib/gsap.min.js" defer></script>
  <script src="/assets/js/lib/ScrollTrigger.min.js" defer></script>
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
    /* Latido suave de los puntitos de estado: la pagina no queda muerta */
    .insignia .punto,.foto-taller .flotante .punto{animation:latido 2.6s ease-in-out infinite}
    @keyframes latido{0%,100%{transform:scale(1);box-shadow:0 0 0 0 rgba(45,183,250,.35)}
      50%{transform:scale(1.12);box-shadow:0 0 0 5px rgba(45,183,250,0)}}
    @media (prefers-reduced-motion: reduce){ .h1-serena .palabra{opacity:1}
      .linea-fx,.punto-fx,.flota-fx,.insignia .punto,.foto-taller .flotante .punto{animation:none} }
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
    .cont{max-width:1560px;margin:0 auto;padding:0 clamp(28px,5.5vw,88px)}
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
    .nav .cont{max-width:none;padding:0 clamp(28px,5.5vw,88px);display:flex;align-items:center;
         justify-content:space-between;height:104px;gap:14px}
    .nav .marca img{height:66px;width:auto;display:block}
    .nav nav{display:flex;align-items:center;gap:20px}
    .nav nav a{color:var(--txt-2);font-family:var(--titulos);font-size:17px;font-weight:500;
         letter-spacing:.005em;white-space:nowrap;transition:color .15s ease}
    .nav nav a:hover{color:var(--txt)}
    .nav .btn{height:42px;padding:0 20px;font-size:15.5px;font-family:var(--titulos);font-weight:600}
    .nav nav a.btn{color:var(--accent-ink)}
    .nav nav a.btn:hover{color:var(--accent-ink)}
    .nav nav a.entrar{color:var(--txt);font-weight:500}
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

    /* Aurora: manchas de color que se mueven despacio detras del hero.
       El desenfoque se aplica una vez y despues solo se mueven con transform,
       que es lo unico que la placa de video hace gratis. */
    .aurora{position:absolute;inset:-20%;pointer-events:none;z-index:0;filter:blur(38px)}
    .aurora i{position:absolute;display:block;border-radius:50%;will-change:transform;mix-blend-mode:screen}
    .aurora i:nth-child(1){width:52vw;height:52vw;left:-8%;top:-16%;
        background:radial-gradient(circle,rgba(45,183,250,1),rgba(45,183,250,.3) 45%,transparent 72%)}
    .aurora i:nth-child(2){width:44vw;height:44vw;right:-6%;top:2%;
        background:radial-gradient(circle,rgba(111,124,245,.9),rgba(111,124,245,.26) 45%,transparent 72%)}
    .aurora i:nth-child(3){width:40vw;height:40vw;left:26%;bottom:-14%;
        background:radial-gradient(circle,rgba(45,183,250,.6),rgba(111,124,245,.18) 45%,transparent 72%)}
    [data-theme="light"] .aurora{opacity:.4}
    [data-theme="light"] .aurora i{mix-blend-mode:multiply}
    .hero .cont,.hero-visual{position:relative;z-index:1}

    /* Titulos que suben desde atras de una linea invisible */
    .palabra-mask{display:inline-block;overflow:hidden;vertical-align:top;padding-bottom:.08em}
    .palabra-mask > i{display:inline-block;font-style:inherit;will-change:transform}

    /* Perspectiva para que las tarjetas entren girando */
    .bento,.planes-grilla,.beneficios{perspective:1400px}
    .caja,.plan,.beneficio,.faq details{will-change:transform,opacity}

    /* Cuanto llevas leido de la pagina */
    #progreso{position:fixed;top:0;left:0;height:2px;width:100%;transform:scaleX(0);transform-origin:0 50%;
        background:linear-gradient(90deg,var(--accent),#6f7cf5);z-index:120;pointer-events:none}

    /* La foto del hero se inclina siguiendo al mouse */
    .hero-visual{perspective:1100px}
    .hero-visual .marco{transform-style:preserve-3d;will-change:transform}
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
    .stats{display:flex;flex-wrap:nowrap;border-top:1px solid var(--bd-suave);padding-top:28px}
    /* Cada columna se reparte el ancho y su contenido va centrado */
    .stat{flex:1 1 0;padding:0 18px;border-left:1px solid var(--bd-suave);min-width:0;
        text-align:center}
    .stat:first-child{border-left:none}
    .stat b{display:block;font-family:var(--titulos);font-size:34px;font-weight:700;
        letter-spacing:-.02em;line-height:1.1;color:var(--txt)}
    .stat span{display:block;margin-top:6px;font-size:14.5px;line-height:1.35;color:var(--txt-3)}
    .hero-visual{position:relative}
    .hero-visual::before{content:'';position:absolute;inset:-10%;pointer-events:none;
        background:radial-gradient(50% 50% at 50% 50%, rgba(45,183,250,.18), transparent 70%)}
    .hero-visual img{position:relative;width:100%;height:auto;display:block}
    /* El marco es el que lleva el borde y la sombra; la foto se mueve adentro
       (por eso va un poco mas grande y con overflow oculto). */
    .marco{position:relative;border-radius:18px;overflow:hidden;
        border:1px solid var(--raised);box-shadow:var(--sombra-img)}
    .marco img{border-radius:0;border:0;box-shadow:none}

    /* ---- Bento herramientas ---- */
    .bento{display:grid;grid-template-columns:repeat(3,1fr);gap:14px}
    .caja{background:var(--surface);border:1px solid var(--bd-suave);border-radius:var(--radio-g);
        padding:26px;position:relative;overflow:hidden;
        transition:border-color .25s ease,transform .25s cubic-bezier(.16,1,.3,1),box-shadow .25s ease}
    .caja:hover{border-color:var(--bd);transform:translateY(-3px);
        box-shadow:0 18px 40px -22px rgba(10,20,35,.55)}
    /* La luz sigue al cursor dentro de cada tarjeta (--mx/--my las escribe el JS) */
    .caja::before{content:'';position:absolute;inset:0;pointer-events:none;opacity:0;
        transition:opacity .3s ease;
        background:radial-gradient(320px circle at var(--mx,50%) var(--my,50%),
                   rgba(45,183,250,.09), transparent 65%)}
    .caja:hover::before{opacity:1}
    .caja > *{position:relative}
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
    .foto-taller .marco img{width:100%;height:100%;object-fit:cover}
    .foto-taller img{width:100%;height:auto;display:block}
    .foto-taller .flotante{position:absolute;right:-14px;top:26px;display:flex;align-items:center;
        gap:10px;background:var(--surface);border:1px solid var(--bd);border-radius:12px;
        padding:11px 15px;box-shadow:0 12px 40px -12px rgba(0,0,0,.6)}
    .foto-taller .flotante .punto{width:8px;height:8px;border-radius:99px;background:var(--ok);
        box-shadow:0 0 8px rgba(62,207,142,.8)}
    .foto-taller .flotante span{font-size:12.5px;font-weight:600}
    /* Los beneficios entran desde la derecha: hasta que les toca estan
       corridos y asomarian fuera de la pantalla. */
    .comunidad{overflow-x:hidden}
    .beneficios{display:grid;grid-template-columns:1fr 1fr;gap:16px;align-content:stretch;height:100%}
    .beneficio{display:flex;gap:14px;align-items:flex-start;background:var(--vidrio);
        border:1px solid var(--bd-suave);border-radius:var(--radio-g);padding:22px;
        transition:border-color .15s ease,background-color .15s ease}
    .beneficio:hover{border-color:var(--bd);background:var(--surface)}
    .beneficio .ico{color:var(--accent);margin-top:2px}
    .beneficio h3{font-size:16.5px;font-weight:700;letter-spacing:-.01em}
    .beneficio p{font-size:14.5px;line-height:1.55;color:var(--txt-2);margin-top:5px}

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
    footer{border-top:1px solid var(--bd-suave);padding:0 0 32px;background:var(--fondo)}
    footer .cont{max-width:none;padding:0 clamp(28px,5.5vw,88px)}
    .footer-grilla{display:grid;grid-template-columns:1.6fr 1fr 1fr 1fr;gap:40px;
      padding:52px 0 40px}
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


    /* El margen ancho es para monitores grandes. Entre 1100 y 1400 el
       encabezado no entra: ahi se achica un poco para que no se corte. */
    @media (max-width:1400px){
      /* Se achican los TRES juntos: si solo se achica el encabezado, el logo
         deja de alinear con el titulo de abajo. */
      .cont,.nav .cont,footer .cont{padding:0 clamp(28px,4vw,56px)}
      .nav .marca img{height:58px}
      .nav nav{gap:12px}
      /* A 17px el menu entra por 4px: demasiado justo. En este rango baja
         un punto y quedan 30 de sobra, sin sacar ningun enlace. */
      .nav nav a{font-size:16px}
    }
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
      .nav .cont, footer .cont{padding:0 20px}   /* en el celular, igual que el resto */
      .nav nav a.link-seccion{display:none}
      .hero{padding:56px 0 44px}
      section{padding:56px 0}
      .bento{grid-template-columns:1fr}
      .caja.grande{grid-column:auto}
      .beneficios{grid-template-columns:1fr}
      /* El encabezado no entraba en el celular: se va lo prescindible */
      .nav .marca img{height:38px}
      .nav .cont{gap:10px}
      .nav nav{gap:8px}
      .nav .idioma-tit{display:none}
      /* El boton crecio con el menu nuevo y ya no entraba a 375 */
      .nav .btn{height:36px;padding:0 14px;font-size:13.5px}
      .nav .tema .tema-btn{display:none}
      .nav .entrar{display:none}
      .stat{padding:0 10px}
      .stat b{font-size:24px}
      .stat span{font-size:12.5px}
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
    <img src="<?php echo $logo_oscuro; ?>" alt="" width="300" height="96" aria-hidden="true">
    <div class="num">0%</div>
    <div class="barra"><i></i></div>
  </div>
  <div id="progreso" aria-hidden="true"></div>
  <header class="nav">
    <div class="cont">
      <a class="marca" href="/">
        <img class="logo-oscuro" src="<?php echo $logo_oscuro; ?>" alt="Printika Tools">
        <img class="logo-claro" src="<?php echo $logo_claro; ?>" alt="Printika Tools">
      </a>
      <nav>
        <a class="link-seccion" href="#herramientas">Herramientas</a>
        <a class="link-seccion" href="#comunidad">Comunidad</a>
        <a class="link-seccion" href="#planes">Precios</a>
        <a class="link-seccion" href="/guias/">Guías</a>
        <a class="link-seccion" href="#faq">FAQ</a>
        <a class="link-seccion" href="/comunidad/cotizador/" target="_blank" rel="noopener">Calculadora</a>
        <span class="tema" role="group" aria-label="Tema de la página">
          <span class="idioma" role="group" aria-label="Idioma / Language" style="display:inline-flex;align-items:center;gap:2px;background:var(--surface-2,rgba(255,255,255,.06));border:1px solid var(--bd,rgba(255,255,255,.12));border-radius:999px;padding:2px;margin-right:10px">
            <span class="idioma-tit" style="font-size:10px;font-weight:600;letter-spacing:.08em;color:var(--txt-3,#8a95a8);padding:0 6px 0 10px">IDIOMA</span>
<?php
            // Son enlaces, no botones: cada idioma tiene su propia direccion y
            // asi Google entiende que son dos versiones de la misma pagina.
            $est = 'display:inline-block;text-decoration:none;border-radius:999px;padding:3px 10px;'
                 . 'font-family:inherit;font-size:11px;font-weight:700;color:inherit;';
            $act = 'opacity:1;background:var(--surface,rgba(255,255,255,.12))';
            ?>
            <a href="/" hreflang="es" style="<?php echo $est . ($en ? 'opacity:.55' : $act); ?>"
               <?php if (!$en) echo 'aria-current="true"'; ?>>ESP</a>
            <a href="/en/" hreflang="en" style="<?php echo $est . ($en ? $act : 'opacity:.55'); ?>"
               <?php if ($en) echo 'aria-current="true"'; ?>>ENG</a>
          </span>
          <button type="button" class="tema-btn" data-tema="light" onclick="ptTema('light')"
                  title="Modo día" aria-label="Modo día"><?php echo ui_icono('sol', 15); ?></button>
          <button type="button" class="tema-btn" data-tema="dark" onclick="ptTema('dark')"
                  title="Modo noche" aria-label="Modo noche"><?php echo ui_icono('luna', 15); ?></button>
        </span>
        <a class="entrar" href="/comunidad/login.php">Iniciar sesión</a>
        <a class="btn" href="#planes">Registrarse</a>
      </nav>
    </div>
  </header>

  <main>
    <div class="hero" style="position:relative;overflow:hidden">
      <div class="aurora" aria-hidden="true"><i></i><i></i><i></i></div>
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
          <h1 class="h1-serena"><?php echo landing_hero_h1(); ?></h1>
          <p class="sub">Calculadora de costos, presupuestos, clientes y stock de materiales.
             Las herramientas de una comunidad de makers, en un mismo lugar.</p>
          <div class="ctas">
            <a class="btn" href="/comunidad/registro.php?plan=gratis">Comenzar gratis</a>
            <a class="btn sec" href="#planes">Ver planes</a>
          </div>
          <div class="stats">
            <div class="stat"><b>+13</b><span>materiales soportados</span></div>
            <div class="stat"><b>3</b><span>monedas · ARS USD EUR</span></div>
            <div class="stat"><b>+6</b><span>herramientas en camino</span></div>
          </div>
        </div>
        <div class="hero-visual">
          <div class="marco"><img src="/assets/img/landing/hero-impresora.webp" alt="Impresora 3D imprimiendo una pieza en un taller" decoding="async" fetchpriority="high"
               width="1376" height="768"></div>
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
            <h3>Presupuestos <span class="chip activo">Disponible</span></h3>
            <p>Generá presupuestos profesionales, guardalos y marcá los vendidos.</p>
          </div>
          <div class="caja">
            <span class="ico-caja"><?php echo ui_icono('clientes', 20); ?></span>
            <h3>Clientes <span class="chip activo">Disponible</span></h3>
            <p>Tu cartera de clientes con su historial de trabajos.</p>
          </div>
          <div class="caja">
            <span class="ico-caja"><?php echo ui_icono('stock', 20); ?></span>
            <h3>Stock <span class="chip activo">Disponible</span></h3>
            <p>Rollos e insumos controlados, con descuento automático al vender.</p>
          </div>
          <div class="caja">
            <span class="ico-caja"><?php echo ui_icono('libreria', 20); ?></span>
            <h3>Librería STL <span class="chip activo">Disponible</span></h3>
            <p>Modelos listos para imprimir, exclusivos para suscriptores.</p>
          </div>
          <div class="caja">
            <span class="ico-caja"><?php echo ui_icono('estadisticas', 20); ?></span>
            <h3>Estadísticas <span class="chip activo">Disponible</span></h3>
            <p>Cuánto imprimís, vendés y ganás, mes a mes.</p>
          </div>
        </div>
        <div style="text-align:center;margin-top:36px">
          <a class="btn sec" href="/comunidad/cotizador/">Probar la calculadora</a>
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
            <div class="marco"><img src="/assets/img/landing/taller-maker.webp" alt="Taller de impresión 3D con piezas impresas y rollos de filamento" decoding="async"
                 width="1376" height="768" loading="lazy"></div>
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
<?php
            // La landing en ingles arranca en dolares, que es como paga ese publico.
            $estMon = 'background:none;border:none;border-radius:999px;padding:5px 14px;font-family:inherit;'
                    . 'font-size:12px;font-weight:700;color:inherit;cursor:pointer;opacity:.55';
            ?>
            <button type="button" data-mon="ars" class="<?php echo $en ? '' : 'activo'; ?>" style="<?php echo $estMon; ?>">ARS</button>
            <button type="button" data-mon="usd" class="<?php echo $en ? 'activo' : ''; ?>" style="<?php echo $estMon; ?>">USD</button>
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
              <li><?php echo ui_icono('check', 15); ?>Recursos en videos y PDF</li>
            </ul>
            <a class="btn sec" href="/comunidad/registro.php?plan=gratis">Empezar gratis</a>
          </div>
          <div class="plan">
            <h3>Printika Pro</h3>
            <p class="precio"><span class="monto" data-ars="$18.000" data-usd="US$15"><?php echo $en ? 'US$15' : '$18.000'; ?></span> <small>/mes</small></p>
            <p class="nota">Renovación mes a mes, sin permanencia</p>
            <ul>
              <li><?php echo ui_icono('check', 15); ?>Calculadora completa (versión PRO)</li>
              <li><?php echo ui_icono('check', 15); ?>Mi Taller: presupuestos, clientes y stock</li>
              <li><?php echo ui_icono('check', 15); ?>Librería STL y estadísticas</li>
              <li><?php echo ui_icono('check', 15); ?>Recursos en videos y PDF</li>
              <li><?php echo ui_icono('check', 15); ?>Tus datos guardados en tu cuenta</li>
              <li><?php echo ui_icono('check', 15); ?>Soporte técnico prioritario</li>
              <li><?php echo ui_icono('check', 15); ?>Herramientas nuevas cada mes</li>
            </ul>
            <a class="btn sec btn-pago" target="_blank" rel="noopener"
               data-mp="https://mpago.la/118mn81" data-pp="https://www.paypal.com/CAMBIAR-mensual"
               href="<?php echo $en ? 'https://www.paypal.com/CAMBIAR-mensual' : 'https://mpago.la/118mn81'; ?>">Suscribirme</a>
          </div>
          <div class="plan destacado">
            <span class="etiqueta swap-mon" data-ars="2 meses gratis" data-usd="2 meses gratis">2 meses gratis</span>
            <h3>Printika Pro Anual</h3>
            <p class="precio"><span class="monto" data-ars="$180.000" data-usd="US$150"><?php echo $en ? 'US$150' : '$180.000'; ?></span> <small>/año</small></p>
            <span class="ahorro" data-ars="Equivale a $15.000 por mes · ahorrás $36.000" data-usd="Equivale a US$12,50 por mes · ahorrás US$30"><?php echo $en ? 'Equivale a US$12,50 por mes · ahorrás US$30' : 'Equivale a $15.000 por mes · ahorrás $36.000'; ?></span>
            <p class="nota" style="margin-top:12px">Un solo pago y te olvidás todo el año</p>
            <ul>
              <li><?php echo ui_icono('check', 15); ?>Todo lo del plan mensual</li>
              <li><?php echo ui_icono('check', 15); ?><span class="swap-mon" data-ars="2 meses sin cargo ($36.000 de ahorro)" data-usd="2 meses sin cargo (US$30 de ahorro)"><?php echo $en ? '2 meses sin cargo (US$30 de ahorro)' : '2 meses sin cargo ($36.000 de ahorro)'; ?></span></li>
              <li><?php echo ui_icono('check', 15); ?>Precio congelado por 12 meses</li>
              <li><?php echo ui_icono('check', 15); ?>Recursos en videos y PDF</li>
              <li><?php echo ui_icono('check', 15); ?>Acceso anticipado a herramientas nuevas</li>
            </ul>
            <a class="btn btn-pago" target="_blank" rel="noopener"
               data-mp="https://mpago.la/1vNcghS" data-pp="https://www.paypal.com/CAMBIAR-anual"
               href="<?php echo $en ? 'https://www.paypal.com/CAMBIAR-anual' : 'https://mpago.la/1vNcghS'; ?>">Suscribirme</a>
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
            <p class="resp">Creás tu cuenta gratis con el botón "Registrarse" y después activás tu suscripción.
            En minutos tenés acceso a todas las herramientas.</p>
          </details>
          <details>
            <summary>¿El pago es mensual o anual?</summary>
            <p class="resp">Como prefieras: el plan mensual cuesta $18.000 y se renueva mes a mes sin permanencia,
            y el plan anual cuesta $180.000 — ahorrás $36.000 (2 meses gratis) y el precio queda congelado todo el año.</p>
          </details>
          <details>
            <summary>¿Puedo cancelar cuando quiera?</summary>
            <p class="resp">Sí. Si cancelás, mantenés el acceso hasta el vencimiento de tu suscripción
            y no se te cobra nada más.</p>
          </details>
          <details>
            <summary>¿Qué incluye el plan gratuito?</summary>
            <p class="resp">La calculadora de costos online completa, la librería de modelos STL
            y los recursos en videos y PDF, sin costo.</p>
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
          <a class="cta-btn" href="/comunidad/registro.php?plan=gratis">Crear mi cuenta
            <span class="flecha-cta">→</span></a>
        </span>
      </div>
    </section>
  </main>

  <footer>
    <div class="cont">
      <div class="footer-grilla">
        <div class="footer-marca">
          <img class="logo-oscuro" src="<?php echo $logo_oscuro; ?>" alt="Printika Tools">
          <img class="logo-claro" src="<?php echo $logo_claro; ?>" alt="Printika Tools">
          <p class="desc">Las herramientas y la comunidad para manejar tu taller de impresión 3D como un negocio.</p>
          <a class="btn sec footer-cta" href="/comunidad/registro.php?plan=gratis">Comenzar gratis</a>
        </div>
        <div>
          <h4>Plataforma</h4>
          <ul>
            <li><a href="/comunidad/cotizador/">Calculadora</a></li>
            <li><a href="/guias/">Guías</a></li>
            <li><a href="#herramientas">Herramientas</a></li>
            <li><a href="#planes">Precios</a></li>
            <li><a href="#faq">FAQ</a></li>
          </ul>
        </div>
        <div>
          <h4>Tu cuenta</h4>
          <ul>
            <li><a href="/comunidad/login.php">Iniciar sesión</a></li>
            <li><a href="/comunidad/registro.php?plan=gratis">Registrarse</a></li>
            <li><a href="/comunidad/suscripcion.php">Planes</a></li>
          </ul>
        </div>
        <div>
          <h4>Comunidad</h4>
          <ul>
            <li><a href="https://t.me/+N5f7IcWPXihhMWQx" target="_blank" rel="noopener">Telegram</a></li>
            <li><a href="https://printika3d.com" target="_blank" rel="noopener">Printika 3D</a></li>
          </ul>
        </div>
      </div>
      <div class="footer-pie">
        <p>© <?php echo date('Y'); ?> Printika Tools. <?php echo t('Todos los derechos reservados.'); ?></p>
        <p><?php echo t('Hecho con impresoras 3D en Argentina'); ?> · <span><?php echo t('Actualizado el'); ?> <time datetime="<?php echo date('Y-m-d'); ?>"><?php echo date($en ? 'm/d/Y' : 'd/m/Y'); ?></time></span></p>
      </div>
    </div>
  </footer>
<script>
document.addEventListener('DOMContentLoaded', function () {
  if (window.ScrollTrigger) gsap.registerPlugin(ScrollTrigger);
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
  .add(function () {
    cargador.remove();
    // El alto de la pagina cambio: hay que recalcular donde arranca cada cosa
    if (window.ScrollTrigger) ScrollTrigger.refresh();
    // Y recien ahora se encienden las entradas: si se encendieran antes,
    // las secciones que ya estan a la vista se animarian detras del cargador.
    activarEntradas();
  }, '-=0.2')
  .to(heroEls, { opacity: 1, y: 0, duration: 0.7, ease: 'power3.out', stagger: 0.09 }, '-=0.35')
  .add(function () {
    document.querySelectorAll('.h1-serena .palabra').forEach(function (w) {
      setTimeout(function () { w.style.animation = 'palabra-aparece .8s ease-out forwards'; },
        parseInt(w.dataset.delay || 0, 10));
    });
  }, '-=0.55');

  // Las imagenes y las fuentes pueden cambiar el alto de la pagina despues
  // de todo esto: hay que volver a medir o las animaciones quedan corridas.
  if (window.ScrollTrigger) {
    window.addEventListener('load', function () { ScrollTrigger.refresh(); });
    if (document.fonts && document.fonts.ready) {
      document.fonts.ready.then(function () { ScrollTrigger.refresh(); });
    }
  }

  window.__tlCarga = tl;
  // Seguro: si la pestaña estuvo en segundo plano, terminar la carga igual
  setTimeout(function () { if (document.getElementById('cargador')) tl.progress(1); }, 7000);

  // ---- La aurora del hero nunca se queda quieta ----
  gsap.utils.toArray('.aurora i').forEach(function (b, i) {
    gsap.to(b, {
      xPercent: gsap.utils.random(-18, 18), yPercent: gsap.utils.random(-16, 16),
      scale: gsap.utils.random(0.85, 1.25),
      duration: gsap.utils.random(9, 14), ease: 'sine.inOut',
      repeat: -1, yoyo: true, delay: i * 0.8
    });
  });

  // ---- Barra de lectura, atada al scroll ----
  var barraLeer = document.getElementById('progreso');
  if (barraLeer && window.ScrollTrigger) {
    gsap.to(barraLeer, {
      scaleX: 1, ease: 'none',
      scrollTrigger: { trigger: document.body, start: 'top top', end: 'bottom bottom', scrub: 0.3 }
    });
  }

  // ---- Titulos: cada palabra sube desde atras de una linea ----
  function partirEnPalabras(el) {
    if (!el || el.dataset.partido) return [];
    var palabras = el.textContent.trim().split(/\s+/);
    el.textContent = '';
    var piezas = palabras.map(function (p, i) {
      var caja = document.createElement('span');
      caja.className = 'palabra-mask';
      var dentro = document.createElement('i');
      dentro.textContent = p;
      caja.appendChild(dentro);
      el.appendChild(caja);
      if (i < palabras.length - 1) el.appendChild(document.createTextNode(' '));
      return dentro;
    });
    el.dataset.partido = '1';
    return piezas;
  }

  /**
   * Cada seccion entra con su propia coreografia.
   *
   * Importante: el estado inicial se deja puesto de entrada (esconder), y
   * recien cuando la seccion asoma se anima hacia el estado final. Si se
   * hiciera al reves (from()), las tarjetas estarian visibles hasta que les
   * toca el turno y se veria un parpadeo.
   */
  var entradasPendientes = [];

  /** Enciende los disparadores. Se llama cuando el cargador ya se fue. */
  function activarEntradas() {
    entradasPendientes.forEach(function (f) { f(); });
    entradasPendientes = [];
  }

  function coreografia(selector, esconder, mostrar) {
    var el = document.querySelector(selector);
    if (!el) return;

    var titulo = el.querySelector('.cabeza h2');
    var palabras = partirEnPalabras(titulo);
    if (palabras.length) gsap.set(palabras, { yPercent: 115 });
    gsap.set(el.querySelectorAll('.cabeza .ceja, .cabeza p'), { opacity: 0, y: 18 });
    esconder(el);

    entradasPendientes.push(function () {
      var tl = gsap.timeline({
        defaults: { ease: 'power3.out' },
        scrollTrigger: { trigger: el, start: 'top 78%', once: true }
      });
      tl.to(el.querySelectorAll('.cabeza .ceja'), { opacity: 1, y: 0, duration: 0.5 }, 0)
        .to(el.querySelectorAll('.cabeza h2 .palabra-mask > i'),
            { yPercent: 0, duration: 0.95, ease: 'power4.out', stagger: 0.055 }, 0.05)
        .to(el.querySelectorAll('.cabeza p'), { opacity: 1, y: 0, duration: 0.6 }, 0.35);
      mostrar(tl, el);
    });
  }

  // Herramientas: las tarjetas llegan girando desde abajo
  coreografia('#herramientas',
    function (el) {
      gsap.set(el.querySelectorAll('.bento .caja'),
        { opacity: 0, y: 90, rotationX: -22, scale: 0.94, transformOrigin: '50% 100%' });
      gsap.set(el.querySelector('.cont > div:last-child'), { opacity: 0, y: 24 });
    },
    function (tl, el) {
      tl.to(el.querySelectorAll('.bento .caja'), {
        opacity: 1, y: 0, rotationX: 0, scale: 1, duration: 1, stagger: 0.09
      }, 0.3)
        .to(el.querySelector('.cont > div:last-child'), { opacity: 1, y: 0, duration: 0.6 }, '-=0.4');
    });

  // Comunidad: la foto entra desde la izquierda y los beneficios desde la derecha
  coreografia('.comunidad',
    function (el) {
      gsap.set(el.querySelector('.foto-taller'), { opacity: 0, x: -70, rotationY: 10 });
      gsap.set(el.querySelectorAll('.beneficio'), { opacity: 0, x: 60 });
      gsap.set(el.querySelector('.foto-taller .flotante'), { opacity: 0, scale: 0.6 });
    },
    function (tl, el) {
      tl.to(el.querySelector('.foto-taller'), { opacity: 1, x: 0, rotationY: 0, duration: 1.05 }, 0.25)
        .to(el.querySelectorAll('.beneficio'), { opacity: 1, x: 0, duration: 0.75, stagger: 0.08 }, 0.4)
        .to(el.querySelector('.foto-taller .flotante'),
            { opacity: 1, scale: 1, duration: 0.6, ease: 'back.out(2)' }, 0.9);
    });

  // Planes: rebotan al llegar, desde el centro hacia los costados
  coreografia('#planes',
    function (el) {
      gsap.set(el.querySelector('.moneda-sel'), { opacity: 0, y: 18 });
      gsap.set(el.querySelectorAll('.plan'), { opacity: 0, y: 80, scale: 0.9 });
    },
    function (tl, el) {
      tl.to(el.querySelector('.moneda-sel'), { opacity: 1, y: 0, duration: 0.5 }, 0.3)
        .to(el.querySelectorAll('.plan'), {
          opacity: 1, y: 0, scale: 1, duration: 0.9, ease: 'back.out(1.5)',
          stagger: { each: 0.11, from: 'center' }
        }, 0.4);
    });

  // FAQ: las preguntas entran una atras de otra desde la izquierda
  coreografia('#faq',
    function (el) { gsap.set(el.querySelectorAll('details'), { opacity: 0, x: -45 }); },
    function (tl, el) {
      tl.to(el.querySelectorAll('details'), { opacity: 1, x: 0, duration: 0.6, stagger: 0.07 }, 0.3);
    });

  // Cierre: el bloque final crece desde el centro
  (function () {
    var el = document.querySelector('.cierre .cont');
    if (!el) return;
    gsap.set(el, { opacity: 0, y: 50, scale: 0.96 });
    entradasPendientes.push(function () {
      gsap.to(el, {
        opacity: 1, y: 0, scale: 1, duration: 1, ease: 'power3.out',
        scrollTrigger: { trigger: el, start: 'top 82%', once: true }
      });
    });
  })();

  // ---- Los numeros del hero cuentan hasta su valor ----
  gsap.utils.toArray('.hero .stat b').forEach(function (b) {
    var texto = b.textContent.trim();
    var destino = parseInt(texto.replace(/\D/g, ''), 10);
    if (!destino) return;
    var prefijo = texto.replace(/[\d].*/, '');
    var n = { v: 0 };
    tl.to(n, {
      v: destino, duration: 1.1, ease: 'power2.out',
      onUpdate: function () { b.textContent = prefijo + Math.round(n.v); }
    }, '-=0.5');
  });

  // ---- Movimientos que dependen del scroll ----
  // Un solo bucle por cuadro, leyendo la posicion una vez y moviendo solo con
  // transform: no se engancha al evento scroll ni fuerza recalculos.
  var mm = gsap.matchMedia();

  mm.add('(prefers-reduced-motion: no-preference)', function () {
    if (!window.ScrollTrigger) return;

    // Las fotos se desplazan dentro de su marco siguiendo el scroll.
    // Van un poco mas grandes para que al moverse no se vea el borde.
    gsap.utils.toArray('.marco img').forEach(function (img) {
      gsap.set(img, { scale: 1.14 });
      gsap.fromTo(img, { yPercent: -5 }, {
        yPercent: 5, ease: 'none',
        scrollTrigger: { trigger: img.closest('.marco'), start: 'top bottom', end: 'bottom top',
                         scrub: 0.6, invalidateOnRefresh: true }
      });
    });

    // El hero se despide: mientras te vas, el texto sube y se desvanece
    // y la foto se achica un poco. Da sensacion de profundidad al salir.
    //
    // Arranca en 'top top' a proposito: asi, arriba de todo, el avance es
    // cero por definicion y el hero NUNCA puede verse desvanecido al entrar,
    // aunque las medidas se calculen mal por una fuente o una imagen que
    // tardo. El grueso del desvanecido se corre al final con el ease.
    var salida = {
      trigger: '.hero', start: 'top top', end: 'bottom top',
      scrub: 0.5, invalidateOnRefresh: true
    };
    var heroTexto = document.querySelector('.hero .cont > div:first-child');
    // Ojo: la salida se aplica al MARCO, no a .hero-visual. Ese lo anima la
    // entrada de la carga, y si los dos tocan la opacidad el que se crea
    // primero se queda con el valor de arranque y la foto no aparece nunca.
    var heroFoto = document.querySelector('.hero-visual .marco');
    if (heroTexto) {
      gsap.to(heroTexto, { yPercent: -18, opacity: 0.15, ease: 'power2.in', scrollTrigger: salida });
    }
    if (heroFoto) {
      gsap.to(heroFoto, { scale: 0.88, opacity: 0.2, ease: 'power2.in', scrollTrigger: salida });
    }

    // La aurora se corre despacio: el fondo no viaja a la misma velocidad
    // que el contenido, que es lo que da la sensacion de que hay capas.
    gsap.to('.aurora', {
      yPercent: 22, ease: 'none',
      scrollTrigger: { trigger: '.hero', start: 'top top', end: 'bottom top',
                       scrub: 0.8, invalidateOnRefresh: true }
    });
  });

  // ---- Cosas que solo tienen sentido con mouse (en el celular no van) ----
  mm.add('(hover: hover) and (pointer: fine)', function () {
    // La luz de cada tarjeta sigue al cursor
    var cajas = gsap.utils.toArray('.caja');
    function luz(e) {
      var c = e.currentTarget.getBoundingClientRect();
      e.currentTarget.style.setProperty('--mx', (e.clientX - c.left) + 'px');
      e.currentTarget.style.setProperty('--my', (e.clientY - c.top) + 'px');
    }
    cajas.forEach(function (c) { c.addEventListener('pointermove', luz); });

    // La foto del hero se inclina siguiendo al cursor
    var visual = document.querySelector('.hero-visual'), marco = visual && visual.querySelector('.marco');
    function inclinar(e) {
      var c = visual.getBoundingClientRect();
      gsap.to(marco, {
        rotationY: ((e.clientX - (c.left + c.width / 2)) / c.width) * 13,
        rotationX: -((e.clientY - (c.top + c.height / 2)) / c.height) * 11,
        duration: 0.6, ease: 'power3.out', transformPerspective: 1100
      });
    }
    function enderezar() {
      gsap.to(marco, { rotationY: 0, rotationX: 0, duration: 1.1, ease: 'elastic.out(1, 0.5)' });
    }
    if (marco) {
      visual.addEventListener('pointermove', inclinar);
      visual.addEventListener('pointerleave', enderezar);
    }

    // Los botones principales se acercan un poco al cursor
    var imanes = gsap.utils.toArray('.hero .ctas .btn, .cierre .btn');
    function acercar(e) {
      var b = e.currentTarget, c = b.getBoundingClientRect();
      gsap.to(b, {
        x: (e.clientX - (c.left + c.width / 2)) * 0.22,
        y: (e.clientY - (c.top + c.height / 2)) * 0.32,
        duration: 0.4, ease: 'power3.out'
      });
    }
    function soltar(e) {
      gsap.to(e.currentTarget, { x: 0, y: 0, duration: 0.6, ease: 'elastic.out(1, 0.45)' });
    }
    imanes.forEach(function (b) {
      b.addEventListener('pointermove', acercar);
      b.addEventListener('pointerleave', soltar);
    });

    return function () {
      if (marco) {
        visual.removeEventListener('pointermove', inclinar);
        visual.removeEventListener('pointerleave', enderezar);
        gsap.set(marco, { clearProps: 'transform' });
      }
      cajas.forEach(function (c) { c.removeEventListener('pointermove', luz); });
      imanes.forEach(function (b) {
        b.removeEventListener('pointermove', acercar);
        b.removeEventListener('pointerleave', soltar);
        gsap.set(b, { clearProps: 'transform' });
      });
    };
  });

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

  // Titulo letra por letra al entrar en pantalla. El texto ya viene en el
  // idioma que corresponde desde el servidor, asi que se parte tal cual esta.
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
<script>
// Idioma automatico segun el navegador.
//
// Solo la PRIMERA vez: apenas la persona toca ESP o ENG queda su eleccion
// guardada y no se la vuelve a mover. El salto se hace apenas se lee el
// documento, antes de pintar, para que no llegue a verse la pagina equivocada.
(function () {
  var elegido = null;
  try { elegido = localStorage.getItem('ptools_idioma'); } catch (e) {}
  if (elegido === 'es' || elegido === 'en') return;      // ya decidio: se respeta

  var hablaEspanol = (navigator.language || 'es').toLowerCase().indexOf('es') === 0;
  var estoyEnIngles = <?php echo $en ? 'true' : 'false'; ?>;

  if (!hablaEspanol && !estoyEnIngles) { location.replace('/en/' + location.hash); return; }
  if (hablaEspanol && estoyEnIngles)   { location.replace('/' + location.hash); }
})();

// Al elegir un idioma a mano queda guardado, y no se vuelve a saltar solo
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.idioma a[hreflang]').forEach(function (a) {
    a.addEventListener('click', function () {
      try { localStorage.setItem('ptools_idioma', a.getAttribute('hreflang')); } catch (e) {}
    });
  });
});
</script>
</body>
</html>
<?php
// Salida final: en ingles el HTML pasa por el diccionario. Todo lo que ve
// Google ya viene traducido del servidor, no depende de JavaScript.
echo landing_traducir(ob_get_clean());
