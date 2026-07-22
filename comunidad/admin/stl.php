<?php
/**
 * Cargar STL: alta y gestión de los modelos de la Librería STL.
 * Archivos en comunidad/uploads/stl/ (fuera de git, sobreviven deploys).
 */
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/ui.php';
require_once __DIR__ . '/../inc/taller.php';

requerir_admin();
$yo = usuario_actual();
taller_migrar();
$db = com_db();

$dir = dirname(__DIR__) . '/uploads/stl';
$aviso = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!com_csrf_ok($_POST['csrf'] ?? '')) {
        $error = 'La sesión expiró, probá de nuevo.';
    } elseif (($_POST['accion'] ?? '') === 'subir') {
        $nombre = mb_substr(trim($_POST['nombre'] ?? ''), 0, 150);
        $cat    = mb_substr(trim($_POST['categoria'] ?? ''), 0, 80);
        $arch   = $_FILES['archivo'] ?? null;

        $ext = strtolower(pathinfo($arch['name'] ?? '', PATHINFO_EXTENSION));
        if ($nombre === '') {
            $error = 'Poné el nombre del modelo.';
        } elseif (empty($arch['tmp_name']) || !is_uploaded_file($arch['tmp_name'])) {
            $error = 'Elegí el archivo STL (o ZIP con varios).';
        } elseif (!in_array($ext, ['stl', 'zip', '3mf', 'obj'], true)) {
            $error = 'El archivo tiene que ser STL, 3MF, OBJ o ZIP.';
        } elseif ($arch['size'] > 60 * 1024 * 1024) {
            $error = 'El archivo no puede superar los 60 MB.';
        } else {
            $img_ext = '';
            if (!empty($_FILES['imagen']['tmp_name']) && is_uploaded_file($_FILES['imagen']['tmp_name'])) {
                $info = @getimagesize($_FILES['imagen']['tmp_name']);
                $tipos = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp'];
                if (!$info || !isset($tipos[$info['mime']]) || $_FILES['imagen']['size'] > 3 * 1024 * 1024) {
                    $error = 'La foto tiene que ser PNG, JPG o WebP de hasta 3 MB.';
                } else {
                    $img_ext = $tipos[$info['mime']];
                }
            }
            if ($error === '') {
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                $db->prepare('INSERT INTO stl_items (nombre, categoria, archivo_ext, imagen_ext, tam_bytes, creado_en)
                              VALUES (?, ?, ?, ?, ?, NOW())')
                   ->execute([$nombre, $cat, $ext, $img_ext, (int) $arch['size']]);
                $id = (int) $db->lastInsertId();
                $ok1 = move_uploaded_file($arch['tmp_name'], "$dir/stl-$id.$ext");
                $ok2 = $img_ext === '' || move_uploaded_file($_FILES['imagen']['tmp_name'], "$dir/img-$id.$img_ext");
                if ($ok1 && $ok2) {
                    $aviso = "«{$nombre}» cargado en la librería.";
                } else {
                    $db->prepare('DELETE FROM stl_items WHERE id=?')->execute([$id]);
                    $error = 'No se pudo guardar el archivo. Probá de nuevo.';
                }
            }
        }
    } elseif (($_POST['accion'] ?? '') === 'publicar') {
        $db->prepare('UPDATE stl_items SET publicado = 1 - publicado WHERE id=?')
           ->execute([(int) ($_POST['id'] ?? 0)]);
        $aviso = 'Visibilidad actualizada.';
    } elseif (($_POST['accion'] ?? '') === 'eliminar') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $db->prepare('SELECT * FROM stl_items WHERE id=?');
        $stmt->execute([$id]);
        if ($it = $stmt->fetch()) {
            @unlink("$dir/stl-$id." . $it['archivo_ext']);
            if ($it['imagen_ext']) @unlink("$dir/img-$id." . $it['imagen_ext']);
            $db->prepare('DELETE FROM stl_items WHERE id=?')->execute([$id]);
            $aviso = 'Modelo eliminado.';
        }
    }
}

$items = $db->query('SELECT * FROM stl_items ORDER BY creado_en DESC, id DESC')->fetchAll();

ui_panel_inicio('Cargar STL', $yo, 'Cargar STL', '../');
?>
    <style>.contenido{max-width:none}</style>
    <h1>Cargar STL</h1>
    <p class="bajada">Subí modelos a la Librería STL que ven todos los usuarios (incluido el plan gratuito).</p>

    <?php if ($aviso): ?><div class="msg ok"><?php echo ui_icono('check', 16); ?><span><?php echo htmlspecialchars($aviso); ?></span></div><?php endif; ?>
    <?php if ($error): ?><div class="msg bad"><?php echo ui_icono('alerta', 16); ?><span><?php echo htmlspecialchars($error); ?></span></div><?php endif; ?>

    <style>
      .alta{background:var(--surface);border:1px solid var(--bd-suave);border-radius:var(--radio-g);
            padding:20px;margin-bottom:18px;max-width:860px}
      .alta h2{font-size:15px;font-weight:600;margin-bottom:10px}
      .alta .fila{display:grid;grid-template-columns:1.2fr 1fr;gap:12px}
      .alta .fila2{display:grid;grid-template-columns:1fr 1fr auto;gap:12px;align-items:end;margin-top:4px}
      input[type=file]{height:auto;padding:8px 12px;font-size:13px}
      .lista{background:var(--surface);border:1px solid var(--bd-suave);border-radius:var(--radio-g);padding:6px 20px;overflow-x:auto}
      table{width:100%;border-collapse:collapse;font-size:13.5px}
      th,td{padding:12px 10px;text-align:left;border-bottom:1px solid var(--bd-suave);vertical-align:middle}
      th{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.07em;color:var(--txt-3)}
      tr:last-child td{border-bottom:none}
      .mini{width:64px;height:48px;border-radius:6px;background:var(--surface-2);object-fit:cover;display:block}
      .apagado{opacity:.5}
      td .acciones{display:flex;gap:6px;justify-content:flex-end}
      td form{margin:0}
    </style>

    <form class="alta" method="post" enctype="multipart/form-data">
      <h2>Nuevo modelo</h2>
      <input type="hidden" name="csrf" value="<?php echo com_csrf(); ?>">
      <input type="hidden" name="accion" value="subir">
      <div class="fila">
        <span><label for="s-nombre">Nombre *</label>
          <input id="s-nombre" type="text" name="nombre" maxlength="150" required placeholder="Soporte para auriculares"></span>
        <span><label for="s-cat">Categoría</label>
          <input id="s-cat" type="text" name="categoria" maxlength="80" list="catsSTL" placeholder="Hogar, Deco, Gadgets..."></span>
      </div>
      <datalist id="catsSTL">
        <option>Hogar</option><option>Deco</option><option>Gadgets</option><option>Organización</option>
        <option>Juguetes</option><option>Repuestos</option>
      </datalist>
      <div class="fila2">
        <span><label for="s-arch">Archivo STL / 3MF / OBJ / ZIP * (máx. 60 MB)</label>
          <input id="s-arch" type="file" name="archivo" accept=".stl,.zip,.3mf,.obj" required></span>
        <span><label for="s-img">Foto de vista previa (PNG/JPG/WebP)</label>
          <input id="s-img" type="file" name="imagen" accept="image/png,image/jpeg,image/webp"></span>
        <button class="btn" type="submit"><?php echo ui_icono('nube', 16); ?> Cargar STL</button>
      </div>
    </form>

    <?php if ($items): ?>
    <div class="lista">
      <table>
        <thead><tr><th></th><th>Modelo</th><th>Categoría</th><th>Descargas</th><th>Estado</th><th style="text-align:right">Acciones</th></tr></thead>
        <tbody>
        <?php foreach ($items as $it): ?>
          <tr class="<?php echo $it['publicado'] ? '' : 'apagado'; ?>">
            <td><?php if ($it['imagen_ext']): ?>
              <img class="mini" src="../uploads/stl/img-<?php echo (int) $it['id'] . '.' . htmlspecialchars($it['imagen_ext']); ?>" alt="">
              <?php else: ?><span class="mini" style="display:flex;align-items:center;justify-content:center;color:var(--txt-3)"><?php echo ui_icono('libreria', 20); ?></span><?php endif; ?></td>
            <td><strong><?php echo htmlspecialchars($it['nombre']); ?></strong><br>
              <span style="font-size:12px;color:var(--txt-3)"><?php echo strtoupper($it['archivo_ext']); ?></span></td>
            <td><?php echo htmlspecialchars($it['categoria'] ?: '—'); ?></td>
            <td><?php echo (int) $it['descargas']; ?></td>
            <td><?php echo $it['publicado'] ? '<span style="color:var(--ok)">Publicado</span>' : '<span style="color:var(--txt-3)">Oculto</span>'; ?></td>
            <td>
              <div class="acciones">
                <form method="post">
                  <input type="hidden" name="csrf" value="<?php echo com_csrf(); ?>">
                  <input type="hidden" name="accion" value="publicar">
                  <input type="hidden" name="id" value="<?php echo (int) $it['id']; ?>">
                  <button class="btn chico" type="submit"><?php echo $it['publicado'] ? 'Ocultar' : 'Publicar'; ?></button>
                </form>
                <form method="post" onsubmit="return confirm('¿Eliminar este modelo y su archivo?')">
                  <input type="hidden" name="csrf" value="<?php echo com_csrf(); ?>">
                  <input type="hidden" name="accion" value="eliminar">
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
<?php ui_panel_fin(); ?>
