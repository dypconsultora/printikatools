<?php
/**
 * Tu plan: pantalla de planes para usuarios logueados. El plan gratis
 * elige acá pasarse a mensual o anual (pago con Mercado Pago).
 */
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/ui.php';
require_once __DIR__ . '/inc/mp.php';

$u = usuario_actual();
if ($u === null) {
    header('Location: login.php');
    exit;
}
$plan = plan_usuario();
$hasta = in_array($plan, ['mensual', 'anual'], true) ? suscripcion_hasta((int) $u['id']) : false;
$elegido = $_GET['plan'] ?? '';

$AVISOS = [
    'volviste' => 'Si completaste el pago, tu plan se activa en unos instantes. Actualizá la página en un ratito.',
    'sin_mp'   => 'El pago online se está configurando. Escribinos por Telegram y lo activamos a mano.',
    'error_mp' => 'No pudimos iniciar el pago. Probá de nuevo en unos minutos o escribinos.',
];
$aviso = $AVISOS[$_GET['aviso'] ?? ''] ?? '';

ui_panel_inicio('Tu plan', $u, 'Tu plan');
?>
    <style>.contenido{max-width:none}</style>
    <h1>Tu plan</h1>
    <p class="bajada">
      <?php if ($plan === 'admin'): ?>Sos administrador: acceso completo.
      <?php elseif ($plan === 'gratis'): ?>Estás en el plan Gratuito: calculadora y librería STL. Pasate al plan completo para desbloquear todo Mi taller.
      <?php else: ?>Tenés el plan <?php echo $plan === 'anual' ? 'Anual' : 'Mensual'; ?> activo<?php echo $hasta ? ' hasta el ' . date('d/m/Y', strtotime($hasta)) : ''; ?>. ¡Gracias por acompañarnos!
      <?php endif; ?>
    </p>

    <?php if ($aviso): ?><div class="msg ok"><?php echo ui_icono('check', 16); ?><span><?php echo htmlspecialchars($aviso); ?></span></div><?php endif; ?>

    <style>
      .planes{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:16px;max-width:1000px}
      .plan-c{position:relative;background:var(--surface);border:1px solid var(--bd-suave);border-radius:var(--radio-g);
              padding:26px 24px;display:flex;flex-direction:column}
      .plan-c.destacado{border-color:var(--accent)}
      .plan-c .cinta{position:absolute;top:-11px;left:50%;transform:translateX(-50%);background:var(--accent);
              color:var(--accent-ink);font-size:11px;font-weight:700;padding:3px 12px;border-radius:999px;white-space:nowrap}
      .plan-c h2{font-size:16px;font-weight:700;margin-bottom:6px}
      .plan-c .precio{font-size:30px;font-weight:700;letter-spacing:-.02em}
      .plan-c .precio small{font-size:13px;font-weight:500;color:var(--txt-2)}
      .plan-c .detalle{font-size:12.5px;color:var(--txt-3);margin-bottom:14px}
      .plan-c ul{list-style:none;margin:0 0 18px;padding:0;display:grid;gap:8px;font-size:13.5px;color:var(--txt-2)}
      .plan-c li{display:flex;gap:8px;align-items:center}
      .plan-c li .ico{color:var(--ok);flex-shrink:0}
      .plan-c .btn, .plan-c .actual{margin-top:auto;width:100%;text-align:center;justify-content:center}
      .plan-c .actual{display:block;padding:10px;border:1px dashed var(--bd);border-radius:var(--radio);
              font-size:13px;font-weight:600;color:var(--txt-3)}
    </style>

    <div class="planes">
      <div class="plan-c">
        <h2>Printika Free</h2>
        <p class="precio">$0</p>
        <p class="detalle">Para siempre</p>
        <ul>
          <li><?php echo ui_icono('check', 15); ?>Calculadora de costos completa</li>
          <li><?php echo ui_icono('check', 15); ?>Librería STL</li>
        </ul>
        <?php if ($plan === 'gratis'): ?><span class="actual">Tu plan actual</span>
        <?php else: ?><span class="actual">Incluido en tu plan</span><?php endif; ?>
      </div>

      <div class="plan-c<?php echo $elegido === 'mensual' ? ' destacado' : ''; ?>">
        <h2>Printika Pro</h2>
        <p class="precio"><?php echo '$' . number_format(COMUNIDAD_PRECIO_MENSUAL, 0, ',', '.'); ?> <small>/mes</small></p>
        <p class="detalle">Renovación mes a mes, sin permanencia</p>
        <ul>
          <li><?php echo ui_icono('check', 15); ?>Todo Mi taller: presupuestos, productos, clientes</li>
          <li><?php echo ui_icono('check', 15); ?>Stock, ventas y estadísticas</li>
          <li><?php echo ui_icono('check', 15); ?>Tus datos guardados en tu cuenta</li>
        </ul>
        <?php if ($plan === 'mensual'): ?><span class="actual">Tu plan actual</span>
        <?php elseif ($plan === 'gratis'): ?>
          <a class="btn" href="mp_checkout.php?plan=mensual">Suscribirme con Mercado Pago</a>
        <?php else: ?><span class="actual">—</span><?php endif; ?>
      </div>

      <div class="plan-c destacado">
        <span class="cinta">Más de 2 meses gratis</span>
        <h2>Printika Pro Anual</h2>
        <p class="precio"><?php echo '$' . number_format(COMUNIDAD_PRECIO_ANUAL, 0, ',', '.'); ?> <small>/año</small></p>
        <p class="detalle">Equivale a $14.167 por mes · ahorrás $46.000</p>
        <ul>
          <li><?php echo ui_icono('check', 15); ?>Todo lo del plan mensual</li>
          <li><?php echo ui_icono('check', 15); ?>Más de 2 meses sin cargo</li>
          <li><?php echo ui_icono('check', 15); ?>Precio congelado por 12 meses</li>
        </ul>
        <?php if ($plan === 'anual'): ?><span class="actual">Tu plan actual</span>
        <?php elseif ($plan === 'admin'): ?><span class="actual">Sos administrador</span>
        <?php else: ?>
          <a class="btn" href="mp_checkout.php?plan=anual">Suscribirme con Mercado Pago</a>
        <?php endif; ?>
      </div>
    </div>

    <p style="margin-top:18px;font-size:13px;color:var(--txt-3)">
      El pago se procesa de forma segura en Mercado Pago y la renovación es automática.
      ¿Dudas? Escribinos por <a href="https://t.me/+N5f7IcWPXihhMWQx" target="_blank" rel="noopener">Telegram</a>.
    </p>
<?php ui_panel_fin(); ?>
