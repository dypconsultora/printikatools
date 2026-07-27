<?php
/**
 * Guia: cuanto cobrar por una impresion 3D.
 *
 * Apunta a las busquedas "cuanto cobrar por una impresion 3D" y similares.
 * Los precios de filamento salen de datos reales del taller (julio 2026) y
 * el ejemplo usa los mismos numeros que la tarjeta de la portada, para que
 * el sitio no se contradiga a si mismo.
 */
require_once __DIR__ . '/../inc/marco.php';

$url  = '/guias/cuanto-cobrar-impresion-3d/';
$base = 'https://printikatools.com';

$preguntas = [
    ['¿Cuánto sale imprimir en 3D en Argentina?',
     'Depende del peso de la pieza y del tiempo de impresión, pero el material es solo una parte: hay que sumarle electricidad, desgaste de la máquina, tu tiempo, las impresiones fallidas y la ganancia. Una pieza de 86 gramos que tarda poco más de 5 horas ronda los $12.450 con un PLA de $15.000 el kilo.'],
    ['¿Cuánto cuesta un kilo de filamento PLA?',
     'En Argentina va de $15.000 a $30.000 según la calidad, y hay materiales especiales que cuestan bastante más. El PETG se mueve en un rango parecido.'],
    ['¿Se cobra por gramo o por hora?',
     'Ni una cosa ni la otra por separado. El peso define el material y el tiempo define la electricidad y el desgaste de la máquina. Cobrar solo por gramo deja afuera las impresiones lentas, y cobrar solo por hora deja afuera las piezas pesadas.'],
    ['¿Hay que cobrar el diseño aparte?',
     'Sí. Modelar es un trabajo distinto de imprimir, con su propio tiempo y su propia tarifa.'],
];

guia_inicio([
    'titulo'      => '¿Cuánto cobrar por una impresión 3D? Guía con precios reales | Printika Tools',
    'descripcion' => 'Los seis costos que forman el precio de una impresión 3D: material, luz, desgaste, mano de obra, fallos y ganancia. Con precios de filamento en Argentina y un ejemplo completo.',
    'url'         => $url,
    'tipo'        => 'articulo',
    'tiene_ingles' => true,
    'migas'       => [
        ['Inicio', $base . '/'],
        ['Guías', $base . '/guias/'],
        ['Cuánto cobrar por una impresión 3D', $base . $url],
    ],
    'jsonld' => [
        [
            '@type'         => 'Article',
            '@id'           => $base . $url . '#articulo',
            'headline'      => '¿Cuánto cobrar por una impresión 3D?',
            'description'   => 'Los seis costos que forman el precio de una impresión 3D, con precios de filamento en Argentina y un ejemplo completo.',
            'inLanguage'    => 'es-AR',
            'datePublished' => '2026-07-26',
            'dateModified'  => '2026-07-26',
            'author'        => ['@type' => 'Organization', 'name' => 'Printika Tools',
                                'url' => $base . '/'],
            'publisher'     => ['@type' => 'Organization', 'name' => 'Printika Tools',
                                'url' => $base . '/',
                                'logo' => ['@type' => 'ImageObject',
                                           'url' => $base . '/assets/img/printika-tools-dark.svg']],
            'image'         => $base . '/assets/img/og-printika.png',
            'mainEntityOfPage' => $base . $url,
            'about'         => ['@type' => 'Thing', 'name' => 'Precios de impresión 3D'],
        ],
        [
            '@type'      => 'FAQPage',
            '@id'        => $base . $url . '#faq',
            'inLanguage' => 'es-AR',
            'mainEntity' => array_map(function ($p) {
                return ['@type' => 'Question', 'name' => $p[0],
                        'acceptedAnswer' => ['@type' => 'Answer', 'text' => $p[1]]];
            }, $preguntas),
        ],
    ],
]);
?>

<article class="guia">
  <div class="cont">
    <div class="arriba">
      <span class="ceja">Guía</span>
      <span class="punto">·</span>
      <span>Actualizada el <time datetime="2026-07-26">26/07/2026</time></span>
      <span class="punto">·</span>
      <span>7 minutos de lectura</span>
    </div>

    <h1>¿Cuánto cobrar por una impresión 3D?</h1>

    <div class="resumen">
      <p><strong>Respuesta corta:</strong> el precio de una impresión 3D se arma sumando seis cosas:
      material, electricidad, desgaste de la máquina, mano de obra, impresiones fallidas y ganancia.
      En Argentina un filamento PLA cuesta hoy entre <strong>$15.000 y $30.000 el kilo</strong> según
      la calidad, y ese es solo el primero de los seis costos. Cobrar solo el material es el error
      más caro que se puede cometer.</p>
      <p>Si querés el número sin leer toda la nota,
      <a href="/comunidad/cotizador/">calculalo acá</a> — es gratis y no hace falta registrarse.</p>
    </div>

    <h2 id="no-es-el-filamento">El precio no es el filamento</h2>
    <p>Casi todo el que arranca cobra así: peso la pieza, la multiplico por lo que me salió el rollo,
    le agrego algo y listo.</p>
    <p>Con esa cuenta estás trabajando gratis. Peor: estás pagando para trabajar, porque la máquina
    se gasta y la electricidad la ponés vos.</p>
    <p>Una pieza de 86 gramos en PLA, con un rollo de $15.000 el kilo, tiene <strong>$1.290 de
    material</strong>. Si esa impresión tardó 5 horas y 20 minutos, el precio final justo ronda los
    <strong>$12.450</strong>. La diferencia entre esos dos números no es «ganancia»: es todo lo que
    la primera cuenta se olvida.</p>

    <h2 id="los-seis-costos">Los seis costos que forman el precio</h2>

    <h3>1. Material</h3>
    <p>El más fácil y el único que casi todos cuentan.</p>
    <div class="formula">Costo del material = (precio del rollo ÷ peso del rollo) × gramos de la pieza</div>
    <p>Hoy en Argentina el PLA va de <strong>$15.000 a $30.000 el kilo</strong> según la calidad, y hay
    especiales que se van bastante más arriba. El PETG se mueve en un rango parecido. La diferencia
    entre un rollo barato y uno bueno no es capricho: un rollo malo te hace fallar impresiones, y una
    impresión fallida cuesta mucho más que la diferencia de precio del rollo.</p>
    <p><strong>Ojo con el soporte y la purga.</strong> Si tu laminador dice 86 gramos de pieza pero
    usás 12 de soporte, gastaste 98. Cobrá 98.</p>

    <h3>2. Electricidad</h3>
    <p>Una impresora no consume mucho por hora, pero las impresiones son largas.</p>
    <div class="formula">Costo de luz = consumo de tu impresora (kW) × horas de impresión × tu tarifa por kWh</div>
    <p>La tarifa sacala de tu propia factura, que cambia según dónde vivas y qué categoría tengas. Si
    tenés varias máquinas trabajando en paralelo todo el día, este número deja de ser un detalle.</p>

    <h3>3. Desgaste de la máquina</h3>
    <p>Este es el que nadie ve y el que más duele cuando llega.</p>
    <p>Tu impresora no es eterna: los ventiladores, las boquillas, las correas, la plancha, el
    extrusor. Todo eso se reemplaza. Si tu impresora costó X y te va a durar Y horas de trabajo, cada
    hora de impresión tiene que devolverte una parte de ese X, más el mantenimiento del año.</p>
    <div class="formula">Desgaste por hora = (costo de la impresora ÷ horas de vida útil) + (mantenimiento anual ÷ horas por año)</div>
    <p>Si no cobrás esto, el día que se te rompe algo lo pagás de tu bolsillo. Y la sensación es que
    «este mes no ganaste nada» — pero en realidad no ganaste ningún mes, solo se notó ahora.</p>

    <h3>4. Tu tiempo</h3>
    <p>Preparar el archivo, laminar, calibrar, sacar la pieza, limpiar soportes, lijar, pintar,
    empaquetar. Nada de eso lo hace la impresora sola.</p>
    <p>Poné cuánto vale tu hora y contá los minutos reales de trabajo humano, antes y después de la
    impresión. Es el costo que más se subestima, sobre todo cuando el post-proceso es largo.</p>

    <h3>5. Las impresiones que fallan</h3>
    <p>Fallan. A todos. Y la que falla también gastó material, luz y horas de máquina.</p>
    <p>Si de cada 10 impresiones se te arruina 1, tenés que repartir ese costo entre las 9 que salieron
    bien. Se hace agregando un porcentaje de fallo al costo total. No es pesimismo, es contabilidad.</p>

    <h3>6. La ganancia</h3>
    <p>Recién acá, arriba de todo lo anterior, va tu ganancia. Todo lo de antes son <strong>costos</strong>:
    recuperarlos no es ganar, es no perder.</p>
    <p>El porcentaje no es un número fijo: depende de la pieza y de lo que el cliente está pidiendo.
    Una pieza simple y repetida no se cobra igual que una que hay que diseñar, calibrar y probar tres
    veces.</p>

    <h2 id="errores">Los errores que se repiten</h2>
    <ul>
      <li><strong>Cobrar solo el material.</strong> El error madre, del que salen todos los demás.</li>
      <li><strong>No contar la luz</strong> porque «es poquito». Poquito por hora, mucho por mes.</li>
      <li><strong>No contar el desgaste de la máquina</strong> hasta que se rompe algo.</li>
      <li><strong>Regalar el diseño.</strong> Si modelaste la pieza, eso es un trabajo aparte del de
        imprimirla, y se cobra aparte.</li>
      <li><strong>No contar las impresiones fallidas</strong>, como si nunca pasaran.</li>
      <li><strong>Olvidarse de las comisiones.</strong> Si vendés por Mercado Libre, la comisión sale
        de tu ganancia, no del aire.</li>
      <li><strong>Cobrar por gramo.</strong> Dos piezas del mismo peso pueden tardar tiempos muy
        distintos según el relleno y la altura de capa. El peso solo no alcanza.</li>
    </ul>

    <h2 id="calcularlo">Cómo calcularlo sin hacer la cuenta a mano</h2>
    <p>Todo esto está resuelto en la <a href="/comunidad/cotizador/">calculadora de Printika
    Tools</a>: cargás el precio del rollo, el peso, el tiempo, tu tarifa de luz y los datos de tu
    impresora, y te devuelve el precio final en pesos, dólares o euros. Es gratis y no necesitás
    registrarte.</p>
    <p>Si además querés guardar los presupuestos, llevar tus clientes y controlar el stock de rollos,
    eso vive en <a href="/comunidad/registro.php?plan=gratis">la plataforma</a>.</p>

    <h2 id="faq">Preguntas frecuentes</h2>
    <div class="faq-guia">
      <?php foreach ($preguntas as $p): ?>
        <details>
          <summary><?php echo htmlspecialchars($p[0]); ?></summary>
          <p class="resp"><?php echo htmlspecialchars($p[1]); ?></p>
        </details>
      <?php endforeach; ?>
    </div>

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
