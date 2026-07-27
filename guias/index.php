<?php
/**
 * Listado de guias.
 *
 * Es el punto de entrada de la seccion: junta las notas y le da a Google una
 * pagina desde donde llegar a todas. Para sumar una guia nueva alcanza con
 * agregarla al array de abajo y crear su carpeta.
 */
require_once __DIR__ . '/inc/marco.php';
require_once dirname(__DIR__) . '/comunidad/inc/guias.php';

$base = 'https://printikatools.com';

$guias = [
    [
        'url'   => (guias_en() ? '/en' : '') . '/guias/cuanto-cobrar-impresion-3d/',
        'ceja'  => 'Precios',
        'titulo' => '¿Cuánto cobrar por una impresión 3D?',
        'bajada' => 'Los seis costos que forman el precio: material, luz, desgaste, tu tiempo, '
                  . 'las impresiones fallidas y la ganancia. Con precios reales de filamento en Argentina.',
    ],
];

// Las que carga la administradora desde el panel, arriba de la escrita a mano
foreach (guias_publicadas() as $g) {
    array_unshift($guias, [
        'url'    => (guias_en() ? '/en' : '') . '/guias/' . $g['slug'] . '/',
        'ceja'   => $g['ceja'],
        'titulo' => $g['titulo'],
        'bajada' => $g['bajada'] !== '' ? $g['bajada'] : guia_extracto($g),
        'imagen' => $g['imagen_ext'] !== '' ? guia_img_url($g['id'], 'portada', $g['imagen_ext']) : '',
    ]);
}

guia_inicio([
    'titulo'      => 'Guías para talleres de impresión 3D | Printika Tools',
    'descripcion' => 'Guías prácticas sobre precios, costos y gestión de un taller de impresión 3D: '
                   . 'cuánto cobrar, cómo calcular el costo real y qué errores evitar.',
    'url'         => '/guias/',
    'tipo'        => 'listado',
    'tiene_ingles' => true,
    'migas'       => [['Inicio', $base . '/'], ['Guías', $base . '/guias/']],
    'jsonld'      => [[
        '@type'      => 'CollectionPage',
        '@id'        => $base . '/guias/#listado',
        'name'       => 'Guías para talleres de impresión 3D',
        'inLanguage' => 'es-AR',
        'hasPart'    => array_map(function ($g) use ($base) {
            return ['@type' => 'Article', 'headline' => $g['titulo'],
                    'url' => $base . $g['url'], 'description' => $g['bajada']];
        }, $guias),
    ]],
]);
?>

<main class="hub">
  <div class="cont">
    <div class="cabeza">
      <h1>Guías para tu taller</h1>
      <p>Cómo poner precio, qué costos se olvidan siempre y cómo llevar las cuentas de un taller
         de impresión 3D. Escritas con datos reales, no con teoría.</p>
    </div>

    <div class="hub-grilla">
      <?php foreach ($guias as $g): ?>
        <a class="tarjeta-guia" href="<?php echo $g['url']; ?>">
          <?php if (!empty($g['imagen'])): ?>
            <img class="tapa" src="<?php echo htmlspecialchars($g['imagen']); ?>" alt="" loading="lazy" decoding="async">
          <?php endif; ?>
          <span class="ceja"><?php echo htmlspecialchars($g['ceja']); ?></span>
          <h2><?php echo htmlspecialchars($g['titulo']); ?></h2>
          <p><?php echo htmlspecialchars($g['bajada']); ?></p>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</main>

<?php guia_fin(); ?>
