<?php
/**
 * Ficha de cada impresora: consumo, precio, vida util y mantenimiento anual.
 *
 * Se escribe UNA sola vez y la usan las dos secciones de la calculadora: el
 * consumo en Costos de Electricidad y el resto en Depreciacion. Antes el consumo
 * vivia adentro del nombre de la opcion ("Bambu Lab A1 (95 W)") y habia que
 * sacarlo con una expresion regular; ahora sale de aca.
 *
 * LOS PRECIOS SON EN PESOS, relevados por Adriana en el mercado argentino
 * (planilla del 2026-07-30). Son de referencia: quien la compro usada, en otro
 * lado o con otros impuestos, edita los campos, que quedan abiertos.
 *
 * PARA ACTUALIZARLOS: se cambian los numeros de la tabla de abajo y listo. Los
 * valores en dolares y euros se calculan solos a partir del peso, con los tipos
 * de cambio que estan aca arriba; esos tambien hay que revisarlos cada tanto.
 */

/** Cuantos pesos vale un dolar. */
define('COT_USD_ARS', 1450);

/** Cuantos pesos vale un euro. */
define('COT_EUR_ARS', 1580);

/**
 * [nombre, watts, precio ARS, vida util en horas, mantenimiento anual ARS, duracion]
 *
 * La ultima columna es a cuantos anos equivale esa vida util con un uso normal.
 * No entra en ninguna cuenta: se muestra al lado del campo para que la persona
 * sepa si el numero de horas le cierra con como usa ella la maquina.
 */
function cot_impresoras() {
    return [
        ['Bambu Lab A1 Mini',         45,  613890,  4000,  35000, '3 a 4 años'],
        ['Bambu Lab A1',              95,  883348,  5000,  45000, '4 a 5 años'],
        ['Bambu Lab P1P',             80, 1290500, 10000,  60000, '5 años'    ],
        ['Bambu Lab P1S',            100, 1407474, 10000,  65000, '5 años'    ],
        ['Bambu Lab P2S',            130, 2419980,  7500,  80000, '5 a 6 años'],
        ['Bambu Lab X1 Carbon',      120, 2900000,  5000,  95000, '6 a 7 años'],
        ['Bambu Lab H2S',            210, 5114980,  8000, 130000, '7+ años'   ],
        ['Bambu Lab H2D',            210, 6599980, 25000, 140000, '7+ años'   ],
        ['Bambu Lab H2C',            210, 8799990,  8000, 160000, '7+ años'   ],
        ['Prusa MK3S+',               80,  928990, 16000,  40000, '6 a 7 años'],
        ['Prusa MK4',                100, 1350000, 14000,  50000, '7+ años'   ],
        ['Creality Ender 3 V2',      110,  479000,  8000,  30000, '2 a 3 años'],
        ['Creality Ender 3 S1',      120,  370000, 14000,  35000, '3 a 4 años'],
        ['Creality K1',              100,  860000, 12000,  45000, '4 a 5 años'],
        ['Creality K1C',             100, 1160261, 15000,  50000, '5 años'    ],
        ['Creality K1 Max',          200, 1449607, 14000,  70000, '5 a 6 años'],
        ['Anycubic Kobra 2',          75,  520000,  4000,  35000, '3 a 4 años'],
        ['Anycubic Vyper',            80,  330000, 14000,  30000, '2 a 3 años'],
        ['SnapMaker U1',             130, 3539930, 20000,  90000, '5 años'    ],
        ['Elegoo Saturn 3 (resina)',  75, 1224868,  2500,  60000, '2 a 3 años'],
        ['Elegoo Saturn 4 (resina)',  75, 1333475,  2000,  65000, '2 a 3 años'],
        ['Voron 2.4 (350mm DIY)',    225, 2100000, 12000,  85000, '6 a 7 años'],
    ];
}

/**
 * Las mismas fichas, listas para el navegador: el nombre con los watts (que es
 * como se ve en la lista de siempre) y los importes en las tres monedas.
 */
function cot_impresoras_js() {
    // Redondeos para que el campo muestre un numero que una persona escribiria:
    // al millar en pesos, a la unidad en dolares y euros.
    $ars = fn($n) => (int) (round($n / 1000) * 1000);
    $usd = fn($n) => (int) round($n / COT_USD_ARS);
    $eur = fn($n) => (int) round($n / COT_EUR_ARS);

    $salida = [];
    foreach (cot_impresoras() as [$nombre, $watts, $costo, $vida, $mant, $duracion]) {
        $salida[] = [
            'n' => $nombre . ' (' . $watts . ' W)',
            'w' => $watts,
            'v' => $vida,
            'd' => $duracion,
            'c' => ['ARS' => $ars($costo), 'USD' => $usd($costo), 'EUR' => $eur($costo)],
            'm' => ['ARS' => $ars($mant),  'USD' => $usd($mant),  'EUR' => $eur($mant)],
        ];
    }
    return $salida;
}
