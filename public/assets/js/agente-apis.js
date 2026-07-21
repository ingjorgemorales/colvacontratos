/* Agente de Pólizas — pantalla Claves APIs / proveedores / motores (vía proxy). */
(function () {
  const PROXY = window.AG_PROXY;
  const $ = (id) => document.getElementById(id);
  function msg(text, ok) {
    const m = $('ag-apis-msg');
    m.className = 'alert alert-' + (ok ? 'success' : 'danger');
    m.textContent = text;
    m.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }
  async function call(path, method, body, isJson) {
    const opt = { method: method || 'GET' };
    if (body != null) {
      if (isJson) { opt.headers = { 'Content-Type': 'application/json' }; opt.body = JSON.stringify(body); }
      else opt.body = body;
    }
    const r = await fetch(PROXY + path, opt);
    const d = await r.json().catch(() => ({}));
    if (!r.ok || d.error) throw new Error(d.error || ('HTTP ' + r.status));
    return d;
  }

  // ── Claves ──
  document.querySelectorAll('.ag-key-save').forEach(b => b.addEventListener('click', async () => {
    const nombre = b.dataset.nombre;
    const inp = document.querySelector('.ag-key-in[data-nombre="' + CSS.escape(nombre) + '"]');
    const valor = inp.value.trim();
    if (valor.length < 8) { msg('La clave es demasiado corta.', false); return; }
    try { await call('/api/apikeys/guardar', 'POST', { nombre, valor }, true); location.reload(); }
    catch (e) { msg(e.message, false); }
  }));
  document.querySelectorAll('.ag-key-del').forEach(b => b.addEventListener('click', async () => {
    if (!confirm('¿Borrar la clave ' + b.dataset.nombre + '?')) return;
    try { await call('/api/apikeys/eliminar/' + encodeURIComponent(b.dataset.nombre), 'DELETE'); location.reload(); }
    catch (e) { msg(e.message, false); }
  }));

  // ── Proveedores ──
  $('ag-prov-add').addEventListener('click', async () => {
    const etiqueta = $('ag-prov-et').value.trim();
    if (!etiqueta) { msg('El nombre del proveedor es obligatorio.', false); return; }
    try {
      await call('/api/proveedores/guardar', 'POST', { etiqueta, base_url: $('ag-prov-url').value.trim(), api_key: $('ag-prov-key').value.trim() }, true);
      location.reload();
    } catch (e) { msg(e.message, false); }
  });
  document.querySelectorAll('.ag-prov-del').forEach(b => b.addEventListener('click', async () => {
    if (!confirm('¿Eliminar este proveedor?')) return;
    try { await call('/api/proveedores/eliminar/' + b.dataset.id, 'DELETE'); location.reload(); }
    catch (e) { msg(e.message, false); }
  }));

  // ── Motores ──
  $('ag-mot-add').addEventListener('click', async () => {
    const etiqueta = $('ag-mot-et').value.trim();
    const proveedor = $('ag-mot-prov').value;
    const model_id = $('ag-mot-model').value.trim();
    if (!etiqueta || !model_id) { msg('Nombre y Model ID son obligatorios.', false); return; }
    try {
      await call('/api/motores/guardar', 'POST', {
        etiqueta, chip: $('ag-mot-chip').value.trim(), proveedor, model_id,
        max_tokens: parseInt($('ag-mot-tok').value) || 4096, activo: true
      }, true);
      location.reload();
    } catch (e) { msg(e.message, false); }
  });
  document.querySelectorAll('.ag-mot-del').forEach(b => b.addEventListener('click', async () => {
    if (!confirm('¿Eliminar este motor?')) return;
    try { await call('/api/motores/eliminar/' + b.dataset.id, 'DELETE'); location.reload(); }
    catch (e) { msg(e.message, false); }
  }));
  document.querySelectorAll('.ag-mot-test').forEach(b => b.addEventListener('click', async () => {
    b.disabled = true; const orig = b.textContent; b.textContent = '…';
    try { const d = await call('/api/motores/probar/' + encodeURIComponent(b.dataset.clave), 'POST'); msg(d.mensaje || 'OK', !!d.ok); }
    catch (e) { msg(e.message, false); }
    finally { b.disabled = false; b.textContent = orig; }
  }));
})();
