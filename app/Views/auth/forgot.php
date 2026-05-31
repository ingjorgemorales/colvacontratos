<div class="login-bg-nodes"></div>

<section class="login-card-pro auth-card-compact" aria-labelledby="forgotTitle">
  <div class="login-brand-panel">
    <img class="login-brand-img" src="assets/img/logo-login.png" alt="Colvatel">
  </div>

  <div class="login-body">
    <div class="login-kicker">Recuperacion de acceso</div>
    <h1 id="forgotTitle" class="login-title">Restablecer contrase&ntilde;a</h1>
    <p class="auth-help">Ingresa tu correo corporativo. Si existe una cuenta activa, enviaremos un enlace temporal para crear una nueva contrase&ntilde;a.</p>

    <?php if(!empty($flash)): ?>
      <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> py-2">
        <?= htmlspecialchars($flash['message']) ?>
      </div>
    <?php endif; ?>

    <?php if(!empty($devResetLink)): ?>
      <div class="alert alert-warning py-2 auth-dev-link">
        <strong>Entorno local:</strong> no hay correo configurado. Usa este enlace:<br>
        <a href="<?= htmlspecialchars($devResetLink) ?>"><?= htmlspecialchars($devResetLink) ?></a>
      </div>
    <?php endif; ?>

    <form method="post" action="index.php?r=password.email" class="login-form">
      <label class="form-label fw-semibold" for="resetEmail">Correo</label>
      <div class="login-field">
        <span class="login-field-icon" aria-hidden="true"><i class="bi bi-envelope"></i></span>
        <input id="resetEmail" class="form-control login-input" name="email" type="email" autocomplete="email" required>
      </div>

      <button type="submit" class="btn btn-primary w-100 login-submit-btn">
        <i class="bi bi-send"></i>
        <span>Enviar enlace</span>
      </button>
    </form>
  </div>

  <div class="login-footer">
    <a href="index.php?r=login">Volver al inicio de sesi&oacute;n</a>
    <span>&copy; Colvatel - <?= date('Y') ?></span>
  </div>
</section>
