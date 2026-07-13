<script setup>
import { ref, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import UserAvatar from '@/Components/UserAvatar.vue'
import * as XLSX from 'xlsx'

const props = defineProps({
  periodo: String,
  tipoliq: { type: [Number, String, null], default: null },
  tipoLiqNombre: { type: String, default: '' },
  fechaPago: { type: String, default: null },
  empresa: { type: Object, default: () => ({}) },
  lineas: { type: Array, default: () => [] },
})

const formatPeriodo = (periodo) => {
  if (!periodo || periodo.length < 6) return periodo
  return `${periodo.substring(0, 4)}/${periodo.substring(4, 6)}`
}

const formatNumber = (num) => {
  if (num === null || num === undefined || num === '') return '0,00'
  return Number(num).toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const formatFecha = (f) => {
  if (!f) return '—'
  const [y, m, d] = String(f).split('-')
  return (y && m && d) ? `${d}/${m}/${y}` : String(f)
}

// La foto por defecto (/img/avatars/...) se descarta para que el avatar caiga a las iniciales
// (mismo criterio que en el recibo individual).
const fotoDe = (l) => {
  const url = l.foto_url || ''
  return url.includes('/img/avatars/') ? '' : url
}

// ---- Buscador rápido ----
const busqueda = ref('')

const lineasFiltradas = computed(() => {
  const q = busqueda.value.toLowerCase().trim()
  if (!q) return props.lineas
  return props.lineas.filter(l =>
    (l.legajo || '').toString().toLowerCase().includes(q) ||
    (l.nombre || '').toLowerCase().includes(q) ||
    (l.convenio || '').toLowerCase().includes(q) ||
    (l.categoria || '').toLowerCase().includes(q)
  )
})

const totalNeto = computed(() =>
  lineasFiltradas.value.reduce((acc, l) => acc + Number(l.neto || 0), 0)
)

// ---- Ordenamiento por columna ----
const sortKey = ref('legajo')
const sortDir = ref('asc')

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

const lineasOrdenadas = computed(() => {
  const arr = [...lineasFiltradas.value]
  const key = sortKey.value
  const dir = sortDir.value === 'asc' ? 1 : -1
  arr.sort((a, b) => {
    const cmp = key === 'neto'
      ? Number(a.neto || 0) - Number(b.neto || 0)
      : String(a[key] ?? '').localeCompare(String(b[key] ?? ''), 'es', { numeric: true, sensitivity: 'base' })
    return cmp * dir
  })
  return arr
})

// URL a la liquidación individual del legajo (mismos filtros de período/tipo).
const urlLiquidacion = (l) => route('liquidacion.individual.index', {
  legajo_id: l.legajo_id,
  periodo: props.periodo,
  ...(props.tipoliq != null ? { tipoliq: props.tipoliq } : {}),
})

const urlVolver = () => route('liquidacion.individual.index', {
  periodo: props.periodo,
  ...(props.tipoliq != null ? { tipoliq: props.tipoliq } : {}),
})

// ---- Exportar a Excel ----
const exportarExcel = () => {
  const data = lineasOrdenadas.value.map(l => ({
    'Legajo': l.legajo ?? '',
    'Apellido y Nombre': l.nombre || '',
    'Convenio': l.convenio || '',
    'Categoría': l.categoria || '',
    'Neto a Pagar': Number(l.neto || 0),
  }))
  data.push({
    'Legajo': '',
    'Apellido y Nombre': 'TOTAL',
    'Convenio': '',
    'Categoría': '',
    'Neto a Pagar': Number(totalNeto.value.toFixed(2)),
  })
  const ws = XLSX.utils.json_to_sheet(data)
  ws['!cols'] = [{ wch: 10 }, { wch: 34 }, { wch: 28 }, { wch: 28 }, { wch: 16 }]
  const range = XLSX.utils.decode_range(ws['!ref'])
  for (let row = range.s.r + 1; row <= range.e.r; row++) {
    const ref = XLSX.utils.encode_cell({ c: 4, r: row })
    if (ws[ref]) { ws[ref].t = 'n'; ws[ref].z = '"$"#,##0.00' }
  }
  const wb = XLSX.utils.book_new()
  XLSX.utils.book_append_sheet(wb, ws, 'Liquidación')
  XLSX.writeFile(wb, `liquidacion_lista_${props.periodo}${props.tipoliq != null ? '_' + tipoLiqSlug() : ''}.xlsx`)
}

const tipoLiqSlug = () => (props.tipoLiqNombre || '').replace(/[^A-Za-z0-9]/g, '')

// ---- Exportar a PDF (se abre en otra pestaña) ----
const exportarPdf = () => {
  const url = route('liquidacion.individual.lista.pdf', {
    periodo: props.periodo,
    ...(props.tipoliq != null ? { tipoliq: props.tipoliq } : {}),
  })
  window.open(url, '_blank')
}
</script>

<template>
  <div class="container-xxl flex-grow-1 container-p-y">

    <!-- HEAD -->
    <div class="row mb-4">
      <div class="col d-flex align-items-center gap-3">
        <Link :href="urlVolver()" class="btn btn-outline-secondary btn-volver-redondo" title="Volver al recibo individual">
          <i class="ri-arrow-left-line"></i>
        </Link>
        <h4 class="fw-bold mb-0">
          <span class="text-muted fw-light">Liquidación /</span> Vista de lista
        </h4>
      </div>
    </div>

    <!-- Datos del período -->
    <div class="row mb-4">
      <div class="col">
        <div class="card">
          <div class="card-body py-3" style="font-size: 0.875rem;">
            <div class="row g-4">
              <div class="col-6 col-md-3">
                <small class="text-muted d-block">Empresa</small>
                <span class="fw-semibold">{{ empresa?.razon ?? empresa?.detalle ?? '—' }}</span>
              </div>
              <div class="col-6 col-md-2">
                <small class="text-muted d-block">Período</small>
                <span class="fw-semibold">{{ formatPeriodo(periodo) }}</span>
              </div>
              <div class="col-6 col-md-2">
                <small class="text-muted d-block">Tipo de liquidación</small>
                <span class="fw-semibold">{{ tipoLiqNombre || '—' }}</span>
              </div>
              <div class="col-6 col-md-2">
                <small class="text-muted d-block">Fecha de pago</small>
                <span class="fw-semibold">{{ formatFecha(fechaPago) }}</span>
              </div>
              <div class="col-6 col-md-2">
                <small class="text-muted d-block">Legajos</small>
                <span class="fw-semibold">{{ lineas.length }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Tabla -->
    <div class="row">
      <div class="col">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Detalle de la liquidación</h5>
            <div class="d-flex align-items-center gap-3">
              <input
                v-model="busqueda"
                type="text"
                class="form-control form-control-sm"
                placeholder="Buscar por legajo, apellido y nombre, convenio, categoría..."
                style="width: 340px;"
              >
              <span class="badge bg-label-primary">{{ lineasFiltradas.length }} legajos</span>
            </div>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-hover table-striped align-middle tabla-lista">
                <thead class="table-light">
                  <tr>
                    <th class="sortable" @click="setSort('legajo')">Legajo <i :class="sortIcon('legajo')"></i></th>
                    <th class="sortable" @click="setSort('nombre')">Apellido y Nombre <i :class="sortIcon('nombre')"></i></th>
                    <th class="sortable" @click="setSort('convenio')">Convenio <i :class="sortIcon('convenio')"></i></th>
                    <th class="sortable" @click="setSort('categoria')">Categoría <i :class="sortIcon('categoria')"></i></th>
                    <th class="sortable text-end" @click="setSort('neto')">Neto a Pagar <i :class="sortIcon('neto')"></i></th>
                    <th class="text-center"></th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="l in lineasOrdenadas" :key="l.legajo">
                    <td>{{ l.legajo }}</td>
                    <td>
                      <div class="d-flex align-items-center gap-2">
                        <UserAvatar :src="fotoDe(l)" :name="l.nombre" :size="34" />
                        <span>{{ l.nombre || '—' }}</span>
                      </div>
                    </td>
                    <td>{{ l.convenio || '—' }}</td>
                    <td>{{ l.categoria || '—' }}</td>
                    <td class="text-end fw-bold" :class="l.neto < 0 ? 'text-danger' : ''">$ {{ formatNumber(l.neto) }}</td>
                    <td class="text-center">
                      <Link
                        v-if="l.legajo_id"
                        :href="urlLiquidacion(l)"
                        class="lupa-liq"
                        title="Abrir la liquidación individual de este legajo"
                      >
                        <i class="ri-search-line"></i>
                      </Link>
                      <span v-else class="text-muted">—</span>
                    </td>
                  </tr>
                  <tr v-if="!lineasFiltradas.length">
                    <td colspan="6" class="text-center text-muted py-4">No hay liquidaciones para este período</td>
                  </tr>
                </tbody>
                <tfoot v-if="lineasFiltradas.length" class="table-light fw-bold">
                  <tr>
                    <td colspan="4" class="text-end">Total Neto a Pagar</td>
                    <td class="text-end" :class="totalNeto < 0 ? 'text-danger' : ''">$ {{ formatNumber(totalNeto) }}</td>
                    <td></td>
                  </tr>
                </tfoot>
              </table>
            </div>
            <div class="d-flex justify-content-start gap-2 mt-3">
              <button type="button" class="btn btn-success" @click="exportarExcel">
                <i class="ri-file-excel-2-line me-1"></i> Exportar a Excel
              </button>
              <button type="button" class="btn btn-danger" @click="exportarPdf">
                <i class="ri-file-pdf-2-line me-1"></i> Exportar a PDF
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
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

.tabla-lista {
  font-size: 0.8125rem;
}

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

/* Hover de filas (mismo criterio que el detalle de emisión LSD) */
.table > tbody > tr:hover > * {
  --bs-table-bg-state: #e7e7ff;
  --bs-table-accent-bg: #e7e7ff;
  background-color: #e7e7ff;
}

.lupa-liq {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 30px;
  height: 30px;
  border-radius: 50%;
  border: 1px solid var(--bs-secondary, #6c757d);
  color: var(--bs-secondary, #6c757d);
  background: transparent;
  text-decoration: none;
  transition: all .15s ease;
}
.lupa-liq:hover,
.lupa-liq:focus {
  background: var(--bs-primary, #696cff);
  border-color: var(--bs-primary, #696cff);
  color: #fff;
  outline: none;
}
</style>
