<?php
/**
 * Backups: descarga de la base de datos completa (.sql) y de los archivos
 * subidos (zip de uploads). El código ya está respaldado en GitHub.
 */
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/ui.php';
require_once __DIR__ . '/../inc/taller.php';

requerir_admin();
$yo = usuario_actual();
taller_migrar();
$db = com_db();

// ---- Descargar base de datos (dump SQL generado en PHP) ----
if (isset($_GET['db'])) {
    cfg_set('backup_db_ultimo', date('Y-m-d H:i:s'));
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="printikatools-base-' . date('Y-m-d-Hi') . '.sql"');
    set_time_limit(300);
    echo "-- Backup Printika Tools · " . date('Y-m-d H:i:s') . "\n";
    echo "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n";
    $tablas = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tablas as $t) {
        $crea = $db->query("SHOW CREATE TABLE `$t`")->fetch();
        echo "DROP TABLE IF EXISTS `$t`;\n" . ($crea['Create Table'] ?? '') . ";\n\n";
        $filas = $db->query("SELECT * FROM `$t`");
        foreach ($filas as $fila) {
            $vals = array_map(function ($v) use ($db) {
                return $v === null ? 'NULL' : $db->quote((string) $v);
            }, array_values($fila));
            $cols = '`' . implode('`,`', array_keys($fila)) . '`';
            echo "INSERT INTO `$t` ($cols) VALUES (" . implode(',', $vals) . ");\n";
        }
        echo "\n";
    }
    echo "SET FOREIGN_KEY_CHECKS=1;\n";
    exit;
}

// ---- Descargar archivos subidos (zip de uploads) ----
if (isset($_GET['archivos'])) {
    $dirUploads = dirname(__DIR__) . '/uploads';
    $tmp = tempnam(sys_get_temp_dir(), 'ptbk');
    $zip = new ZipArchive();
    if ($zip->open($tmp, ZipArchive::OVERWRITE) === true) {
        set_time_limit(300);
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dirUploads, FilesystemIterator::SKIP_DOTS)
        );
        foreach ($it as $f) {
            if ($f->isFile()) {
                $zip->addFile($f->getPathname(), 'uploads/' . substr($f->getPathname(), strlen($dirUploads) + 1));
            }
        }
        $zip->close();
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

$ultimo_db   = cfg_get('backup_db_ultimo');
$ultimo_arch = cfg_get('backup_archivos_ultimo');
$hace = function ($f) {
    if (!$f) return 'nunca';
    $d = max(0, floor((time() - strtotime($f)) / 86400));
    return $d === 0 ? 'hoy' : "hace $d día" . ($d === 1 ? '' : 's');
};

ui_panel_inicio('Backups', $yo, 'Backups', '../');
?>
    <h1>Backups</h1>
    <p class="bajada">Descargá una copia de seguridad de lo irrecuperable: la base de datos y los archivos subidos.</p>

    <?php if (!empty($error)): ?><div class="msg bad"><?php echo ui_icono('alerta', 16); ?><span><?php echo htmlspecialchars($error); ?></span></div><?php endif; ?>

    <style>
      .bk{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:14px;max-width:900px}
      .bk-caja{background:var(--surface);border:1px solid var(--bd-suave);border-radius:var(--radio-g);padding:22px;
               display:flex;flex-direction:column;gap:8px}
      .bk-caja h2{font-size:15px;font-weight:600}
      .bk-caja p{font-size:13px;color:var(--txt-2);line-height:1.55;flex:1}
      .bk-caja .cuando{font-size:12px;color:var(--txt-3)}
      .nota-git{max-width:900px;margin-top:16px;font-size:13px;color:var(--txt-3);line-height:1.6}
    </style>

    <div class="bk">
      <div class="bk-caja">
        <h2>Base de datos</h2>
        <p>Usuarios, suscripciones, presupuestos, clientes, stock, cotizaciones… todo el negocio en un archivo .sql
           que se puede restaurar en cualquier hosting.</p>
        <span class="cuando">Último backup: <?php echo $hace($ultimo_db); ?></span>
        <a class="btn" href="backups.php?db=1"><?php echo ui_icono('descargar', 16); ?> Descargar base de datos</a>
      </div>
      <div class="bk-caja">
        <h2>Archivos subidos</h2>
        <p>Los logos de los usuarios y los modelos STL de la librería, en un zip. Es lo único que no viaja
           por GitHub.</p>
        <span class="cuando">Último backup: <?php echo $hace($ultimo_arch); ?></span>
        <a class="btn" href="backups.php?archivos=1"><?php echo ui_icono('descargar', 16); ?> Descargar archivos</a>
      </div>
    </div>

    <p class="nota-git">El código del sitio ya tiene respaldo automático: cada cambio queda versionado en GitHub.
      Con estos dos archivos descargados cada tanto, podés reconstruir Printika Tools completo en minutos.</p>
<?php ui_panel_fin(); ?>
