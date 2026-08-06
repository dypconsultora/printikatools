<?php
/**
 * Administración: usuarios y suscripciones.
 * Acciones: crear usuario, activar/renovar suscripción, desactivar, cambiar rol.
 */
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/ui.php';
require_once __DIR__ . '/../inc/taller.php';

requerir_admin();
taller_migrar();
$yo = usuario_actual();

$aviso = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!com_csrf_ok($_POST['csrf'] ?? '')) {
        $error = 'La sesión expiró, probá de nuevo.';
    } else {
        $accion = $_POST['accion'] ?? '';
        $uid    = (int) ($_POST['usuario_id'] ?? 0);
        try {
            if ($accion === 'crear') {
                $nombre = trim($_POST['nombre'] ?? '');
                $email  = mb_strtolower(trim($_POST['email'] ?? ''));
                $pass   = $_POST['password'] ?? '';
                if ($nombre === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pass) < 8) {
                    $error = 'Revisá nombre, email y contraseña (mínimo 8).';
                } else {
                    com_db()->prepare('INSERT INTO usuarios (nombre, email, pass_hash, rol, creado_en) VALUES (?, ?, ?, ?, NOW())')
                        ->execute([$nombre, $email, password_hash($pass, PASSWORD_DEFAULT), 'miembro']);
                    $aviso = "Usuario «{$nombre}» creado.";
                }
            } elseif ($accion === 'activar' && $uid) {
                $plan  = ($_POST['plan'] ?? '') === 'anual' ? 'anual' : 'mensual';
                $hasta = trim($_POST['hasta'] ?? '');
                if ($hasta === '') {
                    // Sin fecha: un período del plan elegido
                    $hasta = date('Y-m-d', strtotime($plan === 'anual' ? '+1 year' : '+1 month'));
                }
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) {
                    $error = 'La fecha de vencimiento no es válida.';
                } else {
                    // Cierra suscripciones anteriores y abre una nueva vigente.
                    com_db()->prepare("UPDATE suscripciones SET estado = 'cancelada' WHERE usuario_id = ?")->execute([$uid]);
                    com_db()->prepare("INSERT INTO suscripciones (usuario_id, estado, plan, desde, hasta, notas, creado_en) VALUES (?, 'activa', ?, CURDATE(), ?, 'Activada por admin', NOW())")
                        ->execute([$uid, $plan, $hasta]);
                    $aviso = 'Plan ' . $plan . ' activado.';
                }
            } elseif ($accion === 'desactivar' && $uid) {
                com_db()->prepare("UPDATE suscripciones SET estado = 'cancelada' WHERE usuario_id = ?")->execute([$uid]);
                $aviso = 'Suscripción desactivada.';
            } elseif ($accion === 'borrar_varios') {
                /**
                 * Borrado definitivo de cuentas.
                 *
                 * Se lleva puesto todo lo de esa persona: la base tiene las
                 * claves en cascada (presupuestos, clientes, productos, stock,
                 * ventas, suscripciones), y aca ademas se saca su direccion de
                 * Emails captados y su logo del disco. Si no, quedaria dando
                 * vueltas justo lo que se quiso borrar.
                 */
                $ids = array_values(array_filter(
                    array_map('intval', (array) ($_POST['ids'] ?? [])), fn($n) => $n > 0));
                // Nunca la cuenta con la que se esta mirando la pantalla: seria
                // borrarse a si misma y quedarse afuera del panel
                $ids = array_values(array_diff($ids, [(int) $yo['id']]));

                if (!$ids) {
                    $error = 'No marcaste ninguna cuenta (tu propia cuenta no se puede borrar).';
                } else {
                    $huecos = implode(',', array_fill(0, count($ids), '?'));
                    $stmt = com_db()->prepare("SELECT id, nombre, email, logo_ext FROM usuarios WHERE id IN ($huecos)");
                    $stmt->execute($ids);
                    $victimas = $stmt->fetchAll();

                    foreach ($victimas as $v) {
                        if (($v['logo_ext'] ?? '') !== '') {
                            @unlink(taller_logo_dir() . '/logo-' . (int) $v['id'] . '.' . $v['logo_ext']);
                        }
                    }

                    com_db()->prepare("DELETE FROM usuarios WHERE id IN ($huecos)")->execute($ids);

                    // La direccion tambien sale de Emails captados: si no, la que
                    // se anoto mal seguiria en la lista despues de borrar la cuenta
                    $correos = array_column($victimas, 'email');
                    if ($correos) {
                        $h2 = implode(',', array_fill(0, count($correos), '?'));
                        com_db()->prepare("DELETE FROM novedades_emails WHERE email IN ($h2)")->execute($correos);
                    }

                    $n = count($victimas);
                    $aviso = $n === 1
                        ? 'Se borró la cuenta de «' . $victimas[0]['nombre'] . '» y todos sus datos.'
                        : "Se borraron $n cuentas y todos sus datos.";
                }

            } elseif ($accion === 'rol' && $uid) {
                if ($uid === (int) $yo['id']) {
                    $error = 'No podés cambiar tu propio rol.';
                } else {
                    $rol = ($_POST['rol'] ?? '') === 'admin' ? 'admin' : 'miembro';
                    com_db()->prepare('UPDATE usuarios SET rol = ? WHERE id = ?')->execute([$rol, $uid]);
                    $aviso = 'Rol actualizado.';
                }
            }
        } catch (PDOException $e) {
            $error = ($e->errorInfo[1] ?? 0) == 1062 ? 'Ya existe una cuenta con ese email.' : 'Error de base de datos.';
        }
    }
}

$usuarios = com_db()->query(
    "SELECT u.*, s.hasta AS susc_hasta, s.plan AS susc_plan, s.cancelada_en AS susc_baja,
            (s.id IS NOT NULL) AS susc_activa
       FROM usuarios u
  LEFT JOIN suscripciones s
         ON s.usuario_id = u.id AND s.estado = 'activa'
        AND (s.hasta IS NULL OR s.hasta >= CURDATE())
   ORDER BY u.creado_en DESC"
)->fetchAll();

ui_panel_inicio('Suscripciones', $yo, 'Suscripciones', '../');
?>
    <style>.contenido{max-width:none}</style>
    <h1>Suscripciones</h1>
    <p class="bajada">Usuarios de la comunidad y estado de cada suscripción.</p>

    <?php if ($aviso): ?><div class="msg ok"><?php echo ui_icono('check', 16); ?><span><?php echo htmlspecialchars($aviso); ?></span></div><?php endif; ?>
    <?php if ($error): ?><div class="msg bad"><?php echo ui_icono('alerta', 16); ?><span><?php echo htmlspecialchars($error); ?></span></div><?php endif; ?>

    <style>
      .panel{background:var(--surface);border:1px solid var(--bd-suave);border-radius:var(--radio-g);overflow:hidden}
      table{width:100%;border-collapse:collapse;font-size:13.5px}
      th,td{padding:10px 8px;text-align:left;border-bottom:1px solid var(--bd-suave);vertical-align:middle}
      th{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.07em;
         color:var(--txt-3);background:var(--surface)}
      tr:last-child td{border-bottom:none}
      tbody tr{transition:background-color .15s ease}
      tbody tr:hover{background:var(--surface-2)}
      /* El correo se recorta si es muy largo: el nombre ya identifica a la
         persona, y el correo entero aparece al pasar el mouse por encima.
         Ese ancho es lo que le faltaba a la columna de acciones para entrar. */
      td.email{color:var(--txt-2);max-width:128px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
      td.fecha{color:var(--txt-2);font-variant-numeric:tabular-nums;white-space:nowrap;font-size:12.5px}
      .estado{display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:500;
              padding:3px 10px;border-radius:99px;white-space:nowrap}
      .estado::before{content:'';width:6px;height:6px;border-radius:99px;background:currentColor}
      .estado.si{background:var(--ok-tinte);color:var(--ok)}
      .estado.no{background:var(--bad-tinte);color:var(--bad)}
      .estado.neutro{background:var(--accent-tinte);color:var(--accent)}
      .rol-admin{display:inline-flex;align-items:center;gap:6px;color:var(--accent);font-weight:500}
      /* Todo en una sola linea. Antes envolvia y en las filas con suscripcion
         activa "Hacer admin" caia a un segundo renglon, que hacia mas alta la
         fila y desalineaba la tabla entera. */
      td .acciones{display:flex;gap:3px;align-items:center;flex-wrap:nowrap;justify-content:flex-end}
      td .acciones form{display:flex;gap:4px;align-items:center;margin:0}
      /* Los botones de la fila, un punto mas ajustados: es lo que faltaba
         para que la fila entre entera en una notebook de 1440 */
      td .acciones .btn.chico{padding:0 10px}
      td .acciones input[type=date]{width:auto;min-width:112px;padding:0 5px}
      /* El de admin va al final y mas discreto: es el que menos se usa */
      td .acciones .btn-rol{height:28px;padding:0 9px;font-size:12px;white-space:nowrap;
              margin-left:2px;color:var(--txt-3);border-color:var(--bd-suave)}
      td .acciones .btn-rol:hover{color:var(--txt);border-color:var(--bd)}
      /* Nunca envuelve. Cuando la pantalla no alcanza, la que se corre es la
         tabla entera: se desliza a lo ancho, como ya hace el resto del panel.
         Es preferible a partir el renglon, que hacia mas altas unas filas que
         otras y desalineaba todo. */
      .tabla-scroll{overflow-x:auto}
      /* 40px mas que antes: es lo que ocupa la columna de las tildes. Sin esto
         los nombres se partian en dos renglones y volvia la fila alta que se
         habia arreglado. */
      .tabla-scroll table{min-width:1040px}
      td.usuario{white-space:nowrap}
      th.check,td.check{width:40px;padding-right:0}
      .check input[type=checkbox]{width:17px;height:17px;margin:0;cursor:pointer;accent-color:var(--accent)}
      .check .vos{color:var(--txt-3)}
      .acciones-lote{display:flex;gap:10px;align-items:center;flex-wrap:wrap;
                     padding:12px 16px;border-bottom:1px solid var(--bd-suave);background:var(--surface-2)}
      .acciones-lote .cuenta{font-size:13px;color:var(--txt-2);min-width:150px}
      .acciones-lote .ayuda-lote{font-size:12.5px;color:var(--txt-3)}
      .acciones-lote .btn[disabled]{opacity:.45;pointer-events:none}
      td form{display:inline-flex;gap:6px;align-items:center;margin:0}
      td input[type=date]{width:auto;height:32px;padding:0 8px;font-size:12.5px;border-radius:6px}
      .crear{background:var(--surface);border:1px solid var(--bd-suave);border-radius:var(--radio-g);
             padding:20px;margin-top:16px;max-width:720px}
      .crear h2{font-size:15px;font-weight:600;margin-bottom:2px}
      .crear .nota{font-size:13px;color:var(--txt-2);margin-bottom:6px}
      .crear .fila{display:grid;grid-template-columns:1fr 1.2fr 1fr auto;gap:10px;align-items:end}
      .crear label{margin-top:10px}
      @media (max-width:900px){ .crear .fila{grid-template-columns:1fr} .tabla-scroll{overflow-x:auto} }
    </style>

    <?php
    // El formulario del borrado vive FUERA de la tabla y las tildes se le
    // enganchan con form="borrar-lote". Si envolviera la tabla, los formularios
    // que ya tiene cada fila (activar, desactivar, rol) quedarian anidados y el
    // navegador los tira: dejarian de funcionar los botones que ya andaban.
    ?>
    <form method="post" id="borrar-lote" style="display:none">
      <input type="hidden" name="csrf" value="<?php echo com_csrf(); ?>">
      <input type="hidden" name="accion" value="borrar_varios">
    </form>

    <div class="panel">
    <div class="acciones-lote">
      <span class="cuenta" id="cuenta">Ninguna seleccionada</span>
      <button class="btn chico peligro" type="submit" form="borrar-lote" id="btn-borrar" disabled
              onclick="return confirm('Se borran las cuentas marcadas y TODO lo que tengan cargado: presupuestos, clientes, productos, stock, ventas y su dirección en Emails captados.\n\nEsto no se puede deshacer. ¿Seguimos?')">
        <?php echo ui_icono('basura', 15); ?> Borrar seleccionadas
      </button>
      <span class="ayuda-lote">Marcá una o varias cuentas para borrarlas.</span>
    </div>
    <div class="tabla-scroll">
    <table>
      <thead>
      <tr><th class="check"><input type="checkbox" id="todos" title="Marcar todas"></th>
          <th>Usuario</th><th>Email</th><th>Rol</th><th>Suscripción</th><th>Se registró</th><th>Acciones</th></tr>
      </thead>
      <tbody>
      <?php foreach ($usuarios as $u): ?>
      <tr>
        <td class="check">
          <?php // La propia cuenta no se puede marcar: borrarse a si misma
                // dejaria el panel sin nadie que pueda entrar
                if ((int) $u['id'] !== (int) $yo['id']): ?>
            <input type="checkbox" class="uno" name="ids[]" form="borrar-lote"
                   value="<?php echo (int) $u['id']; ?>"
                   aria-label="Marcar a <?php echo htmlspecialchars($u['nombre'], ENT_QUOTES); ?>">
          <?php else: ?>
            <span class="vos" title="Es tu propia cuenta">—</span>
          <?php endif; ?>
        </td>
        <td class="usuario"><strong><?php echo htmlspecialchars($u['nombre']); ?></strong></td>
        <td class="email" title="<?php echo htmlspecialchars($u['email'], ENT_QUOTES); ?>"><?php echo htmlspecialchars($u['email']); ?></td>
        <td>
          <?php if ($u['rol'] === 'admin'): ?>
            <span class="rol-admin"><?php echo ui_icono('admin', 14); ?>Admin</span>
          <?php else: ?>
            <span style="color:var(--txt-2)">Suscriptor</span>
          <?php endif; ?>
        </td>
        <td>
          <?php if ($u['rol'] === 'admin'): ?>
            <span class="estado neutro">Siempre</span>
          <?php elseif ($u['susc_activa']): ?>
            <?php // Pidio la baja: sigue pago hasta la fecha, pero ya no renueva ?>
            <span class="estado <?php echo $u['susc_baja'] ? 'neutro' : 'si'; ?>"><?php echo ($u['susc_plan'] ?? '') === 'anual' ? 'Anual' : 'Mensual'; ?><?php echo $u['susc_baja'] ? ' · dio de baja' : ''; ?><?php echo $u['susc_hasta'] ? ($u['susc_baja'] ? ' · hasta ' : ' · vence ') . date('d/m/y', strtotime($u['susc_hasta'])) : ''; ?></span>
          <?php else: ?>
            <span class="estado no">Gratis</span>
          <?php endif; ?>
        </td>
        <?php // Cuando se creo la cuenta, que es por lo que ya viene ordenada la tabla ?>
        <td class="fecha"><?php echo $u['creado_en'] ? date('d/m/y H:i', strtotime($u['creado_en'])) : '—'; ?></td>
        <td>
          <div class="acciones">
          <?php if ($u['rol'] !== 'admin'): ?>
            <form method="post">
              <input type="hidden" name="csrf" value="<?php echo com_csrf(); ?>">
              <input type="hidden" name="accion" value="activar">
              <input type="hidden" name="usuario_id" value="<?php echo (int) $u['id']; ?>">
              <select name="plan" aria-label="Plan" style="width:auto;height:32px;padding:0 6px;font-size:12px;border-radius:6px">
                <option value="mensual">Mensual</option>
                <option value="anual">Anual</option>
              </select>
              <input type="date" name="hasta" title="Vencimiento (vacío = un período del plan)" aria-label="Fecha de vencimiento">
              <button class="btn chico" type="submit"><?php echo $u['susc_activa'] ? 'Renovar' : 'Activar'; ?></button>
            </form>
            <?php if ($u['susc_activa']): ?>
            <form method="post">
              <input type="hidden" name="csrf" value="<?php echo com_csrf(); ?>">
              <input type="hidden" name="accion" value="desactivar">
              <input type="hidden" name="usuario_id" value="<?php echo (int) $u['id']; ?>">
              <button class="btn chico peligro" type="submit">Desactivar</button>
            </form>
            <?php endif; ?>
          <?php endif; ?>
          <?php if ((int) $u['id'] !== (int) $yo['id']): ?>
            <form method="post">
              <input type="hidden" name="csrf" value="<?php echo com_csrf(); ?>">
              <input type="hidden" name="accion" value="rol">
              <input type="hidden" name="usuario_id" value="<?php echo (int) $u['id']; ?>">
              <input type="hidden" name="rol" value="<?php echo $u['rol'] === 'admin' ? 'miembro' : 'admin'; ?>">
              <button class="btn chico sec btn-rol" type="submit"><?php echo $u['rol'] === 'admin' ? 'Quitar admin' : 'Hacer admin'; ?></button>
            </form>
          <?php endif; ?>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>
    </div>

    <script>
      (function () {
        var todos  = document.getElementById('todos');
        var unos   = Array.prototype.slice.call(document.querySelectorAll('.uno'));
        var cuenta = document.getElementById('cuenta');
        var boton  = document.getElementById('btn-borrar');

        function actualizar() {
          var n = unos.filter(function (c) { return c.checked; }).length;
          cuenta.textContent = n === 0 ? 'Ninguna seleccionada'
                             : n === 1 ? '1 cuenta seleccionada'
                                       : n + ' cuentas seleccionadas';
          // Deshabilitado mientras no haya nada marcado: asi el boton de borrar
          // no invita a tocarlo sin querer
          boton.disabled = n === 0;
          todos.checked = n > 0 && n === unos.length;
          todos.indeterminate = n > 0 && n < unos.length;
        }
        todos.addEventListener('change', function () {
          unos.forEach(function (c) { c.checked = todos.checked; });
          actualizar();
        });
        unos.forEach(function (c) { c.addEventListener('change', actualizar); });
        actualizar();
      })();
    </script>

    <div class="crear">
      <h2>Crear usuario</h2>
      <p class="nota">Alta manual de un miembro. Después activale la suscripción desde la tabla.</p>
      <form method="post">
        <input type="hidden" name="csrf" value="<?php echo com_csrf(); ?>">
        <input type="hidden" name="accion" value="crear">
        <div class="fila">
          <span><label for="c-nombre">Nombre</label><input id="c-nombre" type="text" name="nombre" required></span>
          <span><label for="c-email">Email</label><input id="c-email" type="email" name="email" required></span>
          <span><label for="c-pass">Contraseña</label><input id="c-pass" type="text" name="password" minlength="8" required></span>
          <button class="btn" type="submit">Crear</button>
        </div>
      </form>
    </div>
<?php ui_panel_fin(); ?>
