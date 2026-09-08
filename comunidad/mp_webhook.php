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

// Solo aceptamos avisos firmados por Mercado Pago (evita que un tercero
// falsifique notificaciones). El secreto se carga en Admin > Mercado Pago.
if (!mp_firma_valida($id)) {
    mp_log("webhook RECHAZADO (firma invalida) tipo=$tipo id=$id ip=" . com_ip());
    exit;
}

mp_log("webhook tipo=$tipo id=$id");
if ($id === '') exit;

$preapproval = null;

if (stripos($tipo, 'preapproval') !== false || $tipo === 'subscription_preapproval') {
    [$code, $preapproval] = mp_api('GET', '/preapproval/' . rawurlencode($id));
    if ($code !== 200) { mp_log("no pude leer preapproval $id (http $code)"); exit; }
} elseif ($tipo === 'subscription_authorized_payment') {
    /*
     * EL COBRO DE CADA MES. Este es el aviso que manda Mercado Pago cada vez
     * que le cobra a alguien que ya esta suscripto.
     *
     * Se estaba tirando a la basura: no dice "preapproval" ni es "payment" a
     * secas, asi que caia en el "else" de abajo y salia sin hacer nada. Eso
     * significaba que la fecha de vencimiento nunca se estiraba: a los 33 dias
     * la persona perdia el acceso AUNQUE le siguieran cobrando. Aparecio en el
     * registro del 25/08 y por eso se corrigio.
     *
     * El id que llega es el del cobro, no el de la suscripcion, asi que primero
     * hay que ir a buscar de que suscripcion es.
     */
    [$code, $cobro] = mp_api('GET', '/authorized_payments/' . rawurlencode($id));
    if ($code !== 200) {
        // Por si MP cambia de endpoint: se prueba el de pagos comunes
        [$code, $cobro] = mp_api('GET', '/v1/payments/' . rawurlencode($id));
    }
    if ($code !== 200) { mp_log("no pude leer el cobro $id (http $code)"); exit; }

    $preId = (string) ($cobro['preapproval_id']
        ?? $cobro['metadata']['preapproval_id']
        ?? $cobro['point_of_interaction']['transaction_data']['subscription_id']
        ?? '');
    // El estado puede venir en la raiz o adentro del pago, segun el endpoint
    $estadoCobro = (string) ($cobro['payment']['status'] ?? $cobro['status'] ?? '');

    if ($preId === '') { mp_log("cobro $id sin numero de suscripcion, no se pudo asociar"); exit; }
    if (!in_array($estadoCobro, ['approved', 'processed'], true)) {
        mp_log("cobro $id todavia no aprobado (estado: $estadoCobro)");
        exit;
    }
    [$code, $preapproval] = mp_api('GET', '/preapproval/' . rawurlencode($preId));
    if ($code !== 200) { mp_log("no pude leer preapproval $preId (http $code)"); exit; }

} elseif ($tipo === 'payment') {
    // Un pago suelto: buscar la suscripción a la que pertenece
    [$code, $pago] = mp_api('GET', '/v1/payments/' . rawurlencode($id));
    if ($code !== 200) { mp_log("no pude leer pago $id (http $code)"); exit; }
    $preId = $pago['metadata']['preapproval_id'] ?? ($pago['point_of_interaction']['transaction_data']['subscription_id'] ?? '');
    if ($preId === '' || ($pago['status'] ?? '') !== 'approved') exit;
    [$code, $preapproval] = mp_api('GET', '/preapproval/' . rawurlencode($preId));
    if ($code !== 200) exit;
} else {
    // Ya no se sale en silencio: un aviso que no sabemos manejar queda anotado.
    // Asi fue como se encontro que los cobros mensuales se estaban perdiendo.
    mp_log("aviso de tipo desconocido, no se hizo nada: tipo=$tipo id=$id");
    exit;
}

// external_reference = "usuario_id:plan"
$ref = explode(':', (string) ($preapproval['external_reference'] ?? ''));
$uid  = (int) ($ref[0] ?? 0);
$plan = ($ref[1] ?? '') === 'anual' ? 'anual' : 'mensual';
$estadoMp = $preapproval['status'] ?? '';
if ($uid <= 0) { mp_log('preapproval sin referencia de usuario'); exit; }

if ($estadoMp === 'authorized') {
    mp_activar_plan($uid, $plan, 'MP ' . ($preapproval['id'] ?? ''), (string) ($preapproval['id'] ?? ''));
    mp_log("plan $plan ACTIVADO/renovado uid=$uid");
} elseif (in_array($estadoMp, ['cancelled', 'paused'], true)) {
    // Se registra la baja pero no se le corta el acceso: sigue hasta la fecha
    // que ya pago. Da igual si la cancelo desde acá, desde Mercado Pago o si
    // MP la corto por cobros fallidos — en ese ultimo caso "hasta" ya esta por
    // vencerse, asi que se apaga sola en unos dias.
    mp_baja_plan($uid);
    mp_log("baja registrada uid=$uid (estado MP: $estadoMp) — el acceso sigue hasta la fecha paga");
} else {
    mp_log("estado MP sin acción: $estadoMp uid=$uid");
}
