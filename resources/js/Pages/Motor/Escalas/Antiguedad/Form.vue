<script setup>
import { computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import Flash from '@/Components/Motor/Flash.vue'
import { errorValidacion } from '@/Components/Motor/ConfirmarBaja.js'

const props = defineProps({ modelo: Object, tramos: Array })
const esEdicion = computed(() => !!props.modelo)
const form = useForm({ codigo: props.modelo?.codigo ?? '', descripcion: props.modelo?.descripcion ?? '', metodo_calculo: props.modelo?.metodo_calculo ?? 'ESCALA', porcentaje_por_anio: props.modelo?.porcentaje_por_anio ?? '', porcentaje_maximo: props.modelo?.porcentaje_maximo ?? '', version: props.modelo?.version ?? 1, estado_validacion: props.modelo?.estado_validacion ?? 'SIN_CONFIRMAR', activo: props.modelo ? !!props.modelo.activo : true, observaciones: props.modelo?.observaciones ?? '', tramos: (props.tramos ?? []).map((t) => ({ anios_desde: t.anios_desde, anios_hasta: t.anios_hasta ?? '', porcentaje: t.porcentaje })) })
const agregar = () => { const ult = form.tramos[form.tramos.length - 1]; form.tramos.push({ anios_desde: ult ? Number(ult.anios_hasta || ult.anios_desde) + 1 : 0, anios_hasta: '', porcentaje: '' }) }
const generar = () => { form.tramos = []; for (let a = 0; a <= 20; a++) form.tramos.push({ anios_desde: a, anios_hasta: a, porcentaje: (a * 0.02).toFixed(2) }); for (let a = 21; a <= 29; a++) form.tramos.push({ anios_desde: a, anios_hasta: a, porcentaje: (0.4 + (a - 20) * 0.03).toFixed(2) }); form.tramos.push({ anios_desde: 30, anios_hasta: '', porcentaje: '0.70' }); form.porcentaje_maximo = '0.70' }
const submit = () => { const onError = (e) => errorValidacion(e); esEdicion.value ? form.put(route('motor.escalas.antiguedad.update', props.modelo.id_modelo_antiguedad), { onError }) : form.post(route('motor.escalas.antiguedad.store'), { onError }) }
</script>

<template>
  <div class="container-xxl flex-grow-1 container-p-y">
    <Flash />
    <div class="app-ecommerce">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-6 row-gap-4"><h4 class="mb-1">{{ esEdicion ? 'Editar modelo de antigüedad' : 'Nuevo modelo de antigüedad' }}</h4><Link :href="route('motor.escalas.antiguedad.index')" class="btn btn-outline-secondary"><i class="ri-arrow-left-line me-1"></i> Volver</Link></div>
      <form @submit.prevent="submit" class="row g-4">
        <div class="col-lg-4"><div class="card"><div class="card-body row g-3">
          <div class="col-12"><label class="form-label">Código *</label><input v-model="form.codigo" class="form-control font-monospace" :class="{ 'is-invalid': form.errors.codigo }" :disabled="esEdicion" style="text-transform: uppercase" /><div v-if="form.errors.codigo" class="invalid-feedback d-block">{{ form.errors.codigo }}</div></div>
          <div class="col-12"><label class="form-label">Descripción *</label><input v-model="form.descripcion" class="form-control" :class="{ 'is-invalid': form.errors.descripcion }" /></div>
          <div class="col-6"><label class="form-label">Método *</label><select v-model="form.metodo_calculo" class="form-select"><option value="ESCALA">ESCALA (tramos)</option><option value="LINEAL">LINEAL (% × años)</option></select></div>
          <div class="col-6"><label class="form-label">Versión</label><input v-model="form.version" type="number" min="1" class="form-control" /></div>
          <div class="col-6" v-if="form.metodo_calculo === 'LINEAL'"><label class="form-label">% por año * <small class="text-muted">(0.01 = 1 %)</small></label><input v-model="form.porcentaje_por_anio" type="text" inputmode="decimal" class="form-control font-monospace" /></div>
          <div class="col-6"><label class="form-label">% máximo <small class="text-muted">(vacío = sin tope)</small></label><input v-model="form.porcentaje_maximo" type="text" inputmode="decimal" class="form-control font-monospace" /></div>
          <div class="col-6"><label class="form-label">Estado</label><select v-model="form.estado_validacion" class="form-select"><option value="SIN_DATO">Sin dato</option><option value="SIN_CONFIRMAR">Sin confirmar</option><option value="CONFIRMADO">Confirmado</option></select></div>
          <div class="col-6"><div class="form-check form-switch mt-4"><input id="act" v-model="form.activo" class="form-check-input" type="checkbox" /><label for="act" class="form-check-label">Activo</label></div></div>
          <div class="col-12"><label class="form-label">Observaciones</label><textarea v-model="form.observaciones" class="form-control" rows="3"></textarea></div>
        </div></div></div>
        <div class="col-lg-8"><div class="card">
          <div class="card-header d-flex justify-content-between align-items-center"><h5 class="card-title mb-0">Tramos {{ form.metodo_calculo === 'LINEAL' ? '(no aplican a LINEAL)' : '' }}</h5><div class="btn-group btn-group-sm"><button type="button" class="btn btn-outline-secondary" @click="generar" :disabled="form.metodo_calculo === 'LINEAL'">Generar 2 %/3 % tope 70 %</button><button type="button" class="btn btn-outline-primary" @click="agregar" :disabled="form.metodo_calculo === 'LINEAL'"><i class="ri-add-line"></i> Tramo</button></div></div>
          <div class="table-responsive"><table class="table table-sm align-middle mb-0">
            <thead class="table-light"><tr><th>#</th><th>Años desde</th><th>Años hasta <small class="text-muted">(vacío = abierto)</small></th><th>Porcentaje (fracción)</th><th></th></tr></thead>
            <tbody>
              <tr v-if="!form.tramos.length"><td colspan="5" class="text-center text-muted py-3">Sin tramos.</td></tr>
              <tr v-for="(t, i) in form.tramos" :key="i"><td>{{ i + 1 }}</td><td><input v-model="t.anios_desde" type="number" step="0.01" min="0" class="form-control form-control-sm" /></td><td><input v-model="t.anios_hasta" type="number" step="0.01" min="0" class="form-control form-control-sm" /></td><td><input v-model="t.porcentaje" type="text" inputmode="decimal" class="form-control form-control-sm font-monospace" placeholder="0.02" /></td><td><button type="button" class="btn btn-link text-danger p-0" @click="form.tramos.splice(i, 1)"><i class="ri-close-line"></i></button></td></tr>
            </tbody>
          </table></div>
          <div class="card-footer d-flex justify-content-end gap-2"><Link :href="route('motor.escalas.antiguedad.index')" class="btn btn-outline-secondary">Cancelar</Link><button type="submit" class="btn btn-primary" :disabled="form.processing">{{ esEdicion ? 'Actualizar' : 'Crear' }}</button></div>
        </div></div>
      </form>
    </div>
  </div>
</template>
