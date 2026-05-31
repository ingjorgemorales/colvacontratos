<?php
$contractId = (int)($contract['id'] ?? 0);
$documents = $documents ?? [];
$addendums = $addendums ?? [];
$policies = $policies ?? [];
$checklist = $checklist ?? [];

$missingChecklist = 0;
foreach ($checklist as $item) {
    if (($item['status'] ?? '') === 'pendiente') {
        $missingChecklist++;
    }
}
$money = static fn($value): string => '$' . number_format((float)$value, 0, ',', '.');
$dateText = static fn($value): string => trim((string)$value) !== '' ? htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') : 'Sin fecha';
$statusClass = static function (string $status): string {
    $status = strtolower($status);
    if (str_contains($status, 'cargado')) return 'doc-ok';
    if (str_contains($status, 'no_aplica') || str_contains($status, 'no aplica')) return 'doc-neutral';
    return 'doc-pending';
};
?>

<section class="documents-manage-modern">
  <div class="module-hero documents-hero">
    <div>
      <a class="back-link" href="index.php?r=documents"><i class="bi bi-arrow-left"></i> Volver a documental</a>
      <span class="section-eyebrow">Expediente contractual</span>
      <h1>Gestion documental</h1>
      <p>Contrato <?= htmlspecialchars($contract['number'] ?? '', ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars($contract['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
    </div>
    <div class="module-actions">
      <a class="btn btn-outline-primary" href="index.php?r=contracts.show&id=<?= $contractId ?>"><i class="bi bi-file-text"></i> Ver contrato</a>
      <a class="btn btn-light" href="index.php?r=documents"><i class="bi bi-folder2-open"></i> Documental</a>
    </div>
  </div>

  <div class="document-summary-strip four">
    <article>
      <i class="bi bi-paperclip"></i>
      <span>Adjuntos</span>
      <strong><?= number_format(count($documents), 0, ',', '.') ?></strong>
    </article>
    <article>
      <i class="bi bi-file-earmark-plus"></i>
      <span>Otrosi</span>
      <strong><?= number_format(count($addendums), 0, ',', '.') ?></strong>
    </article>
    <article>
      <i class="bi bi-shield-check"></i>
      <span>Polizas</span>
      <strong><?= number_format(count($policies), 0, ',', '.') ?></strong>
    </article>
    <article>
      <i class="bi bi-list-check"></i>
      <span>Pendientes checklist</span>
      <strong><?= number_format($missingChecklist, 0, ',', '.') ?></strong>
    </article>
  </div>

  <div class="document-actions-grid">
    <section class="document-action-card">
      <div class="form-section-head">
        <i class="bi bi-cloud-arrow-up"></i>
        <div>
          <h2>Subir adjunto</h2>
          <p>PDF, imagenes u ofimatica permitida.</p>
        </div>
      </div>
      <form method="post" action="index.php?r=documents.upload" enctype="multipart/form-data" class="document-mini-form">
        <input type="hidden" name="contract_id" value="<?= $contractId ?>">
        <label class="form-field">
          <span>Tipo documento</span>
          <select name="document_type" class="form-select">
            <option value="contrato">Contrato</option>
            <option value="factura">Factura</option>
            <option value="poliza">Poliza</option>
            <option value="otrosi">Otrosi</option>
            <option value="acta">Acta</option>
            <option value="general">General</option>
          </select>
        </label>
        <label class="form-field">
          <span>Archivo</span>
          <input type="file" name="file" class="form-control" required>
        </label>
        <label class="form-field">
          <span>Observaciones</span>
          <textarea name="notes" class="form-control" rows="3" placeholder="Notas del adjunto"></textarea>
        </label>
        <button class="btn btn-primary w-100" type="submit"><i class="bi bi-upload"></i> Subir archivo</button>
      </form>
    </section>

    <section class="document-action-card">
      <div class="form-section-head">
        <i class="bi bi-file-earmark-plus"></i>
        <div>
          <h2>Registrar otrosi</h2>
          <p>Control de prorrogas, ajustes y adiciones.</p>
        </div>
      </div>
      <form method="post" action="index.php?r=documents.addendum" class="document-mini-form">
        <input type="hidden" name="contract_id" value="<?= $contractId ?>">
        <div class="two-field-row">
          <label class="form-field"><span>Inicio</span><input type="date" name="start_date" class="form-control"></label>
          <label class="form-field"><span>Fin</span><input type="date" name="end_date" class="form-control"></label>
        </div>
        <label class="form-field"><span>Valor otrosi</span><input name="value" class="form-control" placeholder="0"></label>
        <label class="form-field"><span>Descripcion</span><textarea name="description" class="form-control" rows="3" placeholder="Descripcion"></textarea></label>
        <button class="btn btn-primary w-100" type="submit"><i class="bi bi-check2-circle"></i> Guardar otrosi</button>
      </form>
    </section>

    <section class="document-action-card">
      <div class="form-section-head">
        <i class="bi bi-shield-check"></i>
        <div>
          <h2>Registrar poliza</h2>
          <p>Garantias, vigencias y valor asegurado.</p>
        </div>
      </div>
      <form method="post" action="index.php?r=documents.policy" class="document-mini-form">
        <input type="hidden" name="contract_id" value="<?= $contractId ?>">
        <div class="two-field-row">
          <label class="form-field"><span>Tipo poliza</span><input name="policy_type" class="form-control" placeholder="Tipo poliza"></label>
          <label class="form-field"><span>Numero poliza</span><input name="policy_number" class="form-control" placeholder="Numero"></label>
        </div>
        <label class="form-field"><span>Aseguradora</span><input name="provider" class="form-control" placeholder="Aseguradora"></label>
        <div class="two-field-row">
          <label class="form-field"><span>Inicio</span><input type="date" name="start_date" class="form-control"></label>
          <label class="form-field"><span>Fin</span><input type="date" name="end_date" class="form-control"></label>
        </div>
        <label class="form-field"><span>Valor asegurado</span><input name="insured_value" class="form-control" placeholder="0"></label>
        <button class="btn btn-primary w-100" type="submit"><i class="bi bi-check2-circle"></i> Guardar poliza</button>
      </form>
    </section>
  </div>

  <section class="document-panel">
    <div class="table-card-head">
      <div>
        <h2>Checklist documental inteligente</h2>
        <p>Actualiza el estado de los documentos requeridos para este contrato.</p>
      </div>
      <span class="filter-pill"><?= number_format(count($checklist), 0, ',', '.') ?> items</span>
    </div>
    <div class="document-checklist">
      <?php foreach ($checklist as $item): ?>
        <?php $itemStatus = (string)($item['status'] ?? 'pendiente'); ?>
        <article class="checklist-card">
          <div>
            <strong><?= htmlspecialchars($item['required_document'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong>
            <span class="doc-status <?= $statusClass($itemStatus) ?>"><?= htmlspecialchars($itemStatus, ENT_QUOTES, 'UTF-8') ?></span>
            <?php if (!empty($item['notes'])): ?><p><?= htmlspecialchars($item['notes'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
          </div>
          <form method="post" action="index.php?r=documentflow.checklist.update" class="checklist-update-form">
            <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
            <input type="hidden" name="contract_id" value="<?= $contractId ?>">
            <select name="status" class="form-select form-select-sm">
              <option value="pendiente" <?= $itemStatus === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
              <option value="cargado" <?= $itemStatus === 'cargado' ? 'selected' : '' ?>>Cargado</option>
              <option value="no_aplica" <?= $itemStatus === 'no_aplica' ? 'selected' : '' ?>>No aplica</option>
            </select>
            <input name="notes" class="form-control form-control-sm" placeholder="Notas">
            <button class="btn btn-sm btn-primary" type="submit">OK</button>
          </form>
        </article>
      <?php endforeach; ?>
      <?php if (empty($checklist)): ?>
        <div class="empty-state">
          <i class="bi bi-list-check"></i>
          <strong>Sin checklist documental</strong>
          <span>No hay items registrados para este contrato.</span>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <section class="document-panel">
    <div class="table-card-head">
      <div>
        <h2>Adjuntos</h2>
        <p>Archivos cargados al expediente del contrato.</p>
      </div>
      <span class="filter-pill"><?= number_format(count($documents), 0, ',', '.') ?> archivos</span>
    </div>
    <div class="table-responsive documents-desktop-table">
      <table class="table table-hover align-middle mb-0 modern-table documents-table">
        <thead><tr><th>Tipo</th><th>Archivo</th><th>Notas</th><th>Fecha</th><th class="text-end">Acciones</th></tr></thead>
        <tbody>
          <?php foreach ($documents as $doc): ?>
            <tr>
              <td><span class="status-badge"><?= htmlspecialchars($doc['document_type'] ?? 'general', ENT_QUOTES, 'UTF-8') ?></span></td>
              <td><strong><?= htmlspecialchars($doc['original_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong></td>
              <td><?= htmlspecialchars($doc['notes'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars($doc['created_at'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
              <td class="text-end">
                <div class="row-actions">
                  <a class="icon-action" href="index.php?r=documentflow.viewer&document_id=<?= (int)$doc['id'] ?>" title="Editor firma"><i class="bi bi-vector-pen"></i></a>
                  <a class="icon-action" href="index.php?r=documents.download&id=<?= (int)$doc['id'] ?>" title="Descargar"><i class="bi bi-download"></i></a>
                  <a class="icon-action danger" onclick="return confirm('?Eliminar documento?')" href="index.php?r=documents.delete&id=<?= (int)$doc['id'] ?>&contract_id=<?= $contractId ?>" title="Eliminar"><i class="bi bi-trash"></i></a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($documents)): ?>
            <tr><td colspan="5"><div class="empty-state"><i class="bi bi-paperclip"></i><strong>Sin adjuntos</strong><span>Aun no hay archivos cargados.</span></div></td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <div class="documents-mobile-list compact">
      <?php foreach ($documents as $doc): ?>
        <article class="document-file-card">
          <span><?= htmlspecialchars($doc['document_type'] ?? 'general', ENT_QUOTES, 'UTF-8') ?></span>
          <h3><?= htmlspecialchars($doc['original_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></h3>
          <p><?= htmlspecialchars($doc['notes'] ?? 'Sin notas', ENT_QUOTES, 'UTF-8') ?></p>
          <small><?= htmlspecialchars($doc['created_at'] ?? '', ENT_QUOTES, 'UTF-8') ?></small>
          <div class="mobile-actions">
            <a class="btn btn-outline-primary" href="index.php?r=documentflow.viewer&document_id=<?= (int)$doc['id'] ?>">Firma</a>
            <a class="btn btn-outline-success" href="index.php?r=documents.download&id=<?= (int)$doc['id'] ?>">Descargar</a>
            <a class="btn btn-outline-danger" onclick="return confirm('?Eliminar documento?')" href="index.php?r=documents.delete&id=<?= (int)$doc['id'] ?>&contract_id=<?= $contractId ?>">Eliminar</a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </section>

  <div class="document-bottom-grid">
    <section class="document-panel">
      <div class="table-card-head">
        <div><h2>Otrosi</h2><p>Registros asociados al contrato.</p></div>
        <span class="filter-pill"><?= number_format(count($addendums), 0, ',', '.') ?></span>
      </div>
      <div class="record-list">
        <?php foreach ($addendums as $addendum): ?>
          <article>
            <strong><?= $dateText($addendum['start_date'] ?? '') ?> - <?= $dateText($addendum['end_date'] ?? '') ?></strong>
            <span><?= $money($addendum['value'] ?? 0) ?></span>
            <p><?= htmlspecialchars($addendum['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
          </article>
        <?php endforeach; ?>
        <?php if (empty($addendums)): ?><div class="empty-state"><i class="bi bi-file-earmark-plus"></i><strong>Sin otrosi</strong><span>No hay registros.</span></div><?php endif; ?>
      </div>
    </section>

    <section class="document-panel">
      <div class="table-card-head">
        <div><h2>Polizas</h2><p>Garantias y vigencias registradas.</p></div>
        <span class="filter-pill"><?= number_format(count($policies), 0, ',', '.') ?></span>
      </div>
      <div class="record-list">
        <?php foreach ($policies as $policy): ?>
          <article>
            <strong><?= htmlspecialchars($policy['policy_type'] ?? '', ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars($policy['policy_number'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong>
            <span><?= $money($policy['insured_value'] ?? 0) ?></span>
            <p><?= htmlspecialchars($policy['provider'] ?? '', ENT_QUOTES, 'UTF-8') ?> - vence <?= $dateText($policy['end_date'] ?? '') ?></p>
          </article>
        <?php endforeach; ?>
        <?php if (empty($policies)): ?><div class="empty-state"><i class="bi bi-shield-check"></i><strong>Sin polizas</strong><span>No hay registros.</span></div><?php endif; ?>
      </div>
    </section>
  </div>
</section>
