<?php
/**
 * Ventas: ingresos y gastos del taller, mes a mes.
 * Los presupuestos vendidos aparecen como ingresos automáticamente.
 */
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/ui.php';
require_once __DIR__ . '/inc/taller.php';

requerir_miembro();
$u = usuario_actual();
taller_migrar();
$uid = (int) $u['id'];

// Mes seleccionado (YYYY-MM)
$mes_param = preg_match('/^\d{4}-\d{2}$/', $_GET['mes'] ?? '') ? $_GET['mes'] : date('Y-m');
[$anio, $mes] = array_map('intval', explode('-', $mes_param));

$aviso = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!com_csrf_ok($_POST['csrf'] ?? '')) {
        $error = 'La sesión expiró, probá de nuevo.';
    } else {
        $accion = $_POST['accion'] ?? '';
        if ($accion === 'agregar') {
            $tipo     = ($_POST['tipo'] ?? '') === 'gasto' ? 'gasto' : 'ingreso';
            $concepto = mb_substr(trim($_POST['concepto'] ?? ''), 0, 200);
            $monto    = (float) ($_POST['monto'] ?? 0);
            $fecha    = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['fecha'] ?? '') ? $_POST['fecha'] : date('Y-m-d');
            if ($concepto === '') {
                $error = 'Ingresá el concepto del movimiento.';
            } elseif ($monto <= 0) {
                $error = 'El monto tiene que ser mayor a cero.';
            } else {
                com_db()->prepare('INSERT INTO movimientos (usuario_id, tipo, concepto, monto, fecha, creado_en)
                                   VALUES (?,?,?,?,?,NOW())')
                    ->execute([$uid, $tipo, $concepto, $monto, $fecha]);
                $aviso = $tipo === 'ingreso' ? 'Ingreso agregado.' : 'Gasto agregado.';
                $mes_param = substr($fecha, 0, 7);
                [$anio, $mes] = array_map('intval', explode('-', $mes_param));
            }
        } elseif ($accion === 'eliminar' && (int) ($_POST['movimiento_id'] ?? 0)) {
            com_db()->prepare('DELETE FROM movimientos WHERE id=? AND usuario_id=?')
                ->execute([(int) $_POST['movimiento_id'], $uid]);
            $aviso = 'Movimiento eliminado.';
        }
    }
}

$movimientos = taller_movimientos_mes($uid, $anio, $mes);
[$ingresos, $gastos] = taller_resumen_mes($uid, $anio, $mes);

// Exportar CSV del mes
if (isset($_GET['csv'])) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="ventas-' . $mes_param . '.csv"');
    echo "\xEF\xBB\xBF"; // BOM para Excel
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Fecha', 'Tipo', 'Concepto', 'Monto', 'Origen'], ';', '"', '');
    foreach ($movimientos as $m) {
        fputcsv($out, [$m['fecha'], $m['tipo'], $m['concepto'],
                       number_format((float) $m['monto'], 2, ',', ''),
                       $m['origen'] === 'presupuesto' ? 'Presupuesto vendido' : 'Manual'], ';', '"', '');
    }
    fputcsv($out, [], ';', '"', '');
    fputcsv($out, ['', '', 'Total ingresos', number_format($ingresos, 2, ',', ''), ''], ';', '"', '');
    fputcsv($out, ['', '', 'Total gastos', number_format($gastos, 2, ',', ''), ''], ';', '"', '');
    fclose($out);
    exit;
}

ui_panel_inicio('Ventas', $u, 'Ventas');
?>
    <style>.contenido{max-width:none}</style>
    <h1>Ventas</h1>
    <p class="bajada">Ingresos por ventas y gastos del taller, mes a mes.</p>

    <?php if ($aviso): ?><div class="msg ok"><?php echo ui_icono('check', 16); ?><span><?php echo htmlspecialchars($aviso); ?></span></div><?php endif; ?>
    <?php if ($error): ?><div class="msg bad"><?php echo ui_icono('alerta', 16); ?><span><?php echo htmlspecialchars($error); ?></span></div><?php endif; ?>

    <?php taller_nav_mes('ventas.php', $anio, $mes); ?>

    <style>
      .resumen{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:16px}
      .res-caja{background:var(--surface);border:1px solid var(--bd-suave);border-radius:var(--radio-g);padding:18px 20px}
      .res-caja small{display:block;font-size:11px;font-weight:600;letter-spacing:.07em;text-transform:uppercase;
              color:var(--txt-3);margin-bottom:6px}
      .res-caja b{font-size:24px;font-weight:700;font-variant-numeric:tabular-nums}
      .res-caja.ing b{color:var(--ok)}
      .res-caja.gas b{color:var(--bad)}
      .acciones-mov{display:flex;justify-content:space-between;gap:10px;margin-bottom:16px;flex-wrap:wrap}
      .btn.verde{background:var(--ok);color:#04170d}
      .btn.verde:hover{background:#5adfa4;color:#04170d}
      .btn.rojo{background:var(--bad);color:#2a0a0d}
      .btn.rojo:hover{background:#f79098;color:#2a0a0d}
      .form-mov{background:var(--surface);border:1px solid var(--bd-suave);border-radius:var(--radio-g);
              padding:20px;margin-bottom:16px}
      .form-mov h2{font-size:15px;font-weight:600;margin-bottom:4px}
      .form-mov .fila{display:grid;grid-template-columns:2fr 1fr 1fr auto;gap:10px;align-items:end}
      .panel{background:var(--surface);border:1px solid var(--bd-suave);border-radius:var(--radio-g);overflow:hidden}
      table{width:100%;border-collapse:collapse;font-size:13.5px}
      th,td{padding:12px 16px;text-align:left;border-bottom:1px solid var(--bd-suave);vertical-align:middle}
      th{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.07em;color:var(--txt-3)}
      tr:last-child td{border-bottom:none}
      tbody tr:hover{background:var(--surface-2)}
      td.num,th.num{text-align:right;font-variant-numeric:tabular-nums;white-space:nowrap}
      td.fecha{color:var(--txt-2);white-space:nowrap;font-variant-numeric:tabular-nums}
      .tipo-chip{display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:500;
              padding:3px 10px;border-radius:99px}
      .tipo-chip::before{content:'';width:6px;height:6px;border-radius:99px;background:currentColor}
      .tipo-chip.ingreso{background:var(--ok-tinte);color:var(--ok)}
      .tipo-chip.gasto{background:var(--bad-tinte);color:var(--bad)}
      td .origen{font-size:11.5px;color:var(--txt-3)}
      .vacio{border:1px dashed var(--bd);border-radius:var(--radio-g);padding:56px 20px;text-align:center;
              color:var(--txt-2);font-size:14px}
      .vacio .ctas{display:flex;gap:10px;justify-content:center;margin-top:18px;flex-wrap:wrap}
      td form{margin:0}
      @media (max-width:900px){ .resumen{grid-template-columns:1fr} .form-mov .fila{grid-template-columns:1fr}
        .tabla-scroll{overflow-x:auto} }
    </style>

    <div class="resumen">
      <div class="res-caja ing"><small>Ingresos</small><b><?php echo taller_precio($ingresos); ?></b></div>
      <div class="res-caja gas"><small>Gastos</small><b><?php echo taller_precio($gastos); ?></b></div>
    </div>

    <div class="acciones-mov">
      <button type="button" class="btn verde" data-abrir="ingreso"><?php echo ui_icono('rayo', 15); ?> Agregar ingreso</button>
      <button type="button" class="btn rojo" data-abrir="gasto">Agregar gasto</button>
    </div>

    <div class="form-mov" id="formMov" hidden>
      <h2 id="formMovTitulo">Agregar ingreso</h2>
      <form method="post">
        <input type="hidden" name="csrf" value="<?php echo com_csrf(); ?>">
        <input type="hidden" name="accion" value="agregar">
        <input type="hidden" name="tipo" id="movTipo" value="ingreso">
        <div class="fila">
          <span><label for="movConcepto">Concepto</label>
            <input id="movConcepto" type="text" name="concepto" maxlength="200" required
                   placeholder="Venta presencial, filamento, envío..."></span>
          <span><label for="movMonto">Monto</label>
            <input id="movMonto" type="number" name="monto" min="0.01" step="0.01" required></span>
          <span><label for="movFecha">Fecha</label>
            <input id="movFecha" type="date" name="fecha" value="<?php
              echo $mes_param === date('Y-m') ? date('Y-m-d') : $mes_param . '-01'; ?>"></span>
          <span style="display:flex;gap:8px">
            <button type="button" class="btn sec" id="movCancelar">Cancelar</button>
            <button class="btn" type="submit" id="movGuardar">Agregar</button>
          </span>
        </div>
      </form>
    </div>

    <?php if (!$movimientos): ?>
      <div class="vacio">
        <strong style="color:var(--txt);font-size:15.5px">Sin movimientos este mes</strong><br><br>
        Cargá tus ingresos (ventas presenciales, MercadoLibre, etc.) o gastos del mes.<br>
        Los ingresos de presupuestos vendidos aparecen acá automáticamente.
        <div class="ctas">
          <button type="button" class="btn verde" data-abrir="ingreso">Agregar ingreso</button>
          <button type="button" class="btn rojo" data-abrir="gasto">Agregar gasto</button>
        </div>
      </div>
    <?php else: ?>
      <div class="panel tabla-scroll">
        <table>
          <thead><tr><th>Fecha</th><th>Concepto</th><th>Tipo</th><th class="num">Monto</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($movimientos as $m): ?>
            <tr>
              <td class="fecha"><?php echo date('d/m/Y', strtotime($m['fecha'])); ?></td>
              <td><?php echo htmlspecialchars($m['concepto']); ?>
                <?php if ($m['origen'] === 'presupuesto'): ?>
                  <span class="origen">· automático</span>
                <?php endif; ?></td>
              <td><span class="tipo-chip <?php echo $m['tipo']; ?>"><?php echo $m['tipo'] === 'ingreso' ? 'Ingreso' : 'Gasto'; ?></span></td>
              <td class="num" style="color:<?php echo $m['tipo'] === 'ingreso' ? 'var(--ok)' : 'var(--bad)'; ?>">
                <strong><?php echo ($m['tipo'] === 'gasto' ? '-' : '+') . taller_precio($m['monto']); ?></strong></td>
              <td style="text-align:right">
                <?php if ($m['origen'] === 'presupuesto'): ?>
                  <a class="btn chico sec" href="presupuesto.php?id=<?php echo (int) $m['presupuesto_id']; ?>">Ver presupuesto</a>
                <?php else: ?>
                  <form method="post" onsubmit="return confirm('¿Eliminar este movimiento?');">
                    <input type="hidden" name="csrf" value="<?php echo com_csrf(); ?>">
                    <input type="hidden" name="accion" value="eliminar">
                    <input type="hidden" name="movimiento_id" value="<?php echo (int) $m['id']; ?>">
                    <button class="btn chico peligro" type="submit">Eliminar</button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

    <script>
    (function(){
      var form = document.getElementById('formMov');
      function abrir(tipo){
        form.hidden = false;
        document.getElementById('movTipo').value = tipo;
        document.getElementById('formMovTitulo').textContent = tipo === 'ingreso' ? 'Agregar ingreso' : 'Agregar gasto';
        document.getElementById('movGuardar').className = 'btn ' + (tipo === 'ingreso' ? 'verde' : 'rojo');
        document.getElementById('movConcepto').focus();
      }
      document.querySelectorAll('[data-abrir]').forEach(function(b){
        b.addEventListener('click', function(){ abrir(b.dataset.abrir); });
      });
      document.getElementById('movCancelar').addEventListener('click', function(){ form.hidden = true; });
    })();
    </script>
<?php ui_panel_fin(); ?>
