<?php
/**
 * Backups: copia de la base de datos (.sql) y de los archivos subidos (.zip).
 * Se pueden descargar al momento o guardar en el servidor.
 *
 * Los guardados viven en uploads/backups/, una carpeta cerrada con su propio
 * .htaccess: un dump tiene los correos y las contraseñas cifradas de todos los
 * usuarios, asi que no puede quedar colgando de una direccion adivinable. Se
 * bajan por este mismo archivo, que ya exige sesion de administradora.
 *
 * El código del sitio ya está respaldado en GitHub.
 */
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/ui.php';
require_once __DIR__ . '/../inc/taller.php';

requerir_admin();
$yo = usuario_actual();
taller_migrar();
$db = com_db();

const BK_GUARDADOS = 5;   // cuantas copias de cada tipo se conservan

$aviso = '';
$error = '';

/** Carpeta de los backups guardados, creada y cerrada la primera vez. */
function bk_dir() {
    $d = dirname(__DIR__) . '/uploads/backups';
    if (!is_dir($d)) mkdir($d, 0755, true);
    $ht = "$d/.htaccess";
    if (!is_file($ht)) {
        // Sin esto, cualquiera que adivine el nombre se baja la base entera
        file_put_contents($ht, "Require all denied\n<IfModule !mod_authz_core.c>\n  Deny from all\n</IfModule>\n");
    }
    return $d;
}

/** Escribe el dump de la base en un archivo (o en la salida si $dest es null). */
function bk_volcar_base(PDO $db, $dest = null) {
    set_time_limit(600);
    $f = $dest ? fopen($dest, 'w') : null;
    $put = function ($t) use ($f) { $f ? fwrite($f, $t) : print($t); };

    $put("-- Backup Printika Tools · " . date('Y-m-d H:i:s') . "\n");
    $put("SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");
    foreach ($db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $t) {
        $crea = $db->query("SHOW CREATE TABLE `$t`")->fetch();
        $put("DROP TABLE IF EXISTS `$t`;\n" . ($crea['Create Table'] ?? '') . ";\n\n");
        foreach ($db->query("SELECT * FROM `$t`") as $fila) {
            $vals = array_map(function ($v) use ($db) {
                return $v === null ? 'NULL' : $db->quote((string) $v);
            }, array_values($fila));
            $put("INSERT INTO `$t` (`" . implode('`,`', array_keys($fila)) . "`) VALUES ("
                 . implode(',', $vals) . ");\n");
        }
        $put("\n");
    }
    $put("SET FOREIGN_KEY_CHECKS=1;\n");
    if ($f) fclose($f);
}

/** Arma el zip de uploads. Devuelve true si salio bien. */
function bk_zip_archivos($destino) {
    $dirUploads = dirname(__DIR__) . '/uploads';
    $zip = new ZipArchive();
    if ($zip->open($destino, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) return false;
    set_time_limit(600);
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dirUploads, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($it as $f) {
        if (!$f->isFile()) continue;
        $rel = substr($f->getPathname(), strlen($dirUploads) + 1);
        // Los backups guardados NO entran: si no, cada copia contendria las
        // anteriores y el archivo se duplicaria de tamano cada vez.
        if (strpos($rel, 'backups/') === 0 || strpos($rel, 'tmp/') === 0) continue;
        $zip->addFile($f->getPathname(), 'uploads/' . $rel);
    }
    $ok = $zip->close();
    return $ok && is_file($destino);
}

/** Deja solo las ultimas copias de un tipo y borra las viejas. */
function bk_rotar($prefijo) {
    $files = glob(bk_dir() . "/$prefijo-*") ?: [];
    usort($files, function ($a, $b) { return filemtime($b) <=> filemtime($a); });
    foreach (array_slice($files, BK_GUARDADOS) as $viejo) @unlink($viejo);
}

/** Las copias guardadas, la mas nueva primero. */
function bk_listar() {
    $files = glob(bk_dir() . '/*.{sql,zip}', GLOB_BRACE) ?: [];
    usort($files, function ($a, $b) { return filemtime($b) <=> filemtime($a); });
    return array_map(function ($f) {
        return ['nombre' => basename($f), 'tam' => filesize($f), 'fecha' => filemtime($f),
                'tipo' => substr($f, -4) === '.sql' ? 'Base de datos' : 'Archivos'];
    }, $files);
}

/** Tamaño legible. */
function bk_tam($b) {
    if ($b >= 1073741824) return round($b / 1073741824, 1) . ' GB';
    if ($b >= 1048576)    return round($b / 1048576, 1) . ' MB';
    if ($b >= 1024)       return round($b / 1024) . ' KB';
    return $b . ' B';
}

/** Solo aceptamos nombres con la forma exacta de un backup nuestro. */
function bk_nombre_valido($nombre) {
    return (bool) preg_match('/^printikatools-(base|archivos)-[\w-]+\.(sql|zip)$/', $nombre);
}

// ---- Descargar en el momento ----
if (isset($_GET['db'])) {
    cfg_set('backup_db_ultimo', date('Y-m-d H:i:s'));
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="printikatools-base-' . date('Y-m-d-Hi') . '.sql"');
    bk_volcar_base($db);
    exit;
}

if (isset($_GET['archivos'])) {
    $tmp = tempnam(sys_get_temp_dir(), 'ptbk');
    if (bk_zip_archivos($tmp)) {
        cfg_set('backup_archivos_ultimo', date('Y-m-d H:i:s'));
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="printikatools-archivos-' . date('Y-m-d-Hi') . '.zip"');
        header('Content-Length: ' . filesize($tmp));
        readfile($tmp);
        @unlink($tmp);
        exit;
    }
    @unlink($tmp);
    $error = 'No se pudo generar el zip.';
}

// ---- Bajar una copia ya guardada ----
if (isset($_GET['bajar'])) {
    $nombre = basename((string) $_GET['bajar']);          // nunca una ruta
    $ruta = bk_dir() . '/' . $nombre;
    if (bk_nombre_valido($nombre) && is_file($ruta)) {
        header('Content-Type: ' . (substr($nombre, -4) === '.sql' ? 'application/sql' : 'application/zip'));
        header('Content-Disposition: attachment; filename="' . $nombre . '"');
        header('Content-Length: ' . filesize($ruta));
        readfile($ruta);
        exit;
    }
    $error = 'Esa copia ya no está.';
}

// ---- Guardar en el servidor / borrar ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    if (!com_csrf_ok($_POST['csrf'] ?? '')) {
        $error = 'La sesión expiró, probá de nuevo.';

    } elseif ($accion === 'guardar_db') {
        $nombre = 'printikatools-base-' . date('Y-m-d-Hi') . '.sql';
        bk_volcar_base($db, bk_dir() . '/' . $nombre);
        bk_rotar('printikatools-base');
        cfg_set('backup_db_ultimo', date('Y-m-d H:i:s'));
        $aviso = "Copia de la base guardada en el servidor ($nombre).";

    } elseif ($accion === 'guardar_archivos') {
        $nombre = 'printikatools-archivos-' . date('Y-m-d-Hi') . '.zip';
        if (bk_zip_archivos(bk_dir() . '/' . $nombre)) {
            bk_rotar('printikatools-archivos');
            cfg_set('backup_archivos_ultimo', date('Y-m-d H:i:s'));
            $aviso = "Copia de los archivos guardada en el servidor ($nombre).";
        } else {
            $error = 'No se pudo generar el zip.';
        }

    } elseif ($accion === 'borrar') {
        $nombre = basename((string) ($_POST['nombre'] ?? ''));
        $ruta = bk_dir() . '/' . $nombre;
        if (bk_nombre_valido($nombre) && is_file($ruta)) {
            @unlink($ruta);
            $aviso = 'Copia eliminada.';
        }
    }
}

$ultimo_db   = cfg_get('backup_db_ultimo');
$ultimo_arch = cfg_get('backup_archivos_ultimo');
$hace = function ($f) {
    if (!$f) return 'nunca';
    $d = max(0, floor((time() - strtotime($f)) / 86400));
    return $d === 0 ? 'hoy' : "hace $d día" . ($d === 1 ? '' : 's');
};
$guardadas = bk_listar();
$ocupado = array_sum(array_column($guardadas, 'tam'));

ui_panel_inicio('Backups', $yo, 'Backups', '../');
?>
    <style>.contenido{max-width:none}</style>
    <h1>Backups</h1>
    <p class="bajada">Copias de seguridad de lo irrecuperable: la base de datos y los archivos subidos.
       Podés descargarlas o dejarlas guardadas en el servidor.</p>

    <?php if ($aviso): ?><div class="msg ok"><?php echo ui_icono('check', 16); ?><span><?php echo htmlspecialchars($aviso); ?></span></div><?php endif; ?>
    <?php if ($error): ?><div class="msg bad"><?php echo ui_icono('alerta', 16); ?><span><?php echo htmlspecialchars($error); ?></span></div><?php endif; ?>

    <style>
      .bk{display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:14px}
      .bk-caja{background:var(--surface);border:1px solid var(--bd-suave);border-radius:var(--radio-g);padding:22px;
               display:flex;flex-direction:column;gap:8px}
      .bk-caja h2{font-size:15px;font-weight:600}
      .bk-caja p{font-size:13px;color:var(--txt-2);line-height:1.55;flex:1}
      .bk-caja .cuando{font-size:12px;color:var(--txt-3)}
      .bk-caja .acciones{display:flex;flex-wrap:wrap;gap:8px;margin-top:4px}
      .bk-caja form{margin:0}
      .nota-git{margin-top:18px;font-size:13px;color:var(--txt-3);line-height:1.6}
      .lista{background:var(--surface);border:1px solid var(--bd-suave);border-radius:var(--radio-g);
             padding:6px 20px;margin-top:14px;overflow-x:auto}
      table{width:100%;border-collapse:collapse;font-size:13.5px}
      th,td{padding:12px 10px;text-align:left;border-bottom:1px solid var(--bd-suave);vertical-align:middle}
      th{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.07em;color:var(--txt-3)}
      tr:last-child td{border-bottom:none}
      td .acc{display:flex;gap:6px;justify-content:flex-end}
      td form{margin:0}
      .vacio{font-size:13.5px;color:var(--txt-3);padding:14px 0}
    </style>

    <div class="bk">
      <div class="bk-caja">
        <h2>Base de datos</h2>
        <p>Usuarios, suscripciones, presupuestos, clientes, stock, cotizaciones y guías: todo el negocio
           en un archivo .sql que se puede restaurar en cualquier hosting.</p>
        <span class="cuando">Última copia: <?php echo $hace($ultimo_db); ?></span>
        <div class="acciones">
          <form method="post">
            <input type="hidden" name="csrf" value="<?php echo com_csrf(); ?>">
            <input type="hidden" name="accion" value="guardar_db">
            <button class="btn" type="submit"><?php echo ui_icono('nube', 16); ?> Guardar en el servidor</button>
          </form>
          <a class="btn sec" href="backups.php?db=1"><?php echo ui_icono('descargar', 16); ?> Descargar</a>
        </div>
      </div>
      <div class="bk-caja">
        <h2>Archivos subidos</h2>
        <p>Los logos de los usuarios, los modelos STL, los recursos y las imágenes de las guías, en un zip.
           Es lo único que no viaja por GitHub.</p>
        <span class="cuando">Última copia: <?php echo $hace($ultimo_arch); ?></span>
        <div class="acciones">
          <form method="post">
            <input type="hidden" name="csrf" value="<?php echo com_csrf(); ?>">
            <input type="hidden" name="accion" value="guardar_archivos">
            <button class="btn" type="submit"><?php echo ui_icono('nube', 16); ?> Guardar en el servidor</button>
          </form>
          <a class="btn sec" href="backups.php?archivos=1"><?php echo ui_icono('descargar', 16); ?> Descargar</a>
        </div>
      </div>
    </div>

    <h2 style="font-size:15px;font-weight:600;margin:26px 0 4px">Copias guardadas en el servidor</h2>
    <p class="bajada" style="margin-bottom:0">Se conservan las <?php echo BK_GUARDADOS; ?> últimas de cada tipo;
       las más viejas se borran solas. Ocupan <?php echo bk_tam($ocupado); ?>.</p>

    <?php if ($guardadas): ?>
    <div class="lista">
      <table>
        <thead><tr><th>Copia</th><th>Tipo</th><th>Tamaño</th><th>Fecha</th><th style="text-align:right">Acciones</th></tr></thead>
        <tbody>
        <?php foreach ($guardadas as $g): ?>
          <tr>
            <td><strong><?php echo htmlspecialchars($g['nombre']); ?></strong></td>
            <td><?php echo $g['tipo']; ?></td>
            <td><?php echo bk_tam($g['tam']); ?></td>
            <td><?php echo date('d/m/Y H:i', $g['fecha']); ?></td>
            <td>
              <div class="acc">
                <a class="btn sec" style="height:32px;padding:0 12px;font-size:12.5px"
                   href="backups.php?bajar=<?php echo rawurlencode($g['nombre']); ?>">Descargar</a>
                <form method="post" onsubmit="return confirm('¿Eliminar esta copia?')">
                  <input type="hidden" name="csrf" value="<?php echo com_csrf(); ?>">
                  <input type="hidden" name="accion" value="borrar">
                  <input type="hidden" name="nombre" value="<?php echo htmlspecialchars($g['nombre']); ?>">
                  <button class="btn sec" style="height:32px;padding:0 12px;font-size:12.5px" type="submit"><?php echo ui_icono('basura', 14); ?></button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
      <p class="vacio">Todavía no guardaste ninguna copia en el servidor.</p>
    <?php endif; ?>

    <p class="nota-git">El código del sitio ya tiene respaldo automático: cada cambio queda versionado en GitHub.
      <br><strong>Ojo:</strong> una copia guardada en el mismo servidor te salva de un borrado por error, pero no
      de que se caiga el servidor. Cada tanto conviene descargar una y guardarla en otro lado.</p>
<?php ui_panel_fin(); ?>
