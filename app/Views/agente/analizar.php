<?php
$motores = $motores ?? [];
$agenteUp = $agenteUp ?? false;
?>
<link rel="stylesheet" href="assets/css/agente.css?v=2">

<section class="agente-modern">
  <div class="ag-hero">
    <h1><i class="bi bi-robot"></i> Agente de Aprobación de Pólizas</h1>
    <p>Analiza contratos y pólizas con inteligencia artificial y genera el Acta de Aprobación (A08.P02.F20) según el Manual de Contratación de Colvatel.</p>
    <div class="ag-steps">
      <div class="ag-step"><span class="ag-step-num">1</span> Sube el contrato y una o más pólizas</div>
      <div class="ag-step"><span class="ag-step-num">2</span> La IA extrae y valida los amparos</div>
      <div class="ag-step"><span class="ag-step-num">3</span> Descarga el Acta en Excel</div>
    </div>
  </div>

  <div class="d-flex gap-2 mb-3 flex-wrap">
    <a class="btn btn-outline-primary btn-sm" href="index.php?r=agente.historico"><i class="bi bi-clock-history"></i> Histórico</a>
    <a class="btn btn-outline-primary btn-sm" href="index.php?r=agente.manual"><i class="bi bi-journal-text"></i> Manual</a>
    <a class="btn btn-outline-primary btn-sm" href="index.php?r=agente.apis"><i class="bi bi-key"></i> Claves APIs</a>
  </div>

  <?php if (!$agenteUp): ?>
    <div class="ag-off mb-3"><i class="bi bi-exclamation-triangle-fill"></i>
      El motor del agente no está encendido. Arranca <code>python app.py</code> en la carpeta colvatel-app para poder analizar.
    </div>
  <?php endif; ?>

  <div class="ag-card">
    <div class="ag-card-title"><span class="bar"></span> Documentos a analizar</div>
    <div class="ag-upload-grid">
      <div>
        <div class="ag-upload-label">Contrato</div>
        <label class="ag-zone" id="zone-contrato">
          <input type="file" id="in-contrato" accept=".pdf,.docx,.txt">
          <div class="ico"><i class="bi bi-file-earmark-text"></i></div>
          <div class="zt">Contrato</div>
          <div class="zs">PDF o Word (.docx)</div>
          <div class="ag-filename" id="name-contrato"></div>
        </label>
      </div>
      <div>
        <div class="ag-upload-label">Póliza(s) de seguro</div>
        <label class="ag-zone" id="zone-poliza">
          <input type="file" id="in-poliza" accept=".pdf,.docx,.txt" multiple>
          <div class="ico"><i class="bi bi-shield-check"></i></div>
          <div class="zt">Póliza(s)</div>
          <div class="zs">Uno o más archivos PDF o Word</div>
          <div class="ag-filename" id="name-poliza"></div>
        </label>
      </div>
    </div>
  </div>

  <div class="ag-card">
    <div class="ag-card-title"><span class="bar"></span> Modelo de inteligencia artificial</div>
    <div class="ag-motores" id="ag-motores">
      <?php foreach ($motores as $i => $m): ?>
        <button type="button" class="ag-motor <?= $i === 0 ? 'active' : '' ?>" data-modelo="<?= htmlspecialchars($m['clave'], ENT_QUOTES) ?>">
          <i class="bi bi-cpu"></i> <?= htmlspecialchars($m['etiqueta'], ENT_QUOTES) ?>
          <?php if (!empty($m['chip'])): ?><span class="chip"><?= htmlspecialchars($m['chip'], ENT_QUOTES) ?></span><?php endif; ?>
        </button>
      <?php endforeach; ?>
      <?php if (empty($motores)): ?><span class="text-muted">No hay motores configurados. Ve a Claves APIs.</span><?php endif; ?>
    </div>
  </div>

  <button class="btn btn-primary btn-lg w-100 mb-3" id="btn-analizar" disabled>
    <i class="bi bi-search"></i> Analizar documentos y generar Acta
  </button>

  <div id="ag-progreso" class="ag-card" style="display:none">
    <div class="d-flex align-items-center gap-3">
      <div class="spinner-border text-primary" role="status"></div>
      <div>
        <div style="font-weight:700;color:#0b2257">Procesando documentos</div>
        <div class="text-muted" style="font-size:13px" id="ag-status">El agente de IA está analizando los archivos…</div>
      </div>
    </div>
  </div>

  <div id="ag-error" class="alert alert-danger" style="display:none"></div>

  <div id="ag-resultado" style="display:none">
    <div class="ag-card">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
        <div class="ag-card-title mb-0"><span class="bar"></span> Resultado de validación</div>
        <span class="ag-badge-global" id="ag-badge"></span>
      </div>
      <div id="ag-advertencias" class="ag-alert-warn" style="display:none;margin-bottom:12px"></div>

      <div class="ag-info-grid">
        <div class="ag-info-box">
          <div class="lbl">Contrato</div>
          <div id="ag-info-contrato"></div>
          <div id="ag-fecha-editor" style="display:none;margin-top:12px;border-top:1px dashed #d4deec;padding-top:12px">
            <div style="font-weight:700;font-size:12.5px;margin-bottom:8px">✏️ Corregir fechas del contrato</div>
            <div class="row g-2 align-items-end">
              <div class="col"><label class="form-label" style="font-size:12px">Inicio</label><input type="date" class="form-control form-control-sm" id="ag-f-inicio"></div>
              <div class="col"><label class="form-label" style="font-size:12px">Terminación</label><input type="date" class="form-control form-control-sm" id="ag-f-fin"></div>
              <div class="col-auto"><button class="btn btn-primary btn-sm" id="ag-btn-recalcular">Recalcular</button></div>
            </div>
          </div>
        </div>
        <div class="ag-info-box">
          <div class="lbl">Póliza</div>
          <div id="ag-info-poliza"></div>
        </div>
      </div>

      <div class="table-responsive">
        <table class="ag-amp-table">
          <thead><tr>
            <th>Amparo</th><th>% Req.</th><th>Valor mínimo</th><th>En póliza</th>
            <th>Hasta requerido</th><th>Hasta en póliza</th><th>Estado</th>
          </tr></thead>
          <tbody id="ag-tabla-amparos"></tbody>
        </table>
      </div>

      <button class="btn btn-success mt-3" id="ag-btn-excel"><i class="bi bi-file-earmark-excel"></i> Descargar Acta de Aprobación (Excel)</button>
    </div>

    <div class="ag-chat mt-3">
      <div class="ag-chat-head"><i class="bi bi-chat-dots"></i> Consulta sobre los documentos</div>
      <div class="ag-chat-msgs" id="ag-chat-msgs">
        <div class="ag-msg a">Tengo acceso completo al contrato y a las pólizas analizadas. Hazme cualquier pregunta.</div>
      </div>
      <div class="ag-chat-input">
        <input type="text" class="form-control" id="ag-chat-input" placeholder="Escribe tu pregunta…">
        <button class="btn btn-primary" id="ag-chat-send"><i class="bi bi-send"></i></button>
      </div>
    </div>
  </div>
</section>

<script>window.AG_PROXY = 'index.php?r=agente.proxy&path=';</script>
<script src="assets/js/agente-analizar.js?v=2"></script>
