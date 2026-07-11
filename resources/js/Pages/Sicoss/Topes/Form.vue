<script setup>
import { computed, ref, onMounted } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import Swal from 'sweetalert2'

const props = defineProps({
  tope: Object,
  periodoSugerido: String,
})

const esEdicion = computed(() => !!props.tope?.id)

// Foco automático en el campo Período al abrir el formulario.
const periodoInput = ref(null)
onMounted(() => periodoInput.value?.focus())

const form = useForm({
  periodo_desde: props.tope?.periodo_desde || props.periodoSugerido || '',
  base_minima: props.tope?.base_minima || '',
  tope_aportes: props.tope?.tope_aportes || '',
  normativa: props.tope?.normativa || '',
  ipc_porcentaje: props.tope?.ipc_porcentaje || '',
  observaciones: props.tope?.observaciones || '',
})

const submit = () => {
  const onError = (errores) => {
    const lista = Object.values(errores).join('\n')
    Swal.fire({
      icon: 'error',
      title: 'Revisá los datos',
      text: lista,
      confirmButtonText: 'Cerrar',
    })
  }

  if (esEdicion.value) {
    form.put(route('sicoss.topes.update', props.tope.id), { onError })
  } else {
    form.post(route('sicoss.topes.store'), { onError })
  }
}
</script>

<template>
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="app-ecommerce">
      <form @submit.prevent="submit">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-6 row-gap-4">
          <h4 class="mb-1">
            {{ esEdicion ? 'Editar tope máximo' : 'Nuevo tope máximo' }}
          </h4>
          <div class="d-flex gap-2">
            <Link :href="route('sicoss.topes.index')" class="btn btn-outline-secondary">Cancelar</Link>
            <button type="submit" class="btn btn-primary" :disabled="form.processing">
              <span v-if="!form.processing">{{ esEdicion ? 'Actualizar' : 'Crear' }}</span>
              <span v-else>
                <span class="spinner-border spinner-border-sm me-2"></span>
                Guardando...
              </span>
            </button>
          </div>
        </div>

        <div class="card">
          <div class="card-body">
            <div class="row g-3">
            <div class="col-md-4">
              <label for="periodo_desde" class="form-label">Período (AAAAMM) *</label>
              <input
                id="periodo_desde"
                ref="periodoInput"
                v-model="form.periodo_desde"
                type="text"
                inputmode="numeric"
                maxlength="6"
                pattern="[0-9]{6}"
                placeholder="Ej: 202605"
                class="form-control"
                :class="{ 'is-invalid': form.errors.periodo_desde }"
                @input="form.periodo_desde = form.periodo_desde.replace(/\D/g, '')"
                @keypress="(e) => { if (!/[0-9]/.test(e.key)) e.preventDefault() }"
                required
              />
              <div v-if="form.errors.periodo_desde" class="invalid-feedback">{{ form.errors.periodo_desde }}</div>
              <small class="text-muted">Formato AAAAMM. Ej: 202605 = mayo 2026. Aplica desde ese período en adelante.</small>
            </div>

            <!-- Corte de fila: Período queda solo en la primera línea -->
            <div class="w-100"></div>

            <div class="col-md-4">
              <label for="base_minima" class="form-label">Mínima (pesos)</label>
              <input
                id="base_minima"
                v-model="form.base_minima"
                type="number"
                step="0.01"
                min="0"
                placeholder="Ej: 132420.94"
                class="form-control"
                :class="{ 'is-invalid': form.errors.base_minima }"
              />
              <div v-if="form.errors.base_minima" class="invalid-feedback">{{ form.errors.base_minima }}</div>
              <small class="text-muted">Base imponible mínima de aportes del período.</small>
            </div>

            <div class="col-md-4">
              <label for="tope_aportes" class="form-label">Máxima / tope (pesos) *</label>
              <input
                id="tope_aportes"
                v-model="form.tope_aportes"
                type="number"
                step="0.01"
                min="0"
                placeholder="Ej: 4303619.01"
                class="form-control"
                :class="{ 'is-invalid': form.errors.tope_aportes }"
                required
              />
              <div v-if="form.errors.tope_aportes" class="invalid-feedback">{{ form.errors.tope_aportes }}</div>
              <small class="text-muted">Tope máximo de la base imponible de aportes (BI 1/4/5).</small>
            </div>

            <div class="col-md-8">
              <label for="normativa" class="form-label">Resolución</label>
              <input
                id="normativa"
                v-model="form.normativa"
                type="text"
                maxlength="255"
                placeholder="Ej: Res. 110/2026"
                class="form-control"
                :class="{ 'is-invalid': form.errors.normativa }"
              />
              <div v-if="form.errors.normativa" class="invalid-feedback">{{ form.errors.normativa }}</div>
            </div>

            <div class="col-md-4">
              <label for="ipc_porcentaje" class="form-label">% IPC</label>
              <input
                id="ipc_porcentaje"
                v-model="form.ipc_porcentaje"
                type="number"
                step="0.01"
                placeholder="Ej: 3.38"
                class="form-control"
                :class="{ 'is-invalid': form.errors.ipc_porcentaje }"
              />
              <div v-if="form.errors.ipc_porcentaje" class="invalid-feedback">{{ form.errors.ipc_porcentaje }}</div>
              <small class="text-muted">Variación IPC del período (ej. 3.38 = +3,38%).</small>
            </div>

            <div class="col-md-12">
              <label for="observaciones" class="form-label">Observaciones</label>
              <textarea
                id="observaciones"
                v-model="form.observaciones"
                rows="3"
                class="form-control"
                :class="{ 'is-invalid': form.errors.observaciones }"
              ></textarea>
              <div v-if="form.errors.observaciones" class="invalid-feedback">{{ form.errors.observaciones }}</div>
            </div>

            </div>
          </div>
        </div>

        <div class="mt-4">
          <a href="https://www.argentina.gob.ar/trabajo/seguridadsocial/imss"
             target="_blank"
             rel="noopener noreferrer"
             class="btn btn-topes-ss">
            <i class="ri-earth-line me-1"></i> Topes de la Seguridad Social
          </a>
        </div>
      </form>
    </div>
  </div>
</template>

<style scoped>
.btn-topes-ss {
  background-color: #7e3ff2;
  border-color: #7e3ff2;
  color: #fff;
}
.btn-topes-ss:hover,
.btn-topes-ss:focus {
  background-color: #6a2fd6;
  border-color: #6a2fd6;
  color: #fff;
}
</style>
