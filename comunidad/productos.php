<?php
/**
 * Productos: catálogo de piezas del usuario.
 * Se cargan acá o se guardan desde la calculadora del presupuesto.
 */
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/ui.php';
require_once __DIR__ . '/inc/taller.php';

requerir_miembro();
$u = usuario_actual();
taller_migrar();
$uid = (int) $u['id'];

$aviso = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'moneda') {
    if (com_csrf_ok($_POST['csrf'] ?? '')) {
        taller_guardar_moneda($uid, $_POST['moneda'] ?? '');
    }
    header('Location: productos.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!com_csrf_ok($_POST['csrf'] ?? '')) {
        $error = 'La sesión expiró, probá de nuevo.';
    } else {
        $accion = $_POST['accion'] ?? '';
        $pid    = (int) ($_POST['producto_id'] ?? 0);
        if ($accion === 'guardar') {
            $nombre = trim($_POST['nombre'] ?? '');
            $desc   = trim($_POST['descripcion'] ?? '');
            $costo  = max(0, (float) ($_POST['costo'] ?? 0));
            $precio = max(0, (float) ($_POST['precio'] ?? 0));
            if ($nombre === '' || mb_strlen($nombre) > 150) {
                $error = 'Ingresá el nombre del producto.';
            } elseif ($pid) {
                com_db()->prepare('UPDATE productos SET nombre=?, descripcion=?, costo=?, precio=?, actualizado_en=NOW()
                                    WHERE id=? AND usuario_id=?')
                    ->execute([$nombre, $desc, $costo, $precio, $pid, $uid]);
                $aviso = 'Producto actualizado.';
            } else {
                com_db()->prepare('INSERT INTO productos (usuario_id, nombre, descripcion, costo, precio, creado_en, actualizado_en)
                                   VALUES (?, ?, ?, ?, ?, NOW(), NOW())')
                    ->execute([$uid, $nombre, $desc, $costo, $precio]);
                $aviso = "Producto «{$nombre}» creado.";
            }
        } elseif ($accion === 'eliminar' && $pid) {
            com_db()->prepare('DELETE FROM productos WHERE id=? AND usuario_id=?')->execute([$pid, $uid]);
            $aviso = 'Producto eliminado.';
        }
    }
}

$q = trim($_GET['q'] ?? '');
$sql = 'SELECT * FROM productos WHERE usuario_id = ?';
$par = [$uid];
if ($q !== '') { $sql .= ' AND nombre LIKE ?'; $par[] = "%{$q}%"; }
$sql .= ' ORDER BY actualizado_en DESC';
$stmt = com_db()->prepare($sql);
$stmt->execute($par);
$productos = $stmt->fetchAll();

$editando = null;
if (isset($_GET['editar'])) {
    $stmt = com_db()->prepare('SELECT * FROM productos WHERE id=? AND usuario_id=?');
    $stmt->execute([(int) $_GET['editar'], $uid]);
    $editando = $stmt->fetch() ?: null;
}

$moneda = taller_moneda_usuario() ?: 'ARS';
[$moneda_simbolo, $moneda_dec] = taller_monedas()[$moneda];

ui_panel_inicio('Productos', $u, 'Productos');
?>
    <style>.contenido{max-width:none}</style>
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap">
      <div>
        <h1>Productos</h1>
        <p class="bajada">Tu catálogo de piezas: cargalas acá o guardalas desde la calculadora de un presupuesto.</p>
      </div>
      <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
        <?php taller_chip_moneda(); ?>
        <button type="button" class="btn" id="btnNuevoProducto">+ Nuevo producto</button>
      </div>
    </div>
    <?php taller_popup_moneda(); ?>

    <?php if ($aviso): ?><div class="msg ok"><?php echo ui_icono('check', 16); ?><span><?php echo htmlspecialchars($aviso); ?></span></div><?php endif; ?>
    <?php if ($error): ?><div class="msg bad"><?php echo ui_icono('alerta', 16); ?><span><?php echo htmlspecialchars($error); ?></span></div><?php endif; ?>

    <style>
      .barra-sup{display:flex;gap:10px;align-items:center;margin-bottom:16px;flex-wrap:wrap}
      .barra-sup form.buscar{flex:1;min-width:220px;display:flex}
      .panel{background:var(--surface);border:1px solid var(--bd-suave);border-radius:var(--radio-g);overflow:hidden}
      table{width:100%;border-collapse:collapse;font-size:13.5px}
      th,td{padding:12px 16px;text-align:left;border-bottom:1px solid var(--bd-suave);vertical-align:middle}
      th{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.07em;color:var(--txt-3)}
      tr:last-child td{border-bottom:none}
      tbody tr{transition:background-color .15s ease}
      tbody tr:hover{background:var(--surface-2)}
      td.num{text-align:right;font-variant-numeric:tabular-nums;white-space:nowrap}
      th.num{text-align:right}
      td .acciones{display:flex;gap:6px;justify-content:flex-end}
      td form{margin:0}
      .vacio{padding:44px 20px;text-align:center;color:var(--txt-2);font-size:14px}
      .form-prod{background:var(--surface);border:1px solid var(--bd-suave);border-radius:var(--radio-g);
                 padding:20px;margin-bottom:20px}
      .form-prod h2{font-size:15px;font-weight:600;margin-bottom:4px}
      .form-prod .fila{display:grid;grid-template-columns:1.4fr 2fr .8fr .8fr auto;gap:10px;align-items:end}
      .margen-chip{font-size:12px;color:var(--ok);font-weight:500;white-space:nowrap}
      .zona-nuevo{display:grid;grid-template-columns:minmax(0,1fr) 400px;gap:20px;align-items:start;margin-bottom:20px}
      .form-prod{margin-bottom:0}
      .form-prod .fila{grid-template-columns:1fr 1fr}
      .form-prod .ancho-2{grid-column:span 2}
      .form-prod .pie{display:flex;justify-content:flex-end;gap:10px;margin-top:14px;align-items:center}
      .calc label{margin-top:10px}
      .calc h2{display:flex;align-items:center;gap:8px}
      .calc .fila-2{display:grid;grid-template-columns:1fr 1fr;gap:10px}
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
      @media (max-width:1080px){ .zona-nuevo{grid-template-columns:1fr} }
      @media (max-width:900px){ .form-prod .fila{grid-template-columns:1fr} .form-prod .ancho-2{grid-column:auto} .tabla-scroll{overflow-x:auto} }
    </style>

    <div class="zona-nuevo" id="zonaNuevo" <?php echo ($editando || $error) ? '' : 'hidden'; ?>>
      <div class="form-prod">
        <h2><?php echo $editando ? 'Editar producto' : 'Nuevo producto'; ?></h2>
        <p class="bajada" style="margin-bottom:2px">Costo = lo que te sale imprimirlo · Precio = lo que cobrás.
          Si no sabés el costo, usá la calculadora de al lado.</p>
        <form method="post">
          <input type="hidden" name="csrf" value="<?php echo com_csrf(); ?>">
          <input type="hidden" name="accion" value="guardar">
          <input type="hidden" name="producto_id" value="<?php echo $editando ? (int) $editando['id'] : 0; ?>">
          <div class="fila">
            <span class="ancho-2"><label for="p-nombre">Nombre</label>
              <input id="p-nombre" type="text" name="nombre" maxlength="150" required
                     value="<?php echo htmlspecialchars($editando['nombre'] ?? ''); ?>"></span>
            <span class="ancho-2"><label for="p-desc">Descripción (opcional)</label>
              <input id="p-desc" type="text" name="descripcion" maxlength="500"
                     value="<?php echo htmlspecialchars($editando['descripcion'] ?? ''); ?>"></span>
            <span><label for="p-costo">Costo $</label>
              <input id="p-costo" type="number" name="costo" min="0" step="0.01"
                     value="<?php echo $editando ? (float) $editando['costo'] : ''; ?>"></span>
            <span><label for="p-precio">Precio $</label>
              <input id="p-precio" type="number" name="precio" min="0" step="0.01"
                     value="<?php echo $editando ? (float) $editando['precio'] : ''; ?>"></span>
          </div>
          <div class="pie">
            <?php if ($editando): ?>
              <a href="productos.php" style="font-size:13px">Cancelar edición</a>
            <?php else: ?>
              <button type="button" class="btn sec" id="btnCancelarProducto">Cancelar</button>
            <?php endif; ?>
            <button class="btn" type="submit"><?php echo $editando ? 'Guardar' : 'Crear producto'; ?></button>
          </div>
        </form>
      </div>

      <aside class="form-prod calc">
        <h2><?php echo ui_icono('calculadora', 17); ?> Calculadora de costos</h2>
        <p class="bajada" style="margin-bottom:0">Calculá cuánto te sale la pieza y cargalo en el formulario.</p>
        <div class="fila-2">
          <span><label for="cPeso">Peso usado (g)</label><input id="cPeso" type="number" min="0" step="1" value="0"></span>
          <span><label for="cPrecioCarrete">Precio carrete $</label><input id="cPrecioCarrete" type="number" min="0" step="100" value="25000"></span>
        </div>
        <div class="fila-2">
          <span><label for="cPesoCarrete">Peso carrete (g)</label><input id="cPesoCarrete" type="number" min="1" step="50" value="1000"></span>
          <span><label for="cFallos">Tasa de fallos (%)</label><input id="cFallos" type="number" min="0" max="99" step="1" value="5"></span>
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
            <label for="cOtros">Otros costos $</label>
            <input id="cOtros" type="number" min="0" step="50" value="0">
          </div>
        </details>

        <label for="cMargen">Ganancia (%)</label>
        <input id="cMargen" type="number" min="0" step="5" value="200">

        <div class="desglose">
          <div class="linea"><span>Material</span><span id="dMaterial">$ 0</span></div>
          <div class="linea" id="lineaSop" style="display:none"><span>Soporte</span><span id="dSoporte">$ 0</span></div>
          <div class="linea"><span>Electricidad</span><span id="dElec">$ 0</span></div>
          <div class="linea"><span>Desgaste de máquina</span><span id="dDep">$ 0</span></div>
          <div class="linea"><span>Mano de obra</span><span id="dMano">$ 0</span></div>
          <div class="linea"><span>Extras y fallos</span><span id="dExtras">$ 0</span></div>
          <div class="linea total"><span>Costo total</span><span id="dCosto">$ 0</span></div>
          <div class="linea precio"><span>Precio sugerido</span><span id="dSugerido">$ 0</span></div>
        </div>

        <button type="button" class="btn" id="btnCargarProducto" style="width:100%;margin-top:14px">Cargar producto</button>
        <p style="font-size:12px;color:var(--txt-3);text-align:center;margin-top:8px">
          Completa el costo del formulario (y el precio, si está vacío).</p>
      </aside>
    </div>

    <script>
    (function(){
      'use strict';
      var $ = function(id){ return document.getElementById(id); };

      var zona = $('zonaNuevo');
      $('btnNuevoProducto').addEventListener('click', function(){
        zona.hidden = !zona.hidden;
        if (!zona.hidden) $('p-nombre').focus();
      });
      var cancelar = $('btnCancelarProducto');
      if (cancelar) cancelar.addEventListener('click', function(){ zona.hidden = true; });

      // Calculadora: misma logica y misma config recordada que en presupuestos
      var CFG = ['cModelo','cPrecioCarrete','cPesoCarrete','cWatts','cTarifa','cImpresora','cVida','cMant',
                 'cPrep','cPost','cTarifaMano','cEmpaque','cEnvio','cOtros','cFallos','cMargen',
                 'cPrecioCarreteSop','cPesoCarreteSop'];
      try {
        var cfg = JSON.parse(localStorage.getItem('ptools_calc_cfg') || '{}');
        CFG.forEach(function(id){ if (cfg[id] !== undefined && $(id)) $(id).value = cfg[id]; });
      } catch(e){}
      function guardarCfg(){
        var cfg = {};
        CFG.forEach(function(id){ if ($(id)) cfg[id] = $(id).value; });
        localStorage.setItem('ptools_calc_cfg', JSON.stringify(cfg));
      }

      var val = function(id){ return parseFloat($(id).value) || 0; };
      var MONEDA = { s: <?php echo json_encode($moneda_simbolo); ?>, d: <?php echo (int) $moneda_dec; ?> };
      var fmt = function(n){ return MONEDA.s + ' ' + (+n).toLocaleString('es-AR',
          { minimumFractionDigits: MONEDA.d, maximumFractionDigits: MONEDA.d }); };
      var ultimo = { costo: 0, sugerido: 0 };

      function calcular(){
        var costoGramo = val('cPrecioCarrete') / (val('cPesoCarrete') || 1);
        var material = costoGramo * val('cPeso');
        var sopGramo = val('cPrecioCarreteSop') > 0
            ? val('cPrecioCarreteSop') / (val('cPesoCarreteSop') || 1) : costoGramo;
        var soporte = sopGramo * val('cPesoSop');
        var horas = val('cHoras') + val('cMin') / 60;
        var elec = (val('cWatts') / 1000) * horas * val('cTarifa');
        var mano = ((val('cPrep') + val('cPost')) / 60) * val('cTarifaMano');
        var dep = ((val('cImpresora') / (val('cVida') || 1)) + val('cMant') / 1500) * horas;
        var extras = val('cEmpaque') + val('cEnvio') + val('cOtros');
        var base = material + soporte + elec + mano + dep + extras;
        var fallos = Math.min(0.99, val('cFallos') / 100);
        var costoTotal = base / (1 - fallos);
        var sugerido = costoTotal * (1 + val('cMargen') / 100);
        $('dMaterial').textContent = fmt(material);
        $('lineaSop').style.display = soporte > 0 ? '' : 'none';
        $('dSoporte').textContent = fmt(soporte);
        $('dElec').textContent = fmt(elec);
        $('dDep').textContent = fmt(dep);
        $('dMano').textContent = fmt(mano);
        $('dExtras').textContent = fmt(extras + (costoTotal - base));
        $('dCosto').textContent = fmt(costoTotal);
        $('dSugerido').textContent = fmt(sugerido);
        ultimo.costo = Math.round(costoTotal * 100) / 100;
        ultimo.sugerido = Math.round(sugerido);
      }
      document.querySelectorAll('.calc input, .calc select').forEach(function(el){
        el.addEventListener('input', function(){ calcular(); guardarCfg(); });
      });

      // Modelo de impresora: detecta el "(NNN W)" del nombre y bloquea el consumo
      function aplicarModelo(){
        var m = ($('cModelo').value || '').match(/\((\d+)\s*W\)/i);
        if (m) { $('cWatts').value = m[1]; $('cWatts').disabled = true; }
        else { $('cWatts').disabled = false; }
      }
      $('cModelo').addEventListener('change', function(){ aplicarModelo(); calcular(); guardarCfg(); });
      aplicarModelo();
      calcular();

      window.addEventListener('ptools:moneda', function(e){
        MONEDA.s = e.detail.s; MONEDA.d = e.detail.d;
        calcular();
      });

      $('btnCargarProducto').addEventListener('click', function(){
        $('p-costo').value = ultimo.costo;
        if (!parseFloat($('p-precio').value)) $('p-precio').value = ultimo.sugerido;
        $('p-costo').style.transition = 'box-shadow .15s ease';
        $('p-costo').style.boxShadow = '0 0 0 3px var(--accent-tinte)';
        setTimeout(function(){ $('p-costo').style.boxShadow = ''; }, 900);
        if (!$('p-nombre').value) $('p-nombre').focus();
      });
    })();
    </script>

    <div class="barra-sup">
      <form class="buscar" method="get">
        <input type="search" name="q" placeholder="Buscar producto..." value="<?php echo htmlspecialchars($q); ?>">
      </form>
      <a class="btn" href="presupuesto.php"><?php echo ui_icono('presupuestos', 16); ?> Nuevo presupuesto</a>
    </div>

    <div class="panel tabla-scroll">
      <?php if (!$productos): ?>
        <div class="vacio">Todavía no tenés productos<?php echo $q ? ' que coincidan con la búsqueda' : ''; ?>.<br>
        Cargá el primero con el formulario de arriba, o guardá una pieza desde la calculadora de un presupuesto.</div>
      <?php else: ?>
      <table>
        <thead><tr><th>Producto</th><th>Descripción</th><th class="num">Costo</th><th class="num">Precio</th><th class="num">Ganancia</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($productos as $p): $g = $p['precio'] - $p['costo']; ?>
          <tr>
            <td><strong><?php echo htmlspecialchars($p['nombre']); ?></strong></td>
            <td style="color:var(--txt-2)"><?php echo htmlspecialchars(mb_strimwidth($p['descripcion'] ?? '', 0, 70, '…')); ?></td>
            <td class="num" style="color:var(--txt-2)"><?php echo taller_precio($p['costo']); ?></td>
            <td class="num"><strong><?php echo taller_precio($p['precio']); ?></strong></td>
            <td class="num"><span class="margen-chip" style="color:<?php echo $g >= 0 ? 'var(--ok)' : 'var(--bad)'; ?>">
              <?php echo taller_precio($g); ?></span></td>
            <td>
              <div class="acciones">
                <a class="btn chico sec" href="productos.php?editar=<?php echo (int) $p['id']; ?>">Editar</a>
                <form method="post" onsubmit="return confirm('¿Eliminar «<?php echo htmlspecialchars($p['nombre'], ENT_QUOTES); ?>»?');">
                  <input type="hidden" name="csrf" value="<?php echo com_csrf(); ?>">
                  <input type="hidden" name="accion" value="eliminar">
                  <input type="hidden" name="producto_id" value="<?php echo (int) $p['id']; ?>">
                  <button class="btn chico peligro" type="submit">Eliminar</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
<?php ui_panel_fin(); ?>
