<?php
/**
 * Clientes: cartera del usuario, conectada con los presupuestos
 * (el editor de presupuesto sugiere y vincula estos clientes).
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
        $cid    = (int) ($_POST['cliente_id'] ?? 0);
        if ($accion === 'guardar') {
            $nombre    = mb_substr(trim($_POST['nombre'] ?? ''), 0, 150);
            $email     = mb_strtolower(trim($_POST['email'] ?? ''));
            $telefono  = mb_substr(trim($_POST['telefono'] ?? ''), 0, 50);
            $empresa   = mb_substr(trim($_POST['empresa'] ?? ''), 0, 150);
            $direccion = mb_substr(trim($_POST['direccion'] ?? ''), 0, 200);
            $ciudad    = mb_substr(trim($_POST['ciudad'] ?? ''), 0, 100);
            $provincia = mb_substr(trim($_POST['provincia'] ?? ''), 0, 100);
            if ($nombre === '') {
                $error = 'Ingresá el nombre y apellido.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'El email no es válido.';
            } elseif ($telefono === '') {
                $error = 'Ingresá el teléfono.';
            } else {
                try {
                    if ($cid) {
                        com_db()->prepare('UPDATE clientes SET nombre=?, email=?, telefono=?, empresa=?, direccion=?,
                                           ciudad=?, provincia=? WHERE id=? AND usuario_id=?')
                            ->execute([$nombre, $email, $telefono, $empresa, $direccion, $ciudad, $provincia, $cid, $uid]);
                        // Mantener el nombre sincronizado en los presupuestos existentes
                        com_db()->prepare('UPDATE presupuestos SET cliente_nombre=? WHERE cliente_id=? AND usuario_id=?')
                            ->execute([$nombre, $cid, $uid]);
                        $aviso = 'Cliente actualizado.';
                    } else {
                        com_db()->prepare('INSERT INTO clientes (usuario_id, nombre, email, telefono, empresa, direccion,
                                           ciudad, provincia, creado_en) VALUES (?,?,?,?,?,?,?,?,NOW())')
                            ->execute([$uid, $nombre, $email, $telefono, $empresa, $direccion, $ciudad, $provincia]);
                        $aviso = "Cliente «{$nombre}» creado.";
                    }
                } catch (PDOException $e) {
                    $error = ($e->errorInfo[1] ?? 0) == 1062 ? 'Ya tenés un cliente con ese nombre.' : 'Error de base de datos.';
                }
            }
        } elseif ($accion === 'eliminar' && $cid) {
            com_db()->prepare('DELETE FROM clientes WHERE id=? AND usuario_id=?')->execute([$cid, $uid]);
            $aviso = 'Cliente eliminado. Sus presupuestos se conservan.';
        }
    }
}

$q = trim($_GET['q'] ?? '');
$sql = "SELECT c.*,
               (SELECT COUNT(*) FROM presupuestos p WHERE p.cliente_id = c.id) AS presupuestos,
               (SELECT COALESCE(SUM(i.precio_unit * i.cantidad),0)
                  FROM presupuestos p JOIN presupuesto_items i ON i.presupuesto_id = p.id
                 WHERE p.cliente_id = c.id AND p.estado = 'vendido') AS vendido
          FROM clientes c WHERE c.usuario_id = ?";
$par = [$uid];
if ($q !== '') {
    $sql .= ' AND (c.nombre LIKE ? OR c.email LIKE ? OR c.empresa LIKE ?)';
    array_push($par, "%{$q}%", "%{$q}%", "%{$q}%");
}
$sql .= ' ORDER BY c.nombre ASC';
$stmt = com_db()->prepare($sql);
$stmt->execute($par);
$clientes = $stmt->fetchAll();

$editando = null;
if (isset($_GET['editar'])) {
    $stmt = com_db()->prepare('SELECT * FROM clientes WHERE id=? AND usuario_id=?');
    $stmt->execute([(int) $_GET['editar'], $uid]);
    $editando = $stmt->fetch() ?: null;
}

function campo($editando, $k) { return htmlspecialchars($editando[$k] ?? ''); }

ui_panel_inicio('Clientes', $u, 'Clientes');
?>
    <style>.contenido{max-width:none}</style>
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap">
      <div>
        <h1>Clientes</h1>
        <p class="bajada">Tu cartera de clientes. Al crear un presupuesto podés elegirlos y quedan vinculados.</p>
      </div>
      <button type="button" class="btn" id="btnNuevoCliente">+ Crear cliente</button>
    </div>

    <?php if ($aviso): ?><div class="msg ok"><?php echo ui_icono('check', 16); ?><span><?php echo htmlspecialchars($aviso); ?></span></div><?php endif; ?>
    <?php if ($error): ?><div class="msg bad"><?php echo ui_icono('alerta', 16); ?><span><?php echo htmlspecialchars($error); ?></span></div><?php endif; ?>

    <style>
      .form-cli{background:var(--surface);border:1px solid var(--bd-suave);border-radius:var(--radio-g);
                padding:20px;margin-bottom:20px}
      .form-cli h2{font-size:15px;font-weight:600}
      .form-cli .grilla{display:grid;grid-template-columns:repeat(4,1fr);gap:10px}
      .form-cli .ancho-2{grid-column:span 2}
      .form-cli .pie{display:flex;justify-content:flex-end;gap:10px;margin-top:14px;align-items:center}
      .req{color:var(--bad)}
      .panel{background:var(--surface);border:1px solid var(--bd-suave);border-radius:var(--radio-g);overflow:hidden}
      table{width:100%;border-collapse:collapse;font-size:13.5px}
      th,td{padding:12px 16px;text-align:left;border-bottom:1px solid var(--bd-suave);vertical-align:middle}
      th{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.07em;color:var(--txt-3)}
      tr:last-child td{border-bottom:none}
      tbody tr{transition:background-color .15s ease}
      tbody tr:hover{background:var(--surface-2)}
      td.num,th.num{text-align:right;font-variant-numeric:tabular-nums;white-space:nowrap}
      td .sub{display:block;font-size:12px;color:var(--txt-3)}
      td .acciones{display:flex;gap:6px;justify-content:flex-end;flex-wrap:wrap}
      td form{margin:0}
      .aviso-datos{font-size:11.5px;color:var(--warn)}
      .vacio{padding:48px 20px;text-align:center;color:var(--txt-2);font-size:14px}
      .barra-sup{display:flex;gap:10px;align-items:center;margin-bottom:16px;flex-wrap:wrap}
      .barra-sup form{flex:1;min-width:220px}
      @media (max-width:900px){ .form-cli .grilla{grid-template-columns:1fr 1fr} .tabla-scroll{overflow-x:auto} }
      @media (max-width:560px){ .form-cli .grilla{grid-template-columns:1fr} }
    </style>

    <div class="form-cli" id="formCliente" <?php echo ($editando || $error) ? '' : 'hidden'; ?>>
      <h2><?php echo $editando ? 'Editar cliente' : 'Nuevo cliente'; ?></h2>
      <?php if ($editando && ($editando['email'] === '' || $editando['telefono'] === '')): ?>
        <p class="bajada" style="margin-bottom:0;color:var(--warn)">Este cliente se creó desde un presupuesto: completá el email y el teléfono.</p>
      <?php endif; ?>
      <form method="post">
        <input type="hidden" name="csrf" value="<?php echo com_csrf(); ?>">
        <input type="hidden" name="accion" value="guardar">
        <input type="hidden" name="cliente_id" value="<?php echo $editando ? (int) $editando['id'] : 0; ?>">
        <div class="grilla">
          <span class="ancho-2"><label for="c-nombre">Nombre y apellido <span class="req">*</span></label>
            <input id="c-nombre" type="text" name="nombre" maxlength="150" required value="<?php echo campo($editando, 'nombre'); ?>"></span>
          <span><label for="c-email">Email <span class="req">*</span></label>
            <input id="c-email" type="email" name="email" required value="<?php echo campo($editando, 'email'); ?>"></span>
          <span><label for="c-tel">Teléfono <span class="req">*</span></label>
            <input id="c-tel" type="tel" name="telefono" maxlength="50" required placeholder="+54 9 11..." value="<?php echo campo($editando, 'telefono'); ?>"></span>
          <span class="ancho-2"><label for="c-empresa">Nombre empresa</label>
            <input id="c-empresa" type="text" name="empresa" maxlength="150" value="<?php echo campo($editando, 'empresa'); ?>"></span>
          <span class="ancho-2"><label for="c-dir">Dirección</label>
            <input id="c-dir" type="text" name="direccion" maxlength="200" value="<?php echo campo($editando, 'direccion'); ?>"></span>
          <span class="ancho-2"><label for="c-ciudad">Ciudad</label>
            <input id="c-ciudad" type="text" name="ciudad" maxlength="100" value="<?php echo campo($editando, 'ciudad'); ?>"></span>
          <span class="ancho-2"><label for="c-prov">Provincia</label>
            <input id="c-prov" type="text" name="provincia" maxlength="100" value="<?php echo campo($editando, 'provincia'); ?>"></span>
        </div>
        <div class="pie">
          <?php if ($editando): ?>
            <a href="clientes.php" style="font-size:13px">Cancelar edición</a>
          <?php else: ?>
            <button type="button" class="btn sec" id="btnCancelarCliente">Cancelar</button>
          <?php endif; ?>
          <button class="btn" type="submit"><?php echo $editando ? 'Guardar cambios' : 'Crear cliente'; ?></button>
        </div>
      </form>
    </div>
    <script>
      (function(){
        var form = document.getElementById('formCliente');
        var abrir = document.getElementById('btnNuevoCliente');
        abrir.addEventListener('click', function(){
          form.hidden = !form.hidden;
          if (!form.hidden) document.getElementById('c-nombre').focus();
        });
        var cancelar = document.getElementById('btnCancelarCliente');
        if (cancelar) cancelar.addEventListener('click', function(){ form.hidden = true; });
      })();
    </script>

    <div class="barra-sup">
      <form method="get">
        <input type="search" name="q" placeholder="Buscar por nombre, email o empresa..." value="<?php echo htmlspecialchars($q); ?>">
      </form>
      <a class="btn sec" href="presupuesto.php"><?php echo ui_icono('presupuestos', 16); ?> Nuevo presupuesto</a>
    </div>

    <div class="panel tabla-scroll">
      <?php if (!$clientes): ?>
        <div class="vacio"><?php echo $q ? 'No hay clientes que coincidan.' : 'Todavía no tenés clientes. Cargá el primero con el formulario de arriba — o se crean solos al guardar un presupuesto.'; ?></div>
      <?php else: ?>
      <table>
        <thead><tr><th>Cliente</th><th>Contacto</th><th>Empresa</th><th>Ubicación</th>
          <th class="num">Presup.</th><th class="num">Vendido</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($clientes as $c): ?>
          <tr>
            <td><strong><?php echo htmlspecialchars($c['nombre']); ?></strong>
              <?php if ($c['email'] === '' || $c['telefono'] === ''): ?>
                <span class="sub aviso-datos">Datos incompletos</span>
              <?php endif; ?></td>
            <td><?php echo htmlspecialchars($c['email'] ?: '—'); ?>
              <span class="sub"><?php echo htmlspecialchars($c['telefono'] ?: ''); ?></span></td>
            <td style="color:var(--txt-2)"><?php echo htmlspecialchars($c['empresa'] ?: '—'); ?></td>
            <td style="color:var(--txt-2)"><?php echo htmlspecialchars(trim($c['ciudad'] . ($c['ciudad'] && $c['provincia'] ? ', ' : '') . $c['provincia']) ?: '—'); ?></td>
            <td class="num"><a href="presupuestos.php?q=<?php echo urlencode($c['nombre']); ?>"><?php echo (int) $c['presupuestos']; ?></a></td>
            <td class="num"><strong><?php echo taller_precio($c['vendido']); ?></strong></td>
            <td>
              <div class="acciones">
                <a class="btn chico sec" href="presupuesto.php?cliente=<?php echo urlencode($c['nombre']); ?>">+ Presupuesto</a>
                <a class="btn chico sec" href="clientes.php?editar=<?php echo (int) $c['id']; ?>">Editar</a>
                <form method="post" onsubmit="return confirm('¿Eliminar a «<?php echo htmlspecialchars($c['nombre'], ENT_QUOTES); ?>»? Sus presupuestos se conservan.');">
                  <input type="hidden" name="csrf" value="<?php echo com_csrf(); ?>">
                  <input type="hidden" name="accion" value="eliminar">
                  <input type="hidden" name="cliente_id" value="<?php echo (int) $c['id']; ?>">
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
