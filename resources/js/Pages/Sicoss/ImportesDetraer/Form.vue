<script setup>
import { reactive, computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import Swal from 'sweetalert2'

const props = defineProps({
  importe: Object,
})

const esEdicion = computed(() => !!props.importe?.id)

const form = useForm({
  periodo_desde: props.importe?.periodo_desde || '',
  importe: props.importe?.importe || '',
  normativa: props.importe?.normativa || '',
  observaciones: props.importe?.observaciones || '',
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
    form.put(route('sicoss.importes-detraer.update', props.importe.id), { onError })
  } else {
    form.post(route('sicoss.importes-detraer.store'), { onError })
  }
}
</script>

<template>
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="app-ecommerce">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-6 row-gap-4">
        <h4 class="mb-1">
          {{ esEdicion ? 'Editar importe a detraer' : 'Nuevo importe a detraer' }}
        </h4>
        <Link :href="route('sicoss.importes-detraer.index')" class="btn btn-outline-secondary">
          <i class="ri-arrow-left-line me-1"></i> Volver
        </Link>
      </div>

      <div class="card">
        <div class="card-body">
          <form @submit.prevent="submit" class="row g-3">
            <div class="col-md-4">
              <label for="periodo_desde" class="form-label">Vigente desde (AAAAMM) *</label>
              <input
                id="periodo_desde"
                v-model="form.periodo_desde"
                type="text"
                maxlength="6"
                pattern="[0-9]{6}"
                placeholder="Ej: 202604"
                class="form-control"
                :class="{ 'is-invalid': form.errors.periodo_desde }"
                required
              />
              <div v-if="form.errors.periodo_desde" class="invalid-feedback">{{ form.errors.periodo_desde }}</div>
              <small class="text-muted">Formato AAAAMM. Ej: 202604 = abril 2026.</small>
            </div>

            <div class="col-md-4">
              <label for="importe" class="form-label">Importe (pesos) *</label>
              <input
                id="importe"
                v-model="form.importe"
                type="number"
                step="0.01"
                min="0"
                placeholder="Ej: 7003.68"
                class="form-control"
                :class="{ 'is-invalid': form.errors.importe }"
                required
              />
              <div v-if="form.errors.importe" class="invalid-feedback">{{ form.errors.importe }}</div>
              <small class="text-muted">Sin separador de miles. Coma decimal con punto.</small>
            </div>

            <div class="col-md-12">
              <label for="normativa" class="form-label">Normativa</label>
              <input
                id="normativa"
                v-model="form.normativa"
                type="text"
                maxlength="255"
                placeholder="Ej: RG ARCA 5418/2023 - Ley 27.430 art 4"
                class="form-control"
                :class="{ 'is-invalid': form.errors.normativa }"
              />
              <div v-if="form.errors.normativa" class="invalid-feedback">{{ form.errors.normativa }}</div>
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

            <div class="col-12 d-flex justify-content-end gap-2">
              <Link :href="route('sicoss.importes-detraer.index')" class="btn btn-outline-secondary">Cancelar</Link>
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
