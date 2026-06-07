<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  emision: Object,
  items: Array,
  empresa: Object,
})

const busqueda = ref('')

const itemsFiltrados = computed(() => {
  const q = busqueda.value.toLowerCase().trim()
  if (!q) return props.items
  return props.items.filter(item =>
    (item.cuil || '').toLowerCase().includes(q) ||
    (item.legajo || '').toString().toLowerCase().includes(q) ||
    (item.codigo_concepto || '').toString().toLowerCase().includes(q) ||
    (item.importe || '').toString().includes(q)
  )
})

const formatDate = (date) => {
  if (!date) return '—'
  return new Date(date).toLocaleDateString('es-AR')
}

const formatNumber = (num) => {
  if (!num) return '0,00'
  return Number(num).toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const formatPeriodo = (periodo) => {
  if (!periodo || periodo.length < 6) return periodo
  const anio = periodo.substring(0, 4)
  const mes = periodo.substring(4, 6)
  return `${anio}/${mes}`
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

const getDebitoCreditoLabel = (dc) => {
  return dc === 'C' ? 'Crédito' : 'Débito'
}
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
                  <label class="form-label fw-bold">Fecha de generación</label>
                  <p class="mb-0">{{ formatDate(emision.fecha_generacion) }}</p>
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
                <div class="col-md-4">
                  <label class="form-label fw-bold">N° de emisión</label>
                  <p class="mb-0">{{ emision.numero_emision }}</p>
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

      <!-- Tabla de items -->
      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h5 class="card-title mb-0">Conceptos liquidados</h5>
              <div class="d-flex align-items-center gap-3">
                <input
                  v-model="busqueda"
                  type="text"
                  class="form-control form-control-sm"
                  placeholder="Buscar por CUIL, legajo, concepto..."
                  style="width: 280px;"
                >
                <span class="badge bg-label-primary">{{ itemsFiltrados.length }} registros</span>
              </div>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-hover table-striped">
                  <thead class="table-light">
                    <tr>
                      <th>CUIL</th>
                      <th>Legajo</th>
                      <th>Concepto</th>
                      <th class="text-end">Cantidad</th>
                      <th>Unid.</th>
                      <th class="text-end">Importe</th>
                      <th>D/C</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(item, index) in itemsFiltrados" :key="index">
                      <td>{{ item.cuil }}</td>
                      <td>{{ item.legajo }}</td>
                      <td>{{ item.codigo_concepto }}</td>
                      <td class="text-end">{{ formatNumber(item.cantidad) }}</td>
                      <td>{{ item.unidades }}</td>
                      <td class="text-end">{{ formatNumber(item.importe) }}</td>
                      <td>
                        <span :class="item.debito_credito === 'C' ? 'text-success' : 'text-danger'">
                          {{ getDebitoCreditoLabel(item.debito_credito) }}
                        </span>
                      </td>
                    </tr>
                    <tr v-if="itemsFiltrados.length === 0">
                      <td colspan="7" class="text-center text-muted py-4">No hay registros</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>
