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
function correo_plantilla($titulo, $parrafos, $boton = null, $pie = '', $extra = '', $idioma = 'es') {
    $esc = fn($t) => htmlspecialchars($t, ENT_QUOTES, 'UTF-8');
    $en  = $idioma === 'en';
    $bajada = $en ? '3D printing tools and community'
                  : 'Herramientas y comunidad de impresión 3D';
    $cuerpo = '';
    foreach ((array) $parrafos as $p) {
        $cuerpo .= '<p style="margin:0 0 16px;font-size:15px;line-height:1.65;color:#3d4759">' . $p . '</p>';
    }
    // $extra va entero, sin envolver en <p>: es para tablas (la de planes, por
    // ejemplo), que dentro de un parrafo rompen en Outlook.
    $cuerpo .= $extra;
    $btn = '';
    if ($boton) {
        // Texto blanco sobre un azul mas profundo que el celeste de la marca:
        // sobre el celeste claro el blanco queda lavado y casi no se lee
        $btn = '<table role="presentation" cellpadding="0" cellspacing="0" style="margin:26px 0">
            <tr><td style="border-radius:10px;background:#0c7ab8">
              <a href="' . $esc($boton['url']) . '" style="display:inline-block;padding:14px 32px;
                 font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:bold;
                 color:#ffffff;text-decoration:none;border-radius:10px">' . $esc($boton['texto']) . '</a>
            </td></tr></table>';
    }
    $piehtml = $pie ? '<p style="margin:22px 0 0;font-size:12.5px;line-height:1.6;color:#8a95a8">' . $pie . '</p>' : '';

    return '<!DOCTYPE html>
<html lang="' . ($en ? 'en' : 'es') . '"><head><meta charset="UTF-8">
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
            Printika Tools · ' . $bajada . '<br>
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
 * Los tres planes, con las MISMAS palabras que la seccion Planes de la landing.
 *
 * Se escriben una sola vez y en castellano; para el ingles se traducen con el
 * mismo diccionario que usa la landing (assets/lang/landing-en.json). De esta
 * forma el correo no puede terminar diciendo algo distinto de lo que la persona
 * ve en la web, que es como aparecieron los precios desactualizados de antes.
 *
 * Si se cambia un plan en index.php, hay que cambiarlo aca tambien: son dos
 * lugares, pero las palabras son las mismas y el diccionario ya las cubre.
 */
function correo_planes($en) {
    $dic = [];
    if ($en) {
        $json = @file_get_contents(dirname(__DIR__, 2) . '/assets/lang/landing-en.json');
        if ($json !== false) $dic = json_decode($json, true) ?: [];
    }
    $tr = fn($t) => $dic[$t] ?? $t;

    $monto = fn($ars, $usd) => $en ? $usd : '$' . number_format($ars, 0, ',', '.');

    return [
        [
            'nombre'  => 'Printika Free',
            'precio'  => $en ? 'US$0' : '$0',
            'periodo' => '',
            'nota'    => $tr('Para probar y empezar'),
            'items'   => array_map($tr, [
                'Calculadora de costos online',
                'Cálculo en ARS, USD y EUR',
                'Recursos en videos y PDF',
            ]),
        ],
        // El mensual desaparece del correo si no esta a la venta (bootstrap.php)
        ...(COMUNIDAD_MENSUAL_VISIBLE ? [[
            'nombre'  => 'Printika Pro',
            'precio'  => $monto(COMUNIDAD_PRECIO_MENSUAL, 'US$15'),
            'periodo' => $tr('/mes'),
            'nota'    => $tr('Renovación mes a mes, sin permanencia'),
            'items'   => array_map($tr, [
                'Calculadora completa (versión PRO)',
                'Mi Taller: presupuestos, clientes y stock',
                'Librería STL y estadísticas',
                'Tus datos guardados en tu cuenta',
                'Soporte técnico prioritario',
                'Herramientas nuevas cada mes',
            ]),
        ]] : []),
        [
            'nombre'    => $tr('Printika Pro Anual'),
            'precio'    => $monto(COMUNIDAD_PRECIO_ANUAL, 'US$150'),
            'periodo'   => $tr('/año'),
            'etiqueta'  => $tr('2 meses gratis'),
            'nota'      => $tr('Un solo pago y te olvidás todo el año'),
            'destacado' => true,
            'items'     => array_map($tr, COMUNIDAD_MENSUAL_VISIBLE ? [
                'Todo lo del plan mensual',
                $en ? '2 meses sin cargo (US$30 de ahorro)' : '2 meses sin cargo ($36.000 de ahorro)',
                'Precio congelado por 12 meses',
                'Acceso anticipado a herramientas nuevas',
            ] : [
                'Calculadora completa (versión PRO)',
                'Mi Taller: presupuestos, clientes y stock',
                'Librería STL y estadísticas',
                $en ? '2 meses sin cargo (US$30 de ahorro)' : '2 meses sin cargo ($36.000 de ahorro)',
                'Precio congelado por 12 meses',
                'Soporte técnico prioritario',
            ]),
        ],
    ];
}

/**
 * Una tarjeta de plan del correo. Todo en linea y con tablas porque es lo unico
 * que Gmail y Outlook respetan; el tilde va como texto y no como imagen para
 * que se vea aunque el correo bloquee las imagenes.
 */
function correo_plan_fila($plan) {
    $destacado = !empty($plan['destacado']);
    $borde = $destacado ? '#2db7fa' : '#e3eaf3';
    $fondo = $destacado ? '#f4fbff' : '#ffffff';

    $etiqueta = '';
    if (!empty($plan['etiqueta'])) {
        $etiqueta = '<span style="display:inline-block;margin-bottom:8px;padding:3px 10px;
                     border-radius:99px;background:#2db7fa;color:#06202f;
                     font-size:11px;font-weight:bold">' . $plan['etiqueta'] . '</span><br>';
    }

    $items = '';
    foreach ($plan['items'] as $i) {
        $items .= '<tr>
            <td valign="top" style="padding:3px 8px 3px 0;font-size:13px;color:#2db7fa;font-weight:bold">&#10003;</td>
            <td style="padding:3px 0;font-size:13px;line-height:1.5;color:#3d4759">' . $i . '</td>
          </tr>';
    }

    $periodo = $plan['periodo'] !== ''
        ? ' <span style="font-size:13px;font-weight:normal;color:#5b6779">' . $plan['periodo'] . '</span>'
        : '';

    return '<tr><td style="padding:0 0 12px">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
             style="background:' . $fondo . ';border:1px solid ' . $borde . ';border-radius:12px">
        <tr><td style="padding:16px 18px">
          ' . $etiqueta . '
          <table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>
            <td style="font-size:16px;font-weight:bold;color:#131a27">' . $plan['nombre'] . '</td>
            <td align="right" style="white-space:nowrap;font-size:17px;font-weight:bold;color:#131a27">'
              . $plan['precio'] . $periodo . '</td>
          </tr></table>
          <p style="margin:4px 0 12px;font-size:13px;color:#8a95a8">' . $plan['nota'] . '</p>
          <table role="presentation" cellpadding="0" cellspacing="0">' . $items . '</table>
        </td></tr>
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
        $asunto  = 'The calculator is ready. What is missing is the workshop. PrintikaTools';
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
        $boton = ['url' => $alta, 'texto' => 'Create my free account'];
        $pie   = 'You are getting this because you left your address at the calculator on
                  printikatools.com. If it was not you, just ignore it &mdash; we will not
                  write again.';
        $texto = "We got your email.\n\n"
               . "Create your free Printika Tools account: $alta\n\n"
               . 'Free US$0 · '
               . (COMUNIDAD_MENSUAL_VISIBLE ? 'Pro monthly US$15 · ' : '')
               . 'Pro yearly US$150 (2 months free)';
    } else {
        $asunto  = 'El cotizador ya está. Lo que falta es el taller. PrintikaTools';
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
        $boton = ['url' => $alta, 'texto' => 'Crear mi cuenta gratis'];
        $pie   = 'Recibís este correo porque dejaste tu dirección en el cotizador de
                  printikatools.com. Si no fuiste vos, ignoralo: no te volvemos a escribir.';
        $texto = "Recibimos tu correo.\n\n"
               . "Crea tu cuenta gratis de Printika Tools: $alta\n\n"
               . 'Gratis $0 · '
               . (COMUNIDAD_MENSUAL_VISIBLE
                    ? 'Pro mensual ' . $precio(COMUNIDAD_PRECIO_MENSUAL) . '/mes · ' : '')
               . 'Pro anual ' . $precio(COMUNIDAD_PRECIO_ANUAL) . "/año (2 meses de regalo)";
    }

    $filas = '';
    foreach (correo_planes($en) as $p) $filas .= correo_plan_fila($p);
    $tabla = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                     style="margin:6px 0 2px">' . $filas . '</table>';

    return [
        'asunto' => $asunto,
        'html'   => correo_plantilla($titulo, $parrafos, $boton, $pie, $tabla, $idioma),
        'texto'  => $texto,
    ];
}

/** Manda el correo de bienvenida a una direccion. */
function correo_bienvenida_novedades($email, $idioma = 'es', &$error = null) {
    $c = correo_bienvenida_partes($idioma);
    return correo_enviar($email, '', $c['asunto'], $c['html'], $c['texto'], $error);
}
