<?php
$contracts = $contracts ?? [];
$checkItems = [
    'vigencia' => 'Vigencia correcta',
    'asegurado' => 'Asegurado corresponde',
    'beneficiario' => 'Beneficiario corresponde',
    'amparos' => 'Amparos correctos',
    'valor_asegurado' => 'Valor asegurado correcto',
    'firmas' => 'Firmas / certificado valido',
];
?>

<section class="policy-form-modern">
  <div class="module-hero form-hero policy-hero">
    <div>
      <a href="index.php?r=polizas" class="back-link"><i class="bi bi-arrow-left"></i> Volver a polizas</a>
      <span class="section-eyebrow">Nueva revision</span>
      <h1>Nueva revision de poliza</h1>
      <p>Registra informacion contractual, checklist de validacion y soportes documentales.</p>
    </div>
    <div class="module-actions">
      <a class="btn btn-light" href="index.php?r=polizas"><i class="bi bi-x-lg"></i> Cancelar</a>
      <button class="btn btn-primary" form="policyForm" type="submit"><i class="bi bi-save"></i> Guardar revision</button>
    </div>
  </div>

  <form id="policyForm" method="post" action="index.php?r=polizas.store" enctype="multipart/form-data" class="policy-form-shell">
    <section class="form-section">
      <div class="form-section-head">
        <i class="bi bi-file-earmark-text"></i>
        <div><h2>Contrato y contratista</h2><p>Datos base para vincular la poliza con el expediente contractual.</p></div>
      </div>
      <div class="form-grid policy-form-grid">
        <label class="form-field full">
          <span>Contrato relacionado</span>
          <select class="form-select" name="contract_id">
            <option value="">Manual / no vinculado</option>
            <?php foreach ($contracts as $contract): ?>
              <option value="<?= (int)$contract['id'] ?>"><?= htmlspecialchars(($contract['number'] ?? '') . ' - ' . ($contract['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label class="form-field small"><span>Numero contrato</span><input class="form-control" name="contract_number"></label>
        <label class="form-field small"><span>Numero poliza</span><input class="form-control" name="policy_number"></label>
        <label class="form-field wide"><span>Contratista</span><input class="form-control" name="contractor_name"></label>
        <label class="form-field small"><span>NIT / ID</span><input class="form-control" name="contractor_document"></label>
      </div>
    </section>

    <section class="form-section">
      <div class="form-section-head">
        <i class="bi bi-shield-check"></i>
        <div><h2>Datos de la poliza</h2><p>Informacion de aseguradora, tipo, vigencia y valor asegurado.</p></div>
      </div>
      <div class="form-grid policy-form-grid">
        <label class="form-field wide"><span>Aseguradora</span><input class="form-control" name="insurance_company"></label>
        <label class="form-field wide">
          <span>Tipo de poliza</span>
          <select class="form-select" name="policy_type">
            <option>Cumplimiento</option>
            <option>RCE</option>
            <option>Salarios y prestaciones</option>
            <option>Calidad del servicio</option>
            <option>Estabilidad de obra</option>
            <option>Otra</option>
          </select>
        </label>
        <label class="form-field wide"><span>Valor asegurado</span><input type="number" step="0.01" class="form-control" name="insured_value"></label>
        <label class="form-field small"><span>Fecha inicio</span><input type="date" class="form-control" name="start_date"></label>
        <label class="form-field small"><span>Fecha fin</span><input type="date" class="form-control" name="end_date"></label>
        <label class="form-field wide">
          <span>Estado inicial</span>
          <select class="form-select" name="status">
            <option>PENDIENTE</option>
            <option>APROBADA</option>
            <option>OBSERVADA</option>
            <option>RECHAZADA</option>
          </select>
        </label>
      </div>
    </section>

    <section class="form-section">
      <div class="form-section-head">
        <i class="bi bi-list-check"></i>
        <div><h2>Checklist de revision</h2><p>Marca los puntos que ya fueron revisados y cumplen.</p></div>
      </div>
      <div class="policy-check-grid">
        <?php foreach ($checkItems as $key => $label): ?>
          <label class="policy-check-modern">
            <input type="checkbox" name="check_<?= $key ?>" value="1">
            <span><i class="bi bi-check2"></i></span>
            <strong><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></strong>
          </label>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="form-section">
      <div class="form-section-head">
        <i class="bi bi-cloud-arrow-up"></i>
        <div><h2>Soportes y observaciones</h2><p>Adjunta contrato, poliza y soportes adicionales.</p></div>
      </div>
      <div class="form-grid policy-form-grid">
        <label class="form-field wide"><span>Contrato / documento base</span><input type="file" class="form-control" name="contract_file" accept=".pdf,.doc,.docx"></label>
        <label class="form-field wide"><span>Poliza</span><input type="file" class="form-control" name="policy_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"></label>
        <label class="form-field wide"><span>Otros soportes</span><input type="file" class="form-control" name="other_files[]" multiple></label>
        <label class="form-field full"><span>Observaciones</span><textarea class="form-control" name="observations" rows="4"></textarea></label>
      </div>
    </section>

    <div class="form-sticky-actions">
      <a class="btn btn-light" href="index.php?r=polizas"><i class="bi bi-x-lg"></i> Cancelar</a>
      <button class="btn btn-primary" type="submit"><i class="bi bi-save"></i> Guardar revision</button>
    </div>
  </form>
</section>
