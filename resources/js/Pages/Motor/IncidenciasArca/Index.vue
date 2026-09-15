<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'
import Swal from 'sweetalert2'
import Flash from '@/Components/Motor/Flash.vue'
import { confirmar } from '@/Components/Motor/ConfirmarBaja.js'

const props = defineProps({ aplicaciones: Array, filas: Array, filtros: Object, empresas: Array })
const filas = ref(props.filas.map((f) => ({ ...f, celdas: { ...f.celdas } })))
const naturaleza = ref(props.filtros?.naturaleza ?? '')
const filtrar = () => router.get(route('motor.incidencias-arca.index'), { naturaleza: naturaleza.value }, { preserveState: true, replace: true })
const guardando = ref(null)
const ciclo = { S: 'N', N: 'PENDIENTE', PENDIENTE: 'S' }
const clase = (v) => ({ S: 'btn-success', N: 'btn-secondary', PENDIENTE: 'btn-warning' }[v] || 'btn-outline-warning')

const cambiar = async (fila, clave) => {
  const actual = fila.celdas[clave]?.incide ?? 'PENDIENTE'
  const nuevo = ciclo[actual]
  guardando.value = fila.codigo + clave
  try {
    const { data } = await axios.post(route('motor.incidencias-arca.celda'), { codigo_concepto: fila.codigo, clave, incide: nuevo })
    fila.celdas[clave] = { id: data.id, incide: data.incide, estado: data.estado }
    if (data.aviso) Swal.fire({ icon: 'info', title: data.aviso, toast: true, position: 'top-end', timer: 3500, showConfirmButton: false })
  } catch (e) {
    Swal.fire({ icon: 'error', title: 'No se pudo guardar', text: e.response?.data?.message || e.message })
  } finally { guardando.value = null }
}
const importar = async () => {
  const { value: empresa } = await Swal.fire({ title: 'Importar desde conceptosarcas', html: `<p class="small text-start">Completa las incidencias <b>PENDIENTE</b> (o inexistentes) de los conceptos con código ARCA, usando las marcas de <code>conceptosarcas</code> ya validadas contra el portal. No pisa una S/N decidida. RENATEA → RENATRE.</p><select id="imp-emp" class="form-select"><option value="">Todas las empresas</option>${props.empresas.map((e) => `<option value="${e.value}">${e.label}</option>`).join('')}</select>`,
    showCancelButton: true, reverseButtons: true, buttonsStyling: false, confirmButtonText: 'Importar', cancelButtonText: 'Cancelar', customClass: { confirmButton: 'btn rounded-pill btn-primary ms-2', cancelButton: 'btn rounded-pill btn-outline-secondary' }, preConfirm: () => document.getElementById('imp-emp').value })
  if (empresa === undefined) return
  Swal.fire({ title: 'Importando…', didOpen: () => Swal.showLoading(), allowOutsideClick: false })
  try {
    const { data } = await axios.post(route('motor.importar.incidencias-arca'), { id_empresa: empresa || null })
    await Swal.fire({ icon: 'success', title: 'Importación terminada', html: `<div class="text-start small">Conceptos con código ARCA: <b>${data.conceptos_con_codigo_afip}</b><br>Incidencias creadas: <b>${data.creadas}</b> · actualizadas (PENDIENTE→S/N): <b>${data.actualizadas}</b> · sin cambio: ${data.sin_cambio}<br>${data.sin_marca_legacy.length ? 'Sin marca en el legacy: ' + data.sin_marca_legacy.join(', ') : ''}</div>`, confirmButtonText: 'Cerrar', buttonsStyling: false, customClass: { confirmButton: 'btn rounded-pill btn-primary' } })
    router.reload()
  } catch (e) { Swal.fire({ icon: 'error', title: 'No se pudo importar', text: e.response?.data?.message || e.message }) }
}
</script>

<template>
  <div class="container-xxl flex-grow-1 container-p-y">
    <Flash />
    <div class="app-ecommerce">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-6 row-gap-4">
        <div class="col-lg-8"><h4 class="mb-1">Incidencias ARCA</h4><p class="mb-0 text-muted">Qué concepto integra la base de cada aplicación oficial (subsistema × aporte/contribución). Click en una celda para ciclar S → N → PENDIENTE. PENDIENTE bloquea en PRODUCCIÓN.</p></div>
        <div class="d-flex gap-2"><select v-model="naturaleza" class="form-select form-select-sm" style="width: 200px" @change="filtrar"><option value="">Todas las naturalezas</option><option value="REMUNERATIVO">Remunerativo</option><option value="NO_REMUNERATIVO">No remunerativo</option></select><button type="button" class="btn btn-outline-primary" @click="importar"><i class="ri-download-2-line me-1"></i> Importar desde conceptosarcas</button></div>
      </div>
      <div class="card">
        <div class="table-responsive">
          <table class="table table-sm table-hover align-middle mb-0" style="font-size: 0.85rem">
            <thead class="table-light" style="position: sticky; top: 0; z-index: 1">
              <tr><th style="min-width: 220px">Concepto</th><th v-for="a in aplicaciones" :key="a.clave" class="text-center" :title="`${a.subsistema} ${a.tipo} → ${a.base ?? 'sin base'} (col. LSD ${a.columna})`"><div>{{ a.subsistema }}</div><small class="text-muted">{{ a.tipo === 'APORTE' ? 'Ap.' : 'Contr.' }} {{ a.base ?? '' }}</small></th></tr>
            </thead>
            <tbody>
              <tr v-for="f in filas" :key="f.codigo">
                <td><b class="font-monospace">{{ f.codigo }}</b> <span v-if="f.publicado" class="badge bg-label-primary" title="Publicado: los cambios rigen al publicar nueva versión">pub</span><small class="d-block text-muted text-truncate" style="max-width: 220px">{{ f.nombre }} · {{ f.naturaleza }}</small></td>
                <td v-for="a in aplicaciones" :key="a.clave" class="text-center p-1">
                  <button type="button" class="btn btn-sm px-2 py-0" :class="clase(f.celdas[a.clave]?.incide)" :disabled="guardando === f.codigo + a.clave" @click="cambiar(f, a.clave)">{{ f.celdas[a.clave]?.incide === 'PENDIENTE' || !f.celdas[a.clave] ? '?' : f.celdas[a.clave].incide }}</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>
