<?php include __DIR__ . '/_tabs.php'; ?>
<section class="admin-screen-modern">
  <section class="admin-card">
    <div class="form-section-head"><i class="bi bi-building-add"></i><div><h2>Crear proveedor</h2><p>Alta básica de terceros para uso contractual.</p></div></div>
    <form id="crearProveedor" method="post" action="index.php?r=admin.providers.store" class="admin-form-grid">
      <label class="form-field"><span>NIT / Documento</span><input name="document_number" required class="form-control" placeholder="NIT o documento"></label>
      <label class="form-field"><span>DV</span><input name="verification_digit" class="form-control" placeholder="DV"></label>
      <label class="form-field"><span>Proveedor</span><input name="name" required class="form-control" placeholder="Nombre del proveedor"></label>
      <label class="form-field"><span>Email</span><input type="email" name="email" class="form-control" placeholder="Email"></label>
      <label class="form-field"><span>Dirección</span><input name="address" class="form-control"></label>
      <label class="form-field"><span>Teléfono</span><input name="phone" class="form-control"></label>
      <label class="form-field"><span>Ciudad</span><input name="city" class="form-control"></label>
      <button class="btn btn-primary admin-form-submit" type="submit"><i class="bi bi-save"></i> Guardar proveedor</button>
    </form>
  </section>

  <section class="admin-card table-card">
    <div class="table-card-head"><div><h2>Proveedores registrados</h2><p>Edición rápida de datos básicos.</p></div><form method="get" class="admin-inline-search"><input type="hidden" name="r" value="admin.providers"><input name="q" class="form-control form-control-sm" value="<?= htmlspecialchars($q ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Buscar proveedor"><button class="btn btn-sm btn-outline-primary">Buscar</button></form></div>
    <div class="table-responsive"><table class="table table-hover align-middle mb-0 modern-table admin-table"><thead><tr><th>NIT</th><th>Proveedor</th><th>Teléfono</th><th>Email</th><th>Ciudad</th><th>Activo</th><th>Acciones</th></tr></thead><tbody>
      <?php foreach ($providers as $provider): ?><tr><form method="post" action="index.php?r=admin.providers.update&id=<?= (int)$provider['id'] ?>"><td><input name="document_number" class="form-control form-control-sm" value="<?= htmlspecialchars($provider['document_number'], ENT_QUOTES, 'UTF-8') ?>"><input name="verification_digit" class="form-control form-control-sm mt-1" value="<?= htmlspecialchars($provider['verification_digit'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="DV"></td><td><input name="name" class="form-control form-control-sm" value="<?= htmlspecialchars($provider['name'], ENT_QUOTES, 'UTF-8') ?>"><input name="address" class="form-control form-control-sm mt-1" value="<?= htmlspecialchars($provider['address'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Dirección"></td><td><input name="phone" class="form-control form-control-sm" value="<?= htmlspecialchars($provider['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></td><td><input name="email" class="form-control form-control-sm" value="<?= htmlspecialchars($provider['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></td><td><input name="city" class="form-control form-control-sm" value="<?= htmlspecialchars($provider['city'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></td><td><input type="checkbox" name="active" value="1" <?= ((int)($provider['active'] ?? 1) === 1) ? 'checked' : '' ?>></td><td><button class="btn btn-sm btn-primary"><i class="bi bi-check2"></i></button></td></form></tr><?php endforeach; ?>
    </tbody></table></div>
  </section>
</section>