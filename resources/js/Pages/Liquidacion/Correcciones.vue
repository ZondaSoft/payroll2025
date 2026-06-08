<script setup>
import { ref, computed } from 'vue'
import * as XLSX from 'xlsx'

const props = defineProps({
  correcciones: { type: Array, default: () => [] },
  periodos: { type: Array, default: () => [] },
})

const busqueda = ref('')
const periodoSel = ref('')

const filtradas = computed(() => {
  const q = busqueda.value.toLowerCase().trim()
  return props.correcciones.filter(c => {
    if (periodoSel.value && c.periodo !== periodoSel.value) return false
    if (!q) return true
    return (c.legajo || '').toString().toLowerCase().includes(q) ||
      (c.cuil || '').toLowerCase().includes(q) ||
      (c.empresa || '').toLowerCase().includes(q) ||
      (c.concepto || '').toString().toLowerCase().includes(q) ||
      (c.motivo || '').toLowerCase().includes(q) ||
      (c.origen || '').toLowerCase().includes(q) ||
      (c.usuario || '').toLowerCase().includes(q)
  })
})

const formatPeriodo = (p) => (!p || p.length < 6) ? p : `${p.substring(0, 4)}/${p.substring(4, 6)}`

const formatFecha = (f) => {
  if (!f) return '—'
  const [fecha, hora] = String(f).split(' ')
  const [y, m, d] = (fecha || '').split('-')
  return (y && m && d) ? `${d}/${m}/${y}${hora ? ' ' + hora.substring(0, 5) : ''}` : String(f)
}

const formatNumber = (n) => {
  if (n === null || n === undefined || n === '') return '0,00'
  return Number(n).toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const origenLabel = (o) => ({
  ajuste_aportes_lsd: 'Ajuste de aportes (LSD)',
  liquidacion_individual: 'Liquidación individual',
  liquidacion_global: 'Liquidación global',
  generador_txt: 'Generador LSD',
}[o] || (o || '—'))

const exportarExcel = () => {
  const data = filtradas.value.map(c => ({
    'Fecha': formatFecha(c.fecha),
    'Período': formatPeriodo(c.periodo),
    'Empresa': c.empresa || '',
    'Legajo': c.legajo || '',
    'CUIL': c.cuil || '',
    'Concepto': c.concepto || '',
    'ARCA': c.concepto_arca || '',
    'Importe anterior': Number(c.importe_anterior || 0),
    'Importe nuevo': Number(c.importe_nuevo || 0),
    'Diferencia': Number(c.diferencia || 0),
    'Motivo': c.motivo || '',
    'Origen': origenLabel(c.origen),
    'Usuario': c.usuario || '',
  }))
  const ws = XLSX.utils.json_to_sheet(data)
  const wb = XLSX.utils.book_new()
  XLSX.utils.book_append_sheet(wb, ws, 'Correcciones')
  XLSX.writeFile(wb, 'correcciones_y_ajustes.xlsx')
}
</script>

<template>
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="app-ecommerce">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-6 row-gap-4">
        <div class="col-lg-9">
          <h4 class="mb-1">Correcciones y ajustes de liquidación</h4>
          <p class="mb-0 text-muted">Histórico de correcciones automáticas sobre la liquidación (p. ej. "Ajustar valores" de aportes en el LSD). Registra antes/después, motivo, origen y usuario.</p>
        </div>
      </div>

      <div class="card">
        <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
          <div class="d-flex align-items-center gap-3 flex-wrap">
            <select v-model="periodoSel" class="form-select form-select-sm" style="width: 160px;">
              <option value="">Todos los períodos</option>
              <option v-for="p in periodos" :key="p" :value="p">{{ formatPeriodo(p) }}</option>
            </select>
            <input v-model="busqueda" type="text" class="form-control form-control-sm"
                   placeholder="Buscar por legajo, CUIL, empresa, motivo, usuario..." style="width: 320px;">
            <span class="badge bg-label-primary">{{ filtradas.length }} registros</span>
          </div>
          <button type="button" class="btn btn-success btn-sm" @click="exportarExcel" :disabled="!filtradas.length">
            <i class="ri-file-excel-2-line me-1"></i> Exportar a Excel
          </button>
        </div>

        <div class="table-responsive">
          <table class="table table-striped table-hover align-middle" style="font-size: 0.8125rem;">
            <thead class="table-light">
              <tr>
                <th>Fecha</th>
                <th>Período</th>
                <th>Empresa</th>
                <th>Legajo</th>
                <th>CUIL</th>
                <th>Concepto</th>
                <th class="text-end">Importe anterior</th>
                <th class="text-end">Importe nuevo</th>
                <th class="text-end">Diferencia</th>
                <th>Motivo</th>
                <th>Origen</th>
                <th>Usuario</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!filtradas.length">
                <td colspan="12" class="text-center text-muted py-4">No hay correcciones registradas.</td>
              </tr>
              <tr v-for="c in filtradas" :key="c.id">
                <td class="text-nowrap">{{ formatFecha(c.fecha) }}</td>
                <td>{{ formatPeriodo(c.periodo) }}</td>
                <td>{{ c.empresa || '—' }}</td>
                <td>{{ c.legajo }}</td>
                <td class="font-monospace">{{ c.cuil }}</td>
                <td>{{ c.concepto }}<span v-if="c.concepto_arca" class="text-muted"> ({{ c.concepto_arca }})</span></td>
                <td class="text-end">{{ formatNumber(c.importe_anterior) }}</td>
                <td class="text-end">{{ formatNumber(c.importe_nuevo) }}</td>
                <td class="text-end" :class="c.diferencia < 0 ? 'text-danger' : 'text-success'">{{ formatNumber(c.diferencia) }}</td>
                <td>{{ c.motivo }}</td>
                <td><span class="badge bg-label-info">{{ origenLabel(c.origen) }}</span></td>
                <td>{{ c.usuario || '—' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>
