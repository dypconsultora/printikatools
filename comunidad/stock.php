<?php
/**
 * Stock Materiales: rollos de filamento (con descuento automático al marcar
 * un presupuesto como vendido) y otros insumos del taller.
 */
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/ui.php';
require_once __DIR__ . '/inc/taller.php';

requerir_miembro();
$u = usuario_actual();
taller_migrar();
$uid = (int) $u['id'];
$db  = com_db();

$tab   = ($_GET['tab'] ?? '') === 'insumos' ? 'insumos' : 'filamentos';
$error = '';
$abrir_rollo  = null;  // datos para abrir el panel de rollo (edición o error)
$abrir_insumo = null;

$AVISOS = [
    'rollo_nuevo'  => 'Rollo agregado al stock.',
    'rollo_edit'   => 'Rollo actualizado.',
    'rollo_del'    => 'Rollo eliminado.',
    'insumo_nuevo' => 'Insumo agregado al stock.',
    'insumo_edit'  => 'Insumo actualizado.',
    'insumo_del'   => 'Insumo eliminado.',
];
$aviso = $AVISOS[$_GET['ok'] ?? ''] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    if (!com_csrf_ok($_POST['csrf'] ?? '')) {
        $error = 'La sesión expiró, probá de nuevo.';
    } elseif ($accion === 'rollo_guardar') {
        $id     = (int) ($_POST['id'] ?? 0);
        $marca  = mb_substr(trim($_POST['marca'] ?? ''), 0, 100);
        $tipo   = in_array($_POST['tipo'] ?? '', taller_tipos_rollo(), true) ? $_POST['tipo'] : 'PLA';
        $color  = mb_substr(trim($_POST['color'] ?? ''), 0, 60);
        $peso_o = max(1, (int) ($_POST['peso_original'] ?? 0));
        $peso_d = max(0, (int) ($_POST['peso_disponible'] ?? 0));
        $costo  = max(0, (float) str_replace(',', '.', $_POST['costo_kilo'] ?? '0'));

        if ($marca === '' || $color === '') {
            $error = 'Completá la marca y el color del rollo.';
        } elseif ($peso_d > $peso_o) {
            $error = 'El peso disponible no puede ser mayor que el peso original.';
        } else {
            if ($id > 0) {
                $db->prepare('UPDATE rollos SET marca=?, tipo=?, color=?, peso_original=?, peso_disponible=?, costo_kilo=?
                              WHERE id=? AND usuario_id=?')
                   ->execute([$marca, $tipo, $color, $peso_o, $peso_d, $costo, $id, $uid]);
                header('Location: stock.php?ok=rollo_edit');
            } else {
                $db->prepare('INSERT INTO rollos (usuario_id, marca, tipo, color, peso_original, peso_disponible, costo_kilo, creado_en)
                              VALUES (?,?,?,?,?,?,?,NOW())')
                   ->execute([$uid, $marca, $tipo, $color, $peso_o, $peso_d, $costo]);
                header('Location: stock.php?ok=rollo_nuevo');
            }
            exit;
        }
        $tab = 'filamentos';
        $abrir_rollo = ['id' => $id, 'marca' => $marca, 'tipo' => $tipo, 'color' => $color,
                        'peso_original' => $peso_o, 'peso_disponible' => $peso_d, 'costo_kilo' => $costo];
    } elseif ($accion === 'rollo_eliminar') {
        $db->prepare('DELETE FROM rollos WHERE id=? AND usuario_id=?')
           ->execute([(int) ($_POST['id'] ?? 0), $uid]);
        header('Location: stock.php?ok=rollo_del');
        exit;
    } elseif ($accion === 'insumo_guardar') {
        $id       = (int) ($_POST['id'] ?? 0);
        $nombre   = mb_substr(trim($_POST['nombre'] ?? ''), 0, 150);
        $tipo_i   = mb_substr(trim($_POST['tipo'] ?? ''), 0, 100);
        $cantidad = max(0, (float) str_replace(',', '.', $_POST['cantidad'] ?? '0'));
        $unidad   = mb_substr(trim($_POST['unidad'] ?? ''), 0, 30);
        $aviso_m  = max(0, (float) str_replace(',', '.', $_POST['aviso_minimo'] ?? '0'));
        if ($unidad === '') $unidad = 'unidades';

        if ($nombre === '') {
            $error = 'Ingresá el nombre del insumo.';
        } else {
            if ($id > 0) {
                $db->prepare('UPDATE insumos SET nombre=?, tipo=?, cantidad=?, unidad=?, aviso_minimo=?
                              WHERE id=? AND usuario_id=?')
                   ->execute([$nombre, $tipo_i, $cantidad, $unidad, $aviso_m, $id, $uid]);
                header('Location: stock.php?tab=insumos&ok=insumo_edit');
            } else {
                $db->prepare('INSERT INTO insumos (usuario_id, nombre, tipo, cantidad, unidad, aviso_minimo, creado_en)
                              VALUES (?,?,?,?,?,?,NOW())')
                   ->execute([$uid, $nombre, $tipo_i, $cantidad, $unidad, $aviso_m]);
                header('Location: stock.php?tab=insumos&ok=insumo_nuevo');
            }
            exit;
        }
        $tab = 'insumos';
        $abrir_insumo = ['id' => $id, 'nombre' => $nombre, 'tipo' => $tipo_i,
                         'cantidad' => $cantidad, 'unidad' => $unidad, 'aviso_minimo' => $aviso_m];
    } elseif ($accion === 'insumo_eliminar') {
        $db->prepare('DELETE FROM insumos WHERE id=? AND usuario_id=?')
           ->execute([(int) ($_POST['id'] ?? 0), $uid]);
        header('Location: stock.php?tab=insumos&ok=insumo_del');
        exit;
    } elseif ($accion === 'insumo_ajustar') {
        $delta = (float) ($_POST['delta'] ?? 0);
        $db->prepare('UPDATE insumos SET cantidad = GREATEST(0, cantidad + ?) WHERE id=? AND usuario_id=?')
           ->execute([$delta, (int) ($_POST['id'] ?? 0), $uid]);
        header('Location: stock.php?tab=insumos');
        exit;
    }
}

// Edición: abrir el panel con los datos cargados
if (!$abrir_rollo && preg_match('/^\d+$/', $_GET['editar_rollo'] ?? '')) {
    $stmt = $db->prepare('SELECT * FROM rollos WHERE id=? AND usuario_id=?');
    $stmt->execute([(int) $_GET['editar_rollo'], $uid]);
    $abrir_rollo = $stmt->fetch() ?: null;
    if ($abrir_rollo) $tab = 'filamentos';
}
if (!$abrir_insumo && preg_match('/^\d+$/', $_GET['editar_insumo'] ?? '')) {
    $stmt = $db->prepare('SELECT * FROM insumos WHERE id=? AND usuario_id=?');
    $stmt->execute([(int) $_GET['editar_insumo'], $uid]);
    $abrir_insumo = $stmt->fetch() ?: null;
    if ($abrir_insumo) $tab = 'insumos';
}

$stmt = $db->prepare('SELECT * FROM rollos WHERE usuario_id=? ORDER BY creado_en DESC, id DESC');
$stmt->execute([$uid]);
$rollos = $stmt->fetchAll();

$stmt = $db->prepare('SELECT * FROM insumos WHERE usuario_id=? ORDER BY creado_en DESC, id DESC');
$stmt->execute([$uid]);
$insumos = $stmt->fetchAll();

/** Formatea una cantidad decimal sin ceros de más (2,5 / 3). */
function stock_cant($n) {
    $s = number_format((float) $n, 2, ',', '.');
    return rtrim(rtrim($s, '0'), ',');
}

ui_panel_inicio('Stock Materiales', $u, 'Stock Materiales');
?>
    <style>.contenido{max-width:none}</style>
    <h1>Stock Materiales</h1>
    <p class="bajada">Llevá el control de tus rollos de filamento e insumos del taller.</p>

    <?php if ($aviso): ?><div class="msg ok"><?php echo ui_icono('check', 16); ?><span><?php echo htmlspecialchars($aviso); ?></span></div><?php endif; ?>
    <?php if ($error): ?><div class="msg bad"><?php echo ui_icono('alerta', 16); ?><span><?php echo htmlspecialchars($error); ?></span></div><?php endif; ?>

    <style>
      .tabs{display:flex;gap:26px;border-bottom:1px solid var(--bd-suave);margin-bottom:18px}
      .tabs a{display:flex;align-items:center;gap:9px;padding:12px 2px 13px;font-size:14.5px;font-weight:600;
              color:var(--txt-2);border-bottom:2px solid transparent;margin-bottom:-1px}
      .tabs a:hover{color:var(--txt)}
      .tabs a.activa{color:var(--txt);border-bottom-color:var(--accent)}
      .tabs a .cant{font-size:12px;font-weight:600;color:var(--txt-3);background:var(--surface-2);
              border-radius:999px;padding:1px 8px}
      .barra-sup{display:flex;align-items:center;justify-content:space-between;gap:14px;margin-bottom:16px;flex-wrap:wrap}
      .barra-sup p{font-size:14px;color:var(--txt-2)}
      .vacio{border:1px dashed var(--bd);border-radius:var(--radio-g);padding:70px 24px;text-align:center}
      .vacio .circ{width:64px;height:64px;border-radius:50%;background:var(--surface-2);color:var(--txt-2);
              display:flex;align-items:center;justify-content:center;margin:0 auto 18px}
      .vacio h2{font-size:18px;font-weight:700;margin-bottom:8px}
      .vacio p{font-size:14px;color:var(--txt-2);max-width:480px;margin:0 auto;line-height:1.6}
      .tabla-caja{background:var(--surface);border:1px solid var(--bd-suave);border-radius:var(--radio-g);
              padding:6px 20px;overflow-x:auto}
      table{width:100%;border-collapse:collapse;font-size:13.5px}
      th,td{padding:13px 10px;text-align:left;border-bottom:1px solid var(--bd-suave);vertical-align:middle}
      th{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.07em;color:var(--txt-3);white-space:nowrap}
      tr:last-child td{border-bottom:none}
      td.num,th.num{text-align:right;font-variant-numeric:tabular-nums;white-space:nowrap}
      .rollo-nom b{display:block;font-weight:600}
      .rollo-nom small{font-size:12px;color:var(--txt-3)}
      .chip-tipo{display:inline-block;font-size:11.5px;font-weight:600;padding:3px 10px;border-radius:999px;
              background:var(--accent-tinte);color:var(--accent)}
      .nivel{min-width:190px}
      .nivel .datos{display:flex;justify-content:space-between;font-size:12px;color:var(--txt-2);
              font-variant-numeric:tabular-nums;margin-bottom:5px}
      .nivel .pista{height:7px;border-radius:999px;background:var(--surface-2);overflow:hidden}
      .nivel .pista i{display:block;height:100%;border-radius:999px;background:var(--ok)}
      .nivel.medio .pista i{background:var(--warn)}
      .nivel.bajo .pista i{background:var(--bad)}
      .badge-bajo{display:inline-flex;align-items:center;gap:5px;font-size:11.5px;font-weight:600;
              padding:3px 10px;border-radius:999px;background:var(--warn-tinte);color:var(--warn)}
      .badge-agotado{display:inline-flex;font-size:11.5px;font-weight:600;padding:3px 10px;border-radius:999px;
              background:var(--bad-tinte);color:var(--bad)}
      .acciones{display:flex;gap:6px;justify-content:flex-end}
      .btn-ico{display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;
              border-radius:var(--radio);border:1px solid var(--bd);background:none;color:var(--txt-2);cursor:pointer}
      .btn-ico:hover{color:var(--txt);border-color:var(--txt-3)}
      .btn-ico.peligro:hover{color:var(--bad);border-color:var(--bad)}
      .btn-mini{display:inline-flex;align-items:center;justify-content:center;min-width:32px;height:32px;
              padding:0 8px;border-radius:var(--radio);border:1px solid var(--bd);background:none;color:var(--txt-2);
              font-size:13px;font-weight:600;cursor:pointer;font-variant-numeric:tabular-nums}
      .btn-mini:hover{color:var(--txt);border-color:var(--txt-3)}

      /* Panel lateral (drawer) */
      .velo-stock{position:fixed;inset:0;background:rgba(5,8,14,.6);z-index:80;display:flex;justify-content:flex-end}
      .velo-stock[hidden]{display:none !important}
      .drawer{width:min(460px,100%);height:100%;background:var(--surface);border-left:1px solid var(--bd-suave);
              display:flex;flex-direction:column;animation:drawerIn .22s ease}
      @keyframes drawerIn{from{transform:translateX(30px);opacity:0}to{transform:none;opacity:1}}
      .drawer-cab{display:flex;align-items:center;justify-content:space-between;padding:18px 24px;
              border-bottom:1px solid var(--bd-suave)}
      .drawer-cab h2{font-size:17px;font-weight:700}
      .drawer-cerrar{background:none;border:none;color:var(--txt-3);cursor:pointer;display:flex;padding:4px}
      .drawer-cerrar:hover{color:var(--txt)}
      .drawer-cuerpo{flex:1;overflow-y:auto;padding:22px 24px}
      .drawer-pie{display:flex;justify-content:flex-end;gap:10px;padding:16px 24px;border-top:1px solid var(--bd-suave)}
      .drawer .ayuda{font-size:12.5px;color:var(--txt-3);line-height:1.55;margin-top:6px}
      .drawer .intro{font-size:13px;color:var(--txt-2);line-height:1.6;margin:14px 0 10px}
      .drawer .fila-2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
      .btn-suave{background:none;border:none;color:var(--txt-2);font-size:14px;font-weight:600;cursor:pointer;padding:10px 14px}
      .btn-suave:hover{color:var(--txt)}
      @media (max-width:700px){ .nivel{min-width:130px} .tabla-caja{padding:2px 10px} }
    </style>

    <div class="tabs">
      <a href="stock.php" class="<?php echo $tab === 'filamentos' ? 'activa' : ''; ?>">
        <?php echo ui_icono('cajas', 18); ?>Filamentos
        <?php if ($rollos): ?><span class="cant"><?php echo count($rollos); ?></span><?php endif; ?>
      </a>
      <a href="stock.php?tab=insumos" class="<?php echo $tab === 'insumos' ? 'activa' : ''; ?>">
        <?php echo ui_icono('paquete', 18); ?>Otros materiales
        <?php if ($insumos): ?><span class="cant"><?php echo count($insumos); ?></span><?php endif; ?>
      </a>
    </div>

<?php if ($tab === 'filamentos'): ?>
    <div class="barra-sup">
      <p>Cargá tus rollos de filamento.</p>
      <button class="btn" type="button" id="btnNuevoRollo"><?php echo ui_icono('mas', 16); ?> Agregar rollo</button>
    </div>

    <?php if (!$rollos): ?>
      <div class="vacio">
        <div class="circ"><?php echo ui_icono('cajas', 26); ?></div>
        <h2>Todavía no tenés rollos cargados</h2>
        <p>Tocá "Agregar rollo" para sumar tu primer filamento.
           Después vas a poder descontar stock automáticamente al marcar un presupuesto como vendido.</p>
      </div>
    <?php else: ?>
      <div class="tabla-caja">
        <table>
          <thead><tr>
            <th>Rollo</th><th>Tipo</th><th>Disponible</th><th class="num">Costo por kilo</th>
            <th></th><th class="num">Acciones</th>
          </tr></thead>
          <tbody>
          <?php foreach ($rollos as $r):
              $pct = $r['peso_original'] > 0 ? $r['peso_disponible'] / $r['peso_original'] * 100 : 0;
              $clase = $pct <= 0 ? 'bajo' : ($pct <= 20 ? 'bajo' : ($pct <= 50 ? 'medio' : '')); ?>
            <tr>
              <td class="rollo-nom"><b><?php echo htmlspecialchars($r['marca']); ?></b>
                <small><?php echo htmlspecialchars($r['color']); ?></small></td>
              <td><span class="chip-tipo"><?php echo htmlspecialchars($r['tipo']); ?></span></td>
              <td class="nivel <?php echo $clase; ?>">
                <div class="datos">
                  <span><?php echo number_format((int) $r['peso_disponible'], 0, ',', '.'); ?> g
                        / <?php echo number_format((int) $r['peso_original'], 0, ',', '.'); ?> g</span>
                  <span><?php echo round($pct); ?>%</span>
                </div>
                <div class="pista"><i style="width:<?php echo max(0, min(100, round($pct, 1))); ?>%"></i></div>
              </td>
              <td class="num"><?php echo $r['costo_kilo'] > 0 ? taller_precio($r['costo_kilo']) : '—'; ?></td>
              <td>
                <?php if ((int) $r['peso_disponible'] <= 0): ?>
                  <span class="badge-agotado">Agotado</span>
                <?php elseif ($pct <= 20): ?>
                  <span class="badge-bajo"><?php echo ui_icono('alerta', 13); ?>Queda poco</span>
                <?php endif; ?>
              </td>
              <td>
                <div class="acciones">
                  <a class="btn-ico" href="stock.php?editar_rollo=<?php echo (int) $r['id']; ?>" title="Editar">
                    <?php echo ui_icono('lapiz', 15); ?></a>
                  <form method="post" onsubmit="return confirm('¿Eliminar este rollo del stock?')">
                    <input type="hidden" name="csrf" value="<?php echo com_csrf(); ?>">
                    <input type="hidden" name="accion" value="rollo_eliminar">
                    <input type="hidden" name="id" value="<?php echo (int) $r['id']; ?>">
                    <button class="btn-ico peligro" type="submit" title="Eliminar"><?php echo ui_icono('basura', 15); ?></button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

<?php else: ?>
    <div class="barra-sup">
      <p>Anotá los repuestos e insumos de tu taller.</p>
      <button class="btn" type="button" id="btnNuevoInsumo"><?php echo ui_icono('mas', 16); ?> Agregar insumo</button>
    </div>

    <?php if (!$insumos): ?>
      <div class="vacio">
        <div class="circ"><?php echo ui_icono('paquete', 26); ?></div>
        <h2>Todavía no tenés insumos cargados</h2>
        <p>Tocá "Agregar insumo" para sumar boquillas, alcohol isopropílico, repuestos
           o cualquier material de tu taller y controlar cuánto te queda.</p>
      </div>
    <?php else: ?>
      <div class="tabla-caja">
        <table>
          <thead><tr>
            <th>Insumo</th><th>Tipo</th><th class="num">Cantidad</th><th></th><th class="num">Acciones</th>
          </tr></thead>
          <tbody>
          <?php foreach ($insumos as $i):
              $bajo = $i['aviso_minimo'] > 0 && $i['cantidad'] <= $i['aviso_minimo']; ?>
            <tr>
              <td class="rollo-nom"><b><?php echo htmlspecialchars($i['nombre']); ?></b></td>
              <td><?php echo $i['tipo'] !== '' ? htmlspecialchars($i['tipo']) : '<span style="color:var(--txt-3)">—</span>'; ?></td>
              <td class="num"><strong><?php echo stock_cant($i['cantidad']); ?></strong>
                <span style="color:var(--txt-3)"><?php echo htmlspecialchars($i['unidad']); ?></span></td>
              <td><?php if ($bajo): ?><span class="badge-bajo"><?php echo ui_icono('alerta', 13); ?>Bajo stock</span><?php endif; ?></td>
              <td>
                <div class="acciones">
                  <form method="post">
                    <input type="hidden" name="csrf" value="<?php echo com_csrf(); ?>">
                    <input type="hidden" name="accion" value="insumo_ajustar">
                    <input type="hidden" name="id" value="<?php echo (int) $i['id']; ?>">
                    <input type="hidden" name="delta" value="-1">
                    <button class="btn-mini" type="submit" title="Restar 1">-1</button>
                  </form>
                  <form method="post">
                    <input type="hidden" name="csrf" value="<?php echo com_csrf(); ?>">
                    <input type="hidden" name="accion" value="insumo_ajustar">
                    <input type="hidden" name="id" value="<?php echo (int) $i['id']; ?>">
                    <input type="hidden" name="delta" value="1">
                    <button class="btn-mini" type="submit" title="Sumar 1">+1</button>
                  </form>
                  <a class="btn-ico" href="stock.php?editar_insumo=<?php echo (int) $i['id']; ?>" title="Editar">
                    <?php echo ui_icono('lapiz', 15); ?></a>
                  <form method="post" onsubmit="return confirm('¿Eliminar este insumo del stock?')">
                    <input type="hidden" name="csrf" value="<?php echo com_csrf(); ?>">
                    <input type="hidden" name="accion" value="insumo_eliminar">
                    <input type="hidden" name="id" value="<?php echo (int) $i['id']; ?>">
                    <button class="btn-ico peligro" type="submit" title="Eliminar"><?php echo ui_icono('basura', 15); ?></button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
<?php endif; ?>

    <!-- Panel: nuevo / editar rollo -->
    <div class="velo-stock" id="veloRollo" <?php echo $abrir_rollo === null ? 'hidden' : ''; ?>>
      <aside class="drawer">
        <div class="drawer-cab">
          <h2><?php echo !empty($abrir_rollo['id']) ? 'Editar rollo' : 'Nuevo rollo'; ?></h2>
          <button class="drawer-cerrar" type="button" data-cerrar="veloRollo"><?php echo ui_icono('cerrar', 20); ?></button>
        </div>
        <form method="post" style="display:flex;flex-direction:column;flex:1;min-height:0">
          <div class="drawer-cuerpo">
            <input type="hidden" name="csrf" value="<?php echo com_csrf(); ?>">
            <input type="hidden" name="accion" value="rollo_guardar">
            <input type="hidden" name="id" value="<?php echo (int) ($abrir_rollo['id'] ?? 0); ?>">

            <label for="r-marca">Marca *</label>
            <input id="r-marca" type="text" name="marca" maxlength="100" required placeholder="GST, 3NMAX, BAMBULAB..."
                   value="<?php echo htmlspecialchars($abrir_rollo['marca'] ?? ''); ?>">

            <label for="r-tipo">Tipo</label>
            <select id="r-tipo" name="tipo">
              <?php foreach (taller_tipos_rollo() as $t): ?>
                <option value="<?php echo $t; ?>" <?php echo ($abrir_rollo['tipo'] ?? 'PLA') === $t ? 'selected' : ''; ?>><?php echo $t; ?></option>
              <?php endforeach; ?>
            </select>

            <label for="r-color">Color *</label>
            <input id="r-color" type="text" name="color" maxlength="60" required list="coloresComunes"
                   placeholder="Negro, blanco, rojo..." value="<?php echo htmlspecialchars($abrir_rollo['color'] ?? ''); ?>">
            <datalist id="coloresComunes">
              <option>Negro</option><option>Blanco</option><option>Gris</option><option>Rojo</option>
              <option>Azul</option><option>Verde</option><option>Amarillo</option><option>Naranja</option>
              <option>Violeta</option><option>Rosa</option><option>Transparente</option>
            </datalist>

            <p class="intro">Si el rollo es nuevo, los dos valores son iguales. Si ya lo empezaste a usar,
               cargá el peso original (cuando estaba lleno) y cuánto te queda hoy.</p>
            <div class="fila-2">
              <span>
                <label for="r-peso-o">Peso original (g)</label>
                <input id="r-peso-o" type="number" name="peso_original" min="1" step="1" required
                       value="<?php echo (int) ($abrir_rollo['peso_original'] ?? 1000); ?>">
                <p class="ayuda">Cuánto pesa el rollo lleno (normalmente 1 kg = 1000 g).</p>
              </span>
              <span>
                <label for="r-peso-d">Peso disponible (g)</label>
                <input id="r-peso-d" type="number" name="peso_disponible" min="0" step="1" required
                       value="<?php echo (int) ($abrir_rollo['peso_disponible'] ?? 1000); ?>">
                <p class="ayuda">Lo que te queda hoy. Si es nuevo, dejalo igual al peso original.</p>
              </span>
            </div>

            <label for="r-costo">Costo por kilo (opcional)</label>
            <input id="r-costo" type="number" name="costo_kilo" min="0" step="0.01"
                   value="<?php echo (float) ($abrir_rollo['costo_kilo'] ?? 0) > 0 ? htmlspecialchars($abrir_rollo['costo_kilo']) : ''; ?>"
                   placeholder="0">
          </div>
          <div class="drawer-pie">
            <button class="btn-suave" type="button" data-cerrar="veloRollo">Cancelar</button>
            <button class="btn" type="submit"><?php echo !empty($abrir_rollo['id']) ? 'Guardar cambios' : 'Agregar rollo'; ?></button>
          </div>
        </form>
      </aside>
    </div>

    <!-- Panel: nuevo / editar insumo -->
    <div class="velo-stock" id="veloInsumo" <?php echo $abrir_insumo === null ? 'hidden' : ''; ?>>
      <aside class="drawer">
        <div class="drawer-cab">
          <h2><?php echo !empty($abrir_insumo['id']) ? 'Editar insumo' : 'Nuevo insumo'; ?></h2>
          <button class="drawer-cerrar" type="button" data-cerrar="veloInsumo"><?php echo ui_icono('cerrar', 20); ?></button>
        </div>
        <form method="post" style="display:flex;flex-direction:column;flex:1;min-height:0">
          <div class="drawer-cuerpo">
            <input type="hidden" name="csrf" value="<?php echo com_csrf(); ?>">
            <input type="hidden" name="accion" value="insumo_guardar">
            <input type="hidden" name="id" value="<?php echo (int) ($abrir_insumo['id'] ?? 0); ?>">

            <label for="i-nombre">Nombre *</label>
            <input id="i-nombre" type="text" name="nombre" maxlength="150" required
                   placeholder="Boquilla 0.4mm, alcohol isopropílico..."
                   value="<?php echo htmlspecialchars($abrir_insumo['nombre'] ?? ''); ?>">

            <label for="i-tipo">Tipo (opcional)</label>
            <input id="i-tipo" type="text" name="tipo" maxlength="100" placeholder="Repuesto, limpieza..."
                   value="<?php echo htmlspecialchars($abrir_insumo['tipo'] ?? ''); ?>">

            <div class="fila-2">
              <span>
                <label for="i-cantidad">Cantidad</label>
                <input id="i-cantidad" type="number" name="cantidad" min="0" step="0.01"
                       value="<?php echo htmlspecialchars(stock_cant($abrir_insumo['cantidad'] ?? 0) === '0' ? '0' : rtrim(rtrim(number_format((float) ($abrir_insumo['cantidad'] ?? 0), 2, '.', ''), '0'), '.')); ?>">
              </span>
              <span>
                <label for="i-unidad">Unidad</label>
                <input id="i-unidad" type="text" name="unidad" maxlength="30" list="unidadesComunes"
                       value="<?php echo htmlspecialchars($abrir_insumo['unidad'] ?? 'unidades'); ?>">
                <datalist id="unidadesComunes">
                  <option>unidades</option><option>litros</option><option>metros</option><option>gramos</option><option>packs</option>
                </datalist>
              </span>
            </div>

            <label for="i-aviso">Aviso de bajo stock (opcional)</label>
            <input id="i-aviso" type="number" name="aviso_minimo" min="0" step="0.01"
                   value="<?php echo (float) ($abrir_insumo['aviso_minimo'] ?? 0) > 0 ? htmlspecialchars(rtrim(rtrim(number_format((float) $abrir_insumo['aviso_minimo'], 2, '.', ''), '0'), '.')) : ''; ?>"
                   placeholder="0">
            <p class="ayuda">Te avisamos cuando la cantidad quede en este número o menos.</p>
          </div>
          <div class="drawer-pie">
            <button class="btn-suave" type="button" data-cerrar="veloInsumo">Cancelar</button>
            <button class="btn" type="submit"><?php echo !empty($abrir_insumo['id']) ? 'Guardar cambios' : 'Agregar insumo'; ?></button>
          </div>
        </form>
      </aside>
    </div>

    <script>
    (function () {
      const abrir = (id) => { const v = document.getElementById(id); v.hidden = false; v.querySelector('input,select').focus(); };
      const cerrar = (id) => { document.getElementById(id).hidden = true; };
      const bNR = document.getElementById('btnNuevoRollo');
      const bNI = document.getElementById('btnNuevoInsumo');
      if (bNR) bNR.addEventListener('click', () => abrir('veloRollo'));
      if (bNI) bNI.addEventListener('click', () => abrir('veloInsumo'));
      document.querySelectorAll('[data-cerrar]').forEach(b =>
        b.addEventListener('click', () => cerrar(b.dataset.cerrar)));
      document.querySelectorAll('.velo-stock').forEach(v =>
        v.addEventListener('click', (e) => { if (e.target === v) v.hidden = true; }));
      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') document.querySelectorAll('.velo-stock').forEach(v => v.hidden = true);
      });

      // Rollo nuevo: el peso disponible acompaña al original hasta que lo toquen a mano
      const pesoO = document.getElementById('r-peso-o');
      const pesoD = document.getElementById('r-peso-d');
      const esNuevo = <?php echo empty($abrir_rollo['id']) ? 'true' : 'false'; ?>;
      let tocado = false;
      if (pesoO && pesoD && esNuevo) {
        pesoD.addEventListener('input', () => { tocado = true; });
        pesoO.addEventListener('input', () => { if (!tocado) pesoD.value = pesoO.value; });
      }
    })();
    </script>
<?php ui_panel_fin(); ?>
