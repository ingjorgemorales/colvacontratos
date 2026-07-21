<?php
$manuales = $manuales ?? [];
$manual_activo = $manual_activo ?? null;
$motores = $motores ?? [];
?>
<link rel="stylesheet" href="assets/css/agente.css?v=2">

<section class="agente-modern">
  <div class="ag-hero">
    <h1><i class="bi bi-journal-text"></i> Manual de Contratación</h1>
    <p>Sube el manual y la IA extrae los tipos de contrato, porcentajes y vigencias que rigen la validación de amparos. Activa la versión vigente.</p>
  </div>

  <div class="d-flex gap-2 mb-3 flex-wrap align-items-center">
    <a class="btn btn-outline-primary btn-sm" href="index.php?r=agente"><i class="bi bi-arrow-left"></i> Volver al agente</a>
    <?php if ($manual_activo): ?>
      <span class="badge bg-success">Activo: <?= htmlspecialchars($manual_activo['nombre_archivo'], ENT_QUOTES, 'UTF-8') ?></span>
    <?php else: ?>
      <span class="badge bg-warning text-dark">No hay manual activo (se usan valores por defecto)</span>
    <?php endif; ?>
  </div>

  <div class="ag-card">
    <div class="ag-card-title"><span class="bar"></span> Subir y extraer un manual</div>
    <div class="row g-2 align-items-end">
      <div class="col-12 col-md-5"><label class="form-label" style="font-size:12px">Archivo (PDF o DOCX)</label><input type="file" class="form-control" id="ag-man-file" accept=".pdf,.docx"></div>
      <div class="col-12 col-md-4"><label class="form-label" style="font-size:12px">Motor para extraer</label>
        <select class="form-select" id="ag-man-motor">
          <?php foreach ($motores as $m): ?><option value="<?= htmlspecialchars($m['clave'], ENT_QUOTES) ?>"><?= htmlspecialchars($m['etiqueta'], ENT_QUOTES) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-12 col-md-3 d-grid"><button class="btn btn-primary" id="ag-man-subir"><i class="bi bi-upload"></i> Subir y extraer</button></div>
    </div>
    <div id="ag-man-status" class="mt-2" style="font-size:13px"></div>
    <div id="ag-man-params-wrap" style="display:none" class="mt-3">
      <label class="form-label" style="font-size:12px">Parámetros extraídos (editable, JSON)</label>
      <textarea class="form-control" id="ag-man-params" rows="12" style="font-family:monospace;font-size:12px"></textarea>
      <div class="d-flex gap-2 mt-2">
        <button class="btn btn-outline-primary btn-sm" id="ag-man-guardar"><i class="bi bi-save"></i> Guardar cambios</button>
        <button class="btn btn-success btn-sm" id="ag-man-activar"><i class="bi bi-check-circle"></i> Activar esta versión</button>
      </div>
    </div>
  </div>

  <div class="ag-card">
    <div class="ag-card-title"><span class="bar"></span> Versiones subidas</div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0" style="font-size:13px">
        <thead><tr><th>Archivo</th><th>Fecha</th><th>Por</th><th class="text-center">Parámetros</th><th class="text-center">Estado</th><th class="text-center">Acciones</th></tr></thead>
        <tbody>
          <?php foreach ($manuales as $m): $mid = (int)$m['id']; ?>
            <tr>
              <td><strong><?= htmlspecialchars($m['nombre_archivo'], ENT_QUOTES, 'UTF-8') ?></strong></td>
              <td><?= $m['fecha_subida'] ? date('d/m/Y H:i', strtotime($m['fecha_subida'])) : '' ?></td>
              <td><?= htmlspecialchars($m['subido_por'] ?: '—', ENT_QUOTES, 'UTF-8') ?></td>
              <td class="text-center"><?= !empty($m['tiene_params']) ? '<span class="ag-pill ok">Sí</span>' : '<span class="ag-pill no">No</span>' ?></td>
              <td class="text-center"><?= !empty($m['activo']) ? '<span class="badge bg-success">Activo</span>' : '' ?></td>
              <td class="text-center" style="white-space:nowrap">
                <button class="btn btn-sm btn-outline-secondary ag-man-ver" data-id="<?= $mid ?>" title="Ver/editar parámetros"><i class="bi bi-eye"></i></button>
                <?php if (empty($m['activo'])): ?>
                  <button class="btn btn-sm btn-outline-success ag-man-act" data-id="<?= $mid ?>" title="Activar"><i class="bi bi-check-lg"></i></button>
                  <button class="btn btn-sm btn-outline-danger ag-man-del" data-id="<?= $mid ?>" title="Eliminar"><i class="bi bi-trash"></i></button>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($manuales)): ?><tr><td colspan="6" class="text-center text-muted py-3">No hay manuales subidos.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<script>window.AG_PROXY = 'index.php?r=agente.proxy&path=';</script>
<script src="assets/js/agente-manual.js?v=1"></script>
