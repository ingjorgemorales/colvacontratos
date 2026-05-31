<?php
$k = $kpis ?? [];
$fmtMoney = static fn($value): string => '$ ' . number_format((float)$value, 0, ',', '.');
$severityClass = static function (string $severity): string {
    return [
        'critica' => 'risk-danger',
        'alta' => 'risk-warning',
        'media' => 'status-badge',
        'baja' => 'provider-active',
    ][$severity] ?? 'provider-inactive';
};
$maxArea = 1;
foreach (($byArea ?? []) as $row) {
    $maxArea = max($maxArea, (int)($row['total'] ?? 0));
}
$totalContracts = max(1, (int)($k['total'] ?? 1));
?>

<section class="intelligence-modern">
  <div class="module-hero intelligence-hero">
    <div>
      <span class="section-eyebrow">Motor inteligente</span>
      <h1>Inteligencia KPI</h1>
      <p>Control contractual, riesgos, alertas, insights y automatizaci&oacute;n de seguimiento.</p>
    </div>
    <form method="post" action="index.php?r=intelligence.run" class="module-actions">
      <button class="btn btn-primary" type="submit"><i class="bi bi-cpu"></i> Ejecutar motor inteligente</button>
    </form>
  </div>

  <div class="intelligence-kpi-grid">
    <article><i class="bi bi-files"></i><span>Total contratos</span><strong><?= (int)($k['total'] ?? 0) ?></strong><em>Contratos registrados</em></article>
    <article class="tone-amber"><i class="bi bi-hourglass-split"></i><span>Por vencer 30 d&iacute;as</span><strong><?= (int)($k['due30'] ?? 0) ?></strong><em>Requieren gesti&oacute;n</em></article>
    <article class="tone-red"><i class="bi bi-exclamation-triangle"></i><span>Vencidos</span><strong><?= (int)($k['expired'] ?? 0) ?></strong><em>Riesgo cr&iacute;tico</em></article>
    <article><i class="bi bi-folder-x"></i><span>Sin documentos</span><strong><?= (int)($k['without_docs'] ?? 0) ?></strong><em>Riesgo documental</em></article>
    <article class="tone-green"><i class="bi bi-graph-up-arrow"></i><span>Ejecuci&oacute;n</span><strong><?= number_format((float)($k['execution_pct'] ?? 0), 2, ',', '.') ?>%</strong><em><?= $fmtMoney($k['executed_value'] ?? 0) ?></em></article>
    <article class="tone-slate"><i class="bi bi-activity"></i><span>Score riesgo</span><strong><?= (int)($k['risk_score'] ?? 0) ?>/100</strong><em>Sem&aacute;foro inteligente</em></article>
  </div>

  <div class="intelligence-layout">
    <section class="intelligence-panel wide">
      <div class="table-card-head">
        <div><h2>Insights abiertos</h2><p>Hallazgos generados por el motor inteligente.</p></div>
        <span class="filter-pill"><?= number_format(count($insights ?? []), 0, ',', '.') ?> insights</span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 modern-table intelligence-table">
          <thead><tr><th>Severidad</th><th>Insight</th><th>Mensaje</th><th>Fecha</th><th class="text-end">Acci&oacute;n</th></tr></thead>
          <tbody>
            <?php foreach (($insights ?? []) as $insight): ?>
              <?php $severity = (string)($insight['severity'] ?? ''); ?>
              <tr>
                <td><span class="risk-badge <?= $severityClass($severity) ?>"><?= htmlspecialchars(strtoupper($severity), ENT_QUOTES, 'UTF-8') ?></span></td>
                <td><strong><?= htmlspecialchars($insight['title'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong><small><?= htmlspecialchars($insight['insight_type'] ?? '', ENT_QUOTES, 'UTF-8') ?></small></td>
                <td><?= htmlspecialchars($insight['message'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($insight['created_at'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                <td class="text-end"><?php if (!empty($insight['action_url'])): ?><a class="icon-action" href="<?= htmlspecialchars($insight['action_url'], ENT_QUOTES, 'UTF-8') ?>" title="Ver"><i class="bi bi-eye"></i></a><?php endif; ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($insights)): ?><tr><td colspan="5"><div class="empty-state">Sin insights abiertos. Ejecuta el motor para recalcular.</div></td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>

    <aside class="intelligence-side">
      <section class="intelligence-panel">
        <div class="panel-title-row"><h2>Riesgo por categor&iacute;a</h2></div>
        <div class="report-bars">
          <?php foreach (($byRisk ?? []) as $row): $pct = round(((int)($row['total'] ?? 0) / $totalContracts) * 100); ?>
            <div class="report-bar-row"><div class="report-bar-label"><?= htmlspecialchars($row['label'] ?? '', ENT_QUOTES, 'UTF-8') ?></div><div class="report-bar-bg"><span style="width:<?= max(3, min(100, $pct)) ?>%"></span></div><strong><?= (int)($row['total'] ?? 0) ?></strong></div>
          <?php endforeach; ?>
          <?php if (empty($byRisk)): ?><div class="empty-state">Sin datos de riesgo.</div><?php endif; ?>
        </div>
      </section>

      <section class="intelligence-panel">
        <div class="panel-title-row"><h2>Contratos por &aacute;rea</h2></div>
        <div class="report-bars">
          <?php foreach (($byArea ?? []) as $row): $pct = round(((int)($row['total'] ?? 0) / $maxArea) * 100); ?>
            <div class="report-bar-row"><div class="report-bar-label"><?= htmlspecialchars($row['label'] ?? '', ENT_QUOTES, 'UTF-8') ?></div><div class="report-bar-bg"><span style="width:<?= max(3, min(100, $pct)) ?>%"></span></div><strong><?= (int)($row['total'] ?? 0) ?></strong></div>
          <?php endforeach; ?>
          <?php if (empty($byArea)): ?><div class="empty-state">Sin datos por &aacute;rea.</div><?php endif; ?>
        </div>
      </section>
    </aside>
  </div>

  <section class="intelligence-panel">
    <div class="table-card-head">
      <div><h2>&Uacute;ltimas notificaciones</h2><p>Historial reciente de comunicaciones generadas.</p></div>
      <span class="filter-pill"><?= number_format(count($logs ?? []), 0, ',', '.') ?> registros</span>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0 modern-table intelligence-table">
        <thead><tr><th>Contrato</th><th>Tipo</th><th>Canal</th><th>Destinatario</th><th>Estado</th><th>Fecha</th></tr></thead>
        <tbody>
          <?php foreach (($logs ?? []) as $log): ?>
            <?php $sent = ($log['status'] ?? '') === 'sent'; ?>
            <tr>
              <td><strong><?= htmlspecialchars($log['number'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong></td>
              <td><?= htmlspecialchars($log['alert_type'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars($log['channel'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars($log['recipient'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
              <td><span class="status-badge <?= $sent ? 'provider-active' : 'risk-warning' ?>"><?= htmlspecialchars($log['status'] ?? '', ENT_QUOTES, 'UTF-8') ?></span></td>
              <td><?= htmlspecialchars($log['sent_at'] ?? $log['created_at'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($logs)): ?><tr><td colspan="6"><div class="empty-state">A&uacute;n no hay registros de notificaci&oacute;n.</div></td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>
</section>
