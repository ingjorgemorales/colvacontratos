<?php
$h = fn($v) => htmlspecialchars((string)($v ?? ''));
$money = fn($v) => '$' . number_format((float)($v ?? 0), 0, ',', '.');
$pct = fn($v) => number_format((float)($v ?? 0), 2, ',', '.') . '%';
$days = $contract['days_to_expire'] ?? null;
$daysInt = $days === null || $days === '' ? null : (int)$days;
$riskTone = $daysInt === null ? 'secondary' : ($daysInt < 0 || $daysInt <= 30 ? 'danger' : ($daysInt <= 90 ? 'warning' : 'success'));
$riskLabel = $daysInt === null ? 'Sin fecha' : ($daysInt < 0 ? 'Vencido' : ($daysInt <= 30 ? 'Critico' : ($daysInt <= 90 ? 'Proximo' : 'Vigente')));
$execution = min(100, max(0, (float)($contract['execution_percent'] ?? 0)));
$endDate = $contract['extension_end_date'] ?: ($contract['end_date'] ?? '');
$supervisor = $contract['supervisor_name'] ?: ($contract['supervisor_catalog_name'] ?? '');
$supervisorDoc = $contract['supervisor_document'] ?: ($contract['supervisor_catalog_document'] ?? '');
$supervisorDv = $contract['supervisor_verification_digit'] ?: ($contract['supervisor_catalog_dv'] ?? '');

$generalFields = [
  ['Tipo de contrato', $contract['contract_type_name'] ?? ''],
  ['Tipo compromiso', $contract['commitment_type_name'] ?? ''],
  ['Modalidad seleccion', $contract['selection_modality_name'] ?? ''],
  ['Tema gasto', $contract['expense_topic_name'] ?? ''],
  ['Tipologia especifica', $contract['specific_typology_name'] ?? ''],
  ['Regimen contratacion', $contract['contracting_regime_name'] ?? ''],
  ['Tipo gasto', $contract['expense_type_name'] ?? ''],
  ['Origen presupuesto', $contract['budget_origin_name'] ?? ''],
  ['Origen recursos', $contract['resource_origin_name'] ?? ''],
  ['Tipo moneda', $contract['currency_type_name'] ?? ''],
  ['Area', $contract['area_name'] ?? ''],
  ['Sub area', $contract['sub_area_name'] ?? ''],
];

$dateFields = [
  ['Suscripcion', $contract['subscription_date'] ?? ''],
  ['Inicio', $contract['start_date'] ?? ''],
  ['Fin', $contract['end_date'] ?? ''],
  ['Fin prorroga', $contract['extension_end_date'] ?? ''],
  ['Vigencia meses', $contract['term_months'] ?? ''],
  ['Prorroga automatica', $contract['auto_extension'] ?? 'NO'],
];

$valueFields = [
  ['Valor inicial', $money($contract['initial_value'] ?? 0)],
  ['IVA inicial', $money($contract['initial_vat'] ?? 0)],
  ['Total inicial', $money($contract['total_initial'] ?? 0)],
  ['Adiciones', $money($contract['additions_value'] ?? 0)],
  ['Valor total', $money($contract['total_value'] ?? 0)],
  ['Ejecutado', $money($contract['executed_value'] ?? 0)],
];
?>

<section class="contract-show-modern">
  <div class="contract-detail-hero risk-<?= $riskTone ?>">
    <div class="contract-title-block">
      <a class="back-link" href="index.php?r=contracts"><i class="bi bi-arrow-left"></i> Contratos</a>
      <span class="section-eyebrow"><?= $h($contract['contract_type_name'] ?? 'Contrato') ?></span>
      <h1><?= $h($contract['number']) ?> - <?= $h($contract['name']) ?></h1>
      <p><?= $h($contract['provider_name'] ?? 'Sin proveedor') ?></p>
    </div>
    <div class="contract-hero-actions">
      <span class="risk-badge risk-<?= $riskTone ?>"><?= $h($riskLabel) ?></span>
      <a class="btn btn-primary" href="index.php?r=contracts.edit&id=<?= (int)$contract['id'] ?>"><i class="bi bi-pencil"></i> Editar</a>
      <a class="btn btn-light" href="index.php?r=finance.contract&id=<?= (int)$contract['id'] ?>"><i class="bi bi-cash-coin"></i> Financiera</a>
      <a class="btn btn-light" href="index.php?r=documents&contract_id=<?= (int)$contract['id'] ?>"><i class="bi bi-paperclip"></i> Documental</a>
    </div>
  </div>

  <div class="contract-kpi-row">
    <article><span>Estado</span><strong><?= $h($contract['status_name'] ?? 'Sin estado') ?></strong><em>Estado contractual</em></article>
    <article><span>Dias vencimiento</span><strong><?= $daysInt === null ? '-' : $daysInt ?></strong><em><?= $h($riskLabel) ?></em></article>
    <article><span>Valor total</span><strong><?= $money($contract['total_value'] ?? 0) ?></strong><em>Incluye adiciones</em></article>
    <article><span>Ejecucion</span><strong><?= $pct($execution) ?></strong><em>Avance financiero</em></article>
  </div>

  <div class="contract-detail-grid">
    <article class="detail-panel finance-detail-panel">
      <div class="panel-title-row">
        <div>
          <span class="section-eyebrow">Financiero</span>
          <h2>Ejecucion y valores</h2>
        </div>
        <span class="execution-pill"><?= $pct($execution) ?></span>
      </div>
      <div class="execution-bar"><span style="width: <?= $execution ?>%"></span></div>
      <div class="detail-field-grid two-cols">
        <?php foreach($valueFields as [$label, $value]): ?>
          <div class="detail-field"><span><?= $h($label) ?></span><strong><?= $h($value) ?></strong></div>
        <?php endforeach; ?>
      </div>
    </article>

    <article class="detail-panel">
      <div class="panel-title-row">
        <div>
          <span class="section-eyebrow">Fechas</span>
          <h2>Vigencia contractual</h2>
        </div>
      </div>
      <div class="date-timeline">
        <div><i class="bi bi-calendar-check"></i><span>Inicio</span><strong><?= $h($contract['start_date'] ?? '-') ?></strong></div>
        <div><i class="bi bi-calendar-event"></i><span>Fin</span><strong><?= $h($endDate ?: '-') ?></strong></div>
        <div><i class="bi bi-hourglass-split"></i><span>Dias</span><strong><?= $daysInt === null ? '-' : $daysInt ?></strong></div>
      </div>
      <div class="detail-field-grid">
        <?php foreach($dateFields as [$label, $value]): ?>
          <div class="detail-field"><span><?= $h($label) ?></span><strong><?= $h($value ?: '-') ?></strong></div>
        <?php endforeach; ?>
      </div>
    </article>
  </div>

  <div class="contract-detail-grid">
    <article class="detail-panel">
      <div class="panel-title-row">
        <div>
          <span class="section-eyebrow">Responsables</span>
          <h2>Proveedor y supervisor</h2>
        </div>
      </div>
      <div class="people-grid">
        <div class="person-card">
          <i class="bi bi-building"></i>
          <span>Proveedor</span>
          <strong><?= $h($contract['provider_name'] ?? 'Sin proveedor') ?></strong>
        </div>
        <div class="person-card">
          <i class="bi bi-person-badge"></i>
          <span>Supervisor</span>
          <strong><?= $h($supervisor ?: 'Sin supervisor') ?></strong>
          <em><?= $h(trim($supervisorDoc . ($supervisorDv ? ' - ' . $supervisorDv : ''))) ?></em>
        </div>
      </div>
      <div class="detail-field-grid">
        <div class="detail-field"><span>Tipo control</span><strong><?= $h($contract['control_type_name'] ?? '-') ?></strong></div>
        <div class="detail-field"><span>Procedimiento</span><strong><?= $h($contract['procedure_type_name'] ?? '-') ?></strong></div>
        <div class="detail-field"><span>Ano</span><strong><?= $h($contract['contract_year'] ?? '-') ?></strong></div>
        <div class="detail-field"><span>Mes</span><strong><?= $h($contract['contract_month'] ?? '-') ?></strong></div>
      </div>
    </article>

    <article class="detail-panel quick-actions-panel">
      <div class="panel-title-row">
        <div>
          <span class="section-eyebrow">Acciones</span>
          <h2>Gestion relacionada</h2>
        </div>
      </div>
      <div class="quick-action-grid">
        <a href="index.php?r=documents&contract_id=<?= (int)$contract['id'] ?>"><i class="bi bi-folder2-open"></i><span>Documentos</span></a>
        <a href="index.php?r=finance.contract&id=<?= (int)$contract['id'] ?>"><i class="bi bi-cash-coin"></i><span>Financiera</span></a>
        <a href="index.php?r=polizas.create&contract_id=<?= (int)$contract['id'] ?>"><i class="bi bi-shield-check"></i><span>Polizas</span></a>
        <a href="index.php?r=contracts.edit&id=<?= (int)$contract['id'] ?>"><i class="bi bi-pencil-square"></i><span>Editar</span></a>
      </div>
    </article>
  </div>

  <article class="detail-panel">
    <div class="panel-title-row">
      <div>
        <span class="section-eyebrow">Alcance</span>
        <h2>Objeto y clausula de pago</h2>
      </div>
    </div>
    <div class="text-block-grid">
      <div>
        <h3>Objeto</h3>
        <p><?= nl2br($h($contract['object'] ?? '')) ?></p>
      </div>
      <div>
        <h3>Clausula de pago</h3>
        <p><?= nl2br($h($contract['payment_clause'] ?? '')) ?></p>
      </div>
    </div>
  </article>

  <article class="detail-panel">
    <div class="panel-title-row">
      <div>
        <span class="section-eyebrow">Parametrizacion</span>
        <h2>Datos generales</h2>
      </div>
    </div>
    <div class="detail-field-grid param-grid">
      <?php foreach($generalFields as [$label, $value]): ?>
        <div class="detail-field"><span><?= $h($label) ?></span><strong><?= $h($value ?: '-') ?></strong></div>
      <?php endforeach; ?>
      <div class="detail-field"><span>Pagos iniciales</span><strong><?= $h($contract['agreed_payments_qty'] ?? 0) ?></strong></div>
      <div class="detail-field"><span>Pagos otrosi</span><strong><?= $h($contract['agreed_other_payments_qty'] ?? 0) ?></strong></div>
      <div class="detail-field"><span>Unidad plazo</span><strong><?= $h($contract['term_unit_name'] ?? '-') ?></strong></div>
      <div class="detail-field"><span>Plazo ejecucion</span><strong><?= $h($contract['execution_term_value'] ?? '-') ?></strong></div>
    </div>
  </article>
</section>