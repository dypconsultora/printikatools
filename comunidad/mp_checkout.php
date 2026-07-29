<?php
/**
 * Arranque del pago: crea la suscripción recurrente en Mercado Pago
 * y manda al usuario al checkout de MP.
 */
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/mp.php';

$u = usuario_actual();
if ($u === null) {
    header('Location: login.php');
    exit;
}
com_exigir_email_verificado();

$plan = ($_GET['plan'] ?? '') === 'anual' ? 'anual' : 'mensual';

// Con el mensual fuera de venta, un enlace guardado de antes no puede seguir
// dando de alta cobros mensuales: se lo manda a elegir de nuevo
if ($plan === 'mensual' && !COMUNIDAD_MENSUAL_VISIBLE) {
    header('Location: suscripcion.php?aviso=sin_mensual');
    exit;
}

$info = mp_planes()[$plan];

if (!mp_conectado()) {
    header('Location: suscripcion.php?aviso=sin_mp');
    exit;
}

$base = (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
[$code, $resp] = mp_api('POST', '/preapproval', [
    'reason'             => 'Printika Tools · ' . $info['titulo'],
    'external_reference' => (int) $u['id'] . ':' . $plan,
    'payer_email'        => $u['email'],
    'back_url'           => $base . '/comunidad/suscripcion.php?aviso=volviste',
    'auto_recurring'     => [
        'frequency'          => $info['meses'],
        'frequency_type'     => 'months',
        'transaction_amount' => (float) $info['monto'],
        'currency_id'        => 'ARS',
    ],
]);

if ($code >= 200 && $code < 300 && !empty($resp['init_point'])) {
    mp_log("checkout creado uid={$u['id']} plan=$plan preapproval={$resp['id']}");
    header('Location: ' . $resp['init_point']);
    exit;
}

mp_log("checkout ERROR uid={$u['id']} plan=$plan http=$code " . json_encode($resp));
header('Location: suscripcion.php?aviso=error_mp');
exit;
