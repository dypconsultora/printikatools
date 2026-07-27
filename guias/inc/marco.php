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
 * Las guias tambien viven en dos direcciones, igual que la portada:
 *   /guias/...      castellano
 *   /en/guias/...   ingles  (en/guias/* define GUIAS_EN e incluye estos mismos archivos)
 * El texto se escribe una sola vez y se traduce en el servidor con
 * assets/lang/guias-en.json.
 */
function guias_en() { return defined('GUIAS_EN'); }

/** Diccionario castellano -> ingles (vacio si estamos en castellano). */
function guias_dic() {
    static $dic = null;
    if ($dic !== null) return $dic;
    $dic = [];
    if (guias_en()) {
        $json = @file_get_contents(dirname(__DIR__, 2) . '/assets/lang/guias-en.json');
        if ($json !== false) {
            $d = json_decode($json, true);
            if (is_array($d)) $dic = $d;
        }
    }
    return $dic;
}

/** Traduce una frase suelta. */
function gt($texto) {
    $dic = guias_dic();
    return $dic[$texto] ?? $texto;
}

/**
 * La misma direccion en el otro idioma. Si la guia no tiene version inglesa
 * (las que carga la administradora), cae en el listado en ingles: es mejor
 * que mandarlo a la portada, que es de otro tema.
 */
function guias_url_otro_idioma($url, $tieneIngles = true) {
    if (guias_en()) return preg_replace('~^/en~', '', $url);
    return $tieneIngles ? '/en' . $url : '/en/guias/';
}

/**
 * Traduce el HTML ya armado: texto entre etiquetas y atributos visibles.
 * Lo que esta adentro de <script> y <style> no se toca.
 */
function guias_traducir($html) {
    $dic = guias_dic();
    if (!$dic) return $html;

    $guardado = [];
    $html = preg_replace_callback('#<(script|style)\b[^>]*>.*?</\1\s*>#is', function ($m) use (&$guardado) {
        $guardado[] = $m[0];
        return "\x02" . (count($guardado) - 1) . "\x03";
    }, $html);

    $html = preg_replace_callback('#>([^<]+)<#', function ($m) use ($dic) {
        $bruto  = $m[1];
        $limpio = trim(preg_replace('/\s+/u', ' ', $bruto));
        if ($limpio === '' || !isset($dic[$limpio])) return $m[0];
        // Los espacios de los bordes se conservan: sin esto, un texto que
        // termina antes de un <strong> se pega con lo que sigue
        // ("Updated on26/07/2026", "betweenARS 15,000").
        $izq = preg_match('/^\s/u', $bruto) ? ' ' : '';
        $der = preg_match('/\s$/u', $bruto) ? ' ' : '';
        return '>' . $izq . $dic[$limpio] . $der . '<';
    }, $html);

    $attrs = ['alt', 'title', 'aria-label'];
    $html = preg_replace_callback('#\b(' . implode('|', $attrs) . ')="([^"]*)"#i',
        function ($m) use ($dic) {
            $limpio = trim($m[2]);
            return isset($dic[$limpio])
                ? $m[1] . '="' . htmlspecialchars($dic[$limpio], ENT_QUOTES) . '"' : $m[0];
        }, $html);

    return preg_replace_callback('#\x02(\d+)\x03#', function ($m) use ($guardado) {
        return $guardado[(int) $m[1]] ?? '';
    }, $html);
}

/**
 * Abre la pagina.
 *
 * @param array $d titulo, descripcion, url (sin dominio), tipo ('articulo'|'listado'),
 *                 publicado y actualizado (Y-m-d), jsonld (array extra para el @graph)
 */
function guia_inicio(array $d) {
    ob_start();                       // en ingles el HTML pasa por el diccionario al cerrar
    $base   = 'https://printikatools.com';
    $en     = guias_en();
    $urlEs  = $d['url'];
    $urlEn  = '/en' . $d['url'];
    $tieneIngles = $d['tiene_ingles'] ?? true;
    $url    = $base . ($en ? $urlEn : $urlEs);
    $titulo = gt($d['titulo']);
    $desc   = gt($d['descripcion']);
    $logoOscuro = '/assets/img/printika-tools-dark.svg';
    $logoClaro  = '/assets/img/printika-tools.svg';

    $grafo = $d['jsonld'] ?? [];
    array_unshift($grafo, [
        '@type' => 'BreadcrumbList',
        'itemListElement' => array_map(function ($i, $m) {
            return ['@type' => 'ListItem', 'position' => $i + 1,
                    'name' => gt($m[0]), 'item' => $m[1]];
        }, array_keys($d['migas']), $d['migas']),
    ]);
    ?><!DOCTYPE html>
<html lang="<?php echo $en ? 'en' : 'es'; ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($titulo); ?></title>
  <meta name="description" content="<?php echo htmlspecialchars($desc); ?>">
  <link rel="canonical" href="<?php echo $url; ?>">
<?php if ($tieneIngles): ?>
  <link rel="alternate" hreflang="es" href="<?php echo $base . $urlEs; ?>">
  <link rel="alternate" hreflang="en" href="<?php echo $base . $urlEn; ?>">
  <link rel="alternate" hreflang="x-default" href="<?php echo $base . $urlEs; ?>">
<?php endif; ?>
  <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large">
  <meta name="theme-color" content="#0b0f17">
  <link rel="icon" type="image/svg+xml" href="/assets/img/favicon.svg">
  <link rel="icon" sizes="any" href="/favicon.ico">
  <link rel="apple-touch-icon" href="/assets/img/favicon-180.png">

  <meta property="og:type" content="<?php echo ($d['tipo'] ?? '') === 'articulo' ? 'article' : 'website'; ?>">
  <meta property="og:site_name" content="Printika Tools">
  <meta property="og:locale" content="<?php echo $en ? 'en_US' : 'es_AR'; ?>">
  <meta property="og:url" content="<?php echo $url; ?>">
  <meta property="og:title" content="<?php echo htmlspecialchars($titulo); ?>">
  <meta property="og:description" content="<?php echo htmlspecialchars($desc); ?>">
  <meta property="og:image" content="<?php echo htmlspecialchars($d['imagen'] ?? $base . '/assets/img/og-printika.png'); ?>">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?php echo htmlspecialchars($titulo); ?>">
  <meta name="twitter:description" content="<?php echo htmlspecialchars($desc); ?>">
  <meta name="twitter:image" content="<?php echo htmlspecialchars($d['imagen'] ?? $base . '/assets/img/og-printika.png'); ?>">

  <script type="application/ld+json">
<?php echo json_encode(['@context' => 'https://schema.org', '@graph' => $grafo],
      JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT); ?>
  </script>

  <script>(function(){if(localStorage.getItem('ptools_tema')==='light'){document.documentElement.setAttribute('data-theme','light');}})();
  function ptTema(t){document.documentElement.setAttribute('data-theme',t==='light'?'light':'dark');localStorage.setItem('ptools_tema',t);}</script>
  <link rel="preload" href="/assets/fonts/Inter-400-latin.woff2" as="font" type="font/woff2" crossorigin>
  <link rel="stylesheet" href="/assets/fonts/fuentes.css">
  <link rel="stylesheet" href="/assets/css/guias.css">
  <script>
  // Si la persona elige un idioma en las guias, queda guardado igual que en
  // la portada: las dos usan la misma preferencia.
  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.idioma a[hreflang]').forEach(function (a) {
      a.addEventListener('click', function () {
        try { localStorage.setItem('ptools_idioma', a.getAttribute('hreflang')); } catch (e) {}
      });
    });
  });
  </script>
</head>
<body>
  <header class="nav">
    <div class="cont">
      <a class="marca" href="/">
        <img class="logo-oscuro" src="<?php echo $logoOscuro; ?>" alt="Printika Tools">
        <img class="logo-claro" src="<?php echo $logoClaro; ?>" alt="Printika Tools">
      </a>
      <nav>
        <a class="link-seccion" href="<?php echo $en ? '/en/' : '/'; ?>#herramientas">Herramientas</a>
        <a class="link-seccion" href="<?php echo $en ? '/en/' : '/'; ?>#comunidad">Comunidad</a>
        <a class="link-seccion" href="<?php echo $en ? '/en/' : '/'; ?>#planes">Precios</a>
        <a class="link-seccion" href="<?php echo $en ? '/en/guias/' : '/guias/'; ?>">Guías</a>
        <a class="link-seccion" href="<?php echo $en ? '/en/' : '/'; ?>#faq">FAQ</a>
        <a class="link-seccion" href="/comunidad/cotizador/" target="_blank" rel="noopener">Calculadora</a>
        <span class="tema" role="group" aria-label="Tema de la página">
          <span class="idioma" role="group" aria-label="Idioma / Language">
            <span class="idioma-tit">IDIOMA</span>
            <a href="<?php echo $en ? guias_url_otro_idioma($urlEn, $tieneIngles) : $urlEs; ?>"
               hreflang="es" class="<?php echo $en ? '' : 'activo'; ?>"
               <?php if (!$en) echo 'aria-current="true"'; ?>>ESP</a>
            <a href="<?php echo $en ? $urlEn : guias_url_otro_idioma($urlEs, $tieneIngles); ?>"
               hreflang="en" class="<?php echo $en ? 'activo' : ''; ?>"
               <?php if ($en) echo 'aria-current="true"'; ?>>ENG</a>
          </span>
          <button type="button" class="tema-btn" data-tema="light" onclick="ptTema('light')"
                  title="Modo día" aria-label="Modo día"><?php echo ui_icono('sol', 15); ?></button>
          <button type="button" class="tema-btn" data-tema="dark" onclick="ptTema('dark')"
                  title="Modo noche" aria-label="Modo noche"><?php echo ui_icono('luna', 15); ?></button>
        </span>
        <a class="entrar" href="/comunidad/login.php">Iniciar sesión</a>
        <a class="btn" href="<?php echo $en ? '/en/' : '/'; ?>#planes">Registrarse</a>
      </nav>
    </div>
  </header>

  <nav class="cont migas" aria-label="Migas de pan">
    <?php
    $ultimo = count($d['migas']) - 1;
    foreach ($d['migas'] as $i => $m) {
        if ($i > 0) echo '<span>/</span>';
        $nombre = htmlspecialchars(gt($m[0]));
        $destino = htmlspecialchars(($en ? '/en' : '') . str_replace($base, '', $m[1]));
        echo $i === $ultimo ? $nombre : '<a href="' . $destino . '">' . $nombre . '</a>';
    }
    ?>
  </nav>
<?php
}

/** Cierra la pagina. */
function guia_fin() { $en = guias_en(); ?>
  <footer>
    <div class="cont">
      <div class="footer-grilla">
        <div class="footer-marca">
          <img class="logo-oscuro" src="/assets/img/printika-tools-dark.svg" alt="Printika Tools">
          <img class="logo-claro" src="/assets/img/printika-tools.svg" alt="Printika Tools">
          <p class="desc">Las herramientas y la comunidad para manejar tu taller de impresión 3D
             como un negocio.</p>
          <a class="btn sec footer-cta" href="/comunidad/registro.php?plan=gratis">Comenzar gratis</a>
        </div>
        <div>
          <h4>Plataforma</h4>
          <ul>
            <li><a href="/comunidad/cotizador/">Calculadora</a></li>
            <li><a href="<?php echo guias_en() ? '/en/guias/' : '/guias/'; ?>">Guías</a></li>
            <li><a href="<?php echo guias_en() ? '/en/' : '/'; ?>#herramientas">Herramientas</a></li>
            <li><a href="<?php echo guias_en() ? '/en/' : '/'; ?>#planes">Precios</a></li>
            <li><a href="<?php echo guias_en() ? '/en/' : '/'; ?>#faq">FAQ</a></li>
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
        <p>© <?php echo date('Y'); ?> Printika Tools. <?php echo gt('Todos los derechos reservados.'); ?></p>
        <p><?php echo gt('Hecho con impresoras 3D en Argentina'); ?></p>
      </div>
    </div>
  </footer>
</body>
</html>
<?php
    echo guias_traducir(ob_get_clean());
}
