/* Agente de Pólizas — pantalla Analizar.
   Llama al motor Flask a través del proxy PHP (window.AG_PROXY + ruta /api/...).
   Replica el flujo de colvatel-app: analizar, resultados, recalcular, chat, acta.

   Instrucciones del revisor: texto libre que se envía con el análisis y que el
   motor aplica con prioridad sobre el contrato y el Manual. Si se cambian tras
   un análisis, «Reanalizar» vuelve a enviar los mismos archivos con el texto
   nuevo (queda como un análisis más en el Histórico). */
(function () {
  const PROXY = window.AG_PROXY;
  const MAX_INSTR = 4000;
  let contratoFile = null, polizaFiles = [], modelo = null;
  let ultimoDoc = null, ultimoDatos = null, ultimosResultados = [], ultimosCriterios = [], chatHist = [];
  let instrUsadas = null;   // texto con el que se hizo el último análisis (null = aún no hay)

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
    const zone = $(zoneId), input = $(inputId);
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

  // ── Instrucciones del revisor ──
  const txt = $('ag-instrucciones');
  const instrActual = () => (txt.value || '').trim();

  function refrescarInstr() {
    const n = txt.value.length;
    $('ag-instr-count').textContent = n.toLocaleString('es-CO') + ' / ' + MAX_INSTR.toLocaleString('es-CO');
    $('ag-instr-count').classList.toggle('lleno', n > MAX_INSTR * 0.9);
    if (instrUsadas === null) return;               // todavía no hay un análisis con el que comparar
    const cambio = instrActual() !== instrUsadas;
    $('ag-instr-box').classList.toggle('cambiado', cambio);
    $('ag-instr-estado').textContent = cambio
      ? 'Las instrucciones cambiaron desde el último análisis.'
      : (instrUsadas ? 'El último análisis se hizo con estas instrucciones.' : 'El último análisis se hizo sin instrucciones.');
    $('btn-reanalizar').classList.toggle('btn-primary', cambio);
    $('btn-reanalizar').classList.toggle('btn-outline-primary', !cambio);
  }
  txt.addEventListener('input', refrescarInstr);
  // Ctrl+Enter en la barra de instrucciones lanza el análisis, como en un chat.
  txt.addEventListener('keydown', e => {
    if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) { e.preventDefault(); analizar(); }
  });

  // Ejemplos rápidos: añaden una plantilla y seleccionan el primer [marcador]
  // para que el usuario lo sustituya escribiendo directamente.
  document.querySelectorAll('.ag-chip').forEach(ch => ch.addEventListener('click', () => {
    const plantilla = ch.dataset.txt || '';
    const previo = txt.value.replace(/\s+$/, '');
    txt.value = (previo ? previo + '\n' : '') + plantilla;
    txt.focus();
    const ini = txt.value.length - plantilla.length;
    const m = plantilla.match(/\[[^\]]+\]/);
    if (m) { const a = ini + plantilla.indexOf(m[0]); txt.setSelectionRange(a, a + m[0].length); }
    refrescarInstr();
  }));
  refrescarInstr();

  // ── Analizar / Reanalizar ──
  function bloquear(on) {
    $('btn-analizar').disabled = on || !(contratoFile && polizaFiles.length);
    $('btn-reanalizar').disabled = on;
  }

  async function analizar() {
    if (!contratoFile || !polizaFiles.length) return;
    const instr = instrActual();
    if (instr.length > MAX_INSTR) { showError('Las instrucciones superan los ' + MAX_INSTR + ' caracteres.'); return; }
    if (/\[[^\]]+\]/.test(instr) &&
        !confirm('Las instrucciones tienen marcadores sin completar (entre corchetes). ¿Analizar igualmente?')) return;

    showError(''); $('ag-resultado').style.display = 'none'; $('ag-progreso').style.display = 'block';
    $('ag-status').textContent = instr
      ? 'El agente de IA está analizando los archivos con tus instrucciones…'
      : 'El agente de IA está analizando los archivos…';
    bloquear(true);

    const fd = new FormData();
    fd.append('modelo', modelo || 'gemini');
    fd.append('contrato', contratoFile);
    polizaFiles.forEach(f => fd.append('polizas[]', f));
    if (instr) fd.append('instrucciones', instr);
    try {
      const r = await fetch(PROXY + '/api/analizar', { method: 'POST', body: fd });
      const d = await r.json();
      if (!r.ok || d.error) throw new Error(d.error || ('HTTP ' + r.status));
      instrUsadas = instr;
      $('ag-instr-actions').style.display = 'flex';
      pintarResultado(d);
      refrescarInstr();
    } catch (e) { showError(e.message); }
    finally { $('ag-progreso').style.display = 'none'; bloquear(false); }
  }
  $('btn-analizar').addEventListener('click', analizar);
  $('btn-reanalizar').addEventListener('click', analizar);

  // Etiqueta de procedencia de un requisito: quién lo fijó, si no fue el Manual.
  function fuente(f) {
    if (f === 'revisor') return ' <span class="ag-src rev" title="Fijado por las instrucciones del revisor">revisor</span>';
    if (f === 'documento') return ' <span class="ag-src doc" title="Fijado por la cláusula de garantías del contrato u orden">contrato</span>';
    return '';
  }

  // Dato de la póliza aportado por el revisor: etiqueta con lo que se había leído.
  function marcaRevisor(r, campo, mostrar) {
    const pr = r.poliza_revisor || {};
    if (!(campo in pr)) return '';
    const leido = pr[campo] ? mostrar(pr[campo]) : 'sin dato';
    return ' <span class="ag-src rev" title="Dato indicado por el revisor. La póliza leída decía: ' + esc(leido) + '">revisor</span>';
  }

  function pintarResultado(d) {
    showError('');
    ultimoDoc = d.doc_id; ultimoDatos = d.datos; ultimosResultados = d.resultados || [];
    ultimosCriterios = d.criterios_adicionales || []; chatHist = [];
    const dat = d.datos || {};
    $('ag-resultado').style.display = 'block';
    const ok = d.todos_ok;
    const badge = $('ag-badge');
    badge.className = 'ag-badge-global ' + (ok ? 'ok' : 'no');
    badge.textContent = ok ? '✔ PÓLIZA APROBADA' : '⚠ PÓLIZA OBSERVADA';
    if (ok && d.poliza_revisor) {
      badge.textContent += ' · con datos del revisor';
      badge.title = 'La aprobación se apoya en datos de póliza indicados por el revisor. Verifique su soporte antes de firmar.';
    } else badge.title = '';

    pintarConsumo(d.uso_tokens);

    // Las advertencias del revisor (👤 aplicadas, ✋ no aplicadas) van en su
    // propio recuadro, separadas de los avisos de lectura.
    const todas = d.advertencias || [];
    const esRevisor = a => a.startsWith('👤') || a.startsWith('✋');
    const delRevisor = todas.filter(esRevisor);
    const resto = todas.filter(a => !esRevisor(a));

    const rev = $('ag-revisor');
    if (delRevisor.length) {
      rev.style.display = 'block';
      rev.innerHTML = '<div class="ag-rev-head"><i class="bi bi-person-gear"></i> Instrucciones del revisor</div>'
        + delRevisor.map(a => '<div class="' + (a.startsWith('✋') ? 'na' : '') + '">' + esc(a) + '</div>').join('');
    } else rev.style.display = 'none';

    const adv = $('ag-advertencias');
    if (resto.length) { adv.style.display = 'block'; adv.innerHTML = resto.map(a => '<div>' + esc(a) + '</div>').join(''); }
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
      const req = r.valor_minimo_fijo ? 'Fijo' + fuente(r.fuente_valor) : (r.pct_requerido || 0) + '%' + fuente(r.fuente_pct);
      return '<tr class="' + cls + '"><td>' + esc(r.label) + expl + '</td>' +
        '<td>' + req + '</td>' +
        '<td>' + fmt(r.valor_minimo) + '</td>' +
        '<td class="' + (r.ok_valor ? '' : 'ag-bad') + '">' + fmt(r.valor_poliza) + marcaRevisor(r, 'valor', fmt) + '</td>' +
        '<td>' + esc(r.hasta_requerido || '—') + fuente(r.fuente_meses) + '</td>' +
        '<td class="' + (r.ok_hasta ? '' : 'ag-bad') + '">' + esc(r.hasta_poliza || '—') + marcaRevisor(r, 'hasta', x => x) + '</td>' +
        '<td><span class="ag-pill ' + cls + '">' + (r.ok ? 'CUMPLE' : 'NO CUMPLE') + '</span></td></tr>';
    }).join('');

    pintarCriterios(ultimosCriterios);
  }

  // ── Criterios adicionales del revisor ──
  function pintarCriterios(lista) {
    $('ag-criterios').style.display = lista.length ? 'block' : 'none';
    $('ag-tabla-criterios').innerHTML = lista.map(c => {
      const est = c.cumple === true ? ['ok', 'CUMPLE'] : c.cumple === false ? ['no', 'NO CUMPLE'] : ['rev', 'NO VERIFICABLE'];
      const ref = (c.documento || c.pagina)
        ? '<div class="ag-crit-ref">' + esc(c.documento || '') + (c.pagina ? ' · pág. ' + esc(c.pagina) : '') + '</div>' : '';
      const evidencia = c.cita
        ? '«' + esc(c.cita) + '»' + ref
        : '<span class="ag-crit-sin">Sin evidencia en los documentos</span>';
      return '<tr class="' + est[0] + '"><td>' + esc(c.criterio)
        + (c.explicacion ? '<div class="ag-crit-expl">' + esc(c.explicacion) + '</div>' : '') + '</td>'
        + '<td class="ag-crit-cita">' + evidencia + '</td>'
        + '<td><span class="ag-pill ' + est[0] + '">' + est[1] + '</span></td></tr>';
    }).join('');
  }

  // ── Informe de consumo de tokens ──
  function pintarConsumo(u) {
    const box = $('ag-consumo');
    if (!box) return;
    if (!u || !u.total) { box.style.display = 'none'; return; }
    const n = (v) => (Number(v) || 0).toLocaleString('es-CO');
    // Avisos: entrada alta (cupo gratuito Gemini) o respuesta truncada.
    const avisos = [];
    if (u.entrada > 16000 && !u.vision) avisos.push('La entrada supera 16.000 tokens: excede el cupo gratuito de Gemini y ralentiza a los demás motores.');
    if (u.tope_salida_motor && u.salida >= u.tope_salida_motor * 0.95) avisos.push('La respuesta llegó al tope de salida del motor: puede venir incompleta. Sube «Tokens» en Claves APIs.');
    box.style.display = 'block';
    box.innerHTML =
      '<div class="ag-consumo-head"><i class="bi bi-speedometer2"></i> Consumo del análisis'
      + '<span class="ag-consumo-modelo">' + esc(u.modelo_id || '') + '</span></div>'
      + '<div class="ag-consumo-grid">'
      + '  <div><span>Entrada</span><strong>' + n(u.entrada) + '</strong><em>tokens</em></div>'
      + '  <div><span>Salida</span><strong>' + n(u.salida) + '</strong><em>de ' + n(u.tope_salida_motor) + ' máx.</em></div>'
      + '  <div><span>Total</span><strong>' + n(u.total) + '</strong><em>tokens</em></div>'
      + '  <div><span>Documentos</span><strong>' + n(u.chars_contrato + u.chars_polizas) + '</strong><em>caracteres</em></div>'
      + '</div>'
      + (avisos.length ? '<div class="ag-consumo-aviso">' + avisos.map(a => '<div>⚠ ' + esc(a) + '</div>').join('') + '</div>' : '');
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
    const inp = $('ag-chat-input'); const t = inp.value.trim();
    if (!t || !ultimoDatos) return;
    inp.value = ''; addMsg('user', t); chatHist.push({ role: 'user', content: t });
    try {
      const r = await fetch(PROXY + '/api/chat', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          messages: chatHist,
          context: { datos: ultimoDatos, resultados: ultimosResultados, criterios: ultimosCriterios },
          modelo: modelo
        })
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
