<?php
use App\Core\Auth;
use App\Core\Flash;

$flash = Flash::get();
$isLogged = Auth::check();
$user = Auth::user();
$route = $_GET['r'] ?? 'dashboard';

$routeTitles = [
    'dashboard' => 'Indicadores',
    'contracts' => 'Contratos',
    'providers' => 'Proveedores',
    'documents' => 'Documental',
    'polizas' => 'Revision de polizas',
    'finance' => 'Financiera',
    'reports' => 'Reportes',
    'alerts' => 'Alertas',
    'intelligence' => 'Inteligencia KPI',
    'audit' => 'Auditoria',
    'admin.users' => 'Usuarios',
    'admin.catalogs' => 'Parametricas',
    'admin.panel' => 'Panel admin',
];
$pageTitle = $routeTitles[$route] ?? 'ColvaContratos';
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= htmlspecialchars($app['name'] ?? 'ColvaContratos') ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/app.css?v=20260528_professional" rel="stylesheet">
  <link href="assets/css/inteligencia.css?v=20260528_professional" rel="stylesheet">
  <link href="assets/css/parametricas.css?v=20260528_professional" rel="stylesheet">
  <link href="assets/css/policy_reviews.css?v=20260528_professional" rel="stylesheet">
  <link href="assets/css/ui-modern.css?v=20260528_professional" rel="stylesheet">
</head>
<body class="<?= $isLogged ? 'app-shell app-shell-pro ui-shell modern-shell' : 'login-shell' ?>">
<div class="app-loader" id="appLoader" aria-hidden="true">
  <div class="loader-mark">
    <img src="assets/img/logo.png" alt="">
  </div>
</div>

<?php if($isLogged): ?>
  <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
  <aside class="sidebar-pro ui-sidebar modern-sidebar" id="appSidebar" aria-label="Menu principal">
    <div class="brand-box ui-brand-box modern-brand">
      <a class="brand-link" href="index.php?r=dashboard" aria-label="Ir al inicio">
        <img src="assets/img/logo.png" class="ui-logo modern-logo" alt="COLVATEL">
      </a>
      <button class="side-toggle desktop-collapse" id="sideToggle" type="button" aria-label="Contraer menu">
        <i class="bi bi-layout-sidebar-inset"></i>
      </button>
      <button class="side-toggle mobile-close" id="sidebarClose" type="button" aria-label="Cerrar menu">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>

    <div class="user-box modern-user">
      <div class="avatar-pro"><i class="bi bi-person-fill"></i></div>
      <div class="user-meta">
        <div class="user-name"><?= htmlspecialchars($user['name'] ?? 'Administrador Colvatel') ?></div>
        <div class="user-role"><?= htmlspecialchars($user['role_name'] ?? 'Administrador') ?></div>
      </div>
    </div>

    <nav class="side-menu ui-menu modern-menu">
      <a class="<?= $route==='dashboard'?'active':'' ?>" href="index.php?r=dashboard"><i class="bi bi-speedometer2"></i><span>Indicadores</span></a>
      <a class="<?= str_starts_with($route,'contracts')?'active':'' ?>" href="index.php?r=contracts"><i class="bi bi-file-earmark-text"></i><span>Contratos</span></a>
      <a class="<?= str_starts_with($route,'providers')?'active':'' ?>" href="index.php?r=providers"><i class="bi bi-building"></i><span>Proveedores</span></a>

      <details class="cc-param-details" <?= in_array($route,['admin.catalogs','areas','admin.providers'])?'open':'' ?>>
        <summary class="cc-param-summary">
          <span class="cc-param-title">
            <i class="bi bi-gear-fill"></i>
            <span>Param&eacute;tricas</span>
          </span>
          <i class="bi bi-chevron-down cc-param-chevron"></i>
        </summary>

        <div class="cc-param-menu">
          <details class="cc-param-section">
            <summary>
              <span>Contratos</span>
              <i class="bi bi-chevron-down"></i>
            </summary>
            <div class="cc-param-items">
              <a href="index.php?r=admin.catalogs&group_id=contract_types"><span>Tipos de contrato</span></a>
              <a href="index.php?r=admin.catalogs&group_id=contract_statuses"><span>Estados de contrato</span></a>
              <a href="index.php?r=admin.catalogs&group_id=contract_tipo_compromiso"><span>Tipo compromiso</span></a>
              <a href="index.php?r=admin.catalogs&group_id=contract_modalidad_seleccion"><span>Modalidad selecci&oacute;n</span></a>
              <a href="index.php?r=admin.catalogs&group_id=contract_tema_gasto"><span>Tema gasto</span></a>
              <a href="index.php?r=admin.catalogs&group_id=contract_tipologia_especifica"><span>Tipolog&iacute;a espec&iacute;fica</span></a>
              <a href="index.php?r=admin.catalogs&group_id=contract_regimen_contratacion"><span>R&eacute;gimen contrataci&oacute;n</span></a>
            </div>
          </details>

          <details class="cc-param-section">
            <summary>
              <span>Organizacional</span>
              <i class="bi bi-chevron-down"></i>
            </summary>
            <div class="cc-param-items">
              <a href="index.php?r=admin.catalogs&group_id=provider_cities"><span>Ciudades</span></a>
              <a href="index.php?r=admin.catalogs&group_id=departments"><span>Departamentos</span></a>
              <a href="index.php?r=admin.catalogs&group_id=areas"><span>&Aacute;reas</span></a>
              <a href="index.php?r=admin.catalogs&group_id=cost_centers"><span>CECOS</span></a>
            </div>
          </details>

          <details class="cc-param-section">
            <summary>
              <span>Financiero</span>
              <i class="bi bi-chevron-down"></i>
            </summary>
            <div class="cc-param-items">
              <a href="index.php?r=admin.catalogs&group_id=contract_tipo_gasto"><span>Tipo gasto</span></a>
              <a href="index.php?r=admin.catalogs&group_id=contract_origen_presupuesto"><span>Origen presupuesto</span></a>
              <a href="index.php?r=admin.catalogs&group_id=contract_origen_recursos"><span>Origen recursos</span></a>
              <a href="index.php?r=admin.catalogs&group_id=contract_tipo_moneda"><span>Monedas</span></a>
              <a href="index.php?r=admin.catalogs&group_id=contract_unidad_plazo"><span>Unidad plazo</span></a>
              <a href="index.php?r=admin.catalogs&group_id=contract_tipo_control"><span>Tipo control</span></a>
              <a href="index.php?r=admin.catalogs&group_id=contract_procedimiento"><span>Procedimiento</span></a>
            </div>
          </details>

          <details class="cc-param-section">
            <summary>
              <span>Documental</span>
              <i class="bi bi-chevron-down"></i>
            </summary>
            <div class="cc-param-items">
              <a href="index.php?r=admin.catalogs&group_id=contract_document_types"><span>Tipos documento</span></a>
            </div>
          </details>
        </div>
      </details>

      <a class="<?= str_starts_with($route,'documents') || str_starts_with($route,'documentflow')?'active':'' ?>" href="index.php?r=documents"><i class="bi bi-paperclip"></i><span>Documental</span></a>
      <a class="<?= str_starts_with($route,'polizas') || str_starts_with($route,'policy_reviews')?'active':'' ?>" href="index.php?r=polizas"><i class="bi bi-shield-check"></i><span>Revisi&oacute;n de p&oacute;lizas</span></a>
      <a class="<?= str_starts_with($route,'agente')?'active':'' ?>" href="index.php?r=agente"><i class="bi bi-robot"></i><span>Agente de P&oacute;lizas</span></a>
      <a class="<?= str_starts_with($route,'finance')?'active':'' ?>" href="index.php?r=finance"><i class="bi bi-cash-coin"></i><span>Financiera</span></a>
      <a class="<?= str_starts_with($route,'reports')?'active':'' ?>" href="index.php?r=reports"><i class="bi bi-bar-chart-line"></i><span>Reportes</span></a>
      <a class="<?= str_starts_with($route,'alerts')?'active':'' ?>" href="index.php?r=alerts"><i class="bi bi-bell"></i><span>Alertas</span><em class="alert-pill">8</em></a>
      <a class="<?= str_starts_with($route,'intelligence')?'active':'' ?>" href="index.php?r=intelligence"><i class="bi bi-cpu"></i><span>Inteligencia KPI</span></a>
      <a class="<?= str_starts_with($route,'audit')?'active':'' ?>" href="index.php?r=audit"><i class="bi bi-clock-history"></i><span>Auditor&iacute;a</span></a>
      <a class="<?= str_starts_with($route,'admin.users')?'active':'' ?>" href="index.php?r=admin.users"><i class="bi bi-person-gear"></i><span>Usuarios</span></a>
      <a class="<?= $route==='admin.panel'?'active':'' ?>" href="index.php?r=admin.panel"><i class="bi bi-sliders"></i><span>Panel admin</span></a>
    </nav>
  </aside>

  <section class="main-pro modern-main">
    <header class="topbar-pro ui-topbar modern-topbar">
      <button class="mobile-menu-btn" id="mobileMenuToggle" type="button" aria-label="Abrir menu">
        <i class="bi bi-list"></i>
      </button>
      <div class="topbar-title">
        <span>ColvaContratos</span>
        <strong><?= htmlspecialchars($pageTitle) ?></strong>
      </div>
      <form class="search-pro modern-search d-none d-lg-flex" method="get" action="index.php">
        <input type="hidden" name="r" value="contracts">
        <input name="q" class="form-control" placeholder="Buscar contrato, n&uacute;mero o proveedor">
        <button class="btn btn-primary" aria-label="Buscar"><i class="bi bi-search"></i></button>
      </form>
      <div class="ui-icons">
        <a href="index.php?r=alerts" class="top-icon" title="Alertas"><i class="bi bi-bell"></i><span>5</span></a>
        <a href="index.php?r=alerts" class="top-icon" title="Mensajes"><i class="bi bi-envelope"></i><span>3</span></a>
      </div>
      <div class="top-user">
        <span><?= htmlspecialchars($user['name'] ?? 'Administrador') ?></span>
        <a class="btn btn-sm btn-light" href="index.php?r=logout">Salir</a>
      </div>
    </header>
    <main class="content-pro modern-content">
<?php else: ?>
    <main class="login-main">
<?php endif; ?>
<?php if($isLogged && $flash): ?>
  <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> <?= $isLogged ? 'alert-dismissible fade show' : '' ?>" role="alert">
    <?= htmlspecialchars($flash['message']) ?>
    <?php if($isLogged): ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    <?php endif; ?>
  </div>
<?php endif; ?>
