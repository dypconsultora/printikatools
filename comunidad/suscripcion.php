<?php
/**
 * Tu plan: pantalla de planes para usuarios logueados. El plan gratis
 * elige acá pasarse a mensual o anual (pago con Mercado Pago).
 */
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/ui.php';
require_once __DIR__ . '/inc/mp.php';
require_once __DIR__ . '/inc/vistazo.php';

$u = usuario_actual();
if ($u === null) {
    header('Location: login.php');
    exit;
}
com_exigir_email_verificado();
$plan = plan_usuario();
$hasta = in_array($plan, ['mensual', 'anual'], true) ? suscripcion_hasta((int) $u['id']) : false;
$elegido = $_GET['plan'] ?? '';

$AVISOS = [
    'volviste' => 'Si completaste el pago, tu plan se activa en unos instantes. Actualizá la página en un ratito.',
    'sin_mp'   => 'El pago online se está configurando. Escribinos por Telegram y lo activamos a mano.',
    'error_mp' => 'No pudimos iniciar el pago. Probá de nuevo en unos minutos o escribinos.',
];
$aviso = $AVISOS[$_GET['aviso'] ?? ''] ?? '';

// Venia de tocar un candado: se le muestra un adelanto de ESA pantalla
$bloq = ($plan === 'gratis') ? vistazo_seccion($_GET['bloqueado'] ?? '') : null;
$bloq_img = $bloq ? vistazo_imagen($_GET['bloqueado']) : '';

ui_panel_inicio('Tu plan', $u, 'Tu plan');
?>
    <style>.contenido{max-width:none}</style>

    <?php if ($bloq): ?>
      <style>
        .vistazo{background:var(--surface);border:1px solid var(--bd-suave);border-radius:var(--radio-g);
                 overflow:hidden;margin-bottom:26px}
        .vistazo .cuerpo{display:grid;grid-template-columns:1fr 1.15fr;gap:0;align-items:stretch}
        .vistazo .texto{padding:30px 34px;display:flex;flex-direction:column;justify-content:center}
        .vistazo .etiq{display:inline-flex;align-items:center;gap:7px;align-self:flex-start;
                       background:var(--accent-tinte);color:var(--accent);font-size:11.5px;font-weight:700;
                       letter-spacing:.06em;text-transform:uppercase;padding:5px 12px;border-radius:99px;
                       margin-bottom:14px}
        .vistazo h1{font-size:27px;line-height:1.2;margin-bottom:8px}
        .vistazo .gancho{font-size:16px;line-height:1.5;color:var(--txt);font-weight:500;margin-bottom:10px}
        .vistazo .detalle{font-size:14px;line-height:1.6;color:var(--txt-2);margin-bottom:18px}
        .vistazo ul{list-style:none;margin:0;padding:0;display:grid;gap:9px}
        .vistazo li{display:flex;gap:9px;align-items:flex-start;font-size:14px;color:var(--txt-2)}
        .vistazo li .ico{color:var(--ok);flex-shrink:0;margin-top:2px}
        /* La captura se recorta contra el borde y se desvanece: se entiende que
           hay mas pantalla detras, sin prometer algo que no se ve entero */
        .vistazo .foto{position:relative;background:var(--surface-2);min-height:280px;overflow:hidden}
        .vistazo .foto img{display:block;width:100%;height:100%;object-fit:cover;object-position:left top}
        .vistazo .foto::after{content:'';position:absolute;inset:0;
                              background:linear-gradient(105deg,var(--surface) 0,transparent 22%)}
        .vistazo .pie-v{display:flex;gap:12px;align-items:center;flex-wrap:wrap;
                        padding:16px 34px;border-top:1px solid var(--bd-suave);background:var(--surface-2)}
        .vistazo .pie-v p{font-size:13.5px;color:var(--txt-2);margin:0;margin-right:auto}
        @media (max-width:900px){
          .vistazo .cuerpo{grid-template-columns:1fr}
          .vistazo .foto{min-height:200px;order:-1}
          .vistazo .foto::after{background:linear-gradient(to bottom,transparent 60%,var(--surface))}
          .vistazo .texto{padding:24px}
          .vistazo .pie-v{padding:16px 24px}
        }
      </style>
      <div class="vistazo">
        <div class="cuerpo">
          <div class="texto">
            <span class="etiq"><?php echo ui_icono('candado', 13); ?>Incluido en el plan completo</span>
            <h1><?php echo htmlspecialchars($bloq['titulo']); ?></h1>
            <p class="gancho"><?php echo htmlspecialchars($bloq['gancho']); ?></p>
            <p class="detalle"><?php echo htmlspecialchars($bloq['detalle']); ?></p>
            <ul>
              <?php foreach ($bloq['items'] as $it): ?>
                <li><?php echo ui_icono('check', 16); ?><span><?php echo htmlspecialchars($it); ?></span></li>
              <?php endforeach; ?>
            </ul>
          </div>
          <?php if ($bloq_img): ?>
            <div class="foto">
              <img src="<?php echo htmlspecialchars($bloq_img); ?>" alt="Así se ve <?php echo htmlspecialchars($bloq['titulo']); ?> por dentro" loading="lazy">
            </div>
          <?php endif; ?>
        </div>
        <div class="pie-v">
          <p>Elegí abajo con qué plan lo desbloqueás.</p>
          <a class="btn" href="#planes">Ver los planes</a>
        </div>
      </div>
    <?php endif; ?>

    <h1 id="planes">Tu plan</h1>
    <p class="bajada">
      <?php if ($plan === 'admin'): ?>Sos administrador: acceso completo.
      <?php elseif ($plan === 'gratis'): ?>Estás en el plan Gratuito: tenés la calculadora y los recursos. El plan completo suma la librería STL, todo Mi taller y el soporte por Telegram.
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
        <p class="detalle">Para probar y empezar</p>
        <ul>
          <li><?php echo ui_icono('check', 15); ?>Calculadora de costos online</li>
          <li><?php echo ui_icono('check', 15); ?>Cálculo en ARS, USD y EUR</li>
          <li><?php echo ui_icono('check', 15); ?>Recursos en videos y PDF</li>
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
          <li><?php echo ui_icono('check', 15); ?>Librería STL completa</li>
          <li><?php echo ui_icono('check', 15); ?>Soporte por Telegram</li>
          <li><?php echo ui_icono('check', 15); ?>Tus datos guardados en tu cuenta</li>
        </ul>
        <?php if ($plan === 'mensual'): ?><span class="actual">Tu plan actual</span>
        <?php elseif ($plan === 'gratis'): ?>
          <a class="btn" href="mp_checkout.php?plan=mensual">Suscribirme con Mercado Pago</a>
        <?php else: ?><span class="actual">—</span><?php endif; ?>
      </div>

      <div class="plan-c destacado">
        <span class="cinta">2 meses gratis</span>
        <h2>Printika Pro Anual</h2>
        <p class="precio"><?php echo '$' . number_format(COMUNIDAD_PRECIO_ANUAL, 0, ',', '.'); ?> <small>/año</small></p>
        <p class="detalle">Equivale a $15.000 por mes · ahorrás $36.000</p>
        <ul>
          <li><?php echo ui_icono('check', 15); ?>Todo lo del plan mensual</li>
          <li><?php echo ui_icono('check', 15); ?>2 meses sin cargo</li>
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
