<?php
$totalRows = count($contracts ?? []);
$totalValue = array_reduce($contracts ?? [], fn($sum, $c) => $sum + (float)($c['total_value'] ?? 0), 0.0);
$avgExecution = $totalRows ? array_reduce($contracts ?? [], fn($sum, $c) => $sum + (float)($c['execution_percent'] ?? 0), 0.0) / $totalRows : 0;
$criticalRows = array_filter($contracts ?? [], fn($c) => isset($c['days_to_expire']) && $c['days_to_expire'] !== null && (int)$c['days_to_expire'] <= 30);
$activeFilters = array_filter($filters ?? [], fn($v) => $v !== '' && $v !== null);
$money = fn($n) => '$' . number_format((float)$n, 0, ',', '.');
$riskInfo = function($days): array {
    if ($days === null || $days === '') return ['Sin fecha', 'secondary'];
    $days = (int)$days;
    if ($days < 0) return ['Vencido', 'danger'];
    if ($days <= 30) return ['Critico', 'danger'];
    if ($days <= 90) return ['Proximo', 'warning'];
    return ['Vigente', 'success'];
};
?>

<section class="contracts-index-modern">
  <div class="module-hero">
    <div>
      <span class="section-eyebrow">Gestion contractual</span>
      <h1>Consulta de contratos</h1>
      <p>Administra contratos, vencimientos, documentos y ejecucion financiera desde una sola vista.</p>
    </div>
    <div class="module-actions">
      <a class="btn btn-primary" href="index.php?r=contracts.create"><i class="bi bi-plus-circle"></i> Nuevo contrato</a>
      <a class="btn btn-light" href="index.php?r=reports"><i class="bi bi-file-earmark-excel"></i> Reportes</a>
    </div>
  </div>

  <div class="contract-summary-strip">
    <div><span>Resultados</span><strong><?= (int)$totalRows ?></strong></div>
    <div><span>Valor listado</span><strong><?= $money($totalValue) ?></strong></div>
    <div><span>Ejecucion promedio</span><strong><?= number_format($avgExecution, 2, ',', '.') ?>%</strong></div>
    <div><span>Atencion prioritaria</span><strong><?= count($criticalRows) ?></strong></div>
  </div>

  <form class="filter-panel" method="get">
    <input type="hidden" name="r" value="contracts">
    <div class="filter-grid">
      <label>
        <span>Area</span>
        <select class="form-select" name="area_id">
          <option value="">Todas las areas</option>
          <?php foreach($areas as $a): ?>
            <option value="<?= $a['id'] ?>" <?= (($filters['area_id'] ?? '')==$a['id'])?'selected':'' ?>><?= htmlspecialchars($a['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>
        <span>Estado</span>
        <select class="form-select" name="status_id">
          <option value="">Todos los estados</option>
          <?php foreach($statuses as $s): ?>
            <option value="<?= $s['id'] ?>" <?= (($filters['status_id'] ?? '')==$s['id'])?'selected':'' ?>><?= htmlspecialchars($s['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>
        <span>Tipo</span>
        <select class="form-select" name="contract_type_id">
          <option value="">Todos los tipos</option>
          <?php foreach(($contractTypes ?? []) as $t): ?>
            <option value="<?= $t['id'] ?>" <?= (($filters['contract_type_id'] ?? '')==$t['id'])?'selected':'' ?>><?= htmlspecialchars($t['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="filter-search">
        <span>Buscar</span>
        <input class="form-control" name="q" value="<?= htmlspecialchars($filters['q'] ?? '') ?>" placeholder="Numero, contrato, proveedor o supervisor">
      </label>
      <div class="filter-actions">
        <button class="btn btn-primary"><i class="bi bi-search"></i> Buscar</button>
        <?php if($activeFilters): ?>
          <a class="btn btn-light" href="index.php?r=contracts"><i class="bi bi-x-circle"></i> Limpiar</a>
        <?php endif; ?>
      </div>
    </div>
  </form>

  <div class="contracts-table-card">
    <div class="table-card-head">
      <div>
        <h2>Contratos encontrados</h2>
        <p><?= (int)$totalRows ?> registros cargados en esta consulta</p>
      </div>
      <?php if($activeFilters): ?><span class="filter-pill"><?= count($activeFilters) ?> filtros activos</span><?php endif; ?>
    </div>

    <div class="table-responsive contracts-desktop-table">
      <table class="table modern-table contracts-table align-middle mb-0">
        <thead>
          <tr>
            <th>Contrato</th>
            <th>Proveedor</th>
            <th>Area</th>
            <th>Fechas</th>
            <th>Estado</th>
            <th class="text-end">Valor</th>
            <th>Ejecucion</th>
            <th class="text-end">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($contracts as $c): [$riskLabel, $riskTone] = $riskInfo($c['days_to_expire'] ?? null); ?>
            <tr>
              <td>
                <a class="contract-number" href="index.php?r=contracts.show&id=<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['number']) ?></a>
                <strong><?= htmlspecialchars($c['name']) ?></strong>
                <small><?= htmlspecialchars($c['contract_type_name'] ?? 'Sin tipo') ?></small>
              </td>
              <td><?= htmlspecialchars($c['provider_name'] ?? '') ?></td>
              <td><?= htmlspecialchars($c['area_name'] ?? '') ?></td>
              <td>
                <span><?= htmlspecialchars($c['start_date'] ?? '') ?></span>
                <small>Fin: <?= htmlspecialchars($c['extension_end_date'] ?: ($c['end_date'] ?? '')) ?></small>
              </td>
              <td>
                <span class="status-badge"><?= htmlspecialchars($c['status_name'] ?? 'Sin estado') ?></span>
                <span class="risk-badge risk-<?= $riskTone ?>"><?= htmlspecialchars($riskLabel) ?></span>
              </td>
              <td class="text-end"><?= $money($c['total_value'] ?? 0) ?></td>
              <td>
                <?php $exec = min(100, max(0, (float)($c['execution_percent'] ?? 0))); ?>
                <div class="mini-progress"><span style="width: <?= $exec ?>%"></span></div>
                <small><?= number_format($exec, 2, ',', '.') ?>%</small>
              </td>
              <td class="text-end">
                <div class="row-actions">
                  <a class="icon-action" href="index.php?r=contracts.show&id=<?= (int)$c['id'] ?>" title="Ver"><i class="bi bi-eye"></i></a>
                  <a class="icon-action" href="index.php?r=contracts.edit&id=<?= (int)$c['id'] ?>" title="Editar"><i class="bi bi-pencil"></i></a>
                  <a class="icon-action" href="index.php?r=finance.contract&id=<?= (int)$c['id'] ?>" title="Financiera"><i class="bi bi-cash-coin"></i></a>
                  <a class="icon-action" href="index.php?r=documents&contract_id=<?= (int)$c['id'] ?>" title="Documentos"><i class="bi bi-paperclip"></i></a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if(empty($contracts)): ?>
            <tr><td colspan="8" class="text-center text-muted py-4">No hay contratos para los filtros seleccionados.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <div class="contracts-mobile-list">
      <?php foreach($contracts as $c): [$riskLabel, $riskTone] = $riskInfo($c['days_to_expire'] ?? null); $exec = min(100, max(0, (float)($c['execution_percent'] ?? 0))); ?>
        <article class="contract-mobile-card">
          <div class="mobile-card-top">
            <div>
              <a href="index.php?r=contracts.show&id=<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['number']) ?></a>
              <h3><?= htmlspecialchars($c['name']) ?></h3>
            </div>
            <span class="risk-badge risk-<?= $riskTone ?>"><?= htmlspecialchars($riskLabel) ?></span>
          </div>
          <dl>
            <div><dt>Proveedor</dt><dd><?= htmlspecialchars($c['provider_name'] ?? '') ?></dd></div>
            <div><dt>Area</dt><dd><?= htmlspecialchars($c['area_name'] ?? '') ?></dd></div>
            <div><dt>Fin</dt><dd><?= htmlspecialchars($c['extension_end_date'] ?: ($c['end_date'] ?? '')) ?></dd></div>
            <div><dt>Valor</dt><dd><?= $money($c['total_value'] ?? 0) ?></dd></div>
          </dl>
          <div class="mini-progress"><span style="width: <?= $exec ?>%"></span></div>
          <div class="mobile-actions">
            <a class="btn btn-sm btn-primary" href="index.php?r=contracts.show&id=<?= (int)$c['id'] ?>">Ver</a>
            <a class="btn btn-sm btn-light" href="index.php?r=contracts.edit&id=<?= (int)$c['id'] ?>">Editar</a>
            <a class="btn btn-sm btn-light" href="index.php?r=documents&contract_id=<?= (int)$c['id'] ?>">Docs</a>
          </div>
        </article>
      <?php endforeach; ?>
      <?php if(empty($contracts)): ?>
        <div class="empty-state">No hay contratos para mostrar.</div>
      <?php endif; ?>
    </div>
  </div>
</section>