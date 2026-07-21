<?php
$registros = $registros ?? [];
$px = fn($path) => 'index.php?r=agente.proxy&path=' . rawurlencode($path);
?>
<link rel="stylesheet" href="assets/css/agente.css?v=2">

<section class="agente-modern">
  <div class="ag-hero">
    <h1><i class="bi bi-clock-history"></i> Histórico de análisis</h1>
    <p>Todos los contratos y pólizas analizados por el agente, con sus resultados y descargas.</p>
  </div>

  <div class="d-flex gap-2 mb-3 flex-wrap">
    <a class="btn btn-outline-primary btn-sm" href="index.php?r=agente"><i class="bi bi-arrow-left"></i> Volver al agente</a>
    <a class="btn btn-success btn-sm" href="<?= $px('/api/exportar-historico-excel') ?>"><i class="bi bi-file-earmark-excel"></i> Exportar histórico</a>
  </div>

  <div class="ag-card">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead>
          <tr style="font-size:12px">
            <th>#</th><th>Fecha</th><th>Contrato</th><th>Contratista</th><th>Tipo</th>
            <th class="text-end">Valor s/IVA</th><th class="text-center">Resultado</th><th class="text-center">Archivos</th><th class="text-center">Acta</th>
          </tr>
        </thead>
        <tbody style="font-size:13px">
          <?php foreach ($registros as $r): $rid = (int)$r['id']; ?>
            <tr>
              <td><strong>#<?= $rid ?></strong></td>
              <td><?= $r['fecha'] ? date('d/m/Y H:i', strtotime($r['fecha'])) : '' ?></td>
              <td><strong><?= htmlspecialchars($r['num_contrato'] ?: 'Sin número', ENT_QUOTES, 'UTF-8') ?></strong><br><small class="text-muted"><?= htmlspecialchars($r['modelo'] ?? '', ENT_QUOTES, 'UTF-8') ?></small></td>
              <td><?= htmlspecialchars($r['contratista'] ?: '—', ENT_QUOTES, 'UTF-8') ?><br><small class="text-muted"><?= htmlspecialchars($r['nit_contratista'] ?? '', ENT_QUOTES, 'UTF-8') ?></small></td>
              <td><?= htmlspecialchars($r['tipo_contrato'] ?: '—', ENT_QUOTES, 'UTF-8') ?></td>
              <td class="text-end">$<?= number_format((float)($r['valor_sin_iva'] ?? 0), 0, ',', '.') ?></td>
              <td class="text-center">
                <?php $res = $r['resultado'] ?? ''; ?>
                <span class="ag-pill <?= $res === 'APROBADA' ? 'ok' : 'no' ?>"><?= htmlspecialchars($res ?: '—', ENT_QUOTES, 'UTF-8') ?></span>
              </td>
              <td class="text-center" style="white-space:nowrap">
                <a class="btn btn-sm btn-outline-secondary" title="Descargar contrato" href="<?= $px("/api/descargar-archivo/$rid/contrato") ?>"><i class="bi bi-file-earmark-text"></i></a>
                <?php if (!empty($r['poliza_es_multi'])): ?>
                  <a class="btn btn-sm btn-outline-secondary" title="Descargar todas las pólizas (.zip)" href="<?= $px("/api/descargar-archivo/$rid/poliza") ?>"><i class="bi bi-file-zip"></i></a>
                <?php else: ?>
                  <a class="btn btn-sm btn-outline-secondary" title="Descargar póliza" href="<?= $px("/api/descargar-archivo/$rid/poliza") ?>"><i class="bi bi-shield-check"></i></a>
                <?php endif; ?>
              </td>
              <td class="text-center">
                <a class="btn btn-sm btn-outline-success" title="Descargar acta Excel" href="<?= $px("/api/descargar-excel/$rid") ?>"><i class="bi bi-file-earmark-excel"></i></a>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($registros)): ?>
            <tr><td colspan="9" class="text-center text-muted py-4">Aún no hay análisis registrados. Analiza tu primer contrato en el agente.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>
