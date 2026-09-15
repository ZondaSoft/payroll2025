<script setup>
import { computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import Flash from '@/Components/Motor/Flash.vue'
import { errorValidacion } from '@/Components/Motor/ConfirmarBaja.js'

const props = defineProps({ modelo: Object, tramos: Array })
const esEdicion = computed(() => !!props.modelo)
const form = useForm({ codigo: props.modelo?.codigo ?? '', descripcion: props.modelo?.descripcion ?? '', version: props.modelo?.version ?? 1, estado: props.modelo?.estado ?? 'VIGENTE', fuente: props.modelo?.fuente ?? '', activo: props.modelo ? !!props.modelo.activo : true,
  tramos: (props.tramos?.length ? props.tramos : [{ anios_desde: 0, incluye_desde: true, anios_hasta: 5, incluye_hasta: true, dias: 14 }, { anios_desde: 5, incluye_desde: false, anios_hasta: 10, incluye_hasta: true, dias: 21 }, { anios_desde: 10, incluye_desde: false, anios_hasta: 20, incluye_hasta: true, dias: 28 }, { anios_desde: 20, incluye_desde: false, anios_hasta: '', incluye_hasta: null, dias: 35 }]).map((t) => ({ ...t, anios_hasta: t.anios_hasta ?? '' })) })
const submit = () => { const onError = (e) => errorValidacion(e); esEdicion.value ? form.put(route('motor.escalas.vacaciones.update', props.modelo.id_modelo_vacaciones), { onError }) : form.post(route('motor.escalas.vacaciones.store'), { onError }) }
</script>

<template>
  <div class="container-xxl flex-grow-1 container-p-y">
    <Flash />
    <div class="app-ecommerce">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-6 row-gap-4"><h4 class="mb-1">{{ esEdicion ? 'Editar modelo de vacaciones' : 'Nuevo modelo de vacaciones' }}</h4><Link :href="route('motor.escalas.vacaciones.index')" class="btn btn-outline-secondary"><i class="ri-arrow-left-line me-1"></i> Volver</Link></div>
      <form @submit.prevent="submit" class="row g-4">
        <div class="col-lg-4"><div class="card"><div class="card-body row g-3">
          <div class="col-12"><label class="form-label">Código *</label><input v-model="form.codigo" class="form-control font-monospace" :class="{ 'is-invalid': form.errors.codigo }" :disabled="esEdicion" style="text-transform: uppercase" /></div>
          <div class="col-12"><label class="form-label">Descripción *</label><input v-model="form.descripcion" class="form-control" /></div>
          <div class="col-6"><label class="form-label">Versión</label><input v-model="form.version" type="number" min="1" class="form-control" /></div>
          <div class="col-6"><label class="form-label">Estado</label><input v-model="form.estado" class="form-control" /></div>
          <div class="col-12"><label class="form-label">Fuente</label><input v-model="form.fuente" class="form-control" placeholder="Ley 20.744, artículo 150" /></div>
          <div class="col-12"><div class="form-check form-switch"><input id="act" v-model="form.activo" class="form-check-input" type="checkbox" /><label for="act" class="form-check-label">Activo</label></div></div>
        </div></div></div>
        <div class="col-lg-8"><div class="card">
          <div class="card-header d-flex justify-content-between align-items-center"><h5 class="card-title mb-0">Tramos</h5><button type="button" class="btn btn-sm btn-outline-primary" @click="form.tramos.push({ anios_desde: '', incluye_desde: false, anios_hasta: '', incluye_hasta: true, dias: '' })"><i class="ri-add-line"></i> Tramo</button></div>
          <div class="table-responsive"><table class="table table-sm align-middle mb-0">
            <thead class="table-light"><tr><th>Desde</th><th>Incl.</th><th>Hasta</th><th>Incl.</th><th>Días</th><th></th></tr></thead>
            <tbody><tr v-for="(t, i) in form.tramos" :key="i"><td><input v-model="t.anios_desde" type="number" step="0.01" class="form-control form-control-sm" /></td><td><input v-model="t.incluye_desde" type="checkbox" class="form-check-input" /></td><td><input v-model="t.anios_hasta" type="number" step="0.01" class="form-control form-control-sm" placeholder="abierto" /></td><td><input v-model="t.incluye_hasta" type="checkbox" class="form-check-input" /></td><td><input v-model="t.dias" type="number" min="0" class="form-control form-control-sm" /></td><td><button type="button" class="btn btn-link text-danger p-0" @click="form.tramos.splice(i, 1)"><i class="ri-close-line"></i></button></td></tr></tbody>
          </table></div>
          <div class="card-footer d-flex justify-content-end gap-2"><Link :href="route('motor.escalas.vacaciones.index')" class="btn btn-outline-secondary">Cancelar</Link><button type="submit" class="btn btn-primary" :disabled="form.processing">{{ esEdicion ? 'Actualizar' : 'Crear' }}</button></div>
        </div></div>
      </form>
    </div>
  </div>
</template>
