<?php
$perfil = $perfil ?? [];
$nombre = (string)($perfil['name'] ?? '');
$correo = (string)($perfil['email'] ?? '');
$rol    = (string)($perfil['role_name'] ?? 'Sin rol');
$activo = (int)($perfil['active'] ?? 0) === 1;

// Iniciales para el avatar (máx. 2 letras)
$partes = preg_split('/\s+/', trim($nombre)) ?: [];
$iniciales = mb_strtoupper(mb_substr($partes[0] ?? 'U', 0, 1) . (isset($partes[1]) ? mb_substr($partes[1], 0, 1) : ''));

$fmt = static function ($valor): string {
    if (empty($valor)) return '—';
    $ts = strtotime((string)$valor);
    return $ts ? date('d/m/Y H:i', $ts) : '—';
};
?>
<link rel="stylesheet" href="assets/css/perfil.css?v=1">

<section class="perfil-modern">

  <div class="pf-hero">
    <div class="pf-avatar"><?= htmlspecialchars($iniciales, ENT_QUOTES, 'UTF-8') ?></div>
    <div class="pf-hero-info">
      <h1><?= htmlspecialchars($nombre ?: 'Mi perfil', ENT_QUOTES, 'UTF-8') ?></h1>
      <p class="mail"><i class="bi bi-envelope"></i> <?= htmlspecialchars($correo, ENT_QUOTES, 'UTF-8') ?></p>
      <div class="pf-badges">
        <span class="pf-badge"><i class="bi bi-person-badge"></i> <?= htmlspecialchars($rol, ENT_QUOTES, 'UTF-8') ?></span>
        <span class="pf-badge"><?= $activo ? '<i class="bi bi-check-circle"></i> Activo' : '<i class="bi bi-slash-circle"></i> Inactivo' ?></span>
      </div>
    </div>
  </div>

  <div class="pf-grid">

    <!-- ── Columna izquierda: cuenta + datos personales ── -->
    <div>
      <div class="pf-card mb-3">
        <div class="pf-card-title"><span class="bar"></span> Información de la cuenta</div>
        <div class="pf-datos">
          <div class="pf-dato"><span>Cédula</span><strong><?= htmlspecialchars((string)($perfil['cedula'] ?? '') ?: '—', ENT_QUOTES, 'UTF-8') ?></strong></div>
          <div class="pf-dato"><span>Rol</span><strong><?= htmlspecialchars($rol, ENT_QUOTES, 'UTF-8') ?></strong></div>
          <div class="pf-dato"><span>Miembro desde</span><strong><?= $fmt($perfil['created_at'] ?? null) ?></strong></div>
          <div class="pf-dato"><span>Última actualización</span><strong><?= $fmt($perfil['updated_at'] ?? null) ?></strong></div>
        </div>
      </div>

      <div class="pf-card">
        <div class="pf-card-title"><span class="bar"></span> Datos personales</div>
        <form method="post" action="index.php?r=perfil.update">
          <div class="mb-3">
            <label class="form-label" style="font-size:12.5px">Nombre completo</label>
            <input class="form-control" name="name" maxlength="160" required
                   value="<?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label" style="font-size:12.5px">Correo electrónico</label>
            <input class="form-control" type="email" name="email" maxlength="160" required
                   value="<?= htmlspecialchars($correo, ENT_QUOTES, 'UTF-8') ?>">
            <div class="form-text" style="font-size:11.5px">Este correo es el que usas para iniciar sesión.</div>
          </div>
          <button class="btn btn-primary" type="submit"><i class="bi bi-save"></i> Guardar cambios</button>
        </form>
      </div>
    </div>

    <!-- ── Columna derecha: seguridad / contraseña ── -->
    <div>
      <div class="pf-card">
        <div class="pf-card-title"><span class="bar"></span> Cambiar contraseña</div>
        <form method="post" action="index.php?r=perfil.password" id="pf-form-pass" autocomplete="off">

          <div class="mb-3">
            <label class="form-label" style="font-size:12.5px">Contraseña actual</label>
            <div class="pf-pass">
              <input class="form-control" type="password" name="current_password" id="pf-actual"
                     autocomplete="current-password" required>
              <button class="pf-eye" type="button" data-target="pf-actual" aria-label="Mostrar contraseña">
                <i class="bi bi-eye"></i>
              </button>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label" style="font-size:12.5px">Nueva contraseña</label>
            <div class="pf-pass">
              <input class="form-control" type="password" name="new_password" id="pf-nueva"
                     autocomplete="new-password" minlength="8" required>
              <button class="pf-eye" type="button" data-target="pf-nueva" aria-label="Mostrar contraseña">
                <i class="bi bi-eye"></i>
              </button>
            </div>
            <div class="pf-strength"><i id="pf-bar"></i></div>
            <span class="pf-strength-txt" id="pf-bar-txt">Seguridad de la contraseña</span>
          </div>

          <div class="mb-2">
            <label class="form-label" style="font-size:12.5px">Confirmar nueva contraseña</label>
            <div class="pf-pass">
              <input class="form-control" type="password" name="new_password_confirmation" id="pf-confirm"
                     autocomplete="new-password" minlength="8" required>
              <button class="pf-eye" type="button" data-target="pf-confirm" aria-label="Mostrar contraseña">
                <i class="bi bi-eye"></i>
              </button>
            </div>
          </div>

          <ul class="pf-reqs" id="pf-reqs">
            <li data-req="len"><i class="bi bi-circle"></i> Mínimo 8 caracteres</li>
            <li data-req="dif"><i class="bi bi-circle"></i> Diferente a la contraseña actual</li>
            <li data-req="match"><i class="bi bi-circle"></i> La confirmación coincide</li>
          </ul>

          <button class="btn btn-primary mt-3" type="submit" id="pf-btn-pass">
            <i class="bi bi-shield-lock"></i> Actualizar contraseña
          </button>
        </form>
      </div>
    </div>

  </div>
</section>

<script src="assets/js/perfil.js?v=1"></script>
