<?php
$active = $section ?? 'panel';
$tabs = [
    'panel' => ['Panel Admin', 'bi-sliders'],
    'users' => ['Usuarios / perfiles', 'bi-person-gear'],
    'supervisors' => ['Supervisores', 'bi-people'],
    'providers' => ['Proveedores', 'bi-building'],
    'catalogs' => ['Campos seleccionables', 'bi-ui-checks-grid'],
    'assign' => ['Asignar contratos', 'bi-diagram-3'],
];
?>
<section class="admin-modern-head">
  <div class="module-hero admin-hero">
    <div>
      <span class="section-eyebrow">Administracion</span>
      <h1>Panel Admin</h1>
      <p>Administracion general, usuarios, perfiles, supervisores, proveedores y campos seleccionables.</p>
    </div>
  </div>
  <nav class="admin-tabs-modern" aria-label="Secciones de administracion">
    <?php foreach ($tabs as $key => [$label, $icon]): ?>
      <a class="<?= $active === $key ? 'active' : '' ?>" href="index.php?r=admin.<?= $key ?>">
        <i class="bi <?= $icon ?>"></i>
        <span><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
      </a>
    <?php endforeach; ?>
  </nav>
</section>