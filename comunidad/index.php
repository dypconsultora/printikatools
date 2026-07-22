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

requerir_usuario();
$u = usuario_actual();
$plan = plan_usuario();

$vence = in_array($plan, ['mensual', 'anual'], true) ? suscripcion_hasta((int) $u['id']) : false;
ui_panel_inicio('Inicio', $u, 'Inicio');
?>
    <h1>Hola, <?php echo htmlspecialchars($u['nombre']); ?></h1>
    <p class="bajada">
      <?php if ($u['rol'] === 'admin'): ?>
        Administrás la plataforma.
      <?php elseif ($plan === 'gratis'): ?>
        Estás en el plan Gratuito. <a href="suscripcion.php">Pasate al plan completo</a> para desbloquear todo Mi taller.
      <?php elseif ($vence): ?>
        Tu plan <?php echo $plan === 'anual' ? 'Anual' : 'Mensual'; ?> está activo hasta el <?php echo date('d/m/Y', strtotime($vence)); ?>.
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
      <a class="tarjeta-h" href="calculadora.php">
        <span class="flecha"><?php echo ui_icono('flecha', 16); ?></span>
        <span class="ico-caja"><?php echo ui_icono('calculadora', 19); ?></span>
        <h2>Calculadora de costos</h2>
        <p>Calculá el precio justo de tus impresiones 3D.</p>
      </a>
      <a class="tarjeta-h" href="libreria.php">
        <span class="flecha"><?php echo ui_icono('flecha', 16); ?></span>
        <span class="ico-caja"><?php echo ui_icono('libreria', 19); ?></span>
        <h2>Librería STL</h2>
        <p>Modelos listos para imprimir, seleccionados por Printika.</p>
      </a>
      <a class="tarjeta-h" href="presupuestos.php">
        <span class="flecha"><?php echo ui_icono('flecha', 16); ?></span>
        <span class="ico-caja"><?php echo ui_icono('presupuestos', 19); ?></span>
        <h2>Presupuestos</h2>
        <p>Generá y enviá presupuestos profesionales a tus clientes.</p>
      </a>
      <a class="tarjeta-h" href="productos.php">
        <span class="flecha"><?php echo ui_icono('flecha', 16); ?></span>
        <span class="ico-caja"><?php echo ui_icono('etiqueta', 19); ?></span>
        <h2>Productos</h2>
        <p>Tu catálogo de piezas con costo y precio de venta.</p>
      </a>
      <a class="tarjeta-h" href="clientes.php">
        <span class="flecha"><?php echo ui_icono('flecha', 16); ?></span>
        <span class="ico-caja"><?php echo ui_icono('clientes', 19); ?></span>
        <h2>Clientes</h2>
        <p>Tu cartera de clientes, vinculada a los presupuestos.</p>
      </a>
      <a class="tarjeta-h" href="ventas.php">
        <span class="flecha"><?php echo ui_icono('flecha', 16); ?></span>
        <span class="ico-caja"><?php echo ui_icono('ventas', 19); ?></span>
        <h2>Ventas</h2>
        <p>Ingresos y gastos del taller, mes a mes.</p>
      </a>
      <a class="tarjeta-h" href="estadisticas.php">
        <span class="flecha"><?php echo ui_icono('flecha', 16); ?></span>
        <span class="ico-caja"><?php echo ui_icono('estadisticas', 19); ?></span>
        <h2>Estadísticas</h2>
        <p>Ganancia, ingresos y gastos de los últimos meses.</p>
      </a>
      <a class="tarjeta-h" href="stock.php">
        <span class="flecha"><?php echo ui_icono('flecha', 16); ?></span>
        <span class="ico-caja"><?php echo ui_icono('stock', 19); ?></span>
        <h2>Stock Materiales</h2>
        <p>Controlá tus rollos de filamento e insumos.</p>
      </a>
    </div>
<?php ui_panel_fin(); ?>
