<?php
/**
 * Suscripción a novedades (popup del cotizador).
 * Recibe POST JSON {email} y lo envía a la casilla de la web
 * usando el mismo SMTP del formulario de contacto (raíz + .env).
 */

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
iniciar_sesion();

header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Metodo no permitido']);
    exit;
}

// CSRF de la sesión del cotizador
$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!verificar_csrf($token)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Sesion invalida, recarga la pagina']);
    exit;
}

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true) ?? [];

$email = trim((string) ($data['email'] ?? ''));
$honey = trim((string) ($data['website'] ?? '')); // honeypot antispam

// Bot: aceptar en silencio
if ($honey !== '') {
    echo json_encode(['ok' => true]);
    exit;
}

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Email invalido']);
    exit;
}

// Límite por sesión (anti abuso)
$_SESSION['news_envios'] = ($_SESSION['news_envios'] ?? 0) + 1;
if ($_SESSION['news_envios'] > 3) {
    http_response_code(429);
    echo json_encode(['ok' => false, 'error' => 'Demasiados intentos']);
    exit;
}

// SMTP de la web (config de la raíz, lee el .env)
$config = require dirname(__DIR__, 2) . '/config.php';
require dirname(__DIR__, 2) . '/lib/PHPMailer/Exception.php';
require dirname(__DIR__, 2) . '/lib/PHPMailer/PHPMailer.php';
require dirname(__DIR__, 2) . '/lib/PHPMailer/SMTP.php';

$mail = new PHPMailer\PHPMailer\PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = $config['smtp_host'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $config['smtp_user'];
    $mail->Password   = $config['smtp_pass'];
    $mail->SMTPSecure = $config['smtp_secure'];
    $mail->Port       = (int) $config['smtp_port'];
    $mail->CharSet    = 'UTF-8';

    $mail->setFrom($config['from_email'], $config['from_name']);
    $mail->addAddress($config['to_email'], $config['to_name']);
    $mail->addReplyTo($email);

    $mail->isHTML(true);
    $mail->Subject = 'Nuevo suscriptor a novedades — Cotizador 3D';

    $emailHtml = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
    $fecha     = date('d/m/Y H:i');

    $mail->Body = '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;">'
        . '<h2 style="color:#111;border-bottom:2px solid #00D4FF;padding-bottom:8px;">Nuevo suscriptor del cotizador</h2>'
        . '<p style="color:#222;">Alguien dej&oacute; su email en el popup de novedades del cotizador:</p>'
        . '<p style="padding:12px;background:#f8f8f8;border-left:3px solid #00D4FF;color:#111;font-size:16px;font-weight:bold;">' . $emailHtml . '</p>'
        . '<p style="color:#999;font-size:12px;margin-top:24px;">Enviado desde printika3d.com/cotizador — ' . $fecha . '</p>'
        . '</div>';

    $mail->AltBody = "Nuevo suscriptor a novedades del cotizador:\n$email\n($fecha)";

    $mail->send();
    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    error_log('[suscribir.php] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'No se pudo enviar, proba de nuevo']);
}
