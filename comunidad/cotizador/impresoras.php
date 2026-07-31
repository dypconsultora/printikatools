<?php
/**
 * Ficha de cada impresora: consumo, precio, vida util y mantenimiento anual.
 *
 * Se escribe UNA sola vez y la usan las dos secciones de la calculadora: el
 * consumo en Costos de Electricidad y las otras tres en Depreciacion. Antes el
 * consumo vivia adentro del nombre de la opcion ("Bambu Lab A1 (95 W)") y habia
 * que sacarlo con una expresion regular; ahora sale de aca.
 *
 * LOS PRECIOS ESTAN EN DOLARES. Es la unica moneda en la que un precio escrito
 * hoy sigue queriendo decir lo mismo dentro de seis meses. La calculadora no
 * convierte monedas por su cuenta (el selector de arriba solo cambia el simbolo),
 * asi que aca abajo estan los dos tipos de cambio que se usan para completar los
 * campos, y son lo unico que hay que actualizar cada tanto.
 *
 * Son valores de REFERENCIA, de lista del fabricante. Quien compro en otro lado,
 * usada o con impuestos distintos, los edita a mano: los campos quedan abiertos.
 */

/** Cuantos pesos vale un dolar, para completar los campos en ARS. */
define('COT_USD_ARS', 1450);

/** Cuantos euros vale un dolar. */
define('COT_USD_EUR', 0.92);

/**
 * [nombre, watts, precio USD, vida util en horas, mantenimiento anual USD]
 *
 * Vida util: 4.000 h en las de filamento de uso hogareño y 6.000 h en las de
 * marco cerrado y armazon metalico, que aguantan mas. Las de resina van con
 * 2.000 h, que es lo que dura la pantalla LCD antes de tener que cambiarla.
 *
 * Mantenimiento: boquillas, correas, placa y limpieza. En las de resina incluye
 * el recambio de la pantalla, que es el gasto grande y por eso son las mas caras
 * de mantener en relacion a lo que salen.
 */
function cot_impresoras() {
    return [
        ['Bambu Lab A1 Mini',        45,  249,  4000,  40],
        ['Bambu Lab A1',             95,  339,  4000,  50],
        ['Bambu Lab P1P',            80,  469,  5000,  60],
        ['Bambu Lab P1S',           100,  699,  5000,  60],
        ['Bambu Lab P2S',           130,  949,  6000,  70],
        ['Bambu Lab X1 Carbon',     120, 1199,  6000,  80],
        ['Bambu Lab H2S',           210, 1499,  6000,  90],
        ['Bambu Lab H2D',           210, 1899,  6000, 100],
        ['Bambu Lab H2C',           210, 2199,  6000, 110],
        ['Prusa MK3S+',              80,  799,  6000,  70],
        ['Prusa MK4',               100,  999,  6000,  70],
        ['Creality Ender 3 V2',     110,  199,  4000,  45],
        ['Creality Ender 3 S1',     120,  269,  4000,  45],
        ['Creality K1',             100,  399,  5000,  60],
        ['Creality K1C',            100,  479,  5000,  60],
        ['Creality K1 Max',         200,  749,  5000,  70],
        ['Anycubic Kobra 2',         75,  219,  4000,  45],
        ['Anycubic Vyper',           80,  259,  4000,  45],
        ['SnapMaker U1',            130,  899,  5000,  70],
        ['Elegoo Saturn 3 (resina)', 75,  289,  2000, 120],
        ['Elegoo Saturn 4 (resina)', 75,  349,  2000, 120],
        ['Voron 2.4 (350mm DIY)',   225, 1200,  6000,  90],
    ];
}

/**
 * Las mismas fichas, listas para el navegador: el nombre con los watts (que es
 * como se ve en la lista de siempre) y los tres numeros ya pasados a cada moneda.
 */
function cot_impresoras_js() {
    $salida = [];
    foreach (cot_impresoras() as [$nombre, $watts, $usd, $vida, $mant]) {
        $salida[] = [
            'n' => $nombre . ' (' . $watts . ' W)',
            'w' => $watts,
            'v' => $vida,
            // Redondeados a algo que se pueda leer: nadie escribe 1.087.412
            'c' => ['USD' => $usd,
                    'EUR' => (int) round($usd * COT_USD_EUR),
                    'ARS' => (int) (round($usd * COT_USD_ARS / 1000) * 1000)],
            'm' => ['USD' => $mant,
                    'EUR' => (int) round($mant * COT_USD_EUR),
                    'ARS' => (int) (round($mant * COT_USD_ARS / 1000) * 1000)],
        ];
    }
    return $salida;
}
