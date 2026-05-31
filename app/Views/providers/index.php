<?php
$providers = $providers ?? [];
$q = trim((string)($q ?? ''));
$total = count($providers);
$activeCount = 0;
$inactiveCount = 0;
$withEmail = 0;
$withCity = 0;
$typeCounts = [];

foreach ($providers as $providerRow) {
    if ((int)($providerRow['active'] ?? 0) === 1) {
        $activeCount++;
    } else {
        $inactiveCount++;
    }
    if (trim((string)($providerRow['email'] ?? '')) !== '') {
        $withEmail++;
    }
    if (trim((string)(($providerRow['city_name'] ?? '') ?: ($providerRow['city'] ?? ''))) !== '') {
        $withCity++;
    }
    $typeName = trim((string)($providerRow['type_name'] ?? 'Sin tipo'));
    $typeCounts[$typeName !== '' ? $typeName : 'Sin tipo'] = ($typeCounts[$typeName !== '' ? $typeName : 'Sin tipo'] ?? 0) + 1;
}
arsort($typeCounts);
$mainType = $typeCounts ? array_key_first($typeCounts) : 'Sin datos';

$formatDocument = static function (array $p): string {
    $document = trim((string)($p['document_number'] ?? ''));
    $digit = trim((string)($p['verification_digit'] ?? ''));
    if ($document === '') {
        return 'Sin documento';
    }
    return $document . ($digit !== '' ? '-' . $digit : '');
};

$providerCity = static function (array $p): string {
    $city = trim((string)(($p['city_name'] ?? '') ?: ($p['city'] ?? '')));
    return $city !== '' ? $city : 'Sin ciudad';
};

$contractorProfile = static function (array $p): string {
    $parts = array_filter([
        trim((string)($p['tipo_persona_name'] ?? '')),
        trim((string)($p['naturaleza_name'] ?? '')),
        trim((string)($p['clasificacion_name'] ?? '')),
    ], static fn($value) => $value !== '');
    return $parts ? implode(' / ', $parts) : 'Sin clasificacion';
};

$exportUrl = 'index.php?r=providers.export_excel' . ($q !== '' ? '&q=' . urlencode($q) : '');
?>

<section class="providers-index-modern">
  <div class="module-hero providers-hero">
    <div>
      <span class="section-eyebrow">Maestro de terceros</span>
      <h1>Proveedores</h1>
      <p>Consulta, clasifica y administra los contratistas registrados en ColvaContratos.</p>
    </div>
    <div class="module-actions">
      <a class="btn btn-outline-success" href="<?= htmlspecialchars($exportUrl) ?>">
        <i class="bi bi-file-earmark-excel"></i>
        <span>Exportar Excel</span>
      </a>
      <a class="btn btn-primary" href="index.php?r=providers.create">
        <i class="bi bi-plus-lg"></i>
        <span>Nuevo proveedor</span>
      </a>
    </div>
  </div>

  <div class="provider-summary-strip">
    <article>
      <i class="bi bi-buildings"></i>
      <span>Total proveedores</span>
      <strong><?= number_format($total, 0, ',', '.') ?></strong>
    </article>
    <article>
      <i class="bi bi-check2-circle"></i>
      <span>Activos</span>
      <strong><?= number_format($activeCount, 0, ',', '.') ?></strong>
    </article>
    <article>
      <i class="bi bi-envelope-check"></i>
      <span>Con correo</span>
      <strong><?= number_format($withEmail, 0, ',', '.') ?></strong>
    </article>
    <article>
      <i class="bi bi-diagram-3"></i>
      <span>Tipo principal</span>
      <strong><?= htmlspecialchars($mainType) ?></strong>
    </article>
  </div>

  <form class="filter-panel provider-filter-panel" method="get" action="index.php">
    <input type="hidden" name="r" value="providers">
    <div class="provider-filter-grid">
      <label>
        <span>Busqueda rapida</span>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-search"></i></span>
          <input class="form-control" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="NIT, proveedor, correo, ciudad o clasificacion">
        </div>
      </label>
      <div class="filter-actions">
        <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> Buscar</button>
        <?php if ($q !== ''): ?>
          <a class="btn btn-light" href="index.php?r=providers"><i class="bi bi-x-lg"></i> Limpiar</a>
        <?php endif; ?>
      </div>
    </div>
  </form>

  <div class="providers-table-card">
    <div class="table-card-head">
      <div>
        <h2>Directorio de proveedores</h2>
        <p><?= $q !== '' ? 'Resultados filtrados por "' . htmlspecialchars($q) . '".' : 'Listado completo ordenado alfabeticamente.' ?></p>
      </div>
      <span class="filter-pill"><?= number_format($total, 0, ',', '.') ?> registros</span>
    </div>

    <div class="table-responsive providers-desktop-table">
      <table class="table table-hover align-middle mb-0 providers-table modern-table">
        <thead>
          <tr>
            <th>NIT/ID</th>
            <th>Proveedor</th>
            <th>Clasificacion</th>
            <th>Ciudad</th>
            <th>Contacto</th>
            <th>Estado</th>
            <th class="text-end">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($providers as $p): ?>
            <?php
              $isActive = (int)($p['active'] ?? 0) === 1;
              $document = $formatDocument($p);
              $city = $providerCity($p);
              $profile = $contractorProfile($p);
            ?>
            <tr>
              <td>
                <strong class="provider-doc"><?= htmlspecialchars($document) ?></strong>
                <small><?= htmlspecialchars($p['type_name'] ?? 'Sin tipo') ?></small>
              </td>
              <td>
                <strong><?= htmlspecialchars($p['name'] ?? '') ?></strong>
                <small><?= htmlspecialchars($p['phone'] ?? 'Sin telefono') ?></small>
              </td>
              <td>
                <span class="provider-type"><?= htmlspecialchars($p['tipo_contratista_name'] ?? 'Sin tipo contratista') ?></span>
                <small><?= htmlspecialchars($profile) ?></small>
              </td>
              <td><?= htmlspecialchars($city) ?></td>
              <td>
                <strong><?= htmlspecialchars($p['contact_name'] ?: 'Sin contacto') ?></strong>
                <small><?= htmlspecialchars($p['email'] ?: 'Sin correo') ?></small>
              </td>
              <td>
                <span class="status-badge <?= $isActive ? 'provider-active' : 'provider-inactive' ?>">
                  <i class="bi <?= $isActive ? 'bi-check-circle' : 'bi-pause-circle' ?>"></i>
                  <?= $isActive ? 'Activo' : 'Inactivo' ?>
                </span>
              </td>
              <td class="text-end">
                <div class="row-actions">
                  <a class="icon-action" href="index.php?r=providers.edit&id=<?= (int)$p['id'] ?>" title="Editar proveedor" aria-label="Editar proveedor">
                    <i class="bi bi-pencil-square"></i>
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($providers)): ?>
            <tr>
              <td colspan="7">
                <div class="empty-state">
                  <i class="bi bi-search"></i>
                  <strong>No hay proveedores para mostrar</strong>
                  <span>Revisa el filtro aplicado o crea un nuevo proveedor.</span>
                </div>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <div class="providers-mobile-list">
      <?php foreach ($providers as $p): ?>
        <?php
          $isActive = (int)($p['active'] ?? 0) === 1;
          $document = $formatDocument($p);
          $city = $providerCity($p);
          $profile = $contractorProfile($p);
        ?>
        <article class="provider-mobile-card">
          <div class="provider-card-top">
            <div>
              <span><?= htmlspecialchars($document) ?></span>
              <h3><?= htmlspecialchars($p['name'] ?? '') ?></h3>
            </div>
            <span class="status-badge <?= $isActive ? 'provider-active' : 'provider-inactive' ?>">
              <?= $isActive ? 'Activo' : 'Inactivo' ?>
            </span>
          </div>
          <dl>
            <div>
              <dt>Tipo proveedor</dt>
              <dd><?= htmlspecialchars($p['type_name'] ?? 'Sin tipo') ?></dd>
            </div>
            <div>
              <dt>Ciudad</dt>
              <dd><?= htmlspecialchars($city) ?></dd>
            </div>
            <div>
              <dt>Contacto</dt>
              <dd><?= htmlspecialchars($p['contact_name'] ?: 'Sin contacto') ?></dd>
            </div>
            <div>
              <dt>Correo</dt>
              <dd><?= htmlspecialchars($p['email'] ?: 'Sin correo') ?></dd>
            </div>
            <div class="provider-card-wide">
              <dt>Clasificacion</dt>
              <dd><?= htmlspecialchars(trim((string)($p['tipo_contratista_name'] ?? 'Sin tipo contratista')) . ' - ' . $profile) ?></dd>
            </div>
          </dl>
          <a class="btn btn-outline-primary" href="index.php?r=providers.edit&id=<?= (int)$p['id'] ?>">
            <i class="bi bi-pencil-square"></i> Editar proveedor
          </a>
        </article>
      <?php endforeach; ?>
      <?php if (empty($providers)): ?>
        <div class="empty-state">
          <i class="bi bi-search"></i>
          <strong>No hay proveedores para mostrar</strong>
          <span>Revisa el filtro aplicado o crea un nuevo proveedor.</span>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>
