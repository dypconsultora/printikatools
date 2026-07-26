<?php
/**
 * Portada en ingles: https://printikatools.com/en/
 *
 * No es una copia de la landing. Es la misma index.php de la raiz, servida en
 * otra direccion para que Google la indexe aparte; el texto se traduce solo,
 * con el diccionario de assets/lang/landing-en.json.
 *
 * Cualquier cambio en la landing se hace en /index.php y aparece en los dos
 * idiomas. Si se agrega texto nuevo, sumar su traduccion al diccionario.
 */
define('LANDING_EN', true);
require dirname(__DIR__) . '/index.php';
