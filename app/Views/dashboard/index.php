<?php
$total = (int)($stats['total'] ?? 0);
$active = (int)($stats['active'] ?? 0);
$expiring = (int)($stats['expiring'] ?? 0);
$expired = (int)($stats['expired'] ?? 0);
$critical = (int)($stats['critical'] ?? 0);
$providers = (int)($stats['providers'] ?? 0);
$documents = (int)($stats['documents'] ?? 0);
$value = (float)($stats['value'] ?? 0);
$executed = (float)($stats['executed'] ?? 0);
$executionGlobal = $value > 0 ? round(($executed / $value) * 100, 2) : (float)($stats['avg_execution'] ?? 0);
$available = max(0, $value - $executed);
$risk = $riskSummary ?? [];
$todayLabel = date('d/m/Y H:i');

$money = fn($n) => '$' . number_format((float)$n, 0, ',', '.');
$pct = fn($n) => number_format((float)$n, 2, ',', '.') . '%';
$shortMoney = function(float $n): string {
    $abs = abs($n);
    if ($abs >= 1000000000000) return '$' . number_format($n / 1000000000000, 2, ',', '.') . ' B';
    if ($abs >= 1000000000) return '$' . number_format($n / 1000000000, 2, ',', '.') . ' MM';
    if ($abs >= 1000000) return '$' . number_format($n / 1000000, 1, ',', '.') . ' M';
    return '$' . number_format($n, 0, ',', '.');
};

$kpis = [
  ['label' => 'Contratos', 'value' => $total, 'hint' => 'Total registrados', 'icon' => 'bi-file-earmark-text', 'tone' => 'blue', 'url' => 'index.php?r=contracts'],
  ['label' => 'En ejecucion', 'value' => $active, 'hint' => ($total ? round(($active / $total) * 100) : 0) . '% del portafolio', 'icon' => 'bi-arrow-repeat', 'tone' => 'green', 'url' => 'index.php?r=contracts'],
  ['label' => 'Criticos', 'value' => $critical, 'hint' => 'Vencen en 30 dias', 'icon' => 'bi-exclamation-octagon', 'tone' => 'red', 'url' => 'index.php?r=reports&risk=rojo'],
  ['label' => 'Por vencer', 'value' => $expiring, 'hint' => 'Proximos 90 dias', 'icon' => 'bi-bell', 'tone' => 'amber', 'url' => 'index.php?r=reports&risk=amarillo'],
  ['label' => 'Vencidos', 'value' => $expired, 'hint' => 'Fecha final vencida', 'icon' => 'bi-calendar-x', 'tone' => 'red', 'url' => 'index.php?r=reports&risk=vencido'],
  ['label' => 'Proveedores', 'value' => $providers, 'hint' => 'Aliados registrados', 'icon' => 'bi-building', 'tone' => 'blue', 'url' => 'index.php?r=providers'],
  ['label' => 'Documentos', 'value' => $documents, 'hint' => 'Soportes cargados', 'icon' => 'bi-folder2-open', 'tone' => 'slate', 'url' => 'index.php?r=documents'],
  ['label' => 'Ejecucion', 'value' => $pct($executionGlobal), 'hint' => 'Sobre valor total', 'icon' => 'bi-graph-up-arrow', 'tone' => 'green', 'url' => 'index.php?r=finance'],
];

$riskItems = [
  ['label' => 'Vencidos', 'value' => (int)($risk['vencidos'] ?? 0), 'tone' => 'danger', 'url' => 'index.php?r=reports&risk=vencido'],
  ['label' => '0 a 30 dias', 'value' => (int)($risk['rojo'] ?? 0), 'tone' => 'danger', 'url' => 'index.php?r=reports&risk=rojo'],
  ['label' => '31 a 90 dias', 'value' => (int)($risk['amarillo'] ?? 0), 'tone' => 'warning', 'url' => 'index.php?r=reports&risk=amarillo'],
  ['label' => 'Mas de 90 dias', 'value' => (int)($risk['verde'] ?? 0), 'tone' => 'success', 'url' => 'index.php?r=reports&risk=verde'],
  ['label' => 'Sin fecha', 'value' => (int)($risk['sin_fecha'] ?? 0), 'tone' => 'secondary', 'url' => 'index.php?r=reports'],
];

$statusLabels = array_map(fn($r) => $r['status'] ?? 'Sin estado', $byStatus ?? []);
$statusData = array_map(fn($r) => (int)($r['total'] ?? 0), $byStatus ?? []);
$expenseLabels = array_map(fn($r) => $r['label'] ?? 'Sin tipo', $byExpenseType ?? []);
$expenseData = array_map(fn($r) => (float)($r['value'] ?? 0), $byExpenseType ?? []);
$selectionLabels = array_map(fn($r) => $r['label'] ?? 'Sin modalidad', $bySelectionModality ?? []);
$selectionData = array_map(fn($r) => (int)($r['total'] ?? 0), $bySelectionModality ?? []);
$areaLabels = array_map(fn($r) => $r['area'] ?? 'Sin area', $byArea ?? []);
$areaData = array_map(fn($r) => (int)($r['total'] ?? 0), $byArea ?? []);
?>

<section class="dashboard-modern">
  <div class="dashboard-hero">
    <div>
      <span class="section-eyebrow">Panel ejecutivo</span>
      <h1>Control contractual</h1>
      <p>Vista consolidada de contratos, ejecucion, vencimientos y gestion documental.</p>
    </div>
    <div class="dashboard-actions">
      <a href="index.php?r=contracts.create" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Nuevo contrato</a>
      <a href="index.php?r=reports" class="btn btn-light"><i class="bi bi-file-earmark-excel"></i> Reportes</a>
      <a href="index.php?r=alerts" class="btn btn-outline-primary"><i class="bi bi-bell"></i> Alertas</a>
    </div>
  </div>

  <div class="dashboard-summary">
    <div class="summary-card summary-main">
      <span>Valor total del portafolio</span>
      <strong><?= htmlspecialchars($shortMoney($value)) ?></strong>
      <em><?= $money($value) ?></em>
    </div>
    <div class="summary-card">
      <span>Ejecutado</span>
      <strong><?= htmlspecialchars($shortMoney($executed)) ?></strong>
      <em><?= $pct($executionGlobal) ?></em>
    </div>
    <div class="summary-card">
      <span>Saldo estimado</span>
      <strong><?= htmlspecialchars($shortMoney($available)) ?></strong>
      <em>Actualizado <?= htmlspecialchars($todayLabel) ?></em>
    </div>
  </div>

  <div class="kpi-modern-grid">
    <?php foreach($kpis as $item): ?>
      <a class="kpi-modern-card tone-<?= htmlspecialchars($item['tone']) ?>" href="<?= htmlspecialchars($item['url']) ?>">
        <span class="kpi-modern-icon"><i class="bi <?= htmlspecialchars($item['icon']) ?>"></i></span>
        <span class="kpi-modern-label"><?= htmlspecialchars($item['label']) ?></span>
        <strong><?= htmlspecialchars((string)$item['value']) ?></strong>
        <em><?= htmlspecialchars($item['hint']) ?></em>
      </a>
    <?php endforeach; ?>
  </div>

  <div class="dashboard-grid-main">
    <article class="dashboard-panel risk-panel">
      <div class="panel-title-row">
        <div>
          <span class="section-eyebrow">Riesgo</span>
          <h2>Semaforo contractual</h2>
        </div>
        <a href="index.php?r=reports" class="panel-link">Ver reporte</a>
      </div>
      <div class="risk-list">
        <?php foreach($riskItems as $item): ?>
          <a class="risk-row risk-<?= htmlspecialchars($item['tone']) ?>" href="<?= htmlspecialchars($item['url']) ?>">
            <span><?= htmlspecialchars($item['label']) ?></span>
            <strong><?= (int)$item['value'] ?></strong>
          </a>
        <?php endforeach; ?>
      </div>
    </article>

    <article class="dashboard-panel finance-panel">
      <div class="panel-title-row">
        <div>
          <span class="section-eyebrow">Finanzas</span>
          <h2>Resumen financiero</h2>
        </div>
        <span class="execution-pill"><?= $pct($executionGlobal) ?></span>
      </div>
      <div class="execution-bar" aria-label="Ejecucion global">
        <span style="width: <?= min(100, max(0, $executionGlobal)) ?>%"></span>
      </div>
      <dl class="finance-list">
        <div><dt>Valor inicial</dt><dd><?= $money($financialSummary['valor_inicial'] ?? 0) ?></dd></div>
        <div><dt>IVA</dt><dd><?= $money($financialSummary['iva'] ?? 0) ?></dd></div>
        <div><dt>Adiciones</dt><dd><?= $money($financialSummary['adiciones'] ?? 0) ?></dd></div>
        <div><dt>Total</dt><dd><?= $money($financialSummary['valor_total'] ?? 0) ?></dd></div>
        <div><dt>Ejecutado</dt><dd><?= $money($financialSummary['ejecutado'] ?? 0) ?></dd></div>
      </dl>
    </article>

    <article class="dashboard-panel chart-panel-modern">
      <div class="panel-title-row">
        <div>
          <span class="section-eyebrow">Estados</span>
          <h2>Contratos por estado</h2>
        </div>
      </div>
      <div class="chart-frame chart-frame-donut"><canvas id="chartStatus"></canvas></div>
    </article>
  </div>

  <div class="dashboard-chart-grid">
    <article class="dashboard-panel chart-panel-modern">
      <div class="panel-title-row">
        <div>
          <span class="section-eyebrow">Distribucion</span>
          <h2>Por tipo de gasto</h2>
        </div>
      </div>
      <div class="chart-frame"><canvas id="chartExpense"></canvas></div>
    </article>

    <article class="dashboard-panel chart-panel-modern">
      <div class="panel-title-row">
        <div>
          <span class="section-eyebrow">Seleccion</span>
          <h2>Por modalidad</h2>
        </div>
      </div>
      <div class="chart-frame"><canvas id="chartSelection"></canvas></div>
    </article>

    <article class="dashboard-panel chart-panel-modern">
      <div class="panel-title-row">
        <div>
          <span class="section-eyebrow">Areas</span>
          <h2>Contratos por area</h2>
        </div>
      </div>
      <div class="chart-frame"><canvas id="chartArea"></canvas></div>
    </article>
  </div>

  <div class="dashboard-table-grid">
    <article class="dashboard-panel">
      <div class="panel-title-row">
        <div>
          <span class="section-eyebrow">Agenda</span>
          <h2>Proximos vencimientos</h2>
        </div>
        <a href="index.php?r=reports&risk=amarillo" class="panel-link">Ver todos</a>
      </div>
      <div class="table-responsive modern-table-wrap">
        <table class="table modern-table align-middle mb-0">
          <thead><tr><th>Numero</th><th>Proveedor</th><th>Vence</th><th>Dias</th><th>Riesgo</th></tr></thead>
          <tbody>
            <?php foreach(($expiringRows ?? []) as $row): $rl=$row['risk_level'] ?? ''; $badge=$rl==='ROJO'?'danger':($rl==='AMARILLO'?'warning':'success'); ?>
              <tr>
                <td><a href="index.php?r=contracts.show&id=<?= (int)$row['id'] ?>"><?= htmlspecialchars($row['number'] ?? '') ?></a></td>
                <td><?= htmlspecialchars($row['provider_name'] ?? '') ?></td>
                <td><?= htmlspecialchars($row['end_date'] ?? '') ?></td>
                <td><?= (int)($row['days_left'] ?? 0) ?></td>
                <td><span class="badge text-bg-<?= $badge ?>"><?= htmlspecialchars($rl) ?></span></td>
              </tr>
            <?php endforeach; if(empty($expiringRows)): ?>
              <tr><td colspan="5" class="text-muted">No hay vencimientos proximos.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </article>

    <article class="dashboard-panel">
      <div class="panel-title-row">
        <div>
          <span class="section-eyebrow">Actividad</span>
          <h2>Ultimos contratos</h2>
        </div>
        <a href="index.php?r=contracts" class="panel-link">Ver contratos</a>
      </div>
      <div class="table-responsive modern-table-wrap">
        <table class="table modern-table align-middle mb-0">
          <thead><tr><th>Numero</th><th>Contrato</th><th>Estado</th><th>Valor</th></tr></thead>
          <tbody>
            <?php foreach(($recentContracts ?? []) as $row): ?>
              <tr>
                <td><a href="index.php?r=contracts.show&id=<?= (int)$row['id'] ?>"><?= htmlspecialchars($row['number'] ?? '') ?></a></td>
                <td>
                  <strong><?= htmlspecialchars($row['name'] ?? '') ?></strong>
                  <small><?= htmlspecialchars($row['provider_name'] ?? '') ?></small>
                </td>
                <td><?= htmlspecialchars($row['status'] ?? '') ?></td>
                <td><?= $money($row['total_value'] ?? 0) ?></td>
              </tr>
            <?php endforeach; if(empty($recentContracts)): ?>
              <tr><td colspan="4" class="text-muted">Sin registros recientes.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </article>
  </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function(){
  const palette = ['#0b4168','#55b948','#f59e0b','#dc3545','#6d5dfc','#14a6c8','#64748b','#93a4b7'];
  const statusLabels = <?= json_encode($statusLabels, JSON_UNESCAPED_UNICODE) ?>;
  const statusData = <?= json_encode($statusData) ?>;
  const expenseLabels = <?= json_encode($expenseLabels, JSON_UNESCAPED_UNICODE) ?>;
  const expenseData = <?= json_encode($expenseData) ?>;
  const selectionLabels = <?= json_encode($selectionLabels, JSON_UNESCAPED_UNICODE) ?>;
  const selectionData = <?= json_encode($selectionData) ?>;
  const areaLabels = <?= json_encode($areaLabels, JSON_UNESCAPED_UNICODE) ?>;
  const areaData = <?= json_encode($areaData) ?>;

  const baseOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { labels: { boxWidth: 10, usePointStyle: true, font: { size: 11 } } },
      tooltip: { backgroundColor: '#09223d', padding: 10, cornerRadius: 8 }
    }
  };

  const barOptions = {
    ...baseOptions,
    plugins: { ...baseOptions.plugins, legend: { display: false } },
    scales: {
      x: { grid: { display: false }, ticks: { color: '#60758b', font: { size: 11 } } },
      y: { beginAtZero: true, grid: { color: '#edf3f7' }, ticks: { color: '#60758b', font: { size: 11 } } }
    }
  };

  if (window.Chart && document.getElementById('chartStatus')) new Chart(document.getElementById('chartStatus'), {
    type: 'doughnut',
    data: { labels: statusLabels, datasets: [{ data: statusData, backgroundColor: palette, borderWidth: 3, borderColor: '#fff' }] },
    options: { ...baseOptions, cutout: '66%', plugins: { ...baseOptions.plugins, legend: { ...baseOptions.plugins.legend, position: 'bottom' } } }
  });

  if (window.Chart && document.getElementById('chartExpense')) new Chart(document.getElementById('chartExpense'), {
    type: 'bar',
    data: { labels: expenseLabels, datasets: [{ data: expenseData, backgroundColor: '#0b4168', borderRadius: 8, maxBarThickness: 34 }] },
    options: barOptions
  });

  if (window.Chart && document.getElementById('chartSelection')) new Chart(document.getElementById('chartSelection'), {
    type: 'bar',
    data: { labels: selectionLabels, datasets: [{ data: selectionData, backgroundColor: '#55b948', borderRadius: 8, maxBarThickness: 34 }] },
    options: barOptions
  });

  if (window.Chart && document.getElementById('chartArea')) new Chart(document.getElementById('chartArea'), {
    type: 'bar',
    data: { labels: areaLabels, datasets: [{ data: areaData, backgroundColor: '#14a6c8', borderRadius: 8, maxBarThickness: 34 }] },
    options: barOptions
  });
})();
</script>