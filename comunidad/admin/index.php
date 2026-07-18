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

    <?php if ($aviso): ?><div class="msg ok"><?php echo htmlspecialchars($aviso); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="msg bad"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

    <style>
      table{width:100%;border-collapse:collapse;background:var(--panel);border:1px solid var(--bd);
            border-radius:12px;overflow:hidden;font-size:.85rem}
      th,td{padding:.7rem .8rem;text-align:left;border-bottom:1px solid var(--bd);vertical-align:middle}
      th{font-size:.68rem;text-transform:uppercase;letter-spacing:.06em;color:var(--txt2)}
      tr:last-child td{border-bottom:none}
      .estado{font-size:.72rem;font-weight:700;padding:.18rem .55rem;border-radius:99px}
      .estado.si{background:rgba(0,230,118,.12);color:var(--ok)}
      .estado.no{background:rgba(255,82,82,.12);color:var(--bad)}
      td form{display:inline-flex;gap:.35rem;align-items:center;margin:.1rem .2rem .1rem 0}
      td input[type=date]{width:auto;padding:.3rem .4rem;font-size:.78rem}
      td .btn{padding:.35rem .6rem;font-size:.74rem}
      .crear{background:var(--panel);border:1px solid var(--bd);border-radius:12px;
             padding:1.1rem 1.2rem;margin:1.6rem 0;max-width:640px}
      .crear h2{font-size:1rem;margin-bottom:.2rem}
      .crear .fila{display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:.7rem;align-items:end}
      @media (max-width:900px){ .crear .fila{grid-template-columns:1fr} .tabla-scroll{overflow-x:auto} }
    </style>

    <div class="tabla-scroll">
    <table>
      <tr><th>Usuario</th><th>Email</th><th>Rol</th><th>Suscripción</th><th>Último ingreso</th><th>Acciones</th></tr>
      <?php foreach ($usuarios as $u): ?>
      <tr>
        <td><?php echo htmlspecialchars($u['nombre']); ?></td>
        <td><?php echo htmlspecialchars($u['email']); ?></td>
        <td><?php echo $u['rol'] === 'admin' ? '🛡️ Admin' : 'Miembro'; ?></td>
        <td>
          <?php if ($u['rol'] === 'admin'): ?>
            <span class="estado si">Siempre</span>
          <?php elseif ($u['susc_activa']): ?>
            <span class="estado si">Activa<?php echo $u['susc_hasta'] ? ' · vence ' . date('d/m/y', strtotime($u['susc_hasta'])) : ''; ?></span>
          <?php else: ?>
            <span class="estado no">Inactiva</span>
          <?php endif; ?>
        </td>
        <td><?php echo $u['ultimo_login'] ? date('d/m/y H:i', strtotime($u['ultimo_login'])) : '—'; ?></td>
        <td>
          <?php if ($u['rol'] !== 'admin'): ?>
            <form method="post">
              <input type="hidden" name="csrf" value="<?php echo com_csrf(); ?>">
              <input type="hidden" name="accion" value="activar">
              <input type="hidden" name="usuario_id" value="<?php echo (int) $u['id']; ?>">
              <input type="date" name="hasta" title="Vencimiento (vacío = sin vencimiento)">
              <button class="btn" type="submit"><?php echo $u['susc_activa'] ? 'Renovar' : 'Activar'; ?></button>
            </form>
            <?php if ($u['susc_activa']): ?>
            <form method="post">
              <input type="hidden" name="csrf" value="<?php echo com_csrf(); ?>">
              <input type="hidden" name="accion" value="desactivar">
              <input type="hidden" name="usuario_id" value="<?php echo (int) $u['id']; ?>">
              <button class="btn peligro" type="submit">Desactivar</button>
            </form>
            <?php endif; ?>
          <?php endif; ?>
          <?php if ((int) $u['id'] !== (int) $yo['id']): ?>
            <form method="post">
              <input type="hidden" name="csrf" value="<?php echo com_csrf(); ?>">
              <input type="hidden" name="accion" value="rol">
              <input type="hidden" name="usuario_id" value="<?php echo (int) $u['id']; ?>">
              <input type="hidden" name="rol" value="<?php echo $u['rol'] === 'admin' ? 'miembro' : 'admin'; ?>">
              <button class="btn sec" type="submit"><?php echo $u['rol'] === 'admin' ? 'Quitar admin' : 'Hacer admin'; ?></button>
            </form>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
    </div>

    <div class="crear">
      <h2>Crear usuario</h2>
      <p class="bajada" style="margin-bottom:.6rem">Alta manual de un miembro (después activale la suscripción).</p>
      <form method="post">
        <input type="hidden" name="csrf" value="<?php echo com_csrf(); ?>">
        <input type="hidden" name="accion" value="crear">
        <div class="fila">
          <span><label>Nombre</label><input type="text" name="nombre" required></span>
          <span><label>Email</label><input type="email" name="email" required></span>
          <span><label>Contraseña</label><input type="text" name="password" minlength="8" required></span>
          <button class="btn" type="submit">Crear</button>
        </div>
      </form>
    </div>
<?php ui_panel_fin(); ?>
