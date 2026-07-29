<?php
/**
 * El adelanto que ve alguien del plan gratis cuando toca un candado.
 *
 * Antes el candado lo dejaba en la pantalla de planes sin ninguna explicacion:
 * pedirle plata a alguien que nunca vio lo que esta comprando no funciona. Aca
 * se define, para cada seccion, que es, que resuelve y una captura real de la
 * pantalla, para que la decision la tome viendo y no imaginando.
 *
 * Las capturas viven en assets/img/vistazo/<clave>.webp y son del sistema de
 * verdad, con datos de ejemplo. Si falta alguna no se rompe nada: la tarjeta
 * se muestra igual, solo sin foto.
 */

/**
 * Datos de cada seccion con candado. La clave es el nombre del archivo
 * (presupuestos.php -> 'presupuestos'), y 'telegram' para el grupo de soporte.
 */
function vistazo_secciones() {
    return [
        'libreria' => [
            'titulo'  => 'Librería STL',
            'gancho'  => 'Modelos listos para imprimir y vender, sin buscar por internet.',
            'detalle' => 'Una biblioteca que crece todos los meses, con archivos probados en
                          impresora antes de publicarlos. Los bajás y los imprimís, sin sorpresas.',
            'items'   => [
                'Modelos nuevos todos los meses',
                'Probados antes de publicarse: no vas a perder filamento',
                'Descarga directa, sin registrarte en otro lado',
            ],
        ],
        'presupuestos' => [
            'titulo'  => 'Presupuestos',
            'gancho'  => 'Presupuestos con tu logo, en PDF, en menos de un minuto.',
            'detalle' => 'Elegís el cliente, cargás las piezas y sale el PDF armado con tus datos
                          y tu marca. Se acabó el "te paso el precio por WhatsApp" a mano.',
            'items'   => [
                'PDF con tu logo y tus datos',
                'Quedan todos guardados y los buscás cuando quieras',
                'Del presupuesto a la venta, sin volver a cargar nada',
            ],
        ],
        'productos' => [
            'titulo'  => 'Productos',
            'gancho'  => 'Tus piezas con su precio ya calculado, listas para presupuestar.',
            'detalle' => 'Cargás una vez lo que imprimís seguido, con su costo real, y después lo
                          usás en cualquier presupuesto con un clic.',
            'items'   => [
                'El precio se calcula solo con tu costo de filamento',
                'Se reutilizan en presupuestos y ventas',
                'Si cambia el filamento, actualizás en un solo lugar',
            ],
        ],
        'clientes' => [
            'titulo'  => 'Clientes',
            'gancho'  => 'Toda tu clientela junta, con lo que le vendiste a cada uno.',
            'detalle' => 'Dejás de buscar el teléfono en la agenda y el pedido viejo en el chat.
                          Cada cliente con sus datos, sus presupuestos y sus compras.',
            'items'   => [
                'Datos, teléfono y dirección en un solo lugar',
                'El historial de lo que le vendiste',
                'Se completa solo al hacer un presupuesto',
            ],
        ],
        'stock' => [
            'titulo'  => 'Stock de materiales',
            'gancho'  => 'Cuánto filamento te queda, y cuánto te costó de verdad.',
            'detalle' => 'Cargás cada rollo con lo que pagaste y el sistema descuenta lo que vas
                          usando. Así el precio que cobrás sale de tu costo real, no de un cálculo
                          de hace seis meses.',
            'items'   => [
                'Cuánto queda de cada rollo',
                'Aviso cuando se está por acabar',
                'Tus costos reales alimentan la calculadora',
            ],
        ],
        'ventas' => [
            'titulo'  => 'Ventas',
            'gancho'  => 'Lo que vendiste, lo que cobraste y lo que te falta cobrar.',
            'detalle' => 'Cada venta con su cliente, su fecha y su estado de pago. Sin planilla
                          aparte y sin acordarte de memoria quién te debe.',
            'items'   => [
                'Cobrado y pendiente, separado',
                'Nace del presupuesto: no cargás dos veces',
                'Alimenta las estadísticas del taller',
            ],
        ],
        'estadisticas' => [
            'titulo'  => 'Estadísticas',
            'gancho'  => 'Si tu taller gana plata, y con qué la gana.',
            'detalle' => 'Cuánto facturaste por mes, qué piezas dejan más margen y quiénes son tus
                          mejores clientes. Es la diferencia entre imprimir y tener un negocio.',
            'items'   => [
                'Facturación mes a mes',
                'Qué te deja margen y qué no',
                'Tus mejores clientes, ordenados',
            ],
        ],
        'configuracion' => [
            'titulo'  => 'Configuración del taller',
            'gancho'  => 'Tu logo, tus datos y tu moneda en todo lo que sale a la calle.',
            'detalle' => 'Los presupuestos salen con tu marca y no con la nuestra. También elegís
                          la moneda y activás la verificación en dos pasos.',
            'items'   => [
                'Tu logo en los PDF',
                'Moneda a elección (ARS, USD, EUR)',
                'Verificación en dos pasos',
            ],
        ],
        'telegram' => [
            'titulo'  => 'Soporte por Telegram',
            'gancho'  => 'Preguntá y te contestamos, sin formularios ni esperas de días.',
            'detalle' => 'El grupo privado donde están los que ya viven de esto: consultas de
                          precios, de materiales y de máquina, con respuesta de gente que imprime
                          todos los días.',
            'items'   => [
                'Respuesta rápida a tus consultas',
                'Grupo privado sólo para suscriptores',
                'Novedades y herramientas nuevas primero',
            ],
        ],
    ];
}

/** Los datos de una seccion, o null si la clave no es de una pantalla con candado. */
function vistazo_seccion($clave) {
    return vistazo_secciones()[$clave] ?? null;
}

/**
 * Ruta web de la captura de una seccion, o '' si todavia no hay.
 *
 * Hay una version por idioma (stock.webp y stock-en.webp): el texto del
 * adelanto se traduce solo, pero la foto no, y una captura en castellano
 * debajo de un texto en ingles se nota. Si falta la inglesa cae en la
 * castellana, que es mejor que no mostrar nada.
 */
function vistazo_imagen($clave) {
    $base = dirname(__DIR__, 2);
    require_once __DIR__ . '/taller.php';
    if (taller_idioma() === 'en') {
        $en = '/assets/img/vistazo/' . $clave . '-en.webp';
        if (is_file($base . $en)) return $en;
    }
    $rel = '/assets/img/vistazo/' . $clave . '.webp';
    return is_file($base . $rel) ? $rel : '';
}
