<?php
/**
 * Portada de la Comunidad: exige login y suscripción activa.
 * Es el tablero desde donde se accede a cada herramienta.
 */
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/ui.php';

// Sin base de datos configurada todavia: aviso amable (evita romper produccion).
if (!com_db_ok()) {
    ui_tarjeta_inicio('Comunidad');
    ?>
    <h1>Comunidad</h1>
    <p class="sub">Estamos preparando la plataforma. ¡Muy pronto!</p>
    <?php
    ui_tarjeta_fin();
    exit;
}

requerir_miembro();
$u = usuario_actual();

$vence = suscripcion_hasta((int) $u['id']);
ui_panel_inicio('Inicio', $u, 'Inicio');
?>
    <h1>Hola, <?php echo htmlspecialchars($u['nombre']); ?></h1>
    <p class="bajada">
      <?php if ($u['rol'] === 'admin'): ?>
        Administrás la plataforma.
      <?php elseif ($vence): ?>
        Tu suscripción está activa hasta el <?php echo date('d/m/Y', strtotime($vence)); ?>.
      <?php else: ?>
        Tu suscripción está activa.
      <?php endif; ?>
    </p>

    <style>
      .tarjetas{display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:12px}
      .tarjeta-h{position:relative;background:var(--surface);border:1px solid var(--bd-suave);
                 border-radius:var(--radio-g);padding:20px;display:block;color:var(--txt);
                 transition:border-color .15s ease,background-color .15s ease}
      a.tarjeta-h:hover{border-color:var(--accent);color:var(--txt)}
      a.tarjeta-h .flecha{position:absolute;top:20px;right:18px;color:var(--txt-3);
                 transition:color .15s ease,transform .15s ease}
      a.tarjeta-h:hover .flecha{color:var(--accent);transform:translateX(2px)}
      .tarjeta-h .ico-caja{width:36px;height:36px;border-radius:var(--radio);
                 background:var(--accent-tinte);color:var(--accent);
                 display:flex;align-items:center;justify-content:center;margin-bottom:14px}
      .tarjeta-h h2{font-size:14.5px;font-weight:600;margin-bottom:3px;display:flex;align-items:center;gap:8px}
      .tarjeta-h p{font-size:13px;color:var(--txt-2);line-height:1.5}
      .tarjeta-h.prox .ico-caja{background:var(--surface-2);color:var(--txt-3)}
      .tarjeta-h.prox h2{color:var(--txt-2)}
    </style>
    <div class="tarjetas">
      <a class="tarjeta-h" href="cotizador/">
        <span class="flecha"><?php echo ui_icono('flecha', 16); ?></span>
        <span class="ico-caja"><?php echo ui_icono('calculadora', 19); ?></span>
        <h2>Calculadora de costos</h2>
        <p>Calculá el precio justo de tus impresiones 3D.</p>
      </a>
      <div class="tarjeta-h prox">
        <span class="ico-caja"><?php echo ui_icono('presupuestos', 19); ?></span>
        <h2>Presupuestos <span class="badge">Pronto</span></h2>
        <p>Generá y guardá presupuestos para tus clientes.</p>
      </div>
      <div class="tarjeta-h prox">
        <span class="ico-caja"><?php echo ui_icono('clientes', 19); ?></span>
        <h2>Clientes <span class="badge">Pronto</span></h2>
        <p>Tu cartera de clientes, siempre a mano.</p>
      </div>
      <div class="tarjeta-h prox">
        <span class="ico-caja"><?php echo ui_icono('stock', 19); ?></span>
        <h2>Stock de materiales <span class="badge">Pronto</span></h2>
        <p>Controlá tus rollos de filamento e insumos.</p>
      </div>
    </div>
<?php ui_panel_fin(); ?>
