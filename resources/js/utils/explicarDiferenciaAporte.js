// Diálogo Material Design 3 que explica, para UNA fila del control "Diferencias en aportes"
// de /lsd/generar, de dónde sale la diferencia y si es un falso positivo.
//
// Se monta como overlay propio sobre <body> (z-index por encima de SweetAlert), así abre
// ENCIMA del diálogo de diferencias sin cerrarlo. Esc y clic en el scrim cierran solo este.

const fmt = (n) => new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS', minimumFractionDigits: 2 }).format(Number(n) || 0)
const pct = (a) => `${(Number(a) * 100).toLocaleString('es-AR', { maximumFractionDigits: 4 })} %`
const esc = (s) => String(s ?? '')
  .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
  .replace(/"/g, '&quot;').replace(/'/g, '&#39;')

// Tolerancia de 2 centavos: redondeos del liquidador de origen.
const coincide = (a, b) => Math.abs(Math.round(a * 100) / 100 - Math.round(b * 100) / 100) <= 0.02

// Busca la causa más probable de la diferencia. Devuelve { tipo, titulo, texto, recomendacion }.
// tipo: 'falso_positivo' | 'real' | 'revisar'.
export const diagnosticarDiferenciaAporte = (i) => {
  const alic = Number(i.alicuota) || 0
  const informado = Number(i.informado) || 0
  const base = Number(i.base) || 0
  const bruto = Number(i.bruto) || 0
  const tope = i.tope != null ? Number(i.tope) : null
  const nr = Array.isArray(i.no_remunerativos) ? i.no_remunerativos : []
  const baseImplicita = alic > 0 ? informado / alic : 0

  if (i.sin_haberes) {
    return {
      tipo: 'revisar',
      titulo: 'Aporte descontado en una liquidación sin haberes',
      texto: `Esta liquidación (${esc(i.tipo_liq)}) no tiene haberes remunerativos (bruto $0), pero descuenta ${esc(i.aporte)} por ${fmt(informado)}. Lo habitual es que los haberes hayan quedado cargados en otra liquidación del mismo período.`,
      recomendacion: 'Revisá con la lupa la liquidación individual <strong>antes de "Ajustar valores"</strong>: el ajuste llevaría este aporte a $0.',
    }
  }

  // 1) Conceptos no remunerativos que el liquidador sumó a la base (uno solo o todos juntos).
  if (alic > 0 && nr.length) {
    const candidatos = nr.map(n => ({ conceptos: [n], total: Number(n.importe) }))
    if (nr.length > 1) candidatos.push({ conceptos: nr, total: nr.reduce((s, n) => s + Number(n.importe), 0) })
    const hit = candidatos.find(c => coincide((base + c.total) * alic, informado))
    if (hit) {
      const lista = hit.conceptos.map(n => `<strong>${esc(n.concepto)} – ${esc(n.descripcion || 'sin descripción')}</strong> (${fmt(n.importe)})`).join(', ')
      return {
        tipo: 'falso_positivo',
        titulo: 'Probable falso positivo',
        texto: `El liquidador de origen calculó ${esc(i.aporte)} sobre el remunerativo <strong>más</strong> ${hit.conceptos.length > 1 ? 'los no remunerativos' : 'el no remunerativo'} ${lista}. El control usa solo los haberes remunerativos, por eso la diferencia es exactamente ${pct(alic)} de ${fmt(hit.total)}. No tiene que ver con un tope desactualizado.`,
        recomendacion: `Si por acuerdo/convenio ese concepto lleva aporte a ${esc(i.aporte)}, el descuento está bien: usá <strong>"Continuar igual"</strong>. <strong>No uses "Ajustar valores"</strong>, porque bajaría el aporte a ${fmt(i.esperado)}.`,
      }
    }
  }

  // 2) Topes: el liquidador no topeó, o topeó con un valor distinto del vigente.
  if (alic > 0 && tope) {
    if (base < bruto && coincide(bruto * alic, informado)) {
      return {
        tipo: 'real',
        titulo: 'El liquidador no aplicó el tope',
        texto: `El bruto (${fmt(bruto)}) supera el tope vigente (${fmt(tope)}), pero el aporte informado equivale a ${pct(alic)} del bruto completo, sin topear.`,
        recomendacion: 'Es una diferencia real: corregila en el liquidador de origen o con <strong>"Ajustar valores"</strong>.',
      }
    }
    if (baseImplicita < bruto - 0.5) {
      return {
        tipo: 'real',
        titulo: 'Posible tope desactualizado',
        texto: `El aporte informado equivale a ${pct(alic)} de ${fmt(baseImplicita)}, menos que el bruto (${fmt(bruto)}). Eso indica que el liquidador topeó con un valor cercano a ${fmt(baseImplicita)}, distinto del tope vigente (${fmt(tope)}).`,
        recomendacion: 'Actualizá el tope en el liquidador de origen o usá <strong>"Ajustar valores"</strong>.',
      }
    }
  }

  return {
    tipo: 'revisar',
    titulo: 'Sin explicación automática',
    texto: `El aporte informado equivale a ${pct(alic)} de ${fmt(baseImplicita)}, y el control esperaba una base de ${fmt(base)} (diferencia de base: ${fmt(baseImplicita - base)}). No coincide con ningún no remunerativo del recibo ni con el tope vigente.`,
    recomendacion: 'Revisá la liquidación individual con la lupa antes de decidir entre "Ajustar valores" y "Continuar igual".',
  }
}

const ESTILOS = `
.md3-scrim{position:fixed;inset:0;z-index:100000;background:rgba(0,0,0,.32);display:flex;align-items:center;justify-content:center;padding:16px;opacity:0;transition:opacity .15s ease;}
.md3-scrim.md3-abierto{opacity:1;}
.md3-dialog{--md-primary:#5a5fe0;--md-on-primary:#fff;--md-surface:#f7f5fc;--md-on-surface:#1c1b1f;--md-on-surface-var:#49454f;--md-outline-var:#cac4d0;
  font-family:inherit;background:var(--md-surface);color:var(--md-on-surface);border-radius:28px;box-shadow:0 8px 12px 6px rgba(0,0,0,.15),0 4px 4px rgba(0,0,0,.3);
  width:100%;max-width:600px;max-height:calc(100vh - 32px);display:flex;flex-direction:column;transform:scale(.92);transition:transform .2s cubic-bezier(.2,0,0,1);text-align:left;}
.md3-scrim.md3-abierto .md3-dialog{transform:scale(1);}
.md3-head{padding:24px 24px 0;display:flex;flex-direction:column;align-items:center;gap:16px;}
.md3-head i{font-size:24px;color:var(--md-primary);}
.md3-head h2{margin:0;font-size:24px;line-height:32px;font-weight:400;text-align:center;color:var(--md-on-surface);}
.md3-sub{margin:0;font-size:14px;line-height:20px;color:var(--md-on-surface-var);text-align:center;}
.md3-body{padding:16px 24px;overflow:auto;font-size:14px;line-height:20px;color:var(--md-on-surface-var);}
.md3-chip{display:inline-flex;align-items:center;gap:6px;height:32px;padding:0 12px;border-radius:8px;font-size:14px;font-weight:500;margin-bottom:12px;}
.md3-chip.falso_positivo{background:#c4eed0;color:#072711;}
.md3-chip.real{background:#ffdad6;color:#410002;}
.md3-chip.revisar{background:#ffe08a;color:#261a00;}
.md3-tabla{width:100%;border-collapse:collapse;margin:4px 0 16px;font-size:13px;}
.md3-tabla td{padding:6px 4px;border-bottom:1px solid var(--md-outline-var);}
.md3-tabla td:last-child{text-align:right;font-variant-numeric:tabular-nums;color:var(--md-on-surface);white-space:nowrap;}
.md3-tabla tr.md3-total td{font-weight:600;color:var(--md-on-surface);}
.md3-tabla tr.md3-dif td:last-child{color:#ba1a1a;font-weight:600;}
.md3-titulo-sec{font-size:14px;font-weight:500;color:var(--md-on-surface);margin:0 0 4px;}
.md3-reco{margin-top:12px;padding:12px 16px;border-radius:12px;background:rgba(90,95,224,.10);color:var(--md-on-surface);}
.md3-acciones{padding:8px 24px 24px;display:flex;justify-content:flex-end;gap:8px;}
.md3-btn{height:40px;padding:0 24px;border-radius:20px;border:0;font-size:14px;font-weight:500;letter-spacing:.1px;cursor:pointer;display:inline-flex;align-items:center;gap:8px;text-decoration:none;}
.md3-btn-text{background:transparent;color:var(--md-primary);padding:0 12px;}
.md3-btn-text:hover{background:rgba(90,95,224,.08);}
.md3-btn-filled{background:var(--md-primary);color:var(--md-on-primary);}
.md3-btn-filled:hover{box-shadow:0 1px 2px rgba(0,0,0,.3),0 1px 3px 1px rgba(0,0,0,.15);color:var(--md-on-primary);}
.md3-btn:focus-visible{outline:3px solid var(--md-primary);outline-offset:2px;}
`

const asegurarEstilos = () => {
  if (document.getElementById('md3-dialog-estilos')) return
  const st = document.createElement('style')
  st.id = 'md3-dialog-estilos'
  st.textContent = ESTILOS
  document.head.appendChild(st)
}

// item: fila del control; urlLiquidacion: link a la liquidación individual (opcional).
export const abrirExplicacionAporte = (item, urlLiquidacion = null) => {
  asegurarEstilos()
  const d = diagnosticarDiferenciaAporte(item)
  const alic = Number(item.alicuota) || 0
  const bruto = Number(item.bruto) || 0
  const brutoSac = Number(item.bruto_sac) || 0
  const nr = Array.isArray(item.no_remunerativos) ? item.no_remunerativos : []
  const icono = { falso_positivo: 'ri-checkbox-circle-line', real: 'ri-error-warning-line', revisar: 'ri-question-line' }[d.tipo]

  const filaTope = item.tope
    ? (brutoSac > 0
      ? `<tr><td>Tope mensual / tope SAC (½)</td><td>${fmt(item.tope)} / ${fmt(item.tope / 2)}</td></tr>`
      : `<tr><td>Tope vigente</td><td>${fmt(item.tope)}</td></tr>`)
    : '<tr><td>Tope vigente</td><td>sin tope cargado</td></tr>'

  const tablaNr = nr.length
    ? `<p class="md3-titulo-sec">No remunerativos del recibo (no entran en la base del control)</p>
       <table class="md3-tabla">${nr.map(n => `<tr><td>${esc(n.concepto)} – ${esc(n.descripcion || '—')}</td><td>${fmt(n.importe)}</td></tr>`).join('')}</table>`
    : ''

  const scrim = document.createElement('div')
  scrim.className = 'md3-scrim'
  scrim.innerHTML = `
    <div class="md3-dialog" role="dialog" aria-modal="true" aria-labelledby="md3-titulo">
      <div class="md3-head">
        <i class="ri-information-line" aria-hidden="true"></i>
        <h2 id="md3-titulo">${esc(item.aporte)} · ${esc(item.tipo_liq || '—')}</h2>
        <p class="md3-sub">Legajo ${esc(item.legajo)} — ${esc(item.nombre || '')}${item.concepto ? ` · Concepto ${esc(item.concepto)}` : ''}</p>
      </div>
      <div class="md3-body">
        <span class="md3-chip ${d.tipo}"><i class="${icono}" aria-hidden="true"></i>${esc(d.titulo)}</span>
        <p class="md3-titulo-sec">Cálculo del control</p>
        <table class="md3-tabla">
          <tr><td>Bruto remunerativo</td><td>${fmt(bruto)}</td></tr>
          ${brutoSac > 0 ? `<tr><td>&nbsp;&nbsp;de ese bruto, SAC</td><td>${fmt(brutoSac)}</td></tr>` : ''}
          ${filaTope}
          <tr><td>Base del control (mín. entre bruto y tope)</td><td>${fmt(item.base)}</td></tr>
          <tr><td>Alícuota</td><td>${pct(alic)}</td></tr>
          <tr class="md3-total"><td>Esperado (base × alícuota)</td><td>${fmt(item.esperado)}</td></tr>
          <tr class="md3-total"><td>Informado por el liquidador</td><td>${fmt(item.informado)}</td></tr>
          <tr class="md3-dif"><td>Diferencia</td><td>${fmt(item.diferencia)}</td></tr>
          ${alic > 0 ? `<tr><td>Base que usó el liquidador (informado ÷ alícuota)</td><td>${fmt(Number(item.informado) / alic)}</td></tr>` : ''}
        </table>
        ${tablaNr}
        <p class="md3-titulo-sec">Explicación</p>
        <p style="margin:0;">${d.texto}</p>
        <div class="md3-reco"><strong>Qué hacer:</strong> ${d.recomendacion}</div>
      </div>
      <div class="md3-acciones">
        ${urlLiquidacion ? `<a class="md3-btn md3-btn-text" href="${esc(urlLiquidacion)}" target="_blank" rel="noopener"><i class="ri-search-line"></i>Ver liquidación</a>` : ''}
        <button type="button" class="md3-btn md3-btn-filled" data-md3-cerrar>Entendido</button>
      </div>
    </div>`

  const previo = document.activeElement
  const cerrar = () => {
    document.removeEventListener('keydown', teclas, true)
    scrim.classList.remove('md3-abierto')
    setTimeout(() => scrim.remove(), 150)
    previo?.focus?.()
  }
  // En captura sobre document: el Esc/Tab no llega al handler de SweetAlert (que cerraría el diálogo de abajo).
  const teclas = (e) => {
    if (e.key === 'Escape') { e.preventDefault(); e.stopImmediatePropagation(); cerrar() }
    if (e.key === 'Tab') {
      e.stopImmediatePropagation()
      const foco = [...scrim.querySelectorAll('a[href],button')]
      const i = foco.indexOf(document.activeElement)
      e.preventDefault()
      foco[(i + (e.shiftKey ? -1 : 1) + foco.length) % foco.length]?.focus()
    }
  }
  scrim.addEventListener('click', (e) => { if (e.target === scrim) cerrar() })
  scrim.querySelector('[data-md3-cerrar]').addEventListener('click', cerrar)
  document.addEventListener('keydown', teclas, true)

  document.body.appendChild(scrim)
  requestAnimationFrame(() => scrim.classList.add('md3-abierto'))
  scrim.querySelector('[data-md3-cerrar]').focus()
}
