<?php
$contracts = $contracts ?? [];
$totalValue = 0;
$invoicedTotal = 0;
$paidTotal = 0;
$avgExecution = 0;
foreach ($contracts as $contractRow) {
    $totalValue += (float)($contractRow['total_value'] ?? 0);
    $invoicedTotal += (float)($contractRow['invoiced_total'] ?? 0);
    $paidTotal += (float)($contractRow['paid_total'] ?? 0);
    $avgExecution += (float)($contractRow['execution_percent'] ?? 0);
}
$avgExecution = count($contracts) ? $avgExecution / count($contracts) : 0;
$money = static fn($value): string => '$ ' . number_format((float)$value, 0, ',', '.');
?>

<section class="finance-index-modern">
  <div class="module-hero finance-hero">
    <div>
      <span class="section-eyebrow">Control financiero</span>
      <h1>Modulo financiero</h1>
      <p>Control de facturas, pagos, ejecucion y saldos por contrato.</p>
    </div>
    <div class="module-actions">
      <a class="btn btn-light" href="index.php?r=dashboard"><i class="bi bi-speedometer2"></i> Indicadores</a>
    </div>
  </div>

  <div class="finance-summary-strip">
    <article><i class="bi bi-file-earmark-text"></i><span>Contratos</span><strong><?= number_format(count($contracts), 0, ',', '.') ?></strong></article>
    <article><i class="bi bi-cash-stack"></i><span>Valor contrato</span><strong><?= $money($totalValue) ?></strong></article>
    <article><i class="bi bi-receipt"></i><span>Facturado</span><strong><?= $money($invoicedTotal) ?></strong></article>
    <article><i class="bi bi-credit-card"></i><span>Pagado</span><strong><?= $money($paidTotal) ?></strong></article>
  </div>

  <div class="finance-table-card">
    <div class="table-card-head">
      <div>
        <h2>Ejecucion financiera por contrato</h2>
        <p>Promedio de ejecucion: <?= number_format($avgExecution, 2, ',', '.') ?>%.</p>
      </div>
      <span class="filter-pill"><?= number_format(count($contracts), 0, ',', '.') ?> registros</span>
    </div>

    <div class="table-responsive finance-desktop-table">
      <table class="table table-hover align-middle mb-0 modern-table finance-table">
        <thead><tr><th>Numero</th><th>Contrato</th><th>Proveedor</th><th>Area</th><th>Valor contrato</th><th>Facturado</th><th>Pagado</th><th>Ejecucion</th><th class="text-end">Acciones</th></tr></thead>
        <tbody>
          <?php foreach ($contracts as $contractRow): ?>
            <?php $execution = max(0, min(100, (float)($contractRow['execution_percent'] ?? 0))); ?>
            <tr>
              <td><strong class="finance-number"><?= htmlspecialchars($contractRow['number'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong></td>
              <td><strong><?= htmlspecialchars($contractRow['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong></td>
              <td><?= htmlspecialchars($contractRow['provider_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars($contractRow['area_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= $money($contractRow['total_value'] ?? 0) ?></td>
              <td><?= $money($contractRow['invoiced_total'] ?? 0) ?></td>
              <td><?= $money($contractRow['paid_total'] ?? 0) ?></td>
              <td>
                <span class="finance-percent"><?= number_format((float)($contractRow['execution_percent'] ?? 0), 2, ',', '.') ?>%</span>
                <div class="mini-progress"><span style="width:<?= $execution ?>%"></span></div>
              </td>
              <td class="text-end"><a class="btn btn-sm btn-primary" href="index.php?r=finance.contract&id=<?= (int)$contractRow['id'] ?>"><i class="bi bi-cash-coin"></i> Gestionar</a></td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($contracts)): ?>
            <tr><td colspan="9"><div class="empty-state"><i class="bi bi-cash-coin"></i><strong>Sin contratos financieros</strong><span>No hay registros para mostrar.</span></div></td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <div class="finance-mobile-list">
      <?php foreach ($contracts as $contractRow): ?>
        <?php $execution = max(0, min(100, (float)($contractRow['execution_percent'] ?? 0))); ?>
        <article class="finance-mobile-card">
          <div>
            <span><?= htmlspecialchars($contractRow['number'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
            <h3><?= htmlspecialchars($contractRow['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></h3>
          </div>
          <dl>
            <div><dt>Proveedor</dt><dd><?= htmlspecialchars($contractRow['provider_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></dd></div>
            <div><dt>Valor</dt><dd><?= $money($contractRow['total_value'] ?? 0) ?></dd></div>
            <div><dt>Facturado</dt><dd><?= $money($contractRow['invoiced_total'] ?? 0) ?></dd></div>
            <div><dt>Pagado</dt><dd><?= $money($contractRow['paid_total'] ?? 0) ?></dd></div>
          </dl>
          <div>
            <span class="finance-percent"><?= number_format((float)($contractRow['execution_percent'] ?? 0), 2, ',', '.') ?>%</span>
            <div class="mini-progress"><span style="width:<?= $execution ?>%"></span></div>
          </div>
          <a class="btn btn-primary" href="index.php?r=finance.contract&id=<?= (int)$contractRow['id'] ?>"><i class="bi bi-cash-coin"></i> Gestionar</a>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
