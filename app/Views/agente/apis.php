<?php
$motores = $motores ?? [];
$proveedores = $proveedores ?? [];
$keys = $keys ?? [];
$keyMap = [];
foreach ($keys as $k) { $keyMap[$k['nombre']] = $k['actualizado']; }
?>
<link rel="stylesheet" href="assets/css/agente.css?v=2">

<section class="agente-modern">
  <div class="ag-hero">
    <h1><i class="bi bi-key"></i> Claves APIs, proveedores y motores</h1>
    <p>Gestiona las claves de IA (se guardan cifradas), los proveedores y los motores/botones que aparecen en el agente.</p>
  </div>

  <div class="d-flex gap-2 mb-3"><a class="btn btn-outline-primary btn-sm" href="index.php?r=agente"><i class="bi bi-arrow-left"></i> Volver al agente</a></div>
  <div id="ag-apis-msg" class="mb-3"></div>

  <!-- ── CLAVES ── -->
  <div class="ag-card">
    <div class="ag-card-title"><span class="bar"></span> Claves de API (cifradas)</div>
    <table class="table align-middle mb-0" style="font-size:13px">
      <thead><tr><th>Proveedor</th><th>Nombre de clave</th><th class="text-center">Estado</th><th>Nueva clave</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($proveedores as $p): $nom = $p['api_key_nombre']; $saved = isset($keyMap[$nom]); ?>
          <tr>
            <td><strong><?= htmlspecialchars($p['etiqueta'], ENT_QUOTES, 'UTF-8') ?></strong></td>
            <td><code><?= htmlspecialchars($nom, ENT_QUOTES, 'UTF-8') ?></code></td>
            <td class="text-center"><?= $saved ? '<span class="ag-pill ok">Guardada</span>' : '<span class="ag-pill no">Sin clave</span>' ?></td>
            <td><input type="password" class="form-control form-control-sm ag-key-in" autocomplete="new-password" data-nombre="<?= htmlspecialchars($nom, ENT_QUOTES) ?>" placeholder="pega la clave…"></td>
            <td style="white-space:nowrap">
              <button class="btn btn-sm btn-primary ag-key-save" data-nombre="<?= htmlspecialchars($nom, ENT_QUOTES) ?>">Guardar</button>
              <?php if ($saved): ?><button class="btn btn-sm btn-outline-danger ag-key-del" data-nombre="<?= htmlspecialchars($nom, ENT_QUOTES) ?>">Borrar</button><?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- ── PROVEEDORES ── -->
  <div class="ag-card">
    <div class="ag-card-title"><span class="bar"></span> Proveedores</div>
    <table class="table align-middle mb-2" style="font-size:13px">
      <thead><tr><th>Clave</th><th>Nombre</th><th>Tipo</th><th>URL base</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($proveedores as $p): ?>
          <tr>
            <td><code><?= htmlspecialchars($p['clave'], ENT_QUOTES, 'UTF-8') ?></code></td>
            <td><?= htmlspecialchars($p['etiqueta'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($p['tipo'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><small><?= htmlspecialchars($p['base_url'] ?: '(oficial)', ENT_QUOTES, 'UTF-8') ?></small></td>
            <td><button class="btn btn-sm btn-outline-danger ag-prov-del" data-id="<?= (int)$p['id'] ?>">Eliminar</button></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <div class="row g-2 align-items-end">
      <div class="col-12 col-md-4"><label class="form-label" style="font-size:12px">Nombre del proveedor</label><input class="form-control form-control-sm" id="ag-prov-et" placeholder="Ej: Mistral"></div>
      <div class="col-12 col-md-4"><label class="form-label" style="font-size:12px">URL base (opcional)</label><input class="form-control form-control-sm" id="ag-prov-url" placeholder="https://…/v1"></div>
      <div class="col-6 col-md-2"><label class="form-label" style="font-size:12px">API key (opcional)</label><input type="password" class="form-control form-control-sm" id="ag-prov-key" autocomplete="new-password"></div>
      <div class="col-6 col-md-2"><button class="btn btn-primary btn-sm w-100 text-nowrap" id="ag-prov-add">Añadir</button></div>
    </div>
  </div>

  <!-- ── MOTORES ── -->
  <div class="ag-card">
    <div class="ag-card-title"><span class="bar"></span> Motores (botones del agente)</div>
    <table class="table align-middle mb-2" style="font-size:13px">
      <thead><tr><th>Etiqueta</th><th>Chip</th><th>Proveedor</th><th>Model ID</th><th class="text-center">Tokens</th><th class="text-center">Activo</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($motores as $m): ?>
          <tr>
            <td><strong><?= htmlspecialchars($m['etiqueta'], ENT_QUOTES, 'UTF-8') ?></strong></td>
            <td><?= htmlspecialchars($m['chip'] ?: '—', ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($m['proveedor'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><small><?= htmlspecialchars($m['model_id'], ENT_QUOTES, 'UTF-8') ?></small></td>
            <td class="text-center"><?= (int)$m['max_tokens'] ?></td>
            <td class="text-center"><?= !empty($m['activo']) ? '<span class="ag-pill ok">Sí</span>' : '<span class="ag-pill no">No</span>' ?></td>
            <td style="white-space:nowrap">
              <button class="btn btn-sm btn-outline-secondary ag-mot-test" data-clave="<?= htmlspecialchars($m['clave'], ENT_QUOTES) ?>" title="Probar">Probar</button>
              <button class="btn btn-sm btn-outline-danger ag-mot-del" data-id="<?= (int)$m['id'] ?>" title="Eliminar">×</button>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <div class="row g-2 align-items-end">
      <div class="col-12 col-sm-6 col-lg-3"><label class="form-label" style="font-size:12px">Nombre del botón</label><input class="form-control form-control-sm" id="ag-mot-et" placeholder="Ej: Claude"></div>
      <div class="col-6 col-sm-3 col-lg-2"><label class="form-label" style="font-size:12px">Chip</label><input class="form-control form-control-sm" id="ag-mot-chip" placeholder="Opus"></div>
      <div class="col-6 col-sm-3 col-lg-2"><label class="form-label" style="font-size:12px">Proveedor</label>
        <select class="form-select form-select-sm" id="ag-mot-prov">
          <?php foreach ($proveedores as $p): ?><option value="<?= htmlspecialchars($p['clave'], ENT_QUOTES) ?>"><?= htmlspecialchars($p['clave'], ENT_QUOTES) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-12 col-sm-6 col-lg-2"><label class="form-label" style="font-size:12px">Model ID</label><input class="form-control form-control-sm" id="ag-mot-model" placeholder="gpt-4o"></div>
      <div class="col-6 col-sm-3 col-lg-1"><label class="form-label" style="font-size:12px">Tokens</label><input type="number" class="form-control form-control-sm" id="ag-mot-tok" value="4096"></div>
      <div class="col-6 col-sm-3 col-lg-2"><button class="btn btn-primary btn-sm w-100 text-nowrap" id="ag-mot-add">Añadir</button></div>
    </div>
  </div>
</section>

<script>window.AG_PROXY = 'index.php?r=agente.proxy&path=';</script>
<script src="assets/js/agente-apis.js?v=1"></script>
