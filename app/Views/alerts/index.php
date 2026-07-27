<?php
$days = (int)($days ?? 30);
$expiring = $expiring ?? [];
$expired = $expired ?? [];
$missingDocs = $missingDocs ?? [];
$logs = $logs ?? [];
$logStatusClass = static function (string $status): string {
    if ($status === 'sent') return 'provider-active';
    if ($status === 'skipped') return 'provider-inactive';
    return 'risk-danger';
};
?>

<section class="alerts-modern">
  <div class="module-hero alerts-hero">
    <div>
      <span class="section-eyebrow">Seguimiento preventivo</span>
      <h1>Alertas contractuales</h1>
      <p>Control de vencimientos, contratos vencidos, documentos faltantes y bit&aacute;cora de notificaciones.</p>
    </div>
    <form method="post" action="index.php?r=alerts.run" class="alerts-run-form">
      <label>
        <span>D&iacute;as</span>
        <input type="number" min="1" max="365" name="days" value="<?= $days ?>" class="form-control">
      </label>
      <button class="btn btn-primary" type="submit"><i class="bi bi-send"></i> Ejecutar alertas</button>
    </form>
  </div>

  <div class="alerts-summary-grid">
    <article><i class="bi bi-bell"></i><span>Por vencer</span><strong><?= count($expiring) ?></strong><em>Pr&oacute;ximos <?= $days ?> d&iacute;as</em></article>
    <article><i class="bi bi-exclamation-triangle"></i><span>Vencidos</span><strong><?= count($expired) ?></strong><em>Requieren gesti&oacute;n inmediata</em></article>
    <article><i class="bi bi-folder-x"></i><span>Sin documentos</span><strong><?= count($missingDocs) ?></strong><em>Contratos sin soportes cargados</em></article>
  </div>

  <section class="alert-panel">
    <div class="table-card-head">
      <div><h2>Contratos pr&oacute;ximos a vencer</h2><p>Ventana de seguimiento de <?= $days ?> d&iacute;as.</p></div>
      <span class="filter-pill"><?= number_format(count($expiring), 0, ',', '.') ?> alertas</span>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0 modern-table alerts-table">
        <thead><tr><th>N&uacute;mero</th><th>Contrato</th><th>Proveedor</th><th>Supervisor</th><th>Vence</th><th>D&iacute;as</th></tr></thead>
        <tbody>
          <?php foreach ($expiring as $row): ?>
            <tr>
              <td><a class="contract-number" href="index.php?r=contracts.show&id=<?= (int)$row['id'] ?>"><?= htmlspecialchars($row['number'] ?? '', ENT_QUOTES, 'UTF-8') ?></a></td>
              <td><strong><?= htmlspecialchars($row['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong></td>
              <td><?= htmlspecialchars($row['provider_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars($row['supervisor_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars($row['due_date'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
              <td><span class="risk-badge risk-warning"><?= (int)($row['days_left'] ?? 0) ?></span></td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($expiring)): ?><tr><td colspan="6"><div class="empty-state">Sin vencimientos en este rango.</div></td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>

  <div class="alerts-grid">
    <section class="alert-panel">
      <div class="table-card-head"><div><h2>Contratos vencidos</h2><p>Casos que requieren revisi&oacute;n inmediata.</p></div></div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 modern-table alerts-table small-table">
          <thead><tr><th>N&uacute;mero</th><th>Contrato</th><th>Proveedor</th><th>D&iacute;as vencido</th></tr></thead>
          <tbody>
            <?php foreach ($expired as $row): ?>
              <tr><td><a class="contract-number" href="index.php?r=contracts.show&id=<?= (int)$row['id'] ?>"><?= htmlspecialchars($row['number'] ?? '', ENT_QUOTES, 'UTF-8') ?></a></td><td><strong><?= htmlspecialchars($row['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong></td><td><?= htmlspecialchars($row['provider_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td><td><span class="risk-badge risk-danger"><?= (int)($row['days_expired'] ?? 0) ?></span></td></tr>
            <?php endforeach; ?>
            <?php if (empty($expired)): ?><tr><td colspan="4"><div class="empty-state">Sin contratos vencidos.</div></td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>

    <section class="alert-panel">
      <div class="table-card-head"><div><h2>Contratos sin documentos</h2><p>Expedientes sin soportes cargados.</p></div></div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 modern-table alerts-table small-table">
          <thead><tr><th>N&uacute;mero</th><th>Contrato</th><th>Proveedor</th><th>Supervisor</th></tr></thead>
          <tbody>
            <?php foreach ($missingDocs as $row): ?>
              <tr><td><a class="contract-number" href="index.php?r=documents&contract_id=<?= (int)$row['id'] ?>"><?= htmlspecialchars($row['number'] ?? '', ENT_QUOTES, 'UTF-8') ?></a></td><td><strong><?= htmlspecialchars($row['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong></td><td><?= htmlspecialchars($row['provider_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($row['supervisor_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td></tr>
            <?php endforeach; ?>
            <?php if (empty($missingDocs)): ?><tr><td colspan="4"><div class="empty-state">Todos los contratos tienen documentos.</div></td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>
  </div>

  <section class="alert-panel">
    <div class="table-card-head">
      <div><h2>Bit&aacute;cora de alertas</h2><p>&Uacute;ltimas ejecuciones y resultado de notificaciones.</p></div>
      <span class="filter-pill"><?= isset($pgLogs) ? number_format($pgLogs->total, 0, ',', '.') : number_format(count($logs), 0, ',', '.') ?> registros</span>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0 modern-table alerts-table">
        <thead><tr><th>Fecha</th><th>Contrato</th><th>Canal</th><th>Destino</th><th>Estado</th><th>Error</th></tr></thead>
        <tbody>
          <?php foreach ($logs as $log): ?>
            <?php $status = (string)($log['status'] ?? ''); ?>
            <tr>
              <td><?= htmlspecialchars($log['sent_at'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
              <td><strong><?= htmlspecialchars(trim(($log['number'] ?? '') . ' ' . ($log['contract_name'] ?? '')), ENT_QUOTES, 'UTF-8') ?></strong></td>
              <td><?= htmlspecialchars($log['channel'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars($log['recipient'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
              <td><span class="status-badge <?= $logStatusClass($status) ?>"><?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?></span></td>
              <td><?= htmlspecialchars($log['error_message'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($logs)): ?><tr><td colspan="6"><div class="empty-state">Sin ejecuciones registradas.</div></td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
    <?php if (isset($pgLogs) && $pgLogs->pages > 1): ?>
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 px-3 py-2" style="border-top:1px solid #eef1f6">
        <span class="text-muted" style="font-size:12.5px"><?= $pgLogs->summary() ?></span>
        <?= $pgLogs->links(array_filter(['r'=>'alerts', 'days'=>$days ?? null]), 'lpage') ?>
      </div>
    <?php endif; ?>
  </section>
</section>
