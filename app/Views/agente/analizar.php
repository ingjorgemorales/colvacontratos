<?php
$motores = $motores ?? [];
?>
<link rel="stylesheet" href="assets/css/agente.css?v=6">

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

  <div class="ag-card ag-instr">
    <div class="ag-card-title">
      <span class="bar"></span> Instrucciones para el agente
      <span class="ag-opt">opcional</span>
    </div>
    <p class="ag-instr-help">
      Lo que escribas aquí <b>tiene prioridad sobre el Manual de Contratación, el contrato y lo leído en las pólizas</b>.
      Úsalo para excepciones, parámetros que el Manual no contempla, comprobaciones adicionales o para
      corregir datos del contrato o de la póliza (por ejemplo, un otrosí o un anexo de prórroga que no se adjuntó).
      Cada dato que cambies queda registrado en el acta con lo que decía el documento.
    </p>
    <div class="ag-alert-warn ag-aviso-ia" role="note">
      <i class="bi bi-exclamation-triangle-fill"></i>
      <div>Por favor, revise los resultados: los modelos de IA pueden cometer errores.</div>
    </div>
    <div class="ag-instr-box" id="ag-instr-box">
      <textarea id="ag-instrucciones" rows="3" maxlength="4000"
        placeholder="Ej.: No exigir RCE, el contratista trabaja de forma remota. Salarios al 10 % por 3 años. Verificar que el beneficiario sea Colvatel."></textarea>
      <div class="ag-instr-foot">
        <div class="ag-chips">
          <button type="button" class="ag-chip" data-txt="No exigir el amparo de [amparo] porque [motivo].">No exigir un amparo</button>
          <button type="button" class="ag-chip" data-txt="Exigir [amparo] al [porcentaje] % del valor del contrato, vigente [N] meses después de la terminación.">Cambiar % o vigencia</button>
          <button type="button" class="ag-chip" data-txt="Exigir RCE por un valor mínimo de $[valor].">Valor mínimo fijo</button>
          <button type="button" class="ag-chip" data-txt="La fecha de terminación real es [dd/mm/aaaa] por la prórroga del otrosí n.º [N].">Fecha por otrosí</button>
          <button type="button" class="ag-chip" data-txt="La póliza de [amparo] está prorrogada hasta el [dd/mm/aaaa] por el anexo n.º [N], que no se adjuntó.">Prórroga de póliza</button>
          <button type="button" class="ag-chip" data-txt="La suma asegurada de [amparo] es $[valor] según el anexo n.º [N].">Corregir dato de póliza</button>
          <button type="button" class="ag-chip" data-txt="Verificar que el asegurado y beneficiario sea COLVATEL S.A. E.S.P.">Verificar beneficiario</button>
        </div>
        <span class="ag-instr-count" id="ag-instr-count">0 / 4000</span>
      </div>
    </div>
    <div class="ag-instr-actions" id="ag-instr-actions" style="display:none">
      <span class="ag-instr-estado" id="ag-instr-estado"></span>
      <button type="button" class="btn btn-primary btn-sm" id="btn-reanalizar"
        title="Vuelve a analizar los mismos documentos con las instrucciones actuales. Queda como un análisis nuevo en el Histórico.">
        <i class="bi bi-arrow-repeat"></i> Reanalizar con estas instrucciones
      </button>
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
      <div id="ag-consumo" class="ag-consumo" style="display:none"></div>
      <div id="ag-revisor" class="ag-rev-box" style="display:none;margin-bottom:12px"></div>
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

      <div id="ag-criterios" style="display:none" class="mt-3">
        <div class="ag-sub-title"><i class="bi bi-person-check"></i> Criterios adicionales del revisor</div>
        <div class="table-responsive">
          <table class="ag-amp-table ag-crit-table">
            <thead><tr><th>Criterio</th><th>Evidencia en los documentos</th><th>Estado</th></tr></thead>
            <tbody id="ag-tabla-criterios"></tbody>
          </table>
        </div>
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
<script src="assets/js/agente-analizar.js?v=6"></script>
