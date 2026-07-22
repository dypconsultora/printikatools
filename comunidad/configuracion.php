<?php
/**
 * Configuración del taller: datos del negocio, logo propio (usado en los
 * PDF de presupuestos) y moneda de trabajo.
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'moneda') {
    if (com_csrf_ok($_POST['csrf'] ?? '')) {
        taller_guardar_moneda($uid, $_POST['moneda'] ?? '');
    }
    header('Location: configuracion.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!com_csrf_ok($_POST['csrf'] ?? '')) {
        $error = 'La sesión expiró, probá de nuevo.';
    } else {
        $nombre   = mb_substr(trim($_POST['nombre'] ?? ''), 0, 120);
        $taller   = mb_substr(trim($_POST['taller_nombre'] ?? ''), 0, 150);
        $telefono = mb_substr(trim($_POST['taller_telefono'] ?? ''), 0, 50);

        if ($nombre === '') {
            $error = 'Ingresá tu nombre.';
        } else {
            $logo_ext = $u['logo_ext'] ?? '';

            // Quitar el logo actual
            if (!empty($_POST['quitar_logo']) && $logo_ext !== '') {
                @unlink(taller_logo_dir() . '/logo-' . $uid . '.' . $logo_ext);
                $logo_ext = '';
            }

            // Subida de logo nuevo (PNG o JPG para que funcione en el PDF)
            if (!empty($_FILES['logo']['tmp_name']) && is_uploaded_file($_FILES['logo']['tmp_name'])) {
                $info = @getimagesize($_FILES['logo']['tmp_name']);
                $tipos = ['image/png' => 'png', 'image/jpeg' => 'jpg'];
                if ($_FILES['logo']['size'] > 2 * 1024 * 1024) {
                    $error = 'El logo no puede superar los 2 MB.';
                } elseif (!$info || !isset($tipos[$info['mime']])) {
                    $error = 'El logo tiene que ser una imagen PNG o JPG.';
                } else {
                    $ext_nueva = $tipos[$info['mime']];
                    if (!is_dir(taller_logo_dir())) {
                        mkdir(taller_logo_dir(), 0755, true);
                    }
                    if ($logo_ext !== '' && $logo_ext !== $ext_nueva) {
                        @unlink(taller_logo_dir() . '/logo-' . $uid . '.' . $logo_ext);
                    }
                    if (move_uploaded_file($_FILES['logo']['tmp_name'], taller_logo_dir() . '/logo-' . $uid . '.' . $ext_nueva)) {
                        $logo_ext = $ext_nueva;
                    } else {
                        $error = 'No se pudo guardar el logo. Probá de nuevo.';
                    }
                }
            }

            if ($error === '') {
                com_db()->prepare('UPDATE usuarios SET nombre=?, taller_nombre=?, taller_telefono=?, logo_ext=? WHERE id=?')
                    ->execute([$nombre, $taller, $telefono, $logo_ext, $uid]);
                $aviso = 'Configuración guardada.';
                // Refrescar los datos en pantalla
                $stmt = com_db()->prepare('SELECT * FROM usuarios WHERE id=?');
                $stmt->execute([$uid]);
                $u = $stmt->fetch();
            }
        }
    }
}

$logo_url = taller_logo_url($u);

ui_panel_inicio('Configuración', $u, 'Configuración');
?>
    <style>.contenido{max-width:none}</style>
    <h1>Configuración</h1>
    <p class="bajada">Los datos de tu taller: aparecen en los PDF de tus presupuestos.</p>

    <?php if ($aviso): ?><div class="msg ok"><?php echo ui_icono('check', 16); ?><span><?php echo htmlspecialchars($aviso); ?></span></div><?php endif; ?>
    <?php if ($error): ?><div class="msg bad"><?php echo ui_icono('alerta', 16); ?><span><?php echo htmlspecialchars($error); ?></span></div><?php endif; ?>

    <style>
      .conf-grilla{display:grid;grid-template-columns:minmax(0,1fr) 380px;gap:20px;align-items:start}
      .tarjeta-s{background:var(--surface);border:1px solid var(--bd-suave);border-radius:var(--radio-g);padding:20px}
      .tarjeta-s h2{font-size:15px;font-weight:600;margin-bottom:4px}
      .tarjeta-s .nota{font-size:13px;color:var(--txt-2);margin-bottom:8px}
      .fila-2{display:grid;grid-template-columns:1fr 1fr;gap:10px}
      .logo-zona{display:flex;gap:16px;align-items:center;margin-top:12px;flex-wrap:wrap}
      .logo-prev{width:180px;height:80px;border:1px dashed var(--bd);border-radius:var(--radio);
          background:var(--surface-2);display:flex;align-items:center;justify-content:center;overflow:hidden}
      .logo-prev img{max-width:100%;max-height:100%;object-fit:contain}
      .logo-prev span{font-size:12px;color:var(--txt-3)}
      input[type=file]{height:auto;padding:8px 12px;font-size:13px}
      .check-linea{display:flex;align-items:center;gap:8px;margin-top:10px;font-size:13px;color:var(--txt-2)}
      .check-linea input{width:auto;height:auto}
      .pie-form{display:flex;justify-content:flex-end;margin-top:16px}
      .moneda-linea{display:flex;align-items:center;gap:12px;margin-top:8px}
      @media (max-width:1000px){ .conf-grilla{grid-template-columns:1fr} }
    </style>

    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="csrf" value="<?php echo com_csrf(); ?>">
      <div class="conf-grilla">
        <div class="tarjeta-s">
          <h2>Datos del taller</h2>
          <p class="nota">El nombre y teléfono del taller aparecen al pie de tus PDF.</p>
          <label for="c-nombre">Tu nombre *</label>
          <input id="c-nombre" type="text" name="nombre" maxlength="120" required
                 value="<?php echo htmlspecialchars($u['nombre']); ?>">
          <div class="fila-2">
            <span><label for="c-taller">Nombre del taller / negocio</label>
              <input id="c-taller" type="text" name="taller_nombre" maxlength="150"
                     placeholder="Ej: Printika 3D" value="<?php echo htmlspecialchars($u['taller_nombre'] ?? ''); ?>"></span>
            <span><label for="c-tel">Teléfono / WhatsApp</label>
              <input id="c-tel" type="tel" name="taller_telefono" maxlength="50"
                     placeholder="+54 9 11..." value="<?php echo htmlspecialchars($u['taller_telefono'] ?? ''); ?>"></span>
          </div>

          <label style="margin-top:16px">Moneda del taller</label>
          <div class="moneda-linea"><?php taller_chip_moneda(); ?></div>
        </div>

        <div class="tarjeta-s">
          <h2>Logo para tus PDF</h2>
          <p class="nota">PNG o JPG (máx. 2 MB). Si no cargás ninguno, usamos el de Printika Tools.</p>
          <div class="logo-zona">
            <div class="logo-prev">
              <?php if ($logo_url): ?>
                <img src="<?php echo htmlspecialchars($logo_url); ?>" alt="Tu logo">
              <?php else: ?>
                <span>Sin logo propio</span>
              <?php endif; ?>
            </div>
            <div style="flex:1;min-width:200px">
              <input type="file" name="logo" accept="image/png,image/jpeg">
              <?php if ($logo_url): ?>
                <label class="check-linea" for="quitarLogo">
                  <input id="quitarLogo" type="checkbox" name="quitar_logo" value="1">
                  Quitar mi logo y volver al de Printika Tools
                </label>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
      <div class="pie-form"><button class="btn" type="submit">Guardar configuración</button></div>
    </form>
    <?php taller_popup_moneda(); ?>
<?php ui_panel_fin(); ?>
