<?php
/**
 * Guías: alta, edición y listado de las notas públicas de /guias/.
 *
 * La nota se arma con recuadros (bloques). Cada uno tiene su tipo y su
 * contenido, y se guardan como JSON. Las imágenes van a uploads/guias/,
 * fuera de git, igual que los recursos.
 */
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/ui.php';
require_once __DIR__ . '/../inc/taller.php';
require_once __DIR__ . '/../inc/guias.php';

requerir_admin();
$yo = usuario_actual();
taller_migrar();
$db = com_db();

$dir = guia_dir();
$aviso = '';
$error = '';

/** Valida una imagen subida y devuelve su extensión ('' si no vino). */
function guias_img_ext($campo, &$error) {
    if (empty($_FILES[$campo]['tmp_name']) || !is_uploaded_file($_FILES[$campo]['tmp_name'])) return '';
    $info = @getimagesize($_FILES[$campo]['tmp_name']);
    $tipos = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp'];
    if (!$info || !isset($tipos[$info['mime']]) || $_FILES[$campo]['size'] > 4 * 1024 * 1024) {
        $error = 'Las imágenes tienen que ser PNG, JPG o WebP de hasta 4 MB.';
        return '';
    }
    return $tipos[$info['mime']];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if (!com_csrf_ok($_POST['csrf'] ?? '')) {
        $error = 'La sesión expiró, probá de nuevo.';

    } elseif ($accion === 'guardar') {
        $id      = (int) ($_POST['id'] ?? 0);
        $titulo  = mb_substr(trim($_POST['titulo'] ?? ''), 0, 180);
        $ceja    = mb_substr(trim($_POST['ceja'] ?? ''), 0, 40) ?: 'Guía';
        $bajada  = mb_substr(trim($_POST['bajada'] ?? ''), 0, 300);
        $minutos = max(1, min(60, (int) ($_POST['minutos'] ?? 5)));
        $youtube = guia_youtube_id($_POST['youtube'] ?? '');
        $slugPed = trim($_POST['slug'] ?? '');
        $slug    = guia_slug($slugPed !== '' ? $slugPed : $titulo);

        $anterior = null;
        if ($id > 0) {
            $stmt = $db->prepare('SELECT * FROM guias WHERE id = ?');
            $stmt->execute([$id]);
            $anterior = $stmt->fetch() ?: null;
            if (!$anterior) $error = 'Esa guía ya no existe.';
        }

        // La dirección tiene que ser única y no pisar a la guía escrita a mano
        if ($error === '') {
            if ($slug === 'cuanto-cobrar-impresion-3d') {
                $error = 'Esa dirección ya la usa la primera guía. Cambiá el título o la dirección.';
            } else {
                $stmt = $db->prepare('SELECT id FROM guias WHERE slug = ? AND id <> ?');
                $stmt->execute([$slug, $id]);
                if ($stmt->fetch()) $error = 'Ya hay otra guía con esa dirección. Cambiala.';
            }
        }
        if ($error === '' && $titulo === '') $error = 'Poné el título de la guía.';

        if ($error === '') {
            // Armado de los bloques a partir del formulario
            $viejos = $anterior ? guia_bloques($anterior) : [];
            $porId = [];
            foreach ($viejos as $b) { if (!empty($b['id'])) $porId[$b['id']] = $b; }

            $tipos  = $_POST['bloque_tipo'] ?? [];
            $valores = $_POST['bloque_valor'] ?? [];
            $pies   = $_POST['bloque_pie'] ?? [];
            $ids    = $_POST['bloque_id'] ?? [];
            $bloques = [];
            $subir = [];   // imágenes nuevas: [campo, bid, ext]

            foreach ($tipos as $i => $tipo) {
                if (!isset(guia_tipos()[$tipo])) continue;
                $bid = preg_replace('/[^a-z0-9]/', '', (string) ($ids[$i] ?? '')) ?: substr(md5($i . microtime()), 0, 8);
                $valor = trim((string) ($valores[$i] ?? ''));
                $pie   = mb_substr(trim((string) ($pies[$i] ?? '')), 0, 200);

                if ($tipo === 'imagen') {
                    // Por id y no por posicion: si se mueve o se borra un
                    // recuadro, las posiciones cambian y la imagen se perderia.
                    $campo = 'bloque_img_' . $bid;
                    $ext = guias_img_ext($campo, $error);
                    if ($error !== '') break;
                    $extVieja = $porId[$bid]['ext'] ?? '';
                    if ($ext === '') $ext = $extVieja;                   // se mantiene la que ya estaba
                    else $subir[] = [$campo, $bid, $ext, $extVieja];
                    if ($ext === '') continue;                          // bloque de imagen vacío: se descarta
                    $bloques[] = ['t' => 'imagen', 'id' => $bid, 'ext' => $ext, 'pie' => $pie];

                } elseif ($tipo === 'video') {
                    $ytid = guia_youtube_id($valor);
                    if ($ytid === '') continue;                          // sin link válido: se descarta
                    $bloques[] = ['t' => 'video', 'id' => $bid, 'v' => $ytid, 'pie' => $pie];

                } else {
                    if ($valor === '') continue;
                    $bloques[] = ['t' => $tipo, 'id' => $bid, 'v' => $valor];
                }
            }

            if ($error === '') {
                $json = json_encode($bloques, JSON_UNESCAPED_UNICODE);
                if ($id > 0) {
                    $db->prepare('UPDATE guias SET slug=?, titulo=?, ceja=?, bajada=?, cuerpo_json=?,
                                  youtube=?, minutos=?, actualizado_en=NOW() WHERE id=?')
                       ->execute([$slug, $titulo, $ceja, $bajada, $json, $youtube, $minutos, $id]);
                } else {
                    $db->prepare('INSERT INTO guias (slug, titulo, ceja, bajada, cuerpo_json, youtube,
                                  minutos, publicado, creado_en, actualizado_en)
                                  VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())')
                       ->execute([$slug, $titulo, $ceja, $bajada, $json, $youtube, $minutos]);
                    $id = (int) $db->lastInsertId();
                }

                // Portada y las imágenes de los bloques, ya con el id definitivo
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                $portada = guias_img_ext('portada', $error);
                if ($portada !== '' && move_uploaded_file($_FILES['portada']['tmp_name'], "$dir/g$id-portada.$portada")) {
                    $viejaExt = $anterior['imagen_ext'] ?? '';
                    if ($viejaExt !== '' && $viejaExt !== $portada) @unlink("$dir/g$id-portada.$viejaExt");
                    $db->prepare('UPDATE guias SET imagen_ext=? WHERE id=?')->execute([$portada, $id]);
                }
                foreach ($subir as [$campo, $bid, $ext, $extVieja]) {
                    if (move_uploaded_file($_FILES[$campo]['tmp_name'], "$dir/g$id-$bid.$ext")
                        && $extVieja !== '' && $extVieja !== $ext) {
                        @unlink("$dir/g$id-$bid.$extVieja");   // si cambio el formato, se va la anterior
                    }
                }

                $aviso = "«{$titulo}» guardada. Se ve en /guias/{$slug}/";
                header('Location: guias.php?ok=' . rawurlencode($aviso));
                exit;
            }
        }

    } elseif ($accion === 'publicar') {
        $id = (int) ($_POST['id'] ?? 0);
        $db->prepare('UPDATE guias SET publicado = 1 - publicado, actualizado_en = NOW() WHERE id = ?')
           ->execute([$id]);
        $aviso = 'Listo, cambió el estado de la guía.';

    } elseif ($accion === 'borrar') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $db->prepare('SELECT * FROM guias WHERE id = ?');
        $stmt->execute([$id]);
        if ($g = $stmt->fetch()) {
            foreach (glob("$dir/g$id-*") ?: [] as $f) @unlink($f);
            $db->prepare('DELETE FROM guias WHERE id = ?')->execute([$id]);
            $aviso = "«{$g['titulo']}» eliminada.";
        }
    }
}

if (isset($_GET['ok'])) $aviso = mb_substr((string) $_GET['ok'], 0, 200);

$editar = null;
if (preg_match('/^\d+$/', $_GET['editar'] ?? '')) {
    $stmt = $db->prepare('SELECT * FROM guias WHERE id = ?');
    $stmt->execute([(int) $_GET['editar']]);
    $editar = $stmt->fetch() ?: null;
}
$bloquesEdit = $editar ? guia_bloques($editar) : [];
$guias = $db->query('SELECT * FROM guias ORDER BY creado_en DESC, id DESC')->fetchAll();

ui_panel_inicio('Guías', $yo, 'Guías', '../');
?>
    <style>.contenido{max-width:none}</style>
    <h1>Guías</h1>
    <p class="bajada">Las notas públicas de <a href="/guias/" target="_blank" rel="noopener">printikatools.com/guias/</a>.
       Son las páginas por las que llega gente que todavía no conoce Printika.</p>

    <?php if ($aviso): ?><div class="msg ok"><?php echo ui_icono('check', 16); ?><span><?php echo htmlspecialchars($aviso); ?></span></div><?php endif; ?>
    <?php if ($error): ?><div class="msg bad"><?php echo ui_icono('alerta', 16); ?><span><?php echo htmlspecialchars($error); ?></span></div><?php endif; ?>

    <style>
      .alta{background:var(--surface);border:1px solid var(--bd-suave);border-radius:var(--radio-g);
            padding:20px;margin-bottom:18px}
      .alta h2{font-size:15px;font-weight:600;margin-bottom:12px}
      .fila{display:grid;grid-template-columns:2fr 1fr;gap:12px}
      .fila3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px}
      input[type=file]{height:auto;padding:8px 12px;font-size:13px}
      .ayuda{font-size:12.5px;color:var(--txt-3);margin-top:4px}
      textarea{width:100%;min-height:110px;resize:vertical;font-family:inherit;font-size:14px;line-height:1.6}

      .bloques{display:flex;flex-direction:column;gap:12px;margin-top:6px}
      .bloque{background:var(--surface-2);border:1px solid var(--bd-suave);border-radius:var(--radio);
              padding:14px 16px}
      .bloque .cab{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:9px}
      .bloque .nombre{font-size:12px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;
              color:var(--accent)}
      .bloque .mandos{display:flex;gap:4px}
      .bloque .mandos button{background:none;border:1px solid var(--bd-suave);border-radius:8px;
              color:var(--txt-3);cursor:pointer;padding:5px 9px;font-size:13px;line-height:1}
      .bloque .mandos button:hover{color:var(--txt);border-color:var(--bd)}
      .bloque label{font-size:12px;color:var(--txt-3);margin-top:8px;display:block}
      .agregar{display:flex;flex-wrap:wrap;gap:8px;margin-top:14px}
      .agregar button{background:var(--surface-2);border:1px dashed var(--bd);border-radius:999px;
              color:var(--txt-2);cursor:pointer;padding:8px 15px;font-size:13px;font-weight:600}
      .agregar button:hover{color:var(--txt);border-color:var(--accent);border-style:solid}
      .vacio{font-size:13.5px;color:var(--txt-3);padding:14px 0}

      .lista{background:var(--surface);border:1px solid var(--bd-suave);border-radius:var(--radio-g);
             padding:6px 20px;overflow-x:auto}
      table{width:100%;border-collapse:collapse;font-size:13.5px}
      th,td{padding:12px 10px;text-align:left;border-bottom:1px solid var(--bd-suave);vertical-align:middle}
      th{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.07em;color:var(--txt-3)}
      tr:last-child td{border-bottom:none}
      .mini{width:80px;height:45px;border-radius:6px;background:var(--surface-2);object-fit:cover;display:block}
      .apagado{opacity:.5}
      td .acciones{display:flex;gap:6px;justify-content:flex-end}
      td form{margin:0}
    </style>

    <form class="alta" method="post" enctype="multipart/form-data" id="f-guia">
      <h2><?php echo $editar ? 'Editar guía' : 'Nueva guía'; ?></h2>
      <input type="hidden" name="csrf" value="<?php echo com_csrf(); ?>">
      <input type="hidden" name="accion" value="guardar">
      <input type="hidden" name="id" value="<?php echo (int) ($editar['id'] ?? 0); ?>">

      <div class="fila">
        <span><label for="g-titulo">Título *</label>
          <input id="g-titulo" type="text" name="titulo" maxlength="180" required
                 placeholder="¿Cuánto gasta de luz una impresora 3D?"
                 value="<?php echo htmlspecialchars($editar['titulo'] ?? ''); ?>"></span>
        <span><label for="g-ceja">Etiqueta</label>
          <input id="g-ceja" type="text" name="ceja" maxlength="40" placeholder="Precios"
                 value="<?php echo htmlspecialchars($editar['ceja'] ?? 'Guía'); ?>"></span>
      </div>

      <label for="g-bajada" style="margin-top:10px">Resumen corto (se ve en el listado y en Google)</label>
      <input id="g-bajada" type="text" name="bajada" maxlength="300"
             placeholder="Cuánto consume una impresora por hora y cómo se calcula en la factura."
             value="<?php echo htmlspecialchars($editar['bajada'] ?? ''); ?>">

      <div class="fila3" style="margin-top:10px">
        <span><label for="g-slug">Dirección web</label>
          <input id="g-slug" type="text" name="slug" maxlength="120"
                 placeholder="se arma sola con el título"
                 value="<?php echo htmlspecialchars($editar['slug'] ?? ''); ?>">
          <p class="ayuda">printikatools.com/guias/<b id="vista-slug"><?php echo htmlspecialchars($editar['slug'] ?? '…'); ?></b>/</p></span>
        <span><label for="g-min">Minutos de lectura</label>
          <input id="g-min" type="number" name="minutos" min="1" max="60"
                 value="<?php echo (int) ($editar['minutos'] ?? 5); ?>"></span>
        <span><label for="g-port">Imagen de portada <?php echo $editar ? '(solo si la querés cambiar)' : '(opcional)'; ?></label>
          <input id="g-port" type="file" name="portada" accept="image/png,image/jpeg,image/webp"></span>
      </div>

      <label for="g-yt" style="margin-top:10px">Video de YouTube destacado (opcional)</label>
      <input id="g-yt" type="text" name="youtube" maxlength="200"
             placeholder="https://youtu.be/… — se muestra arriba de todo en la nota"
             value="<?php echo htmlspecialchars($editar['youtube'] ?? ''); ?>">

      <h2 style="margin-top:22px">Contenido de la nota</h2>
      <p class="ayuda" style="margin-bottom:8px">Agregá los recuadros que quieras y ordenalos con las flechas.
         Escribí texto normal: no hace falta saber nada de HTML.</p>
      <div class="bloques" id="bloques">
        <?php if (!$bloquesEdit): ?>
          <p class="vacio" id="sin-bloques">Todavía no agregaste ningún recuadro. Empezá con «Texto».</p>
        <?php endif; ?>
      </div>

      <div class="agregar">
        <?php foreach (guia_tipos() as $t => $d): ?>
          <button type="button" onclick="agregarBloque('<?php echo $t; ?>')">+ <?php echo htmlspecialchars($d[0]); ?></button>
        <?php endforeach; ?>
      </div>

      <div style="display:flex;gap:10px;align-items:center;margin-top:20px">
        <button class="btn" type="submit"><?php echo $editar ? 'Guardar cambios' : ui_icono('check', 16) . ' Publicar guía'; ?></button>
        <?php if ($editar): ?>
          <a class="btn sec" href="/guias/<?php echo htmlspecialchars($editar['slug']); ?>/" target="_blank" rel="noopener">Ver la nota</a>
          <a href="guias.php" style="font-size:13.5px">Cancelar edición</a>
        <?php endif; ?>
      </div>
    </form>

    <?php if ($guias): ?>
    <div class="lista">
      <table>
        <thead><tr><th></th><th>Guía</th><th>Dirección</th><th>Actualizada</th><th>Estado</th><th style="text-align:right">Acciones</th></tr></thead>
        <tbody>
        <?php foreach ($guias as $g): ?>
          <tr class="<?php echo $g['publicado'] ? '' : 'apagado'; ?>">
            <td><?php if ($g['imagen_ext']): ?>
              <img class="mini" src="<?php echo htmlspecialchars(guia_img_url($g['id'], 'portada', $g['imagen_ext'])); ?>" alt="">
              <?php else: ?><span class="mini" style="display:flex;align-items:center;justify-content:center;color:var(--txt-3)"><?php echo ui_icono('cursos', 20); ?></span><?php endif; ?></td>
            <td><strong><?php echo htmlspecialchars($g['titulo']); ?></strong>
              <?php if ($g['bajada']): ?><br><span style="font-size:12px;color:var(--txt-3)"><?php echo htmlspecialchars($g['bajada']); ?></span><?php endif; ?></td>
            <td><a href="/guias/<?php echo htmlspecialchars($g['slug']); ?>/" target="_blank" rel="noopener">/guias/<?php echo htmlspecialchars($g['slug']); ?>/</a></td>
            <td><?php echo date('d/m/Y', strtotime($g['actualizado_en'])); ?></td>
            <td><?php echo $g['publicado'] ? 'Publicada' : 'Oculta'; ?></td>
            <td>
              <div class="acciones">
                <a class="btn sec" style="height:32px;padding:0 12px;font-size:12.5px"
                   href="guias.php?editar=<?php echo (int) $g['id']; ?>">Editar</a>
                <form method="post">
                  <input type="hidden" name="csrf" value="<?php echo com_csrf(); ?>">
                  <input type="hidden" name="accion" value="publicar">
                  <input type="hidden" name="id" value="<?php echo (int) $g['id']; ?>">
                  <button class="btn sec" style="height:32px;padding:0 12px;font-size:12.5px" type="submit">
                    <?php echo $g['publicado'] ? 'Ocultar' : 'Publicar'; ?></button>
                </form>
                <form method="post" onsubmit="return confirm('¿Eliminar «<?php echo htmlspecialchars(addslashes($g['titulo'])); ?>»? No se puede deshacer.')">
                  <input type="hidden" name="csrf" value="<?php echo com_csrf(); ?>">
                  <input type="hidden" name="accion" value="borrar">
                  <input type="hidden" name="id" value="<?php echo (int) $g['id']; ?>">
                  <button class="btn sec" style="height:32px;padding:0 12px;font-size:12.5px" type="submit"><?php echo ui_icono('basura', 14); ?></button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>

<script>
// Los recuadros del cuerpo se arman acá y viajan como campos del formulario.
var TIPOS = <?php echo json_encode(array_map(function ($d) { return ['nombre' => $d[0], 'ej' => $d[1]]; }, guia_tipos()), JSON_UNESCAPED_UNICODE); ?>;
var n = 0;

function nuevoId() { return Math.random().toString(36).slice(2, 10).replace(/[^a-z0-9]/g, 'a'); }

function agregarBloque(tipo, datos) {
  datos = datos || {};
  var sin = document.getElementById('sin-bloques');
  if (sin) sin.remove();

  var i = n++;
  var bid = datos.id || nuevoId();
  var d = TIPOS[tipo];
  var caja = document.createElement('div');
  caja.className = 'bloque';

  var campo = '';
  if (tipo === 'imagen') {
    campo = '<input type="file" name="bloque_img_' + bid + '" accept="image/png,image/jpeg,image/webp">'
          + (datos.ext ? '<p class="ayuda">Ya tiene una imagen cargada. Elegí otra solo si la querés cambiar.</p>' : '')
          + '<label>Epígrafe (opcional)</label>'
          + '<input type="text" name="bloque_pie[]" maxlength="200" value="' + (datos.pie || '') + '">'
          + '<input type="hidden" name="bloque_valor[]" value="">';
  } else if (tipo === 'video') {
    campo = '<input type="text" name="bloque_valor[]" maxlength="200" placeholder="https://youtu.be/…" value="'
          + (datos.v || '') + '">'
          + '<label>Epígrafe (opcional)</label>'
          + '<input type="text" name="bloque_pie[]" maxlength="200" value="' + (datos.pie || '') + '">';
  } else if (tipo === 'titulo' || tipo === 'formula') {
    campo = '<input type="text" name="bloque_valor[]" maxlength="300" placeholder="' + d.ej + '" value="'
          + (datos.v || '') + '">'
          + '<input type="hidden" name="bloque_pie[]" value="">';
  } else {
    campo = '<textarea name="bloque_valor[]" placeholder="' + d.ej + '">' + (datos.v || '') + '</textarea>'
          + '<input type="hidden" name="bloque_pie[]" value="">';
  }

  caja.innerHTML =
    '<div class="cab"><span class="nombre">' + d.nombre + '</span>' +
    '<span class="mandos">' +
      '<button type="button" onclick="moverBloque(this,-1)" title="Subir">&uarr;</button>' +
      '<button type="button" onclick="moverBloque(this,1)" title="Bajar">&darr;</button>' +
      '<button type="button" onclick="borrarBloque(this)" title="Quitar">&times;</button>' +
    '</span></div>' +
    '<input type="hidden" name="bloque_tipo[]" value="' + tipo + '">' +
    '<input type="hidden" name="bloque_id[]" value="' + bid + '">' +
    campo;

  document.getElementById('bloques').appendChild(caja);
}

function moverBloque(b, dir) {
  var caja = b.closest('.bloque'), cont = caja.parentNode;
  if (dir < 0 && caja.previousElementSibling) cont.insertBefore(caja, caja.previousElementSibling);
  if (dir > 0 && caja.nextElementSibling) cont.insertBefore(caja.nextElementSibling, caja);
}

function borrarBloque(b) {
  var caja = b.closest('.bloque'), cont = caja.parentNode;
  caja.remove();
  if (!cont.children.length) {
    cont.innerHTML = '<p class="vacio" id="sin-bloques">Todavía no agregaste ningún recuadro. Empezá con «Texto».</p>';
  }
}

// La dirección web se arma sola mientras se escribe el título
(function () {
  var t = document.getElementById('g-titulo'), s = document.getElementById('g-slug'),
      vista = document.getElementById('vista-slug');
  function limpiar(x) {
    return x.toLowerCase()
      .normalize('NFD').replace(/[̀-ͯ]/g, '')
      .replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 90);
  }
  function pintar() { vista.textContent = limpiar(s.value || t.value) || '…'; }
  t.addEventListener('input', pintar);
  s.addEventListener('input', pintar);
})();

// Al editar, se reconstruyen los recuadros guardados
<?php foreach ($bloquesEdit as $b): ?>
agregarBloque(<?php echo json_encode($b['t'], JSON_UNESCAPED_UNICODE); ?>, <?php
    echo json_encode([
        'id'  => $b['id'] ?? '',
        'v'   => $b['v'] ?? '',
        'pie' => $b['pie'] ?? '',
        'ext' => $b['ext'] ?? '',
    ], JSON_UNESCAPED_UNICODE | JSON_HEX_QUOT | JSON_HEX_APOS); ?>);
<?php endforeach; ?>
</script>
<?php ui_panel_fin(); ?>
