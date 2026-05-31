<style>
@media print { .sidebar-pro,.topbar-pro,.footer-pro,.btn{display:none!important}.main-pro{margin:0!important;width:100%!important}.content-pro{padding:0!important} }
</style>
<h2>Reporte contractual</h2>
<p>Generado: <?= date('Y-m-d H:i') ?></p>

<table class="table table-sm table-bordered">
  <thead><tr><th>Numero</th><th>Contrato</th><th>Proveedor</th><th>Estado</th><th>Valor</th><th>%</th></tr></thead>
  <tbody>
  <?php foreach(($rows ?? []) as $r): ?>
    <tr>
      <td><?= htmlspecialchars($r['number'] ?? '') ?></td>
      <td><?= htmlspecialchars($r['name'] ?? '') ?></td>
      <td><?= htmlspecialchars($r['provider_name'] ?? '') ?></td>
      <td><?= htmlspecialchars($r['status_name'] ?? '') ?></td>
      <td>$<?= number_format((float)($r['total_value'] ?? 0),0,',','.') ?></td>
      <td><?= number_format((float)($r['execution_percent'] ?? 0),2) ?>%</td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<script>window.print();</script>
