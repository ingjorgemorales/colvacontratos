<?php
$reviews = $reviews ?? [];
$kpis = $kpis ?? [];
$filters = $filters ?? [];
$statusMap = [
    'PENDIENTE' => ['Pendientes', 'policy-pendiente', 'bi-hourglass-split'],
    'APROBADA' => ['Aprobadas', 'policy-aprobada', 'bi-check-circle'],
    'OBSERVADA' => ['Observadas', 'policy-observada', 'bi-eye'],
    'RECHAZADA' => ['Rechazadas', 'policy-rechazada', 'bi-x-circle'],
];
$statusClass = static fn($status): string => 'policy-' . strtolower((string)$status);
?>

<section class="policy-index-modern">
  <div class="module-hero policy-hero">
    <div>
      <span class="section-eyebrow">Control de garantias</span>
      <h1>Revision de Polizas</h1>
      <p>Registro, control documental, trazabilidad y seguimiento de polizas contractuales.</p>
    </div>
    <div class="module-actions">
      <a class="btn btn-outline-success" href="index.php?r=polizas.export"><i class="bi bi-file-earmark-excel"></i> Exportar</a>
      <a class="btn btn-primary" href="index.php?r=polizas.create"><i class="bi bi-plus-circle"></i> Nueva revision</a>
    </div>
  </div>

  <div class="policy-kpi-grid">
    <article class="policy-kpi-card total">
      <i class="bi bi-shield-check"></i>
      <span>Total</span>
      <strong><?= (int)($kpis['TOTAL'] ?? 0) ?></strong>
    </article>
    <?php foreach ($statusMap as $key => [$label, $class, $icon]): ?>
      <article class="policy-kpi-card <?= $class ?>">
        <i class="bi <?= $icon ?>"></i>
        <span><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
        <strong><?= (int)($kpis[$key] ?? 0) ?></strong>
      </article>
    <?php endforeach; ?>
  </div>

  <form class="filter-panel policy-filter-panel" method="get">
    <input type="hidden" name="r" value="polizas">
    <div class="policy-filter-grid">
      <label>
        <span>Busqueda</span>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-search"></i></span>
          <input class="form-control" name="q" value="<?= htmlspecialchars($filters['q'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Contrato, contratista o poliza">
        </div>
      </label>
      <label>
        <span>Estado</span>
        <select class="form-select" name="status">
          <option value="">Todos los estados</option>
          <?php foreach (array_keys($statusMap) as $status): ?>
            <option value="<?= $status ?>" <?= (($filters['status'] ?? '') === $status) ? 'selected' : '' ?>><?= $status ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <div class="filter-actions">
        <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> Consultar</button>
        <?php if (!empty($filters['q']) || !empty($filters['status'])): ?>
          <a class="btn btn-light" href="index.php?r=polizas"><i class="bi bi-x-lg"></i> Limpiar</a>
        <?php endif; ?>
      </div>
    </div>
  </form>

  <div class="policy-table-card">
    <div class="table-card-head">
      <div>
        <h2>Revisiones registradas</h2>
        <p>Seguimiento de polizas por contrato, aseguradora y estado.</p>
      </div>
      <span class="filter-pill"><?= number_format(count($reviews), 0, ',', '.') ?> registros</span>
    </div>

    <div class="table-responsive policy-desktop-table">
      <table class="table table-hover align-middle mb-0 modern-table policy-table">
        <thead><tr><th>ID</th><th>Contrato</th><th>Contratista</th><th>Poliza</th><th>Aseguradora</th><th>Vigencia</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
        <tbody>
          <?php foreach ($reviews as $review): ?>
            <tr>
              <td><strong class="policy-id">#<?= (int)$review['id'] ?></strong></td>
              <td><strong><?= htmlspecialchars($review['contract_number'] ?: 'Sin numero', ENT_QUOTES, 'UTF-8') ?></strong></td>
              <td><?= htmlspecialchars($review['contractor_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars($review['policy_number'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars($review['insurance_company'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
              <td><small><?= htmlspecialchars(($review['start_date'] ?? '') . ' / ' . ($review['end_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?></small></td>
              <td><span class="policy-status-badge <?= $statusClass($review['status'] ?? '') ?>"><?= htmlspecialchars($review['status'] ?? '', ENT_QUOTES, 'UTF-8') ?></span></td>
              <td class="text-end"><a class="icon-action" href="index.php?r=polizas.show&id=<?= (int)$review['id'] ?>" title="Ver revision"><i class="bi bi-eye"></i></a></td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($reviews)): ?>
            <tr><td colspan="8"><div class="empty-state"><i class="bi bi-shield-x"></i><strong>No hay revisiones registradas</strong><span>Crea una nueva revision para iniciar el seguimiento.</span></div></td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <div class="policy-mobile-list">
      <?php foreach ($reviews as $review): ?>
        <article class="policy-mobile-card">
          <div class="policy-card-top">
            <div>
              <span>#<?= (int)$review['id'] ?> - <?= htmlspecialchars($review['contract_number'] ?: 'Sin numero', ENT_QUOTES, 'UTF-8') ?></span>
              <h3><?= htmlspecialchars($review['contractor_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></h3>
            </div>
            <span class="policy-status-badge <?= $statusClass($review['status'] ?? '') ?>"><?= htmlspecialchars($review['status'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
          </div>
          <dl>
            <div><dt>Poliza</dt><dd><?= htmlspecialchars($review['policy_number'] ?? '', ENT_QUOTES, 'UTF-8') ?></dd></div>
            <div><dt>Aseguradora</dt><dd><?= htmlspecialchars($review['insurance_company'] ?? '', ENT_QUOTES, 'UTF-8') ?></dd></div>
            <div><dt>Vigencia</dt><dd><?= htmlspecialchars(($review['start_date'] ?? '') . ' / ' . ($review['end_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?></dd></div>
          </dl>
          <a class="btn btn-outline-primary" href="index.php?r=polizas.show&id=<?= (int)$review['id'] ?>"><i class="bi bi-eye"></i> Ver revision</a>
        </article>
      <?php endforeach; ?>
      <?php if (empty($reviews)): ?>
        <div class="empty-state"><i class="bi bi-shield-x"></i><strong>No hay revisiones registradas</strong><span>Crea una nueva revision para iniciar el seguimiento.</span></div>
      <?php endif; ?>
    </div>
  </div>
</section>
