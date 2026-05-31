<div class="login-bg-nodes"></div>

<section class="login-card-pro" aria-labelledby="loginTitle">
  <div class="login-brand-panel">
    <img class="login-brand-img" src="assets/img/logo.png" alt="Colvatel">
  </div>

  <div class="login-body">
    <div class="login-kicker">Sistema de gesti&oacute;n contractual</div>
    <h1 id="loginTitle" class="login-title">Ingreso ColvaContratos</h1>

    <?php if(!empty($flash)): ?>
      <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> py-2">
        <?= htmlspecialchars($flash['message']) ?>
      </div>
    <?php endif; ?>

    <?php if(!empty($error)): ?>
      <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form id="loginForm" method="post" action="index.php?r=auth" class="login-form">
      <label class="form-label fw-semibold" for="loginEmail">Usuario</label>
      <div class="login-field">
        <span class="login-field-icon" aria-hidden="true"><i class="bi bi-person"></i></span>
        <input id="loginEmail" class="form-control login-input" name="email" type="email" autocomplete="username" required>
      </div>

      <label class="form-label fw-semibold" for="loginPassword">Contrase&ntilde;a</label>
      <div class="login-field">
        <span class="login-field-icon" aria-hidden="true"><i class="bi bi-lock"></i></span>
        <input id="loginPassword" class="form-control login-input" type="password" name="password" autocomplete="current-password" required>
        <button id="togglePassword" class="login-password-toggle" type="button" aria-label="Mostrar contrase&ntilde;a" aria-pressed="false">
          <i class="bi bi-eye"></i>
        </button>
      </div>

      <label class="login-terms">
        <input id="termsAccepted" type="checkbox" name="terms" value="1" required>
        <span>
          Acepto la
          <a href="https://colvatel.com.co/wp-content/uploads/2025/07/E01.D05.-Politica-de-Datos-Personales.pdf" target="_blank" rel="noopener noreferrer">pol&iacute;tica de tratamiento de datos personales</a>
          y los t&eacute;rminos y condiciones de uso.
        </span>
      </label>

      <button id="loginSubmit" type="submit" class="btn btn-primary w-100 login-submit-btn" disabled>
        <i class="bi bi-box-arrow-in-right"></i>
        <span>Ingresar</span>
      </button>
    </form>
  </div>

  <div class="login-footer">
    <a href="index.php?r=password.forgot">Recuperar contrase&ntilde;a</a>
    <span>&copy; Colvatel - <?= date('Y') ?></span>
  </div>
</section>

<script>
(function(){
  var terms = document.getElementById('termsAccepted');
  var submit = document.getElementById('loginSubmit');
  var password = document.getElementById('loginPassword');
  var toggle = document.getElementById('togglePassword');

  if (terms && submit) {
    function syncSubmitState() {
      submit.disabled = !terms.checked;
    }
    terms.addEventListener('change', syncSubmitState);
    syncSubmitState();
  }

  if (password && toggle) {
    toggle.addEventListener('click', function(){
      var showing = password.type === 'text';
      password.type = showing ? 'password' : 'text';
      toggle.setAttribute('aria-pressed', showing ? 'false' : 'true');
      toggle.setAttribute('aria-label', showing ? 'Mostrar contrase&ntilde;a' : 'Ocultar contrase&ntilde;a');
      toggle.innerHTML = showing ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
    });
  }
})();
</script>
