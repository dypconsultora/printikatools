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
function correo_plantilla($titulo, $parrafos, $boton = null, $pie = '', $extra = '') {
    $esc = fn($t) => htmlspecialchars($t, ENT_QUOTES, 'UTF-8');
    $cuerpo = '';
    foreach ((array) $parrafos as $p) {
        $cuerpo .= '<p style="margin:0 0 16px;font-size:15px;line-height:1.65;color:#3d4759">' . $p . '</p>';
    }
    // $extra va entero, sin envolver en <p>: es para tablas (la de planes, por
    // ejemplo), que dentro de un parrafo rompen en Outlook.
    $cuerpo .= $extra;
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

/**
 * Una fila de la tabla de planes del correo de bienvenida.
 * Todo en linea y con tablas porque es lo unico que Gmail y Outlook respetan.
 */
function correo_plan_fila($nombre, $precio, $detalle, $destacado = false) {
    $borde = $destacado ? '#2db7fa' : '#e3eaf3';
    $fondo = $destacado ? '#f2fbff' : '#ffffff';
    return '<tr><td style="padding:0 0 10px">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
             style="background:' . $fondo . ';border:1px solid ' . $borde . ';border-radius:12px">
        <tr>
          <td style="padding:14px 16px">
            <span style="font-size:15px;font-weight:bold;color:#131a27">' . $nombre . '</span><br>
            <span style="font-size:13px;line-height:1.5;color:#5b6779">' . $detalle . '</span>
          </td>
          <td align="right" style="padding:14px 16px;white-space:nowrap;
                                   font-size:15px;font-weight:bold;color:#131a27">' . $precio . '</td>
        </tr>
      </table>
    </td></tr>';
}

/**
 * Correo de bienvenida al que deja su direccion en el popup del cotizador.
 *
 * No es un "gracias por suscribirte" y nada mas: le confirma que quedo anotado
 * y aprovecha el unico momento en que nos esta prestando atencion para
 * contarle que la cuenta gratis existe. Por eso muestra los tres planes con el
 * gratuito primero: el objetivo es que se registre, no que lea.
 *
 * $idioma: 'es' o 'en' (el cotizador tiene las dos versiones).
 *
 * Devuelve ['asunto', 'html', 'texto']. Va separado del envio para poder
 * mirar como queda el correo sin mandarselo a nadie.
 */
function correo_bienvenida_partes($idioma = 'es') {
    $en = $idioma === 'en';
    // El alta es la misma en los dos idiomas: la plataforma se traduce sola
    $alta = 'https://printikatools.com/comunidad/registro.php';

    $precio = fn($n) => '$' . number_format($n, 0, ',', '.');

    if ($en) {
        $asunto  = 'Your quote is done. Your workshop is the part that is missing.';
        $titulo  = 'We got your email';
        $parrafos = [
            'Thanks for leaving it. You are on the list, and you will be the first to know
             about every new tool we release.',
            'In the meantime, something you may not know: the calculator you were using is
             only one piece. With a Printika Tools account the price stops being a loose
             number and becomes <strong>your whole workshop running</strong> &mdash; quotes
             with your own logo, clients, filament stock, sales and statistics, all in the
             same place.',
            '<strong>Creating the account is free and we do not ask for a card.</strong>',
        ];
        $planes = correo_plan_fila('Free', 'US$0',
                    'The full calculator, quotes and your first clients.')
                . correo_plan_fila('Pro monthly', 'US$15',
                    'The whole workshop: stock, sales, statistics, STL library and the guides.')
                . correo_plan_fila('Pro yearly', 'US$150',
                    'Same as monthly, with 2 months on us. Most people pick this one.', true);
        $boton = ['url' => $alta, 'texto' => 'Create my free account'];
        $pie   = 'You are getting this because you left your address at the calculator on
                  printikatools.com. If it was not you, just ignore it &mdash; we will not
                  write again.';
        $texto = "We got your email.\n\n"
               . "Create your free Printika Tools account: $alta\n\n"
               . "Free US\$0 · Pro monthly US\$15 · Pro yearly US\$150 (2 months free)";
    } else {
        $asunto  = 'Tu cotización ya está. Lo que falta es el taller.';
        $titulo  = 'Recibimos tu correo';
        $parrafos = [
            'Gracias por dejárnoslo. Ya quedaste anotado y vas a ser de los primeros en
             enterarte de cada herramienta nueva que saquemos.',
            'Mientras tanto, algo que quizás no sabías: el cotizador que estabas usando es
             sólo una parte. Con una cuenta de Printika Tools el precio deja de ser un
             cálculo suelto y pasa a ser <strong>tu taller entero funcionando</strong>:
             presupuestos con tu logo, clientes, stock de filamento, ventas y estadísticas,
             todo en el mismo lugar.',
            '<strong>Crear la cuenta es gratis y no te pedimos tarjeta.</strong>',
        ];
        $planes = correo_plan_fila('Gratis', '$0',
                    'El cotizador completo, presupuestos y tus primeros clientes.')
                . correo_plan_fila('Pro mensual', $precio(COMUNIDAD_PRECIO_MENSUAL) . '/mes',
                    'Todo el taller: stock, ventas, estadísticas, librería STL y las guías.')
                . correo_plan_fila('Pro anual', $precio(COMUNIDAD_PRECIO_ANUAL) . '/año',
                    'Lo mismo, con 2 meses de regalo. Es el que más eligen.', true);
        $boton = ['url' => $alta, 'texto' => 'Crear mi cuenta gratis'];
        $pie   = 'Recibís este correo porque dejaste tu dirección en el cotizador de
                  printikatools.com. Si no fuiste vos, ignoralo: no te volvemos a escribir.';
        $texto = "Recibimos tu correo.\n\n"
               . "Crea tu cuenta gratis de Printika Tools: $alta\n\n"
               . 'Gratis $0 · Pro mensual ' . $precio(COMUNIDAD_PRECIO_MENSUAL) . '/mes · '
               . 'Pro anual ' . $precio(COMUNIDAD_PRECIO_ANUAL) . "/año (2 meses de regalo)";
    }

    $tabla = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                     style="margin:6px 0 2px">' . $planes . '</table>';

    return [
        'asunto' => $asunto,
        'html'   => correo_plantilla($titulo, $parrafos, $boton, $pie, $tabla),
        'texto'  => $texto,
    ];
}

/** Manda el correo de bienvenida a una direccion. */
function correo_bienvenida_novedades($email, $idioma = 'es', &$error = null) {
    $c = correo_bienvenida_partes($idioma);
    return correo_enviar($email, '', $c['asunto'], $c['html'], $c['texto'], $error);
}
