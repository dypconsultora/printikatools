<?php
/**
 * Recursos: videos de YouTube en tres secciones, cargados por la
 * administración. Disponible para todos los usuarios logueados.
 */
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/ui.php';
require_once __DIR__ . '/inc/taller.php';

requerir_usuario();
$u = usuario_actual();
taller_migrar();
$db = com_db();

// Tres secciones, todas de videos de YouTube. Los PDF se retiraron.
$secciones = taller_secciones_recursos();
$tab = isset($secciones[$_GET['tab'] ?? '']) ? $_GET['tab'] : 'youtube';
$conTodo = acceso_total();

$stmt = $db->prepare('SELECT * FROM recursos_videos WHERE publicado=1 AND seccion=? ORDER BY creado_en DESC, id DESC');
$stmt->execute([$tab]);
$videos = $stmt->fetchAll();
$cuentas = [];
foreach ($db->query('SELECT seccion, COUNT(*) c FROM recursos_videos WHERE publicado=1 GROUP BY seccion') as $r) {
    $cuentas[$r['seccion']] = (int) $r['c'];
}

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

ui_panel_inicio('Recursos', $u, $secciones[$tab]);
?>
    <style>.contenido{max-width:none}</style>
    <h1>Recursos</h1>
    <p class="bajada">Videos para mejorar tus impresiones y tu negocio 3D.</p>

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
      <?php foreach ($secciones as $clave => $nombre): ?>
        <a href="recursos.php?tab=<?php echo $clave; ?>" class="<?php echo $tab === $clave ? 'activa' : ''; ?>">
          <?php echo ui_icono('video', 18); ?><?php echo htmlspecialchars($nombre); ?>
          <?php if (!empty($cuentas[$clave])): ?><span class="cant"><?php echo $cuentas[$clave]; ?></span><?php endif; ?>
        </a>
      <?php endforeach; ?>
    </div>

    <?php if (!$videos): ?>
      <div class="vacio">
        <div class="circ"><?php echo ui_icono('video', 26); ?></div>
        <h2>Todavía no hay videos cargados</h2>
        <p>Muy pronto vas a encontrar acá videos y tutoriales sobre impresión 3D.</p>
      </div>
    <?php else: ?>
      <div class="rec-grid">
        <?php foreach ($videos as $v): ?>
          <?php // Plataforma la ve cualquiera: son los videos de como usar el sistema
                $bloqueado = !taller_seccion_libre($tab) && $v['acceso'] === 'pago' && !$conTodo; ?>
          <div class="rec-c">
            <div class="rec-img">
              <?php if (!taller_seccion_libre($tab) && $v['acceso'] === 'pago'): ?><span class="badge-pago"><?php echo ui_icono('candado', 12); ?>Suscriptores</span><?php endif; ?>
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
<?php ui_panel_fin(); ?>
