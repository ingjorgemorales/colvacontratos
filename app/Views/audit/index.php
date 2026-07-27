<?php
use App\Core\Paginator;
$logs = $logs ?? [];
$filters = $filters ?? [];
$usuarios = $usuarios ?? [];
$modulos = $modulos ?? [];
/** @var Paginator $pg */
$pg = $pg ?? new Paginator(0);

// Icono según el módulo (para leer la bitácora de un vistazo).
$iconoModulo = static function (string $m): string {
    $m = mb_strtolower($m);
    $map = [
        'auth'=>'bi-box-arrow-in-right', 'sesión'=>'bi-box-arrow-in-right', 'sesion'=>'bi-box-arrow-in-right',
        'usuarios'=>'bi-person-gear', 'user'=>'bi-person-gear',
        'perfil'=>'bi-person-circle', 'roles'=>'bi-shield-lock', 'permisos'=>'bi-shield-lock',
        'contrato'=>'bi-file-earmark-text', 'contract'=>'bi-file-earmark-text',
        'proveedor'=>'bi-building', 'provider'=>'bi-building',
        'agente'=>'bi-robot', 'poliza'=>'bi-shield-check', 'polizas'=>'bi-shield-check', 'policy'=>'bi-shield-check',
        'documento'=>'bi-paperclip', 'document'=>'bi-paperclip', 'financ'=>'bi-cash-coin',
    ];
    foreach ($map as $k=>$v) { if (str_contains($m, $k)) return $v; }
    return 'bi-activity';
};
$iniciales = static function (string $n): string {
    $p = preg_split('/\s+/', trim($n)) ?: [];
    return mb_strtoupper(mb_substr($p[0] ?? 'S', 0, 1) . (isset($p[1]) ? mb_substr($p[1], 0, 1) : ''));
};
$baseParams = ['r'=>'audit'] + array_filter([
    'user_id'=>$filters['user_id'] ?: null, 'module'=>$filters['module'] ?: null, 'q'=>$filters['q'] ?: null,
]);
?>
<link rel="stylesheet" href="assets/css/audit.css?v=1">

<section class="aud-modern">
  <div class="aud-hero">
    <div>
      <h1><i class="bi bi-clock-history"></i> Auditoría</h1>
      <p>Trazabilidad de los movimientos de cada usuario: qué hizo, cuándo y desde dónde.</p>
    </div>
    <span class="aud-count"><?= $pg->summary() ?></span>
  </div>

  <form class="aud-filters" method="get">
    <input type="hidden" name="r" value="audit">
    <div class="aud-filter">
      <label>Usuario</label>
      <select name="user_id" class="form-select form-select-sm">
        <option value="">Todos los usuarios</option>
        <?php foreach ($usuarios as $u): ?>
          <option value="<?= (int)$u['id'] ?>" <?= (int)$filters['user_id']===(int)$u['id']?'selected':'' ?>><?= htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="aud-filter">
      <label>Módulo</label>
      <select name="module" class="form-select form-select-sm">
        <option value="">Todos</option>
        <?php foreach ($modulos as $m): ?>
          <option value="<?= htmlspecialchars($m, ENT_QUOTES) ?>" <?= $filters['module']===$m?'selected':'' ?>><?= htmlspecialchars($m, ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="aud-filter aud-filter-grow">
      <label>Buscar</label>
      <input name="q" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['q'], ENT_QUOTES, 'UTF-8') ?>" placeholder="Acción, detalle o usuario…">
    </div>
    <div class="aud-filter-actions">
      <button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-search"></i> Filtrar</button>
      <?php if ($filters['user_id'] || $filters['module'] || $filters['q']): ?>
        <a class="btn btn-light btn-sm" href="index.php?r=audit"><i class="bi bi-x-lg"></i></a>
      <?php endif; ?>
    </div>
  </form>

  <div class="aud-card">
    <?php if (empty($logs)): ?>
      <div class="aud-empty"><i class="bi bi-inbox"></i><strong>Sin movimientos</strong><span>Aún no hay registros con estos filtros. Las acciones de los usuarios se irán registrando aquí.</span></div>
    <?php else: ?>
      <ul class="aud-timeline">
        <?php foreach ($logs as $log): $nombre=(string)($log['user_name'] ?? 'Sistema'); ?>
          <li class="aud-item">
            <div class="aud-avatar" title="<?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($iniciales($nombre), ENT_QUOTES, 'UTF-8') ?></div>
            <div class="aud-body">
              <div class="aud-line">
                <strong class="aud-user"><?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?></strong>
                <span class="aud-action"><?= htmlspecialchars($log['action'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                <?php if (!empty($log['module'])): ?>
                  <span class="aud-mod"><i class="bi <?= $iconoModulo((string)$log['module']) ?>"></i> <?= htmlspecialchars($log['module'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
              </div>
              <?php if (!empty($log['detail'])): ?><div class="aud-detail"><?= htmlspecialchars($log['detail'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
              <div class="aud-meta">
                <span><i class="bi bi-clock"></i> <?= htmlspecialchars($log['created_at'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                <?php if (!empty($log['ip'])): ?><span><i class="bi bi-hdd-network"></i> <?= htmlspecialchars($log['ip'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
              </div>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
      <div class="aud-pager-row">
        <span class="text-muted" style="font-size:12.5px"><?= $pg->summary() ?></span>
        <?= $pg->links($baseParams) ?>
      </div>
    <?php endif; ?>
  </div>
</section>
