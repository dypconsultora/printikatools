<?php
/**
 * PLANTILLA de configuración de la plataforma Comunidad.
 *
 * OPCIONAL: si este archivo no existe como comunidad/config.php, la plataforma
 * usa automáticamente comunidad/cotizador/config.php (misma base de datos).
 * Crealo solo si querés que la comunidad use OTRA base distinta a la del cotizador.
 *
 * El config.php real NO se versiona (está en .gitignore) — subilo a mano al servidor.
 */
define('DB_HOST', 'localhost');
define('DB_NAME', 'TU_BASE');
define('DB_USER', 'TU_USUARIO');
define('DB_PASS', 'TU_CLAVE');
define('DB_CHARSET', 'utf8mb4');
