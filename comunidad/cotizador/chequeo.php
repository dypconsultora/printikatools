<?php
/**
 * Chequeo temporal — BORRAR después de usar.
 * Visitar: https://printikatools.com/comunidad/cotizador/chequeo.php
 * No requiere config.php de forma fatal: reporta qué falta.
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');
header('Content-Type: text/plain; charset=utf-8');

echo "PHP: " . PHP_VERSION . "\n\n";

$cfg = __DIR__ . '/config.php';
echo "config.php existe?  " . (file_exists($cfg) ? "SI" : "NO") . "\n";
if (!file_exists($cfg)) {
    echo "\n>>> FALTA config.php. Copiá config.example.php a config.php, completá los datos de la base y volvé a probar.\n";
    exit;
}

require $cfg;
echo "DB_HOST: " . (defined('DB_HOST') ? DB_HOST : '(no def)') . "\n";
echo "DB_NAME: " . (defined('DB_NAME') ? DB_NAME : '(no def)') . "\n";
echo "DB_USER: " . (defined('DB_USER') ? DB_USER : '(no def)') . "\n";
echo "DB_PASS: " . (defined('DB_PASS') ? '(definida, ' . strlen((string)DB_PASS) . ' chars)' : '(no def)') . "\n\n";

echo "Conectando a MySQL...\n";
try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    echo "CONEXION OK — la base responde ✓\n";
    echo "\n>>> Todo bien. Ya podés usar install.php. Después borrá chequeo.php.\n";
} catch (Throwable $e) {
    echo "FALLO: " . $e->getMessage() . "\n\n";
    echo "Pistas:\n";
    echo "- 'Access denied' -> usuario o clave mal.\n";
    echo "- 'Unknown database' -> nombre de base mal (fijate el prefijo en cPanel).\n";
    echo "- 'Connection refused' / 'No such file' -> cambiá DB_HOST a 127.0.0.1 (o al host que muestra cPanel).\n";
}
