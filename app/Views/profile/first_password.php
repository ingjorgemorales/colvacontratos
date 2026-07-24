<?php $nombre = $nombre ?? ''; ?>
<link rel="stylesheet" href="assets/css/perfil.css?v=2">

<section style="max-width:520px;margin:24px auto">
  <div class="pf-card">
    <div style="text-align:center;margin-bottom:14px">
      <div style="width:64px;height:64px;border-radius:50%;background:#eef4ff;color:#1a50aa;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;font-size:28px">
        <i class="bi bi-shield-lock"></i>
      </div>
      <h1 style="font-size:20px;font-weight:700;color:#0b2257;margin:0 0 4px">Crea tu contraseña</h1>
      <p style="color:#5a6b85;font-size:13.5px;line-height:1.6;margin:0">
        Hola <strong><?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?></strong>, por seguridad debes
        reemplazar la contraseña temporal antes de continuar.
      </p>
    </div>

    <form method="post" action="index.php?r=perfil.cambio_inicial.save" id="pf-form-pass" autocomplete="off">
      <div class="mb-3">
        <label class="form-label" style="font-size:12.5px">Nueva contraseña</label>
        <div class="pf-pass">
          <input class="form-control" type="password" name="new_password" id="pf-nueva"
                 autocomplete="new-password" minlength="8" required autofocus>
          <button class="pf-eye" type="button" data-target="pf-nueva" aria-label="Mostrar contraseña"><i class="bi bi-eye"></i></button>
        </div>
        <div class="pf-strength"><i id="pf-bar"></i></div>
        <span class="pf-strength-txt" id="pf-bar-txt">Seguridad de la contraseña</span>
      </div>

      <div class="mb-2">
        <label class="form-label" style="font-size:12.5px">Confirmar nueva contraseña</label>
        <div class="pf-pass">
          <input class="form-control" type="password" name="new_password_confirmation" id="pf-confirm"
                 autocomplete="new-password" minlength="8" required>
          <button class="pf-eye" type="button" data-target="pf-confirm" aria-label="Mostrar contraseña"><i class="bi bi-eye"></i></button>
        </div>
      </div>

      <ul class="pf-reqs" id="pf-reqs">
        <li data-req="len"><i class="bi bi-circle"></i> Mínimo 8 caracteres</li>
        <li data-req="dif"><i class="bi bi-circle"></i> Diferente a la contraseña temporal</li>
        <li data-req="match"><i class="bi bi-circle"></i> La confirmación coincide</li>
      </ul>

      <button class="btn btn-primary w-100 mt-3" type="submit"><i class="bi bi-check2-circle"></i> Guardar y continuar</button>
    </form>
  </div>
</section>

<script src="assets/js/perfil.js?v=2"></script>
