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
      /* La que necesita plan pago: apagada pero legible, y con el cartel */
      .tarjeta-h.bloqueada .ico-caja{background:var(--surface-2);color:var(--txt-3)}
      .tarjeta-h.bloqueada h2, .tarjeta-h.bloqueada p{color:var(--txt-3)}
      .tarjeta-h.bloqueada .flecha{color:var(--txt-3)}
      a.tarjeta-h.bloqueada:hover{border-color:var(--accent)}
      a.tarjeta-h.bloqueada:hover .sello{background:var(--accent);color:var(--accent-ink)}
      .tarjeta-h .sello{display:inline-flex;align-items:center;gap:5px;margin-top:12px;
          padding:4px 10px;border-radius:99px;background:var(--accent-tinte);color:var(--accent);
          font-size:11.5px;font-weight:700;letter-spacing:.02em;transition:background-color .15s ease,color .15s ease}
    </style>
    <?php
    // [archivo, icono, titulo, bajada, esDePago]. Antes las ocho tarjetas se
    // veian iguales: quien estaba en el plan gratis tocaba Presupuestos y recien
    // ahi se enteraba de que no lo tenia. Ahora la que es de pago lo dice antes.
    $accesos = [
        ['calculadora.php',  'calculadora',  'Calculadora de costos', 'Calculá el precio justo de tus impresiones 3D.', false],
        ['libreria.php',     'libreria',     'Librería STL',          'Modelos listos para imprimir, seleccionados por Printika.', true],
        ['presupuestos.php', 'presupuestos', 'Presupuestos',          'Generá y enviá presupuestos profesionales a tus clientes.', true],
        ['productos.php',    'etiqueta',     'Productos',             'Tu catálogo de piezas con costo y precio de venta.', true],
        ['clientes.php',     'clientes',     'Clientes',              'Tu cartera de clientes, vinculada a los presupuestos.', true],
        ['ventas.php',       'ventas',       'Ventas',                'Ingresos y gastos del taller, mes a mes.', true],
        ['estadisticas.php', 'estadisticas', 'Estadísticas',          'Ganancia, ingresos y gastos de los últimos meses.', true],
        ['stock.php',        'stock',        'Stock Materiales',      'Controlá tus rollos de filamento e insumos.', true],
    ];
    $conTodo = acceso_total();
    ?>
    <div class="tarjetas">
      <?php foreach ($accesos as [$url, $ico, $titulo_t, $bajada_t, $dePago]):
          $bloqueada = $dePago && !$conTodo;
          // La bloqueada lleva al adelanto de ESA seccion, no a un cartel generico
          $destino = $bloqueada ? 'suscripcion.php?bloqueado=' . basename($url, '.php') : $url; ?>
        <a class="tarjeta-h<?php echo $bloqueada ? ' bloqueada' : ''; ?>" href="<?php echo htmlspecialchars($destino); ?>">
          <span class="flecha"><?php echo ui_icono($bloqueada ? 'candado' : 'flecha', 16); ?></span>
          <span class="ico-caja"><?php echo ui_icono($ico, 19); ?></span>
          <h2><?php echo htmlspecialchars($titulo_t); ?></h2>
          <p><?php echo htmlspecialchars($bajada_t); ?></p>
          <?php if ($bloqueada): ?>
            <span class="sello"><?php echo ui_icono('candado', 12); ?>Necesitás cuenta Pro</span>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    </div>
<?php ui_panel_fin(); ?>
