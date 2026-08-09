<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import axios from 'axios'
import { router } from '@inertiajs/vue3'
import Swal from 'sweetalert2'
import * as XLSX from 'xlsx'
import { avisarAjustesTipos } from '@/utils/avisarAjustesTipos'

const props = defineProps({
  empresas: Array,
  periodos: Array,
  emisiones: Array,
  ajustesTipos: { type: Array, default: () => [] },
  // Períodos (YYYYMM) que tienen un tope SIPA cargado en lsd_topes.
  topesPeriodos: { type: Array, default: () => [] },
})

onMounted(() => avisarAjustesTipos(props.ajustesTipos))

const tiposLiquidacion = [
  { id: 1, nombre: 'Mes' },
  { id: 2, nombre: 'Quincena' },
  { id: 3, nombre: 'Días' },
  { id: 4, nombre: 'Horas' },
]

// Filtro de liquidaciones a incluir en el TXT (sue090s.tipoliq) — distinto de
// tiposLiquidacion, que es el código M/Q/D/H del Reg 01. '(Todas)' = TXT global del mes.
const tiposLiqFiltro = [
  { value: 'todas', nombre: '(Todas)' },
  { value: '1', nombre: 'Normal' },
  { value: '4', nombre: 'SAC' },
  { value: '5', nombre: 'Liq. Final' },
]

const getTipoLiquidacionNombre = (tipoliq) => {
  const tipos = {
    1: 'Normal',
    2: '1er Quincena',
    3: '2da Quincena',
    4: 'SAC',
    5: 'Liq. Final',
    6: 'DIF.HAB.',
  }
  return tipos[tipoliq] || 'Desconocido'
}

// Nombre del filtro de tipoliq persistido en la emisión ('todas', '1', '4', '5';
// null en emisiones previas al filtro).
const getTipoLiqFiltroNombre = (valor) => {
  if (valor === null || valor === undefined || valor === '') return '—'
  return valor === 'todas' ? 'Todas' : getTipoLiquidacionNombre(Number(valor))
}

const formatPeriodo = (periodo) => {
  if (!periodo || periodo.length < 6) return periodo
  const anio = periodo.substring(0, 4)
  const mes = periodo.substring(4, 6)
  return `${anio}/${mes}`
}

const getNombreEmpresa = (idEmpresa) => {
  const empresa = props.empresas.find(e => e.id === idEmpresa)
  return empresa ? empresa.detalle : 'Desconocida'
}

const cargando = ref(false)

// Fecha de hoy en formato YYYY-MM-DD (zona local, para el input type="date")
const hoyISO = () => {
  const d = new Date()
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
}

const formulario = reactive({
  id_empresa: '',
  // Período más nuevo (periodos llega ordenado desc desde el backend)
  periodo_id: props.periodos?.[0]?.periodo ?? '',
  identificador_envio: 'SJ',          // SJ — Liquidación de Sueldos + DJ F931 (normal)
  tipos_liq: 'todas',                 // filtro de tipoliq: (Todas) = TXT global del mes
  tipo_liquidacion: 1,                // 1 - Mes
  fecha_pago: hoyISO(),               // fecha de hoy
  observaciones: '',
})

const esRectificativa = computed(() => formulario.identificador_envio === 'RE')

// Avisamos si NO hay un tope SIPA cargado para el propio período seleccionado (coincidencia
// exacta de periodo_desde). Aunque el backend haga fallback al tope de un mes anterior
// (LsdTope::vigenteParaPeriodo usa el más reciente <= período), ese valor puede estar
// desactualizado — por eso el aviso salta cuando falta el tope del mes en curso.
const topeSipaVigente = computed(() => {
  const periodo = String(formulario.periodo_id ?? '')
  if (!periodo) return true // sin período no mostramos el aviso
  return (props.topesPeriodos ?? []).some(p => String(p) === periodo)
})

// Flags "ignorar_*" acumulados en la cadena de reintentos de los pre-checks: si el usuario ya
// pasó un modal con "Continuar/Ignorar", los siguientes reintentos conservan esa decisión.
// Un submit sin opts (botón Generar, o regenerar tras ajustar) arranca limpio y re-chequea todo.
let ignorarFlags = {}

const generarEmision = async (opts = {}) => {
  const faltanCamposSJ = !esRectificativa.value && (!formulario.tipo_liquidacion || !formulario.fecha_pago)
  if (!formulario.id_empresa || !formulario.periodo_id || faltanCamposSJ) {
    Swal.fire({
      icon: 'warning',
      title: 'Faltan datos',
      text: 'Por favor completa todos los campos requeridos.',
      confirmButtonText: 'Entendido',
    })
    return
  }

  ignorarFlags = Object.keys(opts).length ? { ...ignorarFlags, ...opts } : {}

  cargando.value = true
  try {
    const response = await axios.post(route('lsd.generar.emision'), { ...formulario, ...ignorarFlags })

    if (response.data.success) {
      if (response.data.download_url) {
        window.open(response.data.download_url, '_blank')
      }
      router.reload({ only: ['emisiones'] })

      const excluidos = response.data.legajos_excluidos
      const sacAuto = response.data.sac_autocompletado
      if (Array.isArray(excluidos) && excluidos.length) {
        // Se generó ignorando legajos con datos SICOSS incompletos: avisar cuáles quedaron afuera.
        Swal.fire({
          icon: 'warning',
          title: 'Emisión generada (con legajos ignorados)',
          html: `Se generó el archivo <b>excluyendo ${excluidos.length} legajo(s)</b> con datos SICOSS incompletos:<br><br>` +
                `<span style="font-family:monospace;">${escapeHtml(excluidos.join(', '))}</span><br><br>` +
                `Esos legajos <b>no se informan</b> en este LSD. Completá sus datos SICOSS y regenerá si corresponde.`,
          confirmButtonText: 'Entendido',
        })
      } else if (Array.isArray(sacAuto) && sacAuto.length) {
        // SAC sin días completado con 30 para que ARCA lo cuente en BI 1/4/5.
        mostrarAvisoSacAutocompletado(sacAuto)
      } else {
        Swal.fire({
          icon: 'success',
          title: 'Emisión generada',
          text: 'La emisión se generó exitosamente.',
          timer: 2500,
          showConfirmButton: false,
        })
      }
    }
  } catch (error) {
    const data = error.response?.data
    if (data?.tipo_error === 'conceptos_huerfanos' && Array.isArray(data?.huerfanos)) {
      mostrarErrorHuerfanos(data.message, data.huerfanos)
    } else if (data?.tipo_error === 'conceptos_sin_arca' && Array.isArray(data?.sin_arca)) {
      mostrarErrorSinArca(data.message, data.sin_arca)
    } else if (data?.tipo_error === 'conceptos_sin_parametrizacion' && Array.isArray(data?.sin_parametrizacion)) {
      mostrarErrorSinParametrizacion(data.message, data.sin_parametrizacion)
    } else if (data?.tipo_error === 'datos_inconsistentes' && Array.isArray(data?.inconsistencias)) {
      mostrarErrorInconsistencias(data.message, data.inconsistencias)
    } else if (data?.tipo_error === 'diferencias_aportes' && Array.isArray(data?.diferencias)) {
      // Control bloqueante POR LIQUIDACIÓN (Normal/SAC/Final por separado): el TXT no se generó.
      mostrarAdvertenciaAportes(data.diferencias)
    } else if (data?.tipo_error === 'activos_sin_liquidacion' && Array.isArray(data?.activos_sin_liquidacion)) {
      // Diálogo para elegir qué activos sin liquidación se incluyen (con bases en 0).
      mostrarActivosSinLiquidacion(data.activos_sin_liquidacion)
    } else {
      const msg = data?.message || error.message || 'Error desconocido'
      Swal.fire({
        icon: 'error',
        title: 'No se pudo generar el archivo',
        html: `<pre style="text-align:left;white-space:pre-wrap;word-break:break-all;font-size:12px;max-height:400px;overflow:auto;margin:0;">${escapeHtml(msg)}</pre>`,
        width: 720,
        confirmButtonText: 'Cerrar',
      })
    }
  } finally {
    cargando.value = false
  }
}

const formatMoney = (n) => new Intl.NumberFormat('es-AR', {
  style: 'currency',
  currency: 'ARS',
  minimumFractionDigits: 2,
}).format(n)

const exportarHuerfanosXLSX = (huerfanos) => {
  const data = huerfanos.map(h => ({
    'Código': h.concepto,
    'Descripción': h.descripcion || '',
    'Veces usado': h.veces,
    'Legajos afectados': h.legajos,
    'Total importe': h.total,
  }))

  const ws = XLSX.utils.json_to_sheet(data)

  // Anchos de columnas (A=código, B=descripción, C=veces, D=legajos, E=total)
  ws['!cols'] = [
    { wch: 10 },
    { wch: 40 },
    { wch: 14 },
    { wch: 18 },
    { wch: 18 },
  ]

  // Formato moneda en la columna E (Total importe)
  const range = XLSX.utils.decode_range(ws['!ref'])
  for (let row = range.s.r + 1; row <= range.e.r; row++) {
    const ref = XLSX.utils.encode_cell({ c: 4, r: row })
    if (ws[ref]) {
      ws[ref].t = 'n'
      ws[ref].z = '"$"#,##0.00'
    }
  }

  const wb = XLSX.utils.book_new()
  XLSX.utils.book_append_sheet(wb, ws, 'Conceptos huérfanos')

  const fecha = new Date().toISOString().slice(0, 10)
  XLSX.writeFile(wb, `conceptos_huerfanos_${fecha}.xlsx`)
}

const exportarSinArcaXLSX = (conceptos) => {
  const data = conceptos.map(h => ({
    'Código': h.concepto,
    'Descripción': h.descripcion || '',
    'Veces usado': h.veces,
    'Legajos afectados': h.legajos,
    'Total importe': h.total,
  }))

  const ws = XLSX.utils.json_to_sheet(data)
  ws['!cols'] = [{ wch: 10 }, { wch: 40 }, { wch: 14 }, { wch: 18 }, { wch: 18 }]

  const range = XLSX.utils.decode_range(ws['!ref'])
  for (let row = range.s.r + 1; row <= range.e.r; row++) {
    const ref = XLSX.utils.encode_cell({ c: 4, r: row })
    if (ws[ref]) {
      ws[ref].t = 'n'
      ws[ref].z = '"$"#,##0.00'
    }
  }

  const wb = XLSX.utils.book_new()
  XLSX.utils.book_append_sheet(wb, ws, 'Conceptos sin ARCA')

  const fecha = new Date().toISOString().slice(0, 10)
  XLSX.writeFile(wb, `conceptos_sin_arca_${fecha}.xlsx`)
}

const exportarInconsistenciasXLSX = (items) => {
  const fmtFecha = (d) => {
    if (!d) return ''
    const [y, m, day] = String(d).slice(0, 10).split('-')
    return (y && m && day) ? `${day}/${m}/${y}` : String(d)
  }
  const data = items.map(i => ({
    'Legajo': i.legajo,
    'CUIL': i.cuil,
    'Empleado': i.nombre || '',
    'Alta': fmtFecha(i.alta),
    'Baja': fmtFecha(i.baja),
    'Campo': i.campo,
    'Valor': i.valor,
    'Esperado': i.esperado,
    'Problema': i.problema,
  }))

  const ws = XLSX.utils.json_to_sheet(data)
  ws['!cols'] = [
    { wch: 10 },
    { wch: 14 },
    { wch: 30 },
    { wch: 12 },
    { wch: 12 },
    { wch: 22 },
    { wch: 40 },
    { wch: 28 },
    { wch: 60 },
  ]

  const wb = XLSX.utils.book_new()
  XLSX.utils.book_append_sheet(wb, ws, 'Inconsistencias SICOSS')

  const fecha = new Date().toISOString().slice(0, 10)
  XLSX.writeFile(wb, `inconsistencias_sicoss_${fecha}.xlsx`)
}

// ----- Aviso NO bloqueante: diferencias de aportes (Jubilación/PAMI/OS) por tope desactualizado -----
const exportarAportesXLSX = (items) => {
  const data = items.map(i => ({
    'Legajo': i.legajo,
    'CUIL': i.cuil,
    'Empleado': i.nombre || '',
    'Tipo liq.': i.tipo_liq || '',
    'Aporte': i.aporte,
    'Alícuota': `${(Number(i.alicuota) * 100).toFixed(2)}%`,
    'Bruto': Number(i.bruto),
    'Base (min. bruto/tope)': Number(i.base),
    'Esperado': Number(i.esperado),
    'Informado': Number(i.informado),
    'Diferencia': Number(i.diferencia),
    'Problema': i.sin_haberes ? 'Aporte sin haberes en la liquidación (bruto $0)' : '',
  }))

  const ws = XLSX.utils.json_to_sheet(data)
  ws['!cols'] = [{ wch: 10 }, { wch: 14 }, { wch: 28 }, { wch: 12 }, { wch: 16 }, { wch: 10 }, { wch: 16 }, { wch: 18 }, { wch: 16 }, { wch: 16 }, { wch: 14 }, { wch: 40 }]

  // Formato moneda en columnas Bruto..Diferencia (índices 6..10)
  const range = XLSX.utils.decode_range(ws['!ref'])
  for (let row = range.s.r + 1; row <= range.e.r; row++) {
    for (const c of [6, 7, 8, 9, 10]) {
      const ref = XLSX.utils.encode_cell({ c, r: row })
      if (ws[ref]) { ws[ref].t = 'n'; ws[ref].z = '"$"#,##0.00' }
    }
  }

  const wb = XLSX.utils.book_new()
  XLSX.utils.book_append_sheet(wb, ws, 'Diferencias aportes')
  const fecha = new Date().toISOString().slice(0, 10)
  XLSX.writeFile(wb, `diferencias_aportes_${fecha}.xlsx`)
}

const ajustarAportes = async (items) => {
  Swal.fire({ title: 'Ajustando valores…', didOpen: () => Swal.showLoading(), allowOutsideClick: false })
  try {
    const response = await axios.post(route('lsd.ajustar.aportes'), {
      id_empresa: formulario.id_empresa,
      periodo_id: formulario.periodo_id,
      tipos_liq: formulario.tipos_liq,
    })
    const ajustados = response.data?.ajustados ?? 0
    await Swal.fire({
      icon: 'success',
      title: 'Valores ajustados',
      html: `Se corrigieron <strong>${ajustados}</strong> aportes en la liquidación.<br>Generando nuevamente el archivo con los valores corregidos…`,
      timer: 2200,
      showConfirmButton: false,
    })
    // Regenerar el .txt con los datos ya corregidos.
    generarEmision()
  } catch (error) {
    const msg = error.response?.data?.message || error.message || 'Error desconocido'
    Swal.fire({ icon: 'error', title: 'No se pudieron ajustar', text: msg, confirmButtonText: 'Cerrar' })
  }
}

const mostrarAdvertenciaAportes = (items) => {
  const ordenados = [...items]

  // Casos "bruto $0": aporte descontado en una liquidación sin haberes — se resaltan para revisión.
  const sinHaberes = ordenados.filter(i => i.sin_haberes)

  const filas = ordenados.map(i => `
    <tr${i.sin_haberes ? ' style="background:#fff3cd;"' : ''}>
      <td style="padding:4px 8px;border:1px solid #dee2e6;font-family:monospace;">${escapeHtml(i.legajo)}</td>
      <td style="padding:4px 8px;border:1px solid #dee2e6;font-family:monospace;">${escapeHtml(i.cuil)}</td>
      <td style="padding:4px 8px;border:1px solid #dee2e6;">${escapeHtml(i.nombre || '—')}</td>
      <td style="padding:4px 8px;border:1px solid #dee2e6;white-space:nowrap;">${escapeHtml(i.tipo_liq || '—')}${i.sin_haberes ? ' <span title="Aporte descontado en una liquidación sin haberes (bruto $0)">⚠️</span>' : ''}</td>
      <td style="padding:4px 8px;border:1px solid #dee2e6;">${escapeHtml(i.aporte)}</td>
      <td style="padding:4px 8px;border:1px solid #dee2e6;text-align:right;">${escapeHtml(formatMoney(i.bruto))}</td>
      <td style="padding:4px 8px;border:1px solid #dee2e6;text-align:right;">${escapeHtml(formatMoney(i.esperado))}</td>
      <td style="padding:4px 8px;border:1px solid #dee2e6;text-align:right;">${escapeHtml(formatMoney(i.informado))}</td>
      <td style="padding:4px 8px;border:1px solid #dee2e6;text-align:right;color:#dc3545;font-weight:600;">${escapeHtml(formatMoney(i.diferencia))}</td>
      <td style="padding:4px 8px;border:1px solid #dee2e6;text-align:center;">
        ${i.legajo_id
          ? `<a href="${route('liquidacion.individual.index', { legajo_id: i.legajo_id, periodo: formulario.periodo_id, ...(i.tipoliq != null ? { tipoliq: i.tipoliq } : {}) })}"
                class="lupa-concepto"
                target="_blank"
                rel="noopener"
                title="Ver la liquidación individual del legajo ${escapeHtml(i.legajo)}">
               <i class="ri-search-line"></i>
             </a>`
          : '—'}
      </td>
    </tr>
  `).join('')

  const html = `
    <style>
      .lupa-concepto{display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:50%;border:1px solid var(--bs-secondary, #6c757d);color:var(--bs-secondary, #6c757d);background:transparent;text-decoration:none;transition:all .15s ease;}
      .lupa-concepto:hover,.lupa-concepto:focus{background:var(--bs-primary, #696cff);border-color:var(--bs-primary, #696cff);color:#fff;outline:none;}
    </style>
    <div style="text-align:left;font-size:14px;">
      <p style="margin-bottom:8px;">El archivo <strong>todavía no se generó</strong>. Se detectaron diferencias entre el aporte descontado y <strong>base × alícuota</strong> (base = mín. entre bruto y tope vigente), controladas <strong>por liquidación</strong> (Normal, SAC y Liq. Final por separado). Suele deberse a un <strong>tope desactualizado en el liquidador de origen</strong>.</p>
      ${sinHaberes.length ? `
      <p style="margin-bottom:8px;padding:6px 10px;background:#fff3cd;border:1px solid #ffe69c;border-radius:6px;">
        ⚠️ <strong>${sinHaberes.length} caso(s)</strong> con aporte descontado en una liquidación <strong>sin haberes (bruto $0)</strong> — resaltados en amarillo.
        Revisá con la lupa si los haberes quedaron en otra liquidación <strong>antes de "Ajustar valores"</strong> (el ajuste llevaría esos aportes a $0).
      </p>` : ''}
      <div style="max-height:300px;overflow:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:12px;">
          <thead style="position:sticky;top:0;background:#f8f9fa;">
            <tr>
              <th style="padding:6px 8px;border:1px solid #dee2e6;text-align:left;">Legajo</th>
              <th style="padding:6px 8px;border:1px solid #dee2e6;text-align:left;">CUIL</th>
              <th style="padding:6px 8px;border:1px solid #dee2e6;text-align:left;">Empleado</th>
              <th style="padding:6px 8px;border:1px solid #dee2e6;text-align:left;">Tipo liq.</th>
              <th style="padding:6px 8px;border:1px solid #dee2e6;text-align:left;">Aporte</th>
              <th style="padding:6px 8px;border:1px solid #dee2e6;text-align:right;">Bruto</th>
              <th style="padding:6px 8px;border:1px solid #dee2e6;text-align:right;">Esperado</th>
              <th style="padding:6px 8px;border:1px solid #dee2e6;text-align:right;">Informado</th>
              <th style="padding:6px 8px;border:1px solid #dee2e6;text-align:right;">Diferencia</th>
              <th style="padding:6px 8px;border:1px solid #dee2e6;text-align:center;">Revisar</th>
            </tr>
          </thead>
          <tbody>${filas}</tbody>
        </table>
      </div>
      <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;margin-top:16px;">
        <button type="button" id="ap-exportar" class="btn btn-success">
          <i class="ri-file-excel-2-line me-1"></i> Exportar Excel
        </button>
        <div style="display:flex;gap:8px;">
          <button type="button" id="ap-cerrar" class="btn btn-outline-secondary">Cancelar</button>
          <button type="button" id="ap-ajustar" class="btn btn-warning">
            <i class="ri-refresh-line me-1"></i> Ajustar valores
          </button>
          <button type="button" id="ap-continuar" class="btn btn-primary">
            Continuar igual
          </button>
        </div>
      </div>
    </div>
  `

  Swal.fire({
    icon: 'warning',
    title: 'Diferencias en aportes (posible tope desactualizado)',
    html,
    width: 950,
    showConfirmButton: false,
    showCancelButton: false,
    didOpen: () => {
      document.getElementById('ap-exportar')?.addEventListener('click', () => exportarAportesXLSX(ordenados))
      document.getElementById('ap-cerrar')?.addEventListener('click', () => Swal.close())
      document.getElementById('ap-ajustar')?.addEventListener('click', () => ajustarAportes(ordenados))
      // Generar el TXT aceptando las diferencias tal como están (salta solo este control).
      document.getElementById('ap-continuar')?.addEventListener('click', () => {
        Swal.close()
        generarEmision({ ignorar_diferencias_aportes: true })
      })
    },
  })
}

// Aviso NO bloqueante: SAC (concepto ARCA 12xxxx) que venía sin días y se completó con 30 para
// que ARCA lo incluya en las bases topeadas (BI 1/4/5). El archivo ya se generó y descargó.
const mostrarAvisoSacAutocompletado = (items) => {
  const tipoLiqNombre = (t) => ({ 1: 'Normal', 2: '1er Quincena', 3: '2da Quincena', 4: 'SAC', 5: 'Liq. Final', 6: 'DIF.HAB.' }[t] || '—')
  const filas = items.map(i => `
    <tr>
      <td style="padding:4px 8px;border:1px solid #dee2e6;font-family:monospace;">${escapeHtml(i.legajo)}</td>
      <td style="padding:4px 8px;border:1px solid #dee2e6;font-family:monospace;">${escapeHtml(i.cuil)}</td>
      <td style="padding:4px 8px;border:1px solid #dee2e6;">${escapeHtml(i.nombre || '—')}</td>
      <td style="padding:4px 8px;border:1px solid #dee2e6;">${escapeHtml(i.concepto)} · ${escapeHtml(i.descripcion || '')}</td>
      <td style="padding:4px 8px;border:1px solid #dee2e6;text-align:center;">${escapeHtml(tipoLiqNombre(i.tipoliq))}</td>
      <td style="padding:4px 8px;border:1px solid #dee2e6;text-align:right;">${escapeHtml(formatMoney(i.importe))}</td>
    </tr>
  `).join('')

  const html = `
    <div style="text-align:left;font-size:14px;">
      <p style="margin-bottom:8px;">El archivo <strong>ya se generó y se descargó</strong>. Se detectaron conceptos de <strong>SAC sin días</strong> que ARCA excluiría de las bases imponibles topeadas (BI 1/4/5). Para que ARCA los cuente, se informaron con <strong>30 días</strong> (mes completo) en el archivo.</p>
      <p style="margin-bottom:8px;color:#664d03;">Verificá en ARCA que la BI 1/4/5 quede correcta; si el prorrateo del tope de SAC pidiera otro valor de días, avisá.</p>
      <div style="max-height:300px;overflow:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:12px;">
          <thead style="position:sticky;top:0;background:#f8f9fa;">
            <tr>
              <th style="padding:6px 8px;border:1px solid #dee2e6;text-align:left;">Legajo</th>
              <th style="padding:6px 8px;border:1px solid #dee2e6;text-align:left;">CUIL</th>
              <th style="padding:6px 8px;border:1px solid #dee2e6;text-align:left;">Empleado</th>
              <th style="padding:6px 8px;border:1px solid #dee2e6;text-align:left;">Concepto SAC</th>
              <th style="padding:6px 8px;border:1px solid #dee2e6;text-align:center;">Tipo Liq.</th>
              <th style="padding:6px 8px;border:1px solid #dee2e6;text-align:right;">Importe</th>
            </tr>
          </thead>
          <tbody>${filas}</tbody>
        </table>
      </div>
    </div>
  `

  Swal.fire({
    icon: 'info',
    title: 'Emisión generada — SAC completado con días',
    html,
    width: 900,
    confirmButtonText: 'Entendido',
  })
}

// Diálogo: empleados ACTIVOS sin liquidación en el período. Checkbox por legajo (tildado por
// defecto) para elegir si se incluyen en el TXT con todas las bases en 0; lupa para abrir el legajo.
const mostrarActivosSinLiquidacion = (items) => {
  const fmtFecha = (d) => {
    if (!d) return '—'
    const [y, m, day] = String(d).slice(0, 10).split('-')
    return (y && m && day) ? `${day}/${m}/${y}` : String(d)
  }

  const filas = items.map(i => `
    <tr>
      <td style="padding:4px 8px;border:1px solid #dee2e6;font-family:monospace;">${escapeHtml(i.legajo)}</td>
      <td style="padding:4px 8px;border:1px solid #dee2e6;font-family:monospace;">${escapeHtml(i.cuil)}</td>
      <td style="padding:4px 8px;border:1px solid #dee2e6;">${escapeHtml(i.nombre || '—')}</td>
      <td style="padding:4px 8px;border:1px solid #dee2e6;text-align:center;">${escapeHtml(fmtFecha(i.alta))}</td>
      <td style="padding:4px 8px;border:1px solid #dee2e6;text-align:center;">
        <input type="checkbox" class="chk-activo" data-legajo="${escapeHtml(i.legajo)}" checked
               style="width:18px;height:18px;cursor:pointer;">
      </td>
      <td style="padding:4px 8px;border:1px solid #dee2e6;text-align:center;">
        ${i.legajo_id
          ? `<a href="${route('legajos.edit', i.legajo_id)}" class="lupa-concepto" target="_blank" rel="noopener" title="Abrir el legajo ${escapeHtml(i.legajo)}"><i class="ri-search-line"></i></a>`
          : '—'}
      </td>
    </tr>
  `).join('')

  const html = `
    <style>
      .lupa-concepto{display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:50%;border:1px solid var(--bs-secondary, #6c757d);color:var(--bs-secondary, #6c757d);background:transparent;text-decoration:none;transition:all .15s ease;}
      .lupa-concepto:hover,.lupa-concepto:focus{background:var(--bs-primary, #696cff);border-color:var(--bs-primary, #696cff);color:#fff;outline:none;}
    </style>
    <div style="text-align:left;font-size:14px;">
      <p style="margin-bottom:8px;">Estos empleados están <strong>activos</strong> pero <strong>no tienen liquidación</strong> en el período. Los que dejes <strong>tildados</strong> se incluirán en el archivo con <strong>todas las bases en 0</strong>. Destildá los que no quieras informar.</p>
      <div style="display:flex;gap:8px;margin-bottom:8px;">
        <button type="button" id="act-todos" class="btn btn-sm btn-outline-primary rounded-pill px-3">Tildar todos</button>
        <button type="button" id="act-ninguno" class="btn btn-sm btn-outline-secondary rounded-pill px-3">Destildar todos</button>
      </div>
      <div style="max-height:320px;overflow:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:12px;">
          <thead style="position:sticky;top:0;background:#f8f9fa;">
            <tr>
              <th style="padding:6px 8px;border:1px solid #dee2e6;text-align:left;">Legajo</th>
              <th style="padding:6px 8px;border:1px solid #dee2e6;text-align:left;">CUIL</th>
              <th style="padding:6px 8px;border:1px solid #dee2e6;text-align:left;">Empleado</th>
              <th style="padding:6px 8px;border:1px solid #dee2e6;text-align:center;">Alta</th>
              <th style="padding:6px 8px;border:1px solid #dee2e6;text-align:center;">Incluir</th>
              <th style="padding:6px 8px;border:1px solid #dee2e6;text-align:center;">Ver</th>
            </tr>
          </thead>
          <tbody>${filas}</tbody>
        </table>
      </div>
    </div>
  `

  Swal.fire({
    icon: 'question',
    title: 'Empleados activos sin liquidación',
    html,
    width: 900,
    showCancelButton: true,
    reverseButtons: true,                 // Cancelar a la izquierda, primario a la derecha
    buttonsStyling: false,                // usamos clases Bootstrap/MD3 (botones ovalados)
    confirmButtonText: 'Generar con seleccionados',
    cancelButtonText: 'Cancelar',
    customClass: {
      confirmButton: 'btn btn-primary rounded-pill px-4',
      cancelButton: 'btn btn-outline-secondary rounded-pill px-4 me-2',
    },
    didOpen: () => {
      document.getElementById('act-todos')?.addEventListener('click', () => {
        document.querySelectorAll('.chk-activo').forEach(c => { c.checked = true })
      })
      document.getElementById('act-ninguno')?.addEventListener('click', () => {
        document.querySelectorAll('.chk-activo').forEach(c => { c.checked = false })
      })
    },
    preConfirm: () => {
      const incluidos = Array.from(document.querySelectorAll('.chk-activo'))
        .filter(c => c.checked)
        .map(c => c.getAttribute('data-legajo'))
      return { incluidos }
    },
  }).then((res) => {
    if (res.isConfirmed) {
      const incluidos = res.value?.incluidos ?? []
      // Regenerar informando que ya se revisó, con los legajos elegidos (posible lista vacía).
      generarEmision({ activos_revisados: true, activos_incluidos: incluidos })
    }
  })
}

// Modal (mismo estilo que el de conceptos huérfanos) para datos SICOSS que no entran en el
// formato de ancho fijo del Reg 04. La lupa abre el legajo en otra pestaña para corregirlo.
const mostrarErrorInconsistencias = (mensaje, items) => {
  const ordenados = [...items].sort((a, b) =>
    String(a.legajo).localeCompare(String(b.legajo), undefined, { numeric: true })
  )

  // Formatea una fecha "YYYY-MM-DD..." como "DD/MM/AAAA" sin parsear con Date (evita corrimiento por timezone).
  const fmtFecha = (d) => {
    if (!d) return '—'
    const [y, m, day] = String(d).slice(0, 10).split('-')
    return (y && m && day) ? `${day}/${m}/${y}` : String(d)
  }

  const filas = ordenados.map(i => `
    <tr>
      <td style="padding:4px 8px;border:1px solid #dee2e6;font-family:monospace;white-space:nowrap;min-width:72px;">${escapeHtml(i.legajo)}</td>
      <td style="padding:4px 8px;border:1px solid #dee2e6;font-family:monospace;white-space:nowrap;">${escapeHtml(i.cuil)}</td>
      <td style="padding:4px 8px;border:1px solid #dee2e6;">${escapeHtml(i.nombre || '—')}</td>
      <td style="padding:4px 8px;border:1px solid #dee2e6;white-space:nowrap;text-align:center;">${escapeHtml(fmtFecha(i.alta))}</td>
      <td style="padding:4px 8px;border:1px solid #dee2e6;white-space:nowrap;text-align:center;">${escapeHtml(fmtFecha(i.baja))}</td>
      <td style="padding:4px 8px;border:1px solid #dee2e6;">${escapeHtml(i.campo)}</td>
      <td style="padding:4px 8px;border:1px solid #dee2e6;text-align:center;font-family:monospace;color:#dc3545;font-weight:600;">${escapeHtml(i.valor)}</td>
      <td style="padding:4px 8px;border:1px solid #dee2e6;">${escapeHtml(i.esperado)}</td>
      <td style="padding:4px 8px;border:1px solid #dee2e6;text-align:center;">
        <a href="${route('legajos.edit', i.id)}#sicoss"
           class="lupa-concepto"
           target="_blank"
           rel="noopener"
           title="Editar el legajo ${escapeHtml(i.legajo)}">
          <i class="ri-search-line"></i>
        </a>
      </td>
    </tr>
  `).join('')

  const html = `
    <style>
      .lupa-concepto{display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:50%;border:1px solid var(--bs-secondary, #6c757d);color:var(--bs-secondary, #6c757d);background:transparent;text-decoration:none;transition:all .15s ease;}
      .lupa-concepto:hover,.lupa-concepto:focus{background:var(--bs-primary, #696cff);border-color:var(--bs-primary, #696cff);color:#fff;outline:none;}
    </style>
    <div style="text-align:left;font-size:14px;">
      <p style="margin-bottom:12px;">${escapeHtml(mensaje)}</p>
      <div style="max-height:300px;overflow:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:12px;">
          <thead style="position:sticky;top:0;background:#f8f9fa;">
            <tr>
              <th style="padding:6px 8px;border:1px solid #dee2e6;text-align:left;white-space:nowrap;min-width:72px;">Legajo</th>
              <th style="padding:6px 8px;border:1px solid #dee2e6;text-align:left;white-space:nowrap;">CUIL</th>
              <th style="padding:6px 8px;border:1px solid #dee2e6;text-align:left;">Empleado</th>
              <th style="padding:6px 8px;border:1px solid #dee2e6;text-align:center;">Alta</th>
              <th style="padding:6px 8px;border:1px solid #dee2e6;text-align:center;">Baja</th>
              <th style="padding:6px 8px;border:1px solid #dee2e6;text-align:left;">Campo</th>
              <th style="padding:6px 8px;border:1px solid #dee2e6;text-align:center;">Valor</th>
              <th style="padding:6px 8px;border:1px solid #dee2e6;text-align:left;">Esperado</th>
              <th style="padding:6px 8px;border:1px solid #dee2e6;text-align:center;">Acción</th>
            </tr>
          </thead>
          <tbody>${filas}</tbody>
        </table>
      </div>
      <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;margin-top:16px;">
        <button type="button" id="inc-exportar" class="btn btn-success">
          <i class="ri-file-excel-2-line me-1"></i> Exportar Excel
        </button>
        <div style="display:flex;gap:8px;align-items:center;">
          <button type="button" id="inc-cerrar" class="btn btn-outline-secondary">Cancelar</button>
          <button type="button" id="inc-reintentar" class="btn btn-warning">
            <i class="ri-refresh-line me-1"></i> Reintentar
          </button>
          <button type="button" id="inc-ignorar" class="btn btn-primary">Ignorar y continuar</button>
        </div>
      </div>
    </div>
  `

  Swal.fire({
    icon: 'error',
    title: 'Inconsistencias en datos SICOSS',
    html,
    width: 1200,
    showConfirmButton: false,
    showCancelButton: false,
    didOpen: () => {
      // El sidebar fijo de Materialize tiene un z-index alto y tapaba el popup: lo elevamos para
      // que el modal se muestre por encima de todo (incluido el menú lateral).
      const cont = Swal.getContainer()
      if (cont) cont.style.zIndex = '99999'
      document.getElementById('inc-exportar')?.addEventListener('click', () => exportarInconsistenciasXLSX(ordenados))
      document.getElementById('inc-cerrar')?.addEventListener('click', () => Swal.close())
      document.getElementById('inc-reintentar')?.addEventListener('click', () => {
        // Vuelve a leer los datos de los empleados y re-valida. Si persisten inconsistencias, este mismo
        // modal se reabre (ciclo de reintento); si ya están corregidos, continúa la generación.
        Swal.close()
        generarEmision()
      })
      document.getElementById('inc-ignorar')?.addEventListener('click', () => {
        // Continúa la generación ignorando (excluyendo) los legajos con datos SICOSS incompletos.
        Swal.close()
        generarEmision({ ignorar_inconsistencias: true })
      })
    },
  })
}

// Modal (idéntico al de huérfanos) para conceptos que existen en sue102s pero quedaron
// sin concepto_arca y sin equivalencia en conceptosarcas. La lupa abre el concepto en otra pestaña.
const mostrarErrorSinArca = (mensaje, conceptos) => {
  const ordenados = [...conceptos].sort((a, b) =>
    String(a.concepto).localeCompare(String(b.concepto), undefined, { numeric: true })
  )

  const filas = ordenados.map(h => `
    <tr>
      <td style="padding:4px 8px;border:1px solid #dee2e6;font-family:monospace;">${escapeHtml(h.concepto)}</td>
      <td style="padding:4px 8px;border:1px solid #dee2e6;">${escapeHtml(h.descripcion || '—')}</td>
      <td style="padding:4px 8px;border:1px solid #dee2e6;text-align:right;">${h.veces}</td>
      <td style="padding:4px 8px;border:1px solid #dee2e6;text-align:right;">${h.legajos}</td>
      <td style="padding:4px 8px;border:1px solid #dee2e6;text-align:right;">${escapeHtml(formatMoney(h.total))}</td>
      <td style="padding:4px 8px;border:1px solid #dee2e6;text-align:center;">
        <a href="${route('liquidacion.conceptos.edit', h.id)}"
           class="lupa-concepto"
           target="_blank"
           rel="noopener"
           title="Editar el concepto ${escapeHtml(h.concepto)}">
          <i class="ri-search-line"></i>
        </a>
      </td>
    </tr>
  `).join('')

  const html = `
    <style>
      .lupa-concepto{display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:50%;border:1px solid var(--bs-secondary, #6c757d);color:var(--bs-secondary, #6c757d);background:transparent;text-decoration:none;transition:all .15s ease;}
      .lupa-concepto:hover,.lupa-concepto:focus{background:var(--bs-primary, #696cff);border-color:var(--bs-primary, #696cff);color:#fff;outline:none;}
    </style>
    <div style="text-align:left;font-size:14px;">
      <p style="margin-bottom:12px;">${escapeHtml(mensaje)}</p>
      <div style="max-height:300px;overflow:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:12px;">
          <thead style="position:sticky;top:0;background:#f8f9fa;">
            <tr>
              <th style="padding:6px 8px;border:1px solid #dee2e6;text-align:left;">Código</th>
              <th style="padding:6px 8px;border:1px solid #dee2e6;text-align:left;">Descripción</th>
              <th style="padding:6px 8px;border:1px solid #dee2e6;text-align:right;">Veces</th>
              <th style="padding:6px 8px;border:1px solid #dee2e6;text-align:right;">Legajos</th>
              <th style="padding:6px 8px;border:1px solid #dee2e6;text-align:right;">Total importe</th>
              <th style="padding:6px 8px;border:1px solid #dee2e6;text-align:center;">Acción</th>
            </tr>
          </thead>
          <tbody>${filas}</tbody>
        </table>
      </div>
      <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;margin-top:16px;">
        <button type="button" id="sa-exportar" class="btn btn-success">
          <i class="ri-file-excel-2-line me-1"></i> Exportar Excel
        </button>
        <button type="button" id="sa-cerrar" class="btn btn-outline-secondary">Cerrar</button>
      </div>
    </div>
  `

  Swal.fire({
    icon: 'error',
    title: 'Conceptos sin código ARCA',
    html,
    width: 900,
    showConfirmButton: false,
    showCancelButton: false,
    didOpen: () => {
      document.getElementById('sa-exportar')?.addEventListener('click', () => exportarSinArcaXLSX(ordenados))
      document.getElementById('sa-cerrar')?.addEventListener('click', () => Swal.close())
    },
  })
}

// Conceptos usados en la liquidación que no están en la parametrización ARCA importada
// (conceptosarcas) de la empresa: la BI 9 puede diferir de la determinada por ARCA.
const mostrarErrorSinParametrizacion = (mensaje, conceptos) => {
  const ordenados = [...conceptos].sort((a, b) =>
    String(a.concepto).localeCompare(String(b.concepto), undefined, { numeric: true })
  )

  const filas = ordenados.map(h => `
    <tr>
      <td style="padding:4px 8px;border:1px solid #dee2e6;font-family:monospace;">${escapeHtml(h.concepto)}</td>
      <td style="padding:4px 8px;border:1px solid #dee2e6;">${escapeHtml(h.descripcion || '—')}</td>
      <td style="padding:4px 8px;border:1px solid #dee2e6;font-family:monospace;">${escapeHtml(h.concepto_arca || '—')}</td>
      <td style="padding:4px 8px;border:1px solid #dee2e6;text-align:right;">${h.veces}</td>
      <td style="padding:4px 8px;border:1px solid #dee2e6;text-align:right;">${h.legajos}</td>
      <td style="padding:4px 8px;border:1px solid #dee2e6;text-align:right;">${escapeHtml(formatMoney(h.total))}</td>
    </tr>
  `).join('')

  const html = `
    <div style="text-align:left;font-size:14px;">
      <p style="margin-bottom:12px;">${escapeHtml(mensaje)}</p>
      <div style="max-height:300px;overflow:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:12px;">
          <thead style="position:sticky;top:0;background:#f8f9fa;">
            <tr>
              <th style="padding:6px 8px;border:1px solid #dee2e6;text-align:left;">Código</th>
              <th style="padding:6px 8px;border:1px solid #dee2e6;text-align:left;">Descripción</th>
              <th style="padding:6px 8px;border:1px solid #dee2e6;text-align:left;">Cód. ARCA</th>
              <th style="padding:6px 8px;border:1px solid #dee2e6;text-align:right;">Veces</th>
              <th style="padding:6px 8px;border:1px solid #dee2e6;text-align:right;">Legajos</th>
              <th style="padding:6px 8px;border:1px solid #dee2e6;text-align:right;">Total importe</th>
            </tr>
          </thead>
          <tbody>${filas}</tbody>
        </table>
      </div>
      <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;margin-top:16px;">
        <a href="${route('arca.importar')}" target="_blank" rel="noopener" class="btn btn-warning">
          <i class="ri-download-2-line me-1"></i> Importar parametrización ARCA
        </a>
        <div style="display:flex;gap:8px;">
          <button type="button" id="sp-cerrar" class="btn btn-outline-secondary">Cancelar</button>
          <button type="button" id="sp-continuar" class="btn btn-primary">Continuar igual</button>
        </div>
      </div>
    </div>
  `

  Swal.fire({
    icon: 'warning',
    title: 'Conceptos sin parametrización ARCA',
    html,
    width: 950,
    showConfirmButton: false,
    showCancelButton: false,
    didOpen: () => {
      document.getElementById('sp-cerrar')?.addEventListener('click', () => Swal.close())
      // Generar igual, asumiendo el riesgo de que la BI 9 difiera de la determinada por ARCA.
      document.getElementById('sp-continuar')?.addEventListener('click', () => {
        Swal.close()
        generarEmision({ ignorar_sin_parametrizacion: true })
      })
    },
  })
}

const mostrarErrorHuerfanos = (mensaje, huerfanos) => {
  // Ordenar por código (numérico-aware: 5 antes que 100)
  const ordenados = [...huerfanos].sort((a, b) =>
    String(a.concepto).localeCompare(String(b.concepto), undefined, { numeric: true })
  )

  const filas = ordenados.map(h => `
    <tr>
      <td style="padding:4px 8px;border:1px solid #dee2e6;font-family:monospace;">${escapeHtml(h.concepto)}</td>
      <td style="padding:4px 8px;border:1px solid #dee2e6;">${escapeHtml(h.descripcion || '—')}</td>
      <td style="padding:4px 8px;border:1px solid #dee2e6;text-align:right;">${h.veces}</td>
      <td style="padding:4px 8px;border:1px solid #dee2e6;text-align:right;">${h.legajos}</td>
      <td style="padding:4px 8px;border:1px solid #dee2e6;text-align:right;">${escapeHtml(formatMoney(h.total))}</td>
      <td style="padding:4px 8px;border:1px solid #dee2e6;text-align:center;">
        <a href="${route('liquidacion.conceptos.create', { codigo: h.concepto })}"
           class="lupa-concepto"
           target="_blank"
           rel="noopener"
           title="Parametrizar el concepto ${escapeHtml(h.concepto)}">
          <i class="ri-search-line"></i>
        </a>
      </td>
    </tr>
  `).join('')

  const html = `
    <style>
      .lupa-concepto{display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:50%;border:1px solid var(--bs-secondary, #6c757d);color:var(--bs-secondary, #6c757d);background:transparent;text-decoration:none;transition:all .15s ease;}
      .lupa-concepto:hover,.lupa-concepto:focus{background:var(--bs-primary, #696cff);border-color:var(--bs-primary, #696cff);color:#fff;outline:none;}
    </style>
    <div style="text-align:left;font-size:14px;">
      <p style="margin-bottom:12px;">${escapeHtml(mensaje)}</p>
      <div style="max-height:300px;overflow:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:12px;">
          <thead style="position:sticky;top:0;background:#f8f9fa;">
            <tr>
              <th style="padding:6px 8px;border:1px solid #dee2e6;text-align:left;">Código</th>
              <th style="padding:6px 8px;border:1px solid #dee2e6;text-align:left;">Descripción</th>
              <th style="padding:6px 8px;border:1px solid #dee2e6;text-align:right;">Veces</th>
              <th style="padding:6px 8px;border:1px solid #dee2e6;text-align:right;">Legajos</th>
              <th style="padding:6px 8px;border:1px solid #dee2e6;text-align:right;">Total importe</th>
              <th style="padding:6px 8px;border:1px solid #dee2e6;text-align:center;">Acción</th>
            </tr>
          </thead>
          <tbody>${filas}</tbody>
        </table>
      </div>
      <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;margin-top:16px;">
        <button type="button" id="hf-exportar" class="btn btn-success">
          <i class="ri-file-excel-2-line me-1"></i> Exportar Excel
        </button>
        <div style="display:flex;gap:8px;">
          <button type="button" id="hf-cerrar" class="btn btn-outline-secondary">Cerrar</button>
          <button type="button" id="hf-generar" class="btn btn-info">
            <i class="ri-add-circle-line me-1"></i> Generar conceptos
          </button>
        </div>
      </div>
    </div>
  `

  Swal.fire({
    icon: 'error',
    title: 'Conceptos sin parametrizar',
    html,
    width: 900,
    showConfirmButton: false,
    showCancelButton: false,
    didOpen: () => {
      document.getElementById('hf-exportar')?.addEventListener('click', () => exportarHuerfanosXLSX(ordenados))
      document.getElementById('hf-cerrar')?.addEventListener('click', () => Swal.close())
      document.getElementById('hf-generar')?.addEventListener('click', () => generarConceptosFaltantes(ordenados))
    },
  })
}

const generarConceptosFaltantes = async (huerfanos) => {
  try {
    const response = await axios.post(route('lsd.generar.conceptos'), {
      conceptos: huerfanos.map(h => ({ concepto: h.concepto, descripcion: h.descripcion })),
    })
    const d = response.data || {}
    await Swal.fire({
      icon: 'success',
      title: 'Conceptos generados',
      html: `
        <div style="text-align:left;font-size:14px;">
          Conceptos creados: <strong>${d.creados ?? 0}</strong><br>
          Ya existían (omitidos): <strong>${d.omitidos ?? 0}</strong><br>
          Sin tipo (código fuera de los rangos): <strong>${d.sin_tipo ?? 0}</strong><br>
          Sin equivalencia ARCA: <strong>${d.sin_arca ?? 0}</strong>
        </div>`,
      confirmButtonText: 'Cerrar',
    })
  } catch (error) {
    const msg = error.response?.data?.message || error.message || 'Error desconocido'
    Swal.fire({ icon: 'error', title: 'No se pudieron generar', text: msg, confirmButtonText: 'Cerrar' })
  }
}

const escapeHtml = (str) => {
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;')
}

const formatDate = (date) => {
  if (!date) return '-'
  const d = new Date(date)
  return d.toLocaleDateString('es-AR')
}

// Extrae HH:MM directo del string ISO (sin conversión de timezone, para mostrar la hora tal cual se guardó).
const formatTime = (datetime) => {
  if (!datetime) return ''
  return String(datetime).slice(11, 16)
}

const formatNumber = (num) => {
  return new Intl.NumberFormat('es-AR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(num)
}

const getEstadoClass = (estado) => {
  const clases = {
    borrador: 'bg-secondary',
    generado: 'bg-info',
    enviado: 'bg-warning',
    confirmado: 'bg-success',
    rechazado: 'bg-danger',
  }
  return clases[estado] || 'bg-secondary'
}

const modalVisible = ref(false)
const emisionSeleccionada = ref(null)

const verDetalles = (emision) => {
  emisionSeleccionada.value = emision
  modalVisible.value = true
}

const cerrarModal = () => {
  modalVisible.value = false
  emisionSeleccionada.value = null
}

const descargarEmision = (id) => {
  window.location.href = route('lsd.emision.download', id)
}

const eliminarEmision = async (id) => {
  const confirmacion = await Swal.fire({
    icon: 'warning',
    title: '¿Eliminar esta emisión?',
    text: 'Solo se pueden eliminar emisiones en borrador. Esta acción no se puede deshacer.',
    showCancelButton: true,
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#dc3545',
  })
  if (!confirmacion.isConfirmed) return

  try {
    const response = await axios.delete(route('lsd.emision.eliminar', id))
    if (response.data.success) {
      await Swal.fire({
        icon: 'success',
        title: 'Eliminada',
        timer: 1500,
        showConfirmButton: false,
      })
      router.reload({ only: ['emisiones'] })
    }
  } catch (error) {
    Swal.fire({
      icon: 'error',
      title: 'No se pudo eliminar',
      text: error.response?.data?.message || error.message || 'Error desconocido',
      confirmButtonText: 'Cerrar',
    })
  }
}

const cambiarEstado = async (id, nuevoEstado, opts) => {
  const confirmacion = await Swal.fire({
    icon: opts.icon || 'question',
    title: opts.titulo,
    text: opts.mensaje,
    showCancelButton: true,
    confirmButtonText: opts.confirmar,
    cancelButtonText: 'Cancelar',
    confirmButtonColor: opts.color || '#0d6efd',
  })
  if (!confirmacion.isConfirmed) return

  try {
    const response = await axios.put(route('lsd.emision.estado', id), { estado: nuevoEstado })
    if (response.data.success) {
      Swal.fire({
        icon: 'success',
        title: 'Estado actualizado',
        text: response.data.message,
        timer: 1800,
        showConfirmButton: false,
      })
      router.reload({ only: ['emisiones'] })
    }
  } catch (error) {
    Swal.fire({
      icon: 'error',
      title: 'No se pudo cambiar el estado',
      text: error.response?.data?.message || error.message || 'Error desconocido',
      confirmButtonText: 'Cerrar',
    })
  }
}

const marcarEnviado = (id) => cambiarEstado(id, 'enviado', {
  icon: 'question',
  titulo: '¿Marcar como enviada a ARCA?',
  mensaje: 'Indicá que ya subiste el archivo TXT al portal LSD.',
  confirmar: 'Sí, marcar enviada',
  color: '#0d6efd',
})

const marcarConfirmado = (id) => cambiarEstado(id, 'confirmado', {
  icon: 'warning',
  titulo: '¿ARCA aceptó esta emisión?',
  mensaje: 'Una vez confirmada NO podrá modificarse ni eliminarse.',
  confirmar: 'Sí, confirmar',
  color: '#198754',
})

const marcarRechazado = (id) => cambiarEstado(id, 'rechazado', {
  icon: 'warning',
  titulo: '¿ARCA rechazó esta emisión?',
  mensaje: 'Quedará archivada como rechazada. No se podrá reabrir ni modificar (solo consulta).',
  confirmar: 'Sí, marcar rechazada',
  color: '#dc3545',
})
</script>

<template>
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="app-ecommerce">
      <!-- HEAD Y BOTONES -->
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-6 row-gap-4">
        <h4 class="mb-1">Generar Libro de Sueldo Digital</h4>
      </div>
      
      <div class="row">
        <div class="col-12">
          <div class="card">
            <!-- <div class="card-header">
              <h5 class="card-title mb-0">Generar Libro de Sueldo Digital</h5>
            </div> -->
            <div class="card-body">
              <form @submit.prevent="generarEmision" class="row g-3">
                <div v-if="!topeSipaVigente" class="col-12">
                  <div class="alert alert-warning mb-0 d-flex align-items-center" role="alert">
                    <i class="ri-error-warning-line me-2" style="font-size: 1.25rem;"></i>
                    <span>Cuidado: Los topes de los importes SIPA tal vez se encuentran desactualizados</span>
                    <a :href="route('sicoss.topes.index')"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="btn btn-sm rounded-pill btn-outline-warning waves-effect ms-3">
                      Actualizar topes SIPA
                    </a>
                  </div>
                </div>

                <!-- Con tope del período cargado: recordatorio preventivo en lugar de la advertencia -->
                <div v-else class="col-12">
                  <div class="alert alert-info mb-0 d-flex align-items-center py-2" role="alert">
                    <i class="ri-information-line me-2 flex-shrink-0" style="font-size: 1.25rem;"></i>
                    <span>Recuerde tener los topes previsionales (SIPA) actualizados</span>
                    <a :href="route('sicoss.topes.index')"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="btn btn-sm rounded-pill btn-outline-info waves-effect ms-3">
                      Ver topes SIPA
                    </a>
                  </div>
                </div>

                <div class="col-md-6">
                  <label for="empresa" class="form-label">Empresa</label>
                  <select
                    id="empresa"
                    v-model="formulario.id_empresa"
                    class="form-select"
                    required
                  >
                    <option value="" disabled="">Seleccionar empresa...</option>
                    <option
                      v-for="empresa in empresas"
                      :key="empresa.id"
                      :value="empresa.id"
                    >
                      {{ empresa.codigo }} - {{ empresa.detalle }}
                    </option>
                  </select>
                </div>

                <div class="col-md-3">
                  <label for="periodo" class="form-label">Período</label>
                  <select
                    id="periodo"
                    v-model="formulario.periodo_id"
                    class="form-select"
                    required
                  >
                    <option value="" disabled="">Seleccionar período...</option>
                    <option
                      v-for="periodo in periodos"
                      :key="periodo.periodo"
                      :value="periodo.periodo"
                    >
                      {{ formatPeriodo(periodo.periodo) }}
                    </option>
                  </select>
                </div>

                <div class="col-md-3">
                  <label for="tipos_liq" class="form-label">Tipo de liquidación</label>
                  <select
                    id="tipos_liq"
                    v-model="formulario.tipos_liq"
                    class="form-select"
                  >
                    <option
                      v-for="tipo in tiposLiqFiltro"
                      :key="tipo.value"
                      :value="tipo.value"
                    >
                      {{ tipo.nombre }}
                    </option>
                  </select>
                  <small class="text-muted">(Todas) genera el TXT global del mes</small>
                </div>

                <div class="col-md-12">
                  <label for="identificador_envio" class="form-label">Tipo de envío</label>
                  <select
                    id="identificador_envio"
                    v-model="formulario.identificador_envio"
                    class="form-select"
                    required
                  >
                    <option value="SJ">SJ — Liquidación de Sueldos + DJ F931 (normal)</option>
                    <option value="RE">RE — Rectificar solo la DJ F931</option>
                  </select>
                  <small class="text-muted">
                    En modo RE el archivo solo lleva Reg 01 + Reg 04. Se omite Tipo de Liquidación y Fecha de Pago.
                  </small>
                </div>

                <div class="col-md-6" v-if="!esRectificativa">
                  <label for="tipo_liquidacion" class="form-label">Tipo de Liquidación (Reg. 01)</label>
                  <select
                    id="tipo_liquidacion"
                    v-model="formulario.tipo_liquidacion"
                    class="form-select"
                    :required="!esRectificativa"
                    :disabled="esRectificativa"
                  >
                    <option value="" disabled="">Seleccionar tipo de liquidación...</option>
                    <option
                      v-for="tipo in tiposLiquidacion"
                      :key="tipo.id"
                      :value="tipo.id"
                    >
                      {{ tipo.id }} - {{ tipo.nombre }}
                    </option>
                  </select>
                </div>

                <div class="col-md-6" v-if="!esRectificativa">
                  <label for="fecha_pago" class="form-label">Fecha de Pago</label>
                  <input
                    id="fecha_pago"
                    v-model="formulario.fecha_pago"
                    type="date"
                    class="form-control"
                    :required="!esRectificativa"
                    :disabled="esRectificativa"
                  />
                </div>

                <div class="col-12">
                  <label for="observaciones" class="form-label">Observaciones</label>
                  <input
                    id="observaciones"
                    v-model="formulario.observaciones"
                    type="text"
                    class="form-control"
                    placeholder="Observaciones (opcional)"
                  />
                </div>

                <div class="col-12">
                  <button
                    id="btnGenerar"
                    type="submit"
                    class="btn btn-primary"
                    :disabled="cargando"
                  >
                    <span v-if="!cargando">Generar Emisión</span>
                    <span v-else>
                      <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                      Generando...
                    </span>
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>

      <!-- Últimas emisiones -->
      <div class="row mt-4">
        <div class="col-12">
          <div class="card">
            <div class="card-header">
              <h5 class="card-title mb-0">Últimas Emisiones</h5>
            </div>
            <div class="table-responsive">
              <table class="table table-striped" id="emisiones" name="emisiones">
                <thead>
                  <tr>
                    <th>Número</th>
                    <th>Empresa</th>
                    <th>Período</th>
                    <th>Tipo liq.</th>
                    <th>Fecha y Hora Emisión</th>
                    <th>Estado</th>
                    <th>Empleados</th>
                    <th>Monto Total</th>
                    <th>Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="emision in emisiones" :key="emision.id">
                    <td>{{ emision.numero_emision }}</td>
                    <td>{{ getNombreEmpresa(emision.id_empresa) }}</td>
                    <td>
                      {{ (emision.periodo) }}
                    </td>
                    <td>{{ getTipoLiqFiltroNombre(emision.tipoliq_filtro) }}</td>
                    <td>
                      {{ formatDate(emision.fecha_emision) }}
                      <small class="text-muted d-block">{{ formatTime(emision.fecha_generacion) }} hs</small>
                    </td>
                    <td>
                      <span :class="getEstadoClass(emision.estado)" class="badge">
                        {{ emision.estado }}
                      </span>
                    </td>
                    <td>{{ emision.cantidad_empleados }}</td>
                    <td>${{ formatNumber(emision.monto_total) }}</td>
                    <td>
                      <div class="d-flex gap-2 align-items-center">
                        <!-- Acciones siempre visibles -->
                        <a
                          :href="route('lsd.emision.detalle', emision.id)"
                          target="_blank"
                          class="btn btn-link text-info p-0"
                          title="Ver detalles"
                          data-bs-toggle="tooltip"
                          data-bs-placement="top"
                          style="text-decoration: none;"
                        >
                          <i class="ri-search-line" style="font-size: 1.25rem;"></i>
                        </a>
                        <button
                          type="button"
                          class="btn btn-link text-success p-0"
                          @click="descargarEmision(emision.id)"
                          title="Descargar TXT"
                          data-bs-toggle="tooltip"
                          data-bs-placement="top"
                          style="text-decoration: none;"
                        >
                          <i class="ri-download-line" style="font-size: 1.25rem;"></i>
                        </button>

                        <!-- borrador/generado: marcar enviado + eliminar -->
                        <button
                          v-if="['borrador','generado'].includes(emision.estado)"
                          type="button"
                          class="btn btn-link text-primary p-0"
                          @click="marcarEnviado(emision.id)"
                          title="Marcar como enviada a ARCA"
                          data-bs-toggle="tooltip"
                          data-bs-placement="top"
                          style="text-decoration: none;"
                        >
                          <i class="ri-send-plane-line" style="font-size: 1.25rem;"></i>
                        </button>
                        <button
                          v-if="['borrador','generado'].includes(emision.estado)"
                          type="button"
                          class="btn btn-link text-danger p-0"
                          @click="eliminarEmision(emision.id)"
                          title="Eliminar emisión"
                          data-bs-toggle="tooltip"
                          data-bs-placement="top"
                          style="text-decoration: none;"
                        >
                          <i class="ri-delete-bin-line" style="font-size: 1.25rem;"></i>
                        </button>

                        <!-- enviado: marcar confirmado o rechazado -->
                        <button
                          v-if="emision.estado === 'enviado'"
                          type="button"
                          class="btn btn-link text-success p-0"
                          @click="marcarConfirmado(emision.id)"
                          title="ARCA aceptó"
                          data-bs-toggle="tooltip"
                          data-bs-placement="top"
                          style="text-decoration: none;"
                        >
                          <i class="ri-check-double-line" style="font-size: 1.25rem;"></i>
                        </button>
                        <button
                          v-if="emision.estado === 'enviado'"
                          type="button"
                          class="btn btn-link text-danger p-0"
                          @click="marcarRechazado(emision.id)"
                          title="ARCA rechazó"
                          data-bs-toggle="tooltip"
                          data-bs-placement="top"
                          style="text-decoration: none;"
                        >
                          <i class="ri-close-circle-line" style="font-size: 1.25rem;"></i>
                        </button>

                        <!-- confirmado o rechazado: solo consulta, ícono candado -->
                        <span
                          v-if="['confirmado','rechazado'].includes(emision.estado)"
                          class="text-muted"
                          title="Solo consulta"
                          data-bs-toggle="tooltip"
                          data-bs-placement="top"
                        >
                          <i class="ri-lock-line" style="font-size: 1.25rem;"></i>
                        </span>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal para detalles de emisión -->
    <div v-if="modalVisible" class="modal fade show d-block" tabindex="-1" role="dialog" aria-labelledby="detallesModalLabel" aria-hidden="true" style="background-color: rgba(0, 0, 0, 0.5);">
      <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="detallesModalLabel">Detalles de Emisión</h5>
            <button type="button" class="btn-close" @click="cerrarModal" aria-label="Close"></button>
          </div>
          <div class="modal-body" v-if="emisionSeleccionada">
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label"><strong>Empresa:</strong></label>
                <p>{{ emisionSeleccionada.id_empresa }}</p>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label"><strong>Número de Emisión:</strong></label>
                <p>{{ emisionSeleccionada.numero_emision }}</p>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label"><strong>Período Desde:</strong></label>
                <p>{{ formatDate(emisionSeleccionada.periodo_desde) }}</p>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label"><strong>Período Hasta:</strong></label>
                <p>{{ formatDate(emisionSeleccionada.periodo_hasta) }}</p>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label"><strong>Fecha Emisión:</strong></label>
                <p>{{ formatDate(emisionSeleccionada.fecha_emision) }}</p>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label"><strong>Estado:</strong></label>
                <p>
                  <span :class="getEstadoClass(emisionSeleccionada.estado)" class="badge">
                    {{ emisionSeleccionada.estado }}
                  </span>
                </p>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label"><strong>Empleados:</strong></label>
                <p>{{ emisionSeleccionada.cantidad_empleados }}</p>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label"><strong>Monto Total:</strong></label>
                <p>${{ formatNumber(emisionSeleccionada.monto_total) }}</p>
              </div>
            </div>
            <div class="row">
              <div class="col-12 mb-3" v-if="emisionSeleccionada.observaciones">
                <label class="form-label"><strong>Observaciones:</strong></label>
                <p>{{ emisionSeleccionada.observaciones }}</p>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="cerrarModal">Cerrar</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>