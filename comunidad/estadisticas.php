<?php
/**
 * Estadísticas: ganancia, ingresos y gastos del taller, mes a mes,
 * con el gráfico de los últimos 6 meses.
 */
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/ui.php';
require_once __DIR__ . '/inc/taller.php';

requerir_miembro();
$u = usuario_actual();
taller_migrar();
$uid = (int) $u['id'];

$mes_param = preg_match('/^\d{4}-\d{2}$/', $_GET['mes'] ?? '') ? $_GET['mes'] : date('Y-m');
[$anio, $mes] = array_map('intval', explode('-', $mes_param));

[$ingresos, $gastos, $ci, $cg] = taller_resumen_mes($uid, $anio, $mes);
$ganancia = $ingresos - $gastos;

// Últimos 6 meses terminando en el mes elegido
$serie = [];
for ($i = 5; $i >= 0; $i--) {
    $t = strtotime("-{$i} month", mktime(0, 0, 0, $mes, 1, $anio));
    [$si, $sg] = taller_resumen_mes($uid, (int) date('Y', $t), (int) date('n', $t));
    $serie[] = ['ts' => $t, 'ingresos' => $si, 'gastos' => $sg];
}
$maximo = max(1, max(array_merge(array_column($serie, 'ingresos'), array_column($serie, 'gastos'))));

// Exportar CSV: resumen de los últimos 12 meses
if (isset($_GET['csv'])) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="estadisticas-' . $mes_param . '.csv"');
    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Mes', 'Ingresos', 'Gastos', 'Ganancia'], ';', '"', '');
    for ($i = 11; $i >= 0; $i--) {
        $t = strtotime("-{$i} month", mktime(0, 0, 0, $mes, 1, $anio));
        [$si, $sg] = taller_resumen_mes($uid, (int) date('Y', $t), (int) date('n', $t));
        fputcsv($out, [TALLER_MESES[(int) date('n', $t)] . ' ' . date('Y', $t),
                       number_format($si, 2, ',', ''), number_format($sg, 2, ',', ''),
                       number_format($si - $sg, 2, ',', '')], ';', '"', '');
    }
    fclose($out);
    exit;
}

ui_panel_inicio('Estadísticas', $u, 'Estadísticas');
?>
    <style>.contenido{max-width:none}</style>
    <h1>Estadísticas</h1>
    <p class="bajada">Cómo viene tu taller: ingresos, gastos y ganancia, mes a mes.</p>

    <?php taller_nav_mes('estadisticas.php', $anio, $mes); ?>

    <style>
      .ganancia-caja{background:var(--surface);border:1px solid var(--bd-suave);border-radius:var(--radio-g);
              padding:22px 24px;margin-bottom:14px}
      .ganancia-caja small{display:block;font-size:11px;font-weight:600;letter-spacing:.07em;
              text-transform:uppercase;color:var(--txt-3);margin-bottom:8px}
      .ganancia-caja b{font-size:40px;font-weight:700;font-variant-numeric:tabular-nums;letter-spacing:-.02em}
      .duo{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px}
      .res-caja{background:var(--surface);border:1px solid var(--bd-suave);border-radius:var(--radio-g);padding:18px 20px}
      .res-caja .cab{display:flex;align-items:center;gap:10px;margin-bottom:10px}
      .res-caja .cab i{display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;
              border-radius:8px;font-style:normal;font-weight:700}
      .res-caja.ing .cab i{background:var(--ok-tinte);color:var(--ok)}
      .res-caja.gas .cab i{background:var(--bad-tinte);color:var(--bad)}
      .res-caja .cab small{font-size:11px;font-weight:600;letter-spacing:.07em;text-transform:uppercase;color:var(--txt-3)}
      .res-caja b{font-size:26px;font-weight:700;font-variant-numeric:tabular-nums}
      .res-caja .sub{font-size:12.5px;color:var(--txt-3);margin-top:2px}
      .graf-caja{background:var(--surface);border:1px solid var(--bd-suave);border-radius:var(--radio-g);padding:22px 24px}
      .graf-caja h2{font-size:16px;font-weight:700;margin-bottom:12px}
      .leyenda{display:flex;gap:18px;font-size:13px;color:var(--txt-2);margin-bottom:18px}
      .leyenda i{display:inline-block;width:11px;height:11px;border-radius:3px;margin-right:7px;font-style:normal}
      .leyenda .vi{background:var(--ok)} .leyenda .vg{background:var(--bad)}
      .grafico{display:flex;align-items:flex-end;gap:4%;height:220px;border-bottom:1px solid var(--bd);
              padding:0 8px;margin-bottom:8px}
      .mes-g{flex:1;display:flex;align-items:flex-end;justify-content:center;gap:6px;height:100%}
      .mes-g i{display:block;width:26px;max-width:40%;border-radius:4px 4px 0 0;font-style:normal;min-height:2px}
      .mes-g .bi{background:var(--ok)} .mes-g .bg{background:var(--bad)}
      .ejes{display:flex;gap:4%;padding:0 8px;margin-bottom:20px}
      .ejes span{flex:1;text-align:center;font-size:12px;color:var(--txt-3)}
      table{width:100%;border-collapse:collapse;font-size:13.5px}
      th,td{padding:12px 4px;text-align:left;border-bottom:1px solid var(--bd-suave)}
      th{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.07em;color:var(--txt-3)}
      tr:last-child td{border-bottom:none}
      td.num,th.num{text-align:right;font-variant-numeric:tabular-nums;white-space:nowrap}
      @media (max-width:800px){ .duo{grid-template-columns:1fr} .ganancia-caja b{font-size:30px} }
    </style>

    <div class="ganancia-caja">
      <small>Ganancia de <?php echo TALLER_MESES[$mes] . ' ' . $anio; ?></small>
      <b style="color:<?php echo $ganancia >= 0 ? 'var(--ok)' : 'var(--bad)'; ?>"><?php echo taller_precio($ganancia); ?></b>
    </div>

    <div class="duo">
      <div class="res-caja ing">
        <div class="cab"><i>↗</i><small>Ingresos</small></div>
        <b><?php echo taller_precio($ingresos); ?></b>
        <div class="sub"><?php echo $ci; ?> movimiento<?php echo $ci === 1 ? '' : 's'; ?></div>
      </div>
      <div class="res-caja gas">
        <div class="cab"><i>↘</i><small>Gastos</small></div>
        <b><?php echo taller_precio($gastos); ?></b>
        <div class="sub"><?php echo $cg; ?> movimiento<?php echo $cg === 1 ? '' : 's'; ?></div>
      </div>
    </div>

    <div class="graf-caja">
      <h2>Últimos 6 meses</h2>
      <div class="leyenda"><span><i class="vi"></i>Ingresos</span><span><i class="vg"></i>Gastos</span></div>
      <div class="grafico">
        <?php foreach ($serie as $p): ?>
          <div class="mes-g" title="<?php echo TALLER_MESES[(int) date('n', $p['ts'])] . ': '
              . taller_precio($p['ingresos']) . ' / ' . taller_precio($p['gastos']); ?>">
            <i class="bi" style="height:<?php echo round($p['ingresos'] / $maximo * 100, 1); ?>%"></i>
            <i class="bg" style="height:<?php echo round($p['gastos'] / $maximo * 100, 1); ?>%"></i>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="ejes">
        <?php foreach ($serie as $p): ?>
          <span><?php echo strtolower(mb_substr(TALLER_MESES[(int) date('n', $p['ts'])], 0, 3)) . " '" . date('y', $p['ts']); ?></span>
        <?php endforeach; ?>
      </div>

      <table>
        <thead><tr><th>Mes</th><th class="num">Ingresos</th><th class="num">Gastos</th><th class="num">Ganancia</th></tr></thead>
        <tbody>
        <?php foreach ($serie as $p): $g = $p['ingresos'] - $p['gastos']; ?>
          <tr>
            <td><a href="ventas.php?mes=<?php echo date('Y-m', $p['ts']); ?>" style="color:var(--txt)">
              <?php echo TALLER_MESES[(int) date('n', $p['ts'])] . ' ' . date('Y', $p['ts']); ?></a></td>
            <td class="num" style="color:var(--ok)"><?php echo taller_precio($p['ingresos']); ?></td>
            <td class="num" style="color:var(--bad)"><?php echo taller_precio($p['gastos']); ?></td>
            <td class="num" style="color:<?php echo $g >= 0 ? 'var(--ok)' : 'var(--bad)'; ?>">
              <strong><?php echo taller_precio($g); ?></strong></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
<?php ui_panel_fin(); ?>
