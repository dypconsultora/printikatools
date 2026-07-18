<?php
/**
 * PLANTILLA de configuración de la Calculadora.
 * Copiá este archivo como  config.php  y completá con los datos reales
 * de la base de datos MySQL del hosting (cPanel → Bases de datos MySQL).
 *
 * El config.php real NO se versiona (está en .gitignore) — subilo a mano al servidor.
 */
define('DB_HOST', 'localhost');            // normalmente "localhost"
define('DB_NAME', 'TU_BASE');              // ej: cpaneluser_dyp_calc3d
define('DB_USER', 'TU_USUARIO');           // usuario MySQL
define('DB_PASS', 'TU_CLAVE');             // contraseña del usuario MySQL
define('DB_CHARSET', 'utf8mb4');
define('APP_NOMBRE', 'Calculadora 3D · Printika');
define('APP_SECRET', 'CAMBIAR-por-un-texto-largo-y-aleatorio-unico'); // cadena secreta random
