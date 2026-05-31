<?php
$payments = $payments ?? [];
$summary = $summary ?? [];
$money = static fn($value): string => '$ ' . number_format((float)$value, 0, ',', '.');
$execution = max(0, min(100, (float)($contract['execution_percent'] ?? 0)));
?>

<section class="finance-contract-modern">
  <div class="module-hero finance-hero">
    <div>
      <a class="back-link" href="index.php?r=finance"><i class="bi bi-arrow-left"></i> Volver a financiera</a>
      <span class="section-eyebrow">Financiera del contrato</span>
      <h1><?= htmlspecialchars($contract['number'] ?? '', ENT_QUOTES, 'UTF-8') ?></h1>
      <p><?= htmlspecialchars($contract['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
    </div>
    <div class="module-actions">
      <a class="btn btn-outline-primary" href="index.php?r=contracts.show&id=<?= (int)$contract['id'] ?>"><i class="bi bi-file-text"></i> Detalle contrato</a>
    </div>
  </div>

  <div class="finance-summary-strip">
    <article><i class="bi bi-cash-stack"></i><span>Valor contrato</span><strong><?= $money($contract['total_value'] ?? 0) ?></strong></article>
    <article><i class="bi bi-receipt"></i><span>Total facturado</span><strong><?= $money($summary['invoiced'] ?? 0) ?></strong></article>
    <article><i class="bi bi-credit-card"></i><span>Total pagado</span><strong><?= $money($summary['paid'] ?? 0) ?></strong></article>
    <article><i class="bi bi-graph-up-arrow"></i><span>Ejecucion</span><strong><?= number_format((float)($contract['execution_percent'] ?? 0), 2, ',', '.') ?>%</strong></article>
  </div>

  <section class="finance-entry-card">
    <div class="form-section-head">
      <i class="bi bi-receipt-cutoff"></i>
      <div><h2>Registrar factura / pago</h2><p>Agrega factura, pago y avance fisico para recalcular ejecucion.</p></div>
    </div>
    <form method="post" action="index.php?r=finance.store&id=<?= (int)$contract['id'] ?>" class="finance-entry-form">
      <label class="form-field"><span>Fecha factura</span><input type="date" name="invoice_date" class="form-control"></label>
      <label class="form-field"><span>Numero factura *</span><input name="invoice_number" class="form-control" required></label>
      <label class="form-field"><span>Valor no gravado</span><input name="recorded_value" class="form-control money-input" value="0"></label>
      <label class="form-field"><span>IVA</span><input name="vat" class="form-control money-input" value="0"></label>
      <label class="form-field"><span>Total factura</span><input name="invoice_total" class="form-control money-input" value="0"></label>
      <label class="form-field"><span>Orden pago</span><input name="payment_order" class="form-control"></label>
      <label class="form-field"><span>Valor pagado</span><input name="paid_value" class="form-control money-input" value="0"></label>
      <label class="form-field"><span>Ejecutado acumulado</span><input name="accumulated_executed_value" class="form-control money-input" value="0"></label>
      <label class="form-field"><span>Avance fisico %</span><input name="physical_progress" class="form-control" value="0"></label>
      <button class="btn btn-primary finance-submit" type="submit"><i class="bi bi-save"></i> Guardar registro</button>
    </form>
  </section>

  <section class="finance-table-card">
    <div class="table-card-head">
      <div>
        <h2>Historial financiero</h2>
        <p><?= number_format(count($payments), 0, ',', '.') ?> registros asociados a este contrato.</p>
      </div>
      <div class="finance-table-progress">
        <span><?= number_format((float)($contract['execution_percent'] ?? 0), 2, ',', '.') ?>%</span>
        <div class="mini-progress"><span style="width:<?= $execution ?>%"></span></div>
      </div>
    </div>

    <div class="table-responsive finance-desktop-table">
      <table class="table table-hover align-middle mb-0 modern-table finance-table">
        <thead><tr><th>Fecha</th><th>Factura</th><th>Valor</th><th>IVA</th><th>Total factura</th><th>Orden pago</th><th>Pagado</th><th>Ejecutado acum.</th><th>Avance fisico</th><th class="text-end">Acciones</th></tr></thead>
        <tbody>
          <?php foreach ($payments as $payment): ?>
            <tr>
              <td><?= htmlspecialchars($payment['invoice_date'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
              <td><strong><?= htmlspecialchars($payment['invoice_number'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong></td>
              <td><?= $money($payment['recorded_value'] ?? 0) ?></td>
              <td><?= $money($payment['vat'] ?? 0) ?></td>
              <td><?= $money($payment['invoice_total'] ?? 0) ?></td>
              <td><?= htmlspecialchars($payment['payment_order'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= $money($payment['paid_value'] ?? 0) ?></td>
              <td><?= $money($payment['accumulated_executed_value'] ?? 0) ?></td>
              <td><?= number_format((float)($payment['physical_progress'] ?? 0), 2, ',', '.') ?>%</td>
              <td class="text-end"><a onclick="return confirm('?Eliminar registro financiero?')" class="icon-action danger" href="index.php?r=finance.delete&id=<?= (int)$payment['id'] ?>&contract_id=<?= (int)$contract['id'] ?>" title="Borrar"><i class="bi bi-trash"></i></a></td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($payments)): ?>
            <tr><td colspan="10"><div class="empty-state"><i class="bi bi-receipt"></i><strong>Sin registros financieros</strong><span>Agrega la primera factura o pago del contrato.</span></div></td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <div class="finance-mobile-list">
      <?php foreach ($payments as $payment): ?>
        <article class="finance-mobile-card">
          <div>
            <span><?= htmlspecialchars($payment['invoice_date'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
            <h3><?= htmlspecialchars($payment['invoice_number'] ?? '', ENT_QUOTES, 'UTF-8') ?></h3>
          </div>
          <dl>
            <div><dt>Total factura</dt><dd><?= $money($payment['invoice_total'] ?? 0) ?></dd></div>
            <div><dt>Pagado</dt><dd><?= $money($payment['paid_value'] ?? 0) ?></dd></div>
            <div><dt>Orden pago</dt><dd><?= htmlspecialchars($payment['payment_order'] ?? '', ENT_QUOTES, 'UTF-8') ?></dd></div>
            <div><dt>Avance fisico</dt><dd><?= number_format((float)($payment['physical_progress'] ?? 0), 2, ',', '.') ?>%</dd></div>
          </dl>
          <a onclick="return confirm('?Eliminar registro financiero?')" class="btn btn-outline-danger" href="index.php?r=finance.delete&id=<?= (int)$payment['id'] ?>&contract_id=<?= (int)$contract['id'] ?>"><i class="bi bi-trash"></i> Borrar</a>
        </article>
      <?php endforeach; ?>
    </div>
  </section>
</section>
