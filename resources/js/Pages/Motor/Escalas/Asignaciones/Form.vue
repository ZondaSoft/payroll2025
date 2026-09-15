<script setup>
import { computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import Flash from '@/Components/Motor/Flash.vue'
import { errorValidacion } from '@/Components/Motor/ConfirmarBaja.js'

const props = defineProps({ asignacion: Object, tipo: String, convenios: Array, modelos_antiguedad: Array, modelos_vacaciones: Array })
const esEdicion = computed(() => !!props.asignacion)
const a = props.asignacion
const form = useForm({ tipo: props.tipo, id_convenio: a?.id_convenio ?? '', id_modelo: a?.id_modelo_antiguedad ?? a?.id_modelo_vacaciones ?? '', vigencia_desde: a?.vigencia_desde ?? new Date().toISOString().substring(0, 8) + '01', vigencia_hasta: a?.vigencia_hasta ?? '', estado_validacion: a?.estado_validacion ?? 'SIN_CONFIRMAR', fuente: a?.fuente ?? '' })
const modelos = computed(() => (form.tipo === 'VACACIONES' ? props.modelos_vacaciones : props.modelos_antiguedad))
const id = a?.id_asignacion_antiguedad ?? a?.id_asignacion_vacaciones
const submit = () => { const onError = (e) => errorValidacion(e); esEdicion.value ? form.put(route('motor.escalas.asignaciones.update', id), { onError }) : form.post(route('motor.escalas.asignaciones.store'), { onError }) }
</script>

<template>
  <div class="container-xxl flex-grow-1 container-p-y">
    <Flash />
    <div class="app-ecommerce">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-6 row-gap-4"><h4 class="mb-1">{{ esEdicion ? 'Editar asignación' : 'Nueva asignación' }} — {{ form.tipo === 'VACACIONES' ? 'vacaciones' : 'antigüedad' }}</h4><Link :href="route('motor.escalas.asignaciones.index')" class="btn btn-outline-secondary"><i class="ri-arrow-left-line me-1"></i> Volver</Link></div>
      <div class="card"><div class="card-body"><form @submit.prevent="submit" class="row g-3">
        <div class="col-md-3" v-if="!esEdicion"><label class="form-label">Tipo</label><select v-model="form.tipo" class="form-select" @change="form.id_modelo = ''"><option value="ANTIGUEDAD">Antigüedad</option><option value="VACACIONES">Vacaciones</option></select></div>
        <div class="col-md-4"><label class="form-label">Convenio *</label><select v-model="form.id_convenio" class="form-select" :class="{ 'is-invalid': form.errors.id_convenio }"><option value="">—</option><option v-for="c in convenios" :key="c.value" :value="c.value">{{ c.label }}</option></select></div>
        <div class="col-md-5"><label class="form-label">Modelo *</label><select v-model="form.id_modelo" class="form-select" :class="{ 'is-invalid': form.errors.id_modelo }"><option value="">—</option><option v-for="m in modelos" :key="m.value" :value="m.value">{{ m.label }}</option></select></div>
        <div class="col-md-3"><label class="form-label">Vigencia desde *</label><input v-model="form.vigencia_desde" type="date" class="form-control" /></div>
        <div class="col-md-3"><label class="form-label">Vigencia hasta</label><input v-model="form.vigencia_hasta" type="date" class="form-control" /></div>
        <div class="col-md-3" v-if="form.tipo === 'ANTIGUEDAD'"><label class="form-label">Estado</label><select v-model="form.estado_validacion" class="form-select"><option value="SIN_DATO">Sin dato</option><option value="SIN_CONFIRMAR">Sin confirmar</option><option value="CONFIRMADO">Confirmado</option></select></div>
        <div class="col-md-6" v-else><label class="form-label">Fuente</label><input v-model="form.fuente" class="form-control" /></div>
        <div class="col-12 d-flex justify-content-end gap-2"><Link :href="route('motor.escalas.asignaciones.index')" class="btn btn-outline-secondary">Cancelar</Link><button type="submit" class="btn btn-primary" :disabled="form.processing">{{ esEdicion ? 'Actualizar' : 'Crear' }}</button></div>
      </form></div></div>
    </div>
  </div>
</template>
