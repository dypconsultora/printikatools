<?php
/**
 * Envío de correos de la plataforma (verificación de email, avisos).
 * Usa el mismo SMTP del sitio: config.php de la raíz, que lee el .env.
 */
require_once __DIR__ . '/bootstrap.php';

/** Config SMTP de la raíz (null si no está disponible). */
function correo_config() {
    static $cfg = false;
    if ($cfg !== false) return $cfg;
    $ruta = dirname(__DIR__, 2) . '/config.php';
    $cfg = is_readable($ruta) ? require $ruta : null;
    if (is_array($cfg) && empty($cfg['smtp_host'])) $cfg = null;
    return $cfg;
}

/** true si el envío de correos está configurado. */
function correo_disponible() { return correo_config() !== null; }

/**
 * Plantilla HTML de los correos: logo de Printika, título, cuerpo y un
 * botón grande. Tablas y estilos en línea porque es lo único que
 * renderizan bien Gmail, Outlook y compañía.
 */
function correo_plantilla($titulo, $parrafos, $boton = null, $pie = '') {
    $esc = fn($t) => htmlspecialchars($t, ENT_QUOTES, 'UTF-8');
    $cuerpo = '';
    foreach ((array) $parrafos as $p) {
        $cuerpo .= '<p style="margin:0 0 16px;font-size:15px;line-height:1.65;color:#3d4759">' . $p . '</p>';
    }
    $btn = '';
    if ($boton) {
        $btn = '<table role="presentation" cellpadding="0" cellspacing="0" style="margin:26px 0">
            <tr><td style="border-radius:10px;background:#2db7fa">
              <a href="' . $esc($boton['url']) . '" style="display:inline-block;padding:14px 32px;
                 font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:bold;
                 color:#06202f;text-decoration:none;border-radius:10px">' . $esc($boton['texto']) . '</a>
            </td></tr></table>';
    }
    $piehtml = $pie ? '<p style="margin:22px 0 0;font-size:12.5px;line-height:1.6;color:#8a95a8">' . $pie . '</p>' : '';

    return '<!DOCTYPE html>
<html lang="es"><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#eef2f7;font-family:Arial,Helvetica,sans-serif">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eef2f7;padding:32px 16px">
    <tr><td align="center">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
             style="max-width:560px;background:#ffffff;border-radius:16px;overflow:hidden;
                    box-shadow:0 2px 14px rgba(18,28,45,.08)">
        <tr><td align="center" style="background:#131a27;padding:28px 24px">
          <img src="cid:logoprintika" alt="Printika Tools" width="240"
               style="display:block;width:240px;max-width:70%;height:auto;border:0">
        </td></tr>
        <tr><td style="padding:32px 34px">
          <h1 style="margin:0 0 18px;font-size:21px;line-height:1.3;color:#131a27">' . $esc($titulo) . '</h1>
          ' . $cuerpo . $btn . $piehtml . '
        </td></tr>
        <tr><td style="background:#f6f9fd;padding:18px 34px;border-top:1px solid #e3eaf3">
          <p style="margin:0;font-size:12px;line-height:1.6;color:#8a95a8">
            Printika Tools · Herramientas y comunidad de impresión 3D<br>
            <a href="https://printikatools.com/" style="color:#2db7fa;text-decoration:none">printikatools.com</a>
          </p>
        </td></tr>
      </table>
    </td></tr>
  </table>
</body></html>';
}

/**
 * Envía un correo HTML con el logo incrustado.
 * Devuelve true si salió; false si falló (y deja el motivo en $error).
 */
function correo_enviar($para, $nombre, $asunto, $html, $texto, &$error = null) {
    $cfg = correo_config();
    if (!$cfg) { $error = 'El envío de correos no está configurado.'; return false; }

    $base = dirname(__DIR__, 2);
    require_once $base . '/lib/PHPMailer/Exception.php';
    require_once $base . '/lib/PHPMailer/PHPMailer.php';
    require_once $base . '/lib/PHPMailer/SMTP.php';

    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = $cfg['smtp_host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $cfg['smtp_user'];
        $mail->Password   = $cfg['smtp_pass'];
        $mail->SMTPSecure = $cfg['smtp_secure'];
        $mail->Port       = (int) $cfg['smtp_port'];
        $mail->CharSet    = 'UTF-8';
        $mail->Timeout    = 15;

        $mail->setFrom($cfg['from_email'], 'Printika Tools');
        $mail->addAddress($para, $nombre !== '' ? $nombre : $para);

        $logo = $base . '/assets/img/printika-tools-mail.png';
        if (is_readable($logo)) {
            $mail->addEmbeddedImage($logo, 'logoprintika', 'printika-tools.png');
        }

        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body    = $html;
        $mail->AltBody = $texto;
        $mail->send();
        return true;
    } catch (Throwable $e) {
        $error = 'No se pudo enviar el correo.';
        return false;
    }
}
