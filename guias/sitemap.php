<?php
/**
 * Mapa del sitio de las guias, armado solo.
 *
 * Se sirve como /guias/sitemap.xml (lo reescribe el .htaccess de esta
 * carpeta). Asi cada guia nueva que carga la administradora aparece sin que
 * nadie tenga que tocar el sitemap principal.
 */
require_once dirname(__DIR__) . '/comunidad/inc/bootstrap.php';
require_once dirname(__DIR__) . '/comunidad/inc/guias.php';

header('Content-Type: application/xml; charset=utf-8');

$base = 'https://printikatools.com';
$urls = [
    ['loc' => $base . '/guias/', 'mod' => date('Y-m-d'), 'freq' => 'weekly', 'pri' => '0.8'],
    ['loc' => $base . '/guias/cuanto-cobrar-impresion-3d/', 'mod' => '2026-07-26',
     'freq' => 'monthly', 'pri' => '0.8'],
];
foreach (guias_publicadas() as $g) {
    $urls[] = [
        'loc'  => $base . '/guias/' . $g['slug'] . '/',
        'mod'  => date('Y-m-d', strtotime($g['actualizado_en'])),
        'freq' => 'monthly',
        'pri'  => '0.8',
    ];
}

// Las que tienen version inglesa van tambien con su direccion /en/
$urls[] = ['loc' => $base . '/en/guias/', 'mod' => date('Y-m-d'), 'freq' => 'weekly', 'pri' => '0.7'];
$urls[] = ['loc' => $base . '/en/guias/cuanto-cobrar-impresion-3d/', 'mod' => '2026-07-27',
           'freq' => 'monthly', 'pri' => '0.7'];

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as $u) {
    echo "  <url>\n";
    echo '    <loc>' . htmlspecialchars($u['loc']) . "</loc>\n";
    echo '    <lastmod>' . $u['mod'] . "</lastmod>\n";
    echo '    <changefreq>' . $u['freq'] . "</changefreq>\n";
    echo '    <priority>' . $u['pri'] . "</priority>\n";
    echo "  </url>\n";
}
echo "</urlset>\n";
