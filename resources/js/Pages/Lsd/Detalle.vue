<script setup>
import { ref, computed } from 'vue'
import * as XLSX from 'xlsx'

const props = defineProps({
  emision: Object,
  empresa: Object,
  resumen: { type: Array, default: () => [] },
  detallePorCuil: { type: Object, default: () => ({}) },
  resumenLiq: { type: Object, default: () => null },
})

const busqueda = ref('')
const vista = ref('totalizador') // 'totalizador' | 'resumen'

// Legajos ignorados en la generación (puede ser null en emisiones generadas antes de esta función).
const mostrarIgnorados = ref(false)
const legajosIgnorados = computed(() => props.emision?.legajos_ignorados || [])

const resumenFiltrado = computed(() => {
  const q = busqueda.value.toLowerCase().trim()
  if (!q) return props.resumen
  return props.resumen.filter(e =>
    (e.cuil || '').toLowerCase().includes(q) ||
    (e.nombre || '').toLowerCase().includes(q) ||
    (e.legajo || '').toString().toLowerCase().includes(q) ||
    (e.convenio || '').toLowerCase().includes(q) ||
    (e.tipo_liq || '').toLowerCase().includes(q)
  )
})

const totales = computed(() => {
  return resumenFiltrado.value.reduce((acc, e) => {
    acc.rem += Number(e.remunerativos || 0)
    acc.norem += Number(e.no_remunerativos || 0)
    acc.desc += Number(e.descuentos || 0)
    acc.neto += Number(e.neto || 0)
    return acc
  }, { rem: 0, norem: 0, desc: 0, neto: 0 })
})

// ---- Ordenamiento por columna ----
const sortKey = ref('cuil')
const sortDir = ref('asc')
const numericCols = ['remunerativos', 'no_remunerativos', 'descuentos', 'neto']

const setSort = (key) => {
  if (sortKey.value === key) {
    sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortKey.value = key
    sortDir.value = 'asc'
  }
}

const sortIcon = (key) => {
  if (sortKey.value !== key) return 'ri-expand-up-down-line lsd-sort-ic text-muted'
  return (sortDir.value === 'asc' ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line') + ' lsd-sort-ic text-primary'
}

const resumenOrdenado = computed(() => {
  const arr = [...resumenFiltrado.value]
  const key = sortKey.value
  const dir = sortDir.value === 'asc' ? 1 : -1
  arr.sort((a, b) => {
    let cmp
    if (numericCols.includes(key)) {
      cmp = Number(a[key] || 0) - Number(b[key] || 0)
    } else {
      cmp = String(a[key] ?? '').localeCompare(String(b[key] ?? ''), 'es', { numeric: true, sensitivity: 'base' })
    }
    return cmp * dir
  })
  return arr
})

const formatDate = (date) => {
  if (!date) return '—'
  return new Date(date).toLocaleDateString('es-AR')
}

const formatNumber = (num) => {
  if (num === null || num === undefined || num === '') return '0,00'
  return Number(num).toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const formatPeriodo = (periodo) => {
  if (!periodo || periodo.length < 6) return periodo
  return `${periodo.substring(0, 4)}/${periodo.substring(4, 6)}`
}

const getEstadoClass = (estado) => {
  const clases = {
    borrador: 'bg-label-secondary',
    generado: 'bg-label-info',
    enviado: 'bg-label-warning',
    confirmado: 'bg-label-success',
    rechazado: 'bg-label-danger',
  }
  return clases[estado] || 'bg-label-secondary'
}

// ---- Modal de detalle por empleado ----
const empleadoSel = ref(null)

const detalleSel = computed(() => {
  if (!empleadoSel.value) return null
  return props.detallePorCuil[empleadoSel.value.cuil] || { conceptos: [], bases: null }
})

const abrirDetalle = (emp) => { empleadoSel.value = emp }
const cerrarDetalle = () => { empleadoSel.value = null }

// Filas de bases imponibles / cálculos para el modal (label + valor).
const filasBases = computed(() => {
  const b = detalleSel.value?.bases
  if (!b) return []
  return [
    { label: 'Remuneración bruta', valor: b.rem_bruta },
    { label: 'BI 1 — Aportes SIPA', valor: b.bi1 },
    { label: 'BI 2 — Contrib. SIPA + INSSJyP', valor: b.bi2 },
    { label: 'BI 3 — Contrib. FNE / AAFF / RENATRE', valor: b.bi3 },
    { label: 'BI 4 — Aportes Obra Social + FSR', valor: b.bi4 },
    { label: 'BI 5 — Aportes INSSJyP', valor: b.bi5 },
    { label: 'BI 6 — Aportes diferenciales', valor: b.bi6 },
    { label: 'BI 7 — Aportes reg. especiales', valor: b.bi7 },
    { label: 'BI 8 — Contrib. Obra Social + FSR', valor: b.bi8 },
    { label: 'BI 9 — LRT', valor: b.bi9 },
    { label: 'BI 10 — Ley 27.430', valor: b.bi10 },
    { label: 'Importe a detraer', valor: b.importe_detraer },
    { label: 'Base dif. aportes OS+FSR', valor: b.dif_aportes_os },
    { label: 'Base dif. contrib. OS+FSR', valor: b.dif_contrib_os },
    { label: 'Base dif. LRT', valor: b.dif_lrt },
    { label: 'Base dif. aporte SS (BI 1)', valor: b.dif_aporte_ss },
    { label: 'Base dif. contrib. SS (BI 2)', valor: b.dif_contrib_ss },
    { label: 'Remuneración maternidad', valor: b.rem_maternidad },
  ]
})

const tipoLabel = (t) => ({ H: 'Remunerativo', NR: 'No Remunerativo', D: 'Descuento' }[t] || (t || '—'))
const tipoClass = (t) => ({ H: 'text-success', NR: 'text-info', D: 'text-danger' }[t] || 'text-muted')

// ---- Exportar el totalizador a Excel ----
const exportarExcel = () => {
  const data = resumenOrdenado.value.map(e => ({
    'Tipo de liquidación': e.tipo_liq || '',
    'Convenio': e.convenio || '',
    'CUIL': e.cuil || '',
    'Apellido y Nombre': e.nombre || '',
    'Legajo': e.legajo ?? '',
    'Total Remunerativos': Number(e.remunerativos || 0),
    'Descuentos': Number(e.descuentos || 0),
    'Total No Remunerativos': Number(e.no_remunerativos || 0),
    'Total Neto': Number(e.neto || 0),
  }))
  // Fila de totales al pie.
  data.push({
    'Tipo de liquidación': '',
    'Convenio': '',
    'CUIL': '',
    'Apellido y Nombre': 'TOTALES',
    'Legajo': '',
    'Total Remunerativos': Number(totales.value.rem.toFixed(2)),
    'Descuentos': Number(totales.value.desc.toFixed(2)),
    'Total No Remunerativos': Number(totales.value.norem.toFixed(2)),
    'Total Neto': Number(totales.value.neto.toFixed(2)),
  })
  const ws = XLSX.utils.json_to_sheet(data)
  const wb = XLSX.utils.book_new()
  XLSX.utils.book_append_sheet(wb, ws, 'Totalizador')
  const periodo = (props.emision?.periodo ?? '').toString()
  XLSX.writeFile(wb, `totalizador_lsd_emision_${props.emision?.numero_emision ?? ''}_periodo_${periodo}.xlsx`)
}

// ---- Filtro "nuevos conceptos este mes" en el resumen de liquidación ----
// Conceptos del catálogo dados de alta en el mes del período (flag `nuevo` del backend).
const filtrarNuevos = ref(false)

const nuevosCount = computed(() =>
  (props.resumenLiq?.conceptos || []).filter(c => c.nuevo).length
)

const conceptosMostrados = computed(() => {
  const todos = props.resumenLiq?.conceptos || []
  return filtrarNuevos.value ? todos.filter(c => c.nuevo) : todos
})

// ---- Exportar el resumen de liquidación (totales por concepto) a Excel ----
const exportarResumenExcel = () => {
  const data = (props.resumenLiq?.conceptos || []).map(c => ({
    'Código': c.codigo,
    'Descripción': c.descripcion || '',
    'Importes totales': Number(c.total || 0),
    'Importe período actual': Number(c.actual || 0),
    'Importe otros períodos': Number(c.otros || 0),
  }))
  const ws = XLSX.utils.json_to_sheet(data)
  const wb = XLSX.utils.book_new()
  XLSX.utils.book_append_sheet(wb, ws, 'Resumen liquidación')
  const periodo = (props.emision?.periodo ?? '').toString()
  XLSX.writeFile(wb, `resumen_liquidacion_emision_${props.emision?.numero_emision ?? ''}_periodo_${periodo}.xlsx`)
}
</script>

<template>
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="app-ecommerce">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-6 row-gap-4">
        <div class="d-flex align-items-center gap-3">
          <button v-if="vista === 'resumen'" type="button" class="btn btn-outline-secondary btn-volver-redondo" @click="vista = 'totalizador'" title="Volver">
            <i class="ri-arrow-left-line"></i>
          </button>
          <h4 class="mb-1">Libro Sueldo Digital</h4>
        </div>
        <button v-if="vista === 'totalizador'" type="button" class="btn btn-naranja" @click="vista = 'resumen'">
          <i class="ri-file-list-3-line me-1"></i> Resumen de liquidación
        </button>
      </div>

      <!-- ===================== VISTA TOTALIZADOR ===================== -->
      <div v-if="vista === 'totalizador'">
      <!-- Encabezado de la emisión -->
      <div class="row">
        <div class="col-12">
          <div class="card mb-6">
            <div class="card-header">
              <h5 class="card-title mb-0">Datos de la emisión #{{ emision.numero_emision }}</h5>
            </div>
            <div class="card-body">
              <div class="row g-4">
                <div class="col-md-3">
                  <label class="form-label fw-bold">Empresa</label>
                  <p class="mb-0">{{ empresa?.detalle ?? '—' }}</p>
                </div>
                <div class="col-md-3">
                  <label class="form-label fw-bold">CUIT</label>
                  <p class="mb-0">{{ emision.cuit_empresa || '—' }}</p>
                </div>
                <div class="col-md-3">
                  <label class="form-label fw-bold">Periodo</label>
                  <p class="mb-0">{{ formatPeriodo(emision.periodo) }}</p>
                </div>
                <div class="col-md-3">
                  <label class="form-label fw-bold">Fecha de emisión</label>
                  <p class="mb-0">{{ formatDate(emision.fecha_emision) }}</p>
                </div>
                <div class="col-md-3">
                  <label class="form-label fw-bold">Estado</label>
                  <p class="mb-0">
                    <span :class="getEstadoClass(emision.estado)" class="badge">{{ emision.estado }}</span>
                  </p>
                </div>
                <div class="col-md-3">
                  <label class="form-label fw-bold">Cantidad de empleados</label>
                  <p class="mb-0">
                    {{ emision.cantidad_empleados }}
                    <span
                      v-if="legajosIgnorados.length"
                      class="badge rounded-pill bg-danger ms-2 lsd-pill-ignorados"
                      role="button"
                      title="Ver legajos ignorados en la generación"
                      @click="mostrarIgnorados = true"
                    >{{ legajosIgnorados.length }} ignorado{{ legajosIgnorados.length === 1 ? '' : 's' }}</span>
                  </p>
                </div>
                <div class="col-md-3">
                  <label class="form-label fw-bold">Monto total</label>
                  <p class="mb-0">${{ formatNumber(emision.monto_total) }}</p>
                </div>
                <div class="col-12" v-if="emision.observaciones">
                  <label class="form-label fw-bold">Observaciones</label>
                  <p class="mb-0">{{ emision.observaciones }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Totalizador por empleado -->
      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h5 class="card-title mb-0">Totalizador por empleado</h5>
              <div class="d-flex align-items-center gap-3">
                <input
                  v-model="busqueda"
                  type="text"
                  class="form-control form-control-sm"
                  placeholder="Buscar por CUIL, apellido y nombre, legajo..."
                  style="width: 300px;"
                >
                <span class="badge bg-label-primary">{{ resumenFiltrado.length }} empleados</span>
              </div>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-hover table-striped align-middle tabla-totalizador">
                  <thead class="table-light">
                    <tr>
                      <th class="sortable" @click="setSort('tipo_liq')">Tipo de Liq. <i :class="sortIcon('tipo_liq')"></i></th>
                      <th class="sortable" @click="setSort('convenio')">Convenio <i :class="sortIcon('convenio')"></i></th>
                      <th class="sortable" @click="setSort('cuil')">CUIL <i :class="sortIcon('cuil')"></i></th>
                      <th class="sortable" @click="setSort('nombre')">Apellido y Nombre <i :class="sortIcon('nombre')"></i></th>
                      <th class="sortable" @click="setSort('legajo')">Legajo <i :class="sortIcon('legajo')"></i></th>
                      <th class="sortable text-end" @click="setSort('remunerativos')">Total Remunerativos <i :class="sortIcon('remunerativos')"></i></th>
                      <th class="sortable text-end" @click="setSort('descuentos')">Descuentos <i :class="sortIcon('descuentos')"></i></th>
                      <th class="sortable text-end" @click="setSort('no_remunerativos')">Total No Remunerativos <i :class="sortIcon('no_remunerativos')"></i></th>
                      <th class="sortable text-end" @click="setSort('neto')">Total Neto <i :class="sortIcon('neto')"></i></th>
                      <th class="text-center"></th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(e, index) in resumenOrdenado" :key="index">
                      <td>{{ e.tipo_liq || '—' }}</td>
                      <td>{{ e.convenio || '—' }}</td>
                      <td class="font-monospace">{{ e.cuil }}</td>
                      <td>{{ e.nombre || '—' }}</td>
                      <td>{{ e.legajo }}</td>
                      <td class="text-end text-success">{{ formatNumber(e.remunerativos) }}</td>
                      <td class="text-end text-danger">{{ formatNumber(e.descuentos) }}</td>
                      <td class="text-end text-info">{{ formatNumber(e.no_remunerativos) }}</td>
                      <td class="text-end fw-bold">{{ formatNumber(e.neto) }}</td>
                      <td class="text-center">
                        <button
                          type="button"
                          class="lupa-detalle"
                          title="Ver detalle y cálculos del empleado"
                          @click="abrirDetalle(e)"
                        >
                          <i class="ri-search-line"></i>
                        </button>
                      </td>
                    </tr>
                    <tr v-if="resumenFiltrado.length === 0">
                      <td colspan="10" class="text-center text-muted py-4">No hay empleados</td>
                    </tr>
                  </tbody>
                  <tfoot v-if="resumenFiltrado.length" class="table-light fw-bold">
                    <tr>
                      <td colspan="5" class="text-end">Totales</td>
                      <td class="text-end text-success">{{ formatNumber(totales.rem) }}</td>
                      <td class="text-end text-danger">{{ formatNumber(totales.desc) }}</td>
                      <td class="text-end text-info">{{ formatNumber(totales.norem) }}</td>
                      <td class="text-end fw-bold">{{ formatNumber(totales.neto) }}</td>
                      <td></td>
                    </tr>
                  </tfoot>
                </table>
              </div>
              <div class="d-flex justify-content-start mt-3">
                <button type="button" class="btn btn-success" @click="exportarExcel">
                  <i class="ri-file-excel-2-line me-1"></i> Exportar a Excel
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
      </div>
      <!-- /vista totalizador -->

      <!-- ===================== VISTA RESUMEN DE LIQUIDACIÓN ===================== -->
      <div v-else>
        <div class="card mb-4">
          <div class="resumen-header text-center text-white py-3 px-3">
            <h5 class="fw-bold mb-2">RESUMEN DE LIQUIDACIÓN DE SUELDOS INGRESADA A ARCA</h5>
            <div>
              <strong>Contribuyente:</strong> {{ resumenLiq?.cuit }} &nbsp;&nbsp;
              <strong>Razón Social:</strong> {{ resumenLiq?.razon_social }}
            </div>
          </div>
          <div class="card-body">
            <h6 class="fw-bold mb-3">Datos de la liquidación</h6>
            <div class="resumen-datos row g-3 p-2 rounded">
              <div class="col-md-4">
                <div class="d-flex justify-content-between"><span>Período:</span><strong>{{ resumenLiq?.periodo }}</strong></div>
                <div class="d-flex justify-content-between"><span>Liquidación:</span><strong>{{ resumenLiq?.liquidacion }}</strong></div>
              </div>
              <div class="col-md-4">
                <div class="d-flex justify-content-between"><span>Cantidad de trabajadores:</span><strong>{{ resumenLiq?.cant_trabajadores }}</strong></div>
                <div class="d-flex justify-content-between"><span>Cantidad de trabajadores eventuales:</span><strong>{{ resumenLiq?.cant_eventuales }}</strong></div>
                <div class="d-flex justify-content-between"><span>Cantidad de conceptos:</span><strong>{{ resumenLiq?.cant_conceptos }}</strong></div>
              </div>
              <div class="col-md-4">
                <div class="d-flex justify-content-between"><span>Registros '01'</span><strong>{{ resumenLiq?.reg01 }}</strong></div>
                <div class="d-flex justify-content-between"><span>Registros '02'</span><strong>{{ resumenLiq?.reg02 }}</strong></div>
                <div class="d-flex justify-content-between"><span>Registros '03'</span><strong>{{ resumenLiq?.reg03 }}</strong></div>
                <div class="d-flex justify-content-between"><span>Registros '04'</span><strong>{{ resumenLiq?.reg04 }}</strong></div>
                <div class="d-flex justify-content-between"><span>Registros '05'</span><strong>{{ resumenLiq?.reg05 }}</strong></div>
              </div>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-header d-flex align-items-center gap-4">
            <h6 class="fw-bold mb-0">Detalle de conceptos ingresados</h6>
            <button
              v-if="nuevosCount > 0"
              type="button"
              class="btn btn-link p-0 text-success fw-semibold text-decoration-none"
              :title="filtrarNuevos ? 'Quitar el filtro y mostrar todos los conceptos' : 'Ver solo los conceptos dados de alta en el mes del período'"
              @click="filtrarNuevos = !filtrarNuevos"
            >
              <i :class="filtrarNuevos ? 'ri-filter-off-line' : 'ri-information-line'" class="me-1"></i>
              {{ filtrarNuevos ? 'Ver todos' : (nuevosCount === 1 ? '1 nuevo concepto este mes' : `${nuevosCount} nuevos conceptos este mes`) }}
            </button>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-bordered table-striped tabla-totalizador">
                <thead class="table-light">
                  <tr>
                    <th>Código</th>
                    <th>Descripción</th>
                    <th class="text-end">Importes totales</th>
                    <th class="text-end">Importe período actual</th>
                    <th class="text-end">Importe otros períodos</th>
                    <th class="text-center"></th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(c, i) in conceptosMostrados" :key="i">
                    <td>{{ c.codigo }}</td>
                    <td>{{ c.descripcion || '—' }}</td>
                    <td class="text-end" :class="c.total < 0 ? 'text-danger' : ''">$ {{ formatNumber(c.total) }}</td>
                    <td class="text-end" :class="c.actual < 0 ? 'text-danger' : ''">$ {{ formatNumber(c.actual) }}</td>
                    <td class="text-end" :class="c.otros < 0 ? 'text-danger' : ''">$ {{ formatNumber(c.otros) }}</td>
                    <td class="text-center">
                      <a
                        :href="route('lsd.emision.detalle.concepto', [emision.id, c.codigo])"
                        class="lupa-detalle"
                        title="Ver líneas que conforman el total de este concepto"
                      >
                        <i class="ri-search-line"></i>
                      </a>
                    </td>
                  </tr>
                  <tr v-if="!conceptosMostrados.length">
                    <td colspan="6" class="text-center text-muted py-3">Sin conceptos</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div class="d-flex justify-content-start mt-3">
              <button type="button" class="btn btn-success" @click="exportarResumenExcel">
                <i class="ri-file-excel-2-line me-1"></i> Exportar a Excel
              </button>
            </div>
          </div>
        </div>
      </div>
      <!-- /vista resumen -->
    </div>

    <!-- Modal: legajos ignorados en la generación -->
    <div v-if="mostrarIgnorados" class="lsd-modal-backdrop" @click.self="mostrarIgnorados = false">
      <div class="lsd-modal-card">
        <div class="lsd-modal-header">
          <div>
            <h5 class="mb-1">Legajos ignorados en la generación</h5>
            <small class="text-muted">{{ legajosIgnorados.length }} legajo(s) excluido(s) por datos inconsistentes</small>
          </div>
          <button type="button" class="btn-close" @click="mostrarIgnorados = false"></button>
        </div>
        <div class="lsd-modal-body">
          <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0">
              <thead class="table-light">
                <tr>
                  <th>Legajo</th>
                  <th>CUIL</th>
                  <th>Apellido y Nombre</th>
                  <th>Motivo(s) por los que se ignoró</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(l, i) in legajosIgnorados" :key="i">
                  <td>{{ l.legajo }}</td>
                  <td class="font-monospace">{{ l.cuil }}</td>
                  <td>{{ l.nombre || '—' }}</td>
                  <td>
                    <ul class="mb-0 ps-3">
                      <li v-for="(m, j) in l.motivos" :key="j">
                        <strong>{{ m.campo }}:</strong> {{ m.detalle }}
                      </li>
                    </ul>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        <div class="lsd-modal-footer">
          <button type="button" class="btn btn-outline-secondary" @click="mostrarIgnorados = false">Cerrar</button>
        </div>
      </div>
    </div>

    <!-- Modal: detalle y cálculos del empleado -->
    <div v-if="empleadoSel" class="lsd-modal-backdrop" @click.self="cerrarDetalle">
      <div class="lsd-modal-card">
        <div class="lsd-modal-header">
          <div>
            <h5 class="mb-1">{{ empleadoSel.nombre || '—' }}</h5>
            <small class="text-muted">CUIL {{ empleadoSel.cuil }} · Legajo {{ empleadoSel.legajo }}</small>
          </div>
          <button type="button" class="btn-close" @click="cerrarDetalle"></button>
        </div>

        <div class="lsd-modal-body">
          <!-- Conceptos liquidados -->
          <h6 class="fw-bold mb-2">Conceptos liquidados</h6>
          <div class="table-responsive mb-4">
            <table class="table table-sm table-bordered mb-0">
              <thead class="table-light">
                <tr>
                  <th>Concepto</th>
                  <th>Descripción</th>
                  <th>Tipo</th>
                  <th class="text-end">Cantidad</th>
                  <th class="text-end">Importe</th>
                  <th class="text-center">D/C</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(c, i) in detalleSel.conceptos" :key="i">
                  <td class="font-monospace">{{ c.concepto }}</td>
                  <td>{{ c.descripcion || '—' }}</td>
                  <td :class="tipoClass(c.tipo)">{{ tipoLabel(c.tipo) }}</td>
                  <td class="text-end">{{ formatNumber(c.cantidad) }}</td>
                  <td class="text-end">{{ formatNumber(c.importe) }}</td>
                  <td class="text-center">
                    <span :class="c.debito_credito === 'C' ? 'text-success' : 'text-danger'">{{ c.debito_credito }}</span>
                  </td>
                </tr>
                <tr v-if="!detalleSel.conceptos.length">
                  <td colspan="6" class="text-center text-muted py-3">Sin conceptos</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Cálculos LSD (Registro 04) -->
          <h6 class="fw-bold mb-2">Cálculos LSD (Registro 04)</h6>
          <div v-if="detalleSel.bases">
            <div class="row g-2 mb-3">
              <div class="col-6 col-md-4 col-lg-3" v-for="(f, i) in filasBases" :key="i">
                <div class="lsd-base-box">
                  <span class="lsd-base-label">{{ f.label }}</span>
                  <span class="lsd-base-value">{{ formatNumber(f.valor) }}</span>
                </div>
              </div>
            </div>
            <div class="d-flex flex-wrap gap-2">
              <span class="badge bg-label-secondary">Situación: {{ detalleSel.bases.situacion }}</span>
              <span class="badge bg-label-secondary">Condición: {{ detalleSel.bases.condicion }}</span>
              <span class="badge bg-label-secondary">Actividad: {{ detalleSel.bases.actividad }}</span>
              <span class="badge bg-label-secondary">Modalidad: {{ detalleSel.bases.modalidad }}</span>
              <span class="badge bg-label-secondary">Siniestrado: {{ detalleSel.bases.siniestro }}</span>
              <span class="badge bg-label-secondary">Localidad: {{ detalleSel.bases.localidad }}</span>
            </div>
          </div>
          <p v-else class="text-muted">No se pudieron leer los cálculos del archivo TXT (puede no estar disponible).</p>
        </div>

        <div class="lsd-modal-footer">
          <button type="button" class="btn btn-outline-secondary" @click="cerrarDetalle">Cerrar</button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Botón naranja "Resumen de liquidación" */
.btn-naranja {
  background-color: #fd7e14;
  border-color: #fd7e14;
  color: #fff;
}
.btn-naranja:hover,
.btn-naranja:focus {
  background-color: #e8690b;
  border-color: #e8690b;
  color: #fff;
}

/* Botón "Volver" redondo (solo la flecha) */
.btn-volver-redondo {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  padding: 0;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

/* Vista resumen de liquidación (similar al reporte de ARCA) */
.resumen-header {
  background-color: #4a7ba6;
  border-radius: .5rem .5rem 0 0;
}
.resumen-datos {
  background-color: #dbeefb;
}

/* Fuente de la tabla ~2px más chica */
.tabla-totalizador {
  font-size: 0.8125rem;
}

/* Hover de filas: pinta toda la fila (pisa las variables de Bootstrap 5.3 para
   ganarle al rayado de table-striped y al color de table-hover). */
.table > tbody > tr:hover > * {
  --bs-table-bg-state: #e7e7ff;
  --bs-table-accent-bg: #e7e7ff;
  background-color: #e7e7ff;
}

/* Columnas ordenables */
.sortable {
  cursor: pointer;
  user-select: none;
  white-space: normal;
  vertical-align: middle;
  font-size: 0.75rem;
}
.sortable:hover {
  background: rgba(0, 0, 0, .03);
}
.lsd-sort-ic {
  font-size: 1rem;
  vertical-align: middle;
}

/* Lupa de fondo transparente rodeada de un círculo */
.lupa-detalle {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  border: 1px solid var(--bs-secondary, #6c757d);
  color: var(--bs-secondary, #6c757d);
  background: transparent;
  text-decoration: none;
  transition: all .15s ease;
  cursor: pointer;
}
.lupa-detalle:hover,
.lupa-detalle:focus {
  background: var(--bs-primary, #696cff);
  border-color: var(--bs-primary, #696cff);
  color: #fff;
  outline: none;
}

.lsd-modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, .5);
  display: flex;
  align-items: flex-start;
  justify-content: center;
  z-index: 99999;
  padding: 3rem 1rem;
  overflow-y: auto;
}
.lsd-modal-card {
  background: var(--bs-card-bg, #fff);
  border-radius: .5rem;
  width: 100%;
  max-width: 1000px;
  box-shadow: 0 .5rem 2rem rgba(0, 0, 0, .25);
}
.lsd-modal-header,
.lsd-modal-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.5rem;
  border-bottom: 1px solid var(--bs-border-color, #e0e0e0);
}
.lsd-modal-footer {
  border-bottom: none;
  border-top: 1px solid var(--bs-border-color, #e0e0e0);
  justify-content: flex-end;
}
.lsd-modal-body {
  padding: 1.25rem 1.5rem;
  max-height: 65vh;
  overflow-y: auto;
}
.lsd-base-box {
  display: flex;
  flex-direction: column;
  border: 1px solid var(--bs-border-color, #e0e0e0);
  border-radius: .375rem;
  padding: .5rem .75rem;
  height: 100%;
}
.lsd-base-label {
  font-size: .75rem;
  color: var(--bs-secondary, #6c757d);
  line-height: 1.2;
}
.lsd-base-value {
  font-weight: 600;
  font-family: var(--bs-font-monospace, monospace);
}
</style>
