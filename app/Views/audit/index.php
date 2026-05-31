<?php $logs = $logs ?? []; ?>
<section class="audit-modern">
  <div class="module-hero audit-hero">
    <div>
      <span class="section-eyebrow">Trazabilidad</span>
      <h1>Auditor&iacute;a</h1>
      <p>&Uacute;ltimos movimientos registrados en el sistema.</p>
    </div>
    <div class="module-actions">
      <span class="filter-pill"><?= number_format(count($logs), 0, ',', '.') ?> registros</span>
    </div>
  </div>

  <section class="audit-panel">
    <div class="table-card-head">
      <div><h2>Bit&aacute;cora del sistema</h2><p>Acciones recientes por m&oacute;dulo, detalle e IP.</p></div>
    </div>
    <div class="table-responsive audit-desktop-table">
      <table class="table table-hover align-middle mb-0 modern-table audit-table">
        <thead><tr><th>Fecha</th><th>M&oacute;dulo</th><th>Acci&oacute;n</th><th>Detalle</th><th>IP</th></tr></thead>
        <tbody>
          <?php foreach ($logs as $log): ?>
            <tr>
              <td><?= htmlspecialchars($log['created_at'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
              <td><span class="status-badge"><?= htmlspecialchars($log['module'] ?? '', ENT_QUOTES, 'UTF-8') ?></span></td>
              <td><strong><?= htmlspecialchars($log['action'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong></td>
              <td><?= htmlspecialchars($log['detail'] ?? $log['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars($log['ip'] ?? $log['ip_address'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($logs)): ?><tr><td colspan="5"><div class="empty-state">Sin registros de auditor&iacute;a.</div></td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>

    <div class="audit-mobile-list">
      <?php foreach ($logs as $log): ?>
        <article class="audit-mobile-card">
          <div><span><?= htmlspecialchars($log['created_at'] ?? '', ENT_QUOTES, 'UTF-8') ?></span><h3><?= htmlspecialchars($log['action'] ?? '', ENT_QUOTES, 'UTF-8') ?></h3></div>
          <p><?= htmlspecialchars($log['detail'] ?? $log['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
          <dl>
            <div><dt>M&oacute;dulo</dt><dd><?= htmlspecialchars($log['module'] ?? '', ENT_QUOTES, 'UTF-8') ?></dd></div>
            <div><dt>IP</dt><dd><?= htmlspecialchars($log['ip'] ?? $log['ip_address'] ?? '', ENT_QUOTES, 'UTF-8') ?></dd></div>
          </dl>
        </article>
      <?php endforeach; ?>
      <?php if (empty($logs)): ?><div class="empty-state">Sin registros de auditor&iacute;a.</div><?php endif; ?>
    </div>
  </section>
</section>
