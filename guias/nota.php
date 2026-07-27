<?php
/**
 * Muestra una guia cargada desde el administrador.
 *
 * Se llega por /guias/<direccion>/ gracias al .htaccess de esta carpeta.
 * La primera guia (cuanto-cobrar-impresion-3d) es una carpeta de verdad, asi
 * que no pasa por aca: el .htaccess no reescribe lo que existe en el disco.
 */
require_once __DIR__ . '/inc/marco.php';
require_once dirname(__DIR__) . '/comunidad/inc/guias.php';

$slug = preg_replace('/[^a-z0-9-]/', '', strtolower((string) ($_GET['slug'] ?? '')));
$guia = $slug !== '' ? guia_por_slug($slug) : null;

if (!$guia) {
    http_response_code(404);
    guia_inicio([
        'titulo'      => 'Esta guía no existe · Printika Tools',
        'descripcion' => 'La guía que buscabas no está disponible.',
        'url'         => '/guias/',
        'tipo'        => 'listado',
        'migas'       => [['Inicio', 'https://printikatools.com/'],
                          ['Guías', 'https://printikatools.com/guias/']],
    ]);
    ?>
    <article class="guia">
      <div class="cont">
        <h1>Esta guía no existe</h1>
        <p>Puede que la hayamos cambiado de lugar o que todavía no esté publicada.</p>
        <div class="cta-guia">
          <h2 style="margin-bottom:8px">Mirá las guías que sí están</h2>
          <div class="botones">
            <a class="btn" href="/guias/">Ver todas las guías</a>
            <a class="btn sec" href="/comunidad/cotizador/">Abrir la calculadora</a>
          </div>
        </div>
      </div>
    </article>
    <?php
    guia_fin();
    exit;
}

$base = 'https://printikatools.com';
$url  = '/guias/' . $guia['slug'] . '/';
$desc = $guia['bajada'] !== '' ? $guia['bajada'] : guia_extracto($guia);
$img  = $guia['imagen_ext'] !== ''
      ? $base . guia_img_url($guia['id'], 'portada', $guia['imagen_ext'])
      : $base . '/assets/img/og-printika.png';

guia_inicio([
    'titulo'      => $guia['titulo'] . ' | Printika Tools',
    'descripcion' => $desc,
    'url'         => $url,
    'tipo'        => 'articulo',
    'tiene_ingles' => false,   // el cuerpo lo escribe la administradora
    'imagen'      => $img,
    'migas'       => [
        ['Inicio', $base . '/'],
        ['Guías', $base . '/guias/'],
        [$guia['titulo'], $base . $url],
    ],
    'jsonld' => [[
        '@type'         => 'Article',
        '@id'           => $base . $url . '#articulo',
        'headline'      => $guia['titulo'],
        'description'   => $desc,
        'inLanguage'    => 'es-AR',
        'datePublished' => date('Y-m-d', strtotime($guia['creado_en'])),
        'dateModified'  => date('Y-m-d', strtotime($guia['actualizado_en'])),
        'author'    => ['@type' => 'Organization', 'name' => 'Printika Tools', 'url' => $base . '/'],
        'publisher' => ['@type' => 'Organization', 'name' => 'Printika Tools', 'url' => $base . '/',
                        'logo' => ['@type' => 'ImageObject',
                                   'url' => $base . '/assets/img/printika-tools-dark.svg']],
        'image'     => $img,
        'mainEntityOfPage' => $base . $url,
    ]],
]);
?>

<article class="guia">
  <div class="cont">
    <div class="arriba">
      <span class="ceja"><?php echo htmlspecialchars($guia['ceja']); ?></span>
      <span class="punto">·</span>
      <span>Actualizada el <time datetime="<?php echo date('Y-m-d', strtotime($guia['actualizado_en'])); ?>">
        <?php echo date('d/m/Y', strtotime($guia['actualizado_en'])); ?></time></span>
      <span class="punto">·</span>
      <span><?php echo (int) $guia['minutos']; ?> minutos de lectura</span>
    </div>

    <h1><?php echo htmlspecialchars($guia['titulo']); ?></h1>

    <?php if ($guia['imagen_ext'] !== ''): ?>
      <figure class="fig-guia portada">
        <img src="<?php echo htmlspecialchars(guia_img_url($guia['id'], 'portada', $guia['imagen_ext'])); ?>"
             alt="<?php echo htmlspecialchars($guia['titulo']); ?>" decoding="async" fetchpriority="high">
      </figure>
    <?php endif; ?>

    <?php if ($guia['youtube'] !== ''): ?>
      <figure class="fig-guia">
        <div class="video-guia">
          <iframe src="https://www.youtube-nocookie.com/embed/<?php echo htmlspecialchars($guia['youtube']); ?>"
                  title="<?php echo htmlspecialchars($guia['titulo']); ?>" loading="lazy" allowfullscreen
                  allow="accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                  referrerpolicy="strict-origin-when-cross-origin"></iframe>
        </div>
      </figure>
    <?php endif; ?>

    <?php guia_render($guia); ?>

    <div class="cta-guia">
      <h2 style="margin-bottom:8px">Calculá el precio de tu próxima impresión</h2>
      <p>Gratis, sin registro y en pesos, dólares o euros.</p>
      <div class="botones">
        <a class="btn" href="/comunidad/cotizador/">Abrir la calculadora</a>
        <a class="btn sec" href="/comunidad/registro.php?plan=gratis">Crear cuenta gratis</a>
      </div>
    </div>
  </div>
</article>

<?php guia_fin(); ?>
