<?php
$money = static fn($value): string => '$ ' . number_format((float)$value, 0, ',', '.');
$filters = $filters ?? [];
$exportFilters = $filters;
unset($exportFilters['r']);
$exportUrl = function (string $route) use ($exportFilters): string {
    $params = $exportFilters;
    $params['r'] = $route;
    return 'index.php?' . http_build_query($params);
};
$activeTab = $filters['tab'] ?? 'analitica';
$tabUrl = function (string $tab) use ($filters): string {
    $params = $filters;
    $params['r'] = 'reports';
    $params['tab'] = $tab;
    return 'index.php?' . http_build_query($params);
};
$maxTotal = static function (array $rows): float {
    $max = 0;
    foreach ($rows as $row) {
        $max = max($max, (float)($row['total'] ?? 0));
    }
    return $max ?: 1;
};
$maxValue = static function (array $rows): float {
    $max = 0;
    foreach ($rows as $row) {
        $max = max($max, (float)($row['value'] ?? $row['contract_value'] ?? 0));
    }
    return $max ?: 1;
};
$maxScore = static function (array $rows): float {
    $max = 0;
    foreach ($rows as $row) {
        $max = max($max, (float)($row['risk_score'] ?? 0));
    }
    return $max ?: 1;
};
$barRows = static function (array $rows, callable $valueGetter, callable $labelGetter, callable $rightGetter, float $max, ?callable $colorGetter = null) {
    foreach ($rows as $row) {
        $value = (float)$valueGetter($row);
        $width = max(3, min(100, round(($value / max(1, $max)) * 100)));
        $cls = $colorGetter ? ' ' . $colorGetter($row) : '';
        echo '<div class="report-bar-row">';
        echo '<div class="report-bar-label">' . $labelGetter($row) . '</div>';
        echo '<div class="report-bar-bg"><span class="' . trim($cls) . '" style="width:' . $width . '%"></span></div>';
        echo '<strong>' . $rightGetter($row) . '</strong>';
        echo '</div>';
    }
};
// Color de barra según el nivel de riesgo (por el texto de la etiqueta).
$riskBarClass = static function (array $row): string {
    $t = mb_strtolower((string)($row['label'] ?? ''));
    if (str_contains($t, 'venc'))     return 'rb-expired';
    if (str_contains($t, 'rojo'))     return 'rb-danger';
    if (str_contains($t, 'amarillo')) return 'rb-warning';
    if (str_contains($t, 'verde'))    return 'rb-success';
    return '';
};
$riskBadge = static function (string $risk): string {
    $class = ($risk === 'VENCIDO' || $risk === 'ROJO') ? 'risk-danger' : ($risk === 'AMARILLO' ? 'risk-warning' : 'risk-success');
    return '<span class="risk-badge ' . $class . '">' . htmlspecialchars($risk, ENT_QUOTES, 'UTF-8') . '</span>';
};
$tabs = [
    'analitica' => ['Analítica', 'bi-bar-chart-line'],
    'inteligencia' => ['Inteligencia', 'bi-cpu'],
    'contratos' => ['Contratos', 'bi-file-earmark-text'],
    'proveedores' => ['Proveedores', 'bi-building'],
    'financiero' => ['Financiero', 'bi-cash-coin'],
    'documental' => ['Documental', 'bi-folder2-open'],
];
?>

<section class="reports-modern">
  <div class="module-hero reports-hero">
    <div>
      <span class="section-eyebrow">Centro ejecutivo</span>
      <h1>Reportes e inteligencia contractual</h1>
      <p>KPIs, analíticas gerencial, riesgos, reportes operativos y exportación Excel.</p>
    </div>
    <div class="module-actions">
      <a class="btn btn-success" href="<?= $exportUrl('reports.analytics.excel') ?>"><i class="bi bi-file-earmark-bar-graph"></i> Analítica</a>
      <a class="btn btn-outline-success" href="<?= $exportUrl('reports.excel') ?>"><i class="bi bi-file-earmark-excel"></i> Contratos</a>
      <a class="btn btn-outline-success" href="<?= $exportUrl('reports.providers.excel') ?>"><i class="bi bi-building"></i> Proveedores</a>
      <a class="btn btn-outline-success" href="<?= $exportUrl('reports.financial.excel') ?>"><i class="bi bi-cash-coin"></i> Financiero</a>
      <a class="btn btn-outline-success" href="<?= $exportUrl('reports.documents.excel') ?>"><i class="bi bi-folder2-open"></i> Documental</a>
    </div>
  </div>

  <div class="reports-kpi-grid">
    <article><i class="bi bi-files"></i><span>Contratos</span><strong><?= (int)($kpis['total'] ?? 0) ?></strong></article>
    <article><i class="bi bi-cash-stack"></i><span>Valor total</span><strong><?= $money($kpis['valor_total'] ?? 0) ?></strong></article>
    <article><i class="bi bi-graph-up-arrow"></i><span>Ejecutado</span><strong><?= $money($kpis['ejecutado'] ?? 0) ?></strong></article>
    <article><i class="bi bi-wallet2"></i><span>Saldo</span><strong><?= $money($kpis['saldo'] ?? 0) ?></strong></article>
    <article><i class="bi bi-exclamation-triangle"></i><span>Vencidos</span><strong><?= (int)($kpis['vencidos'] ?? 0) ?></strong></article>
    <article><i class="bi bi-lightning-charge"></i><span>Críticos</span><strong><?= (int)($kpis['criticos'] ?? 0) ?></strong></article>
  </div>

  <form method="get" action="index.php" class="filter-panel reports-filter">
    <input type="hidden" name="r" value="reports">
    <input type="hidden" name="tab" value="<?= htmlspecialchars($activeTab, ENT_QUOTES, 'UTF-8') ?>">
    <div class="reports-filter-grid">
      <label><span>Área</span><select name="area_id" class="form-select"><option value="">Todas</option><?php foreach (($areas ?? []) as $area): ?><option value="<?= (int)$area['id'] ?>" <?= (string)($filters['area_id'] ?? '') === (string)$area['id'] ? 'selected' : '' ?>><?= htmlspecialchars($area['name'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></label>
      <label><span>Estado</span><select name="status_id" class="form-select"><option value="">Todos</option><?php foreach (($statuses ?? []) as $status): ?><option value="<?= (int)$status['id'] ?>" <?= (string)($filters['status_id'] ?? '') === (string)$status['id'] ? 'selected' : '' ?>><?= htmlspecialchars($status['name'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></label>
      <label><span>Proveedor</span><select name="provider_id" class="form-select"><option value="">Todos</option><?php foreach (($providers ?? []) as $provider): ?><option value="<?= (int)$provider['id'] ?>" <?= (string)($filters['provider_id'] ?? '') === (string)$provider['id'] ? 'selected' : '' ?>><?= htmlspecialchars($provider['name'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></label>
      <label><span>Riesgo</span><select name="risk" class="form-select"><option value="">Todos</option><?php foreach (['vencido' => 'Vencidos', 'rojo' => 'Rojo 0-30 días', 'amarillo' => 'Amarillo 31-90 días', 'verde' => 'Verde +90 días'] as $key => $label): ?><option value="<?= $key ?>" <?= ($filters['risk'] ?? '') === $key ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></label>
      <label><span>Tipo gasto</span><select name="expense_type_id" class="form-select"><option value="">Todos</option><?php foreach (($expenseTypes ?? []) as $item): ?><option value="<?= (int)$item['id'] ?>" <?= (string)($filters['expense_type_id'] ?? '') === (string)$item['id'] ? 'selected' : '' ?>><?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></label>
      <label><span>Modalidad</span><select name="selection_modality_id" class="form-select"><option value="">Todas</option><?php foreach (($selectionModalities ?? []) as $item): ?><option value="<?= (int)$item['id'] ?>" <?= (string)($filters['selection_modality_id'] ?? '') === (string)$item['id'] ? 'selected' : '' ?>><?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></label>
      <label><span>Vence desde</span><input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($filters['date_from'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></label>
      <label><span>Vence hasta</span><input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($filters['date_to'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></label>
      <label class="wide"><span>Buscar</span><input name="q" class="form-control" value="<?= htmlspecialchars($filters['q'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Número, contrato, proveedor, supervisor, factura u orden"></label>
      <label><span>Tipo contratista</span><select name="tipo_contratista_id" class="form-select"><option value="">Todos</option><?php foreach (($catalogosProveedor['tipo_contratista'] ?? []) as $item): ?><option value="<?= (int)$item['id'] ?>" <?= (string)($filters['tipo_contratista_id'] ?? '') === (string)$item['id'] ? 'selected' : '' ?>><?= htmlspecialchars($item['nombre'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></label>
      <label><span>Clasificación</span><select name="clasificacion_id" class="form-select"><option value="">Todas</option><?php foreach (($catalogosProveedor['clasificacion'] ?? []) as $item): ?><option value="<?= (int)$item['id'] ?>" <?= (string)($filters['clasificacion_id'] ?? '') === (string)$item['id'] ? 'selected' : '' ?>><?= htmlspecialchars($item['nombre'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></label>
      <div class="filter-actions"><button class="btn btn-primary" type="submit"><i class="bi bi-funnel"></i> Aplicar</button><a class="btn btn-light" href="index.php?r=reports"><i class="bi bi-x-lg"></i> Limpiar</a></div>
    </div>
  </form>

  <nav class="reports-tabs" aria-label="Secciones de reportes">
    <?php foreach ($tabs as $key => [$label, $icon]): ?>
      <a class="<?= $activeTab === $key ? 'active' : '' ?>" href="<?= $tabUrl($key) ?>"><i class="bi <?= $icon ?>"></i><span><?= $label ?></span></a>
    <?php endforeach; ?>
  </nav>

  <?php if ($activeTab === 'analitica'): ?>
    <div class="reports-chart-grid">
      <section class="report-panel"><div class="panel-title-row"><h2>Riesgo contractual</h2></div><?php $barRows($analytics['riesgo'] ?? [], fn($r) => (float)($r['total'] ?? 0), fn($r) => htmlspecialchars($r['label'] ?? '', ENT_QUOTES, 'UTF-8'), fn($r) => (int)($r['total'] ?? 0), $maxTotal($analytics['riesgo'] ?? []), $riskBarClass); ?></section>
      <section class="report-panel"><div class="panel-title-row"><h2>Contratos por Área</h2></div><?php $barRows($analytics['areas'] ?? [], fn($r) => (float)($r['value'] ?? 0), fn($r) => htmlspecialchars($r['label'] ?? '', ENT_QUOTES, 'UTF-8'), fn($r) => $money($r['value'] ?? 0), $maxValue($analytics['areas'] ?? [])); ?></section>
      <section class="report-panel"><div class="panel-title-row"><h2>Proveedores por clasificación</h2></div><?php $barRows($analytics['proveedores_clasificacion'] ?? [], fn($r) => (float)($r['total'] ?? 0), fn($r) => htmlspecialchars($r['label'] ?? '', ENT_QUOTES, 'UTF-8'), fn($r) => (int)($r['total'] ?? 0), $maxTotal($analytics['proveedores_clasificacion'] ?? [])); ?></section>
      <section class="report-panel"><div class="panel-title-row"><h2>Tipo contratista</h2></div><?php $barRows($analytics['proveedores_tipo'] ?? [], fn($r) => (float)($r['total'] ?? 0), fn($r) => htmlspecialchars($r['label'] ?? '', ENT_QUOTES, 'UTF-8'), fn($r) => (int)($r['total'] ?? 0), $maxTotal($analytics['proveedores_tipo'] ?? [])); ?></section>
      <section class="report-panel"><div class="panel-title-row"><h2>Top proveedores por valor</h2></div><?php $barRows($analytics['financiero_proveedor'] ?? [], fn($r) => (float)($r['value'] ?? 0), fn($r) => htmlspecialchars($r['label'] ?? '', ENT_QUOTES, 'UTF-8'), fn($r) => $money($r['value'] ?? 0), $maxValue($analytics['financiero_proveedor'] ?? [])); ?></section>
      <section class="report-panel"><div class="panel-title-row"><h2>Documentos por estado</h2></div><?php $barRows($analytics['documentos_estado'] ?? [], fn($r) => (float)($r['total'] ?? 0), fn($r) => htmlspecialchars($r['label'] ?? '', ENT_QUOTES, 'UTF-8'), fn($r) => (int)($r['total'] ?? 0), $maxTotal($analytics['documentos_estado'] ?? [])); ?></section>
    </div>

  <?php elseif ($activeTab === 'inteligencia'): ?>
    <?php $summary = $intelligence['summary'] ?? []; ?>
    <div class="reports-kpi-grid compact">
      <article><i class="bi bi-files"></i><span>Contratos</span><strong><?= (int)($summary['contracts'] ?? 0) ?></strong></article>
      <article><i class="bi bi-building"></i><span>Proveedores activos</span><strong><?= (int)($summary['providers'] ?? 0) ?></strong></article>
      <article><i class="bi bi-exclamation-circle"></i><span>Vencidos</span><strong><?= (int)($summary['expired'] ?? 0) ?></strong></article>
      <article><i class="bi bi-lightning-charge"></i><span>Rojo 0-30 días</span><strong><?= (int)($summary['red'] ?? 0) ?></strong></article>
      <article><i class="bi bi-folder-x"></i><span>Pendientes docs</span><strong><?= (int)($summary['pendingDocs'] ?? 0) ?></strong></article>
      <article><i class="bi bi-cash-stack"></i><span>Valor total</span><strong><?= $money($summary['value'] ?? 0) ?></strong></article>
    </div>
    <div class="reports-chart-grid">
      <section class="report-panel wide-panel"><div class="panel-title-row"><h2>Alertas de vencimiento 120 días</h2><a class="panel-link" href="<?= $exportUrl('reports.intelligence.excel') ?>">Exportar</a></div><div class="table-responsive"><table class="table table-hover align-middle modern-table"><thead><tr><th>Contrato</th><th>Proveedor</th><th>Fin</th><th>Días</th><th>Riesgo</th></tr></thead><tbody><?php foreach (($intelligence['expiring'] ?? []) as $row): ?><tr><td><strong><?= htmlspecialchars($row['number'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong><small><?= htmlspecialchars($row['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></small></td><td><?= htmlspecialchars($row['provider_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($row['end_effective'] ?? '', ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars((string)($row['days_left'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td><td><?= $riskBadge($row['risk_level'] ?? '') ?></td></tr><?php endforeach; if (empty($intelligence['expiring'])): ?><tr><td colspan="5"><div class="empty-state">Sin alertas.</div></td></tr><?php endif; ?></tbody></table></div></section>
      <section class="report-panel"><div class="panel-title-row"><h2>Riesgo por proveedor</h2></div><?php $barRows($intelligence['provider_risk'] ?? [], fn($r) => (float)($r['risk_score'] ?? 0), fn($r) => htmlspecialchars($r['provider_name'] ?? '', ENT_QUOTES, 'UTF-8') . '<small>Vencidos ' . (int)($r['expired_total'] ?? 0) . ' - Rojo ' . (int)($r['red_total'] ?? 0) . '</small>', fn($r) => (int)($r['risk_score'] ?? 0), $maxScore($intelligence['provider_risk'] ?? [])); ?></section>
      <section class="report-panel"><div class="panel-title-row"><h2>Concentración contractual</h2></div><?php $barRows($intelligence['concentration'] ?? [], fn($r) => (float)($r['contract_value'] ?? 0), fn($r) => htmlspecialchars($r['provider_name'] ?? '', ENT_QUOTES, 'UTF-8') . '<small>' . (float)($r['percent_value'] ?? 0) . '% del total</small>', fn($r) => $money($r['contract_value'] ?? 0), $maxValue($intelligence['concentration'] ?? [])); ?></section>
      <section class="report-panel wide-panel"><div class="panel-title-row"><h2>Documentos pendientes</h2></div><div class="table-responsive"><table class="table table-hover align-middle modern-table"><thead><tr><th>Contrato</th><th>Proveedor</th><th>Pendientes</th><th>Valor</th></tr></thead><tbody><?php foreach (($intelligence['missing_docs'] ?? []) as $row): ?><tr><td><strong><?= htmlspecialchars($row['number'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong><small><?= htmlspecialchars($row['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></small></td><td><?= htmlspecialchars($row['provider_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td><td><span class="status-badge"><?= (int)($row['pending_docs'] ?? 0) ?></span></td><td><?= $money($row['total_value'] ?? 0) ?></td></tr><?php endforeach; if (empty($intelligence['missing_docs'])): ?><tr><td colspan="4"><div class="empty-state">Sin pendientes.</div></td></tr><?php endif; ?></tbody></table></div></section>
    </div>

  <?php elseif ($activeTab === 'proveedores'): ?>
    <section class="report-panel table-panel"><div class="table-card-head"><div><h2>Reporte proveedores</h2><p>Contratos y valor acumulado por tercero.</p></div><a class="btn btn-sm btn-success" href="<?= $exportUrl('reports.providers.excel') ?>">Exportar Excel</a></div><div class="table-responsive"><table class="table table-hover align-middle modern-table report-table"><thead><tr><th>NIT</th><th>Proveedor</th><th>Tipo contratista</th><th>Tipo persona</th><th>Naturaleza</th><th>Clasificación</th><th>Nacionalidad</th><th>Contratos</th><th>Valor</th><th>Estado</th></tr></thead><tbody><?php foreach (($providersReport ?? []) as $provider): ?><tr><td><strong><?= htmlspecialchars($provider['document_number'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong></td><td><strong><?= htmlspecialchars($provider['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong><small><?= htmlspecialchars($provider['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></small></td><td><?= htmlspecialchars($provider['tipo_contratista'] ?? '', ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($provider['tipo_persona'] ?? '', ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($provider['naturaleza'] ?? '', ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($provider['clasificacion'] ?? '', ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($provider['nacionalidad'] ?? '', ENT_QUOTES, 'UTF-8') ?></td><td><?= (int)($provider['contratos_total'] ?? 0) ?></td><td><?= $money($provider['contratos_valor'] ?? 0) ?></td><td><span class="status-badge <?= !empty($provider['active']) ? 'provider-active' : 'provider-inactive' ?>"><?= !empty($provider['active']) ? 'Activo' : 'Inactivo' ?></span></td></tr><?php endforeach; if (empty($providersReport)): ?><tr><td colspan="10"><div class="empty-state">Sin resultados.</div></td></tr><?php endif; ?></tbody></table></div></section>

  <?php elseif ($activeTab === 'financiero'): ?>
    <section class="report-panel table-panel"><div class="table-card-head"><div><h2>Reporte financiero</h2><p>Facturación, pagos y avance físico.</p></div><a class="btn btn-sm btn-success" href="<?= $exportUrl('reports.financial.excel') ?>">Exportar Excel</a></div><div class="table-responsive"><table class="table table-hover align-middle modern-table report-table"><thead><tr><th>Contrato</th><th>Proveedor</th><th>Factura</th><th>Fecha</th><th>Registrado</th><th>IVA</th><th>Total</th><th>Pagado</th><th>Orden</th><th>Avance</th></tr></thead><tbody><?php foreach (($paymentsReport ?? []) as $payment): ?><tr><td><strong><?= htmlspecialchars($payment['number'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong><small><?= htmlspecialchars($payment['contract_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></small></td><td><?= htmlspecialchars($payment['provider_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($payment['invoice_number'] ?? '', ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($payment['invoice_date'] ?? '', ENT_QUOTES, 'UTF-8') ?></td><td><?= $money($payment['recorded_value'] ?? 0) ?></td><td><?= $money($payment['vat'] ?? 0) ?></td><td><?= $money($payment['invoice_total'] ?? 0) ?></td><td><?= $money($payment['paid_value'] ?? 0) ?></td><td><?= htmlspecialchars($payment['payment_order'] ?? '', ENT_QUOTES, 'UTF-8') ?></td><td><?= number_format((float)($payment['physical_progress'] ?? 0), 2, ',', '.') ?>%</td></tr><?php endforeach; if (empty($paymentsReport)): ?><tr><td colspan="10"><div class="empty-state">Sin resultados.</div></td></tr><?php endif; ?></tbody></table></div></section>

  <?php elseif ($activeTab === 'documental'): ?>
    <section class="report-panel table-panel"><div class="table-card-head"><div><h2>Reporte documental</h2><p>Archivos, estados de firma y versiones.</p></div><a class="btn btn-sm btn-success" href="<?= $exportUrl('reports.documents.excel') ?>">Exportar Excel</a></div><div class="table-responsive"><table class="table table-hover align-middle modern-table report-table"><thead><tr><th>Contrato</th><th>Proveedor</th><th>Tipo</th><th>Archivo</th><th>Estado firma</th><th>Versión</th><th>Fecha</th></tr></thead><tbody><?php foreach (($documentsReport ?? []) as $document): ?><tr><td><strong><?= htmlspecialchars($document['number'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong><small><?= htmlspecialchars($document['contract_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></small></td><td><?= htmlspecialchars($document['provider_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($document['document_type'] ?? ($document['type'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($document['original_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td><td><span class="status-badge"><?= htmlspecialchars($document['signed_status'] ?? '', ENT_QUOTES, 'UTF-8') ?></span></td><td><?= htmlspecialchars((string)($document['version_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($document['created_at'] ?? '', ENT_QUOTES, 'UTF-8') ?></td></tr><?php endforeach; if (empty($documentsReport)): ?><tr><td colspan="7"><div class="empty-state">Sin resultados.</div></td></tr><?php endif; ?></tbody></table></div></section>

  <?php else: ?>
    <section class="report-panel table-panel"><div class="table-card-head"><div><h2>Reporte contractual</h2><p>Listado filtrado de contratos, riesgos y ejecución.</p></div><a class="btn btn-sm btn-success" href="<?= $exportUrl('reports.excel') ?>">Exportar Excel</a></div><div class="table-responsive"><table class="table table-hover align-middle modern-table report-table"><thead><tr><th>Número</th><th>Contrato</th><th>Proveedor</th><th>Área</th><th>Estado</th><th>Tipo gasto</th><th>Modalidad</th><th>Fin</th><th>Valor</th><th>%</th><th>Días</th><th>Riesgo</th></tr></thead><tbody><?php foreach (($contracts ?? []) as $contract): ?><tr><td><strong><?= htmlspecialchars($contract['number'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong></td><td><?= htmlspecialchars($contract['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($contract['provider_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($contract['area_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($contract['status_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($contract['expense_type_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($contract['selection_modality_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($contract['extension_end_date'] ?: ($contract['end_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td><td><?= $money($contract['total_value'] ?? 0) ?></td><td><?= number_format((float)($contract['execution_percent'] ?? 0), 2, ',', '.') ?>%</td><td><?= htmlspecialchars((string)($contract['days_left'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td><td><?= $riskBadge($contract['risk_level'] ?? '') ?></td></tr><?php endforeach; if (empty($contracts)): ?><tr><td colspan="12"><div class="empty-state">Sin resultados.</div></td></tr><?php endif; ?></tbody></table></div></section>
  <?php endif; ?>
</section>