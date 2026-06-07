<script setup>
import { computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import Swal from 'sweetalert2'

const props = defineProps({
  parametro: Object,
})

const TIPOS = [
  { value: 'H', label: 'Haberes' },
  { value: 'D', label: 'Descuentos' },
  { value: 'A', label: 'Asignaciones' },
  { value: 'NR', label: 'No remunerativos' },
  { value: 'GA', label: 'Ganancias' },
  { value: 'DG', label: 'Devolución de Ganancias' },
  { value: 'RE', label: 'Redondeo' },
  { value: 'AP', label: 'Aportes' },
  { value: 'AU', label: 'Auxiliares' },
]

const esEdicion = computed(() => !!props.parametro?.id)

const form = useForm({
  tiporem: props.parametro?.tiporem || 'H',
  desde: props.parametro?.desde ?? '',
  hasta: props.parametro?.hasta ?? '',
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
    form.put(route('config.parametros.update', props.parametro.id), { onError })
  } else {
    form.post(route('config.parametros.store'), { onError })
  }
}
</script>

<template>
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="app-ecommerce">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-6 row-gap-4">
        <h4 class="mb-1">
          {{ esEdicion ? 'Editar parámetro' : 'Nuevo parámetro' }}
        </h4>
        <Link :href="route('config.parametros.index')" class="btn btn-outline-secondary">
          <i class="ri-arrow-left-line me-1"></i> Volver
        </Link>
      </div>

      <div class="card">
        <div class="card-body">
          <form @submit.prevent="submit" class="row g-3">
            <div class="col-md-4">
              <label for="tiporem" class="form-label">Tipo *</label>
              <select
                id="tiporem"
                v-model="form.tiporem"
                class="form-select"
                :class="{ 'is-invalid': form.errors.tiporem }"
                required
              >
                <option v-for="t in TIPOS" :key="t.value" :value="t.value">{{ t.label }}</option>
              </select>
              <div v-if="form.errors.tiporem" class="invalid-feedback">{{ form.errors.tiporem }}</div>
            </div>

            <div class="col-md-4">
              <label for="desde" class="form-label">Desde *</label>
              <input
                id="desde"
                v-model="form.desde"
                type="number"
                min="0"
                step="1"
                placeholder="Ej: 1"
                class="form-control"
                :class="{ 'is-invalid': form.errors.desde }"
                required
              />
              <div v-if="form.errors.desde" class="invalid-feedback">{{ form.errors.desde }}</div>
              <small class="text-muted">Código de concepto inicial del rango.</small>
            </div>

            <div class="col-md-4">
              <label for="hasta" class="form-label">Hasta *</label>
              <input
                id="hasta"
                v-model="form.hasta"
                type="number"
                min="0"
                step="1"
                placeholder="Ej: 199"
                class="form-control"
                :class="{ 'is-invalid': form.errors.hasta }"
                required
              />
              <div v-if="form.errors.hasta" class="invalid-feedback">{{ form.errors.hasta }}</div>
              <small class="text-muted">Código de concepto final del rango.</small>
            </div>

            <div class="col-12 d-flex justify-content-end gap-2">
              <Link :href="route('config.parametros.index')" class="btn btn-outline-secondary">Cancelar</Link>
              <button type="submit" class="btn btn-primary" :disabled="form.processing">
                <span v-if="!form.processing">{{ esEdicion ? 'Actualizar' : 'Crear' }}</span>
                <span v-else>
                  <span class="spinner-border spinner-border-sm me-2"></span>
                  Guardando...
                </span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>
