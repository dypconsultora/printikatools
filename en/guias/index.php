<?php
/**
 * Listado de guias en ingles. Es el mismo archivo de /guias/, servido en otra
 * direccion; el texto se traduce solo con assets/lang/guias-en.json.
 */
define('GUIAS_EN', true);
require dirname(__DIR__, 2) . '/guias/index.php';
