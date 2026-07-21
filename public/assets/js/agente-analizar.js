/* Agente de Pólizas — pantalla Analizar.
   Llama al motor Flask a través del proxy PHP (window.AG_PROXY + ruta /api/...).
   Replica el flujo de colvatel-app: analizar, resultados, recalcular, chat, acta. */
(function () {
  const PROXY = window.AG_PROXY;
  let contratoFile = null, polizaFiles = [], modelo = null;
  let ultimoDoc = null, ultimoDatos = null, ultimosResultados = [], chatHist = [];

  const $ = (id) => document.getElementById(id);
  const fmt = (v) => '$' + (Number(v) || 0).toLocaleString('es-CO');
  const esc = (s) => (s == null ? '' : String(s).replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c])));

  // ── Selección de motor ──
  document.querySelectorAll('.ag-motor').forEach(b => {
    if (b.classList.contains('active')) modelo = b.dataset.modelo;
    b.addEventListener('click', () => {
      document.querySelectorAll('.ag-motor').forEach(x => x.classList.remove('active'));
      b.classList.add('active'); modelo = b.dataset.modelo;
    });
  });

  // ── Zonas de carga ──
  function wireZone(zoneId, inputId, nameId, multi) {
    const zone = $(zoneId), input = $(inputId), name = $(nameId);
    input.addEventListener('change', () => setFiles(input.files, nameId, multi));
    ['dragover', 'dragenter'].forEach(e => zone.addEventListener(e, ev => { ev.preventDefault(); zone.classList.add('drag'); }));
    ['dragleave', 'drop'].forEach(e => zone.addEventListener(e, ev => { ev.preventDefault(); zone.classList.remove('drag'); }));
    zone.addEventListener('drop', ev => { if (ev.dataTransfer.files.length) { input.files = ev.dataTransfer.files; setFiles(ev.dataTransfer.files, nameId, multi); } });
  }
  function setFiles(files, nameId, multi) {
    if (multi) { polizaFiles = Array.from(files); $(nameId).textContent = polizaFiles.map(f => f.name).join(', '); }
    else { contratoFile = files[0] || null; $(nameId).textContent = contratoFile ? contratoFile.name : ''; }
    $('btn-analizar').disabled = !(contratoFile && polizaFiles.length);
  }
  wireZone('zone-contrato', 'in-contrato', 'name-contrato', false);
  wireZone('zone-poliza', 'in-poliza', 'name-poliza', true);

  // ── Analizar ──
  $('btn-analizar').addEventListener('click', async () => {
    if (!contratoFile || !polizaFiles.length) return;
    showError(''); $('ag-resultado').style.display = 'none'; $('ag-progreso').style.display = 'block';
    $('btn-analizar').disabled = true;
    const fd = new FormData();
    fd.append('modelo', modelo || 'gemini');
    fd.append('contrato', contratoFile);
    polizaFiles.forEach(f => fd.append('polizas[]', f));
    try {
      const r = await fetch(PROXY + '/api/analizar', { method: 'POST', body: fd });
      const d = await r.json();
      if (!r.ok || d.error) throw new Error(d.error || ('HTTP ' + r.status));
      pintarResultado(d);
    } catch (e) { showError(e.message); }
    finally { $('ag-progreso').style.display = 'none'; $('btn-analizar').disabled = false; }
  });

  function pintarResultado(d) {
    showError('');
    ultimoDoc = d.doc_id; ultimoDatos = d.datos; ultimosResultados = d.resultados || []; chatHist = [];
    const dat = d.datos || {};
    $('ag-resultado').style.display = 'block';
    const ok = d.todos_ok;
    const badge = $('ag-badge');
    badge.className = 'ag-badge-global ' + (ok ? 'ok' : 'no');
    badge.textContent = ok ? '✔ PÓLIZA APROBADA' : '⚠ PÓLIZA OBSERVADA';

    const adv = $('ag-advertencias');
    if (d.advertencias && d.advertencias.length) { adv.style.display = 'block'; adv.innerHTML = d.advertencias.map(a => '<div>' + esc(a) + '</div>').join(''); }
    else adv.style.display = 'none';

    $('ag-info-contrato').innerHTML =
      '<b>' + esc(dat.numero_contrato || 'N/D') + '</b> · ' + esc(dat.tipo || '') + '<br>' +
      esc(dat.contratista || '') + ' (NIT ' + esc(dat.nit_contratista || 'N/D') + ')<br>' +
      'Vigencia: ' + esc(dat.fecha_inicio || '?') + ' → ' + esc(dat.fecha_fin || '?') + '<br>' +
      'Valor sin IVA: <b>' + fmt(dat.valor_sin_iva) + '</b> · Total: ' + fmt(dat.valor_total);
    $('ag-info-poliza').innerHTML =
      'Aseguradora: <b>' + esc(dat.aseguradora || 'N/D') + '</b><br>' +
      'Póliza N°: ' + esc(dat.num_poliza || 'N/D') + '<br>' +
      'Prima pagada: ' + (dat.prima_pagada ? 'Sí' : 'No') + ' · Firmada: ' + (dat.firmada ? 'Sí' : 'No');

    $('ag-fecha-editor').style.display = 'block';
    if (dat.fecha_inicio) $('ag-f-inicio').value = dat.fecha_inicio;
    if (dat.fecha_fin) $('ag-f-fin').value = dat.fecha_fin;

    $('ag-tabla-amparos').innerHTML = (d.resultados || []).map(r => {
      const cls = r.ok ? 'ok' : 'no';
      const expl = (!r.ok && r.explicacion_no_cumple) ? '<div style="font-size:11.5px" class="ag-bad">' + esc(r.explicacion_no_cumple) + '</div>' : '';
      return '<tr class="' + cls + '"><td>' + esc(r.label) + expl + '</td>' +
        '<td>' + (r.pct_requerido || 0) + '%</td>' +
        '<td>' + fmt(r.valor_minimo) + '</td>' +
        '<td class="' + (r.ok_valor ? '' : 'ag-bad') + '">' + fmt(r.valor_poliza) + '</td>' +
        '<td>' + esc(r.hasta_requerido || '—') + '</td>' +
        '<td class="' + (r.ok_hasta ? '' : 'ag-bad') + '">' + esc(r.hasta_poliza || '—') + '</td>' +
        '<td><span class="ag-pill ' + cls + '">' + (r.ok ? 'CUMPLE' : 'NO CUMPLE') + '</span></td></tr>';
    }).join('');
  }

  // ── Recalcular fechas ──
  $('ag-btn-recalcular').addEventListener('click', async () => {
    if (!ultimoDoc) return;
    const body = { doc_id: ultimoDoc, fecha_inicio: $('ag-f-inicio').value, fecha_fin: $('ag-f-fin').value };
    try {
      const r = await fetch(PROXY + '/api/recalcular', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) });
      const d = await r.json();
      if (!r.ok || d.error) throw new Error(d.error || ('HTTP ' + r.status));
      pintarResultado(d);
    } catch (e) { showError(e.message); }
  });

  // ── Descargar acta ──
  $('ag-btn-excel').addEventListener('click', () => {
    if (ultimoDoc) window.location = PROXY + '/api/descargar-excel/' + ultimoDoc;
  });

  // ── Chat ──
  function addMsg(role, text) {
    const div = document.createElement('div');
    div.className = 'ag-msg ' + (role === 'user' ? 'u' : 'a');
    div.textContent = text;
    $('ag-chat-msgs').appendChild(div);
    $('ag-chat-msgs').scrollTop = $('ag-chat-msgs').scrollHeight;
  }
  async function enviarChat() {
    const inp = $('ag-chat-input'); const txt = inp.value.trim();
    if (!txt || !ultimoDatos) return;
    inp.value = ''; addMsg('user', txt); chatHist.push({ role: 'user', content: txt });
    try {
      const r = await fetch(PROXY + '/api/chat', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ messages: chatHist, context: { datos: ultimoDatos, resultados: ultimosResultados }, modelo: modelo })
      });
      const d = await r.json();
      if (!r.ok || d.error) throw new Error(d.error || ('HTTP ' + r.status));
      addMsg('assistant', d.reply); chatHist.push({ role: 'assistant', content: d.reply });
    } catch (e) { addMsg('assistant', '⚠ ' + e.message); }
  }
  $('ag-chat-send').addEventListener('click', enviarChat);
  $('ag-chat-input').addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); enviarChat(); } });

  function showError(msg) {
    const b = $('ag-error');
    if (!msg) { b.style.display = 'none'; return; }
    b.style.display = 'block'; b.textContent = msg;
  }
})();
