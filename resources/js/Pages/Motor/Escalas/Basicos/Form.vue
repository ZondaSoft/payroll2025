<script setup>
import { computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import FloatMultiselect from '@/Components/FloatMultiselect.vue'
import Flash from '@/Components/Motor/Flash.vue'
import { errorValidacion } from '@/Components/Motor/ConfirmarBaja.js'

const props = defineProps({ basico: Object, categorias: Array })
const esEdicion = computed(() => !!props.basico)
const form = useForm({ id_categoria: props.basico?.id_categoria ?? '', vigencia_desde: props.basico?.vigencia_desde ?? new Date().toISOString().substring(0, 8) + '01', vigencia_hasta: props.basico?.vigencia_hasta ?? '', importe_basico: props.basico?.importe_basico ?? '', estado_validacion: props.basico?.estado_validacion ?? 'SIN_CONFIRMAR', fuente_normativa: props.basico?.fuente_normativa ?? '' })
const submit = () => { const onError = (e) => errorValidacion(e); esEdicion.value ? form.put(route('motor.escalas.basicos.update', props.basico.id_basico), { onError }) : form.post(route('motor.escalas.basicos.store'), { onError }) }
</script>

<template>
  <div class="container-xxl flex-grow-1 container-p-y">
    <Flash />
    <div class="app-ecommerce">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-6 row-gap-4"><h4 class="mb-1">{{ esEdicion ? 'Editar básico' : 'Nuevo básico' }}</h4><Link :href="route('motor.escalas.basicos.index')" class="btn btn-outline-secondary"><i class="ri-arrow-left-line me-1"></i> Volver</Link></div>
      <div class="card"><div class="card-body"><form @submit.prevent="submit" class="row g-3">
        <div class="col-md-6"><FloatMultiselect id="cat" label="Categoría (sue006s) *" :options="categorias" v-model="form.id_categoria" :error="form.errors.id_categoria" /></div>
        <div class="col-md-3"><label class="form-label">Importe básico *</label><input v-model="form.importe_basico" type="text" inputmode="decimal" class="form-control font-monospace" :class="{ 'is-invalid': form.errors.importe_basico }" placeholder="1335827.18" /><div v-if="form.errors.importe_basico" class="invalid-feedback d-block">{{ form.errors.importe_basico }}</div></div>
        <div class="col-md-3"><label class="form-label">Estado de validación</label><select v-model="form.estado_validacion" class="form-select"><option value="SIN_DATO">Sin dato</option><option value="SIN_CONFIRMAR">Sin confirmar</option><option value="CONFIRMADO">Confirmado</option></select></div>
        <div class="col-md-3"><label class="form-label">Vigencia desde *</label><input v-model="form.vigencia_desde" type="date" class="form-control" :class="{ 'is-invalid': form.errors.vigencia_desde }" /></div>
        <div class="col-md-3"><label class="form-label">Vigencia hasta</label><input v-model="form.vigencia_hasta" type="date" class="form-control" :class="{ 'is-invalid': form.errors.vigencia_hasta }" /><small class="text-muted">RT-007: sin superposición por categoría.</small></div>
        <div class="col-md-6"><label class="form-label">Fuente normativa</label><input v-model="form.fuente_normativa" class="form-control" placeholder="CCT 371/03 — escala vigente desde 07/2026" /></div>
        <div class="col-12 d-flex justify-content-end gap-2"><Link :href="route('motor.escalas.basicos.index')" class="btn btn-outline-secondary">Cancelar</Link><button type="submit" class="btn btn-primary" :disabled="form.processing">{{ esEdicion ? 'Actualizar' : 'Crear' }}</button></div>
      </form></div></div>
    </div>
  </div>
</template>
