/* Roles y permisos — interacción de la matriz. */
(function () {
  const form = document.getElementById('rp-form');
  const hint = document.getElementById('rp-hint');
  if (!form) return;

  // Marca visual de "hay cambios sin guardar"
  let sucio = false;
  function marcarSucio() {
    if (sucio) return;
    sucio = true;
    if (hint) {
      hint.textContent = 'Tienes cambios sin guardar.';
      hint.classList.add('dirty');
    }
  }
  form.addEventListener('change', function (e) {
    if (e.target.matches('input[type="checkbox"]')) marcarSucio();
  });

  // Botón "Todo / nada" por rol: alterna todas las casillas editables de esa columna
  document.querySelectorAll('.rp-all').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const rol = btn.dataset.rol;
      const casillas = form.querySelectorAll('input[type="checkbox"][data-rol="' + rol + '"]:not([disabled])');
      if (!casillas.length) return;
      // Si todas están marcadas, las desmarca; si no, las marca todas
      const todasMarcadas = Array.from(casillas).every(function (c) { return c.checked; });
      casillas.forEach(function (c) { c.checked = !todasMarcadas; });
      marcarSucio();
    });
  });

  // Aviso si intenta salir con cambios pendientes
  window.addEventListener('beforeunload', function (e) {
    if (sucio) { e.preventDefault(); e.returnValue = ''; }
  });
  form.addEventListener('submit', function () { sucio = false; });
})();
