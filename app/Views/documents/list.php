<?php
$contracts = $contracts ?? [];
$total = count($contracts);
$withProvider = 0;
$activeStatuses = [];
foreach ($contracts as $row) {
    if (trim((string)($row['provider_name'] ?? '')) !== '') {
        $withProvider++;
    }
    $status = trim((string)($row['status_name'] ?? 'Sin estado'));
    $activeStatuses[$status !== '' ? $status : 'Sin estado'] = ($activeStatuses[$status !== '' ? $status : 'Sin estado'] ?? 0) + 1;
}
arsort($activeStatuses);
$mainStatus = $activeStatuses ? array_key_first($activeStatuses) : 'Sin datos';
?>

<section class="documents-list-modern">
  <div class="module-hero documents-hero">
    <div>
      <span class="section-eyebrow">Gestion documental</span>
      <h1>Documental</h1>
      <p>Selecciona un contrato para administrar adjuntos, otrosi, polizas y checklist documental.</p>
    </div>
    <div class="module-actions">
      <a class="btn btn-primary" href="index.php?r=contracts"><i class="bi bi-file-earmark-text"></i> Ver contratos</a>
    </div>
  </div>

  <div class="document-summary-strip">
    <article>
      <i class="bi bi-folder2-open"></i>
      <span>Contratos visibles</span>
      <strong><?= number_format($total, 0, ',', '.') ?></strong>
    </article>
    <article>
      <i class="bi bi-building-check"></i>
      <span>Con proveedor</span>
      <strong><?= number_format($withProvider, 0, ',', '.') ?></strong>
    </article>
    <article>
      <i class="bi bi-flag"></i>
      <span>Estado principal</span>
      <strong><?= htmlspecialchars($mainStatus, ENT_QUOTES, 'UTF-8') ?></strong>
    </article>
  </div>

  <div class="documents-table-card">
    <div class="table-card-head">
      <div>
        <h2>Contratos disponibles</h2>
        <p>Accede al expediente documental de cada contrato.</p>
      </div>
      <span class="filter-pill"><?= number_format($total, 0, ',', '.') ?> registros</span>
    </div>

    <div class="table-responsive documents-desktop-table">
      <table class="table table-hover align-middle mb-0 modern-table documents-table">
        <thead>
          <tr>
            <th>Numero</th>
            <th>Contrato</th>
            <th>Proveedor</th>
            <th>Area</th>
            <th>Estado</th>
            <th class="text-end">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($contracts as $contractRow): ?>
            <tr>
              <td><strong class="document-number"><?= htmlspecialchars($contractRow['number'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong></td>
              <td>
                <strong><?= htmlspecialchars($contractRow['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong>
                <small><?= htmlspecialchars($contractRow['contract_type_name'] ?? 'Contrato', ENT_QUOTES, 'UTF-8') ?></small>
              </td>
              <td><?= htmlspecialchars($contractRow['provider_name'] ?? 'Sin proveedor', ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars($contractRow['area_name'] ?? 'Sin area', ENT_QUOTES, 'UTF-8') ?></td>
              <td><span class="status-badge"><?= htmlspecialchars($contractRow['status_name'] ?? 'Sin estado', ENT_QUOTES, 'UTF-8') ?></span></td>
              <td class="text-end">
                <a class="btn btn-sm btn-primary" href="index.php?r=documents&contract_id=<?= (int)$contractRow['id'] ?>">
                  <i class="bi bi-paperclip"></i> Gestionar
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($contracts)): ?>
            <tr>
              <td colspan="6">
                <div class="empty-state">
                  <i class="bi bi-folder-x"></i>
                  <strong>No hay contratos para gestionar</strong>
                  <span>Cuando existan contratos, apareceran aqui.</span>
                </div>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <div class="documents-mobile-list">
      <?php foreach ($contracts as $contractRow): ?>
        <article class="document-contract-card">
          <div>
            <span><?= htmlspecialchars($contractRow['number'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
            <h3><?= htmlspecialchars($contractRow['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></h3>
          </div>
          <dl>
            <div><dt>Proveedor</dt><dd><?= htmlspecialchars($contractRow['provider_name'] ?? 'Sin proveedor', ENT_QUOTES, 'UTF-8') ?></dd></div>
            <div><dt>Area</dt><dd><?= htmlspecialchars($contractRow['area_name'] ?? 'Sin area', ENT_QUOTES, 'UTF-8') ?></dd></div>
            <div><dt>Estado</dt><dd><?= htmlspecialchars($contractRow['status_name'] ?? 'Sin estado', ENT_QUOTES, 'UTF-8') ?></dd></div>
          </dl>
          <a class="btn btn-primary" href="index.php?r=documents&contract_id=<?= (int)$contractRow['id'] ?>">
            <i class="bi bi-paperclip"></i> Gestionar documentos
          </a>
        </article>
      <?php endforeach; ?>
      <?php if (empty($contracts)): ?>
        <div class="empty-state">
          <i class="bi bi-folder-x"></i>
          <strong>No hay contratos para gestionar</strong>
          <span>Cuando existan contratos, apareceran aqui.</span>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>
