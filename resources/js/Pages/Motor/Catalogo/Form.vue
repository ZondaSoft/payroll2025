<script setup>
import { computed, onMounted, ref } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import Flash from '@/Components/Motor/Flash.vue'
import { errorValidacion } from '@/Components/Motor/ConfirmarBaja.js'

const props = defineProps({ definicion: Object, item: Object, opciones: Object })
const esEdicion = computed(() => !!props.item)
const claveDe = () => props.item?.[Object.keys(props.item)[0]]

const inicial = {}
for (const c of props.definicion.campos) {
  const v = props.item?.[c.nombre]
  inicial[c.nombre] = v !== undefined && v !== null ? (c.tipo === 'booleano' ? !!v : c.tipo === 'fecha' ? String(v).substring(0, 10) : v) : (c.default ?? (c.tipo === 'booleano' ? false : ''))
}
const form = useForm(inicial)
const primero = ref(null)
onMounted(() => primero.value?.focus?.())

const editable = (c) => {
  if (esEdicion.value && c.soloLectura) return false
  if (esEdicion.value && props.definicion.cerrado && !props.definicion.editables_cerrado.includes(c.nombre)) return false
  return true
}
const opcionesDe = (c) => c.opciones || props.opciones?.[c.nombre] || []

const submit = () => {
  const onError = (e) => errorValidacion(e)
  if (esEdicion.value) form.put(route(props.definicion.ruta + '.update', claveDe()), { onError })
  else form.post(route(props.definicion.ruta + '.store'), { onError })
}
</script>

<template>
  <div class="container-xxl flex-grow-1 container-p-y">
    <Flash />
    <div class="app-ecommerce">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-6 row-gap-4">
        <div>
          <h4 class="mb-1">{{ esEdicion ? 'Editar' : 'Nuevo' }} {{ definicion.titulo.toLowerCase() }}</h4>
          <p class="mb-0 text-muted">{{ definicion.descripcion }}</p>
        </div>
        <Link :href="route(definicion.ruta + '.index')" class="btn btn-outline-secondary"><i class="ri-arrow-left-line me-1"></i> Volver</Link>
      </div>

      <div v-if="esEdicion && definicion.cerrado" class="alert alert-warning d-flex align-items-center mb-4" role="alert">
        <i class="ri-lock-line ri-22px me-2"></i>
        <span>Catálogo cerrado: solo se pueden editar {{ definicion.editables_cerrado.join(', ') }}. Los códigos oficiales no se renombran.</span>
      </div>

      <div class="card">
        <div class="card-body">
          <form class="row g-3" @submit.prevent="submit">
            <template v-for="(c, i) in definicion.campos" :key="c.nombre">
              <div :class="'col-md-' + (c.col || 6)">
                <label :for="c.nombre" class="form-label">{{ c.etiqueta }}<span v-if="c.obligatorio"> *</span></label>

                <div v-if="c.tipo === 'booleano'" class="form-check form-switch mt-2">
                  <input :id="c.nombre" v-model="form[c.nombre]" class="form-check-input" type="checkbox" :disabled="!editable(c)" />
                  <label class="form-check-label" :for="c.nombre">{{ form[c.nombre] ? 'Sí' : 'No' }}</label>
                </div>
                <select v-else-if="c.tipo === 'estado_validacion'" :id="c.nombre" v-model="form[c.nombre]" class="form-select" :class="{ 'is-invalid': form.errors[c.nombre] }" :disabled="!editable(c)">
                  <option value="SIN_DATO">Sin dato</option>
                  <option value="SIN_CONFIRMAR">Sin confirmar</option>
                  <option value="CONFIRMADO">Confirmado</option>
                </select>
                <select v-else-if="c.tipo === 'select'" :id="c.nombre" v-model="form[c.nombre]" class="form-select" :class="{ 'is-invalid': form.errors[c.nombre] }" :disabled="!editable(c)">
                  <option v-if="!c.obligatorio" value="">— (vacío) —</option>
                  <option v-for="o in opcionesDe(c)" :key="o.value" :value="o.value">{{ o.label }}</option>
                </select>
                <textarea v-else-if="c.tipo === 'texto_largo'" :id="c.nombre" v-model="form[c.nombre]" class="form-control" rows="3" :class="{ 'is-invalid': form.errors[c.nombre] }" :disabled="!editable(c)"></textarea>
                <input v-else-if="c.tipo === 'fecha'" :id="c.nombre" v-model="form[c.nombre]" type="date" class="form-control" :class="{ 'is-invalid': form.errors[c.nombre] }" :disabled="!editable(c)" />
                <input v-else-if="c.tipo === 'periodo'" :id="c.nombre" v-model="form[c.nombre]" type="month" class="form-control" :class="{ 'is-invalid': form.errors[c.nombre] }" :disabled="!editable(c)" />
                <input v-else-if="c.tipo === 'entero'" :id="c.nombre" v-model="form[c.nombre]" type="number" step="1" class="form-control" :class="{ 'is-invalid': form.errors[c.nombre] }" :disabled="!editable(c)" />
                <input v-else :id="c.nombre" :ref="i === 0 ? 'primero' : undefined" v-model="form[c.nombre]" type="text" :inputmode="['decimal', 'porcentaje'].includes(c.tipo) ? 'decimal' : undefined" class="form-control" :class="{ 'is-invalid': form.errors[c.nombre], 'font-monospace': c.nombre === definicion.clave_natural }" :disabled="!editable(c)" />

                <div v-if="form.errors[c.nombre]" class="invalid-feedback d-block">{{ form.errors[c.nombre] }}</div>
                <small v-if="c.ayuda" class="text-muted">{{ c.ayuda }}</small>
              </div>
            </template>

            <div class="col-12 d-flex justify-content-end gap-2">
              <Link :href="route(definicion.ruta + '.index')" class="btn btn-outline-secondary">Cancelar</Link>
              <button type="submit" class="btn btn-primary" :disabled="form.processing">
                <span v-if="!form.processing">{{ esEdicion ? 'Actualizar' : 'Crear' }}</span>
                <span v-else><span class="spinner-border spinner-border-sm me-2"></span>Guardando...</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>
