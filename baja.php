<?php
/**
 * Baja de la lista de novedades.
 *
 * El enlace llega en el pie de los correos, firmado: sin la firma correcta no
 * se borra nada, para que nadie pueda dar de baja a otro escribiendo su
 * direccion en la barra del navegador.
 *
 * Da de baja de una, sin pedir confirmacion. Es lo que piden Gmail y Yahoo (un
 * solo clic) y ademas es lo correcto: obligar a confirmar hace que la gente
 * marque el correo como spam en vez de darse de baja, y eso nos arruina la
 * entrega a todos los demas.
 *
 * Atiende GET (la persona toco el enlace) y POST (el boton "Cancelar
 * suscripcion" que Gmail muestra arriba del mensaje).
 */
require_once __DIR__ . '/comunidad/inc/auth.php';
require_once __DIR__ . '/comunidad/inc/ui.php';
require_once __DIR__ . '/comunidad/inc/taller.php';

$email = trim((string) ($_GET['e'] ?? $_POST['e'] ?? ''));
$token = trim((string) ($_GET['t'] ?? $_POST['t'] ?? ''));
$en    = taller_idioma() === 'en';

$estado = 'invalido';   // invalido | listo | noestaba | error

if ($email !== '' && $token !== '' && hash_equals(com_baja_token($email), $token)) {
    try {
        taller_migrar();
        $stmt = com_db()->prepare('DELETE FROM novedades_emails WHERE email = ?');
        $stmt->execute([$email]);
        $estado = $stmt->rowCount() > 0 ? 'listo' : 'noestaba';
    } catch (Throwable $e) {
        error_log('[baja] ' . $e->getMessage());
        $estado = 'error';
    }
}

// Gmail avisa por POST y no espera una pagina: con el codigo alcanza
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    http_response_code($estado === 'error' || $estado === 'invalido' ? 400 : 200);
    header('Content-Type: text/plain; charset=utf-8');
    echo $estado === 'listo' || $estado === 'noestaba' ? 'OK' : 'ERROR';
    exit;
}

if ($estado === 'invalido') http_response_code(400);
header('X-Robots-Tag: noindex');

$T = $en ? [
    'listo_t'    => 'You are unsubscribed',
    'listo_p'    => 'We removed %s from the list. You will not get any more news from us.',
    'noestaba_t' => 'That address is not on the list',
    'noestaba_p' => 'Either you already unsubscribed, or it was never on the list. Either way, we will not write to you.',
    'invalido_t' => 'This link is not valid',
    'invalido_p' => 'It may be incomplete. Copy the full link from the email, or write to us and we will remove you by hand.',
    'error_t'    => 'We could not do it right now',
    'error_p'    => 'Try again in a few minutes. If it keeps happening, write to us and we will remove you by hand.',
    'volver'     => 'Go to the site',
    'nota'       => 'This does not touch your Printika Tools account, if you have one.',
] : [
    'listo_t'    => 'Listo, te diste de baja',
    'listo_p'    => 'Sacamos %s de la lista. No te llegan más novedades nuestras.',
    'noestaba_t' => 'Esa dirección no está en la lista',
    'noestaba_p' => 'O ya te diste de baja antes, o nunca estuvo anotada. En cualquier caso, no te vamos a escribir.',
    'invalido_t' => 'Este enlace no es válido',
    'invalido_p' => 'Puede que esté cortado. Copiá el enlace entero desde el correo, o escribinos y te sacamos a mano.',
    'error_t'    => 'No pudimos hacerlo ahora',
    'error_p'    => 'Probá de nuevo en unos minutos. Si sigue pasando, escribinos y te sacamos a mano.',
    'volver'     => 'Ir al sitio',
    'nota'       => 'Esto no toca tu cuenta de Printika Tools, si es que tenés una.',
];

$titulo = $T[$estado . '_t'];
$texto  = $estado === 'listo'
    ? sprintf($T['listo_p'], $email)
    : $T[$estado . '_p'];

ui_tarjeta_inicio($titulo);
?>
    <style>
      .baja-ico{display:flex;align-items:center;justify-content:center;width:64px;height:64px;
                margin:0 auto 18px;border-radius:50%;
                background:var(--<?php echo $estado === 'listo' ? 'ok' : 'warn'; ?>-tinte);
                color:var(--<?php echo $estado === 'listo' ? 'ok' : 'warn'; ?>)}
      .tarjeta h1{text-align:center}
      .baja-txt{text-align:center;font-size:14.5px;line-height:1.6;color:var(--txt-2);margin:6px 0 22px}
      .baja-nota{text-align:center;font-size:12.5px;color:var(--txt-3);margin-top:16px}
    </style>
    <div class="baja-ico"><?php echo ui_icono($estado === 'listo' ? 'check' : 'alerta', 30); ?></div>
    <h1><?php echo htmlspecialchars($titulo); ?></h1>
    <p class="baja-txt"><?php echo htmlspecialchars($texto); ?></p>
    <a class="btn" href="/" style="width:100%"><?php echo htmlspecialchars($T['volver']); ?></a>
    <p class="baja-nota"><?php echo htmlspecialchars($T['nota']); ?></p>
<?php ui_tarjeta_fin(); ?>
