<?php include __DIR__.'/_tabs.php';
$groupMap=[]; foreach(($groups ?? []) as $g){ $groupMap[$g['id']]=$g; }
$currentGroup = $group_id && isset($groupMap[$group_id]) ? $groupMap[$group_id] : null;
$grouped=[]; foreach(($groups ?? []) as $g){ $grouped[$g['category']][]=$g; }
?>
<div class="param-pro-page">
  <div class="param-hero card-pro mb-3">
    <div>
      <div class="text-uppercase small fw-bold text-primary">Gestión de catálogos</div>
      <h3 class="mb-1">Panel inteligente de paramétricas</h3>
      <p class="text-muted mb-0">Administra catálogos por grupo, busca opciones y evita navegar por listas largas.</p>
    </div>
    <div class="param-stats">
      <div class="param-stat"><i class="bi bi-collection"></i><b><?= (int)($stats['catalogs'] ?? 0) ?></b><span>Catálogos</span></div>
      <div class="param-stat"><i class="bi bi-list-check"></i><b><?= (int)($stats['items'] ?? 0) ?></b><span>Opciones</span></div>
      <div class="param-stat"><i class="bi bi-check-circle"></i><b><?= (int)($stats['active'] ?? 0) ?></b><span>Activas</span></div>
    </div>
  </div>

  <form class="card-pro param-filter mb-3" method="get" action="index.php">
    <input type="hidden" name="r" value="admin.catalogs">
    <div class="row g-2 align-items-center">
      <div class="col-lg-5"><input name="q" class="form-control" value="<?= htmlspecialchars($q ?? '') ?>" placeholder="Buscar por código, nombre o catálogo..."></div>
      <div class="col-lg-4">
        <select name="group_id" class="form-select" onchange="this.form.submit()">
          <option value="">Todos los catálogos</option>
          <?php foreach($grouped as $cat=>$catGroups): ?>
            <optgroup label="<?= htmlspecialchars($cat) ?>">
              <?php foreach($catGroups as $g): ?>
                <option value="<?= htmlspecialchars($g['id']) ?>" <?= ((string)$group_id===(string)$g['id'])?'selected':'' ?>><?= htmlspecialchars($g['name']) ?></option>
              <?php endforeach; ?>
            </optgroup>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-lg-3 d-grid"><button class="btn btn-primary"><i class="bi bi-search"></i> Buscar / filtrar</button></div>
    </div>
  </form>

  <?php if(!$currentGroup && empty($q)): ?>
    <div class="card-pro mb-3">
      <div class="card-pro-head"><h5>Catálogos por grupo funcional</h5></div>
      <div class="param-category-grid p-3">
        <?php foreach(($categoryCards ?? []) as $card): ?>
          <div class="param-category-card">
            <div class="param-category-icon"><i class="bi <?= htmlspecialchars($card['icon']) ?>"></i></div>
            <div class="flex-grow-1">
              <h5><?= htmlspecialchars($card['name']) ?></h5>
              <p><?= htmlspecialchars($card['description']) ?></p>
              <span><?= (int)$card['count'] ?> catálogos</span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <div class="row g-3">
    <div class="col-xl-4">
      <div class="card-pro h-100">
        <div class="card-pro-head d-flex justify-content-between align-items-center"><h5>Catálogos disponibles</h5><span class="badge bg-primary"><?= count($groups ?? []) ?></span></div>
        <div class="param-group-list">
          <?php foreach($grouped as $cat=>$catGroups): ?>
            <div class="param-group-title"><?= htmlspecialchars($cat) ?></div>
            <?php foreach($catGroups as $g): ?>
              <a class="param-catalog-item <?= ((string)$group_id===(string)$g['id'])?'active':'' ?>" href="index.php?r=admin.catalogs&group_id=<?= urlencode($g['id']) ?>">
                <i class="bi <?= htmlspecialchars($g['icon'] ?? 'bi-grid') ?>"></i>
                <span><b><?= htmlspecialchars($g['name']) ?></b><small><?= htmlspecialchars($g['description'] ?? $g['id']) ?></small></span>
              </a>
            <?php endforeach; ?>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <div class="col-xl-8">
      <div class="card-pro mb-3">
        <div class="card-pro-head"><h5><?= $currentGroup ? 'Crear opción en '.htmlspecialchars($currentGroup['name']) : 'Crear opción seleccionable' ?></h5></div>
        <form method="post" action="index.php?r=admin.catalogs.item.store" class="row g-2 p-3">
          <div class="col-md-4">
            <label class="form-label small fw-bold">Catálogo</label>
            <select name="group_id" class="form-select" required>
              <?php foreach($grouped as $cat=>$catGroups): ?>
                <optgroup label="<?= htmlspecialchars($cat) ?>">
                  <?php foreach($catGroups as $g): ?>
                    <option value="<?= htmlspecialchars($g['id']) ?>" <?= ((string)$group_id===(string)$g['id'])?'selected':'' ?>><?= htmlspecialchars($g['name']) ?></option>
                  <?php endforeach; ?>
                </optgroup>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-2"><label class="form-label small fw-bold">Código</label><input name="code" class="form-control" placeholder="Ej: CLI"></div>
          <div class="col-md-4"><label class="form-label small fw-bold">Nombre</label><input name="name" class="form-control" placeholder="Nombre visible" required></div>
          <div class="col-md-2"><label class="form-label small fw-bold">Orden</label><input name="sort_order" type="number" class="form-control" value="0"></div>
          <div class="col-12"><button class="btn btn-primary"><i class="bi bi-plus-circle"></i> Guardar opción</button></div>
        </form>
      </div>

      <div class="card-pro">
        <div class="card-pro-head d-flex justify-content-between align-items-center">
          <h5>Opciones registradas</h5>
          <span class="badge text-bg-light"><?= count($items ?? []) ?> resultados</span>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 param-table">
            <thead><tr><th>Catálogo</th><th>Código</th><th>Nombre</th><th>Orden</th><th>Activo</th><th></th></tr></thead>
            <tbody>
              <?php foreach(($items ?? []) as $i): ?>
                <tr>
                  <form method="post" action="index.php?r=admin.catalogs.item.update&id=<?= (int)$i['id'] ?>">
                    <input type="hidden" name="group_id" value="<?= htmlspecialchars($i['group_id']) ?>">
                    <td><b><?= htmlspecialchars($i['group_name']) ?></b><br><small class="text-muted"><?= htmlspecialchars($i['category'] ?? $i['group_id']) ?></small></td>
                    <td><input name="code" class="form-control form-control-sm" value="<?= htmlspecialchars($i['code']) ?>"></td>
                    <td><input name="name" class="form-control form-control-sm" value="<?= htmlspecialchars($i['name']) ?>"></td>
                    <td><input name="sort_order" type="number" class="form-control form-control-sm" value="<?= (int)$i['sort_order'] ?>"></td>
                    <td><input class="form-check-input" type="checkbox" name="active" value="1" <?= ((int)$i['active']===1)?'checked':'' ?>></td>
                    <td><button class="btn btn-sm btn-primary"><i class="bi bi-check2"></i></button></td>
                  </form>
                </tr>
              <?php endforeach; ?>
              <?php if(empty($items)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">No hay opciones con los filtros aplicados.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
