<?php
/**
 * Productos: catálogo de piezas del usuario.
 * Se cargan acá o se guardan desde la calculadora del presupuesto.
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
        $pid    = (int) ($_POST['producto_id'] ?? 0);
        if ($accion === 'guardar') {
            $nombre = trim($_POST['nombre'] ?? '');
            $desc   = trim($_POST['descripcion'] ?? '');
            $costo  = max(0, (float) ($_POST['costo'] ?? 0));
            $precio = max(0, (float) ($_POST['precio'] ?? 0));
            if ($nombre === '' || mb_strlen($nombre) > 150) {
                $error = 'Ingresá el nombre del producto.';
            } elseif ($pid) {
                com_db()->prepare('UPDATE productos SET nombre=?, descripcion=?, costo=?, precio=?, actualizado_en=NOW()
                                    WHERE id=? AND usuario_id=?')
                    ->execute([$nombre, $desc, $costo, $precio, $pid, $uid]);
                $aviso = 'Producto actualizado.';
            } else {
                com_db()->prepare('INSERT INTO productos (usuario_id, nombre, descripcion, costo, precio, creado_en, actualizado_en)
                                   VALUES (?, ?, ?, ?, ?, NOW(), NOW())')
                    ->execute([$uid, $nombre, $desc, $costo, $precio]);
                $aviso = "Producto «{$nombre}» creado.";
            }
        } elseif ($accion === 'eliminar' && $pid) {
            com_db()->prepare('DELETE FROM productos WHERE id=? AND usuario_id=?')->execute([$pid, $uid]);
            $aviso = 'Producto eliminado.';
        }
    }
}

$q = trim($_GET['q'] ?? '');
$sql = 'SELECT * FROM productos WHERE usuario_id = ?';
$par = [$uid];
if ($q !== '') { $sql .= ' AND nombre LIKE ?'; $par[] = "%{$q}%"; }
$sql .= ' ORDER BY actualizado_en DESC';
$stmt = com_db()->prepare($sql);
$stmt->execute($par);
$productos = $stmt->fetchAll();

$editando = null;
if (isset($_GET['editar'])) {
    $stmt = com_db()->prepare('SELECT * FROM productos WHERE id=? AND usuario_id=?');
    $stmt->execute([(int) $_GET['editar'], $uid]);
    $editando = $stmt->fetch() ?: null;
}

ui_panel_inicio('Productos', $u, 'Productos');
?>
    <h1>Productos</h1>
    <p class="bajada">Tu catálogo de piezas: cargalas acá o guardalas desde la calculadora de un presupuesto.</p>

    <?php if ($aviso): ?><div class="msg ok"><?php echo ui_icono('check', 16); ?><span><?php echo htmlspecialchars($aviso); ?></span></div><?php endif; ?>
    <?php if ($error): ?><div class="msg bad"><?php echo ui_icono('alerta', 16); ?><span><?php echo htmlspecialchars($error); ?></span></div><?php endif; ?>

    <style>
      .barra-sup{display:flex;gap:10px;align-items:center;margin-bottom:16px;flex-wrap:wrap}
      .barra-sup form.buscar{flex:1;min-width:220px;display:flex}
      .panel{background:var(--surface);border:1px solid var(--bd-suave);border-radius:var(--radio-g);overflow:hidden}
      table{width:100%;border-collapse:collapse;font-size:13.5px}
      th,td{padding:12px 16px;text-align:left;border-bottom:1px solid var(--bd-suave);vertical-align:middle}
      th{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.07em;color:var(--txt-3)}
      tr:last-child td{border-bottom:none}
      tbody tr{transition:background-color .15s ease}
      tbody tr:hover{background:var(--surface-2)}
      td.num{text-align:right;font-variant-numeric:tabular-nums;white-space:nowrap}
      th.num{text-align:right}
      td .acciones{display:flex;gap:6px;justify-content:flex-end}
      td form{margin:0}
      .vacio{padding:44px 20px;text-align:center;color:var(--txt-2);font-size:14px}
      .form-prod{background:var(--surface);border:1px solid var(--bd-suave);border-radius:var(--radio-g);
                 padding:20px;margin-bottom:20px;max-width:820px}
      .form-prod h2{font-size:15px;font-weight:600;margin-bottom:4px}
      .form-prod .fila{display:grid;grid-template-columns:1.4fr 2fr .8fr .8fr auto;gap:10px;align-items:end}
      .margen-chip{font-size:12px;color:var(--ok);font-weight:500;white-space:nowrap}
      @media (max-width:900px){ .form-prod .fila{grid-template-columns:1fr} .tabla-scroll{overflow-x:auto} }
    </style>

    <div class="form-prod">
      <h2><?php echo $editando ? 'Editar producto' : 'Nuevo producto'; ?></h2>
      <p class="bajada" style="margin-bottom:2px">Costo = lo que te sale imprimirlo · Precio = lo que cobrás.</p>
      <form method="post">
        <input type="hidden" name="csrf" value="<?php echo com_csrf(); ?>">
        <input type="hidden" name="accion" value="guardar">
        <input type="hidden" name="producto_id" value="<?php echo $editando ? (int) $editando['id'] : 0; ?>">
        <div class="fila">
          <span><label for="p-nombre">Nombre</label>
            <input id="p-nombre" type="text" name="nombre" maxlength="150" required
                   value="<?php echo htmlspecialchars($editando['nombre'] ?? ''); ?>"></span>
          <span><label for="p-desc">Descripción (opcional)</label>
            <input id="p-desc" type="text" name="descripcion" maxlength="500"
                   value="<?php echo htmlspecialchars($editando['descripcion'] ?? ''); ?>"></span>
          <span><label for="p-costo">Costo $</label>
            <input id="p-costo" type="number" name="costo" min="0" step="0.01"
                   value="<?php echo $editando ? (float) $editando['costo'] : ''; ?>"></span>
          <span><label for="p-precio">Precio $</label>
            <input id="p-precio" type="number" name="precio" min="0" step="0.01"
                   value="<?php echo $editando ? (float) $editando['precio'] : ''; ?>"></span>
          <button class="btn" type="submit"><?php echo $editando ? 'Guardar' : 'Crear'; ?></button>
        </div>
      </form>
      <?php if ($editando): ?>
        <p style="margin-top:10px;font-size:13px"><a href="productos.php">Cancelar edición</a></p>
      <?php endif; ?>
    </div>

    <div class="barra-sup">
      <form class="buscar" method="get">
        <input type="search" name="q" placeholder="Buscar producto..." value="<?php echo htmlspecialchars($q); ?>">
      </form>
      <a class="btn" href="presupuesto.php"><?php echo ui_icono('presupuestos', 16); ?> Nuevo presupuesto</a>
    </div>

    <div class="panel tabla-scroll">
      <?php if (!$productos): ?>
        <div class="vacio">Todavía no tenés productos<?php echo $q ? ' que coincidan con la búsqueda' : ''; ?>.<br>
        Cargá el primero con el formulario de arriba, o guardá una pieza desde la calculadora de un presupuesto.</div>
      <?php else: ?>
      <table>
        <thead><tr><th>Producto</th><th>Descripción</th><th class="num">Costo</th><th class="num">Precio</th><th class="num">Ganancia</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($productos as $p): $g = $p['precio'] - $p['costo']; ?>
          <tr>
            <td><strong><?php echo htmlspecialchars($p['nombre']); ?></strong></td>
            <td style="color:var(--txt-2)"><?php echo htmlspecialchars(mb_strimwidth($p['descripcion'] ?? '', 0, 70, '…')); ?></td>
            <td class="num" style="color:var(--txt-2)"><?php echo taller_precio($p['costo']); ?></td>
            <td class="num"><strong><?php echo taller_precio($p['precio']); ?></strong></td>
            <td class="num"><span class="margen-chip" style="color:<?php echo $g >= 0 ? 'var(--ok)' : 'var(--bad)'; ?>">
              <?php echo taller_precio($g); ?></span></td>
            <td>
              <div class="acciones">
                <a class="btn chico sec" href="productos.php?editar=<?php echo (int) $p['id']; ?>">Editar</a>
                <form method="post" onsubmit="return confirm('¿Eliminar «<?php echo htmlspecialchars($p['nombre'], ENT_QUOTES); ?>»?');">
                  <input type="hidden" name="csrf" value="<?php echo com_csrf(); ?>">
                  <input type="hidden" name="accion" value="eliminar">
                  <input type="hidden" name="producto_id" value="<?php echo (int) $p['id']; ?>">
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
