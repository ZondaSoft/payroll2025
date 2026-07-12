<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import axios from 'axios'
import Swal from 'sweetalert2'
import * as XLSX from 'xlsx'

const props = defineProps({
  emision: Object,
  empresa: Object,
  concepto: Object,           // { codigo, descripcion, tipo }
  lineas: { type: Array, default: () => [] },
  total: { type: Number, default: 0 },
  // Catálogo de conceptos del mismo tipo que el original (para editar el código por línea).
  conceptosMismoTipo: { type: Array, default: () => [] },
})

// ---- Modal "Editar código del concepto" (por línea) ----
const lineaEnEdicion = ref(null)   // línea seleccionada (null = modal cerrado)
const nuevoCodigo = ref('')
const grabandoCodigo = ref(false)

const abrirEditarCodigo = (l) => {
  lineaEnEdicion.value = l
  nuevoCodigo.value = ''
}
const cerrarEditarCodigo = () => { lineaEnEdicion.value = null }

const grabarNuevoCodigo = async () => {
  if (!nuevoCodigo.value || !lineaEnEdicion.value || grabandoCodigo.value) return
  grabandoCodigo.value = true
  try {
    const response = await axios.post(
      route('lsd.emision.detalle.concepto.editar_codigo', [props.emision.id, props.concepto.codigo]),
      { item_id: lineaEnEdicion.value.item_id, nuevo_codigo: nuevoCodigo.value },
    )
    cerrarEditarCodigo()
    await Swal.fire({
      icon: 'success',
      title: 'Código actualizado',
      text: (response.data?.message || '') + ' Regenerá la emisión para que el TXT lo refleje.',
      timer: 3000,
      showConfirmButton: false,
    })
    router.reload()
  } catch (error) {
    const msg = error.response?.data?.message
      || Object.values(error.response?.data?.errors || {}).flat().join(' ')
      || error.message
    Swal.fire({ icon: 'error', title: 'No se pudo actualizar', text: msg, confirmButtonText: 'Cerrar' })
  } finally {
    grabandoCodigo.value = false
  }
}

// URL a la liquidación individual de la que proviene la línea (legajo + período + tipoliq).
const urlLiquidacion = (l) => route('liquidacion.individual.index', {
  legajo_id: l.legajo_id,
  periodo: props.emision?.periodo,
  ...(l.tipoliq_cod != null ? { tipoliq: l.tipoliq_cod } : {}),
})

const busqueda = ref('')

const lineasFiltradas = computed(() => {
  const q = busqueda.value.toLowerCase().trim()
  if (!q) return props.lineas
  return props.lineas.filter(l =>
    (l.cuil || '').toLowerCase().includes(q) ||
    (l.nombre || '').toLowerCase().includes(q) ||
    (l.legajo || '').toString().toLowerCase().includes(q) ||
    (l.tipo_liq || '').toLowerCase().includes(q)
  )
})

const totalFiltrado = computed(() =>
  lineasFiltradas.value.reduce((acc, l) => acc + Number(l.importe || 0), 0)
)

// ---- Ordenamiento por columna ----
const sortKey = ref('legajo')
const sortDir = ref('asc')
const numericCols = ['cantidad', 'importe']

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

const formatNumber = (num) => {
  if (num === null || num === undefined || num === '') return '0,00'
  return Number(num).toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const formatPeriodo = (periodo) => {
  if (!periodo || periodo.length < 6) return periodo
  return `${periodo.substring(0, 4)}/${periodo.substring(4, 6)}`
}

const volver = () => window.history.back()

// ---- Exportar a Excel ----
const exportarExcel = () => {
  const data = lineasOrdenadas.value.map(l => ({
    'Legajo': l.legajo ?? '',
    'CUIL': l.cuil || '',
    'Apellido y Nombre': l.nombre || '',
    'Tipo de liquidación': l.tipo_liq || '',
    'Cantidad': Number(l.cantidad || 0),
    'D/C': l.debito_credito || '',
    'Importe': Number(l.importe || 0),
  }))
  data.push({
    'Legajo': '',
    'CUIL': '',
    'Apellido y Nombre': 'TOTAL',
    'Tipo de liquidación': '',
    'Cantidad': '',
    'D/C': '',
    'Importe': Number(totalFiltrado.value.toFixed(2)),
  })
  const ws = XLSX.utils.json_to_sheet(data)
  const wb = XLSX.utils.book_new()
  XLSX.utils.book_append_sheet(wb, ws, `Concepto ${props.concepto?.codigo ?? ''}`)
  const periodo = (props.emision?.periodo ?? '').toString()
  XLSX.writeFile(wb, `concepto_${props.concepto?.codigo ?? ''}_emision_${props.emision?.numero_emision ?? ''}_periodo_${periodo}.xlsx`)
}
</script>

<template>
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="app-ecommerce">
      <div class="d-flex align-items-center gap-3 mb-6">
        <button type="button" class="btn btn-outline-secondary btn-volver-redondo" @click="volver" title="Volver">
          <i class="ri-arrow-left-line"></i>
        </button>
        <div>
          <h4 class="mb-1">Concepto {{ concepto?.codigo }} — {{ concepto?.descripcion || 'Sin descripción' }}</h4>
          <p class="mb-0 text-muted">
            Emisión #{{ emision?.numero_emision }} · {{ empresa?.detalle ?? '—' }} · Período {{ formatPeriodo(emision?.periodo) }}
          </p>
        </div>
      </div>

      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0">Líneas que conforman el total</h5>
          <div class="d-flex align-items-center gap-3">
            <input
              v-model="busqueda"
              type="text"
              class="form-control form-control-sm"
              placeholder="Buscar por legajo, CUIL, apellido y nombre..."
              style="width: 300px;"
            >
            <span class="badge bg-label-primary">{{ lineasFiltradas.length }} líneas</span>
          </div>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-hover table-striped align-middle tabla-totalizador">
              <thead class="table-light">
                <tr>
                  <th class="sortable" @click="setSort('legajo')">Legajo <i :class="sortIcon('legajo')"></i></th>
                  <th class="sortable" @click="setSort('cuil')">CUIL <i :class="sortIcon('cuil')"></i></th>
                  <th class="sortable" @click="setSort('nombre')">Apellido y Nombre <i :class="sortIcon('nombre')"></i></th>
                  <th class="sortable" @click="setSort('tipo_liq')">Tipo de Liq. <i :class="sortIcon('tipo_liq')"></i></th>
                  <th class="sortable text-end" @click="setSort('cantidad')">Cantidad <i :class="sortIcon('cantidad')"></i></th>
                  <th class="text-center">D/C</th>
                  <th class="sortable text-end" @click="setSort('importe')">Importe <i :class="sortIcon('importe')"></i></th>
                  <th class="text-center">Ver</th>
                  <th class="text-center"></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(l, i) in lineasOrdenadas" :key="i">
                  <td>{{ l.legajo }}</td>
                  <td class="font-monospace">{{ l.cuil }}</td>
                  <td>{{ l.nombre || '—' }}</td>
                  <td>{{ l.tipo_liq || '—' }}</td>
                  <td class="text-end">{{ formatNumber(l.cantidad) }}</td>
                  <td class="text-center">
                    <span :class="l.debito_credito === 'C' ? 'text-success' : 'text-danger'">{{ l.debito_credito }}</span>
                  </td>
                  <td class="text-end fw-bold" :class="l.importe < 0 ? 'text-danger' : ''">$ {{ formatNumber(l.importe) }}</td>
                  <td class="text-center">
                    <Link
                      v-if="l.legajo_id"
                      :href="urlLiquidacion(l)"
                      class="lupa-liq"
                      title="Ir a la liquidación individual de este legajo"
                    >
                      <i class="ri-search-line"></i>
                    </Link>
                    <span v-else class="text-muted">—</span>
                  </td>
                  <td class="text-center">
                    <button
                      type="button"
                      class="lupa-liq"
                      title="Editar código del concepto"
                      @click="abrirEditarCodigo(l)"
                    >
                      <i class="ri-pencil-line"></i>
                    </button>
                  </td>
                </tr>
                <tr v-if="!lineasFiltradas.length">
                  <td colspan="9" class="text-center text-muted py-4">No hay líneas para este concepto</td>
                </tr>
              </tbody>
              <tfoot v-if="lineasFiltradas.length" class="table-light fw-bold">
                <tr>
                  <td colspan="6" class="text-end">Total</td>
                  <td class="text-end" :class="totalFiltrado < 0 ? 'text-danger' : ''">$ {{ formatNumber(totalFiltrado) }}</td>
                  <td></td>
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

    <!-- Modal: editar código del concepto de una línea -->
    <div v-if="lineaEnEdicion" class="lsd-modal-backdrop" @click.self="cerrarEditarCodigo">
      <div class="lsd-modal-card">
        <div class="lsd-modal-header">
          <div>
            <h5 class="mb-1">Editar código del concepto</h5>
            <small class="text-muted">
              Legajo {{ lineaEnEdicion.legajo }} · {{ lineaEnEdicion.nombre || '—' }} · {{ lineaEnEdicion.tipo_liq }}
            </small>
          </div>
          <button type="button" class="btn-close" @click="cerrarEditarCodigo"></button>
        </div>

        <div class="lsd-modal-body">
          <div class="mb-4">
            <label class="form-label mb-1">Código actual</label>
            <p class="fw-semibold mb-0">{{ concepto?.codigo }} — {{ concepto?.descripcion || 'Sin descripción' }}</p>
          </div>

          <div class="mb-2">
            <label for="nuevo_codigo" class="form-label mb-1">Nuevo código</label>
            <select id="nuevo_codigo" v-model="nuevoCodigo" class="form-select">
              <option value="" disabled>Seleccionar nuevo código...</option>
              <option v-for="c in conceptosMismoTipo" :key="c.codigo" :value="c.codigo">
                {{ c.codigo }} - {{ c.detalle }}
              </option>
            </select>
            <small class="text-muted">
              Solo conceptos del mismo tipo ({{ concepto?.tipo || '—' }}). El cambio impacta en la
              liquidación de origen; regenerá la emisión para que el TXT lo refleje.
            </small>
          </div>
        </div>

        <div class="lsd-modal-footer">
          <div class="d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-outline-secondary" @click="cerrarEditarCodigo">Cancelar</button>
            <button
              type="button"
              class="btn btn-primary"
              :disabled="!nuevoCodigo || grabandoCodigo"
              @click="grabarNuevoCodigo"
            >
              <span v-if="!grabandoCodigo">Grabar</span>
              <span v-else>
                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                Grabando...
              </span>
            </button>
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

/* Modal (mismo estilo que los modales del detalle de emisión) */
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
  max-width: 560px;
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
}
</style>
