<?php
/**
 * Recursos: PDFs para descargar y videos de YouTube, cargados por la
 * administración. Disponible para todos los usuarios logueados.
 */
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/ui.php';
require_once __DIR__ . '/inc/taller.php';

requerir_usuario();
$u = usuario_actual();
taller_migrar();
$db = com_db();

// Descarga de un PDF (cuenta la descarga)
if (preg_match('/^\d+$/', $_GET['descargar'] ?? '')) {
    $stmt = $db->prepare('SELECT * FROM recursos_pdf WHERE id=? AND publicado=1');
    $stmt->execute([(int) $_GET['descargar']]);
    $item = $stmt->fetch();
    if ($item && $item['acceso'] === 'pago' && !acceso_total()) {
        header('Location: suscripcion.php');
        exit;
    }
    $ruta = $item ? __DIR__ . '/uploads/recursos/pdf-' . $item['id'] . '.pdf' : '';
    if ($item && is_readable($ruta)) {
        $db->prepare('UPDATE recursos_pdf SET descargas = descargas + 1 WHERE id=?')->execute([(int) $item['id']]);
        $nombre = preg_replace('/[^\w\s\-\.]/u', '', $item['titulo']) ?: 'recurso';
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $nombre . '.pdf"');
        header('Content-Length: ' . filesize($ruta));
        readfile($ruta);
        exit;
    }
    header('Location: recursos.php?tab=pdf');
    exit;
}

$tab = ($_GET['tab'] ?? '') === 'videos' ? 'videos' : 'pdf';
$conTodo = acceso_total();

$pdfs = $db->query('SELECT * FROM recursos_pdf WHERE publicado=1 ORDER BY creado_en DESC, id DESC')->fetchAll();
$videos = $db->query('SELECT * FROM recursos_videos WHERE publicado=1 ORDER BY creado_en DESC, id DESC')->fetchAll();

function rec_tam($b) {
    if ($b >= 1048576) return number_format($b / 1048576, 1, ',', '.') . ' MB';
    return number_format(max(1, round($b / 1024)), 0, ',', '.') . ' KB';
}

/** Miniatura del video: imagen subida por el admin o la que genera YouTube. */
function rec_miniatura($v) {
    if ($v['imagen_ext'] !== '') {
        return 'uploads/recursos/vid-' . (int) $v['id'] . '.' . $v['imagen_ext'];
    }
    return 'https://img.youtube.com/vi/' . rawurlencode($v['youtube_id']) . '/hqdefault.jpg';
}

ui_panel_inicio('Recursos', $u, $tab === 'videos' ? 'Videos' : 'PDF');
?>
    <style>.contenido{max-width:none}</style>
    <h1>Recursos</h1>
    <p class="bajada">Guías en PDF y videos para mejorar tus impresiones y tu negocio 3D.</p>

    <style>
      .tabs{display:flex;gap:26px;border-bottom:1px solid var(--bd-suave);margin-bottom:18px}
      .tabs a{display:flex;align-items:center;gap:9px;padding:12px 2px 13px;font-size:14.5px;font-weight:600;
              color:var(--txt-2);border-bottom:2px solid transparent;margin-bottom:-1px}
      .tabs a:hover{color:var(--txt)}
      .tabs a.activa{color:var(--txt);border-bottom-color:var(--accent)}
      .tabs a .cant{font-size:12px;font-weight:600;color:var(--txt-3);background:var(--surface-2);
              border-radius:999px;padding:1px 8px}
      .rec-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px}
      @media (max-width:1100px){ .rec-grid{grid-template-columns:repeat(2,minmax(0,1fr))} }
      @media (max-width:700px){ .rec-grid{grid-template-columns:1fr} }
      .rec-c{background:var(--surface);border:1px solid var(--bd-suave);border-radius:var(--radio-g);overflow:hidden;
             display:flex;flex-direction:column}
      .rec-img{aspect-ratio:16/9;background:var(--surface-2);display:flex;align-items:center;justify-content:center;
             color:var(--txt-3);overflow:hidden;position:relative}
      .rec-img img{width:100%;height:100%;object-fit:cover}
      .rec-c .cuerpo{padding:14px 16px;display:flex;flex-direction:column;gap:5px;flex:1}
      .rec-c h2{font-size:14px;font-weight:600;line-height:1.35}
      .rec-c .desc{font-size:12.5px;color:var(--txt-2);line-height:1.5;flex:1}
      .rec-c .meta{font-size:12px;color:var(--txt-3)}
      .rec-c .btn{margin:0 16px 14px;justify-content:center}
      .play{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(5,8,14,.25);
            transition:background .15s ease;cursor:pointer;border:none;padding:0;width:100%}
      .play i{width:52px;height:52px;border-radius:50%;background:rgba(10,14,22,.78);color:#fff;
            display:flex;align-items:center;justify-content:center;font-style:normal;transition:transform .15s ease}
      .play:hover i{transform:scale(1.08)}
      .badge-pago{display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:600;
            padding:3px 9px;border-radius:999px;background:var(--warn-tinte);color:var(--warn);
            position:absolute;top:10px;right:10px;z-index:2}
      .btn-bloq{display:flex;align-items:center;justify-content:center;gap:8px;margin:0 16px 14px;
            padding:9px;border:1px dashed var(--bd);border-radius:var(--radio);
            font-size:13px;font-weight:600;color:var(--txt-3)}
      .btn-bloq:hover{color:var(--accent);border-color:var(--accent)}
      .vacio{border:1px dashed var(--bd);border-radius:var(--radio-g);padding:70px 24px;text-align:center}
      .vacio .circ{width:64px;height:64px;border-radius:50%;background:var(--surface-2);color:var(--txt-2);
              display:flex;align-items:center;justify-content:center;margin:0 auto 18px}
      .vacio h2{font-size:18px;font-weight:700;margin-bottom:8px}
      .vacio p{font-size:14px;color:var(--txt-2)}
      .velo-video{position:fixed;inset:0;background:rgba(5,8,14,.82);z-index:90;display:flex;
              align-items:center;justify-content:center;padding:24px}
      .velo-video[hidden]{display:none !important}
      .velo-video .marco{width:min(960px,100%);aspect-ratio:16/9;background:#000;border-radius:var(--radio-g);
              overflow:hidden;position:relative}
      .velo-video iframe{width:100%;height:100%;border:0;display:block}
      .velo-video .cerrar-v{position:absolute;top:-42px;right:0;background:none;border:none;color:#fff;
              cursor:pointer;display:flex;align-items:center;gap:6px;font-size:13px;font-family:inherit}
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
    <?php if (!$pdfs): ?>
      <div class="vacio">
        <div class="circ"><?php echo ui_icono('pdf', 26); ?></div>
        <h2>Todavía no hay PDFs cargados</h2>
        <p>Muy pronto vas a encontrar acá guías y material descargable.</p>
      </div>
    <?php else: ?>
      <div class="rec-grid">
        <?php foreach ($pdfs as $p): ?>
          <?php $bloqueado = $p['acceso'] === 'pago' && !$conTodo; ?>
          <div class="rec-c">
            <div class="rec-img">
              <?php if ($p['acceso'] === 'pago'): ?><span class="badge-pago"><?php echo ui_icono('candado', 12); ?>Suscriptores</span><?php endif; ?>
              <?php if ($p['imagen_ext']): ?>
                <img src="uploads/recursos/img-<?php echo (int) $p['id'] . '.' . htmlspecialchars($p['imagen_ext']); ?>"
                     alt="<?php echo htmlspecialchars($p['titulo']); ?>" loading="lazy">
              <?php else: ?><?php echo ui_icono('pdf', 34); ?><?php endif; ?>
            </div>
            <div class="cuerpo">
              <h2><?php echo htmlspecialchars($p['titulo']); ?></h2>
              <?php if ($p['descripcion']): ?><p class="desc"><?php echo htmlspecialchars($p['descripcion']); ?></p><?php endif; ?>
              <span class="meta">PDF · <?php echo rec_tam((int) $p['tam_bytes']); ?>
                · <?php echo (int) $p['descargas']; ?> descarga<?php echo (int) $p['descargas'] === 1 ? '' : 's'; ?></span>
            </div>
            <?php if ($bloqueado): ?>
              <a class="btn-bloq" href="suscripcion.php"><?php echo ui_icono('candado', 14); ?> Disponible en el plan completo</a>
            <?php else: ?>
              <a class="btn" href="recursos.php?descargar=<?php echo (int) $p['id']; ?>">
                <?php echo ui_icono('descargar', 15); ?> Descargar</a>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

<?php else: ?>
    <?php if (!$videos): ?>
      <div class="vacio">
        <div class="circ"><?php echo ui_icono('video', 26); ?></div>
        <h2>Todavía no hay videos cargados</h2>
        <p>Muy pronto vas a encontrar acá videos y tutoriales sobre impresión 3D.</p>
      </div>
    <?php else: ?>
      <div class="rec-grid">
        <?php foreach ($videos as $v): ?>
          <?php $bloqueado = $v['acceso'] === 'pago' && !$conTodo; ?>
          <div class="rec-c">
            <div class="rec-img">
              <?php if ($v['acceso'] === 'pago'): ?><span class="badge-pago"><?php echo ui_icono('candado', 12); ?>Suscriptores</span><?php endif; ?>
              <img src="<?php echo htmlspecialchars(rec_miniatura($v)); ?>"
                   alt="<?php echo htmlspecialchars($v['titulo']); ?>" loading="lazy">
              <?php if ($bloqueado): ?>
                <a class="play" href="suscripcion.php" aria-label="Disponible en el plan completo">
                  <i><?php echo ui_icono('candado', 20); ?></i>
                </a>
              <?php else: ?>
                <button class="play" type="button" data-video="<?php echo htmlspecialchars($v['youtube_id']); ?>"
                        aria-label="Ver <?php echo htmlspecialchars($v['titulo']); ?>">
                  <i><svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg></i>
                </button>
              <?php endif; ?>
            </div>
            <div class="cuerpo">
              <h2><?php echo htmlspecialchars($v['titulo']); ?></h2>
              <?php if ($v['descripcion']): ?><p class="desc"><?php echo htmlspecialchars($v['descripcion']); ?></p><?php endif; ?>
              <?php if ($bloqueado): ?><span class="meta"><?php echo ui_icono('candado', 12); ?> Disponible en el plan completo</span><?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="velo-video" id="veloVideo" hidden>
      <div class="marco">
        <button class="cerrar-v" type="button" id="cerrarVideo"><?php echo ui_icono('cerrar', 16); ?> Cerrar</button>
        <iframe id="playerVideo" src="about:blank" title="Video"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                allowfullscreen></iframe>
      </div>
    </div>

    <script>
    (function () {
      const velo = document.getElementById('veloVideo');
      const player = document.getElementById('playerVideo');
      const abrir = (id) => {
        player.src = 'https://www.youtube-nocookie.com/embed/' + encodeURIComponent(id) + '?autoplay=1&rel=0';
        velo.hidden = false;
      };
      const cerrar = () => { velo.hidden = true; player.src = 'about:blank'; };
      document.querySelectorAll('button.play').forEach(b => b.addEventListener('click', () => abrir(b.dataset.video)));
      document.getElementById('cerrarVideo').addEventListener('click', cerrar);
      velo.addEventListener('click', (e) => { if (e.target === velo) cerrar(); });
      document.addEventListener('keydown', (e) => { if (e.key === 'Escape') cerrar(); });
    })();
    </script>
<?php endif; ?>
<?php ui_panel_fin(); ?>
