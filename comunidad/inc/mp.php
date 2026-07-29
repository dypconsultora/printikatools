<?php
/**
 * Mercado Pago: credenciales guardadas en la tabla config (nunca en el repo)
 * y llamadas a la API de suscripciones (preapproval).
 */
require_once __DIR__ . '/bootstrap.php';

function mp_token()      { return (string) (cfg_get('mp_access_token') ?? ''); }
function mp_public_key() { return (string) (cfg_get('mp_public_key') ?? ''); }
function mp_conectado()  { return mp_token() !== ''; }

/** Llamada a la API de MP. Devuelve [código HTTP, respuesta decodificada]. */
function mp_api($metodo, $ruta, $body = null) {
    $ch = curl_init('https://api.mercadopago.com' . $ruta);
    $headers = ['Authorization: Bearer ' . mp_token(), 'Content-Type: application/json'];
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => $metodo,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_TIMEOUT        => 20,
    ]);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
    }
    $resp = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    return [$code, $resp !== false ? json_decode($resp, true) : null];
}

/** Datos de cada plan pago. */
function mp_planes() {
    return [
        // Los nombres son los que ve el cliente en la web: si en el checkout
        // de Mercado Pago aparece otro, desconfia justo antes de pagar.
        'mensual' => ['titulo' => 'Printika Pro',       'monto' => COMUNIDAD_PRECIO_MENSUAL, 'meses' => 1],
        'anual'   => ['titulo' => 'Printika Pro Anual', 'monto' => COMUNIDAD_PRECIO_ANUAL,   'meses' => 12],
    ];
}

/** Activa o renueva el plan pago de un usuario (cierra suscripciones previas). */
function mp_activar_plan($usuario_id, $plan, $notas = '') {
    $meses = $plan === 'anual' ? 12 : 1;
    $db = com_db();
    $db->prepare("UPDATE suscripciones SET estado='cancelada' WHERE usuario_id=?")
       ->execute([(int) $usuario_id]);
    // Unos días de gracia para que el cobro recurrente llegue antes del corte
    $db->prepare("INSERT INTO suscripciones (usuario_id, estado, plan, desde, hasta, notas, creado_en)
                  VALUES (?, 'activa', ?, CURDATE(), DATE_ADD(DATE_ADD(CURDATE(), INTERVAL ? MONTH), INTERVAL 3 DAY), ?, NOW())")
       ->execute([(int) $usuario_id, $plan === 'anual' ? 'anual' : 'mensual', $meses, mb_substr($notas, 0, 255)]);
}

/** Cancela el plan pago de un usuario (baja a gratis). */
function mp_cancelar_plan($usuario_id) {
    com_db()->prepare("UPDATE suscripciones SET estado='cancelada' WHERE usuario_id=?")
        ->execute([(int) $usuario_id]);
}

/** Secreto para validar la firma de los webhooks (Admin > Mercado Pago). */
function mp_webhook_secret() { return (string) (cfg_get('mp_webhook_secret') ?? ''); }

/**
 * Valida la cabecera x-signature que manda Mercado Pago.
 * El manifiesto es "id:<data.id>;request-id:<x-request-id>;ts:<ts>;" firmado
 * con HMAC-SHA256 y el secreto del panel de MP.
 * Si todavia no se cargo el secreto, se acepta (para no frenar la puesta en
 * marcha) pero queda registrado en el log como aviso.
 */
function mp_firma_valida($data_id) {
    $secreto = mp_webhook_secret();
    if ($secreto === '') {
        mp_log('AVISO: webhook sin secreto configurado — cargalo en Admin > Mercado Pago');
        return true;
    }
    $firma = $_SERVER['HTTP_X_SIGNATURE'] ?? '';
    $req   = $_SERVER['HTTP_X_REQUEST_ID'] ?? '';
    if ($firma === '') return false;

    $ts = $v1 = '';
    foreach (explode(',', $firma) as $parte) {
        $kv = explode('=', trim($parte), 2);
        if (count($kv) !== 2) continue;
        if (trim($kv[0]) === 'ts') $ts = trim($kv[1]);
        if (trim($kv[0]) === 'v1') $v1 = trim($kv[1]);
    }
    if ($ts === '' || $v1 === '') return false;

    // Rechazar avisos viejos (repeticion de una notificacion capturada)
    if (abs(time() - (int) $ts) > 600) return false;

    $manifiesto = 'id:' . $data_id . ';request-id:' . $req . ';ts:' . $ts . ';';
    return hash_equals(hash_hmac('sha256', $manifiesto, $secreto), $v1);
}

/** Registro simple de lo que pasa con MP (para poder mirar si algo falla). */
function mp_log($mensaje) {
    $dir = dirname(__DIR__) . '/uploads';
    if (is_dir($dir)) {
        @file_put_contents($dir . '/mp_log.txt',
            '[' . date('Y-m-d H:i:s') . '] ' . $mensaje . "\n", FILE_APPEND);
    }
}
