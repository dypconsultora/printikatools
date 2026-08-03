<?php
/**
 * Administración: mailings a la lista de Emails captados.
 *
 * El recorrido es siempre el mismo, y el orden importa:
 *   escribir → guardar borrador → mirar cómo queda → probarlo en la casilla
 *   propia → recién ahí elegir el grupo y mandar.
 *
 * El envío no se hace de una: se va pidiendo de a tandas desde el navegador
 * (ver inc/mailing.php). Por eso hay una respuesta JSON acá adentro.
 */
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/ui.php';
require_once __DIR__ . '/../inc/taller.php';
require_once __DIR__ . '/../inc/mailing.php';

requerir_admin();
taller_migrar();
$yo = usuario_actual();
$db = com_db();

$aviso = '';
$error = '';

/* ---- Vista previa: el correo tal cual, adentro de un marco ---- */
if (isset($_GET['vista'])) {
    $m = mailing_get((int) $_GET['vista']);
    if (!$m) { http_response_code(404); exit('No existe'); }
    header('Content-Type: text/html; charset=utf-8');
    header('X-Robots-Tag: noindex');
    // En el correo el logo va incrustado (cid:), que el navegador no sabe
    // resolver: en la vista previa se apunta al archivo de verdad para que no
    // aparezca una imagen rota y parezca que algo se rompió.
    echo str_replace(
        'src="cid:logoprintika"',
        'src="../../assets/img/printika-tools-mail.png"',
        mailing_html($m, $yo['email'], $m['idioma'] === 'en' ? 'en' : 'es')
    );
    exit;
}

/* ---- Una tanda del envío. Contesta JSON: lo llama el JavaScript ---- */
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['accion'] ?? '') === 'tanda') {
    header('Content-Type: application/json; charset=utf-8');
    if (!com_csrf_ok($_POST['csrf'] ?? '')) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'La sesión expiró. Recargá la página.']);
        exit;
    }
    $id = (int) ($_POST['id'] ?? 0);
    [$enviados, $fallados, $quedan] = mailing_tanda($id);
    $m = mailing_get($id);
    echo json_encode([
        'ok'       => true,
        'enviados' => (int) ($m['enviados'] ?? 0),
        'fallados' => (int) ($m['fallados'] ?? 0),
        'total'    => (int) ($m['total'] ?? 0),
        'quedan'   => $quedan,
        'estado'   => $m['estado'] ?? '',
        // Si una tanda entera falla, no tiene sentido seguir golpeando el SMTP
        'frenar'   => ($enviados === 0 && $fallados === 0 && $quedan > 0),
    ]);
    exit;
}

/* ---- Acciones normales ---- */
$editando = 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!com_csrf_ok($_POST['csrf'] ?? '')) {
        $error = 'La sesión expiró, probá de nuevo.';
    } else {
        $accion = $_POST['accion'] ?? '';
        $id     = (int) ($_POST['id'] ?? 0);
        try {
            if ($accion === 'guardar') {
                if (trim($_POST['asunto'] ?? '') === '') {
                    $error = 'Poné un asunto: es lo primero que se ve en la bandeja.';
                    $editando = $id;
                } elseif (trim($_POST['cuerpo'] ?? '') === '' && trim($_POST['html_propio'] ?? '') === '') {
                    $error = 'El correo está vacío.';
                    $editando = $id;
                } else {
                    $id = mailing_guardar($_POST, $id);
                    $aviso = 'Borrador guardado. Miralo abajo y mandate una prueba antes de enviarlo.';
                    header('Location: mailing.php?id=' . $id . '&ok=guardado');
                    exit;
                }

            } elseif ($accion === 'prueba' && $id) {
                $m = mailing_get($id);
                if (!$m) {
                    $error = 'No encontramos ese mailing.';
                } elseif (!correo_disponible()) {
                    $error = 'El envío de correos no está configurado (falta el .env con el SMTP).';
                } elseif (mailing_prueba($m, $yo['email'], $motivo)) {
                    $aviso = 'Te mandamos la prueba a ' . $yo['email'] . '. Miralá en el celular también.';
                } else {
                    $error = 'No pudimos mandar la prueba. Probá de nuevo en unos minutos.';
                }

            } elseif ($accion === 'enviar' && $id) {
                $m = mailing_get($id);
                if (!$m || $m['estado'] !== 'borrador') {
                    $error = 'Ese mailing ya se envió o se está enviando.';
                } elseif (!correo_disponible()) {
                    $error = 'El envío de correos no está configurado.';
                } else {
                    $n = mailing_encolar($id);
                    if ($n === 0) {
                        $error = 'No hay ninguna dirección en el grupo elegido.';
                    } else {
                        header('Location: mailing.php?id=' . $id . '&arrancar=1');
                        exit;
                    }
                }

            } elseif ($accion === 'borrar' && $id) {
                $m = mailing_get($id);
                if ($m && $m['estado'] === 'enviando') {
                    $error = 'Ese mailing se está enviando: esperá a que termine.';
                } else {
                    $db->prepare('DELETE FROM mailings WHERE id = ?')->execute([$id]);
                    $aviso = 'Mailing borrado.';
                }
            }
        } catch (Throwable $e) {
            error_log('[admin/mailing] ' . $e->getMessage());
            $error = 'No se pudo completar la acción.';
        }
    }
}

if (($_GET['ok'] ?? '') === 'guardado' && !$aviso) {
    $aviso = 'Borrador guardado. Miralo abajo y mandate una prueba antes de enviarlo.';
} elseif (($_GET['ok'] ?? '') === 'enviado' && !$aviso) {
    $ult = $db->query("SELECT enviados, total, fallados FROM mailings
                        WHERE estado='enviado' ORDER BY terminado_en DESC LIMIT 1")->fetch();
    $aviso = $ult
        ? 'Mailing enviado: ' . (int) $ult['enviados'] . ' de ' . (int) $ult['total'] . ' salieron'
          . ((int) $ult['fallados'] ? ', ' . (int) $ult['fallados'] . ' fallaron.' : '.')
        : 'Mailing enviado.';
}

$id_actual = (int) ($_GET['id'] ?? $editando);
$actual    = $id_actual ? mailing_get($id_actual) : null;
$arrancar  = $actual && $actual['estado'] === 'enviando';

// El formulario muestra lo que se estaba editando si hubo un error, y si no,
// el borrador abierto
$f = $error && $editando ? $_POST : ($actual && $actual['estado'] === 'borrador' ? $actual : []);

$historial = $db->query('SELECT * FROM mailings ORDER BY id DESC LIMIT 25')->fetchAll();
$total_lista = (int) $db->query('SELECT COUNT(*) FROM novedades_emails')->fetchColumn();

ui_panel_inicio('Mailing', $yo, 'Mailing', '../');
?>
    <style>.contenido{max-width:none}</style>
    <h1>Mailing</h1>
    <p class="bajada">Escribí una vez y sale para toda la lista de Emails captados, con el mismo
      diseño que los correos del sistema. Todos llevan el enlace de baja: quien lo toca sale de
      la lista y su cuenta no se toca.</p>

    <?php if ($aviso): ?><div class="msg ok"><?php echo ui_icono('check', 16); ?><span><?php echo htmlspecialchars($aviso); ?></span></div><?php endif; ?>
    <?php if ($error): ?><div class="msg bad"><?php echo ui_icono('alerta', 16); ?><span><?php echo htmlspecialchars($error); ?></span></div><?php endif; ?>
    <?php if (!correo_disponible()): ?>
      <div class="msg warn"><?php echo ui_icono('alerta', 16); ?><span>El envío de correos no está
        configurado en este servidor: podés escribir y guardar, pero no mandar.</span></div>
    <?php endif; ?>

    <style>
      .ml-grilla{display:grid;grid-template-columns:minmax(0,1fr) 420px;gap:20px;align-items:start}
      .caja{background:var(--surface);border:1px solid var(--bd-suave);border-radius:var(--radio-g);padding:20px}
      .caja h2{font-size:15px;font-weight:600;margin-bottom:4px}
      .caja .nota{font-size:13px;color:var(--txt-2);margin-bottom:10px}
      .caja textarea{width:100%;min-height:190px;padding:12px;border-radius:8px;border:1px solid var(--bd);
                     background:var(--surface-2);color:var(--txt);font:inherit;font-size:14px;line-height:1.6;
                     resize:vertical}
      .caja textarea.chico{min-height:110px;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:12.5px}
      .fila-2{display:grid;grid-template-columns:1fr 1fr;gap:10px}
      .ayuda{font-size:12px;color:var(--txt-3);margin-top:6px;line-height:1.6}
      .ayuda code{background:var(--surface-2);padding:1px 5px;border-radius:4px}
      .ml-grupo{display:flex;flex-direction:column;gap:8px;margin-top:8px}
      .ml-grupo label{display:flex;gap:9px;align-items:center;font-size:13.5px;color:var(--txt-2);
                   margin:0;cursor:pointer}
      .ml-grupo input[type=radio]{width:auto;height:auto;margin:0;accent-color:var(--accent)}
      .ml-grupo .ml-cant{margin-left:auto;font-size:12px;color:var(--txt-3);font-variant-numeric:tabular-nums}
      .pie-form{display:flex;justify-content:flex-end;gap:8px;margin-top:16px;flex-wrap:wrap}
      .vista{width:100%;height:520px;border:1px solid var(--bd-suave);border-radius:var(--radio);
             background:#fff}
      .barra-envio{margin-top:12px}
      .barra{height:10px;border-radius:99px;background:var(--surface-2);overflow:hidden}
      .barra i{display:block;height:100%;width:0;background:var(--accent);transition:width .3s ease}
      .barra-txt{font-size:13px;color:var(--txt-2);margin-top:8px}
      table.hist{width:100%;border-collapse:collapse;font-size:13.5px;margin-top:8px}
      table.hist th,table.hist td{padding:10px 8px;text-align:left;border-bottom:1px solid var(--bd-suave)}
      table.hist th{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.07em;color:var(--txt-3)}
      table.hist tr:last-child td{border-bottom:none}
      .chip{display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:500;
            padding:3px 10px;border-radius:99px;white-space:nowrap}
      .chip::before{content:'';width:6px;height:6px;border-radius:99px;background:currentColor}
      .chip.ok{background:var(--ok-tinte);color:var(--ok)}
      .chip.yendo{background:var(--accent-tinte);color:var(--accent)}
      .chip.bor{background:var(--surface-2);color:var(--txt-3)}
      .aviso-smtp{font-size:12.5px;color:var(--txt-3);line-height:1.6;margin-top:12px;
                  border-top:1px solid var(--bd-suave);padding-top:12px}
      @media (max-width:1100px){ .ml-grilla{grid-template-columns:1fr} }
    </style>

    <div class="ml-grilla">
      <div class="caja">
        <h2><?php echo $f && !empty($f['id']) ? 'Editar el borrador' : 'Escribir un mailing'; ?></h2>
        <p class="nota">El diseño (logo, colores, botón) lo pone el sistema: acá va solo el contenido.</p>
        <form method="post">
          <input type="hidden" name="csrf" value="<?php echo com_csrf(); ?>">
          <input type="hidden" name="accion" value="guardar">
          <input type="hidden" name="id" value="<?php echo (int) ($f['id'] ?? 0); ?>">

          <label for="asunto">Asunto *</label>
          <input id="asunto" type="text" name="asunto" maxlength="200" required
                 placeholder="Ya podés llevar el stock de tu taller"
                 value="<?php echo htmlspecialchars($f['asunto'] ?? ''); ?>">

          <label for="titulo">Título adentro del correo</label>
          <input id="titulo" type="text" name="titulo" maxlength="200"
                 placeholder="Si lo dejás vacío, se usa el asunto"
                 value="<?php echo htmlspecialchars($f['titulo'] ?? ''); ?>">

          <label for="cuerpo">El texto</label>
          <textarea id="cuerpo" name="cuerpo" placeholder="Un renglón en blanco separa párrafos."><?php
            echo htmlspecialchars($f['cuerpo'] ?? ''); ?></textarea>
          <p class="ayuda">Un renglón en blanco separa párrafos. Para resaltar:
            <code>**en negrita**</code>. Para un enlace:
            <code>[lo que se lee](https://printikatools.com/…)</code>.</p>

          <div class="fila-2" style="margin-top:14px">
            <span><label for="bt">Texto del botón</label>
              <input id="bt" type="text" name="boton_texto" maxlength="80" placeholder="Ver la novedad"
                     value="<?php echo htmlspecialchars($f['boton_texto'] ?? ''); ?>"></span>
            <span><label for="bu">A dónde lleva</label>
              <input id="bu" type="url" name="boton_url" maxlength="300" placeholder="https://printikatools.com/"
                     value="<?php echo htmlspecialchars($f['boton_url'] ?? ''); ?>"></span>
          </div>
          <p class="ayuda">Si dejás los dos vacíos, el correo sale sin botón.</p>

          <details style="margin-top:16px">
            <summary style="cursor:pointer;font-size:13.5px;color:var(--txt-2)">Pegar un HTML propio</summary>
            <p class="ayuda">Para un correo armado aparte. Si ponés algo acá, reemplaza todo lo de
              arriba y el diseño corre por tu cuenta; el pie con el enlace de baja se agrega igual.</p>
            <textarea class="chico" name="html_propio" placeholder="&lt;table&gt;…"><?php
              echo htmlspecialchars($f['html_propio'] ?? ''); ?></textarea>
          </details>

          <h2 style="margin-top:20px">¿A quiénes?</h2>
          <div class="ml-grupo">
            <?php foreach (mailing_filtros() as $clave => $nombre): ?>
              <label>
                <input type="radio" name="filtro" value="<?php echo $clave; ?>"
                       <?php echo ($f['filtro'] ?? 'todos') === $clave ? 'checked' : ''; ?>>
                <?php echo htmlspecialchars($nombre); ?>
                <span class="ml-cant"><?php echo mailing_contar($clave, 'ambos'); ?></span>
              </label>
            <?php endforeach; ?>
          </div>
          <label for="idi" style="margin-top:14px">Idioma</label>
          <select id="idi" name="idioma">
            <?php foreach (['ambos' => 'Los dos idiomas', 'es' => 'Solo castellano', 'en' => 'Solo inglés'] as $k => $v): ?>
              <option value="<?php echo $k; ?>" <?php echo ($f['idioma'] ?? 'ambos') === $k ? 'selected' : ''; ?>>
                <?php echo $v; ?> (<?php echo mailing_contar($f['filtro'] ?? 'todos', $k); ?>)
              </option>
            <?php endforeach; ?>
          </select>

          <div class="pie-form"><button class="btn" type="submit">Guardar borrador</button></div>
        </form>
      </div>

      <div>
        <?php if ($actual): ?>
        <div class="caja">
          <h2>Cómo queda</h2>
          <p class="nota">Es el correo de verdad, con tu enlace de baja.</p>
          <iframe class="vista" src="mailing.php?vista=<?php echo (int) $actual['id']; ?>"
                  title="Vista previa del correo"></iframe>

          <?php if ($actual['estado'] === 'borrador'): ?>
            <div class="pie-form">
              <form method="post" style="margin:0">
                <input type="hidden" name="csrf" value="<?php echo com_csrf(); ?>">
                <input type="hidden" name="accion" value="prueba">
                <input type="hidden" name="id" value="<?php echo (int) $actual['id']; ?>">
                <button class="btn sec" type="submit">Enviarme una prueba</button>
              </form>
              <?php $cuantos = mailing_contar($actual['filtro'], $actual['idioma']); ?>
              <form method="post" style="margin:0"
                    onsubmit="return confirm('Se va a enviar a <?php echo $cuantos; ?> direcciones. ¿Le mandaste una prueba a tu casilla y la miraste?')">
                <input type="hidden" name="csrf" value="<?php echo com_csrf(); ?>">
                <input type="hidden" name="accion" value="enviar">
                <input type="hidden" name="id" value="<?php echo (int) $actual['id']; ?>">
                <button class="btn" type="submit" <?php echo $cuantos ? '' : 'disabled'; ?>>
                  Enviar a <?php echo $cuantos; ?> <?php echo $cuantos === 1 ? 'dirección' : 'direcciones'; ?>
                </button>
              </form>
            </div>
          <?php endif; ?>

          <?php if ($actual['estado'] !== 'borrador'): ?>
            <div class="barra-envio" id="envio" data-id="<?php echo (int) $actual['id']; ?>">
              <div class="barra"><i id="barraLlena"></i></div>
              <p class="barra-txt" id="barraTxt">
                <?php echo (int) $actual['enviados']; ?> de <?php echo (int) $actual['total']; ?> enviados
              </p>
            </div>
          <?php endif; ?>

          <p class="aviso-smtp"><?php echo ui_icono('alerta', 13); ?>
            Los correos salen desde el servidor del hosting, de a
            <?php echo MAILING_POR_TANDA; ?> por vez. Con la lista chica alcanza; cuando crezca hay
            que pasar a un servicio de envío, o el hosting empieza a rebotar <em>todo</em>, incluidos
            los códigos de acceso de las cuentas.</p>
        </div>
        <?php else: ?>
        <div class="caja">
          <h2>Antes de mandar</h2>
          <p class="nota" style="margin-bottom:0">Guardá el borrador y acá vas a ver cómo queda el
            correo de verdad, con un botón para mandártelo a tu casilla. <strong>Miralo siempre en
            el celular antes de enviarlo a la lista</strong>: es donde lo va a abrir casi todo el
            mundo. Hoy hay <?php echo $total_lista; ?>
            <?php echo $total_lista === 1 ? 'dirección' : 'direcciones'; ?> en la lista.</p>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($historial): ?>
    <div class="caja" style="margin-top:20px">
      <h2>Lo que mandaste</h2>
      <table class="hist">
        <thead><tr><th>Asunto</th><th>Estado</th><th>Llegó a</th><th>Fecha</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($historial as $h): ?>
          <tr>
            <td><a href="mailing.php?id=<?php echo (int) $h['id']; ?>"><?php echo htmlspecialchars($h['asunto']); ?></a></td>
            <td>
              <?php if ($h['estado'] === 'enviado'): ?><span class="chip ok">Enviado</span>
              <?php elseif ($h['estado'] === 'enviando'): ?><span class="chip yendo">Enviando</span>
              <?php else: ?><span class="chip bor">Borrador</span><?php endif; ?>
            </td>
            <td><?php echo $h['estado'] === 'borrador' ? '—'
                  : (int) $h['enviados'] . ' de ' . (int) $h['total']
                    . ((int) $h['fallados'] ? ' · ' . (int) $h['fallados'] . ' fallaron' : ''); ?></td>
            <td style="color:var(--txt-2);white-space:nowrap"><?php echo date('d/m/y H:i', strtotime($h['creado_en'])); ?></td>
            <td style="text-align:right">
              <?php if ($h['estado'] !== 'enviando'): ?>
              <form method="post" style="margin:0;display:inline"
                    onsubmit="return confirm('¿Borrar «<?php echo htmlspecialchars($h['asunto'], ENT_QUOTES); ?>» del historial?')">
                <input type="hidden" name="csrf" value="<?php echo com_csrf(); ?>">
                <input type="hidden" name="accion" value="borrar">
                <input type="hidden" name="id" value="<?php echo (int) $h['id']; ?>">
                <button class="btn chico peligro" type="submit"><?php echo ui_icono('basura', 14); ?></button>
              </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>

    <?php if ($arrancar): ?>
    <script>
    /*
     * El envio se va pidiendo de a tandas desde acá.
     *
     * Es asi porque el hosting corta los procesos largos: mandar 200 correos en
     * un solo pedido no termina nunca. Cada tanda que vuelve actualiza la barra.
     * Si se cierra la pestaña, lo que quedo pendiente sigue anotado y el envio se
     * retoma entrando de nuevo a este mailing.
     */
    (function () {
      var caja  = document.getElementById('envio');
      var llena = document.getElementById('barraLlena');
      var txt   = document.getElementById('barraTxt');
      if (!caja) return;
      var id = caja.getAttribute('data-id');
      var csrf = '<?php echo com_csrf(); ?>';

      function pintar(d) {
        var pct = d.total ? Math.round((d.enviados + d.fallados) * 100 / d.total) : 100;
        llena.style.width = pct + '%';
        txt.textContent = d.enviados + ' de ' + d.total + ' enviados'
          + (d.fallados ? ' · ' + d.fallados + ' fallaron' : '')
          + (d.quedan ? ' · faltan ' + d.quedan : '');
      }

      function tanda() {
        var cuerpo = new FormData();
        cuerpo.append('csrf', csrf);
        cuerpo.append('accion', 'tanda');
        cuerpo.append('id', id);
        fetch('mailing.php', { method: 'POST', body: cuerpo })
          .then(function (r) { return r.json(); })
          .then(function (d) {
            if (!d.ok) { txt.textContent = d.error || 'No se pudo seguir enviando.'; return; }
            pintar(d);
            if (d.frenar) {
              txt.textContent += ' — el servidor de correo no está respondiendo. '
                + 'Volvé a entrar más tarde y sigue desde donde quedó.';
              return;
            }
            if (d.quedan > 0) {
              setTimeout(tanda, 1200);   // un respiro entre tandas
            } else {
              txt.textContent = 'Listo: ' + d.enviados + ' de ' + d.total + ' enviados'
                + (d.fallados ? ' · ' + d.fallados + ' fallaron' : '') + '.';
              llena.style.width = '100%';
              // La tabla de abajo se dibujo antes de que salieran las tandas y
              // quedo diciendo "Enviando": se recarga para que diga la verdad
              setTimeout(function () { location.href = 'mailing.php?ok=enviado'; }, 1500);
            }
          })
          .catch(function () { txt.textContent = 'Se cortó la conexión. Recargá para seguir.'; });
      }
      tanda();
    })();
    </script>
    <?php endif; ?>
<?php ui_panel_fin(); ?>
