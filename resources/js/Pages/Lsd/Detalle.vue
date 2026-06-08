<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  emision: Object,
  empresa: Object,
  resumen: { type: Array, default: () => [] },
  detallePorCuil: { type: Object, default: () => ({}) },
})

const busqueda = ref('')

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
    return acc
  }, { rem: 0, norem: 0, desc: 0 })
})

// ---- Ordenamiento por columna ----
const sortKey = ref('cuil')
const sortDir = ref('asc')
const numericCols = ['remunerativos', 'no_remunerativos', 'descuentos']

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
</script>

<template>
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="app-ecommerce">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-6 row-gap-4">
        <h4 class="mb-1">Libro Sueldo Digital</h4>
      </div>

      <!-- Encabezado de la emisión -->
      <div class="row">
        <div class="col-12">
          <div class="card mb-6">
            <div class="card-header">
              <h5 class="card-title mb-0">Datos de la emisión #{{ emision.numero_emision }}</h5>
            </div>
            <div class="card-body">
              <div class="row g-3">
                <div class="col-md-4">
                  <label class="form-label fw-bold">Empresa</label>
                  <p class="mb-0">{{ empresa?.detalle ?? '—' }}</p>
                </div>
                <div class="col-md-4">
                  <label class="form-label fw-bold">CUIT</label>
                  <p class="mb-0">{{ emision.cuit_empresa || '—' }}</p>
                </div>
                <div class="col-md-4">
                  <label class="form-label fw-bold">Periodo</label>
                  <p class="mb-0">{{ formatPeriodo(emision.periodo) }}</p>
                </div>
                <div class="col-md-4">
                  <label class="form-label fw-bold">Fecha de emisión</label>
                  <p class="mb-0">{{ formatDate(emision.fecha_emision) }}</p>
                </div>
                <div class="col-md-4">
                  <label class="form-label fw-bold">Estado</label>
                  <p class="mb-0">
                    <span :class="getEstadoClass(emision.estado)" class="badge">{{ emision.estado }}</span>
                  </p>
                </div>
                <div class="col-md-4">
                  <label class="form-label fw-bold">Cantidad de empleados</label>
                  <p class="mb-0">{{ emision.cantidad_empleados }}</p>
                </div>
                <div class="col-md-4">
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
                      <th class="sortable" @click="setSort('tipo_liq')">Tipo de liquidación <i :class="sortIcon('tipo_liq')"></i></th>
                      <th class="sortable" @click="setSort('convenio')">Convenio <i :class="sortIcon('convenio')"></i></th>
                      <th class="sortable" @click="setSort('cuil')">CUIL <i :class="sortIcon('cuil')"></i></th>
                      <th class="sortable" @click="setSort('nombre')">Apellido y Nombre <i :class="sortIcon('nombre')"></i></th>
                      <th class="sortable" @click="setSort('legajo')">Legajo <i :class="sortIcon('legajo')"></i></th>
                      <th class="sortable text-end" @click="setSort('remunerativos')">Total Remunerativos <i :class="sortIcon('remunerativos')"></i></th>
                      <th class="sortable text-end" @click="setSort('no_remunerativos')">Total No Remunerativos <i :class="sortIcon('no_remunerativos')"></i></th>
                      <th class="sortable text-end" @click="setSort('descuentos')">Descuentos <i :class="sortIcon('descuentos')"></i></th>
                      <th class="text-center">Detalle</th>
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
                      <td class="text-end text-info">{{ formatNumber(e.no_remunerativos) }}</td>
                      <td class="text-end text-danger">{{ formatNumber(e.descuentos) }}</td>
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
                      <td colspan="9" class="text-center text-muted py-4">No hay empleados</td>
                    </tr>
                  </tbody>
                  <tfoot v-if="resumenFiltrado.length" class="table-light fw-bold">
                    <tr>
                      <td colspan="5" class="text-end">Totales</td>
                      <td class="text-end text-success">{{ formatNumber(totales.rem) }}</td>
                      <td class="text-end text-info">{{ formatNumber(totales.norem) }}</td>
                      <td class="text-end text-danger">{{ formatNumber(totales.desc) }}</td>
                      <td></td>
                    </tr>
                  </tfoot>
                </table>
              </div>
            </div>
          </div>
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
/* Fuente de la tabla ~2px más chica */
.tabla-totalizador {
  font-size: 0.8125rem;
}

/* Columnas ordenables */
.sortable {
  cursor: pointer;
  user-select: none;
  white-space: nowrap;
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
