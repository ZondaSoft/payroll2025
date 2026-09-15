<script setup>
import { computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import Flash from '@/Components/Motor/Flash.vue'
import { errorValidacion } from '@/Components/Motor/ConfirmarBaja.js'

const props = defineProps({ item: Object, empresas: Array })
const esEdicion = computed(() => !!props.item)
const i = props.item
const form = useForm({ empresa_id: i?.empresa_id ?? '', encuadramiento: i?.encuadramiento ?? 'B', detalle: i?.detalle ?? '', vigencia_desde: i?.vigencia_desde ?? '2026-01-01', vigencia_hasta: i?.vigencia_hasta ?? '', certificado_mipyme: i?.certificado_mipyme ?? '', vencimiento_certificado: i?.vencimiento_certificado ?? '', origen: i?.origen ?? '', documento_respaldo: i?.documento_respaldo ?? '', estado_validacion: i?.estado_validacion ?? 'SIN_CONFIRMAR' })
const submit = () => { const onError = (e) => errorValidacion(e); esEdicion.value ? form.put(route('motor.encuadramientos.update', i.encuadramiento_id), { onError }) : form.post(route('motor.encuadramientos.store'), { onError }) }
</script>

<template>
  <div class="container-xxl flex-grow-1 container-p-y">
    <Flash />
    <div class="app-ecommerce">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-6 row-gap-4"><h4 class="mb-1">{{ esEdicion ? 'Editar encuadramiento' : 'Registrar encuadramiento' }}</h4><Link :href="route('motor.encuadramientos.index')" class="btn btn-outline-secondary"><i class="ri-arrow-left-line me-1"></i> Volver</Link></div>
      <div class="card"><div class="card-body"><form @submit.prevent="submit" class="row g-3">
        <div class="col-md-5"><label class="form-label">Empresa *</label><select v-model="form.empresa_id" class="form-select" :class="{ 'is-invalid': form.errors.empresa_id }"><option value="">—</option><option v-for="e in empresas" :key="e.value" :value="e.value">{{ e.label }}</option></select><div v-if="form.errors.empresa_id" class="invalid-feedback d-block">{{ form.errors.empresa_id }}</div></div>
        <div class="col-md-3"><label class="form-label">Encuadramiento *</label><select v-model="form.encuadramiento" class="form-select"><option value="A">A — supera Mediana Tramo 2 (20,40 %)</option><option value="B">B — restantes empleadores (18 %)</option></select></div>
        <div class="col-md-4"><label class="form-label">Estado</label><select v-model="form.estado_validacion" class="form-select"><option value="SIN_DATO">Sin dato</option><option value="SIN_CONFIRMAR">Sin confirmar</option><option value="CONFIRMADO">Confirmado</option></select></div>
        <div class="col-md-3"><label class="form-label">Vigencia desde *</label><input v-model="form.vigencia_desde" type="date" class="form-control" :class="{ 'is-invalid': form.errors.vigencia_desde }" /></div>
        <div class="col-md-3"><label class="form-label">Vigencia hasta</label><input v-model="form.vigencia_hasta" type="date" class="form-control" /></div>
        <div class="col-md-6"><label class="form-label">Detalle</label><input v-model="form.detalle" class="form-control" /></div>
        <div class="col-md-4"><label class="form-label">Certificado MiPyME</label><input v-model="form.certificado_mipyme" class="form-control" /></div>
        <div class="col-md-2"><label class="form-label">Vencimiento</label><input v-model="form.vencimiento_certificado" type="date" class="form-control" /></div>
        <div class="col-md-3"><label class="form-label">Origen</label><input v-model="form.origen" class="form-control" /></div>
        <div class="col-md-3"><label class="form-label">Documento de respaldo</label><input v-model="form.documento_respaldo" class="form-control" /></div>
        <div class="col-12 d-flex justify-content-end gap-2"><Link :href="route('motor.encuadramientos.index')" class="btn btn-outline-secondary">Cancelar</Link><button type="submit" class="btn btn-primary" :disabled="form.processing">{{ esEdicion ? 'Actualizar' : 'Registrar' }}</button></div>
      </form></div></div>
    </div>
  </div>
</template>
