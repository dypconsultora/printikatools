<?php
/**
 * Conexión con Mercado Pago: credenciales (guardadas en la base, nunca en
 * el repo), prueba de conexión y URL del webhook para configurar en MP.
 */
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/ui.php';
require_once __DIR__ . '/../inc/taller.php';
require_once __DIR__ . '/../inc/mp.php';

requerir_admin();
$yo = usuario_actual();
taller_migrar();

$aviso = '';
$error = '';
$prueba = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!com_csrf_ok($_POST['csrf'] ?? '')) {
        $error = 'La sesión expiró, probá de nuevo.';
    } elseif (($_POST['accion'] ?? '') === 'guardar') {
        $token = trim($_POST['access_token'] ?? '');
        $pub   = trim($_POST['public_key'] ?? '');
        if ($token !== '') cfg_set('mp_access_token', $token);
        if ($pub !== '')   cfg_set('mp_public_key', $pub);
        $aviso = 'Credenciales guardadas.';
    } elseif (($_POST['accion'] ?? '') === 'desconectar') {
        cfg_set('mp_access_token', '');
        cfg_set('mp_public_key', '');
        $aviso = 'Mercado Pago desconectado.';
    } elseif (($_POST['accion'] ?? '') === 'probar') {
        if (!mp_conectado()) {
            $error = 'Primero guardá el Access Token.';
        } else {
            [$code, $resp] = mp_api('GET', '/users/me');
            $prueba = $code === 200
                ? 'Conexión OK: cuenta ' . ($resp['nickname'] ?? $resp['email'] ?? 'verificada')
                  . (($resp['site_id'] ?? '') ? ' (' . $resp['site_id'] . ')' : '')
                : 'Falló la conexión (HTTP ' . $code . '). Revisá el Access Token.';
        }
    }
}

$token = mp_token();
$pub   = mp_public_key();
$mask  = fn($v) => $v === '' ? '' : substr($v, 0, 12) . '····' . substr($v, -4);
$base  = (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'printikatools.com');
$webhook = $base . '/comunidad/mp_webhook.php';

ui_panel_inicio('Mercado Pago', $yo, 'Mercado Pago', '../');
?>
    <h1>Mercado Pago</h1>
    <p class="bajada">Conectá tu cuenta para cobrar las suscripciones mensuales y anuales de forma automática.</p>

    <?php if ($aviso): ?><div class="msg ok"><?php echo ui_icono('check', 16); ?><span><?php echo htmlspecialchars($aviso); ?></span></div><?php endif; ?>
    <?php if ($error): ?><div class="msg bad"><?php echo ui_icono('alerta', 16); ?><span><?php echo htmlspecialchars($error); ?></span></div><?php endif; ?>
    <?php if ($prueba !== null): ?><div class="msg <?php echo str_starts_with($prueba, 'Conexión OK') ? 'ok' : 'bad'; ?>">
      <?php echo ui_icono(str_starts_with($prueba, 'Conexión OK') ? 'check' : 'alerta', 16); ?><span><?php echo htmlspecialchars($prueba); ?></span></div><?php endif; ?>

    <style>
      .mp-caja{background:var(--surface);border:1px solid var(--bd-suave);border-radius:var(--radio-g);
               padding:22px;max-width:720px;margin-bottom:16px}
      .mp-caja h2{font-size:15px;font-weight:600;margin-bottom:4px}
      .mp-caja .nota{font-size:13px;color:var(--txt-2);margin-bottom:10px;line-height:1.55}
      .mp-estado{display:inline-flex;align-items:center;gap:8px;font-size:13px;font-weight:600;
               padding:6px 14px;border-radius:999px;margin-bottom:14px}
      .mp-estado::before{content:'';width:8px;height:8px;border-radius:99px;background:currentColor}
      .mp-estado.on{background:var(--ok-tinte);color:var(--ok)}
      .mp-estado.off{background:var(--bad-tinte);color:var(--bad)}
      .mp-caja code{display:block;background:var(--surface-2);border:1px solid var(--bd-suave);border-radius:var(--radio);
               padding:10px 12px;font-size:12.5px;word-break:break-all;user-select:all}
      .mp-botones{display:flex;gap:10px;margin-top:14px;flex-wrap:wrap}
      ol{font-size:13.5px;color:var(--txt-2);line-height:1.7;padding-left:20px}
    </style>

    <div class="mp-caja">
      <span class="mp-estado <?php echo mp_conectado() ? 'on' : 'off'; ?>">
        <?php echo mp_conectado() ? 'Conectado' : 'Sin conectar'; ?></span>
      <h2>Credenciales</h2>
      <p class="nota">Las encontrás en Mercado Pago → Tu negocio → Configuración →
        <strong>Credenciales de producción</strong>. Se guardan en el servidor, nunca en GitHub.</p>
      <form method="post" autocomplete="off">
        <input type="hidden" name="csrf" value="<?php echo com_csrf(); ?>">
        <input type="hidden" name="accion" value="guardar">
        <label for="mp-token">Access Token</label>
        <input id="mp-token" type="text" name="access_token"
               placeholder="<?php echo $token ? 'Guardado: ' . htmlspecialchars($mask($token)) : 'APP_USR-...'; ?>">
        <label for="mp-pub">Public Key</label>
        <input id="mp-pub" type="text" name="public_key"
               placeholder="<?php echo $pub ? 'Guardada: ' . htmlspecialchars($mask($pub)) : 'APP_USR-...'; ?>">
        <p class="nota" style="margin-top:6px">Dejá un campo vacío para conservar el valor guardado.</p>
        <div class="mp-botones">
          <button class="btn" type="submit">Guardar credenciales</button>
        </div>
      </form>
      <form method="post" style="display:inline-block;margin-top:10px">
        <input type="hidden" name="csrf" value="<?php echo com_csrf(); ?>">
        <input type="hidden" name="accion" value="probar">
        <button class="btn chico" type="submit">Probar conexión</button>
      </form>
      <?php if (mp_conectado()): ?>
      <form method="post" style="display:inline-block;margin-left:8px" onsubmit="return confirm('¿Desconectar Mercado Pago? Los botones de pago dejan de funcionar.')">
        <input type="hidden" name="csrf" value="<?php echo com_csrf(); ?>">
        <input type="hidden" name="accion" value="desconectar">
        <button class="btn chico peligro" type="submit">Desconectar</button>
      </form>
      <?php endif; ?>
    </div>

    <div class="mp-caja">
      <h2>Webhook (avisos automáticos)</h2>
      <p class="nota">Para que las suscripciones se activen y se den de baja solas, configurá esta URL
        en Mercado Pago → Tus integraciones → tu aplicación → <strong>Webhooks</strong>,
        marcando el evento <strong>Planes y suscripciones</strong>:</p>
      <code><?php echo htmlspecialchars($webhook); ?></code>
      <p class="nota" style="margin-top:10px">Doble seguro: aunque un aviso no llegue, cada plan tiene su
        vencimiento guardado en nuestra base — al vencer sin renovación, la cuenta baja a gratis sola.</p>
    </div>

    <div class="mp-caja">
      <h2>Cómo funciona el cobro</h2>
      <ol>
        <li>El usuario elige Mensual ($<?php echo number_format(COMUNIDAD_PRECIO_MENSUAL, 0, ',', '.'); ?>) o
            Anual ($<?php echo number_format(COMUNIDAD_PRECIO_ANUAL, 0, ',', '.'); ?>) en "Tu plan".</li>
        <li>Paga en el checkout seguro de Mercado Pago (suscripción con renovación automática).</li>
        <li>MP nos avisa por webhook y el plan se activa al instante, sin que hagas nada.</li>
        <li>Si el pago falla o cancelan, la cuenta baja a gratis automáticamente.</li>
      </ol>
    </div>
<?php ui_panel_fin(); ?>
