<?php
/**
 * Terminos y Condiciones en ingles: https://printikatools.com/en/terminos/
 *
 * Es el mismo archivo de /terminos/, servido en otra direccion. El texto ingles
 * esta escrito adentro de ese archivo, seccion por seccion; aca solo se prende
 * la constante que lo activa.
 */
define('GUIAS_EN', true);
require dirname(__DIR__, 2) . '/terminos/index.php';
