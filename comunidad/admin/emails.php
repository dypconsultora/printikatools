<?php
/**
 * Administración: los emails que deja la gente en el popup del cotizador.
 *
 * En el Panel se ven los últimos doce, nada más. Acá está la lista completa,
 * con búsqueda, borrado de a uno o de a muchos, y el estado del correo de
 * bienvenida (el que les cuenta que existe la cuenta gratis).
 */
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/ui.php';
require_once __DIR__ . '/../inc/taller.php';

requerir_admin();
taller_migrar();
$yo = usuario_actual();
$db = com_db();

const EMAILS_POR_PAGINA = 50;

$aviso = '';
$error = '';

/** Los ids que llegaron tildados, ya limpios. */
function em_ids_marcados() {
    $ids = array_map('intval', (array) ($_POST['ids'] ?? []));
    return array_values(array_filter($ids, fn($n) => $n > 0));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!com_csrf_ok($_POST['csrf'] ?? '')) {
        $error = 'La sesión expiró, probá de nuevo.';
    } else {
        $accion = $_POST['accion'] ?? '';
        try {
            if ($accion === 'borrar_uno') {
                $id = (int) ($_POST['id'] ?? 0);
                $db->prepare('DELETE FROM novedades_emails WHERE id = ?')->execute([$id]);
                $aviso = 'Se borró la dirección.';

            } elseif ($accion === 'borrar_varios') {
                $ids = em_ids_marcados();
                if (!$ids) {
                    $error = 'No marcaste ninguna dirección.';
                } else {
                    // Los ids ya son enteros, así que armar los signos de pregunta
                    // uno por uno es seguro y evita una consulta por fila
                    $huecos = implode(',', array_fill(0, count($ids), '?'));
                    $stmt = $db->prepare("DELETE FROM novedades_emails WHERE id IN ($huecos)");
                    $stmt->execute($ids);
                    $n = $stmt->rowCount();
                    $aviso = $n === 1 ? 'Se borró 1 dirección.' : "Se borraron $n direcciones.";
                }

            } elseif ($accion === 'borrar_todo') {
                $n = (int) $db->query('SELECT COUNT(*) c FROM novedades_emails')->fetch()['c'];
                $db->exec('DELETE FROM novedades_emails');
                $aviso = "Se vació la lista: $n direcciones borradas.";

            } elseif ($accion === 'bienvenida') {
                require_once __DIR__ . '/../inc/correo.php';
                $ids = em_ids_marcados();
                if (!$ids) {
                    $error = 'No marcaste ninguna dirección.';
                } elseif (!correo_disponible()) {
                    $error = 'El envío de correos no está configurado (falta el .env con el SMTP).';
                } else {
                    $huecos = implode(',', array_fill(0, count($ids), '?'));
                    $filas = $db->prepare("SELECT id, email, idioma FROM novedades_emails WHERE id IN ($huecos)");
                    $filas->execute($ids);

                    $enviados = 0;
                    $fallados = 0;
                    foreach ($filas->fetchAll() as $f) {
                        // Cada uno en el idioma en el que se anoto, no siempre en castellano
                        if (correo_bienvenida_novedades($f['email'], $f['idioma'])) {
                            $db->prepare('UPDATE novedades_emails SET bienvenida_en = NOW() WHERE id = ?')
                               ->execute([$f['id']]);
                            $enviados++;
                        } else {
                            $fallados++;
                        }
                    }
                    $aviso = "Se envió la bienvenida a $enviados " . ($enviados === 1 ? 'dirección.' : 'direcciones.');
                    if ($fallados) $error = "A $fallados no se les pudo enviar.";
                }
            }
        } catch (Throwable $e) {
            $error = 'No se pudo completar la acción.';
        }
    }
}

// Export CSV (respeta la búsqueda activa)
$busca = trim((string) ($_GET['q'] ?? ''));
$where = '';
$args  = [];
if ($busca !== '') {
    $where = 'WHERE email LIKE ?';
    $args  = ['%' . $busca . '%'];
}

if (isset($_GET['csv'])) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="emails-novedades.csv"');
    echo "\xEF\xBB\xBF";                                   // para que Excel lea los acentos
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Email', 'Idioma', 'Fecha', 'Bienvenida'], ';', '"', '');
    $stmt = $db->prepare("SELECT email, idioma, creado_en, bienvenida_en FROM novedades_emails $where ORDER BY creado_en DESC");
    $stmt->execute($args);
    foreach ($stmt as $r) {
        fputcsv($out, [
            $r['email'],
            $r['idioma'] === 'en' ? 'Inglés' : 'Castellano',
            $r['creado_en'],
            $r['bienvenida_en'] ?: 'sin enviar',
        ], ';', '"', '');
    }
    fclose($out);
    exit;
}

$stmt = $db->prepare("SELECT COUNT(*) c FROM novedades_emails $where");
$stmt->execute($args);
$total = (int) $stmt->fetch()['c'];

$tot_general = (int) $db->query('SELECT COUNT(*) c FROM novedades_emails')->fetch()['c'];
$tot_sin     = (int) $db->query('SELECT COUNT(*) c FROM novedades_emails WHERE bienvenida_en IS NULL')->fetch()['c'];

$paginas = max(1, (int) ceil($total / EMAILS_POR_PAGINA));
$pagina  = min($paginas, max(1, (int) ($_GET['p'] ?? 1)));
$desde   = ($pagina - 1) * EMAILS_POR_PAGINA;

// El LIMIT va interpolado porque son enteros ya calculados: MySQL no acepta
// parámetros en LIMIT cuando las consultas no se emulan
$stmt = $db->prepare("SELECT id, email, idioma, creado_en, bienvenida_en FROM novedades_emails
                      $where ORDER BY creado_en DESC LIMIT $desde, " . EMAILS_POR_PAGINA);
$stmt->execute($args);
$lista = $stmt->fetchAll();

/** Arma un enlace de esta pantalla conservando la búsqueda. */
function em_url($pagina, $busca, $extra = '') {
    $q = [];
    if ($busca !== '') $q['q'] = $busca;
    if ($pagina > 1)   $q['p'] = $pagina;
    return 'emails.php' . ($q ? '?' . http_build_query($q) : '') . $extra;
}

$csv_url = 'emails.php?csv=1' . ($busca !== '' ? '&q=' . urlencode($busca) : '');

ui_panel_inicio('Emails captados', $yo, 'Emails captados', '../');
?>
    <style>.contenido{max-width:none}</style>
    <h1>Emails captados</h1>
    <p class="bajada">Las direcciones que deja la gente en el popup del cotizador. Cada una recibe
      un correo de bienvenida con los planes; acá podés ver quién lo recibió y limpiar la lista.</p>

    <?php if ($aviso): ?><div class="msg ok"><?php echo ui_icono('check', 16); ?><span><?php echo htmlspecialchars($aviso); ?></span></div><?php endif; ?>
    <?php if ($error): ?><div class="msg bad"><?php echo ui_icono('alerta', 16); ?><span><?php echo htmlspecialchars($error); ?></span></div><?php endif; ?>

    <style>
      .kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-bottom:16px}
      .kpi{background:var(--surface);border:1px solid var(--bd-suave);border-radius:var(--radio-g);padding:18px 20px}
      .kpi small{display:block;font-size:11px;font-weight:600;letter-spacing:.07em;text-transform:uppercase;
                 color:var(--txt-3);margin-bottom:6px}
      .kpi b{font-size:28px;font-weight:700;font-variant-numeric:tabular-nums}
      .barra{display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:12px}
      .barra form.buscar{display:flex;gap:8px;align-items:center;margin:0}
      .barra input[type=search]{height:38px;min-width:240px;padding:0 12px;border-radius:8px;
                                border:1px solid var(--bd);background:var(--surface);color:var(--txt)}
      .barra .der{margin-left:auto;display:flex;gap:8px;align-items:center;flex-wrap:wrap}
      .panel{background:var(--surface);border:1px solid var(--bd-suave);border-radius:var(--radio-g);overflow:hidden}
      table{width:100%;border-collapse:collapse;font-size:13.5px}
      th,td{padding:11px 16px;text-align:left;border-bottom:1px solid var(--bd-suave);vertical-align:middle}
      th{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.07em;
         color:var(--txt-3);background:var(--surface)}
      tr:last-child td{border-bottom:none}
      tbody tr:hover{background:var(--surface-2)}
      th.check,td.check{width:44px;padding-right:0}
      .check input[type=checkbox]{width:17px;height:17px;margin:0;cursor:pointer;
                                  accent-color:var(--accent)}
      td.fecha{color:var(--txt-2);font-variant-numeric:tabular-nums;white-space:nowrap}
      td.acc{width:60px;text-align:right}
      .estado{display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:500;
              padding:3px 10px;border-radius:99px;white-space:nowrap}
      .estado::before{content:'';width:6px;height:6px;border-radius:99px;background:currentColor}
      .estado.si{background:var(--ok-tinte);color:var(--ok)}
      .estado.no{background:var(--accent-tinte);color:var(--accent)}
      .idi{font-size:11px;font-weight:700;letter-spacing:.06em;color:var(--txt-3)}
      .acciones-lote{display:flex;gap:8px;align-items:center;flex-wrap:wrap;
                     padding:12px 16px;border-bottom:1px solid var(--bd-suave);background:var(--surface-2)}
      .acciones-lote .cuenta{font-size:13px;color:var(--txt-2)}
      .btn-icono{display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;
                 border:1px solid var(--bd-suave);border-radius:8px;background:transparent;
                 color:var(--txt-3);cursor:pointer;transition:all .15s ease}
      .btn-icono:hover{color:var(--bad);border-color:var(--bad)}
      .paginas{display:flex;gap:6px;align-items:center;justify-content:center;margin-top:14px;font-size:13px}
      .paginas a,.paginas span{padding:6px 11px;border-radius:8px;border:1px solid var(--bd-suave)}
      .paginas span.actual{background:var(--accent-tinte);color:var(--accent);border-color:transparent;font-weight:600}
      .vacio{padding:34px 16px;text-align:center;color:var(--txt-3);font-size:14px}
      @media (max-width:820px){ .tabla-scroll{overflow-x:auto} }
    </style>

    <div class="kpis">
      <div class="kpi"><small>Direcciones en la lista</small><b style="color:var(--accent)"><?php echo $tot_general; ?></b></div>
      <div class="kpi"><small>Sin correo de bienvenida</small><b><?php echo $tot_sin; ?></b></div>
    </div>

    <div class="barra">
      <form class="buscar" method="get" action="emails.php">
        <input type="search" name="q" placeholder="Buscar una dirección…"
               value="<?php echo htmlspecialchars($busca); ?>">
        <button class="btn sec" type="submit">Buscar</button>
        <?php if ($busca !== ''): ?><a class="btn sec" href="emails.php">Ver todas</a><?php endif; ?>
      </form>
      <div class="der">
        <?php if ($tot_general): ?>
          <a class="btn sec" href="<?php echo htmlspecialchars($csv_url); ?>">
            <?php echo ui_icono('descargar', 15); ?> Exportar CSV</a>
          <form method="post" style="margin:0"
                onsubmit="return confirm('Vas a borrar las <?php echo $tot_general; ?> direcciones de la lista. Esto no se puede deshacer. ¿Seguro?')">
            <input type="hidden" name="csrf" value="<?php echo com_csrf(); ?>">
            <input type="hidden" name="accion" value="borrar_todo">
            <button class="btn sec" type="submit"><?php echo ui_icono('basura', 15); ?> Vaciar la lista</button>
          </form>
        <?php endif; ?>
      </div>
    </div>

    <?php if (!$lista): ?>
      <div class="panel"><p class="vacio">
        <?php echo $busca !== ''
            ? 'Ninguna dirección coincide con «' . htmlspecialchars($busca) . '».'
            : 'Todavía nadie dejó su email en el popup del cotizador.'; ?>
      </p></div>
    <?php else: ?>

    <form method="post" id="lote">
      <input type="hidden" name="csrf" value="<?php echo com_csrf(); ?>">
      <div class="panel">
        <div class="acciones-lote">
          <span class="cuenta" id="cuenta">Ninguna seleccionada</span>
          <button class="btn sec" type="submit" name="accion" value="bienvenida"
                  onclick="return confirm('Se les va a mandar el correo de bienvenida con los planes. ¿Seguimos?')">
            Enviar bienvenida
          </button>
          <button class="btn sec" type="submit" name="accion" value="borrar_varios"
                  onclick="return confirm('Se borran las direcciones marcadas. ¿Seguro?')">
            <?php echo ui_icono('basura', 15); ?> Borrar seleccionadas
          </button>
        </div>

        <div class="tabla-scroll">
        <table>
          <thead><tr>
            <th class="check"><input type="checkbox" id="todos" title="Marcar todas"></th>
            <th>Email</th><th>Idioma</th><th>Bienvenida</th><th>Fecha</th><th class="acc"></th>
          </tr></thead>
          <tbody>
          <?php foreach ($lista as $r): ?>
            <tr>
              <td class="check"><input type="checkbox" class="uno" name="ids[]" value="<?php echo (int) $r['id']; ?>"></td>
              <td><?php echo htmlspecialchars($r['email']); ?></td>
              <td><span class="idi"><?php echo $r['idioma'] === 'en' ? 'ENG' : 'ESP'; ?></span></td>
              <td><?php if ($r['bienvenida_en']): ?>
                    <span class="estado si">Enviada <?php echo date('d/m/y', strtotime($r['bienvenida_en'])); ?></span>
                  <?php else: ?>
                    <span class="estado no">Sin enviar</span>
                  <?php endif; ?></td>
              <td class="fecha"><?php echo date('d/m/y H:i', strtotime($r['creado_en'])); ?></td>
              <td class="acc">
                <button class="btn-icono" type="submit" form="uno-<?php echo (int) $r['id']; ?>"
                        title="Borrar esta dirección"
                        onclick="return confirm('¿Borrar <?php echo htmlspecialchars($r['email'], ENT_QUOTES); ?>?')">
                  <?php echo ui_icono('basura', 15); ?>
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        </div>
      </div>
    </form>

    <?php // Un formulario suelto por fila: si fueran anidados el navegador los ignora ?>
    <?php foreach ($lista as $r): ?>
      <form method="post" id="uno-<?php echo (int) $r['id']; ?>" style="display:none">
        <input type="hidden" name="csrf" value="<?php echo com_csrf(); ?>">
        <input type="hidden" name="accion" value="borrar_uno">
        <input type="hidden" name="id" value="<?php echo (int) $r['id']; ?>">
      </form>
    <?php endforeach; ?>

    <?php if ($paginas > 1): ?>
      <div class="paginas">
        <?php if ($pagina > 1): ?><a href="<?php echo em_url($pagina - 1, $busca); ?>">Anterior</a><?php endif; ?>
        <span class="actual"><?php echo $pagina; ?> de <?php echo $paginas; ?></span>
        <?php if ($pagina < $paginas): ?><a href="<?php echo em_url($pagina + 1, $busca); ?>">Siguiente</a><?php endif; ?>
      </div>
    <?php endif; ?>

    <script>
      (function () {
        var todos  = document.getElementById('todos');
        var unos   = Array.prototype.slice.call(document.querySelectorAll('.uno'));
        var cuenta = document.getElementById('cuenta');

        function actualizar() {
          var n = unos.filter(function (c) { return c.checked; }).length;
          cuenta.textContent = n === 0 ? 'Ninguna seleccionada'
                             : n === 1 ? '1 seleccionada'
                                       : n + ' seleccionadas';
          todos.checked = n > 0 && n === unos.length;
          todos.indeterminate = n > 0 && n < unos.length;
        }
        todos.addEventListener('change', function () {
          unos.forEach(function (c) { c.checked = todos.checked; });
          actualizar();
        });
        unos.forEach(function (c) { c.addEventListener('change', actualizar); });
        actualizar();
      })();
    </script>

    <?php endif; ?>
<?php ui_panel_fin(); ?>
