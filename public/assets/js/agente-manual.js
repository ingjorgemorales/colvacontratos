/* Agente de Pólizas — pantalla Manual de Contratación (vía proxy al Flask). */
(function () {
  const PROXY = window.AG_PROXY;
  const $ = (id) => document.getElementById(id);
  let manualId = null;

  async function jpost(path, body, isJson) {
    const opt = { method: 'POST' };
    if (isJson) { opt.headers = { 'Content-Type': 'application/json' }; opt.body = JSON.stringify(body); }
    else { opt.body = body; }
    const r = await fetch(PROXY + path, opt);
    const d = await r.json().catch(() => ({}));
    if (!r.ok || d.error) throw new Error(d.error || ('HTTP ' + r.status));
    return d;
  }
  function status(msg, cls) { const s = $('ag-man-status'); s.className = cls || ''; s.textContent = msg; }

  $('ag-man-subir').addEventListener('click', async () => {
    const f = $('ag-man-file').files[0];
    if (!f) { status('Selecciona un archivo primero.', 'text-danger'); return; }
    const btn = $('ag-man-subir'); btn.disabled = true;
    try {
      status('Subiendo el manual…', 'text-muted');
      const fd = new FormData(); fd.append('manual', f);
      const up = await jpost('/api/manual/subir', fd, false);
      manualId = up.manual_id;
      status('Extrayendo parámetros con IA (' + $('ag-man-motor').value + ')… puede tardar.', 'text-muted');
      const ex = await jpost('/api/manual/extraer/' + manualId, { modelo: $('ag-man-motor').value }, true);
      $('ag-man-params').value = JSON.stringify(ex.parametros, null, 2);
      $('ag-man-params-wrap').style.display = 'block';
      status('✔ Parámetros extraídos. Revisa, guarda y activa la versión.', 'text-success');
    } catch (e) { status('⚠ ' + e.message, 'text-danger'); }
    finally { btn.disabled = false; }
  });

  $('ag-man-guardar').addEventListener('click', async () => {
    if (!manualId) return;
    try {
      await jpost('/api/manual/guardar-params/' + manualId, { parametros_json: $('ag-man-params').value }, true);
      status('✔ Cambios guardados.', 'text-success');
    } catch (e) { status('⚠ ' + e.message, 'text-danger'); }
  });

  $('ag-man-activar').addEventListener('click', async () => {
    if (!manualId) return;
    try { await jpost('/api/manual/activar/' + manualId, {}, true); location.reload(); }
    catch (e) { status('⚠ ' + e.message, 'text-danger'); }
  });

  document.querySelectorAll('.ag-man-ver').forEach(b => b.addEventListener('click', async () => {
    manualId = b.dataset.id;
    try {
      const r = await fetch(PROXY + '/api/manual/params/' + manualId);
      const d = await r.json();
      if (d.error) throw new Error(d.error);
      $('ag-man-params').value = d.parametros_json ? JSON.stringify(JSON.parse(d.parametros_json), null, 2) : '(sin parámetros extraídos)';
      $('ag-man-params-wrap').style.display = 'block';
      $('ag-man-params-wrap').scrollIntoView({ behavior: 'smooth' });
      status('Editando manual #' + manualId, 'text-muted');
    } catch (e) { status('⚠ ' + e.message, 'text-danger'); }
  }));

  document.querySelectorAll('.ag-man-act').forEach(b => b.addEventListener('click', async () => {
    try { await jpost('/api/manual/activar/' + b.dataset.id, {}, true); location.reload(); }
    catch (e) { alert('Error: ' + e.message); }
  }));

  document.querySelectorAll('.ag-man-del').forEach(b => b.addEventListener('click', async () => {
    if (!confirm('¿Eliminar este manual?')) return;
    try {
      const r = await fetch(PROXY + '/api/manual/eliminar/' + b.dataset.id, { method: 'DELETE' });
      const d = await r.json().catch(() => ({}));
      if (!r.ok || d.error) throw new Error(d.error || ('HTTP ' + r.status));
      location.reload();
    } catch (e) { alert('Error: ' + e.message); }
  }));
})();
