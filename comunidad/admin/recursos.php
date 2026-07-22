<?php
/**
 * Cargar recursos: PDFs descargables y videos de YouTube para la sección
 * Recursos que ven los usuarios. Archivos en uploads/recursos/ (fuera de git).
 */
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/ui.php';
require_once __DIR__ . '/../inc/taller.php';

requerir_admin();
$yo = usuario_actual();
taller_migrar();
$db = com_db();

$dir = dirname(__DIR__) . '/uploads/recursos';
$tab = ($_GET['tab'] ?? '') === 'videos' ? 'videos' : 'pdf';
$aviso = '';
$error = '';

/** Saca el ID de un link de YouTube (youtu.be, watch?v=, shorts, embed). */
function admin_youtube_id($url) {
    $url = trim($url);
    if (preg_match('/^[\w-]{11}$/', $url)) return $url; // ya es un ID
    $patrones = [
        '~youtu\.be/([\w-]{11})~',
        '~youtube\.com/watch\?(?:[^#]*&)?v=([\w-]{11})~',
        '~youtube\.com/shorts/([\w-]{11})~',
        '~youtube\.com/embed/([\w-]{11})~',
        '~youtube\.com/live/([\w-]{11})~',
    ];
    foreach ($patrones as $p) {
        if (preg_match($p, $url, $m)) return $m[1];
    }
    return '';
}

/** Valida y devuelve la extensión de una imagen subida ('' si no vino). */
function admin_img_ext($campo, &$error) {
    if (empty($_FILES[$campo]['tmp_name']) || !is_uploaded_file($_FILES[$campo]['tmp_name'])) return '';
    $info = @getimagesize($_FILES[$campo]['tmp_name']);
    $tipos = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp'];
    if (!$info || !isset($tipos[$info['mime']]) || $_FILES[$campo]['size'] > 3 * 1024 * 1024) {
        $error = 'La imagen tiene que ser PNG, JPG o WebP de hasta 3 MB.';
        return '';
    }
    return $tipos[$info['mime']];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    if (!com_csrf_ok($_POST['csrf'] ?? '')) {
        $error = 'La sesión expiró, probá de nuevo.';

    } elseif ($accion === 'subir_pdf') {
        $tab = 'pdf';
        $titulo = mb_substr(trim($_POST['titulo'] ?? ''), 0, 150);
        $desc   = mb_substr(trim($_POST['descripcion'] ?? ''), 0, 300);
        $arch   = $_FILES['archivo'] ?? null;
        $mime   = !empty($arch['tmp_name']) ? (string) @mime_content_type($arch['tmp_name']) : '';
        if ($titulo === '') {
            $error = 'Poné el título del PDF.';
        } elseif (empty($arch['tmp_name']) || !is_uploaded_file($arch['tmp_name'])) {
            $error = 'Elegí el archivo PDF.';
        } elseif ($mime !== 'application/pdf') {
            $error = 'El archivo tiene que ser un PDF.';
        } elseif ($arch['size'] > 30 * 1024 * 1024) {
            $error = 'El PDF no puede superar los 30 MB.';
        } else {
            $img_ext = admin_img_ext('imagen', $error);
            if ($error === '') {
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                $db->prepare('INSERT INTO recursos_pdf (titulo, descripcion, imagen_ext, tam_bytes, creado_en)
                              VALUES (?, ?, ?, ?, NOW())')
                   ->execute([$titulo, $desc, $img_ext, (int) $arch['size']]);
                $id = (int) $db->lastInsertId();
                $ok1 = move_uploaded_file($arch['tmp_name'], "$dir/pdf-$id.pdf");
                $ok2 = $img_ext === '' || move_uploaded_file($_FILES['imagen']['tmp_name'], "$dir/img-$id.$img_ext");
                if ($ok1 && $ok2) {
                    $aviso = "«{$titulo}» cargado en Recursos.";
                } else {
                    $db->prepare('DELETE FROM recursos_pdf WHERE id=?')->execute([$id]);
                    $error = 'No se pudo guardar el archivo. Probá de nuevo.';
                }
            }
        }

    } elseif ($accion === 'subir_video') {
        $tab = 'videos';
        $titulo = mb_substr(trim($_POST['titulo'] ?? ''), 0, 150);
        $desc   = mb_substr(trim($_POST['descripcion'] ?? ''), 0, 300);
        $ytid   = admin_youtube_id($_POST['youtube'] ?? '');
        if ($titulo === '') {
            $error = 'Poné el título del video.';
        } elseif ($ytid === '') {
            $error = 'El link de YouTube no es válido. Pegalo como aparece en el navegador.';
        } else {
            $img_ext = admin_img_ext('imagen', $error);
            if ($error === '') {
                $db->prepare('INSERT INTO recursos_videos (titulo, descripcion, youtube_id, imagen_ext, creado_en)
                              VALUES (?, ?, ?, ?, NOW())')
                   ->execute([$titulo, $desc, $ytid, $img_ext]);
                $id = (int) $db->lastInsertId();
                if ($img_ext !== '') {
                    if (!is_dir($dir)) mkdir($dir, 0755, true);
                    move_uploaded_file($_FILES['imagen']['tmp_name'], "$dir/vid-$id.$img_ext");
                }
                $aviso = "«{$titulo}» cargado en Recursos.";
            }
        }

    } elseif ($accion === 'publicar_pdf' || $accion === 'publicar_video') {
        $tabla = $accion === 'publicar_pdf' ? 'recursos_pdf' : 'recursos_videos';
        $tab = $accion === 'publicar_pdf' ? 'pdf' : 'videos';
        $db->prepare("UPDATE $tabla SET publicado = 1 - publicado WHERE id=?")
           ->execute([(int) ($_POST['id'] ?? 0)]);
        $aviso = 'Visibilidad actualizada.';

    } elseif ($accion === 'eliminar_pdf') {
        $tab = 'pdf';
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $db->prepare('SELECT * FROM recursos_pdf WHERE id=?');
        $stmt->execute([$id]);
        if ($it = $stmt->fetch()) {
            @unlink("$dir/pdf-$id.pdf");
            if ($it['imagen_ext']) @unlink("$dir/img-$id." . $it['imagen_ext']);
            $db->prepare('DELETE FROM recursos_pdf WHERE id=?')->execute([$id]);
            $aviso = 'PDF eliminado.';
        }

    } elseif ($accion === 'eliminar_video') {
        $tab = 'videos';
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $db->prepare('SELECT * FROM recursos_videos WHERE id=?');
        $stmt->execute([$id]);
        if ($it = $stmt->fetch()) {
            if ($it['imagen_ext']) @unlink("$dir/vid-$id." . $it['imagen_ext']);
            $db->prepare('DELETE FROM recursos_videos WHERE id=?')->execute([$id]);
            $aviso = 'Video eliminado.';
        }
    }
}

$pdfs = $db->query('SELECT * FROM recursos_pdf ORDER BY creado_en DESC, id DESC')->fetchAll();
$videos = $db->query('SELECT * FROM recursos_videos ORDER BY creado_en DESC, id DESC')->fetchAll();

ui_panel_inicio('Cargar recursos', $yo, 'Cargar recursos', '../');
?>
    <style>.contenido{max-width:none}</style>
    <h1>Cargar recursos</h1>
    <p class="bajada">PDFs descargables y videos de YouTube para la sección Recursos de los usuarios.</p>

    <?php if ($aviso): ?><div class="msg ok"><?php echo ui_icono('check', 16); ?><span><?php echo htmlspecialchars($aviso); ?></span></div><?php endif; ?>
    <?php if ($error): ?><div class="msg bad"><?php echo ui_icono('alerta', 16); ?><span><?php echo htmlspecialchars($error); ?></span></div><?php endif; ?>

    <style>
      .tabs{display:flex;gap:26px;border-bottom:1px solid var(--bd-suave);margin-bottom:18px}
      .tabs a{display:flex;align-items:center;gap:9px;padding:12px 2px 13px;font-size:14.5px;font-weight:600;
              color:var(--txt-2);border-bottom:2px solid transparent;margin-bottom:-1px}
      .tabs a.activa{color:var(--txt);border-bottom-color:var(--accent)}
      .tabs a .cant{font-size:12px;font-weight:600;color:var(--txt-3);background:var(--surface-2);
              border-radius:999px;padding:1px 8px}
      .alta{background:var(--surface);border:1px solid var(--bd-suave);border-radius:var(--radio-g);
            padding:20px;margin-bottom:18px;max-width:860px}
      .alta h2{font-size:15px;font-weight:600;margin-bottom:10px}
      .alta .fila{display:grid;grid-template-columns:1fr 1fr;gap:12px}
      .alta .fila3{display:grid;grid-template-columns:1fr 1fr auto;gap:12px;align-items:end;margin-top:4px}
      input[type=file]{height:auto;padding:8px 12px;font-size:13px}
      .ayuda{font-size:12.5px;color:var(--txt-3);margin-top:4px}
      .lista{background:var(--surface);border:1px solid var(--bd-suave);border-radius:var(--radio-g);padding:6px 20px;overflow-x:auto}
      table{width:100%;border-collapse:collapse;font-size:13.5px}
      th,td{padding:12px 10px;text-align:left;border-bottom:1px solid var(--bd-suave);vertical-align:middle}
      th{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.07em;color:var(--txt-3)}
      tr:last-child td{border-bottom:none}
      .mini{width:80px;height:45px;border-radius:6px;background:var(--surface-2);object-fit:cover;display:block}
      .apagado{opacity:.5}
      td .acciones{display:flex;gap:6px;justify-content:flex-end}
      td form{margin:0}
    </style>

    <div class="tabs">
      <a href="recursos.php?tab=pdf" class="<?php echo $tab === 'pdf' ? 'activa' : ''; ?>">
        <?php echo ui_icono('pdf', 18); ?>PDF
        <?php if ($pdfs): ?><span class="cant"><?php echo count($pdfs); ?></span><?php endif; ?>
      </a>
      <a href="recursos.php?tab=videos" class="<?php echo $tab === 'videos' ? 'activa' : ''; ?>">
        <?php echo ui_icono('video', 18); ?>Videos
        <?php if ($videos): ?><span class="cant"><?php echo count($videos); ?></span><?php endif; ?>
      </a>
    </div>

<?php if ($tab === 'pdf'): ?>
    <form class="alta" method="post" enctype="multipart/form-data">
      <h2>Nuevo PDF</h2>
      <input type="hidden" name="csrf" value="<?php echo com_csrf(); ?>">
      <input type="hidden" name="accion" value="subir_pdf">
      <div class="fila">
        <span><label for="p-titulo">Título *</label>
          <input id="p-titulo" type="text" name="titulo" maxlength="150" required
                 placeholder="Guía de calibración de la cama"></span>
        <span><label for="p-desc">Descripción corta</label>
          <input id="p-desc" type="text" name="descripcion" maxlength="300"
                 placeholder="Paso a paso para nivelar la cama en 10 minutos"></span>
      </div>
      <div class="fila3">
        <span><label for="p-arch">Archivo PDF * (máx. 30 MB)</label>
          <input id="p-arch" type="file" name="archivo" accept="application/pdf" required></span>
        <span><label for="p-img">Imagen de portada (PNG/JPG/WebP)</label>
          <input id="p-img" type="file" name="imagen" accept="image/png,image/jpeg,image/webp"></span>
        <button class="btn" type="submit"><?php echo ui_icono('nube', 16); ?> Cargar PDF</button>
      </div>
    </form>

    <?php if ($pdfs): ?>
    <div class="lista">
      <table>
        <thead><tr><th></th><th>PDF</th><th>Descargas</th><th>Estado</th><th style="text-align:right">Acciones</th></tr></thead>
        <tbody>
        <?php foreach ($pdfs as $it): ?>
          <tr class="<?php echo $it['publicado'] ? '' : 'apagado'; ?>">
            <td><?php if ($it['imagen_ext']): ?>
              <img class="mini" src="../uploads/recursos/img-<?php echo (int) $it['id'] . '.' . htmlspecialchars($it['imagen_ext']); ?>" alt="">
              <?php else: ?><span class="mini" style="display:flex;align-items:center;justify-content:center;color:var(--txt-3)"><?php echo ui_icono('pdf', 20); ?></span><?php endif; ?></td>
            <td><strong><?php echo htmlspecialchars($it['titulo']); ?></strong>
              <?php if ($it['descripcion']): ?><br><span style="font-size:12px;color:var(--txt-3)"><?php echo htmlspecialchars($it['descripcion']); ?></span><?php endif; ?></td>
            <td><?php echo (int) $it['descargas']; ?></td>
            <td><?php echo $it['publicado'] ? '<span style="color:var(--ok)">Publicado</span>' : '<span style="color:var(--txt-3)">Oculto</span>'; ?></td>
            <td>
              <div class="acciones">
                <form method="post">
                  <input type="hidden" name="csrf" value="<?php echo com_csrf(); ?>">
                  <input type="hidden" name="accion" value="publicar_pdf">
                  <input type="hidden" name="id" value="<?php echo (int) $it['id']; ?>">
                  <button class="btn chico" type="submit"><?php echo $it['publicado'] ? 'Ocultar' : 'Publicar'; ?></button>
                </form>
                <form method="post" onsubmit="return confirm('¿Eliminar este PDF y su archivo?')">
                  <input type="hidden" name="csrf" value="<?php echo com_csrf(); ?>">
                  <input type="hidden" name="accion" value="eliminar_pdf">
                  <input type="hidden" name="id" value="<?php echo (int) $it['id']; ?>">
                  <button class="btn chico peligro" type="submit">Eliminar</button>
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
    <form class="alta" method="post" enctype="multipart/form-data">
      <h2>Nuevo video de YouTube</h2>
      <input type="hidden" name="csrf" value="<?php echo com_csrf(); ?>">
      <input type="hidden" name="accion" value="subir_video">
      <label for="v-link">Link de YouTube *</label>
      <input id="v-link" type="url" name="youtube" required
             placeholder="https://www.youtube.com/watch?v=...">
      <p class="ayuda">Pegá el link como aparece en el navegador (también sirven youtu.be y Shorts).</p>
      <div class="fila" style="margin-top:8px">
        <span><label for="v-titulo">Título *</label>
          <input id="v-titulo" type="text" name="titulo" maxlength="150" required
                 placeholder="Cómo calibrar el flujo en 5 minutos"></span>
        <span><label for="v-desc">Descripción corta</label>
          <input id="v-desc" type="text" name="descripcion" maxlength="300"
                 placeholder="Tutorial rápido para mejorar la calidad de tus piezas"></span>
      </div>
      <div class="fila3">
        <span><label for="v-img">Imagen de muestra (opcional)</label>
          <input id="v-img" type="file" name="imagen" accept="image/png,image/jpeg,image/webp">
          <p class="ayuda">Si no subís ninguna, usamos la miniatura del propio video de YouTube.</p></span>
        <span></span>
        <button class="btn" type="submit"><?php echo ui_icono('nube', 16); ?> Cargar video</button>
      </div>
    </form>

    <?php if ($videos): ?>
    <div class="lista">
      <table>
        <thead><tr><th></th><th>Video</th><th>YouTube</th><th>Estado</th><th style="text-align:right">Acciones</th></tr></thead>
        <tbody>
        <?php foreach ($videos as $it): ?>
          <tr class="<?php echo $it['publicado'] ? '' : 'apagado'; ?>">
            <td><img class="mini" alt=""
              src="<?php echo $it['imagen_ext']
                  ? '../uploads/recursos/vid-' . (int) $it['id'] . '.' . htmlspecialchars($it['imagen_ext'])
                  : 'https://img.youtube.com/vi/' . rawurlencode($it['youtube_id']) . '/default.jpg'; ?>"></td>
            <td><strong><?php echo htmlspecialchars($it['titulo']); ?></strong>
              <?php if ($it['descripcion']): ?><br><span style="font-size:12px;color:var(--txt-3)"><?php echo htmlspecialchars($it['descripcion']); ?></span><?php endif; ?></td>
            <td><a href="https://www.youtube.com/watch?v=<?php echo rawurlencode($it['youtube_id']); ?>"
                   target="_blank" rel="noopener" style="font-size:12.5px"><?php echo htmlspecialchars($it['youtube_id']); ?></a></td>
            <td><?php echo $it['publicado'] ? '<span style="color:var(--ok)">Publicado</span>' : '<span style="color:var(--txt-3)">Oculto</span>'; ?></td>
            <td>
              <div class="acciones">
                <form method="post">
                  <input type="hidden" name="csrf" value="<?php echo com_csrf(); ?>">
                  <input type="hidden" name="accion" value="publicar_video">
                  <input type="hidden" name="id" value="<?php echo (int) $it['id']; ?>">
                  <button class="btn chico" type="submit"><?php echo $it['publicado'] ? 'Ocultar' : 'Publicar'; ?></button>
                </form>
                <form method="post" onsubmit="return confirm('¿Eliminar este video de la lista?')">
                  <input type="hidden" name="csrf" value="<?php echo com_csrf(); ?>">
                  <input type="hidden" name="accion" value="eliminar_video">
                  <input type="hidden" name="id" value="<?php echo (int) $it['id']; ?>">
                  <button class="btn chico peligro" type="submit">Eliminar</button>
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
<?php ui_panel_fin(); ?>
