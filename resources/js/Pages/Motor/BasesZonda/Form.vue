<script setup>
import { computed, ref } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import Flash from '@/Components/Motor/Flash.vue'
import { errorValidacion } from '@/Components/Motor/ConfirmarBaja.js'

const props = defineProps({ base: Object, composicion: Array, conceptos: Array })
const esEdicion = computed(() => !!props.base)
const form = useForm({
  codigo: props.base?.codigo ?? '', nombre: props.base?.nombre ?? '', descripcion: props.base?.descripcion ?? '', activo: props.base ? props.base.activo : true, estado_validacion: props.base?.estado_validacion ?? 'SIN_CONFIRMAR',
  composicion: (props.composicion ?? []).map((c) => ({ ...c })),
})
const filtro = ref('')
const candidatos = computed(() => props.conceptos.filter((c) => !form.composicion.some((x) => x.codigo_concepto === c.codigo) && (!filtro.value || (c.codigo + ' ' + c.nombre).toLowerCase().includes(filtro.value.toLowerCase()))))
const consumidores = computed(() => props.conceptos.filter((c) => c.consume === form.codigo).map((c) => c.codigo))
const agregar = (c) => form.composicion.push({ id: null, codigo_concepto: c.codigo, integra: true, desde: new Date().toISOString().substring(0, 8) + '01', hasta: '' })
const quitar = (i) => form.composicion.splice(i, 1)
const circular = (codigo) => consumidores.value.includes(codigo)
const submit = () => {
  const onError = (e) => errorValidacion(e)
  esEdicion.value ? form.put(route('motor.bases-zonda.update', props.base.id), { onError }) : form.post(route('motor.bases-zonda.store'), { onError })
}
</script>

<template>
  <div class="container-xxl flex-grow-1 container-p-y">
    <Flash />
    <div class="app-ecommerce">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-6 row-gap-4">
        <div><h4 class="mb-1">{{ esEdicion ? 'Editar base' : 'Nueva base de cálculo' }}</h4><p class="mb-0 text-muted">La identidad de una base es estable; lo que cambia con el tiempo es su composición (con vigencia).</p></div>
        <Link :href="route('motor.bases-zonda.index')" class="btn btn-outline-secondary"><i class="ri-arrow-left-line me-1"></i> Volver</Link>
      </div>
      <form @submit.prevent="submit" class="row g-4">
        <div class="col-lg-4">
          <div class="card"><div class="card-body row g-3">
            <div class="col-12"><label class="form-label">Código *</label><input v-model="form.codigo" class="form-control font-monospace" :class="{ 'is-invalid': form.errors.codigo }" :disabled="esEdicion" style="text-transform: uppercase" /><div v-if="form.errors.codigo" class="invalid-feedback d-block">{{ form.errors.codigo }}</div><small class="text-muted">Lo referencian las estrategias como BASE(codigo): no se renombra.</small></div>
            <div class="col-12"><label class="form-label">Nombre *</label><input v-model="form.nombre" class="form-control" :class="{ 'is-invalid': form.errors.nombre }" /><div v-if="form.errors.nombre" class="invalid-feedback d-block">{{ form.errors.nombre }}</div></div>
            <div class="col-12"><label class="form-label">Descripción</label><textarea v-model="form.descripcion" class="form-control" rows="4"></textarea></div>
            <div class="col-6"><label class="form-label">Estado de validación</label><select v-model="form.estado_validacion" class="form-select"><option value="SIN_DATO">Sin dato</option><option value="SIN_CONFIRMAR">Sin confirmar</option><option value="CONFIRMADO">Confirmado</option></select></div>
            <div class="col-6"><div class="form-check form-switch mt-4"><input id="activo" v-model="form.activo" class="form-check-input" type="checkbox" /><label for="activo" class="form-check-label">Activa</label></div></div>
            <div class="col-12" v-if="consumidores.length"><small class="text-muted">La consumen:</small> <span v-for="c in consumidores" :key="c" class="badge bg-label-info font-monospace me-1">{{ c }}</span></div>
          </div></div>
        </div>
        <div class="col-lg-8">
          <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Composición — qué conceptos arman esta base</h5></div>
            <div class="card-body">
              <div class="alert alert-danger small py-2" v-if="form.composicion.some((c) => circular(c.codigo_concepto))"><b>R13:</b> un concepto que consume esta base no puede integrarla.</div>
              <table class="table table-sm align-middle">
                <thead class="table-light"><tr><th>Concepto</th><th>Integra</th><th>Desde</th><th>Hasta</th><th></th></tr></thead>
                <tbody>
                  <tr v-if="!form.composicion.length"><td colspan="5" class="text-center text-muted py-3">Sin conceptos. Agregá desde la lista de abajo.</td></tr>
                  <tr v-for="(c, i) in form.composicion" :key="c.codigo_concepto" :class="{ 'table-danger': circular(c.codigo_concepto) }">
                    <td class="font-monospace fw-bold">{{ c.codigo_concepto }}</td>
                    <td><select v-model="c.integra" class="form-select form-select-sm"><option :value="true">S — integra</option><option :value="false">N — excluido explícitamente</option></select></td>
                    <td><input v-model="c.desde" type="date" class="form-control form-control-sm" /></td><td><input v-model="c.hasta" type="date" class="form-control form-control-sm" /></td>
                    <td><button type="button" class="btn btn-link text-danger p-0" @click="quitar(i)"><i class="ri-close-line"></i></button></td>
                  </tr>
                </tbody>
              </table>
              <div class="d-flex gap-2 align-items-center mb-2"><input v-model="filtro" class="form-control form-control-sm" style="width: 260px" placeholder="Buscar concepto para agregar…" /><small class="text-muted">{{ candidatos.length }} disponibles</small></div>
              <div class="d-flex flex-wrap gap-1" style="max-height: 180px; overflow: auto">
                <button v-for="c in candidatos" :key="c.codigo" type="button" class="btn btn-sm" :class="circular(c.codigo) ? 'btn-outline-danger' : c.naturaleza === 'REMUNERATIVO' ? 'btn-outline-primary' : 'btn-outline-secondary'" :title="c.nombre + (circular(c.codigo) ? ' — consume esta base (R13)' : '')" @click="agregar(c)">{{ c.codigo }}</button>
              </div>
            </div>
            <div class="card-footer d-flex justify-content-end gap-2">
              <Link :href="route('motor.bases-zonda.index')" class="btn btn-outline-secondary">Cancelar</Link>
              <button type="submit" class="btn btn-primary" :disabled="form.processing"><span v-if="!form.processing">{{ esEdicion ? 'Actualizar' : 'Crear' }}</span><span v-else><span class="spinner-border spinner-border-sm me-2"></span>Guardando...</span></button>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>
