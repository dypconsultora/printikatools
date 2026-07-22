<?php
/**
 * Librería STL: modelos listos para descargar. Disponible para todos los
 * usuarios logueados, incluido el plan gratuito.
 */
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/ui.php';
require_once __DIR__ . '/inc/taller.php';

requerir_usuario();
$u = usuario_actual();
taller_migrar();
$db = com_db();

// Descarga: sirve el archivo y cuenta la descarga
if (preg_match('/^\d+$/', $_GET['descargar'] ?? '')) {
    $stmt = $db->prepare('SELECT * FROM stl_items WHERE id=? AND publicado=1');
    $stmt->execute([(int) $_GET['descargar']]);
    $item = $stmt->fetch();
    $ruta = $item ? __DIR__ . '/uploads/stl/stl-' . $item['id'] . '.' . $item['archivo_ext'] : '';
    if ($item && is_readable($ruta)) {
        $db->prepare('UPDATE stl_items SET descargas = descargas + 1 WHERE id=?')->execute([(int) $item['id']]);
        $nombre = preg_replace('/[^\w\s\-\.]/u', '', $item['nombre']) ?: 'modelo';
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $nombre . '.' . $item['archivo_ext'] . '"');
        header('Content-Length: ' . filesize($ruta));
        readfile($ruta);
        exit;
    }
    header('Location: libreria.php');
    exit;
}

$cat = trim($_GET['cat'] ?? '');
$sql = 'SELECT * FROM stl_items WHERE publicado=1';
$par = [];
if ($cat !== '') { $sql .= ' AND categoria=?'; $par[] = $cat; }
$stmt = $db->prepare($sql . ' ORDER BY creado_en DESC, id DESC');
$stmt->execute($par);
$items = $stmt->fetchAll();

$cats = $db->query("SELECT DISTINCT categoria FROM stl_items WHERE publicado=1 AND categoria <> '' ORDER BY categoria")
           ->fetchAll(PDO::FETCH_COLUMN);

function stl_tam($b) {
    if ($b >= 1048576) return number_format($b / 1048576, 1, ',', '.') . ' MB';
    return number_format(max(1, round($b / 1024)), 0, ',', '.') . ' KB';
}

ui_panel_inicio('Librería STL', $u, 'Librería STL');
?>
    <style>.contenido{max-width:none}</style>
    <h1>Librería STL</h1>
    <p class="bajada">Modelos listos para imprimir, seleccionados por Printika Tools.</p>

    <style>
      .cats{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:18px}
      .cats a{font-size:13px;font-weight:600;padding:6px 14px;border-radius:999px;border:1px solid var(--bd);color:var(--txt-2)}
      .cats a.activa{background:var(--accent-tinte);border-color:var(--accent);color:var(--accent)}
      .stl-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:14px}
      .stl-c{background:var(--surface);border:1px solid var(--bd-suave);border-radius:var(--radio-g);overflow:hidden;
             display:flex;flex-direction:column}
      .stl-img{aspect-ratio:4/3;background:var(--surface-2);display:flex;align-items:center;justify-content:center;
             color:var(--txt-3);overflow:hidden}
      .stl-img img{width:100%;height:100%;object-fit:cover}
      .stl-c .cuerpo{padding:14px 16px;display:flex;flex-direction:column;gap:4px;flex:1}
      .stl-c h2{font-size:14px;font-weight:600}
      .stl-c .meta{font-size:12px;color:var(--txt-3)}
      .stl-c .btn{margin:0 16px 14px;justify-content:center}
      .vacio{border:1px dashed var(--bd);border-radius:var(--radio-g);padding:70px 24px;text-align:center}
      .vacio .circ{width:64px;height:64px;border-radius:50%;background:var(--surface-2);color:var(--txt-2);
              display:flex;align-items:center;justify-content:center;margin:0 auto 18px}
      .vacio h2{font-size:18px;font-weight:700;margin-bottom:8px}
      .vacio p{font-size:14px;color:var(--txt-2)}
    </style>

    <?php if ($cats): ?>
      <div class="cats">
        <a href="libreria.php" class="<?php echo $cat === '' ? 'activa' : ''; ?>">Todos</a>
        <?php foreach ($cats as $c): ?>
          <a href="libreria.php?cat=<?php echo urlencode($c); ?>" class="<?php echo $cat === $c ? 'activa' : ''; ?>">
            <?php echo htmlspecialchars($c); ?></a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if (!$items): ?>
      <div class="vacio">
        <div class="circ"><?php echo ui_icono('libreria', 26); ?></div>
        <h2>Estamos preparando la librería</h2>
        <p>Muy pronto vas a encontrar acá modelos STL listos para imprimir.</p>
      </div>
    <?php else: ?>
      <div class="stl-grid">
        <?php foreach ($items as $it): ?>
          <div class="stl-c">
            <div class="stl-img">
              <?php if ($it['imagen_ext']): ?>
                <img src="uploads/stl/img-<?php echo (int) $it['id'] . '.' . htmlspecialchars($it['imagen_ext']); ?>"
                     alt="<?php echo htmlspecialchars($it['nombre']); ?>" loading="lazy">
              <?php else: ?><?php echo ui_icono('libreria', 34); ?><?php endif; ?>
            </div>
            <div class="cuerpo">
              <h2><?php echo htmlspecialchars($it['nombre']); ?></h2>
              <span class="meta"><?php echo htmlspecialchars($it['categoria'] ?: 'General'); ?>
                · <?php echo stl_tam((int) $it['tam_bytes']); ?>
                · <?php echo (int) $it['descargas']; ?> descarga<?php echo (int) $it['descargas'] === 1 ? '' : 's'; ?></span>
            </div>
            <a class="btn" href="libreria.php?descargar=<?php echo (int) $it['id']; ?>">
              <?php echo ui_icono('descargar', 15); ?> Descargar</a>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
<?php ui_panel_fin(); ?>
