<script setup>
import { computed, watch } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import FloatMultiselect from '@/Components/FloatMultiselect.vue'
import Flash from '@/Components/Motor/Flash.vue'
import { errorValidacion } from '@/Components/Motor/ConfirmarBaja.js'

const props = defineProps({ item: Object, preseleccion: Object, tipos: Array, columnas: Array, catalogo_legacy: Array })
const esEdicion = computed(() => !!props.item)
const form = useForm({ cod_nov: props.item?.cod_nov ?? props.preseleccion?.cod_nov ?? '', columna_origen: props.item?.columna_origen ?? 'cantidad', codigo_tipo_novedad: props.item?.codigo_tipo_novedad ?? '', unidad: props.item?.unidad ?? '', es_importe: !!props.item?.es_importe, factor: props.item?.factor ?? '1', accion: props.item?.accion ?? 'NORMALIZAR', activo: props.item ? !!props.item.activo : true, observaciones: props.item?.observaciones ?? '' })
watch(() => form.codigo_tipo_novedad, (v) => { const t = props.tipos.find((x) => x.value === v); if (t && !form.unidad) form.unidad = t.unidad; if (t) form.es_importe = t.unidad === 'IMPORTE' })
const submit = () => { const onError = (e) => errorValidacion(e); esEdicion.value ? form.put(route('motor.novedades-mapeo.update', props.item.id_mapeo), { onError }) : form.post(route('motor.novedades-mapeo.store'), { onError }) }
</script>

<template>
  <div class="container-xxl flex-grow-1 container-p-y">
    <Flash />
    <div class="app-ecommerce">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-6 row-gap-4"><h4 class="mb-1">{{ esEdicion ? 'Editar mapeo' : 'Nuevo mapeo de novedad' }}</h4><Link :href="route('motor.novedades-mapeo.index')" class="btn btn-outline-secondary"><i class="ri-arrow-left-line me-1"></i> Volver</Link></div>
      <div class="card"><div class="card-body"><form @submit.prevent="submit" class="row g-3">
        <div class="col-md-5"><FloatMultiselect id="cod" label="Código de novedad legacy (sue031s) *" :options="catalogo_legacy" v-model="form.cod_nov" :disabled="esEdicion" :error="form.errors.cod_nov" /></div>
        <div class="col-md-3"><label class="form-label">Columna de origen *</label><select v-model="form.columna_origen" class="form-select" :disabled="esEdicion"><option v-for="c in columnas" :key="c" :value="c">{{ c }}</option></select><small class="text-muted">sue028s.cantidad o los contadores int_*.</small></div>
        <div class="col-md-4"><label class="form-label">Acción *</label><select v-model="form.accion" class="form-select"><option value="NORMALIZAR">NORMALIZAR → crea la novedad en el motor</option><option value="IGNORAR">IGNORAR → no interesa al motor (deja de aparecer como sin mapeo)</option></select></div>
        <template v-if="form.accion === 'NORMALIZAR'">
          <div class="col-md-5"><FloatMultiselect id="tipo" label="Tipo de novedad del motor *" :options="tipos" v-model="form.codigo_tipo_novedad" :error="form.errors.codigo_tipo_novedad" /></div>
          <div class="col-md-2"><label class="form-label">Unidad</label><input v-model="form.unidad" class="form-control" /></div>
          <div class="col-md-2"><label class="form-label">Factor</label><input v-model="form.factor" type="text" inputmode="decimal" class="form-control font-monospace" /><small class="text-muted">× cantidad origen</small></div>
          <div class="col-md-3"><div class="form-check form-switch mt-4"><input id="imp" v-model="form.es_importe" class="form-check-input" type="checkbox" /><label for="imp" class="form-check-label">La cantidad de origen son <b>pesos</b> (importe)</label></div></div>
        </template>
        <div class="col-md-9"><label class="form-label">Observaciones</label><input v-model="form.observaciones" class="form-control" /></div>
        <div class="col-md-3"><div class="form-check form-switch mt-4"><input id="act" v-model="form.activo" class="form-check-input" type="checkbox" /><label for="act" class="form-check-label">Activo</label></div></div>
        <div class="col-12 d-flex justify-content-end gap-2"><Link :href="route('motor.novedades-mapeo.index')" class="btn btn-outline-secondary">Cancelar</Link><button type="submit" class="btn btn-primary" :disabled="form.processing">{{ esEdicion ? 'Actualizar' : 'Crear' }}</button></div>
      </form></div></div>
    </div>
  </div>
</template>
