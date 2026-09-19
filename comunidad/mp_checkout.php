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
$recurrente = [
    'frequency'          => $info['meses'],
    'frequency_type'     => 'months',
    'transaction_amount' => (float) $info['monto'],
    'currency_id'        => 'ARS',
];
// Promo del primer mes gratis (ver COMUNIDAD_PROMO_HASTA). Solo para quien
// nunca tuvo un plan pago: si no, cualquiera podria darse de baja y volver a
// suscribirse para no pagar un mes mas.
$conTrial = $plan === 'mensual' && mp_puede_mes_gratis((int) $u['id']);
if ($conTrial) {
    $recurrente['free_trial'] = ['frequency' => 1, 'frequency_type' => 'months'];
}
[$code, $resp] = mp_api('POST', '/preapproval', [
    'reason'             => 'Printika Tools · ' . $info['titulo'] . ($conTrial ? ' (1er mes gratis)' : ''),
    'external_reference' => (int) $u['id'] . ':' . $plan,
    'payer_email'        => $u['email'],
    'back_url'           => $base . '/comunidad/suscripcion.php?aviso=volviste',
    'auto_recurring'     => $recurrente,
]);

if ($code >= 200 && $code < 300 && !empty($resp['init_point'])) {
    // Con la promo se anota si MP acepto el mes gratis: esta funcion no esta
    // documentada para suscripciones sin plan, asi que esta linea es la prueba.
    $trial = '';
    if ($conTrial) {
        $trial = ' trial=' . (!empty($resp['auto_recurring']['free_trial']) ? 'SI' : 'NO')
               . ' primer_cobro=' . ($resp['next_payment_date'] ?? '?');
    }
    mp_log("checkout creado uid={$u['id']} plan=$plan preapproval={$resp['id']}$trial");
    header('Location: ' . $resp['init_point']);
    exit;
}

mp_log("checkout ERROR uid={$u['id']} plan=$plan http=$code " . json_encode($resp));
header('Location: suscripcion.php?aviso=error_mp');
exit;
