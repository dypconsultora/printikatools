<?php
/**
 * Carga la configuración SMTP desde el archivo .env (no versionado).
 * Las credenciales viven SOLO en .env; este archivo no contiene secretos.
 */

$env = [];
$envFile = __DIR__ . '/.env';
if (is_readable($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }
        $pos = strpos($line, '=');
        if ($pos === false) {
            continue;
        }
        $key = trim(substr($line, 0, $pos));
        $val = trim(substr($line, $pos + 1));
        // Quitar comillas envolventes si las hubiera
        if (strlen($val) >= 2 && ($val[0] === '"' || $val[0] === "'") && substr($val, -1) === $val[0]) {
            $val = substr($val, 1, -1);
        }
        $env[$key] = $val;
    }
}

$get = function ($key, $default = '') use ($env) {
    return (isset($env[$key]) && $env[$key] !== '') ? $env[$key] : $default;
};

return [
    'smtp_host'      => $get('SMTP_HOST'),
    'smtp_port'      => (int) $get('SMTP_PORT', '465'),
    'smtp_secure'    => $get('SMTP_SECURE', 'ssl'),
    'smtp_user'      => $get('SMTP_USER'),
    'smtp_pass'      => $get('SMTP_PASS'),

    'from_email'     => $get('MAIL_FROM', $get('SMTP_USER')),
    'from_name'      => $get('MAIL_FROM_NAME', 'Printika 3D'),

    'to_email'       => $get('MAIL_TO', $get('SMTP_USER')),
    'to_name'        => $get('MAIL_TO_NAME', 'Printika 3D'),

    'allowed_origin' => $get('ALLOWED_ORIGIN', ''),
];
