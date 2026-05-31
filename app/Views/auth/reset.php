<div class="login-bg-nodes"></div>

<section class="login-card-pro auth-card-compact" aria-labelledby="resetTitle">
  <div class="login-brand-panel">
    <img class="login-brand-img" src="assets/img/logo-login.png" alt="Colvatel">
  </div>

  <div class="login-body">
    <div class="login-kicker">Nuevo acceso</div>
    <h1 id="resetTitle" class="login-title">Crear nueva contrase&ntilde;a</h1>
    <p class="auth-help">Cuenta: <strong><?= htmlspecialchars($email ?? '') ?></strong></p>

    <?php if(!empty($flash)): ?>
      <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> py-2">
        <?= htmlspecialchars($flash['message']) ?>
      </div>
    <?php endif; ?>

    <form id="resetPasswordForm" method="post" action="index.php?r=password.update" class="login-form">
      <input type="hidden" name="token" value="<?= htmlspecialchars($token ?? '') ?>">

      <label class="form-label fw-semibold" for="newPassword">Nueva contrase&ntilde;a</label>
      <div class="login-field">
        <span class="login-field-icon" aria-hidden="true"><i class="bi bi-lock"></i></span>
        <input id="newPassword" class="form-control login-input" name="password" type="password" minlength="8" autocomplete="new-password" required>
        <button class="login-password-toggle" type="button" data-toggle-password="newPassword" aria-label="Mostrar contrase&ntilde;a" aria-pressed="false">
          <i class="bi bi-eye"></i>
        </button>
      </div>

      <label class="form-label fw-semibold" for="confirmPassword">Confirmar contrase&ntilde;a</label>
      <div class="login-field">
        <span class="login-field-icon" aria-hidden="true"><i class="bi bi-check2-circle"></i></span>
        <input id="confirmPassword" class="form-control login-input" name="password_confirmation" type="password" minlength="8" autocomplete="new-password" required>
        <button class="login-password-toggle" type="button" data-toggle-password="confirmPassword" aria-label="Mostrar contrase&ntilde;a" aria-pressed="false">
          <i class="bi bi-eye"></i>
        </button>
      </div>

      <button type="submit" class="btn btn-primary w-100 login-submit-btn">
        <i class="bi bi-shield-check"></i>
        <span>Guardar contrase&ntilde;a</span>
      </button>
    </form>
  </div>

  <div class="login-footer">
    <a href="index.php?r=login">Volver al inicio de sesi&oacute;n</a>
    <span>&copy; Colvatel - <?= date('Y') ?></span>
  </div>
</section>

<script>
(function(){
  document.querySelectorAll('[data-toggle-password]').forEach(function(toggle){
    var input = document.getElementById(toggle.getAttribute('data-toggle-password'));
    if (!input) return;
    toggle.addEventListener('click', function(){
      var showing = input.type === 'text';
      input.type = showing ? 'password' : 'text';
      toggle.setAttribute('aria-pressed', showing ? 'false' : 'true');
      toggle.setAttribute('aria-label', showing ? 'Mostrar contrase&ntilde;a' : 'Ocultar contrase&ntilde;a');
      toggle.innerHTML = showing ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
    });
  });
})();
</script>
