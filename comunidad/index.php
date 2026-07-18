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
    <h1>¡Hola, <?php echo htmlspecialchars($u['nombre']); ?>!</h1>
    <p class="bajada">
      Bienvenido a tu taller digital.
      <?php if ($u['rol'] === 'admin'): ?>
        Sos administrador de la plataforma.
      <?php elseif ($vence): ?>
        Tu suscripción está activa hasta el <?php echo date('d/m/Y', strtotime($vence)); ?>.
      <?php else: ?>
        Tu suscripción está activa.
      <?php endif; ?>
    </p>

    <style>
      .tarjetas{display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:1rem}
      .tarjeta-h{background:var(--panel);border:1px solid var(--bd);border-radius:12px;
                 padding:1.2rem;display:block;color:var(--txt)}
      a.tarjeta-h:hover{border-color:var(--accent)}
      .tarjeta-h .ico{font-size:1.5rem}
      .tarjeta-h h2{font-size:1rem;margin:.5rem 0 .2rem}
      .tarjeta-h p{font-size:.8rem;color:var(--txt2)}
      .tarjeta-h.prox{opacity:.55}
    </style>
    <div class="tarjetas">
      <a class="tarjeta-h" href="cotizador/">
        <span class="ico">🧮</span>
        <h2>Calculadora de costos</h2>
        <p>Calculá el precio justo de tus impresiones 3D.</p>
      </a>
      <div class="tarjeta-h prox">
        <span class="ico">📋</span>
        <h2>Presupuestos <span class="badge">Próx.</span></h2>
        <p>Generá y guardá presupuestos para tus clientes.</p>
      </div>
      <div class="tarjeta-h prox">
        <span class="ico">👥</span>
        <h2>Clientes <span class="badge">Próx.</span></h2>
        <p>Tu cartera de clientes, siempre a mano.</p>
      </div>
      <div class="tarjeta-h prox">
        <span class="ico">🧵</span>
        <h2>Stock de materiales <span class="badge">Próx.</span></h2>
        <p>Controlá tus rollos de filamento e insumos.</p>
      </div>
    </div>
<?php ui_panel_fin(); ?>
