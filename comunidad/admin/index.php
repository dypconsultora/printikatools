<?php
/**
 * Panel de administración: resumen del negocio de un vistazo
 * + emails captados por el popup del cotizador.
 */
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/ui.php';
require_once __DIR__ . '/../inc/taller.php';

requerir_admin();
$yo = usuario_actual();
taller_migrar();
$db = com_db();

// Export CSV de emails captados
if (isset($_GET['csv_emails'])) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="emails-novedades.csv"');
    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Email', 'Fecha'], ';', '"', '');
    foreach ($db->query('SELECT email, creado_en FROM novedades_emails ORDER BY creado_en DESC') as $r) {
        fputcsv($out, [$r['email'], $r['creado_en']], ';', '"', '');
    }
    fclose($out);
    exit;
}

$tot = fn($sql) => (int) $db->query($sql)->fetch()['c'];
$usuarios_tot = $tot('SELECT COUNT(*) c FROM usuarios');
$pagos = $tot("SELECT COUNT(DISTINCT usuario_id) c FROM suscripciones
               WHERE estado='activa' AND (hasta IS NULL OR hasta >= CURDATE())");
$admins = $tot("SELECT COUNT(*) c FROM usuarios WHERE rol='admin'");
$gratis = max(0, $usuarios_tot - $pagos - $admins);
$por_vencer = $db->query("SELECT u.nombre, u.email, s.plan, s.hasta FROM suscripciones s
                          JOIN usuarios u ON u.id = s.usuario_id
                          WHERE s.estado='activa' AND s.hasta BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                          ORDER BY s.hasta")->fetchAll();
$ultimos = $db->query('SELECT nombre, email, creado_en FROM usuarios ORDER BY creado_en DESC LIMIT 6')->fetchAll();
$emails = $db->query('SELECT email, creado_en FROM novedades_emails ORDER BY creado_en DESC LIMIT 12')->fetchAll();
$emails_tot = $tot('SELECT COUNT(*) c FROM novedades_emails');
$stl_tot = $tot('SELECT COUNT(*) c FROM stl_items WHERE publicado=1');

ui_panel_inicio('Panel', $yo, 'Panel', '../');
?>
    <style>.contenido{max-width:none}</style>
    <h1>Panel de administración</h1>
    <p class="bajada">El estado de Printika Tools de un vistazo.</p>

    <style>
      .kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-bottom:16px}
      .kpi{background:var(--surface);border:1px solid var(--bd-suave);border-radius:var(--radio-g);padding:18px 20px}
      .kpi small{display:block;font-size:11px;font-weight:600;letter-spacing:.07em;text-transform:uppercase;
                 color:var(--txt-3);margin-bottom:6px}
      .kpi b{font-size:28px;font-weight:700;font-variant-numeric:tabular-nums}
      .dos{display:grid;grid-template-columns:1fr 1fr;gap:14px;align-items:start}
      .caja{background:var(--surface);border:1px solid var(--bd-suave);border-radius:var(--radio-g);padding:18px 20px}
      .caja h2{font-size:15px;font-weight:600;margin-bottom:10px;display:flex;justify-content:space-between;align-items:center}
      .caja h2 a{font-size:12.5px;font-weight:500}
      .caja table{width:100%;border-collapse:collapse;font-size:13px}
      .caja td{padding:8px 4px;border-bottom:1px solid var(--bd-suave);vertical-align:middle}
      .caja tr:last-child td{border-bottom:none}
      .caja td.sec{color:var(--txt-3);white-space:nowrap;text-align:right}
      .caja .nada{font-size:13px;color:var(--txt-3);padding:14px 0}
      @media (max-width:1000px){ .dos{grid-template-columns:1fr} }
    </style>

    <div class="kpis">
      <div class="kpi"><small>Usuarios totales</small><b><?php echo $usuarios_tot; ?></b></div>
      <div class="kpi"><small>Planes pagos activos</small><b style="color:var(--ok)"><?php echo $pagos; ?></b></div>
      <div class="kpi"><small>Plan gratuito</small><b><?php echo $gratis; ?></b></div>
      <div class="kpi"><small>STL publicados</small><b><?php echo $stl_tot; ?></b></div>
      <div class="kpi"><small>Emails captados</small><b style="color:var(--accent)"><?php echo $emails_tot; ?></b></div>
    </div>

    <div class="dos">
      <div class="caja">
        <h2>Vencen esta semana <a href="suscripciones.php">Ver suscripciones</a></h2>
        <?php if (!$por_vencer): ?><p class="nada">Ninguna suscripción vence en los próximos 7 días.</p>
        <?php else: ?>
          <table><?php foreach ($por_vencer as $s): ?>
            <tr><td><strong><?php echo htmlspecialchars($s['nombre']); ?></strong><br>
                <span style="color:var(--txt-3)"><?php echo htmlspecialchars($s['email']); ?></span></td>
              <td class="sec"><?php echo $s['plan'] === 'anual' ? 'Anual' : 'Mensual'; ?>
                · vence <?php echo date('d/m', strtotime($s['hasta'])); ?></td></tr>
          <?php endforeach; ?></table>
        <?php endif; ?>
      </div>

      <div class="caja">
        <h2>Últimos registrados</h2>
        <?php if (!$ultimos): ?><p class="nada">Todavía no hay usuarios.</p>
        <?php else: ?>
          <table><?php foreach ($ultimos as $r): ?>
            <tr><td><strong><?php echo htmlspecialchars($r['nombre']); ?></strong><br>
                <span style="color:var(--txt-3)"><?php echo htmlspecialchars($r['email']); ?></span></td>
              <td class="sec"><?php echo date('d/m/y', strtotime($r['creado_en'])); ?></td></tr>
          <?php endforeach; ?></table>
        <?php endif; ?>
      </div>

      <div class="caja">
        <h2>Emails captados (cotizador)
          <?php if ($emails_tot): ?><a href="index.php?csv_emails=1">Exportar CSV</a><?php endif; ?></h2>
        <?php if (!$emails): ?><p class="nada">Cuando alguien deje su email en el popup del cotizador, aparece acá.</p>
        <?php else: ?>
          <table><?php foreach ($emails as $e): ?>
            <tr><td><?php echo htmlspecialchars($e['email']); ?></td>
              <td class="sec"><?php echo date('d/m/y', strtotime($e['creado_en'])); ?></td></tr>
          <?php endforeach; ?></table>
        <?php endif; ?>
      </div>
    </div>
<?php ui_panel_fin(); ?>
