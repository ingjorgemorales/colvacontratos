<?php include __DIR__ . '/_tabs.php'; ?>
<section class="admin-screen-modern">
  <section class="admin-card">
    <div class="form-section-head"><i class="bi bi-person-plus"></i><div><h2>Crear usuario</h2><p>Alta rápida de usuarios del sistema.</p></div></div>
    <form method="post" action="index.php?r=admin.users.store" class="admin-form-grid">
      <label class="form-field"><span>Nombre</span><input name="name" required class="form-control" placeholder="Nombre completo"></label>
      <label class="form-field"><span>Email / usuario</span><input type="email" name="email" required class="form-control" placeholder="correo@colvatel.com"></label>
      <label class="form-field"><span>Perfil</span><select name="role_id" class="form-select"><?php foreach ($roles as $role): ?><option value="<?= (int)$role['id'] ?>"><?= htmlspecialchars($role['name'] . ' - ' . ($role['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></label>
      <label class="form-field"><span>Clave inicial</span><input name="password" class="form-control" placeholder="Opcional: Colvatel2026* por defecto"></label>
      <button class="btn btn-primary admin-form-submit" type="submit"><i class="bi bi-save"></i> Guardar usuario</button>
    </form>
  </section>

  <section class="admin-card table-card">
    <div class="table-card-head"><div><h2>Usuarios registrados</h2><p>Actualiza datos, perfil, estado o contraseña.</p></div><span class="filter-pill"><?= number_format(count($users ?? []), 0, ',', '.') ?> usuarios</span></div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0 modern-table admin-table">
        <thead><tr><th>ID</th><th>Usuario</th><th>Email</th><th>Perfil</th><th>Activo</th><th>Acciones</th></tr></thead>
        <tbody>
          <?php foreach ($users as $userRow): ?>
            <tr>
              <form method="post" action="index.php?r=admin.users.update&id=<?= (int)$userRow['id'] ?>">
                <td><strong>#<?= (int)$userRow['id'] ?></strong></td>
                <td><input name="name" class="form-control form-control-sm" value="<?= htmlspecialchars($userRow['name'], ENT_QUOTES, 'UTF-8') ?>"></td>
                <td><input name="email" class="form-control form-control-sm" value="<?= htmlspecialchars($userRow['email'], ENT_QUOTES, 'UTF-8') ?>"></td>
                <td><select name="role_id" class="form-select form-select-sm"><?php foreach ($roles as $role): ?><option value="<?= (int)$role['id'] ?>" <?= ((int)$userRow['role_id'] === (int)$role['id']) ? 'selected' : '' ?>><?= htmlspecialchars($role['name'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></td>
                <td><input type="checkbox" name="active" value="1" <?= ((int)$userRow['active'] === 1) ? 'checked' : '' ?>></td>
                <td><div class="admin-row-actions"><input name="password" class="form-control form-control-sm" placeholder="Nueva clave"><button class="btn btn-sm btn-primary" type="submit"><i class="bi bi-check2"></i></button></div></td>
              </form>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
</section>