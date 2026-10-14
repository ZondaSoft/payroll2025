<script setup>
import { computed, reactive, ref } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import axios from 'axios'
import Swal from 'sweetalert2'
import Flash from '@/Components/Motor/Flash.vue'
import EstadoValidacionBadge from '@/Components/Motor/EstadoValidacionBadge.vue'
import { confirmar, errorValidacion } from '@/Components/Motor/ConfirmarBaja.js'
import { fecha } from '@/Components/Motor/formato.js'

const props = defineProps({ perfil: Object, reglas: Array, catalogos: Object })
const esEdicion = computed(() => !!props.perfil)
const p = props.perfil
const hoy = new Date().toISOString().substring(0, 8) + '01'

const form = useForm({
  codigo: p?.codigo ?? '', nombre: p?.nombre ?? '', nivel: p?.nivel ?? 'CONVENIO',
  id_convenio: p?.id_convenio ?? '', id_categoria: p?.id_categoria ?? '', empresa_id: p?.empresa_id ?? '',
  relacion_laboral_id: p?.relacion_laboral_id ?? '', desde: p?.desde ?? hoy, hasta: p?.hasta ?? '',
  activo: p ? p.activo : true, observaciones: p?.observaciones ?? '',
})
const submit = () => {
  const onError = (e) => errorValidacion(e)
  esEdicion.value ? form.put(route('motor.perfiles.update', p.id), { onError }) : form.post(route('motor.perfiles.store'), { onError })
}
const precedencia = computed(() => ({ TRABAJADOR: 1, EMPRESA: 2, CATEGORIA: 3, CONVENIO: 4, GENERAL: 5 })[form.nivel])

const nuevo = () => ({ id: null, codigo_concepto: '', aplica: true, variante: 'UNICA', motivo: '', desde: p?.desde ?? hoy, hasta: '', estado: 'SIN_CONFIRMAR' })
const editando = ref(null)
const fila = reactive(nuevo())
const conceptoElegido = computed(() => props.catalogos.conceptos.find((c) => c.value === fila.codigo_concepto))
const variantesDelConcepto = computed(() => conceptoElegido.value?.variantes ?? [])
const sinFormula = computed(() => !!fila.codigo_concepto && variantesDelConcepto.value.length === 0)

const editar = (r) => { editando.value = r.id; Object.assign(fila, { ...r, variante: r.variante ?? 'UNICA', motivo: r.motivo ?? '', hasta: r.hasta ?? '' }) }
const cancelar = () => { editando.value = null; Object.assign(fila, nuevo()) }
const elegirConcepto = () => { const v = variantesDelConcepto.value; fila.variante = v.length ? v[0] : 'UNICA' }

const guardarRegla = async () => {
  try {
    await axios.post(route('motor.perfiles.reglas.store', p.id), fila)
    Swal.fire({ icon: 'success', title: 'Regla guardada', toast: true, position: 'top-end', timer: 1500, showConfirmButton: false })
    cancelar(); router.reload({ only: ['reglas'] })
  } catch (e) { const d = e.response?.data; Swal.fire({ icon: 'error', title: 'No se pudo guardar', html: d?.errors ? Object.values(d.errors).flat().join('<br>') : d?.message || e.message }) }
}
const eliminarRegla = async (r) => {
  if (!(await confirmar({ titulo: `¿Quitar ${r.codigo_concepto} del perfil?`, html: 'Si ningún otro perfil se pronuncia sobre el concepto, el motor lo bloquea con <code>PERFIL_SIN_REGLA</code> en vez de omitirlo en silencio.', confirmar: 'Sí, quitar', peligro: true }))) return
  await axios.delete(route('motor.perfiles.reglas.destroy', [p.id, r.id])); router.reload({ only: ['reglas'] })
}
const nombreConcepto = (c) => props.catalogos.conceptos.find((x) => x.value === c)?.label ?? c
</script>

<template>
  <div class="container-xxl flex-grow-1 container-p-y">
    <Flash />
    <div class="app-ecommerce">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-6 row-gap-4">
        <div>
          <h4 class="mb-1">{{ esEdicion ? 'Editar perfil' : 'Nuevo perfil' }} <span v-if="esEdicion" class="font-monospace text-muted">{{ perfil.codigo }}</span></h4>
          <p class="mb-0 text-muted">La regla ganadora aporta <b>aplica y variante</b> juntas: sin variante el motor no puede elegir fórmula.</p>
        </div>
        <Link :href="route('motor.perfiles.index')" class="btn btn-outline-secondary"><i class="ri-arrow-left-line me-1"></i> Volver</Link>
      </div>

      <div class="row g-4">
        <div class="col-lg-4">
          <form @submit.prevent="submit" class="card"><div class="card-body row g-3">
            <div class="col-12"><label class="form-label">Código *</label><input v-model="form.codigo" class="form-control font-monospace" :class="{ 'is-invalid': form.errors.codigo }" :disabled="esEdicion" style="text-transform: uppercase" placeholder="CCT_371_03" /><div v-if="form.errors.codigo" class="invalid-feedback d-block">{{ form.errors.codigo }}</div></div>
            <div class="col-12"><label class="form-label">Nombre *</label><input v-model="form.nombre" class="form-control" :class="{ 'is-invalid': form.errors.nombre }" /><div v-if="form.errors.nombre" class="invalid-feedback d-block">{{ form.errors.nombre }}</div></div>
            <div class="col-12">
              <label class="form-label">Nivel *</label>
              <select v-model="form.nivel" class="form-select"><option v-for="n in catalogos.niveles" :key="n.value" :value="n.value">{{ n.label }}</option></select>
              <div class="form-text">Precedencia {{ precedencia }}: gana sobre todos los números mayores.</div>
            </div>
            <div class="col-12" v-if="form.nivel !== 'GENERAL'">
              <label class="form-label">Ámbito *</label>
              <select v-if="form.nivel === 'CONVENIO'" v-model="form.id_convenio" class="form-select" :class="{ 'is-invalid': form.errors.id_convenio }"><option value="">—</option><option v-for="o in catalogos.convenios" :key="o.value" :value="o.value">{{ o.label }}</option></select>
              <select v-else-if="form.nivel === 'CATEGORIA'" v-model="form.id_categoria" class="form-select" :class="{ 'is-invalid': form.errors.id_categoria }"><option value="">—</option><option v-for="o in catalogos.categorias" :key="o.value" :value="o.value">{{ o.label }}</option></select>
              <select v-else-if="form.nivel === 'EMPRESA'" v-model="form.empresa_id" class="form-select" :class="{ 'is-invalid': form.errors.empresa_id }"><option value="">—</option><option v-for="o in catalogos.empresas" :key="o.value" :value="o.value">{{ o.label }}</option></select>
              <select v-else v-model="form.relacion_laboral_id" class="form-select" :class="{ 'is-invalid': form.errors.relacion_laboral_id }"><option value="">—</option><option v-for="o in catalogos.relaciones" :key="o.value" :value="o.value">{{ o.label }}</option></select>
              <div v-for="k in ['id_convenio', 'id_categoria', 'empresa_id', 'relacion_laboral_id']" :key="k">
                <div v-if="form.errors[k]" class="invalid-feedback d-block">{{ form.errors[k] }}</div>
              </div>
            </div>
            <div class="col-6"><label class="form-label">Desde *</label><input v-model="form.desde" type="date" class="form-control" /></div>
            <div class="col-6"><label class="form-label">Hasta</label><input v-model="form.hasta" type="date" class="form-control" /></div>
            <div class="col-12"><label class="form-label">Observaciones</label><textarea v-model="form.observaciones" class="form-control" rows="2"></textarea></div>
            <div class="col-12"><div class="form-check form-switch"><input id="act" v-model="form.activo" class="form-check-input" type="checkbox" /><label for="act" class="form-check-label">Activo</label></div></div>
            <div class="col-12 d-flex justify-content-end gap-2"><Link :href="route('motor.perfiles.index')" class="btn btn-outline-secondary">Cancelar</Link><button type="submit" class="btn btn-primary" :disabled="form.processing">{{ esEdicion ? 'Actualizar' : 'Crear' }}</button></div>
          </div></form>
        </div>

        <div v-if="esEdicion" class="col-lg-8">
          <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h5 class="card-title mb-0">Matriz de conceptos</h5>
              <span class="text-muted small">{{ reglas.length }} regla(s)</span>
            </div>
            <div class="table-responsive"><table class="table table-sm align-middle mb-0">
              <thead class="table-light"><tr><th>Concepto</th><th>Aplica</th><th>Variante</th><th>Motivo</th><th>Vigencia</th><th>Estado</th><th></th></tr></thead>
              <tbody>
                <tr v-if="!reglas.length"><td colspan="7" class="text-center text-muted py-3">Sin reglas: este perfil no se pronuncia sobre ningún concepto.</td></tr>
                <tr v-for="r in reglas" :key="r.id" :class="{ 'table-warning': editando === r.id }">
                  <td class="font-monospace fw-bold">{{ r.codigo_concepto }}</td>
                  <td><span class="badge" :class="r.aplica ? 'bg-label-success' : 'bg-label-secondary'">{{ r.aplica ? 'sí' : 'no' }}</span></td>
                  <td><span v-if="r.variante" class="badge bg-label-info font-monospace">{{ r.variante }}</span></td>
                  <td class="small text-muted" style="max-width: 320px">{{ r.motivo }}</td>
                  <td class="small">{{ fecha(r.desde) }} → {{ r.hasta ? fecha(r.hasta) : '∞' }}</td>
                  <td><EstadoValidacionBadge :estado="r.estado" small /></td>
                  <td class="text-end"><button type="button" class="btn btn-link text-warning p-0 me-2" @click="editar(r)"><i class="ri-edit-line"></i></button><button type="button" class="btn btn-link text-danger p-0" @click="eliminarRegla(r)"><i class="ri-delete-bin-line"></i></button></td>
                </tr>
              </tbody>
            </table></div>
            <div class="card-body border-top">
              <h6 class="mb-3">{{ editando ? 'Editar regla' : 'Nueva regla' }}</h6>
              <div class="row g-2 align-items-end">
                <div class="col-md-5">
                  <label class="form-label">Concepto *</label>
                  <select v-model="fila.codigo_concepto" class="form-select form-select-sm" :disabled="!!editando" @change="elegirConcepto">
                    <option value="">—</option>
                    <option v-for="c in catalogos.conceptos" :key="c.value" :value="c.value">{{ c.label }}</option>
                  </select>
                </div>
                <div class="col-md-3">
                  <label class="form-label">¿Aplica?</label>
                  <select v-model="fila.aplica" class="form-select form-select-sm"><option :value="true">Sí, se liquida</option><option :value="false">No, excluido</option></select>
                </div>
                <div class="col-md-4" v-if="fila.aplica">
                  <label class="form-label">Variante *</label>
                  <select v-if="variantesDelConcepto.length" v-model="fila.variante" class="form-select form-select-sm font-monospace">
                    <option v-for="v in variantesDelConcepto" :key="v" :value="v">{{ v }}</option>
                  </select>
                  <input v-else v-model="fila.variante" class="form-control form-control-sm font-monospace" placeholder="UNICA" />
                  <div v-if="sinFormula" class="form-text text-warning">Este concepto no tiene fórmula publicada vigente: si aplica, el motor lo dejará <code>NO_MODELADO</code>.</div>
                </div>
                <div class="col-12">
                  <label class="form-label">{{ fila.aplica ? 'Motivo (opcional)' : 'Motivo de la exclusión *' }}</label>
                  <input v-model="fila.motivo" class="form-control form-control-sm" :placeholder="fila.aplica ? 'Concepto del CCT 371/03.' : 'En larga distancia las horas extras no corresponden (H-4).'" />
                  <div v-if="!fila.aplica" class="form-text">Se copia al recibo: explica por qué el concepto no está.</div>
                </div>
                <div class="col-md-3"><label class="form-label">Desde *</label><input v-model="fila.desde" type="date" class="form-control form-control-sm" /></div>
                <div class="col-md-3"><label class="form-label">Hasta</label><input v-model="fila.hasta" type="date" class="form-control form-control-sm" /></div>
                <div class="col-md-3"><label class="form-label">Estado</label><select v-model="fila.estado" class="form-select form-select-sm"><option value="SIN_DATO">Sin dato</option><option value="SIN_CONFIRMAR">Sin confirmar</option><option value="CONFIRMADO">Confirmado</option></select></div>
                <div class="col-md-3 d-flex gap-2 justify-content-end"><button v-if="editando" type="button" class="btn btn-sm btn-outline-secondary" @click="cancelar">Cancelar</button><button type="button" class="btn btn-sm btn-primary" :disabled="!fila.codigo_concepto" @click="guardarRegla">{{ editando ? 'Actualizar' : 'Agregar' }}</button></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
