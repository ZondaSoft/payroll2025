<script setup>
import { computed, reactive, ref } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import axios from 'axios'
import Swal from 'sweetalert2'
import Flash from '@/Components/Motor/Flash.vue'
import EstadoValidacionBadge from '@/Components/Motor/EstadoValidacionBadge.vue'
import { confirmar, errorValidacion } from '@/Components/Motor/ConfirmarBaja.js'
import { fecha } from '@/Components/Motor/formato.js'

const props = defineProps({ parametro: Object, valores: Array, catalogos: Object })
const esEdicion = computed(() => !!props.parametro)
const form = useForm({ codigo: props.parametro?.codigo ?? '', descripcion: props.parametro?.descripcion ?? '', tipo_parametro: props.parametro?.tipo_parametro ?? 'PORCENTAJE', unidad: props.parametro?.unidad ?? '', es_tecnico: !!props.parametro?.es_tecnico, activo: props.parametro ? props.parametro.activo : true })
const submit = () => { const onError = (e) => errorValidacion(e); esEdicion.value ? form.put(route('motor.parametros.update', props.parametro.id), { onError }) : form.post(route('motor.parametros.store'), { onError }) }

const nuevo = () => ({ id: null, alcance: 'GENERAL', id_convenio: '', id_categoria: '', id_empresa: '', relacion_laboral_id: '', valor: '', desde: new Date().toISOString().substring(0, 8) + '01', hasta: '', estado: 'SIN_CONFIRMAR' })
const editando = ref(null)
const fila = reactive(nuevo())
const editar = (v) => { editando.value = v.id; Object.assign(fila, { ...v, hasta: v.hasta ?? '', id_convenio: v.id_convenio ?? '', id_categoria: v.id_categoria ?? '', id_empresa: v.id_empresa ?? '', relacion_laboral_id: v.relacion_laboral_id ?? '' }) }
const cancelar = () => { editando.value = null; Object.assign(fila, nuevo()) }
const guardarValor = async () => {
  try {
    await axios.post(route('motor.parametros.valores.store', props.parametro.id), fila)
    Swal.fire({ icon: 'success', title: 'Valor guardado', toast: true, position: 'top-end', timer: 1500, showConfirmButton: false })
    cancelar(); router.reload({ only: ['valores'] })
  } catch (e) { const d = e.response?.data; Swal.fire({ icon: 'error', title: 'No se pudo guardar', html: d?.errors ? Object.values(d.errors).flat().join('<br>') : d?.message || e.message }) }
}
const eliminarValor = async (v) => {
  if (!(await confirmar({ titulo: '¿Eliminar este valor?', html: 'Si una liquidación histórica lo usó, corresponde cerrar la vigencia en lugar de borrarlo (RT-006).', confirmar: 'Sí, eliminar', peligro: true }))) return
  await axios.delete(route('motor.parametros.valores.destroy', [props.parametro.id, v.id])); router.reload({ only: ['valores'] })
}
const etiqueta = (v) => { const o = (l, k) => props.catalogos[l]?.find((x) => String(x.value) === String(k))?.label ?? k; return v.alcance === 'CONVENIO' ? o('convenios', v.id_convenio) : v.alcance === 'CATEGORIA' ? o('categorias', v.id_categoria) : v.alcance === 'EMPRESA' ? o('empresas', v.id_empresa) : v.alcance === 'TRABAJADOR' ? o('relaciones', v.relacion_laboral_id) : 'Todos' }
</script>

<template>
  <div class="container-xxl flex-grow-1 container-p-y">
    <Flash />
    <div class="app-ecommerce">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-6 row-gap-4">
        <div><h4 class="mb-1">{{ esEdicion ? 'Editar parámetro' : 'Nuevo parámetro' }} <span v-if="esEdicion" class="font-monospace text-muted">{{ parametro.codigo }}</span></h4><p class="mb-0 text-muted">Un valor por ámbito y vigencia. Dos vigentes en el mismo nivel = empate = bloqueo.</p></div>
        <Link :href="route('motor.parametros.index')" class="btn btn-outline-secondary"><i class="ri-arrow-left-line me-1"></i> Volver</Link>
      </div>
      <div class="row g-4">
        <div class="col-lg-4">
          <form @submit.prevent="submit" class="card"><div class="card-body row g-3">
            <div class="col-12"><label class="form-label">Código *</label><input v-model="form.codigo" class="form-control font-monospace" :class="{ 'is-invalid': form.errors.codigo }" :disabled="esEdicion" style="text-transform: uppercase" /><div v-if="form.errors.codigo" class="invalid-feedback d-block">{{ form.errors.codigo }}</div></div>
            <div class="col-12"><label class="form-label">Descripción *</label><input v-model="form.descripcion" class="form-control" :class="{ 'is-invalid': form.errors.descripcion }" /><div v-if="form.errors.descripcion" class="invalid-feedback d-block">{{ form.errors.descripcion }}</div></div>
            <div class="col-6"><label class="form-label">Tipo</label><select v-model="form.tipo_parametro" class="form-select"><option v-for="t in catalogos.tipos" :key="t.value" :value="t.value">{{ t.label }}</option></select></div>
            <div class="col-6"><label class="form-label">Unidad</label><input v-model="form.unidad" class="form-control" placeholder="%, ARS/KM, HORAS" /></div>
            <div class="col-6"><div class="form-check form-switch"><input id="tec" v-model="form.es_tecnico" class="form-check-input" type="checkbox" /><label for="tec" class="form-check-label">Técnico del motor</label></div></div>
            <div class="col-6"><div class="form-check form-switch"><input id="act" v-model="form.activo" class="form-check-input" type="checkbox" /><label for="act" class="form-check-label">Activo</label></div></div>
            <div class="col-12 d-flex justify-content-end gap-2"><Link :href="route('motor.parametros.index')" class="btn btn-outline-secondary">Cancelar</Link><button type="submit" class="btn btn-primary" :disabled="form.processing">{{ esEdicion ? 'Actualizar' : 'Crear' }}</button></div>
          </div></form>
        </div>
        <div class="col-lg-8" v-if="esEdicion">
          <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Valores por alcance y vigencia</h5></div>
            <div class="table-responsive"><table class="table table-sm align-middle mb-0">
              <thead class="table-light"><tr><th>Alcance</th><th>Ámbito</th><th class="text-end">Valor</th><th>Vigencia</th><th>Estado</th><th></th></tr></thead>
              <tbody>
                <tr v-if="!valores.length"><td colspan="6" class="text-center text-muted py-3">Sin valores: el motor bloquea (PARAMETRO_SIN_VALOR) cuando un concepto lo necesite.</td></tr>
                <tr v-for="v in valores" :key="v.id" :class="{ 'table-warning': editando === v.id }"><td><span class="badge bg-label-secondary">{{ v.alcance }}</span></td><td class="small">{{ etiqueta(v) }}</td><td class="text-end font-monospace">{{ v.valor }}</td><td class="small">{{ fecha(v.desde) }} → {{ v.hasta ? fecha(v.hasta) : '∞' }}</td><td><EstadoValidacionBadge :estado="v.estado" small /></td>
                  <td class="text-end"><button type="button" class="btn btn-link text-warning p-0 me-2" @click="editar(v)"><i class="ri-edit-line"></i></button><button type="button" class="btn btn-link text-danger p-0" @click="eliminarValor(v)"><i class="ri-delete-bin-line"></i></button></td></tr>
              </tbody>
            </table></div>
            <div class="card-body border-top">
              <h6 class="mb-3">{{ editando ? 'Editar valor' : 'Nuevo valor' }}</h6>
              <div class="row g-2 align-items-end">
                <div class="col-md-3"><label class="form-label">Alcance</label><select v-model="fila.alcance" class="form-select form-select-sm"><option v-for="a in catalogos.alcances" :key="a.value" :value="a.value">{{ a.label }}</option></select></div>
                <div class="col-md-5">
                  <label class="form-label">Ámbito</label>
                  <select v-if="fila.alcance === 'CONVENIO'" v-model="fila.id_convenio" class="form-select form-select-sm"><option value="">—</option><option v-for="o in catalogos.convenios" :key="o.value" :value="o.value">{{ o.label }}</option></select>
                  <select v-else-if="fila.alcance === 'CATEGORIA'" v-model="fila.id_categoria" class="form-select form-select-sm"><option value="">—</option><option v-for="o in catalogos.categorias" :key="o.value" :value="o.value">{{ o.label }}</option></select>
                  <select v-else-if="fila.alcance === 'EMPRESA'" v-model="fila.id_empresa" class="form-select form-select-sm"><option value="">—</option><option v-for="o in catalogos.empresas" :key="o.value" :value="o.value">{{ o.label }}</option></select>
                  <select v-else-if="fila.alcance === 'TRABAJADOR'" v-model="fila.relacion_laboral_id" class="form-select form-select-sm"><option value="">—</option><option v-for="o in catalogos.relaciones" :key="o.value" :value="o.value">{{ o.label }}</option></select>
                  <input v-else class="form-control form-control-sm" value="Todos" disabled />
                </div>
                <div class="col-md-4"><label class="form-label">Valor * <small class="text-muted">(fracción: 8 % = 0.08)</small></label><input v-model="fila.valor" type="text" inputmode="decimal" class="form-control form-control-sm font-monospace" /></div>
                <div class="col-md-3"><label class="form-label">Desde *</label><input v-model="fila.desde" type="date" class="form-control form-control-sm" /></div>
                <div class="col-md-3"><label class="form-label">Hasta</label><input v-model="fila.hasta" type="date" class="form-control form-control-sm" /></div>
                <div class="col-md-3"><label class="form-label">Estado</label><select v-model="fila.estado" class="form-select form-select-sm"><option value="SIN_DATO">Sin dato</option><option value="SIN_CONFIRMAR">Sin confirmar</option><option value="CONFIRMADO">Confirmado</option></select></div>
                <div class="col-md-3 d-flex gap-2 justify-content-end"><button v-if="editando" type="button" class="btn btn-sm btn-outline-secondary" @click="cancelar">Cancelar</button><button type="button" class="btn btn-sm btn-primary" @click="guardarValor">{{ editando ? 'Actualizar' : 'Agregar' }}</button></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
