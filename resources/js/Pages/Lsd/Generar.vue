<script setup>
import { ref, reactive } from 'vue'
import axios from 'axios'

const props = defineProps({
  empresas: Array,
  periodos: Array,
  emisiones: Array,
})

const tiposLiquidacion = [
  { id: 1, nombre: 'Mes' },
  { id: 2, nombre: 'Quincena' },
  { id: 3, nombre: 'Días' },
  { id: 4, nombre: 'Horas' },
]

const getTipoLiquidacionNombre = (tipoliq) => {
  const tipos = {
    1: 'Normal',
    2: '1er Quincena',
    3: '2da Quincena',
    4: 'SAC',
    5: 'Liq. Final',
  }
  return tipos[tipoliq] || 'Desconocido'
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
const formulario = reactive({
  id_empresa: '',
  periodo_id: '',
  tipo_liquidacion: '',
  fecha_pago: '',
  observaciones: '',
})

const generarEmision = async () => {
  if (!formulario.id_empresa || !formulario.periodo_id || !formulario.tipo_liquidacion || !formulario.fecha_pago) {
    alert('Por favor completa todos los campos requeridos')
    return
  }

  cargando.value = true
  try {
    const response = await axios.post('/lsd/generar-emision', formulario)
    
    if (response.data.success) {
      alert('Emisión generada exitosamente')
      // Recargar la página
      window.location.reload()
    }
  } catch (error) {
    alert('Error: ' + (error.response?.data?.message || error.message))
  } finally {
    cargando.value = false
  }
}

const formatDate = (date) => {
  if (!date) return '-'
  const d = new Date(date)
  return d.toLocaleDateString('es-AR')
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

const descargarEmision = async (id) => {
  try {
    const response = await axios.get(`/lsd/descargar/${id}`, {
      responseType: 'blob',
    })
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `emision_${id}.pdf`)
    document.body.appendChild(link)
    link.click()
    link.parentNode.removeChild(link)
  } catch (error) {
    alert('Error al descargar: ' + (error.response?.data?.message || error.message))
  }
}

const anularEmision = async (id) => {
  if (!confirm('¿Está seguro que desea anular esta emisión?')) {
    return
  }
  try {
    const response = await axios.post(`/lsd/anular/${id}`)
    if (response.data.success) {
      alert('Emisión anulada exitosamente')
      window.location.reload()
    }
  } catch (error) {
    alert('Error: ' + (error.response?.data?.message || error.message))
  }
}
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

                <div class="col-md-6">
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
                      :key="periodo.id"
                      :value="periodo.id"
                    >
                      {{ formatPeriodo(periodo.periodo) }} ({{ getTipoLiquidacionNombre(periodo.tipoliq) }})
                    </option>
                  </select>
                </div>

                <div class="col-md-6">
                  <label for="tipo_liquidacion" class="form-label">Tipo de Liquidación</label>
                  <select
                    id="tipo_liquidacion"
                    v-model="formulario.tipo_liquidacion"
                    class="form-select"
                    required
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

                <div class="col-md-6">
                  <label for="fecha_pago" class="form-label">Fecha de Pago</label>
                  <input
                    id="fecha_pago"
                    v-model="formulario.fecha_pago"
                    type="date"
                    class="form-control"
                    required
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
                    <th>Fecha Emisión</th>
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
                    <td>{{ formatDate(emision.fecha_emision) }}</td>
                    <td>
                      <span :class="getEstadoClass(emision.estado)" class="badge">
                        {{ emision.estado }}
                      </span>
                    </td>
                    <td>{{ emision.cantidad_empleados }}</td>
                    <td>${{ formatNumber(emision.monto_total) }}</td>
                    <td>
                      <div class="d-flex gap-2">
                        <button
                          type="button"
                          class="btn btn-link text-info p-0"
                          @click="verDetalles(emision)"
                          title="Ver detalles"
                          data-bs-toggle="tooltip"
                          data-bs-placement="top"
                          style="text-decoration: none;"
                        >
                          <i class="ri-search-line" style="font-size: 1.25rem;"></i>
                        </button>
                        <button
                          type="button"
                          class="btn btn-link text-success p-0"
                          @click="descargarEmision(emision.id)"
                          title="Descargar emisión"
                          data-bs-toggle="tooltip"
                          data-bs-placement="top"
                          style="text-decoration: none;"
                        >
                          <i class="ri-download-line" style="font-size: 1.25rem;"></i>
                        </button>
                        <button
                          type="button"
                          class="btn btn-link text-danger p-0"
                          @click="anularEmision(emision.id)"
                          title="Anular emisión"
                          data-bs-toggle="tooltip"
                          data-bs-placement="top"
                          style="text-decoration: none;"
                        >
                          <i class="ri-close-line" style="font-size: 1.25rem;"></i>
                        </button>
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