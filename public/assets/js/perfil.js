/* Mi Perfil — mostrar/ocultar contraseña, requisitos en vivo y barra de seguridad. */
(function () {
  const $ = (id) => document.getElementById(id);

  // ── Botón de ojo: alterna entre password y texto ──
  document.querySelectorAll('.pf-eye').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const input = $(btn.dataset.target);
      if (!input) return;
      const oculto = input.type === 'password';
      input.type = oculto ? 'text' : 'password';
      btn.innerHTML = oculto ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
      btn.setAttribute('aria-label', oculto ? 'Ocultar contraseña' : 'Mostrar contraseña');
      input.focus();
    });
  });

  const actual = $('pf-actual');
  const nueva = $('pf-nueva');
  const confirmar = $('pf-confirm');
  const barra = $('pf-bar');
  const barraTxt = $('pf-bar-txt');
  const reqs = $('pf-reqs');
  if (!nueva || !confirmar || !reqs) return;

  function marcar(clave, estado) {
    const li = reqs.querySelector('[data-req="' + clave + '"]');
    if (!li) return;
    li.classList.remove('ok', 'bad');
    let icono = 'bi-circle';
    if (estado === true) { li.classList.add('ok'); icono = 'bi-check-circle-fill'; }
    else if (estado === false) { li.classList.add('bad'); icono = 'bi-x-circle-fill'; }
    li.querySelector('i').className = 'bi ' + icono;
  }

  function fuerza(pw) {
    let p = 0;
    if (pw.length >= 8) p++;
    if (pw.length >= 12) p++;
    if (/[a-z]/.test(pw) && /[A-Z]/.test(pw)) p++;
    if (/\d/.test(pw)) p++;
    if (/[^A-Za-z0-9]/.test(pw)) p++;
    return p; // 0..5
  }

  function evaluar() {
    const val = nueva.value;
    const conf = confirmar.value;

    // Requisitos
    marcar('len', val === '' ? null : val.length >= 8);
    marcar('dif', val === '' ? null : (actual && actual.value !== '' ? val !== actual.value : null));
    marcar('match', conf === '' ? null : (val === conf && val !== ''));

    // Barra de seguridad
    const p = val === '' ? 0 : fuerza(val);
    const niveles = [
      { w: '0%',   c: '#e9edf4', t: 'Seguridad de la contraseña' },
      { w: '20%',  c: '#e24b4a', t: 'Muy débil' },
      { w: '40%',  c: '#ef9f27', t: 'Débil' },
      { w: '60%',  c: '#f0c419', t: 'Aceptable' },
      { w: '80%',  c: '#63a534', t: 'Buena' },
      { w: '100%', c: '#1e6b2e', t: 'Excelente' }
    ];
    const n = niveles[p] || niveles[0];
    barra.style.width = n.w;
    barra.style.background = n.c;
    barraTxt.textContent = n.t;
  }

  [nueva, confirmar, actual].forEach(function (el) {
    if (el) el.addEventListener('input', evaluar);
  });

  // Aviso claro antes de enviar si la confirmación no coincide
  const form = $('pf-form-pass');
  if (form) {
    form.addEventListener('submit', function (e) {
      if (nueva.value !== confirmar.value) {
        e.preventDefault();
        confirmar.setCustomValidity('Las contraseñas no coinciden');
        confirmar.reportValidity();
        setTimeout(function () { confirmar.setCustomValidity(''); }, 100);
      }
    });
  }
})();
