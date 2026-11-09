<?php
$c = $contract ?? [];
$v = fn($k, $d='') => htmlspecialchars((string)($c[$k] ?? $d));
function optlist($items, $field, $current, $empty='Seleccione') { ?>
  <option value=""><?= htmlspecialchars($empty) ?></option>
  <?php foreach(($items ?? []) as $i): ?>
    <option value="<?= (int)$i['id'] ?>" <?= ((string)$current === (string)$i['id'])?'selected':'' ?>><?= htmlspecialchars($i['name'] ?? $i['full_name'] ?? '') ?></option>
  <?php endforeach; ?>
<?php }
$isEdit = !empty($c['id']);
$currentMonth = \App\Models\Contract::normalizeMonth((string)($c['contract_month'] ?? ''));
?>

<section class="contract-form-modern">
  <div class="module-hero form-hero">
    <div>
      <a class="back-link" href="index.php?r=contracts"><i class="bi bi-arrow-left"></i> Contratos</a>
      <span class="section-eyebrow"><?= $isEdit ? 'Edicion contractual' : 'Nuevo registro' ?></span>
      <h1><?= htmlspecialchars($title) ?></h1>
      <p>Completa la informacion contractual, parametrica, financiera y documental base.</p>
    </div>
    <div class="module-actions">
      <button class="btn btn-primary" form="contractForm"><i class="bi bi-save"></i> Guardar</button>
      <a href="index.php?r=contracts" class="btn btn-light"><i class="bi bi-x-circle"></i> Cancelar</a>
    </div>
  </div>

  <form method="post" action="<?= htmlspecialchars($action) ?>" id="contractForm" class="contract-form-shell">
    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger" role="alert">
        <?php foreach ($errors as $error): ?><p class="mb-1"><?= htmlspecialchars($error) ?></p><?php endforeach; ?>
      </div>
    <?php endif; ?>
    <input type="hidden" name="number" value="<?= $v('number') ?>">
    <input type="hidden" name="term_months" value="0">

    <article class="form-section">
      <div class="form-section-head">
        <i class="bi bi-file-earmark-text"></i>
        <div>
          <h2>Identificacion del contrato</h2>
          <p>Datos principales que permiten reconocer el contrato.</p>
        </div>
      </div>
      <div class="form-grid">
        <label class="form-field small">
          <span>Ano</span>
          <input type="number" name="contract_year" class="form-control" value="<?= $v('contract_year', date('Y')) ?>">
        </label>
        <label class="form-field small">
          <span>Mes</span>
          <select name="contract_month" class="form-select">
            <option value="">Seleccione</option>
            <?php if ($currentMonth !== '' && !in_array($currentMonth, \App\Models\Contract::MONTHS, true)): ?>
              <option value="<?= htmlspecialchars($currentMonth) ?>" selected><?= htmlspecialchars($currentMonth) ?> (valor anterior)</option>
            <?php endif; ?>
            <?php foreach (\App\Models\Contract::MONTHS as $month): ?>
              <option value="<?= $month ?>" <?= $currentMonth === $month ? 'selected' : '' ?>><?= $month ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label class="form-field wide">
          <span>Numero de Contrato</span>
          <input name="name" class="form-control" required value="<?= $v('name') ?>">
        </label>
        <label class="form-field full">
          <span>Objeto</span>
          <textarea name="object" class="form-control" rows="4" required><?= $v('object') ?></textarea>
        </label>
        <label class="form-field">
          <span>Frecuencia de pago</span>
          <select name="payment_frequency" class="form-select">
            <option value="">Sin especificar</option>
            <?php foreach (\App\Models\Contract::PAYMENT_FREQUENCIES as $key => $label): ?>
              <option value="<?= $key ?>" <?= ($c['payment_frequency'] ?? '') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label class="form-field">
          <span>Plazo de pago (dias)</span>
          <input type="number" name="payment_term_days" class="form-control" min="0" max="65535" step="1" value="<?= $v('payment_term_days') ?>">
        </label>
        <label class="form-field full">
          <span>Clausula de pago (detalle contractual)</span>
          <textarea name="payment_clause" class="form-control" rows="4"><?= $v('payment_clause') ?></textarea>
        </label>
      </div>
    </article>

    <article class="form-section">
      <div class="form-section-head">
        <i class="bi bi-sliders"></i>
        <div>
          <h2>Parametrizacion SIVICOF</h2>
          <p>Clasificacion, modalidad, gasto, area y condiciones del contrato.</p>
        </div>
      </div>
      <div class="form-grid">
        <label class="form-field">
          <span>Tipo de contrato</span>
          <select name="contract_type_id" class="form-select" required><?php optlist($contractTypes,'id',$c['contract_type_id'] ?? '', 'Seleccione tipo') ?></select>
          <em>Cliente, proveedor o subcontratista.</em>
        </label>
        <label class="form-field">
          <span>Tipo compromiso</span>
          <select name="commitment_type_id" class="form-select"><?php optlist($commitmentTypes,'id',$c['commitment_type_id'] ?? '') ?></select>
        </label>
        <label class="form-field">
          <span>Modalidad de seleccion</span>
          <select name="selection_modality_id" class="form-select"><?php optlist($selectionModalities,'id',$c['selection_modality_id'] ?? '') ?></select>
        </label>
        <label class="form-field">
          <span>Tipologia especifica</span>
          <select name="specific_typology_id" class="form-select"><?php optlist($specificTypologies,'id',$c['specific_typology_id'] ?? '') ?></select>
        </label>
        <label class="form-field wide">
          <span>Tema del gasto</span>
          <select name="expense_topic_id" class="form-select"><?php optlist($expenseTopics,'id',$c['expense_topic_id'] ?? '') ?></select>
        </label>
        <label class="form-field small">
          <span>Unidad plazo</span>
          <select name="term_unit_id" id="termUnit" class="form-select"><?php optlist($termUnits,'id',$c['term_unit_id'] ?? '') ?></select>
        </label>
        <label class="form-field small">
          <span>Plazo ejecucion</span>
          <input type="number" name="execution_term_value" id="termValue" class="form-control" value="<?= $v('execution_term_value',0) ?>">
        </label>
        <div class="form-note wide">
          <i class="bi bi-info-circle"></i>
          <span>Para meses + dias, selecciona dias y registra el total. Para solo meses, selecciona meses.</span>
        </div>
        <label class="form-field">
          <span>Regimen contratacion</span>
          <select name="contracting_regime_id" class="form-select"><?php optlist($contractingRegimes,'id',$c['contracting_regime_id'] ?? '') ?></select>
        </label>
        <label class="form-field">
          <span>Tipo gasto</span>
          <select name="expense_type_id" class="form-select"><?php optlist($expenseTypes,'id',$c['expense_type_id'] ?? '') ?></select>
        </label>
        <label class="form-field">
          <span>Origen presupuesto</span>
          <select name="budget_origin_id" class="form-select"><?php optlist($budgetOrigins,'id',$c['budget_origin_id'] ?? '') ?></select>
        </label>
        <label class="form-field">
          <span>Origen recurso</span>
          <select name="resource_origin_id" class="form-select"><?php optlist($resourceOrigins,'id',$c['resource_origin_id'] ?? '') ?></select>
        </label>
        <label class="form-field">
          <span>Tipo moneda</span>
          <select name="currency_type_id" class="form-select"><?php optlist($currencyTypes,'id',$c['currency_type_id'] ?? '') ?></select>
        </label>
        <label class="form-field">
          <span>Area</span>
          <select name="area_id" id="areaSelect" class="form-select"><?php optlist($areas,'id',$c['area_id'] ?? '') ?></select>
        </label>
        <label class="form-field">
          <span>Subarea</span>
          <select name="sub_area_id" id="subAreaSelect" class="form-select">
            <?php
            $areaChildren = array_values(array_filter($subAreas ?? [], fn($item) => (string)$item['area_id'] === (string)($c['area_id'] ?? '')));
            optlist($areaChildren, 'id', $c['sub_area_id'] ?? '', empty($c['area_id']) ? 'Seleccione un area' : ($areaChildren ? 'Seleccione subarea' : 'No aplica'));
            ?>
          </select>
        </label>
      </div>
    </article>

    <article class="form-section">
      <div class="form-section-head">
        <i class="bi bi-person-badge"></i>
        <div>
          <h2>Responsables y contraparte</h2>
          <p>Supervisor, control de ejecucion y contratista/proveedor.</p>
        </div>
      </div>
      <div class="form-grid">
        <label class="form-field wide">
          <span>Supervisor</span>
          <select name="supervisor_id" id="supervisorSelect" class="form-select"><?php optlist($supervisors,'id',$c['supervisor_id'] ?? '') ?></select>
        </label>
        <input type="hidden" name="supervisor_name" id="supervisorName" value="<?= $v('supervisor_name') ?>">
        <label class="form-field small">
          <span>Id supervisor</span>
          <input name="supervisor_document" id="supervisorDoc" class="form-control" value="<?= $v('supervisor_document') ?>" readonly>
        </label>
        <label class="form-field small">
          <span>DV</span>
          <input name="supervisor_verification_digit" id="supervisorDv" class="form-control" value="<?= $v('supervisor_verification_digit') ?>" readonly>
        </label>
        <label class="form-field">
          <span>Tipo control</span>
          <select name="control_type_id" class="form-select"><?php optlist($controlTypes,'id',$c['control_type_id'] ?? '') ?></select>
        </label>
        <label class="form-field">
          <span>Procedimiento</span>
          <select name="procedure_type_id" class="form-select"><?php optlist($procedureTypes,'id',$c['procedure_type_id'] ?? '') ?></select>
        </label>
        <label class="form-field wide">
          <span>Contratista / proveedor</span>
          <select name="provider_id" class="form-select"><?php optlist($providers,'id',$c['provider_id'] ?? '') ?></select>
        </label>
      </div>
    </article>

    <article class="form-section">
      <div class="form-section-head">
        <i class="bi bi-calendar2-week"></i>
        <div>
          <h2>Fechas, pagos y valores</h2>
          <p>Define fechas contractuales, pagos pactados y valores base.</p>
        </div>
      </div>
      <div class="form-grid">
        <label class="form-field">
          <span>Pagos pactados iniciales</span>
          <input type="number" name="agreed_payments_qty" class="form-control" value="<?= $v('agreed_payments_qty',0) ?>">
        </label>
        <label class="form-field">
          <span>Pagos pactados otrosi</span>
          <input type="number" name="agreed_other_payments_qty" class="form-control" value="<?= $v('agreed_other_payments_qty',0) ?>">
        </label>
        <label class="form-field">
          <span>Fecha suscripcion</span>
          <input type="date" name="subscription_date" class="form-control" value="<?= $v('subscription_date') ?>">
        </label>
        <label class="form-field">
          <span>Fecha inicio</span>
          <input type="date" name="start_date" class="form-control" value="<?= $v('start_date') ?>">
        </label>
        <label class="form-field">
          <span>Fin contrato</span>
          <input type="date" name="end_date" class="form-control" value="<?= $v('end_date') ?>">
        </label>
        <label class="form-field">
          <span>Fin prorroga</span>
          <input type="date" name="extension_end_date" class="form-control" value="<?= $v('extension_end_date') ?>">
        </label>
        <label class="form-field">
          <span>Valor inicial</span>
          <input name="initial_value" id="initialValue" class="form-control money" value="<?= $v('initial_value',0) ?>">
        </label>
        <label class="form-field">
          <span>IVA inicial</span>
          <input name="initial_vat" id="initialVat" class="form-control money" value="<?= $v('initial_vat',0) ?>">
        </label>
        <label class="form-field">
          <span>Total inicial</span>
          <input name="total_initial" id="totalInitial" class="form-control money" value="<?= $v('total_initial',0) ?>">
        </label>
        <label class="form-field">
          <span>Valor adiciones</span>
          <input name="additions_value" id="additionsValue" class="form-control money" value="<?= $v('additions_value',0) ?>">
        </label>
        <label class="form-field">
          <span>Valor total automatico</span>
          <input name="total_value" id="totalValue" class="form-control money bg-light" value="<?= $v('total_value',0) ?>" readonly>
        </label>
        <label class="form-field">
          <span>Prorroga automatica</span>
          <select name="auto_extension" class="form-select">
            <option <?= ($v('auto_extension','NO')==='NO')?'selected':'' ?>>NO</option>
            <option <?= ($v('auto_extension')==='SI')?'selected':'' ?>>SI</option>
          </select>
        </label>
        <div class="form-note full">
          <i class="bi bi-lightning-charge"></i>
          <span>El estado se calcula automaticamente: En ejecucion si la fecha final esta vigente; Por liquidar si ya vencio.</span>
        </div>
      </div>
    </article>

    <div class="form-sticky-actions">
      <a class="btn btn-light" href="index.php?r=contracts">Cancelar</a>
      <button class="btn btn-primary"><i class="bi bi-save"></i> Guardar contrato</button>
    </div>
  </form>
</section>

<script>
const contractSubAreas = <?= json_encode($subAreas ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
const initialContractArea = <?= json_encode((string)($c['area_id'] ?? '')) ?>;
const initialContractSubArea = <?= json_encode((string)($c['sub_area_id'] ?? '')) ?>;
function loadContractSubAreas(selected = '') {
  const area = document.getElementById('areaSelect').value;
  const select = document.getElementById('subAreaSelect');
  const children = contractSubAreas.filter(item => String(item.area_id) === area && (Number(item.active) === 1 || String(item.id) === selected));
  const preserveEmpty = area === initialContractArea && initialContractSubArea === '' && <?= $isEdit ? 'true' : 'false' ?>;
  select.replaceChildren(new Option(!area ? 'Seleccione un area' : (children.length ? (preserveEmpty ? 'Sin especificar' : 'Seleccione subarea') : 'No aplica'), ''));
  children.forEach(item => select.add(new Option(item.name + (Number(item.active) === 1 ? '' : ' (historica)'), String(item.id))));
  select.value = children.some(item => String(item.id) === selected) ? selected : '';
  select.disabled = !area || children.length === 0;
  select.required = !!area && children.length > 0 && !preserveEmpty;
}
document.getElementById('areaSelect').addEventListener('change', () => loadContractSubAreas());
loadContractSubAreas(initialContractSubArea);
const supervisors = <?= json_encode($supervisors ?? [], JSON_UNESCAPED_UNICODE) ?>;
function num(v){ return parseFloat(String(v||'0').replace(/[$.\s]/g,'').replace(',','.'))||0; }
function recalc(){
  const initialValue = document.getElementById('initialValue');
  const initialVat = document.getElementById('initialVat');
  const additionsValue = document.getElementById('additionsValue');
  const totalInitial = document.getElementById('totalInitial');
  const totalValue = document.getElementById('totalValue');
  if (!initialValue || !initialVat || !additionsValue || !totalInitial || !totalValue) return;
  const ti = num(initialValue.value) + num(initialVat.value);
  totalInitial.value = ti.toFixed(0);
  totalValue.value = (ti + num(additionsValue.value)).toFixed(0);
}
['initialValue','initialVat','additionsValue'].forEach(id=>document.getElementById(id)?.addEventListener('input', recalc));
recalc();
function loadSupervisor(){
  const supervisorSelect = document.getElementById('supervisorSelect');
  const supervisorName = document.getElementById('supervisorName');
  const supervisorDoc = document.getElementById('supervisorDoc');
  const supervisorDv = document.getElementById('supervisorDv');
  if (!supervisorSelect || !supervisorName || !supervisorDoc || !supervisorDv) return;
  const id = supervisorSelect.value;
  const s = supervisors.find(x=>String(x.id)===String(id)) || {};
  supervisorName.value = s.full_name || s.name || '';
  supervisorDoc.value = s.document_number || '';
  supervisorDv.value = s.verification_digit || s.supervisor_catalog_dv || '';
}
document.getElementById('supervisorSelect')?.addEventListener('change', loadSupervisor);
if(document.getElementById('supervisorSelect')?.value) loadSupervisor();
</script>
