<?php
$modulo = $modulo ?? '';
$etiqueta = \App\Models\RolePermission::MODULOS[$modulo]['label'] ?? 'esta sección';
$rol = \App\Core\Auth::user()['role_name'] ?? 'tu rol';
?>
<section style="max-width:640px;margin:40px auto;text-align:center">
  <div style="background:#fff;border:1px solid #e6ebf3;border-radius:16px;padding:38px 30px;box-shadow:0 1px 3px rgba(16,32,64,.05)">
    <div style="width:74px;height:74px;border-radius:50%;background:#fdecec;color:#9c0006;display:flex;align-items:center;justify-content:center;margin:0 auto 18px;font-size:34px">
      <i class="bi bi-shield-lock"></i>
    </div>
    <h1 style="font-size:22px;font-weight:700;color:#0b2257;margin-bottom:10px">Acceso restringido</h1>
    <p style="color:#5a6b85;font-size:14.5px;line-height:1.7;margin-bottom:22px">
      Tu rol <strong><?= htmlspecialchars((string)$rol, ENT_QUOTES, 'UTF-8') ?></strong>
      no tiene permiso para entrar a <strong><?= htmlspecialchars((string)$etiqueta, ENT_QUOTES, 'UTF-8') ?></strong>.<br>
      Si necesitas acceso, solicítalo a un administrador.
    </p>
    <a class="btn btn-primary" href="index.php?r=dashboard"><i class="bi bi-house"></i> Volver al inicio</a>
  </div>
</section>
