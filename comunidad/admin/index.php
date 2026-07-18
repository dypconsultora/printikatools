<?php
/**
 * Administración: usuarios y suscripciones.
 * Acciones: crear usuario, activar/renovar suscripción, desactivar, cambiar rol.
 */
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/ui.php';

requerir_admin();
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
                $hasta = trim($_POST['hasta'] ?? '');
                $hasta = $hasta === '' ? null : $hasta;
                if ($hasta !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) {
                    $error = 'La fecha de vencimiento no es válida.';
                } else {
                    // Cierra suscripciones anteriores y abre una nueva vigente.
                    com_db()->prepare("UPDATE suscripciones SET estado = 'cancelada' WHERE usuario_id = ?")->execute([$uid]);
                    com_db()->prepare("INSERT INTO suscripciones (usuario_id, estado, desde, hasta, creado_en) VALUES (?, 'activa', CURDATE(), ?, NOW())")
                        ->execute([$uid, $hasta]);
                    $aviso = 'Suscripción activada.';
                }
            } elseif ($accion === 'desactivar' && $uid) {
                com_db()->prepare("UPDATE suscripciones SET estado = 'cancelada' WHERE usuario_id = ?")->execute([$uid]);
                $aviso = 'Suscripción desactivada.';
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
    "SELECT u.*, s.hasta AS susc_hasta,
            (s.id IS NOT NULL) AS susc_activa
       FROM usuarios u
  LEFT JOIN suscripciones s
         ON s.usuario_id = u.id AND s.estado = 'activa'
        AND (s.hasta IS NULL OR s.hasta >= CURDATE())
   ORDER BY u.creado_en DESC"
)->fetchAll();

ui_panel_inicio('Suscripciones', $yo, 'Suscripciones', '../');
?>
    <h1>Suscripciones</h1>
    <p class="bajada">Usuarios de la comunidad y estado de cada suscripción.</p>

    <?php if ($aviso): ?><div class="msg ok"><?php echo ui_icono('check', 16); ?><span><?php echo htmlspecialchars($aviso); ?></span></div><?php endif; ?>
    <?php if ($error): ?><div class="msg bad"><?php echo ui_icono('alerta', 16); ?><span><?php echo htmlspecialchars($error); ?></span></div><?php endif; ?>

    <style>
      .panel{background:var(--surface);border:1px solid var(--bd-suave);border-radius:var(--radio-g);overflow:hidden}
      table{width:100%;border-collapse:collapse;font-size:13.5px}
      th,td{padding:12px 16px;text-align:left;border-bottom:1px solid var(--bd-suave);vertical-align:middle}
      th{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.07em;
         color:var(--txt-3);background:var(--surface)}
      tr:last-child td{border-bottom:none}
      tbody tr{transition:background-color .15s ease}
      tbody tr:hover{background:var(--surface-2)}
      td.email{color:var(--txt-2)}
      td.fecha{color:var(--txt-2);font-variant-numeric:tabular-nums;white-space:nowrap}
      .estado{display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:500;
              padding:3px 10px;border-radius:99px;white-space:nowrap}
      .estado::before{content:'';width:6px;height:6px;border-radius:99px;background:currentColor}
      .estado.si{background:var(--ok-tinte);color:var(--ok)}
      .estado.no{background:var(--bad-tinte);color:var(--bad)}
      .estado.neutro{background:var(--accent-tinte);color:var(--accent)}
      .rol-admin{display:inline-flex;align-items:center;gap:6px;color:var(--accent);font-weight:500}
      td .acciones{display:flex;gap:6px;align-items:center;flex-wrap:wrap}
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

    <div class="panel tabla-scroll">
    <table>
      <thead>
      <tr><th>Usuario</th><th>Email</th><th>Rol</th><th>Suscripción</th><th>Último ingreso</th><th>Acciones</th></tr>
      </thead>
      <tbody>
      <?php foreach ($usuarios as $u): ?>
      <tr>
        <td><strong><?php echo htmlspecialchars($u['nombre']); ?></strong></td>
        <td class="email"><?php echo htmlspecialchars($u['email']); ?></td>
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
            <span class="estado si">Activa<?php echo $u['susc_hasta'] ? ' · vence ' . date('d/m/y', strtotime($u['susc_hasta'])) : ''; ?></span>
          <?php else: ?>
            <span class="estado no">Inactiva</span>
          <?php endif; ?>
        </td>
        <td class="fecha"><?php echo $u['ultimo_login'] ? date('d/m/y H:i', strtotime($u['ultimo_login'])) : '—'; ?></td>
        <td>
          <div class="acciones">
          <?php if ($u['rol'] !== 'admin'): ?>
            <form method="post">
              <input type="hidden" name="csrf" value="<?php echo com_csrf(); ?>">
              <input type="hidden" name="accion" value="activar">
              <input type="hidden" name="usuario_id" value="<?php echo (int) $u['id']; ?>">
              <input type="date" name="hasta" title="Vencimiento (vacío = sin vencimiento)" aria-label="Fecha de vencimiento">
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
              <button class="btn chico sec" type="submit"><?php echo $u['rol'] === 'admin' ? 'Quitar admin' : 'Hacer admin'; ?></button>
            </form>
          <?php endif; ?>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>

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
