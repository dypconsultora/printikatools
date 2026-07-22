<?php
/**
 * Webhook de Mercado Pago: MP avisa acá cada novedad de una suscripción
 * (autorizada, pago acreditado, cancelada). Activamos o damos de baja
 * el plan automáticamente. Siempre respondemos 200 para que MP no reintente
 * de más; el doble seguro es el vencimiento guardado en nuestra base.
 */
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/taller.php';
require_once __DIR__ . '/inc/mp.php';

http_response_code(200);
header('Content-Type: application/json');
echo '{"ok":true}';
// Seguir procesando después de responder
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
}

if (!mp_conectado()) exit;
taller_migrar();

$cuerpo = json_decode(file_get_contents('php://input'), true) ?: [];
$tipo = $cuerpo['type'] ?? ($_GET['type'] ?? ($_GET['topic'] ?? ''));
$id   = $cuerpo['data']['id'] ?? ($_GET['data_id'] ?? ($_GET['id'] ?? ''));
mp_log("webhook tipo=$tipo id=$id");
if ($id === '') exit;

$preapproval = null;

if (stripos($tipo, 'preapproval') !== false || $tipo === 'subscription_preapproval') {
    [$code, $preapproval] = mp_api('GET', '/preapproval/' . rawurlencode($id));
    if ($code !== 200) { mp_log("no pude leer preapproval $id (http $code)"); exit; }
} elseif ($tipo === 'payment') {
    // Un pago suelto: buscar la suscripción a la que pertenece
    [$code, $pago] = mp_api('GET', '/v1/payments/' . rawurlencode($id));
    if ($code !== 200) { mp_log("no pude leer pago $id (http $code)"); exit; }
    $preId = $pago['metadata']['preapproval_id'] ?? ($pago['point_of_interaction']['transaction_data']['subscription_id'] ?? '');
    if ($preId === '' || ($pago['status'] ?? '') !== 'approved') exit;
    [$code, $preapproval] = mp_api('GET', '/preapproval/' . rawurlencode($preId));
    if ($code !== 200) exit;
} else {
    exit;
}

// external_reference = "usuario_id:plan"
$ref = explode(':', (string) ($preapproval['external_reference'] ?? ''));
$uid  = (int) ($ref[0] ?? 0);
$plan = ($ref[1] ?? '') === 'anual' ? 'anual' : 'mensual';
$estadoMp = $preapproval['status'] ?? '';
if ($uid <= 0) { mp_log('preapproval sin referencia de usuario'); exit; }

if ($estadoMp === 'authorized') {
    mp_activar_plan($uid, $plan, 'MP ' . ($preapproval['id'] ?? ''));
    mp_log("plan $plan ACTIVADO/renovado uid=$uid");
} elseif (in_array($estadoMp, ['cancelled', 'paused'], true)) {
    mp_cancelar_plan($uid);
    mp_log("plan CANCELADO uid=$uid (estado MP: $estadoMp)");
} else {
    mp_log("estado MP sin acción: $estadoMp uid=$uid");
}
