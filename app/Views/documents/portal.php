<div class="page-head-pro">
  <div>
    <h2>Documental</h2>
    <p>Seleccione un contrato para gestionar adjuntos, otrosi y polizas.</p>
  </div>
</div>

<div class="card p-3">
  <div class="table-responsive">
    <table class="table table-sm table-bordered align-middle">
      <thead><tr><th>Numero</th><th>Contrato</th><th>Proveedor</th><th>Area</th><th>Estado</th><th></th></tr></thead>
      <tbody>
      <?php foreach(($contracts ?? []) as $c): ?>
        <tr>
          <td><?= htmlspecialchars($c['number'] ?? '') ?></td>
          <td><?= htmlspecialchars($c['name'] ?? '') ?></td>
          <td><?= htmlspecialchars($c['provider_name'] ?? '') ?></td>
          <td><?= htmlspecialchars($c['area_name'] ?? '') ?></td>
          <td><?= htmlspecialchars($c['status_name'] ?? '') ?></td>
          <td><a class="btn btn-sm btn-primary" href="index.php?r=documents&contract_id=<?= (int)$c['id'] ?>"><i class="bi bi-paperclip"></i> Gestionar</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
