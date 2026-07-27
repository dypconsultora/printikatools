<?php
/**
 * Marco compartido de las guias: encabezado, pie y cabecera HTML.
 *
 * Las guias son las paginas por las que entra gente que todavia no conoce
 * Printika: alguien busca "cuanto cobrar una impresion 3D", cae aca, lee, y
 * termina en la calculadora. Por eso cada una vive en su propia direccion y
 * lleva sus datos estructurados.
 */
require_once dirname(__DIR__, 2) . '/comunidad/inc/bootstrap.php';
require_once dirname(__DIR__, 2) . '/comunidad/inc/ui.php';   // ui_icono()

/**
 * Abre la pagina.
 *
 * @param array $d titulo, descripcion, url (sin dominio), tipo ('articulo'|'listado'),
 *                 publicado y actualizado (Y-m-d), jsonld (array extra para el @graph)
 */
function guia_inicio(array $d) {
    $base   = 'https://printikatools.com';
    $url    = $base . $d['url'];
    $titulo = $d['titulo'];
    $desc   = $d['descripcion'];
    $logoOscuro = '/assets/img/printika-tools-dark.svg';
    $logoClaro  = '/assets/img/printika-tools.svg';

    $grafo = $d['jsonld'] ?? [];
    array_unshift($grafo, [
        '@type' => 'BreadcrumbList',
        'itemListElement' => array_map(function ($i, $m) {
            return ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $m[0], 'item' => $m[1]];
        }, array_keys($d['migas']), $d['migas']),
    ]);
    ?><!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($titulo); ?></title>
  <meta name="description" content="<?php echo htmlspecialchars($desc); ?>">
  <link rel="canonical" href="<?php echo $url; ?>">
  <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large">
  <meta name="theme-color" content="#0b0f17">
  <link rel="icon" type="image/svg+xml" href="/assets/img/favicon.svg">
  <link rel="icon" sizes="any" href="/favicon.ico">
  <link rel="apple-touch-icon" href="/assets/img/favicon-180.png">

  <meta property="og:type" content="<?php echo ($d['tipo'] ?? '') === 'articulo' ? 'article' : 'website'; ?>">
  <meta property="og:site_name" content="Printika Tools">
  <meta property="og:locale" content="es_AR">
  <meta property="og:url" content="<?php echo $url; ?>">
  <meta property="og:title" content="<?php echo htmlspecialchars($titulo); ?>">
  <meta property="og:description" content="<?php echo htmlspecialchars($desc); ?>">
  <meta property="og:image" content="<?php echo $base; ?>/assets/img/og-printika.png">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?php echo htmlspecialchars($titulo); ?>">
  <meta name="twitter:description" content="<?php echo htmlspecialchars($desc); ?>">
  <meta name="twitter:image" content="<?php echo $base; ?>/assets/img/og-printika.png">

  <script type="application/ld+json">
<?php echo json_encode(['@context' => 'https://schema.org', '@graph' => $grafo],
      JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT); ?>
  </script>

  <script>(function(){if(localStorage.getItem('ptools_tema')==='light'){document.documentElement.setAttribute('data-theme','light');}})();
  function ptTema(t){document.documentElement.setAttribute('data-theme',t==='light'?'light':'dark');localStorage.setItem('ptools_tema',t);}</script>
  <link rel="preload" href="/assets/fonts/Inter-400-latin.woff2" as="font" type="font/woff2" crossorigin>
  <link rel="stylesheet" href="/assets/fonts/fuentes.css">
  <link rel="stylesheet" href="/assets/css/guias.css">
</head>
<body>
  <header class="nav">
    <div class="cont">
      <a class="marca" href="/">
        <img class="logo-oscuro" src="<?php echo $logoOscuro; ?>" alt="Printika Tools">
        <img class="logo-claro" src="<?php echo $logoClaro; ?>" alt="Printika Tools">
      </a>
      <nav>
        <a class="link-seccion" href="/guias/">Guías</a>
        <a class="link-seccion" href="/#herramientas">Herramientas</a>
        <a class="link-seccion" href="/#planes">Precios</a>
        <a class="link-seccion" href="/comunidad/cotizador/" target="_blank" rel="noopener">Calculadora</a>
        <span class="tema" role="group" aria-label="Tema de la página">
          <button type="button" class="tema-btn" data-tema="light" onclick="ptTema('light')"
                  title="Modo día" aria-label="Modo día"><?php echo ui_icono('sol', 15); ?></button>
          <button type="button" class="tema-btn" data-tema="dark" onclick="ptTema('dark')"
                  title="Modo noche" aria-label="Modo noche"><?php echo ui_icono('luna', 15); ?></button>
        </span>
        <a class="btn" href="/comunidad/registro.php?plan=gratis">Comenzar gratis</a>
      </nav>
    </div>
  </header>

  <nav class="cont migas" aria-label="Migas de pan">
    <?php
    $ultimo = count($d['migas']) - 1;
    foreach ($d['migas'] as $i => $m) {
        if ($i > 0) echo '<span>/</span>';
        echo $i === $ultimo
            ? htmlspecialchars($m[0])
            : '<a href="' . htmlspecialchars(str_replace($base, '', $m[1])) . '">' . htmlspecialchars($m[0]) . '</a>';
    }
    ?>
  </nav>
<?php
}

/** Cierra la pagina. */
function guia_fin() { ?>
  <footer>
    <div class="cont">
      <div class="footer-grilla">
        <div class="footer-marca">
          <img class="logo-oscuro" src="/assets/img/printika-tools-dark.svg" alt="Printika Tools">
          <img class="logo-claro" src="/assets/img/printika-tools.svg" alt="Printika Tools">
          <p class="desc">Las herramientas y la comunidad para manejar tu taller de impresión 3D
             como un negocio.</p>
        </div>
        <div>
          <h4>Plataforma</h4>
          <ul>
            <li><a href="/comunidad/cotizador/">Calculadora</a></li>
            <li><a href="/guias/">Guías</a></li>
            <li><a href="/#herramientas">Herramientas</a></li>
            <li><a href="/#planes">Precios</a></li>
          </ul>
        </div>
        <div>
          <h4>Tu cuenta</h4>
          <ul>
            <li><a href="/comunidad/login.php">Iniciar sesión</a></li>
            <li><a href="/comunidad/registro.php?plan=gratis">Registrarse</a></li>
            <li><a href="https://t.me/+N5f7IcWPXihhMWQx" target="_blank" rel="noopener">Telegram</a></li>
          </ul>
        </div>
      </div>
      <div class="footer-pie">
        <p>© <?php echo date('Y'); ?> Printika Tools. Todos los derechos reservados.</p>
        <p>Hecho con impresoras 3D en Argentina</p>
      </div>
    </div>
  </footer>
</body>
</html>
<?php
}
