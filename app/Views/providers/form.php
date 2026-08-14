<?php
$p = $provider ?? [];
$isEdit = !empty($p['id']);
$v = static fn($key, $default = '') => htmlspecialchars((string)($p[$key] ?? $default), ENT_QUOTES, 'UTF-8');
$sel = function (string $key, array $rows) use ($p): string {
    $current = (int)($p[$key] ?? 0);
    $html = '<option value="">Seleccione...</option>';
    foreach ($rows as $row) {
        $id = (int)$row['id'];
        $label = trim(((string)($row['code'] ?? '')) !== '' ? ($row['code'] . ' - ' . $row['name']) : $row['name']);
        $html .= '<option value="' . $id . '" ' . ($current === $id ? 'selected' : '') . '>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</option>';
    }
    return $html;
};
$members = json_decode((string)($p['consortium_members_json'] ?? '[]'), true);
if (!is_array($members)) {
    $members = [];
}
$profile = (string)($p['counterparty_profile'] ?? 'proveedor');
$profileOptions = [
    'proveedor' => 'Proveedor',
    'cliente' => 'Cliente',
    'subcontratista' => 'Subcontratista',
];
?>

<section class="provider-form-modern">
  <div class="module-hero form-hero">
    <div>
      <a class="back-link" href="index.php?r=providers"><i class="bi bi-arrow-left"></i> Volver a directorio</a>
      <span class="section-eyebrow"><?= $isEdit ? 'Edicion de contraparte' : 'Nueva contraparte' ?></span>
      <h1><?= htmlspecialchars($title ?? ($isEdit ? 'Editar contraparte' : 'Nueva Contraparte'), ENT_QUOTES, 'UTF-8') ?></h1>
      <p>Actualiza los datos de la contraparte, su clasificacion y la informacion de notificacion.</p>
    </div>
    <div class="module-actions">
      <a class="btn btn-light" href="index.php?r=providers"><i class="bi bi-x-lg"></i> Cancelar</a>
      <button class="btn btn-primary" form="providerForm" type="submit"><i class="bi bi-check2-circle"></i> Guardar</button>
    </div>
  </div>

  <form id="providerForm" method="post" action="<?= htmlspecialchars($action, ENT_QUOTES, 'UTF-8') ?>" class="provider-form-shell" data-mode="<?= $isEdit ? 'edit-' . (int)$p['id'] : 'create' ?>">
    <section class="form-section provider-section">
      <div class="form-section-head">
        <i class="bi bi-person-vcard"></i>
        <div>
          <h2>Identificacion</h2>
          <p>Datos principales para reconocer la contraparte en contratos y reportes.</p>
        </div>
      </div>
      <div class="form-grid provider-form-grid">
        <label class="form-field small">
          <span>NIT / ID *</span>
          <input name="document_number" required class="form-control" value="<?= $v('document_number') ?>">
        </label>
        <label class="form-field small">
          <span>DV</span>
          <input name="verification_digit" class="form-control" value="<?= $v('verification_digit') ?>">
        </label>
        <label class="form-field wide">
          <span>Nombre *</span>
          <input name="name" required class="form-control" value="<?= $v('name') ?>">
        </label>
        <label class="form-field wide">
          <span>Perfil contraparte</span>
          <select name="counterparty_profile" id="counterpartyProfile" class="form-select">
            <?php foreach ($profileOptions as $value => $label): ?>
              <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>" <?= $profile === $value ? 'selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label class="form-field wide">
          <span>Tipo Contraparte</span>
          <select name="provider_type_id" class="form-select"><?= $sel('provider_type_id', $types ?? []) ?></select>
        </label>
        <label class="form-field wide">
          <span>Estado</span>
          <div class="modern-check-card">
            <input class="form-check-input" type="checkbox" id="providerActive" name="active" value="1" <?= ((int)($p['active'] ?? 1) === 1) ? 'checked' : '' ?>>
            <label for="providerActive">
              <strong>Contraparte activa</strong>
              <small>Disponible para seleccion y gestion contractual.</small>
            </label>
          </div>
        </label>
      </div>
    </section>

    <section class="form-section provider-section">
      <div class="form-section-head">
        <i class="bi bi-geo-alt"></i>
        <div>
          <h2>Ubicacion y contacto</h2>
          <p>Informacion operativa para comunicacion, trazabilidad y documentos.</p>
        </div>
      </div>
      <div class="form-grid provider-form-grid">
        <label class="form-field wide">
          <span>Ciudad</span>
          <select name="city_id" class="form-select">
            <option value="">Seleccione...</option>
            <?php foreach (($cities ?? []) as $city): ?>
              <option value="<?= (int)$city['id'] ?>" <?= ((int)($p['city_id'] ?? 0) === (int)$city['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($city['name'] . (!empty($city['department']) ? ' - ' . $city['department'] : ''), ENT_QUOTES, 'UTF-8') ?>
              </option>
            <?php endforeach; ?>
          </select>
          <input type="hidden" name="city" value="<?= $v('city') ?>">
        </label>
        <label class="form-field wide">
          <span>Domicilio contraparte</span>
          <input name="address" class="form-control" value="<?= $v('address') ?>">
        </label>
        <label class="form-field small">
          <span>Telefono</span>
          <input name="phone" class="form-control" value="<?= $v('phone') ?>">
        </label>
        <input type="hidden" name="contact_name" value="<?= $v('contact_name') ?>">
        <label class="form-field wide">
          <span>Correo Notificaciones</span>
          <input type="email" name="email" class="form-control" value="<?= $v('email') ?>">
        </label>
      </div>
    </section>

    <section class="form-section provider-section">
      <div class="form-section-head">
        <i class="bi bi-shield-check"></i>
        <div>
          <h2>Clasificacion Contraparte</h2>
          <p>Parametrizacion usada para reportes, filtros y gobierno contractual.</p>
        </div>
      </div>
      <div class="form-grid provider-form-grid">
        <label class="form-field wide contractor-field">
          <span>Tipo contratista</span>
          <select name="tipo_contratista_id" class="form-select"><?= $sel('tipo_contratista_id', $tipoContratista ?? []) ?></select>
        </label>
        <label class="form-field wide">
          <span>Tipo persona</span>
          <select name="tipo_persona_id" class="form-select"><?= $sel('tipo_persona_id', $tipoPersona ?? []) ?></select>
        </label>
        <label class="form-field wide">
          <span>Naturaleza</span>
          <select name="naturaleza_id" class="form-select"><?= $sel('naturaleza_id', $naturaleza ?? []) ?></select>
        </label>
        <label class="form-field wide">
          <span>Clasificacion</span>
          <select name="clasificacion_id" class="form-select"><?= $sel('clasificacion_id', $clasificacion ?? []) ?></select>
        </label>
        <label class="form-field wide contractor-field">
          <span>Nacionalidad del contratista</span>
          <select name="nacionalidad_contratista_id" class="form-select"><?= $sel('nacionalidad_contratista_id', $nacionalidadContratista ?? []) ?></select>
        </label>
        <label class="form-field wide contractor-field">
          <span>Clase contratista</span>
          <select name="clase_contratista_id" id="claseContratista" class="form-select"><?= $sel('clase_contratista_id', $claseContratista ?? []) ?></select>
        </label>
      </div>

      <div class="ut-members-panel" id="utMembersBox">
        <div class="form-note full">
          <i class="bi bi-info-circle"></i>
          <span><strong>Union Temporal / Consorcio:</strong> registra los integrantes y el porcentaje de participacion.</span>
        </div>
        <div class="ut-members-grid">
          <?php for ($i = 0; $i < 4; $i++): $member = $members[$i] ?? []; ?>
            <label class="form-field">
              <span>Integrante <?= $i + 1 ?></span>
              <input name="ut_member_name[]" class="form-control" value="<?= htmlspecialchars((string)($member['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Nombre integrante">
            </label>
            <label class="form-field">
              <span>% Participacion <?= $i + 1 ?></span>
              <input name="ut_member_percent[]" type="number" step="0.01" min="0" max="100" class="form-control" value="<?= htmlspecialchars((string)($member['percent'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="0.00">
            </label>
          <?php endfor; ?>
        </div>
      </div>
    </section>

    <section class="form-section provider-section">
      <div class="form-section-head">
        <i class="bi bi-journal-text"></i>
        <div>
          <h2>Notas internas</h2>
          <p>Observaciones de gestion o trazabilidad del tercero.</p>
        </div>
      </div>
      <label class="form-field full">
        <span>Notas</span>
        <textarea name="notes" class="form-control" rows="4"><?= $v('notes') ?></textarea>
      </label>
    </section>

    <div class="form-sticky-actions">
      <a class="btn btn-light" href="index.php?r=providers"><i class="bi bi-x-lg"></i> Cancelar</a>
      <button class="btn btn-primary" type="submit"><i class="bi bi-check2-circle"></i> Guardar contraparte</button>
    </div>
  </form>
</section>

<script>
(function(){
  const form = document.getElementById('providerForm');
  const profile = document.getElementById('counterpartyProfile');
  const selector = document.getElementById('claseContratista');
  const box = document.getElementById('utMembersBox');
  const contractorFields = document.querySelectorAll('.contractor-field');
  function toggleProfileFields(){
    const isClient = (profile?.value || '') === 'cliente';
    contractorFields.forEach((field) => {
      field.classList.toggle('d-none', isClient);
      field.querySelectorAll('select,input,textarea').forEach((control) => {
        control.disabled = isClient;
      });
    });
    toggleMembers();
  }
  function toggleMembers(){
    if (!selector || !box) return;
    const isClient = (profile?.value || '') === 'cliente';
    const text = (selector.options[selector.selectedIndex]?.text || '').toLowerCase();
    const shouldShow = !isClient && (text.includes('union temporal') || text.includes('union temporal') || text.includes('consorcio'));
    box.classList.toggle('is-visible', shouldShow);
  }
  function initDraft(){
    if (!form || !window.localStorage) return;
    const key = 'colvacontratos:counterparty-draft:' + (form.dataset.mode || 'create');
    if ((form.dataset.mode || '') === 'create') {
      const raw = localStorage.getItem(key);
      if (raw) {
        try {
          const draft = JSON.parse(raw);
          Object.entries(draft).forEach(([name, value]) => {
            const controls = form.elements[name];
            if (!controls) return;
            const list = controls instanceof RadioNodeList ? Array.from(controls) : [controls];
            list.forEach((control) => {
              if (control.type === 'checkbox') control.checked = value === '1';
              else if (control.type !== 'hidden') control.value = value;
            });
          });
        } catch (error) {}
      }
    }
    form.addEventListener('input', () => {
      const data = {};
      new FormData(form).forEach((value, name) => { data[name] = value; });
      data.active = form.elements.active?.checked ? '1' : '0';
      localStorage.setItem(key, JSON.stringify(data));
    });
    form.addEventListener('submit', () => localStorage.removeItem(key));
  }
  profile?.addEventListener('change', toggleProfileFields);
  selector?.addEventListener('change', toggleMembers);
  toggleProfileFields();
  initDraft();
})();
</script>
