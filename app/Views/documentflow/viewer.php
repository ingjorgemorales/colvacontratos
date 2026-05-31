<div class="page-head d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <div><h1 class="h3 mb-1">Editor documental tipo DocuSeal</h1><p class="text-muted mb-0">Contrato: <?= htmlspecialchars($contract['title'] ?? $contract['number'] ?? ('#'.$doc['contract_id'])) ?></p></div>
  <a class="btn btn-outline-secondary" href="index.php?r=documents&contract_id=<?= (int)$doc['contract_id'] ?>">Volver a documentos</a>
</div>
<div class="row g-3">
  <div class="col-lg-8"><div class="card-pro p-3">
    <div class="d-flex justify-content-between align-items-center mb-2"><strong><?= htmlspecialchars($doc['original_name'] ?? 'Documento') ?></strong><span class="badge text-bg-info">Estado firma: <?= htmlspecialchars($doc['signed_status'] ?? 'pendiente') ?></span></div>
    <div class="doc-preview-wrap">
      <?php $file = htmlspecialchars($doc['path_resolved'] ?? $doc['file_path'] ?? $doc['stored_path'] ?? ''); ?>
      <?php if(preg_match('/\.pdf$/i',$file)): ?><iframe class="doc-preview" src="index.php?r=documents.download&id=<?= (int)$doc['id'] ?>"></iframe>
      <?php else: ?><div class="doc-preview-empty"><i class="bi bi-file-earmark"></i><p>Vista previa disponible principalmente para PDF.</p><a class="btn btn-primary" href="index.php?r=documents.download&id=<?= (int)$doc['id'] ?>">Descargar</a></div><?php endif; ?>
    </div>
  </div></div>
  <div class="col-lg-4">
    <div class="card-pro p-3 mb-3"><h5>Agregar campo</h5>
      <form method="post" action="index.php?r=documentflow.field.store" class="row g-2">
        <input type="hidden" name="contract_id" value="<?= (int)$doc['contract_id'] ?>"><input type="hidden" name="document_id" value="<?= (int)$doc['id'] ?>">
        <div class="col-12"><input class="form-control" name="signer_name" placeholder="Nombre firmante" required></div>
        <div class="col-12"><input class="form-control" name="signer_email" placeholder="Correo firmante"></div>
        <div class="col-6"><select class="form-select" name="field_type"><option value="firma">Firma</option><option value="inicial">Inicial</option><option value="fecha">Fecha</option><option value="texto">Texto</option><option value="check">Check</option></select></div>
        <div class="col-6"><input class="form-control" name="role_name" placeholder="Rol"></div>
        <div class="col-3"><input class="form-control" name="page_number" value="1" placeholder="Pág"></div>
        <div class="col-3"><input class="form-control" name="x_pos" value="10" placeholder="X %"></div>
        <div class="col-3"><input class="form-control" name="y_pos" value="10" placeholder="Y %"></div>
        <div class="col-3"><input class="form-control" name="width_pos" value="25" placeholder="W %"></div>
        <div class="col-12"><input class="form-control" name="height_pos" value="8" placeholder="Alto %"></div>
        <div class="col-12"><button class="btn btn-primary w-100">Agregar campo</button></div>
      </form>
    </div>
    <div class="card-pro p-3 mb-3"><h5>Campos configurados</h5>
      <?php foreach($fields as $f): ?><div class="sign-field-row"><div><strong><?= htmlspecialchars($f['field_type']) ?></strong><br><small><?= htmlspecialchars($f['signer_name']) ?> - Pág <?= (int)$f['page_number'] ?></small></div><a class="btn btn-sm btn-outline-success" href="index.php?r=documentflow.field.sign&id=<?= (int)$f['id'] ?>&document_id=<?= (int)$doc['id'] ?>">Firmado</a></div><?php endforeach; if(!$fields): ?><p class="text-muted">Sin campos.</p><?php endif; ?>
    </div>
    <div class="card-pro p-3"><h5>Versiones</h5><?php foreach($versions as $v): ?><div class="small border-bottom py-2">v<?= (int)$v['version_number'] ?> - <?= htmlspecialchars($v['created_at']) ?></div><?php endforeach; if(!$versions): ?><p class="text-muted">Sin versiones registradas.</p><?php endif; ?></div>
  </div>
</div>
