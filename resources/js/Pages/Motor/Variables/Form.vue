<script setup>
import { computed, reactive, ref } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import axios from 'axios'
import Swal from 'sweetalert2'
import Flash from '@/Components/Motor/Flash.vue'
import EstadoValidacionBadge from '@/Components/Motor/EstadoValidacionBadge.vue'
import { confirmar, errorValidacion } from '@/Components/Motor/ConfirmarBaja.js'
import { fecha } from '@/Components/Motor/formato.js'

const props = defineProps({ variable: Object, valores: Array, catalogos: Object })
const esEdicion = computed(() => !!props.variable)
const v = props.variable
const form = useForm({
  codigo: v?.codigo ?? '', descripcion: v?.descripcion ?? '', unidad: v?.unidad ?? '',
  origen: v?.origen ?? 'CONVENIO', activo: v ? v.activo : true, observaciones: v?.observaciones ?? '',
})
const submit = () => {
  const onError = (e) => errorValidacion(e)
  esEdicion.value ? form.put(route('motor.variables.update', v.codigo), { onError }) : form.post(route('motor.variables.store'), { onError })
}

const nuevo = () => ({ id: null, alcance: 'CONVENIO', id_convenio: '', id_categoria: '', relacion_laboral_id: '', valor: '', desde: new Date().toISOString().substring(0, 8) + '01', hasta: '', estado: 'SIN_CONFIRMAR', fuente: '' })
const editando = ref(null)
const fila = reactive(nuevo())
const editar = (x) => { editando.value = x.id; Object.assign(fila, { ...x, hasta: x.hasta ?? '', fuente: x.fuente ?? '', id_convenio: x.id_convenio ?? '', id_categoria: x.id_categoria ?? '', relacion_laboral_id: x.relacion_laboral_id ?? '' }) }
const cancelar = () => { editando.value = null; Object.assign(fila, nuevo()) }
const guardarValor = async () => {
  try {
    await axios.post(route('motor.variables.valores.store', v.codigo), fila)
    Swal.fire({ icon: 'success', title: 'Valor guardado', toast: true, position: 'top-end', timer: 1500, showConfirmButton: false })
    cancelar(); router.reload({ only: ['valores'] })
  } catch (e) { const d = e.response?.data; Swal.fire({ icon: 'error', title: 'No se pudo guardar', html: d?.errors ? Object.values(d.errors).flat().join('<br>') : d?.message || e.message }) }
}
const eliminarValor = async (x) => {
  if (!(await confirmar({ titulo: '¿Eliminar este valor?', html: 'Si una liquidación histórica lo usó, corresponde <b>cerrar la vigencia</b> en lugar de borrarlo: el motor tiene que poder reproducir lo liquidado.', confirmar: 'Sí, eliminar', peligro: true }))) return
  await axios.delete(route('motor.variables.valores.destroy', [v.codigo, x.id])); router.reload({ only: ['valores'] })
}
const etiqueta = (x) => {
  const o = (l, k) => props.catalogos[l]?.find((y) => String(y.value) === String(k))?.label ?? k
  return x.alcance === 'CONVENIO' ? o('convenios', x.id_convenio) : x.alcance === 'CATEGORIA' ? o('categorias', x.id_categoria) : x.alcance === 'TRABAJADOR' ? o('relaciones', x.relacion_laboral_id) : 'Todos'
}
</script>

<template>
  <div class="container-xxl flex-grow-1 container-p-y">
    <Flash />
    <div class="app-ecommerce">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-6 row-gap-4">
        <div>
          <h4 class="mb-1">{{ esEdicion ? 'Editar variable' : 'Nueva variable' }} <span v-if="esEdicion" class="font-monospace text-muted">{{ variable.codigo }}</span></h4>
          <p class="mb-0 text-muted">Un valor por ámbito y vigencia. Dos vigentes en el mismo nivel = empate = bloqueo.</p>
        </div>
        <Link :href="route('motor.variables.index')" class="btn btn-outline-secondary"><i class="ri-arrow-left-line me-1"></i> Volver</Link>
      </div>

      <div v-if="variable?.del_contexto" class="alert alert-info d-flex align-items-start" role="alert">
        <i class="ri-information-line me-2 mt-1"></i>
        <div><b>{{ variable.codigo }}</b> la resuelve el contexto del trabajador, no un valor cargado: sale de la escala de básicos, de la jornada pactada o de los días de la liquidación. Un valor cargado acá no se leería nunca, y por eso el formulario de valores no está disponible.</div>
      </div>

      <div class="row g-4">
        <div class="col-lg-4">
          <form @submit.prevent="submit" class="card"><div class="card-body row g-3">
            <div class="col-12">
              <label class="form-label">Código *</label>
              <input v-model="form.codigo" class="form-control font-monospace" :class="{ 'is-invalid': form.errors.codigo }" :disabled="esEdicion" maxlength="7" style="text-transform: uppercase" />
              <div class="form-text">Máximo 7 caracteres: es el límite del DSL para los códigos de <code>VAR()</code> (§4).</div>
              <div v-if="form.errors.codigo" class="invalid-feedback d-block">{{ form.errors.codigo }}</div>
            </div>
            <div class="col-12"><label class="form-label">Descripción *</label><input v-model="form.descripcion" class="form-control" :class="{ 'is-invalid': form.errors.descripcion }" /><div v-if="form.errors.descripcion" class="invalid-feedback d-block">{{ form.errors.descripcion }}</div></div>
            <div class="col-12">
              <label class="form-label">Unidad</label>
              <select v-model="form.unidad" class="form-select"><option value="">— sin declarar —</option><option v-for="u in catalogos.unidades" :key="u.value" :value="u.value">{{ u.label }}</option></select>
              <div class="form-text">Sin unidad el analizador no puede verificar que una fórmula devuelva pesos: avisa <code>CFG_UNIDAD_SIN_DECLARAR</code> y no deja publicar.</div>
            </div>
            <div class="col-12"><label class="form-label">Origen *</label><select v-model="form.origen" class="form-select"><option v-for="o in catalogos.origenes" :key="o.value" :value="o.value">{{ o.label }}</option></select></div>
            <div class="col-12"><label class="form-label">Observaciones</label><textarea v-model="form.observaciones" class="form-control" rows="3"></textarea></div>
            <div class="col-12"><div class="form-check form-switch"><input id="act" v-model="form.activo" class="form-check-input" type="checkbox" /><label for="act" class="form-check-label">Activa</label></div></div>
            <div class="col-12 d-flex justify-content-end gap-2"><Link :href="route('motor.variables.index')" class="btn btn-outline-secondary">Cancelar</Link><button type="submit" class="btn btn-primary" :disabled="form.processing">{{ esEdicion ? 'Actualizar' : 'Crear' }}</button></div>
          </div></form>
        </div>

        <div v-if="esEdicion && !variable.del_contexto" class="col-lg-8">
          <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Valores por ámbito y vigencia</h5></div>
            <div class="table-responsive"><table class="table table-sm align-middle mb-0">
              <thead class="table-light"><tr><th>Alcance</th><th>Ámbito</th><th class="text-end">Valor</th><th>Vigencia</th><th>Estado</th><th>Fuente</th><th></th></tr></thead>
              <tbody>
                <tr v-if="!valores.length"><td colspan="7" class="text-center text-muted py-3">Sin valores: el motor bloquea con <code>VARIABLE_SIN_VALOR</code> cuando una fórmula la necesite.</td></tr>
                <tr v-for="x in valores" :key="x.id" :class="{ 'table-warning': editando === x.id }">
                  <td><span class="badge bg-label-secondary">{{ x.alcance }}</span></td>
                  <td class="small">{{ etiqueta(x) }}</td>
                  <td class="text-end font-monospace">{{ x.valor }}</td>
                  <td class="small">{{ fecha(x.desde) }} → {{ x.hasta ? fecha(x.hasta) : '∞' }}</td>
                  <td><EstadoValidacionBadge :estado="x.estado" small /></td>
                  <td class="small text-muted">{{ x.fuente }}</td>
                  <td class="text-end"><button type="button" class="btn btn-link text-warning p-0 me-2" @click="editar(x)"><i class="ri-edit-line"></i></button><button type="button" class="btn btn-link text-danger p-0" @click="eliminarValor(x)"><i class="ri-delete-bin-line"></i></button></td>
                </tr>
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
                  <select v-else-if="fila.alcance === 'TRABAJADOR'" v-model="fila.relacion_laboral_id" class="form-select form-select-sm"><option value="">—</option><option v-for="o in catalogos.relaciones" :key="o.value" :value="o.value">{{ o.label }}</option></select>
                  <input v-else class="form-control form-control-sm" value="Todos" disabled />
                </div>
                <div class="col-md-4"><label class="form-label">Valor *</label><input v-model="fila.valor" type="text" inputmode="decimal" class="form-control form-control-sm font-monospace" placeholder="25" /></div>
                <div class="col-md-3"><label class="form-label">Desde *</label><input v-model="fila.desde" type="date" class="form-control form-control-sm" /></div>
                <div class="col-md-3"><label class="form-label">Hasta</label><input v-model="fila.hasta" type="date" class="form-control form-control-sm" /></div>
                <div class="col-md-3"><label class="form-label">Estado</label><select v-model="fila.estado" class="form-select form-select-sm"><option value="SIN_DATO">Sin dato</option><option value="SIN_CONFIRMAR">Sin confirmar</option><option value="CONFIRMADO">Confirmado</option></select></div>
                <div class="col-md-3 d-flex gap-2 justify-content-end align-items-end"><button v-if="editando" type="button" class="btn btn-sm btn-outline-secondary" @click="cancelar">Cancelar</button><button type="button" class="btn btn-sm btn-primary" @click="guardarValor">{{ editando ? 'Actualizar' : 'Agregar' }}</button></div>
                <div class="col-12"><label class="form-label">Fuente normativa</label><input v-model="fila.fuente" class="form-control form-control-sm" placeholder="CCT 371/03 — jornales del mes" /></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
