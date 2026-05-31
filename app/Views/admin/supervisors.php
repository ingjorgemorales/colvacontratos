<?php include __DIR__ . '/_tabs.php'; ?>
<section class="admin-screen-modern">
  <section class="admin-card">
    <div class="form-section-head"><i class="bi bi-person-plus"></i><div><h2>Crear supervisor</h2><p>Responsables asociados a contratos y usuarios.</p></div></div>
    <form method="post" action="index.php?r=admin.supervisors.store" class="admin-form-grid">
      <label class="form-field"><span>Usuario asociado</span><select name="user_id" class="form-select"><option value="">Sin asociar</option><?php foreach ($users as $userRow): ?><option value="<?= (int)$userRow['id'] ?>"><?= htmlspecialchars($userRow['name'] . ' - ' . $userRow['email'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></label>
      <label class="form-field"><span>Cédula</span><input name="document_number" required class="form-control"></label>
      <label class="form-field"><span>Nombre completo</span><input name="full_name" required class="form-control"></label>
      <label class="form-field"><span>Email</span><input type="email" name="email" class="form-control"></label>
      <button class="btn btn-primary admin-form-submit" type="submit"><i class="bi bi-save"></i> Guardar supervisor</button>
    </form>
  </section>
  <section class="admin-card table-card">
    <div class="table-card-head"><div><h2>Supervisores registrados</h2><p>Actualiza responsables y asociación con usuarios.</p></div><span class="filter-pill"><?= number_format(count($supervisors ?? []), 0, ',', '.') ?> registros</span></div>
    <div class="table-responsive"><table class="table table-hover align-middle mb-0 modern-table admin-table"><thead><tr><th>Cédula</th><th>Supervisor</th><th>Usuario</th><th>Email</th><th>Activo</th><th>Acciones</th></tr></thead><tbody>
      <?php foreach ($supervisors as $supervisor): ?><tr><form method="post" action="index.php?r=admin.supervisors.update&id=<?= (int)$supervisor['id'] ?>"><td><input name="document_number" class="form-control form-control-sm" value="<?= htmlspecialchars($supervisor['document_number'], ENT_QUOTES, 'UTF-8') ?>"></td><td><input name="full_name" class="form-control form-control-sm" value="<?= htmlspecialchars($supervisor['full_name'], ENT_QUOTES, 'UTF-8') ?>"></td><td><select name="user_id" class="form-select form-select-sm"><option value="">Sin asociar</option><?php foreach ($users as $userRow): ?><option value="<?= (int)$userRow['id'] ?>" <?= ((int)($supervisor['user_id'] ?? 0) === (int)$userRow['id']) ? 'selected' : '' ?>><?= htmlspecialchars($userRow['name'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></td><td><input name="email" class="form-control form-control-sm" value="<?= htmlspecialchars($supervisor['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></td><td><input type="checkbox" name="active" value="1" <?= ((int)$supervisor['active'] === 1) ? 'checked' : '' ?>></td><td><button class="btn btn-sm btn-primary"><i class="bi bi-check2"></i></button></td></form></tr><?php endforeach; ?>
    </tbody></table></div>
  </section>
</section>