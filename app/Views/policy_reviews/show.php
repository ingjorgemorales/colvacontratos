<?php
$check = json_decode($review['checklist_json'] ?? '{}', true) ?: [];
$files = $files ?? [];
$checkItems = [
    'vigencia' => 'Vigencia correcta',
    'asegurado' => 'Asegurado corresponde',
    'beneficiario' => 'Beneficiario corresponde',
    'amparos' => 'Amparos correctos',
    'valor_asegurado' => 'Valor asegurado correcto',
    'firmas' => 'Firmas / certificado valido',
];
$status = (string)($review['status'] ?? 'PENDIENTE');
$money = '$' . number_format((float)($review['insured_value'] ?? 0), 2, ',', '.');
?>

<section class="policy-show-modern">
  <div class="contract-detail-hero policy-detail-hero">
    <div class="contract-title-block">
      <a href="index.php?r=polizas" class="back-link"><i class="bi bi-arrow-left"></i> Volver a polizas</a>
      <span class="section-eyebrow">Revision #<?= (int)$review['id'] ?></span>
      <h1><?= htmlspecialchars($review['policy_number'] ?: 'Poliza sin numero', ENT_QUOTES, 'UTF-8') ?></h1>
      <p><?= htmlspecialchars($review['contract_number'] ?? '', ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars($review['contractor_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
    </div>
    <div class="contract-hero-actions">
      <span class="policy-status-badge policy-<?= strtolower($status) ?> large"><?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?></span>
    </div>
  </div>

  <div class="contract-kpi-row">
    <article><span>Aseguradora</span><strong><?= htmlspecialchars($review['insurance_company'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong><em><?= htmlspecialchars($review['policy_type'] ?? 'Tipo no definido', ENT_QUOTES, 'UTF-8') ?></em></article>
    <article><span>Valor asegurado</span><strong><?= $money ?></strong><em>Valor registrado</em></article>
    <article><span>Inicio</span><strong><?= htmlspecialchars($review['start_date'] ?? 'Sin fecha', ENT_QUOTES, 'UTF-8') ?></strong><em>Vigencia inicial</em></article>
    <article><span>Fin</span><strong><?= htmlspecialchars($review['end_date'] ?? 'Sin fecha', ENT_QUOTES, 'UTF-8') ?></strong><em>Vigencia final</em></article>
  </div>

  <div class="policy-show-grid">
    <section class="detail-panel">
      <div class="panel-title-row"><h2>Informacion general</h2></div>
      <div class="detail-field-grid">
        <div class="detail-field"><span>Contrato</span><strong><?= htmlspecialchars($review['contract_number'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong></div>
        <div class="detail-field"><span>Contratista</span><strong><?= htmlspecialchars($review['contractor_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong></div>
        <div class="detail-field"><span>NIT / ID</span><strong><?= htmlspecialchars($review['contractor_document'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong></div>
        <div class="detail-field"><span>Tipo poliza</span><strong><?= htmlspecialchars($review['policy_type'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong></div>
      </div>

      <div class="policy-check-result">
        <h3>Checklist</h3>
        <div class="policy-check-result-grid">
          <?php foreach ($checkItems as $key => $label): ?>
            <span class="<?= !empty($check[$key]) ? 'is-ok' : 'is-pending' ?>">
              <i class="bi <?= !empty($check[$key]) ? 'bi-check-circle-fill' : 'bi-circle' ?>"></i>
              <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
            </span>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="policy-observations">
        <h3>Observaciones</h3>
        <p><?= nl2br(htmlspecialchars($review['observations'] ?? '', ENT_QUOTES, 'UTF-8')) ?></p>
      </div>
    </section>

    <aside class="policy-side-stack">
      <section class="detail-panel">
        <div class="panel-title-row"><h2>Actualizar estado</h2></div>
        <form method="post" action="index.php?r=polizas.status" class="document-mini-form">
          <input type="hidden" name="id" value="<?= (int)$review['id'] ?>">
          <label class="form-field">
            <span>Estado</span>
            <select class="form-select" name="status">
              <?php foreach (['PENDIENTE','APROBADA','OBSERVADA','RECHAZADA'] as $option): ?>
                <option value="<?= $option ?>" <?= $status === $option ? 'selected' : '' ?>><?= $option ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <label class="form-field">
            <span>Observaciones</span>
            <textarea class="form-control" name="observations" rows="4"><?= htmlspecialchars($review['observations'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
          </label>
          <button class="btn btn-primary w-100" type="submit"><i class="bi bi-check2-circle"></i> Actualizar</button>
        </form>
      </section>

      <section class="detail-panel">
        <div class="panel-title-row"><h2>Archivos</h2><span class="filter-pill"><?= number_format(count($files), 0, ',', '.') ?></span></div>
        <div class="policy-file-list">
          <?php foreach ($files as $file): ?>
            <a class="policy-file-modern" href="index.php?r=polizas.download&file_id=<?= (int)$file['id'] ?>">
              <i class="bi bi-paperclip"></i>
              <span><?= htmlspecialchars($file['file_type'] . ' - ' . $file['original_name'], ENT_QUOTES, 'UTF-8') ?></span>
            </a>
          <?php endforeach; ?>
          <?php if (empty($files)): ?><div class="empty-state"><i class="bi bi-folder-x"></i><strong>Sin archivos</strong><span>No hay soportes cargados.</span></div><?php endif; ?>
        </div>
      </section>
    </aside>
  </div>
</section>
