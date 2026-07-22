<?php
/**
 * Editor de presupuesto (nuevo o existente).
 * Izquierda: cliente + piezas + totales. Derecha: la calculadora del taller
 * (misma lógica que la Calculadora de costos) para crear piezas nuevas,
 * con opción de guardarlas también como producto del catálogo.
 */
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/ui.php';
require_once __DIR__ . '/inc/taller.php';

requerir_miembro();
$u = usuario_actual();
taller_migrar();
$uid = (int) $u['id'];

$error = '';
$presupuesto = null;
$items = [];

$pid = (int) ($_GET['id'] ?? 0);
if ($pid) {
    $stmt = com_db()->prepare('SELECT * FROM presupuestos WHERE id=? AND usuario_id=?');
    $stmt->execute([$pid, $uid]);
    $presupuesto = $stmt->fetch() ?: null;
    if (!$presupuesto) { header('Location: presupuestos.php'); exit; }
    $stmt = com_db()->prepare('SELECT * FROM presupuesto_items WHERE presupuesto_id=? ORDER BY id');
    $stmt->execute([$pid]);
    $items = $stmt->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'moneda') {
    if (com_csrf_ok($_POST['csrf'] ?? '')) {
        taller_guardar_moneda($uid, $_POST['moneda'] ?? '');
    }
    header('Location: presupuesto.php' . ($pid ? '?id=' . $pid : ''));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!com_csrf_ok($_POST['csrf'] ?? '')) {
        $error = 'La sesión expiró, probá de nuevo.';
    } else {
        $datos = json_decode($_POST['datos'] ?? '', true);
        if (!is_array($datos)) {
            $error = 'No se pudieron leer los datos del presupuesto.';
        } else {
            $cliente = mb_substr(trim($datos['cliente'] ?? ''), 0, 150);
            $notas   = mb_substr(trim($datos['notas'] ?? ''), 0, 2000);
            $dtipo   = ($datos['descuento_tipo'] ?? '') === 'porcentaje' ? 'porcentaje' : 'monto';
            $dvalor  = max(0, (float) ($datos['descuento_valor'] ?? 0));
            $estado  = ($datos['estado'] ?? '') === 'vendido' ? 'vendido' : 'pendiente';
            $items_in = is_array($datos['items'] ?? null) ? array_slice($datos['items'], 0, 200) : [];

            if ($cliente === '') {
                $error = 'Completá el nombre del cliente para guardar.';
            } elseif (!$items_in) {
                $error = 'Agregá al menos una pieza al presupuesto.';
            } else {
                $db = com_db();
                $db->beginTransaction();
                try {
                    $cliente_id = taller_cliente_id($uid, $cliente);
                    if ($presupuesto) {
                        $db->prepare('UPDATE presupuestos SET cliente_id=?, cliente_nombre=?, estado=?, descuento_tipo=?,
                                      descuento_valor=?, notas=?, actualizado_en=NOW() WHERE id=? AND usuario_id=?')
                           ->execute([$cliente_id, $cliente, $estado, $dtipo, $dvalor, $notas, $pid, $uid]);
                        $db->prepare('DELETE FROM presupuesto_items WHERE presupuesto_id=?')->execute([$pid]);
                    } else {
                        $db->prepare('INSERT INTO presupuestos (usuario_id, cliente_id, cliente_nombre, estado, descuento_tipo,
                                      descuento_valor, notas, creado_en, actualizado_en) VALUES (?,?,?,?,?,?,?,NOW(),NOW())')
                           ->execute([$uid, $cliente_id, $cliente, $estado, $dtipo, $dvalor, $notas]);
                        $pid = (int) $db->lastInsertId();
                    }
                    $ins = $db->prepare('INSERT INTO presupuesto_items (presupuesto_id, producto_id, nombre, descripcion,
                                         cantidad, precio_unit, costo_unit, datos_json) VALUES (?,?,?,?,?,?,?,?)');
                    $nuevo_prod = $db->prepare('INSERT INTO productos (usuario_id, nombre, descripcion, costo, precio,
                                                datos_json, creado_en, actualizado_en) VALUES (?,?,?,?,?,?,NOW(),NOW())');
                    foreach ($items_in as $it) {
                        $nombre = mb_substr(trim($it['nombre'] ?? ''), 0, 150);
                        if ($nombre === '') continue;
                        $descripcion = mb_substr(trim($it['descripcion'] ?? ''), 0, 500);
                        $cantidad = max(1, min(9999, (int) ($it['cantidad'] ?? 1)));
                        $precio = max(0, (float) ($it['precio'] ?? 0));
                        $costo  = max(0, (float) ($it['costo'] ?? 0));
                        $djson  = isset($it['datos']) && is_array($it['datos']) ? json_encode($it['datos']) : null;
                        $producto_id = (int) ($it['producto_id'] ?? 0) ?: null;
                        if (!empty($it['guardar_producto']) && !$producto_id) {
                            $nuevo_prod->execute([$uid, $nombre, $descripcion, $costo, $precio, $djson]);
                            $producto_id = (int) $db->lastInsertId();
                        }
                        $ins->execute([$pid, $producto_id, $nombre, $descripcion, $cantidad, $precio, $costo, $djson]);
                    }
                    taller_cambiar_estado($uid, $pid, $estado);
                    $db->commit();
                    header('Location: presupuesto.php?id=' . $pid . '&ok=1');
                    exit;
                } catch (Throwable $e) {
                    $db->rollBack();
                    $error = 'No se pudo guardar el presupuesto.';
                }
            }
        }
    }
}

// Datos para la vista
$stmt = com_db()->prepare('SELECT nombre, telefono, empresa FROM clientes WHERE usuario_id=? ORDER BY nombre LIMIT 500');
$stmt->execute([$uid]);
$clientes = $stmt->fetchAll();
$clientes_js = json_encode(array_column($clientes, 'telefono', 'nombre'), JSON_UNESCAPED_UNICODE);

$stmt = com_db()->prepare('SELECT id, nombre, descripcion, costo, precio FROM productos WHERE usuario_id=? ORDER BY nombre LIMIT 500');
$stmt->execute([$uid]);
$productos = $stmt->fetchAll();

$estado_json = json_encode([
    'cliente' => $presupuesto['cliente_nombre'] ?? mb_substr(trim($_GET['cliente'] ?? ''), 0, 150),
    'notas'   => $presupuesto['notas'] ?? '',
    'estado'  => $presupuesto['estado'] ?? 'pendiente',
    'descuento_tipo'  => $presupuesto['descuento_tipo'] ?? 'monto',
    'descuento_valor' => (float) ($presupuesto['descuento_valor'] ?? 0),
    'items'   => array_map(fn($i) => [
        'producto_id' => $i['producto_id'] ? (int) $i['producto_id'] : null,
        'nombre' => $i['nombre'], 'descripcion' => $i['descripcion'] ?? '',
        'cantidad' => (int) $i['cantidad'], 'precio' => (float) $i['precio_unit'],
        'costo' => (float) $i['costo_unit'],
        'datos' => $i['datos_json'] ? json_decode($i['datos_json'], true) : null,
    ], $items),
], JSON_UNESCAPED_UNICODE);

$materiales = ['PLA','ABS','PETG','TPU','Nylon','Resina','ASA','PC','HIPS','PVA','CF-Nylon','Wood-PLA','Flex'];
$moneda = taller_moneda_usuario() ?: 'ARS';
[$moneda_simbolo, $moneda_dec] = taller_monedas()[$moneda];

ui_panel_inicio($presupuesto ? 'Editar presupuesto' : 'Nuevo presupuesto', $u, 'Presupuestos');
?>
    <style>.contenido{max-width:none}</style>
    <p style="margin-bottom:14px"><a href="presupuestos.php">&larr; Presupuestos</a></p>
    <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap">
      <h1><?php echo $presupuesto ? 'Presupuesto' : 'Nuevo presupuesto'; ?></h1>
      <?php if ($presupuesto): ?>
        <span class="estado-chip <?php echo $presupuesto['estado']; ?>" id="estadoChip">
          <?php echo $presupuesto['estado'] === 'vendido' ? 'Vendido' : 'Pendiente'; ?></span>
      <?php endif; ?>
      <?php taller_chip_moneda(); ?>
    </div>
    <?php taller_popup_moneda(); ?>
    <p class="bajada">Completá los datos del cliente y agregá las piezas.</p>

    <?php if (isset($_GET['ok'])): ?><div class="msg ok"><?php echo ui_icono('check', 16); ?><span>Presupuesto guardado.</span></div><?php endif; ?>
    <?php if ($error): ?><div class="msg bad"><?php echo ui_icono('alerta', 16); ?><span><?php echo htmlspecialchars($error); ?></span></div><?php endif; ?>

    <style>
      .dos-col{display:grid;grid-template-columns:minmax(0,1fr) 400px;gap:20px;align-items:start}
      .tarjeta-s{background:var(--surface);border:1px solid var(--bd-suave);border-radius:var(--radio-g);padding:20px}
      .tarjeta-s h2{font-size:14.5px;font-weight:600;margin-bottom:12px}
      .estado-chip{display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:600;
              padding:4px 12px;border-radius:99px}
      .estado-chip::before{content:'';width:6px;height:6px;border-radius:99px;background:currentColor}
      .estado-chip.vendido{background:var(--ok-tinte);color:var(--ok)}
      .estado-chip.pendiente{background:var(--warn-tinte);color:var(--warn)}
      .fila-2{display:grid;grid-template-columns:1fr 1fr;gap:10px}
      #tablaItems{width:100%;border-collapse:collapse;font-size:13.5px}
      #tablaItems th,#tablaItems td{padding:10px 10px;border-bottom:1px solid var(--bd-suave);text-align:left;vertical-align:middle}
      #tablaItems th{font-size:11px;text-transform:uppercase;letter-spacing:.07em;color:var(--txt-3)}
      #tablaItems td.num{text-align:right;font-variant-numeric:tabular-nums;white-space:nowrap}
      #tablaItems th.num{text-align:right}
      #tablaItems input[type=number]{height:32px;padding:0 8px;font-size:13px;border-radius:6px}
      #tablaItems .cant{width:64px}
      #tablaItems .precio-in{width:110px;text-align:right}
      #tablaItems .quitar{background:none;border:none;color:var(--txt-3);cursor:pointer;padding:6px;border-radius:6px}
      #tablaItems .quitar:hover{color:var(--bad);background:var(--bad-tinte)}
      .vacio-items{border:1px dashed var(--bd);border-radius:var(--radio-g);padding:34px 16px;text-align:center;
              color:var(--txt-2);font-size:13.5px;margin-bottom:12px}
      .elegir-prod{position:relative;margin:12px 0}
      .elegir-prod > button{width:100%}
      .lista-prod{position:absolute;z-index:5;top:calc(100% + 6px);left:0;right:0;background:var(--surface);
              border:1px solid var(--bd);border-radius:var(--radio-g);max-height:280px;overflow:auto;
              box-shadow:0 16px 50px -12px rgba(0,0,0,.5);display:none}
      .lista-prod.abierta{display:block}
      .lista-prod .item-p{display:flex;justify-content:space-between;gap:10px;width:100%;background:none;border:none;
              padding:11px 14px;color:var(--txt);font-size:13.5px;cursor:pointer;text-align:left;
              border-bottom:1px solid var(--bd-suave)}
      .lista-prod .item-p:last-child{border-bottom:none}
      .lista-prod .item-p:hover{background:var(--surface-2)}
      .lista-prod .sin{padding:14px;color:var(--txt-2);font-size:13px;text-align:center}
      .totales{margin-top:14px;border-top:1px solid var(--bd-suave);padding-top:14px;display:flex;
              flex-direction:column;gap:10px;font-size:14px}
      .totales .linea{display:flex;align-items:center;justify-content:space-between;gap:10px}
      .totales .grande{font-size:18px;font-weight:700}
      .desc-tipo{display:inline-flex;border:1px solid var(--bd);border-radius:8px;overflow:hidden}
      .desc-tipo button{background:none;border:none;color:var(--txt-3);padding:6px 12px;cursor:pointer;font-size:13px;font-weight:600}
      .desc-tipo button.on{background:var(--accent);color:var(--accent-ink)}
      #descValor{width:110px;height:34px;text-align:right}
      .acciones-pie{display:flex;gap:10px;justify-content:flex-end;align-items:center;margin-top:16px;flex-wrap:wrap}
      .nota-pie{margin-right:auto;font-size:13px;color:var(--txt-3)}
      /* Calculadora */
      .calc{position:sticky;top:16px}
      .calc h2{display:flex;align-items:center;gap:8px}
      .calc label{margin-top:10px}
      .calc details{border:1px solid var(--bd-suave);border-radius:var(--radio);padding:0 12px;margin-top:12px}
      .calc details summary{cursor:pointer;list-style:none;font-size:12.5px;font-weight:600;color:var(--txt-2);
              padding:10px 0;display:flex;justify-content:space-between;align-items:center}
      .calc details summary::-webkit-details-marker{display:none}
      .calc details summary::after{content:'+';color:var(--txt-3)}
      .calc details[open] summary::after{content:'–'}
      .calc details .inner{padding-bottom:12px}
      .desglose{background:var(--surface-2);border:1px solid var(--bd-suave);border-radius:var(--radio);
              padding:12px 14px;margin-top:14px;font-size:12.5px;color:var(--txt-2);display:flex;
              flex-direction:column;gap:6px}
      .desglose .linea{display:flex;justify-content:space-between;font-variant-numeric:tabular-nums}
      .desglose .linea.total{color:var(--txt);font-weight:600;border-top:1px solid var(--bd);padding-top:8px;margin-top:4px}
      .desglose .linea.precio{color:var(--accent);font-weight:700;font-size:14px}
      .check-linea{display:flex;align-items:center;gap:8px;margin-top:12px;font-size:13px;color:var(--txt-2)}
      .check-linea input{width:auto;height:auto}
      @media (max-width:1080px){ .dos-col{grid-template-columns:1fr} .calc{position:static} }

      /* ============================================================
         EXPORTAR PDF — mismo formato que el exportador del cotizador
         ============================================================ */
      #printDoc { display: none; }
      @media print {
        @page { margin: 0; }
        body { background: #fff !important; }
        body > *:not(#printDoc) { display: none !important; }
        #printDoc {
          display: block;
          padding: 16mm 15mm;
          font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
          color: #17202e;
          -webkit-print-color-adjust: exact;
          print-color-adjust: exact;
        }
        .pd-top { display: flex; justify-content: space-between; align-items: flex-end;
          padding-bottom: 18px; border-bottom: 2px solid #17202e; }
        .pd-logo { height: 60px; width: auto; }
        .pd-meta { text-align: right; }
        .pd-kicker { font-size: 11px; font-weight: 700; letter-spacing: 0.24em;
          text-transform: uppercase; color: #0b78b5; }
        .pd-project { font-size: 25px; font-weight: 800; letter-spacing: -0.02em;
          color: #101b29; margin-top: 4px; }
        .pd-date { font-size: 12px; color: #64748c; margin-top: 5px; }
        .pd-specs { display: flex; gap: 36px; margin: 20px 0 4px; }
        .pd-spec { font-size: 11px; color: #64748c; text-transform: uppercase; letter-spacing: 0.06em; }
        .pd-spec strong { display: block; font-size: 15px; font-weight: 700; color: #101b29;
          margin-top: 3px; text-transform: none; letter-spacing: 0; font-variant-numeric: tabular-nums; }
        .pd-rows { margin-top: 22px; }
        .pd-row { display: flex; justify-content: space-between; padding: 10px 2px;
          border-bottom: 1px solid #e5eaf1; font-size: 13.5px; }
        .pd-row .l { color: #44536b; }
        .pd-row .l small { color: #8a97ab; font-size: 12px; }
        .pd-row .v { font-weight: 600; color: #101b29; font-variant-numeric: tabular-nums; }
        .pd-row.sub { border-top: 2px solid #17202e; border-bottom: none; padding-top: 13px; margin-top: 2px; }
        .pd-row.sub .l, .pd-row.sub .v { font-weight: 700; color: #101b29; }
        .pd-total { display: flex; justify-content: space-between; align-items: center;
          margin-top: 16px; padding: 15px 20px; background: #eaf4fb; border: 1px solid #bdd9ec;
          border-radius: 10px; }
        .pd-total .l { font-size: 11px; font-weight: 700; letter-spacing: 0.2em;
          text-transform: uppercase; color: #0b6ca8; }
        .pd-total .v { font-size: 30px; font-weight: 800; letter-spacing: -0.02em;
          color: #0b6ca8; font-variant-numeric: tabular-nums; }
        .pd-notas { margin-top: 22px; font-size: 12.5px; color: #44536b; }
        .pd-notas .pd-h { font-size: 11px; font-weight: 700; letter-spacing: 0.2em;
          text-transform: uppercase; color: #64748c; padding-bottom: 6px; }
      }
    </style>

    <div class="dos-col">
      <div>
        <div class="tarjeta-s">
          <h2>Para quién es</h2>
          <label for="cliente">Nombre del cliente *</label>
          <input id="cliente" type="text" list="listaClientes" maxlength="150"
                 placeholder="Escribí el nombre (nuevo o de tu cartera)...">
          <datalist id="listaClientes">
            <?php foreach ($clientes as $c): ?>
              <option value="<?php echo htmlspecialchars($c['nombre']); ?>"><?php echo htmlspecialchars($c['empresa']); ?></option>
            <?php endforeach; ?>
          </datalist>
          <p style="font-size:12px;color:var(--txt-3);margin-top:6px"><span id="clienteHint">No hace falta que exista:
            si es nuevo, se crea solo al guardar.</span> <a href="clientes.php">Gestionar clientes</a></p>
          <label for="notas">Notas (opcional)</label>
          <input id="notas" type="text" maxlength="2000" placeholder="Seña, plazo de entrega, aclaraciones...">
        </div>

        <div class="tarjeta-s" style="margin-top:16px">
          <h2>Piezas <span id="cuentaItems" style="color:var(--txt-3);font-weight:400"></span></h2>
          <div class="vacio-items" id="vacioItems">Tu presupuesto está vacío. Elegí un producto guardado o calculá una pieza nueva con la calculadora.</div>
          <div class="tabla-scroll" id="zonaTabla" style="display:none">
            <table id="tablaItems">
              <thead><tr><th>Pieza</th><th class="num">Cant.</th><th class="num">Precio unit.</th><th class="num">Subtotal</th><th></th></tr></thead>
              <tbody></tbody>
            </table>
          </div>

          <div class="elegir-prod">
            <button type="button" class="btn sec" id="btnElegir"><?php echo ui_icono('libreria', 16); ?> Elegir un producto</button>
            <div class="lista-prod" id="listaProd">
              <?php if (!$productos): ?>
                <div class="sin">No tenés productos todavía. Calculá una pieza con la calculadora
                y marcá "guardar como producto", o cargalos en la sección <a href="productos.php">Productos</a>.</div>
              <?php else: foreach ($productos as $p): ?>
                <button type="button" class="item-p" data-prod='<?php echo htmlspecialchars(json_encode([
                    'producto_id' => (int) $p['id'], 'nombre' => $p['nombre'],
                    'descripcion' => $p['descripcion'] ?? '', 'precio' => (float) $p['precio'],
                    'costo' => (float) $p['costo'],
                ], JSON_UNESCAPED_UNICODE), ENT_QUOTES); ?>'>
                  <span><?php echo htmlspecialchars($p['nombre']); ?></span>
                  <strong><?php echo taller_precio($p['precio']); ?></strong>
                </button>
              <?php endforeach; endif; ?>
            </div>
          </div>

          <div class="totales">
            <div class="linea"><span>Subtotal</span><strong id="tSubtotal">$ 0</strong></div>
            <div class="linea">
              <span style="display:flex;align-items:center;gap:10px">Descuento
                <span class="desc-tipo">
                  <button type="button" id="descMonto" class="on">$</button>
                  <button type="button" id="descPct">%</button>
                </span>
              </span>
              <input id="descValor" type="number" min="0" step="0.01" value="0">
            </div>
            <div class="linea" id="lineaGanancia" style="color:var(--txt-3);font-size:12.5px">
              <span>Ganancia estimada (precio − costo)</span><span id="tGanancia">$ 0</span></div>
            <div class="linea grande"><span>Total</span><span id="tTotal">$ 0</span></div>
          </div>
        </div>

        <div class="acciones-pie">
          <span class="nota-pie" id="notaPie"></span>
          <button type="button" class="btn sec" id="btnPdf"><?php echo ui_icono('descargar', 16); ?> Exportar PDF</button>
          <button type="button" class="btn sec" id="btnWpp"><?php echo ui_icono('whatsapp', 16); ?> Compartir</button>
          <button type="button" class="btn sec" id="btnVendido">Guardar y marcar vendido</button>
          <button type="button" class="btn" id="btnGuardar">Guardar</button>
        </div>
      </div>

      <aside class="tarjeta-s calc">
        <h2><?php echo ui_icono('calculadora', 17); ?> Calcular nueva pieza</h2>

        <label for="cNombre">Nombre de la pieza *</label>
        <input id="cNombre" type="text" maxlength="150" placeholder="Soporte GoPro, llavero personalizado...">
        <label for="cDesc">Descripción (opcional)</label>
        <input id="cDesc" type="text" maxlength="500" placeholder="Color, material, acabado...">

        <div class="fila-2">
          <span><label for="cMaterial">Material</label>
            <select id="cMaterial"><?php foreach ($materiales as $m): ?><option><?php echo $m; ?></option><?php endforeach; ?></select></span>
          <span><label for="cPeso">Peso usado (g)</label><input id="cPeso" type="number" min="0" step="1" value="0"></span>
        </div>
        <div class="fila-2">
          <span><label for="cPrecioCarrete">Precio carrete $</label><input id="cPrecioCarrete" type="number" min="0" step="100" value="25000"></span>
          <span><label for="cPesoCarrete">Peso carrete (g)</label><input id="cPesoCarrete" type="number" min="1" step="50" value="1000"></span>
        </div>
        <div class="fila-2">
          <span><label for="cHoras">Horas</label><input id="cHoras" type="number" min="0" step="1" value="0"></span>
          <span><label for="cMin">Minutos</label><input id="cMin" type="number" min="0" max="59" step="1" value="0"></span>
        </div>

        <details>
          <summary>Material de soporte</summary>
          <div class="inner">
            <div class="fila-2">
              <span><label for="cPesoSop">Peso soporte (g)</label><input id="cPesoSop" type="number" min="0" step="1" value="0"></span>
              <span><label for="cPrecioCarreteSop">Precio carrete sop. <small>(0 = mismo)</small></label>
                <input id="cPrecioCarreteSop" type="number" min="0" step="100" value="0"></span>
            </div>
            <label for="cPesoCarreteSop">Peso carrete soporte (g)</label>
            <input id="cPesoCarreteSop" type="number" min="1" step="50" value="1000">
          </div>
        </details>

        <details>
          <summary>Impresora y electricidad</summary>
          <div class="inner">
            <label for="cModelo">Modelo de impresora</label>
            <select id="cModelo">
              <option value="">Otro / Personalizado</option>
              <?php foreach (taller_impresoras() as $imp): ?>
                <option value="<?php echo htmlspecialchars($imp); ?>"><?php echo htmlspecialchars($imp); ?></option>
              <?php endforeach; ?>
            </select>
            <p style="font-size:12px;color:var(--txt-3);margin-top:6px">Elegí tu modelo y autocompletamos el consumo (W).
              Si no está en la lista, dejá "Otro / Personalizado".</p>
            <div class="fila-2">
              <span><label for="cWatts">Consumo (W)</label><input id="cWatts" type="number" min="0" step="10" value="150"></span>
              <span><label for="cTarifa">Tarifa ($/kWh)</label><input id="cTarifa" type="number" min="0" step="1" value="140"></span>
            </div>
            <div class="fila-2">
              <span><label for="cImpresora">Costo impresora $</label><input id="cImpresora" type="number" min="0" step="1000" value="0"></span>
              <span><label for="cVida">Vida útil (hs)</label><input id="cVida" type="number" min="1" step="100" value="4320"></span>
            </div>
            <label for="cMant">Mantenimiento anual $</label>
            <input id="cMant" type="number" min="0" step="500" value="0">
          </div>
        </details>

        <details>
          <summary>Mano de obra y extras</summary>
          <div class="inner">
            <div class="fila-2">
              <span><label for="cPrep">Preparación (min)</label><input id="cPrep" type="number" min="0" step="5" value="0"></span>
              <span><label for="cPost">Post-proceso (min)</label><input id="cPost" type="number" min="0" step="5" value="0"></span>
            </div>
            <label for="cTarifaMano">Tarifa mano de obra ($/h)</label>
            <input id="cTarifaMano" type="number" min="0" step="100" value="0">
            <div class="fila-2">
              <span><label for="cEmpaque">Empaquetado $</label><input id="cEmpaque" type="number" min="0" step="50" value="0"></span>
              <span><label for="cEnvio">Envío $</label><input id="cEnvio" type="number" min="0" step="50" value="0"></span>
            </div>
            <div class="fila-2">
              <span><label for="cOtros">Otros costos $</label><input id="cOtros" type="number" min="0" step="50" value="0"></span>
              <span><label for="cFallos">Tasa de fallos (%)</label><input id="cFallos" type="number" min="0" max="99" step="1" value="5"></span>
            </div>
          </div>
        </details>

        <label for="cMargen">Ganancia (%)</label>
        <input id="cMargen" type="number" min="0" step="5" value="200">

        <div class="desglose" id="desglose">
          <div class="linea"><span>Material</span><span id="dMaterial">$ 0</span></div>
          <div class="linea" id="lineaSop" style="display:none"><span>Soporte</span><span id="dSoporte">$ 0</span></div>
          <div class="linea"><span>Electricidad</span><span id="dElec">$ 0</span></div>
          <div class="linea"><span>Desgaste de máquina</span><span id="dDep">$ 0</span></div>
          <div class="linea"><span>Mano de obra</span><span id="dMano">$ 0</span></div>
          <div class="linea"><span>Extras y fallos</span><span id="dExtras">$ 0</span></div>
          <div class="linea total"><span>Costo total</span><span id="dCosto">$ 0</span></div>
          <div class="linea precio"><span>Precio sugerido</span><span id="dSugerido">$ 0</span></div>
        </div>

        <div class="fila-2" style="margin-top:4px">
          <span><label for="cPrecioFinal">Precio final $ (editable)</label>
            <input id="cPrecioFinal" type="number" min="0" step="50" value="0"></span>
          <span><label for="cCantidad">Cantidad</label>
            <input id="cCantidad" type="number" min="1" max="9999" step="1" value="1"></span>
        </div>

        <p id="margenInverso" style="font-size:12.5px;color:var(--txt-3);margin-top:6px"></p>

        <label class="check-linea" for="cGuardarProd">
          <input id="cGuardarProd" type="checkbox" checked>
          Guardar también como producto del catálogo
        </label>

        <button type="button" class="btn" id="btnAgregar" style="width:100%;margin-top:14px">+ Agregar pieza</button>
        <p id="ayudaAgregar" style="font-size:12.5px;color:var(--txt-3);text-align:center;margin-top:8px"></p>
      </aside>
    </div>

    <form method="post" id="formGuardar" style="display:none">
      <input type="hidden" name="csrf" value="<?php echo com_csrf(); ?>">
      <input type="hidden" name="datos" id="datosJson">
    </form>

<script>
(function(){
  'use strict';
  const $ = id => document.getElementById(id);
  const MONEDA = { s: <?php echo json_encode($moneda_simbolo); ?>, d: <?php echo (int) $moneda_dec; ?> };
  const fmt = n => MONEDA.s + ' ' + (+n).toLocaleString('es-AR',
      { minimumFractionDigits: MONEDA.d, maximumFractionDigits: MONEDA.d });

  // ---------- Estado del presupuesto ----------
  const estado = <?php echo $estado_json; ?>;
  $('cliente').value = estado.cliente;
  $('notas').value = estado.notas;

  function render(){
    const tb = $('tablaItems').querySelector('tbody');
    tb.innerHTML = '';
    estado.items.forEach((it, i) => {
      const tr = document.createElement('tr');
      const sub = it.precio * it.cantidad;
      tr.innerHTML =
        '<td><strong></strong><div style="color:var(--txt-3);font-size:12px"></div></td>' +
        '<td class="num"><input type="number" class="cant" min="1" max="9999" value="' + it.cantidad + '"></td>' +
        '<td class="num"><input type="number" class="precio-in" min="0" step="50" value="' + it.precio + '"></td>' +
        '<td class="num"><strong>' + fmt(sub) + '</strong></td>' +
        '<td><button type="button" class="quitar" title="Quitar" aria-label="Quitar">✕</button></td>';
      tr.querySelector('strong').textContent = it.nombre + (it.guardar_producto ? ' ★' : '');
      tr.querySelector('div').textContent = it.descripcion || '';
      tr.querySelector('.cant').addEventListener('input', e => { it.cantidad = Math.max(1, parseInt(e.target.value || 1)); render(); });
      tr.querySelector('.precio-in').addEventListener('input', e => { it.precio = Math.max(0, parseFloat(e.target.value || 0)); totales(); });
      tr.querySelector('.quitar').addEventListener('click', () => { estado.items.splice(i, 1); render(); });
      tb.appendChild(tr);
    });
    $('zonaTabla').style.display = estado.items.length ? '' : 'none';
    $('vacioItems').style.display = estado.items.length ? 'none' : '';
    $('cuentaItems').textContent = '(' + estado.items.length + ')';
    totales();
  }

  function totales(){
    const st = estado.items.reduce((a, it) => a + it.precio * it.cantidad, 0);
    const costo = estado.items.reduce((a, it) => a + (it.costo || 0) * it.cantidad, 0);
    const dv = Math.max(0, parseFloat($('descValor').value || 0));
    const desc = estado.descuento_tipo === 'porcentaje' ? st * Math.min(100, dv) / 100 : Math.min(st, dv);
    const total = Math.max(0, st - desc);
    $('tSubtotal').textContent = fmt(st);
    $('tTotal').textContent = fmt(total);
    $('tGanancia').textContent = fmt(total - costo);
    estado.descuento_valor = dv;
    const falta = !$('cliente').value.trim() ? 'Completá el nombre del cliente para guardar.'
                : (!estado.items.length ? 'Agregá al menos una pieza.' : '');
    $('notaPie').textContent = falta;
  }

  function setDescTipo(t){
    estado.descuento_tipo = t;
    $('descMonto').classList.toggle('on', t === 'monto');
    $('descPct').classList.toggle('on', t === 'porcentaje');
    totales();
  }
  $('descMonto').addEventListener('click', () => setDescTipo('monto'));
  $('descPct').addEventListener('click', () => setDescTipo('porcentaje'));
  $('descValor').value = estado.descuento_valor || 0;
  setDescTipo(estado.descuento_tipo);
  $('descValor').addEventListener('input', totales);
  $('cliente').addEventListener('input', totales);

  // ---------- Elegir producto ----------
  $('btnElegir').addEventListener('click', () => $('listaProd').classList.toggle('abierta'));
  document.addEventListener('click', e => {
    if (!e.target.closest('.elegir-prod')) $('listaProd').classList.remove('abierta');
  });
  document.querySelectorAll('.item-p').forEach(b => b.addEventListener('click', () => {
    const p = JSON.parse(b.dataset.prod);
    estado.items.push({ producto_id: p.producto_id, nombre: p.nombre, descripcion: p.descripcion,
                        cantidad: 1, precio: p.precio, costo: p.costo });
    $('listaProd').classList.remove('abierta');
    render();
  }));

  // ---------- Calculadora (misma lógica que la Calculadora de costos) ----------
  const CFG_CAMPOS = ['cModelo','cPrecioCarrete','cPesoCarrete','cWatts','cTarifa','cImpresora','cVida','cMant',
                      'cPrep','cPost','cTarifaMano','cEmpaque','cEnvio','cOtros','cFallos','cMargen',
                      'cPrecioCarreteSop','cPesoCarreteSop'];
  try {
    const cfg = JSON.parse(localStorage.getItem('ptools_calc_cfg') || '{}');
    CFG_CAMPOS.forEach(id => { if (cfg[id] !== undefined) $(id).value = cfg[id]; });
  } catch(e){}
  function guardarCfg(){
    const cfg = {};
    CFG_CAMPOS.forEach(id => cfg[id] = $(id).value);
    localStorage.setItem('ptools_calc_cfg', JSON.stringify(cfg));
  }

  const val = id => parseFloat($(id).value) || 0;
  let precioTocado = false;

  function calcular(){
    const costoGramo = val('cPrecioCarrete') / (val('cPesoCarrete') || 1);
    const material = costoGramo * val('cPeso');
    // Soporte: carrete propio o, si no se cargó precio, el mismo costo por gramo
    const sopGramo = val('cPrecioCarreteSop') > 0
        ? val('cPrecioCarreteSop') / (val('cPesoCarreteSop') || 1) : costoGramo;
    const soporte = sopGramo * val('cPesoSop');
    const horas = val('cHoras') + val('cMin') / 60;
    const elec = (val('cWatts') / 1000) * horas * val('cTarifa');
    const mano = ((val('cPrep') + val('cPost')) / 60) * val('cTarifaMano');
    const depHora = (val('cImpresora') / (val('cVida') || 1)) + val('cMant') / 1500;
    const dep = depHora * horas;
    const extras = val('cEmpaque') + val('cEnvio') + val('cOtros');
    const base = material + soporte + elec + mano + dep + extras;
    const fallos = Math.min(0.99, val('cFallos') / 100);
    const costoTotal = base / (1 - fallos);
    const sugerido = costoTotal * (1 + val('cMargen') / 100);

    $('dMaterial').textContent = fmt(material);
    $('lineaSop').style.display = soporte > 0 ? '' : 'none';
    $('dSoporte').textContent = fmt(soporte);
    $('dElec').textContent = fmt(elec);
    $('dDep').textContent = fmt(dep);
    $('dMano').textContent = fmt(mano);
    $('dExtras').textContent = fmt(extras + (costoTotal - base));
    $('dCosto').textContent = fmt(costoTotal);
    $('dSugerido').textContent = fmt(sugerido);
    if (!precioTocado) $('cPrecioFinal').value = Math.round(sugerido);

    // Margen resultante del precio final (editable): igual que el modo precio fijo del cotizador
    const pf = val('cPrecioFinal');
    if (costoTotal > 0 && pf > 0) {
      const m = ((pf - costoTotal) / costoTotal) * 100;
      $('margenInverso').textContent = 'Con este precio, tu margen es ' + m.toFixed(1) + '% ('
          + fmt(pf - costoTotal) + ' de ganancia).';
      $('margenInverso').style.color = m >= 0 ? 'var(--ok)' : 'var(--bad)';
    } else {
      $('margenInverso').textContent = '';
    }

    const nombreOk = $('cNombre').value.trim() !== '';
    $('btnAgregar').disabled = !nombreOk;
    $('btnAgregar').style.opacity = nombreOk ? 1 : .5;
    $('ayudaAgregar').textContent = nombreOk ? '' : 'Ponele un nombre a la pieza para poder agregarla.';
    return costoTotal;
  }
  document.querySelectorAll('.calc input, .calc select').forEach(el =>
    el.addEventListener('input', () => { if (el.id === 'cPrecioFinal') precioTocado = true; calcular(); guardarCfg(); }));

  // Modelo de impresora: detecta el "(NNN W)" del nombre y bloquea el consumo
  function aplicarModelo(){
    const m = ($('cModelo').value || '').match(/\((\d+)\s*W\)/i);
    if (m) { $('cWatts').value = m[1]; $('cWatts').disabled = true; }
    else { $('cWatts').disabled = false; }
  }
  $('cModelo').addEventListener('change', () => { aplicarModelo(); calcular(); guardarCfg(); });
  aplicarModelo();

  $('btnAgregar').addEventListener('click', () => {
    const nombre = $('cNombre').value.trim();
    if (!nombre) return;
    const costo = calcular();
    estado.items.push({
      producto_id: null,
      nombre: nombre,
      descripcion: $('cDesc').value.trim(),
      cantidad: Math.max(1, parseInt($('cCantidad').value || 1)),
      precio: Math.max(0, parseFloat($('cPrecioFinal').value || 0)),
      costo: Math.round(costo * 100) / 100,
      guardar_producto: $('cGuardarProd').checked,
      datos: { material: $('cMaterial').value, peso_g: val('cPeso'),
               horas: val('cHoras'), minutos: val('cMin') },
    });
    $('cNombre').value = ''; $('cDesc').value = '';
    $('cPeso').value = 0; $('cHoras').value = 0; $('cMin').value = 0; $('cCantidad').value = 1;
    precioTocado = false;
    calcular();
    render();
  });

  // ---------- Guardar / vendido / compartir ----------
  function enviar(estadoNuevo){
    estado.cliente = $('cliente').value.trim();
    estado.notas = $('notas').value.trim();
    if (estadoNuevo) estado.estado = estadoNuevo;
    if (!estado.cliente || !estado.items.length) { totales(); return; }
    $('datosJson').value = JSON.stringify(estado);
    $('formGuardar').submit();
  }
  $('btnGuardar').addEventListener('click', () => enviar(null));
  $('btnVendido').addEventListener('click', () => enviar('vendido'));

  const CLIENTES_TEL = <?php echo $clientes_js; ?>;

  // Indicador en vivo: cliente de la cartera vs. cliente nuevo
  function pintarHintCliente(){
    const n = $('cliente').value.trim();
    const h = $('clienteHint');
    if (!n) {
      h.textContent = 'No hace falta que exista: si es nuevo, se crea solo al guardar.';
      h.style.color = '';
    } else if (CLIENTES_TEL.hasOwnProperty(n)) {
      h.textContent = '✓ Cliente de tu cartera.';
      h.style.color = 'var(--ok)';
    } else {
      h.textContent = '✓ Cliente nuevo: al guardar se suma a tu sección Clientes para completarle los datos.';
      h.style.color = 'var(--accent)';
    }
  }
  $('cliente').addEventListener('input', pintarHintCliente);
  pintarHintCliente();
  // Exportar PDF: mismo documento que el exportador del cotizador,
  // con las piezas del presupuesto como filas de detalle.
  const printDoc = document.createElement('div');
  printDoc.id = 'printDoc';
  printDoc.setAttribute('aria-hidden', 'true');
  document.body.appendChild(printDoc);

  $('btnPdf').addEventListener('click', () => {
    const esc = s => String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const cliente = $('cliente').value.trim() || 'Cliente';
    const fecha = new Date().toLocaleDateString('es-AR', { day: '2-digit', month: '2-digit', year: 'numeric' });
    const st = estado.items.reduce((a, it) => a + it.precio * it.cantidad, 0);
    const dv = Math.max(0, parseFloat($('descValor').value || 0));
    const desc = estado.descuento_tipo === 'porcentaje' ? st * Math.min(100, dv) / 100 : Math.min(st, dv);
    const total = Math.max(0, st - desc);
    const unidades = estado.items.reduce((a, it) => a + it.cantidad, 0);

    let html =
      '<div class="pd-top">' +
        '<img class="pd-logo" src="../assets/img/printika-tools.svg" alt="Printika Tools">' +
        '<div class="pd-meta">' +
          '<div class="pd-kicker">Presupuesto</div>' +
          '<div class="pd-project">' + esc(cliente) + '</div>' +
          '<div class="pd-date">' + esc(fecha) + ' &middot; <?php echo $moneda; ?></div>' +
        '</div>' +
      '</div>' +
      '<div class="pd-specs">' +
        '<div class="pd-spec">Piezas<strong>' + estado.items.length + '</strong></div>' +
        '<div class="pd-spec">Unidades<strong>' + unidades + '</strong></div>' +
        '<div class="pd-spec">Fecha de emision<strong>' + esc(fecha) + '</strong></div>' +
      '</div>' +
      '<div class="pd-rows">' +
        estado.items.map(it =>
          '<div class="pd-row"><span class="l">' + esc(it.nombre) +
          (it.cantidad > 1 ? ' &times; ' + it.cantidad : '') +
          (it.descripcion ? ' <small>&middot; ' + esc(it.descripcion) + '</small>' : '') +
          '</span><span class="v">' + esc(fmt(it.precio * it.cantidad)) + '</span></div>').join('') +
        '<div class="pd-row sub"><span class="l">Subtotal</span><span class="v">' + esc(fmt(st)) + '</span></div>' +
        (desc > 0 ? '<div class="pd-row"><span class="l">Descuento' +
          (estado.descuento_tipo === 'porcentaje' ? ' (' + dv + '%)' : '') +
          '</span><span class="v">&minus; ' + esc(fmt(desc)) + '</span></div>' : '') +
      '</div>' +
      '<div class="pd-total"><span class="l">Total</span><span class="v">' + esc(fmt(total)) + '</span></div>';

    const notas = $('notas').value.trim();
    if (notas) {
      html += '<div class="pd-notas"><div class="pd-h">Notas</div>' + esc(notas) + '</div>';
    }

    printDoc.innerHTML = html;
    setTimeout(() => window.print(), 60);
  });

  // ---------- Compartir por WhatsApp con el PDF adjunto ----------
  // En celulares usa el menu nativo de compartir (el PDF viaja adjunto);
  // en computadoras descarga el PDF y abre el chat para adjuntarlo a mano.
  function cargarJsPDF(){
    if (window.jspdf) return Promise.resolve();
    return new Promise((res, rej) => {
      const sc = document.createElement('script');
      sc.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js';
      sc.onload = res; sc.onerror = rej;
      document.head.appendChild(sc);
    });
  }

  let logoPngCache = null;
  function cargarLogo(){
    if (logoPngCache) return Promise.resolve(logoPngCache);
    return new Promise((res) => {
      const img = new Image();
      img.onload = () => {
        const cv = document.createElement('canvas');
        cv.width = 504; cv.height = 180;
        cv.getContext('2d').drawImage(img, 0, 0, 504, 180);
        logoPngCache = cv.toDataURL('image/png');
        res(logoPngCache);
      };
      img.onerror = () => res(null);
      img.src = '../assets/img/printika-tools.svg';
    });
  }

  function textoWpp(st, desc){
    let t = 'Hola' + ($('cliente').value.trim() ? ' ' + $('cliente').value.trim() : '') + '! Te paso el presupuesto:\n\n';
    estado.items.forEach(it => {
      t += '- ' + it.nombre + (it.cantidad > 1 ? ' x' + it.cantidad : '') + ': ' + fmt(it.precio * it.cantidad) + '\n';
    });
    if (desc > 0) t += '\nDescuento: -' + fmt(desc) + '\n';
    t += '\n*Total: ' + fmt(Math.max(0, st - desc)) + '*\n\nPrintika Tools';
    return t;
  }

  async function generarPdf(){
    await cargarJsPDF();
    const logo = await cargarLogo();
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF({ unit: 'mm', format: 'a4' });
    const MI = 15, MD = 195;               // margenes izquierdo y derecho
    const cliente = $('cliente').value.trim() || 'Cliente';
    const fecha = new Date().toLocaleDateString('es-AR', { day: '2-digit', month: '2-digit', year: 'numeric' });
    const st = estado.items.reduce((a, it) => a + it.precio * it.cantidad, 0);
    const dv = Math.max(0, parseFloat($('descValor').value || 0));
    const desc = estado.descuento_tipo === 'porcentaje' ? st * Math.min(100, dv) / 100 : Math.min(st, dv);
    const total = Math.max(0, st - desc);
    const unidades = estado.items.reduce((a, it) => a + it.cantidad, 0);

    // Encabezado: logo + PRESUPUESTO / cliente / fecha (mismo diseño que el export del cotizador)
    if (logo) doc.addImage(logo, 'PNG', MI, 16, 42, 15);
    doc.setFont('helvetica', 'bold'); doc.setFontSize(9); doc.setTextColor(11, 120, 181);
    doc.text('P R E S U P U E S T O', MD, 18, { align: 'right' });
    doc.setFontSize(19); doc.setTextColor(16, 27, 41);
    doc.text(cliente, MD, 26, { align: 'right' });
    doc.setFont('helvetica', 'normal'); doc.setFontSize(9); doc.setTextColor(100, 116, 140);
    doc.text(fecha + '  ·  <?php echo $moneda; ?>', MD, 31.5, { align: 'right' });
    doc.setDrawColor(23, 32, 46); doc.setLineWidth(0.7);
    doc.line(MI, 36, MD, 36);

    // Ficha: piezas / unidades / fecha
    let y = 43;
    const spec = (x, k, v) => {
      doc.setFontSize(7.5); doc.setTextColor(100, 116, 140); doc.setFont('helvetica', 'normal');
      doc.text(k.toUpperCase(), x, y);
      doc.setFontSize(11); doc.setTextColor(16, 27, 41); doc.setFont('helvetica', 'bold');
      doc.text(String(v), x, y + 5.5);
    };
    spec(MI, 'Piezas', estado.items.length);
    spec(MI + 30, 'Unidades', unidades);
    spec(MI + 60, 'Fecha de emision', fecha);
    y += 16;

    // Detalle de piezas
    const fila = (izq, der, sub, gris) => {
      if (y > 265) { doc.addPage(); y = 20; }
      if (sub) { doc.setDrawColor(23, 32, 46); doc.setLineWidth(0.7); doc.line(MI, y, MD, y); y += 6.5; }
      doc.setFontSize(10); doc.setFont('helvetica', sub ? 'bold' : 'normal');
      doc.setTextColor.apply(doc, sub ? [16, 27, 41] : [68, 83, 107]);
      doc.text(izq, MI, y);
      if (gris) { doc.setFontSize(8); doc.setTextColor(138, 151, 171); doc.text(gris, MI + doc.getTextWidth(izq) + 2, y); doc.setFontSize(10); }
      doc.setFont('helvetica', sub ? 'bold' : 'bold'); doc.setTextColor(16, 27, 41);
      doc.text(der, MD, y, { align: 'right' });
      y += 3.5;
      if (!sub) { doc.setDrawColor(229, 234, 241); doc.setLineWidth(0.25); doc.line(MI, y, MD, y); }
      y += 6;
    };
    estado.items.forEach(it => {
      fila(it.nombre + (it.cantidad > 1 ? '  × ' + it.cantidad : ''), fmt(it.precio * it.cantidad),
           false, it.descripcion ? '· ' + it.descripcion : '');
    });
    fila('Subtotal', fmt(st), true, '');
    if (desc > 0) fila('Descuento' + (estado.descuento_tipo === 'porcentaje' ? ' (' + dv + '%)' : ''), '- ' + fmt(desc), false, '');

    // Caja del total
    if (y > 250) { doc.addPage(); y = 20; }
    y += 2;
    doc.setFillColor(234, 244, 251); doc.setDrawColor(189, 217, 236); doc.setLineWidth(0.3);
    doc.roundedRect(MI, y, MD - MI, 17, 2.5, 2.5, 'FD');
    doc.setFontSize(8); doc.setFont('helvetica', 'bold'); doc.setTextColor(11, 108, 168);
    doc.text('T O T A L', MI + 6, y + 10.5);
    doc.setFontSize(19);
    doc.text(fmt(total), MD - 6, y + 11.5, { align: 'right' });
    y += 25;

    const notas = $('notas').value.trim();
    if (notas) {
      doc.setFontSize(7.5); doc.setTextColor(100, 116, 140);
      doc.text('NOTAS', MI, y);
      doc.setFontSize(9.5); doc.setTextColor(68, 83, 107); doc.setFont('helvetica', 'normal');
      doc.text(doc.splitTextToSize(notas, MD - MI), MI, y + 5);
    }
    return doc;
  }

  $('btnWpp').addEventListener('click', async () => {
    const st = estado.items.reduce((a, it) => a + it.precio * it.cantidad, 0);
    const dv = Math.max(0, parseFloat($('descValor').value || 0));
    const desc = estado.descuento_tipo === 'porcentaje' ? st * Math.min(100, dv) / 100 : Math.min(st, dv);
    const texto = textoWpp(st, desc);
    const tel = (CLIENTES_TEL[$('cliente').value.trim()] || '').replace(/[^0-9]/g, '');
    const nombreArchivo = 'Presupuesto - ' + ($('cliente').value.trim() || 'cliente') + '.pdf';

    $('btnWpp').disabled = true;
    try {
      const doc = await generarPdf();
      const archivo = new File([doc.output('blob')], nombreArchivo, { type: 'application/pdf' });
      if (navigator.canShare && navigator.canShare({ files: [archivo] })) {
        // Menu nativo: en el celular elegis WhatsApp y el PDF va adjunto
        await navigator.share({ files: [archivo], text: texto });
      } else {
        // Escritorio: descarga el PDF y abre el chat para adjuntarlo
        doc.save(nombreArchivo);
        $('notaPie').textContent = 'PDF descargado: adjuntalo en el chat de WhatsApp que se abrió.';
        window.open('https://wa.me/' + tel + '?text=' + encodeURIComponent(texto), '_blank');
      }
    } catch (e) {
      if (e && e.name === 'AbortError') return; // cancelo el menu de compartir
      // Sin PDF (ej. fallo el CDN): compartir solo el texto como antes
      window.open('https://wa.me/' + tel + '?text=' + encodeURIComponent(texto), '_blank');
    } finally {
      $('btnWpp').disabled = false;
    }
  });

  window.addEventListener('ptools:moneda', e => {
    MONEDA.s = e.detail.s; MONEDA.d = e.detail.d;
    render();
    calcular();
  });

  render();
  calcular();
})();
</script>
<?php ui_panel_fin(); ?>
