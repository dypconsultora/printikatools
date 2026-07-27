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

/**
 * Mueve un archivo subido a su lugar definitivo.
 * Los que llegaron por pedazos ya estan en el disco: van con rename().
 */
function stl_mover(array $a, $destino) {
    return isset($a['movido']) ? @rename($a['tmp'], $destino)
                               : move_uploaded_file($a['tmp'], $destino);
}
$aviso = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!com_csrf_ok($_POST['csrf'] ?? '')) {
        $error = 'La sesión expiró, probá de nuevo.';
    } elseif (($_POST['accion'] ?? '') === 'subir') {
        $nombre = mb_substr(trim($_POST['nombre'] ?? ''), 0, 150);
        $cat    = mb_substr(trim($_POST['categoria'] ?? ''), 0, 80);

        // Camino nuevo: el archivo llego por pedazos y ya esta rearmado en
        // uploads/tmp. Es lo que evita el "Request Timeout" con archivos grandes.
        $archivos = [];
        $sid = strtolower((string) ($_POST['subida_id'] ?? ''));
        $nombreOrig = (string) ($_POST['subida_nombre'] ?? '');
        if (preg_match('/^[a-f0-9]{16,40}$/', $sid)) {
            $parte = dirname(__DIR__) . "/uploads/tmp/sub-$sid.part";
            $extS  = strtolower(pathinfo($nombreOrig, PATHINFO_EXTENSION));
            if (!is_file($parte)) {
                $error = 'La subida se cortó. Probá de nuevo.';
            } elseif (!in_array($extS, ['stl', 'zip', '3mf', 'obj'], true)) {
                $error = 'El archivo tiene que ser STL, 3MF, OBJ o ZIP.';
            } else {
                $archivos[] = ['tmp' => $parte, 'ext' => $extS,
                               'tam' => (int) filesize($parte), 'movido' => false];
            }
        }

        // Camino viejo (por si el navegador no pudo con los pedazos)
        for ($k = 0; $k < 4 && $error === '' && !$archivos; $k++) {
            if (empty($_FILES['archivos']['tmp_name'][$k]) || !is_uploaded_file($_FILES['archivos']['tmp_name'][$k])) continue;
            $extK = strtolower(pathinfo($_FILES['archivos']['name'][$k], PATHINFO_EXTENSION));
            if (!in_array($extK, ['stl', 'zip', '3mf', 'obj'], true)) {
                $error = 'El archivo ' . ($k + 1) . ' tiene que ser STL, 3MF, OBJ o ZIP.';
            } elseif ($_FILES['archivos']['size'][$k] > 60 * 1024 * 1024) {
                $error = 'El archivo ' . ($k + 1) . ' no puede superar los 60 MB.';
            } else {
                $archivos[] = ['tmp' => $_FILES['archivos']['tmp_name'][$k],
                               'ext' => $extK, 'tam' => (int) $_FILES['archivos']['size'][$k]];
            }
        }

        if ($error !== '') {
            // ya hay mensaje
        } elseif ($nombre === '') {
            $error = 'Poné el nombre del modelo.';
        } elseif (!$archivos) {
            $error = 'Elegí al menos un archivo STL (o ZIP con varios).';
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
                $tam_total = array_sum(array_column($archivos, 'tam'));
                $db->prepare('INSERT INTO stl_items (nombre, categoria, archivo_ext, imagen_ext, tam_bytes, creado_en)
                              VALUES (?, ?, ?, ?, ?, NOW())')
                   ->execute([$nombre, $cat, $archivos[0]['ext'], $img_ext, $tam_total]);
                $id = (int) $db->lastInsertId();
                $ok = stl_mover($archivos[0], "$dir/stl-$id." . $archivos[0]['ext']);
                foreach (array_slice($archivos, 1) as $n => $a) {
                    $orden = $n + 2;
                    if (stl_mover($a, "$dir/stl-$id-$orden." . $a['ext'])) {
                        $db->prepare('INSERT INTO stl_archivos (stl_id, orden, ext, tam_bytes) VALUES (?,?,?,?)')
                           ->execute([$id, $orden, $a['ext'], $a['tam']]);
                    } else {
                        $ok = false;
                    }
                }
                $ok = $ok && ($img_ext === '' || move_uploaded_file($_FILES['imagen']['tmp_name'], "$dir/img-$id.$img_ext"));
                if ($ok) {
                    $aviso = "«{$nombre}» cargado en la librería"
                           . (count($archivos) > 1 ? ' (' . count($archivos) . ' archivos).' : '.');
                } else {
                    foreach (glob("$dir/stl-$id*") ?: [] as $f) @unlink($f);
                    $db->prepare('DELETE FROM stl_items WHERE id=?')->execute([$id]);
                    $error = 'No se pudieron guardar los archivos. Probá de nuevo.';
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
            foreach (glob("$dir/stl-$id*") ?: [] as $f) @unlink($f);
            if ($it['imagen_ext']) @unlink("$dir/img-$id." . $it['imagen_ext']);
            $db->prepare('DELETE FROM stl_items WHERE id=?')->execute([$id]);
            $aviso = 'Modelo eliminado.';
        }
    }
}

$items = $db->query('SELECT i.*, 1 + (SELECT COUNT(*) FROM stl_archivos a WHERE a.stl_id = i.id) AS cant_archivos
                     FROM stl_items i ORDER BY i.creado_en DESC, i.id DESC')->fetchAll();

ui_panel_inicio('Cargar STL', $yo, 'Cargar STL', '../');
?>
    <style>.contenido{max-width:none}</style>
    <h1>Cargar STL</h1>
    <p class="bajada">Subí modelos a la Librería STL que ven todos los usuarios (incluido el plan gratuito).</p>

    <?php if ($aviso): ?><div class="msg ok"><?php echo ui_icono('check', 16); ?><span><?php echo htmlspecialchars($aviso); ?></span></div><?php endif; ?>
    <?php if ($error): ?><div class="msg bad"><?php echo ui_icono('alerta', 16); ?><span><?php echo htmlspecialchars($error); ?></span></div><?php endif; ?>

    <style>
      .alta{background:var(--surface);border:1px solid var(--bd-suave);border-radius:var(--radio-g);
            padding:20px;margin-bottom:18px}
      .alta h2{font-size:15px;font-weight:600;margin-bottom:10px}
      .alta .fila{display:grid;grid-template-columns:1.2fr 1fr;gap:12px}
      .alta .fila2{display:grid;grid-template-columns:1fr 1fr auto;gap:12px;align-items:end;margin-top:10px}
      .alta .fila-archivos{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}
      @media (max-width:1100px){ .alta .fila-archivos{grid-template-columns:1fr 1fr} }
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

    <form class="alta" method="post" enctype="multipart/form-data" id="f-stl">
      <h2>Nuevo modelo</h2>
      <input type="hidden" name="csrf" value="<?php echo com_csrf(); ?>">
      <input type="hidden" name="accion" value="subir">
      <input type="hidden" name="subida_id" id="subidaId">
      <input type="hidden" name="subida_nombre" id="subidaNombre">
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
          <input id="s-arch" type="file" name="archivos[]" accept=".stl,.zip,.3mf,.obj" required>
          <p style="font-size:12px;color:var(--txt-3);margin-top:4px">Si el modelo tiene varias piezas, subilas juntas en un ZIP.
            Límite del servidor: <?php echo htmlspecialchars(ini_get('upload_max_filesize')); ?> por archivo.</p></span>
        <span><label for="s-img">Foto de vista previa (PNG/JPG/WebP)</label>
          <input id="s-img" type="file" name="imagen" accept="image/png,image/jpeg,image/webp"></span>
        <button class="btn" type="submit" id="btnSTL"><?php echo ui_icono('nube', 16); ?> Cargar STL</button>
      </div>
      <div id="barraSTL" hidden style="margin-top:14px">
        <div style="height:6px;border-radius:99px;background:var(--surface-2);overflow:hidden">
          <i id="barraSTLi" style="display:block;height:100%;width:0;background:var(--accent);
             transition:width .2s ease"></i>
        </div>
        <p id="barraSTLtxt" style="font-size:12.5px;color:var(--txt-3);margin-top:6px"></p>
      </div>
    </form>

<script>
/**
 * El archivo se manda por pedazos, no de una.
 *
 * El servidor corta las subidas largas: un STL grande por una conexion casera
 * tarda mas de lo que el hosting espera y devuelve "Request Timeout". Partido
 * en pedazos de 2 MB, cada pedido tarda segundos y no lo corta nadie.
 * Cuando termina, el formulario viaja livianito con la referencia al archivo
 * ya rearmado en el servidor.
 */
(function () {
  var form = document.getElementById('f-stl');
  var campo = document.getElementById('s-arch');
  var boton = document.getElementById('btnSTL');
  var barra = document.getElementById('barraSTL');
  var barraI = document.getElementById('barraSTLi');
  var barraT = document.getElementById('barraSTLtxt');
  var listo = false;
  var TROZO = 2 * 1024 * 1024;

  function id() {
    var s = '';
    for (var i = 0; i < 20; i++) s += Math.floor(Math.random() * 16).toString(16);
    return s;
  }

  form.addEventListener('submit', function (e) {
    var f = campo.files && campo.files[0];
    if (listo || !f || !window.FormData || !f.slice) return;   // sin soporte: camino de siempre

    e.preventDefault();
    boton.disabled = true;
    barra.hidden = false;
    var sid = id(), partes = Math.ceil(f.size / TROZO), i = 0;

    function pintar() {
      var pct = Math.round((i / partes) * 100);
      barraI.style.width = pct + '%';
      barraT.textContent = 'Subiendo ' + f.name + ' — ' + pct + '%';
    }

    function mandar() {
      if (i >= partes) {
        document.getElementById('subidaId').value = sid;
        document.getElementById('subidaNombre').value = f.name;
        campo.value = '';                       // ya viajo por pedazos
        campo.removeAttribute('required');
        barraT.textContent = 'Guardando…';
        listo = true;
        form.submit();
        return;
      }
      var fd = new FormData();
      fd.append('csrf', form.querySelector('[name=csrf]').value);
      fd.append('sid', sid);
      fd.append('i', i);
      fd.append('trozo', f.slice(i * TROZO, (i + 1) * TROZO));

      fetch('stl_trozo.php', { method: 'POST', body: fd, credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (d) {
          if (!d.ok) throw new Error(d.error || 'Falló la subida');
          i++; pintar(); mandar();
        })
        .catch(function (err) {
          boton.disabled = false;
          barraT.textContent = 'No se pudo subir: ' + err.message + '. Probá de nuevo.';
          barraI.style.background = 'var(--mal, #ff6b6b)';
        });
    }
    pintar();
    mandar();
  });
})();
</script>

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
              <span style="font-size:12px;color:var(--txt-3)"><?php echo strtoupper($it['archivo_ext']);
                echo (int) $it['cant_archivos'] > 1 ? ' · ' . (int) $it['cant_archivos'] . ' archivos' : ''; ?></span></td>
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
