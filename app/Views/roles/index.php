<?php
$roles   = $roles ?? [];
$modulos = $modulos ?? [];
$matriz  = $matriz ?? [];
$idAdmin = $idAdmin ?? 1;

/** ¿El rol tiene marcado ese módulo? (admin siempre sí; reglas fijas mandan) */
$marcado = static function (int $rid, string $clave, array $cfg) use ($matriz, $idAdmin): bool {
    if ($rid === $idAdmin) return true;
    if (!empty($cfg['siempre'])) return true;
    if (!empty($cfg['solo_admin'])) return false;
    if (!isset($matriz[$rid])) return true;      // rol sin configurar: todo abierto aún
    return (bool)($matriz[$rid][$clave] ?? false);
};
/** ¿La casilla es editable? (las reglas fijas no lo son) */
$editable = static function (int $rid, array $cfg) use ($idAdmin): bool {
    return $rid !== $idAdmin && empty($cfg['siempre']) && empty($cfg['solo_admin']);
};
?>
<link rel="stylesheet" href="assets/css/roles.css?v=1">

<section class="rp-modern">
  <div class="rp-hero">
    <h1><i class="bi bi-shield-lock"></i> Roles y permisos</h1>
    <p>Define a qué vistas puede entrar cada rol. Marca la casilla para dar acceso y desmárcala para ocultar la vista a ese rol.</p>
  </div>

  <div class="rp-legend">
    <span><i class="bi bi-lock-fill"></i> El rol <strong>admin</strong> siempre tiene acceso a todo (no editable).</span>
    <span><i class="bi bi-person-circle"></i> <strong>Mi perfil</strong> está siempre disponible para todos.</span>
    <span><i class="bi bi-shield-lock"></i> <strong>Roles y permisos</strong> es exclusiva del admin.</span>
  </div>

  <form method="post" action="index.php?r=roles.save" id="rp-form">
    <div class="rp-card">
      <div class="table-responsive">
        <table class="rp-table">
          <thead>
            <tr>
              <th class="rp-col-vista">Vista / módulo</th>
              <?php foreach ($roles as $rol): $rid = (int)$rol['id']; ?>
                <th class="rp-col-rol">
                  <span class="rp-rol-name"><?= htmlspecialchars($rol['name'], ENT_QUOTES, 'UTF-8') ?></span>
                  <?php if ($rid === $idAdmin): ?>
                    <span class="rp-tag-admin">máximo</span>
                  <?php else: ?>
                    <button type="button" class="rp-all" data-rol="<?= $rid ?>">Todo / nada</button>
                  <?php endif; ?>
                </th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($modulos as $clave => $cfg): ?>
              <tr>
                <td class="rp-col-vista">
                  <i class="bi <?= htmlspecialchars($cfg['icono'], ENT_QUOTES, 'UTF-8') ?>"></i>
                  <span><?= htmlspecialchars($cfg['label'], ENT_QUOTES, 'UTF-8') ?></span>
                  <?php if (!empty($cfg['siempre'])): ?><em class="rp-nota">siempre visible</em><?php endif; ?>
                  <?php if (!empty($cfg['solo_admin'])): ?><em class="rp-nota">solo admin</em><?php endif; ?>
                </td>
                <?php foreach ($roles as $rol): $rid = (int)$rol['id']; $on = $marcado($rid, $clave, $cfg); $ed = $editable($rid, $cfg); ?>
                  <td class="rp-col-rol">
                    <label class="rp-switch <?= $ed ? '' : 'is-fixed' ?>" title="<?= $ed ? 'Clic para cambiar' : 'No editable' ?>">
                      <input type="checkbox"
                             name="permisos[<?= $rid ?>][]"
                             value="<?= htmlspecialchars($clave, ENT_QUOTES, 'UTF-8') ?>"
                             data-rol="<?= $rid ?>"
                             <?= $on ? 'checked' : '' ?>
                             <?= $ed ? '' : 'disabled' ?>>
                      <span class="rp-slider"></span>
                    </label>
                  </td>
                <?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="rp-actions">
      <span class="rp-hint" id="rp-hint">Los cambios se aplican al guardar.</span>
      <button class="btn btn-primary" type="submit"><i class="bi bi-save"></i> Guardar permisos</button>
    </div>
  </form>
</section>

<script src="assets/js/roles.js?v=1"></script>
