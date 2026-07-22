<?php
/**
 * Presupuestos: listado con estados, búsqueda por cliente y orden.
 */
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/ui.php';
require_once __DIR__ . '/inc/taller.php';

requerir_miembro();
$u = usuario_actual();
taller_migrar();
$uid = (int) $u['id'];

$aviso = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!com_csrf_ok($_POST['csrf'] ?? '')) {
        $error = 'La sesión expiró, probá de nuevo.';
    } else {
        $accion = $_POST['accion'] ?? '';
        $pid    = (int) ($_POST['presupuesto_id'] ?? 0);
        if ($accion === 'estado' && $pid) {
            $nuevo = ($_POST['estado'] ?? '') === 'vendido' ? 'vendido' : 'pendiente';
            taller_cambiar_estado($uid, $pid, $nuevo);
            $aviso = $nuevo === 'vendido' ? 'Presupuesto marcado como vendido.' : 'Presupuesto vuelto a pendiente.';
        } elseif ($accion === 'eliminar' && $pid) {
            com_db()->prepare('DELETE FROM presupuestos WHERE id=? AND usuario_id=?')->execute([$pid, $uid]);
            $aviso = 'Presupuesto eliminado.';
        }
    }
}

$tab   = in_array($_GET['tab'] ?? '', ['pendientes', 'vendidos'], true) ? $_GET['tab'] : 'todos';
$q     = trim($_GET['q'] ?? '');
$orden = in_array($_GET['orden'] ?? '', ['antiguos', 'mayor'], true) ? $_GET['orden'] : 'recientes';

// Conteos por estado
$stmt = com_db()->prepare("SELECT estado, COUNT(*) c FROM presupuestos WHERE usuario_id=? GROUP BY estado");
$stmt->execute([$uid]);
$conteo = ['pendiente' => 0, 'vendido' => 0];
foreach ($stmt->fetchAll() as $f) { $conteo[$f['estado']] = (int) $f['c']; }
$total_todos = $conteo['pendiente'] + $conteo['vendido'];

$sql = "SELECT p.*,
               (SELECT COUNT(*) FROM presupuesto_items i WHERE i.presupuesto_id = p.id) AS items,
               (SELECT COALESCE(SUM(i.precio_unit * i.cantidad),0) FROM presupuesto_items i WHERE i.presupuesto_id = p.id) AS subtotal
          FROM presupuestos p WHERE p.usuario_id = ?";
$par = [$uid];
if ($tab === 'pendientes') { $sql .= " AND p.estado = 'pendiente'"; }
if ($tab === 'vendidos')   { $sql .= " AND p.estado = 'vendido'"; }
if ($q !== '') { $sql .= ' AND p.cliente_nombre LIKE ?'; $par[] = "%{$q}%"; }
$sql .= $orden === 'antiguos' ? ' ORDER BY p.creado_en ASC'
      : ($orden === 'mayor' ? ' ORDER BY subtotal DESC' : ' ORDER BY p.creado_en DESC');
$stmt = com_db()->prepare($sql);
$stmt->execute($par);
$presupuestos = $stmt->fetchAll();

function url_lista($tab, $q, $orden) {
    return 'presupuestos.php?' . http_build_query(array_filter(['tab' => $tab === 'todos' ? null : $tab, 'q' => $q ?: null, 'orden' => $orden === 'recientes' ? null : $orden]));
}

ui_panel_inicio('Presupuestos', $u, 'Presupuestos');
?>
    <style>.contenido{max-width:none}</style>
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap">
      <div>
        <h1>Presupuestos</h1>
        <p class="bajada">Generá y enviá presupuestos profesionales a tus clientes.</p>
      </div>
      <a class="btn" href="presupuesto.php">+ Nuevo presupuesto</a>
    </div>

    <?php if ($aviso): ?><div class="msg ok"><?php echo ui_icono('check', 16); ?><span><?php echo htmlspecialchars($aviso); ?></span></div><?php endif; ?>
    <?php if ($error): ?><div class="msg bad"><?php echo ui_icono('alerta', 16); ?><span><?php echo htmlspecialchars($error); ?></span></div><?php endif; ?>

    <style>
      .buscador{margin-bottom:18px}
      .tabs-fila{display:flex;align-items:center;justify-content:space-between;gap:14px;
                 border-bottom:1px solid var(--bd-suave);margin-bottom:0;flex-wrap:wrap}
      .tabs{display:flex;gap:4px}
      .tab{display:inline-flex;align-items:center;gap:8px;padding:10px 14px;font-size:14px;font-weight:600;
           color:var(--txt-2);border-bottom:2px solid transparent;margin-bottom:-1px}
      .tab:hover{color:var(--txt)}
      .tab.activa{color:var(--txt);border-bottom-color:var(--accent)}
      .tab .cuenta{font-size:11px;font-weight:600;background:var(--surface-2);border-radius:99px;
                   padding:2px 8px;color:var(--txt-2)}
      .tab.activa .cuenta{background:var(--accent);color:var(--accent-ink)}
      .orden{display:flex;align-items:center;gap:8px;font-size:13px;color:var(--txt-3)}
      .orden select{width:auto;height:34px;font-size:13px}
      .panel{background:var(--surface);border:1px solid var(--bd-suave);border-radius:0 0 var(--radio-g) var(--radio-g);
             border-top:none;overflow:hidden}
      table{width:100%;border-collapse:collapse;font-size:13.5px}
      th,td{padding:13px 16px;text-align:left;border-bottom:1px solid var(--bd-suave);vertical-align:middle}
      th{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.07em;color:var(--txt-3)}
      tr:last-child td{border-bottom:none}
      tbody tr{transition:background-color .15s ease}
      tbody tr:hover{background:var(--surface-2)}
      td.num,th.num{text-align:right;font-variant-numeric:tabular-nums;white-space:nowrap}
      td.fecha{color:var(--txt-2);white-space:nowrap;font-variant-numeric:tabular-nums}
      .estado{display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:500;
              padding:3px 10px;border-radius:99px;white-space:nowrap}
      .estado::before{content:'';width:6px;height:6px;border-radius:99px;background:currentColor}
      .estado.vendido{background:var(--ok-tinte);color:var(--ok)}
      .estado.pendiente{background:var(--warn-tinte);color:var(--warn)}
      td .acciones{display:flex;gap:6px;justify-content:flex-end;flex-wrap:wrap}
      td form{margin:0}
      .vacio{padding:52px 20px;text-align:center;color:var(--txt-2);font-size:14px}
      @media (max-width:900px){ .tabla-scroll{overflow-x:auto} }
    </style>

    <form class="buscador" method="get">
      <?php if ($tab !== 'todos'): ?><input type="hidden" name="tab" value="<?php echo $tab; ?>"><?php endif; ?>
      <?php if ($orden !== 'recientes'): ?><input type="hidden" name="orden" value="<?php echo $orden; ?>"><?php endif; ?>
      <input type="search" name="q" placeholder="Buscar por cliente..." value="<?php echo htmlspecialchars($q); ?>">
    </form>

    <div class="tabs-fila">
      <div class="tabs">
        <a class="tab<?php echo $tab === 'todos' ? ' activa' : ''; ?>" href="<?php echo url_lista('todos', $q, $orden); ?>">
          Todos <span class="cuenta"><?php echo $total_todos; ?></span></a>
        <a class="tab<?php echo $tab === 'pendientes' ? ' activa' : ''; ?>" href="<?php echo url_lista('pendientes', $q, $orden); ?>">
          Pendientes <span class="cuenta"><?php echo $conteo['pendiente']; ?></span></a>
        <a class="tab<?php echo $tab === 'vendidos' ? ' activa' : ''; ?>" href="<?php echo url_lista('vendidos', $q, $orden); ?>">
          Vendidos <span class="cuenta"><?php echo $conteo['vendido']; ?></span></a>
      </div>
      <form class="orden" method="get">
        <?php if ($tab !== 'todos'): ?><input type="hidden" name="tab" value="<?php echo $tab; ?>"><?php endif; ?>
        <?php if ($q !== ''): ?><input type="hidden" name="q" value="<?php echo htmlspecialchars($q); ?>"><?php endif; ?>
        <label for="orden" style="margin:0;font-size:13px">Ordenar:</label>
        <select id="orden" name="orden" onchange="this.form.submit()">
          <option value="recientes" <?php echo $orden === 'recientes' ? 'selected' : ''; ?>>Más recientes</option>
          <option value="antiguos" <?php echo $orden === 'antiguos' ? 'selected' : ''; ?>>Más antiguos</option>
          <option value="mayor" <?php echo $orden === 'mayor' ? 'selected' : ''; ?>>Mayor total</option>
        </select>
      </form>
    </div>

    <div class="panel tabla-scroll">
      <?php if (!$presupuestos): ?>
        <div class="vacio">
          <?php echo $q || $tab !== 'todos' ? 'No hay presupuestos que coincidan.' : 'Todavía no creaste ningún presupuesto.'; ?><br>
          <a class="btn" style="margin-top:16px" href="presupuesto.php">+ Nuevo presupuesto</a>
        </div>
      <?php else: ?>
      <table>
        <thead><tr><th>Cliente</th><th>Fecha</th><th class="num">Piezas</th><th class="num">Total</th><th>Estado</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($presupuestos as $p):
            [$st, $dc, $tot] = taller_totales($p, [['precio_unit' => $p['subtotal'], 'cantidad' => 1]]); ?>
          <tr>
            <td><strong><?php echo htmlspecialchars($p['cliente_nombre'] ?: 'Sin nombre'); ?></strong></td>
            <td class="fecha"><?php echo date('d/m/y H:i', strtotime($p['creado_en'])); ?></td>
            <td class="num"><?php echo (int) $p['items']; ?></td>
            <td class="num"><strong><?php echo taller_precio($tot); ?></strong></td>
            <td><span class="estado <?php echo $p['estado']; ?>"><?php echo $p['estado'] === 'vendido' ? 'Vendido' : 'Pendiente'; ?></span></td>
            <td>
              <div class="acciones">
                <a class="btn chico sec" href="presupuesto.php?id=<?php echo (int) $p['id']; ?>">Abrir</a>
                <form method="post">
                  <input type="hidden" name="csrf" value="<?php echo com_csrf(); ?>">
                  <input type="hidden" name="accion" value="estado">
                  <input type="hidden" name="presupuesto_id" value="<?php echo (int) $p['id']; ?>">
                  <input type="hidden" name="estado" value="<?php echo $p['estado'] === 'vendido' ? 'pendiente' : 'vendido'; ?>">
                  <button class="btn chico <?php echo $p['estado'] === 'vendido' ? 'sec' : ''; ?>" type="submit">
                    <?php echo $p['estado'] === 'vendido' ? 'Volver a pendiente' : 'Marcar vendido'; ?></button>
                </form>
                <form method="post" onsubmit="return confirm('¿Eliminar el presupuesto de «<?php echo htmlspecialchars($p['cliente_nombre'], ENT_QUOTES); ?>»?');">
                  <input type="hidden" name="csrf" value="<?php echo com_csrf(); ?>">
                  <input type="hidden" name="accion" value="eliminar">
                  <input type="hidden" name="presupuesto_id" value="<?php echo (int) $p['id']; ?>">
                  <button class="btn chico peligro" type="submit">Eliminar</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
<?php ui_panel_fin(); ?>
