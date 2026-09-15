<script setup>
import { ref, watch } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import axios from 'axios'
import Swal from 'sweetalert2'
import Flash from '@/Components/Motor/Flash.vue'
import { fecha, escapeHtml } from '@/Components/Motor/formato.js'

const props = defineProps({ items: Object, filtros: Object, empresas: Array, resumen: Object, estados: Array })
const f = ref({ empresa: props.filtros?.empresa ?? '', estado: props.filtros?.estado ?? '', buscar: props.filtros?.buscar ?? '' })
let t = null
watch(() => f.value.buscar, () => { clearTimeout(t); t = setTimeout(aplicar, 300) })
const aplicar = () => router.get(route('motor.relaciones.index'), f.value, { preserveState: true, replace: true })
const estadoClase = (e) => ({ VIGENTE: 'bg-label-success', BAJA: 'bg-label-secondary', INCONSISTENTE_ORIGEN: 'bg-label-danger' }[e] || 'bg-label-secondary')

const sincronizar = async () => {
  const { value } = await Swal.fire({ title: 'Sincronizar desde legajos', html: `<p class="small text-start">Crea/actualiza las relaciones laborales a partir de los legajos activos de <code>sue001s</code>. Idempotente. Cambios de categoría se historizan; cambios de empresa se marcan INCONSISTENTE (no se mueven).</p><select id="s-emp" class="form-select mb-2"><option value="">Todas las empresas</option>${props.empresas.map((e) => `<option value="${e.codigo}">${escapeHtml(e.label)}</option>`).join('')}</select><input id="s-per" type="month" class="form-control" value="${new Date().toISOString().substring(0, 7)}">`,
    showCancelButton: true, reverseButtons: true, buttonsStyling: false, confirmButtonText: 'Sincronizar', cancelButtonText: 'Cancelar', customClass: { confirmButton: 'btn rounded-pill btn-primary ms-2', cancelButton: 'btn rounded-pill btn-outline-secondary' }, preConfirm: () => ({ empresa: document.getElementById('s-emp').value || null, periodo: document.getElementById('s-per').value }) })
  if (!value) return
  Swal.fire({ title: 'Sincronizando…', didOpen: () => Swal.showLoading(), allowOutsideClick: false })
  try {
    const { data } = await axios.post(route('motor.relaciones.sincronizar'), value)
    await Swal.fire({ icon: data.conflictos.length ? 'warning' : 'success', title: 'Sincronización terminada', width: 700, html: `<div class="text-start small"><b>Creadas:</b> ${data.creadas} · <b>Actualizadas:</b> ${data.actualizadas} · <b>Sin cambio:</b> ${data.sinCambio} · <b>Bajas:</b> ${data.bajas}${data.avisos.length ? `<hr><b>Avisos (${data.avisos.length})</b><ul>${data.avisos.slice(0, 15).map((a) => `<li>${escapeHtml(a)}</li>`).join('')}</ul>` : ''}${data.conflictos.length ? `<hr><b class="text-danger">Conflictos (${data.conflictos.length})</b><ul>${data.conflictos.map((a) => `<li>${escapeHtml(a)}</li>`).join('')}</ul>` : ''}</div>`, confirmButtonText: 'Cerrar', buttonsStyling: false, customClass: { confirmButton: 'btn rounded-pill btn-primary' } })
    router.reload()
  } catch (e) { Swal.fire({ icon: 'error', title: 'No se pudo sincronizar', text: e.response?.data?.message || e.message }) }
}
const normalizar = async () => {
  const { value: periodo } = await Swal.fire({ title: 'Normalizar novedades del período', html: `<p class="small text-start">Traduce las novedades legacy (<code>sue028s</code>) a tipos del motor según el mapeo cargado. Lo que no tenga mapeo queda trazado como SIN_MAPEO.</p><input id="n-per" type="month" class="form-control" value="${new Date().toISOString().substring(0, 7)}">`,
    showCancelButton: true, reverseButtons: true, buttonsStyling: false, confirmButtonText: 'Normalizar', cancelButtonText: 'Cancelar', customClass: { confirmButton: 'btn rounded-pill btn-primary ms-2', cancelButton: 'btn rounded-pill btn-outline-secondary' }, preConfirm: () => document.getElementById('n-per').value })
  if (!periodo) return
  Swal.fire({ title: 'Normalizando…', didOpen: () => Swal.showLoading(), allowOutsideClick: false })
  try {
    const { data } = await axios.post(route('motor.relaciones.normalizar-novedades'), { periodo })
    const sm = Object.entries(data.sinMapeo).sort((a, b) => b[1] - a[1])
    await Swal.fire({ icon: sm.length ? 'warning' : 'success', title: 'Normalización terminada', width: 700, html: `<div class="text-start small"><b>Creadas:</b> ${data.creadas} · <b>Actualizadas:</b> ${data.actualizadas} · <b>Sin cambio:</b> ${data.sinCambio} · <b>Anuladas:</b> ${data.anuladas} · <b>Ignoradas:</b> ${data.ignoradas}${sm.length ? `<hr><b>Sin mapeo (${sm.length} códigos):</b> ${sm.map(([c, n]) => `<code>${escapeHtml(c)}</code> (${n})`).join(' ')}<br><a href="${route('motor.novedades-mapeo.index')}">Cargar mapeos →</a>` : ''}${Object.keys(data.sinRelacion).length ? `<hr><b class="text-danger">Legajos sin relación laboral:</b> ${Object.keys(data.sinRelacion).join(', ')}` : ''}</div>`, confirmButtonText: 'Cerrar', buttonsStyling: false, customClass: { confirmButton: 'btn rounded-pill btn-primary' } })
  } catch (e) { Swal.fire({ icon: 'error', title: 'No se pudo normalizar', text: e.response?.data?.message || e.message }) }
}
</script>

<template>
  <div class="container-xxl flex-grow-1 container-p-y">
    <Flash />
    <div class="app-ecommerce">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-6 row-gap-4">
        <div class="col-lg-7"><h4 class="mb-1">Relaciones laborales</h4><p class="mb-0 text-muted">Contratación canónica del trabajador (RELACION_LABORAL). Los datos personales viven en el legajo; acá lo necesario para liquidar. Vinculadas por <code>sue001s.id</code>: sobreviven a renumeraciones.</p></div>
        <div class="d-flex gap-2 flex-wrap"><button type="button" class="btn btn-outline-primary" @click="normalizar"><i class="ri-shuffle-line me-1"></i> Normalizar novedades</button><button type="button" class="btn btn-primary" @click="sincronizar"><i class="ri-refresh-line me-1"></i> Sincronizar desde legajos</button></div>
      </div>
      <div class="row g-4 mb-4">
        <div class="col-md-4"><div class="card"><div class="card-body py-3"><h4 class="mb-0">{{ resumen.legajos_activos }}</h4><small class="text-muted">legajos activos en sue001s</small></div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body py-3"><h4 class="mb-0 text-success">{{ resumen.relaciones_vigentes }}</h4><small class="text-muted">relaciones no dadas de baja</small></div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body py-3"><h4 class="mb-0" :class="resumen.sin_sincronizar ? 'text-warning' : ''">{{ resumen.sin_sincronizar }}</h4><small class="text-muted">activos sin sincronizar (aprox.)</small></div></div></div>
      </div>
      <div class="card">
        <div class="card-header d-flex gap-3 flex-wrap">
          <select v-model="f.empresa" class="form-select form-select-sm" style="width: 260px" @change="aplicar"><option value="">Todas las empresas</option><option v-for="e in empresas" :key="e.value" :value="e.value">{{ e.label }}</option></select>
          <select v-model="f.estado" class="form-select form-select-sm" style="width: 200px" @change="aplicar"><option value="">Todos los estados</option><option v-for="e in estados" :key="e.value" :value="e.value">{{ e.label }}</option></select>
          <input v-model="f.buscar" class="form-control form-control-sm" style="width: 240px" placeholder="Legajo, nombre o CUIL…" /><span class="badge bg-label-primary align-self-center">{{ items.total }}</span>
        </div>
        <div class="table-responsive"><table class="table table-striped table-hover align-middle">
          <thead class="table-light"><tr><th>Legajo</th><th>Apellido y nombre</th><th>CUIL</th><th>Empresa</th><th>Ingreso</th><th>Egreso</th><th>Estado</th><th></th></tr></thead>
          <tbody>
            <tr v-if="!items.data.length"><td colspan="8" class="text-center text-muted py-4">Sin relaciones laborales. Sincronizá desde legajos.</td></tr>
            <tr v-for="r in items.data" :key="r.id"><td class="font-monospace fw-bold">{{ r.legajo }}</td><td>{{ r.nombre }}</td><td class="font-monospace small">{{ r.cuil ?? '—' }}</td><td class="small">{{ r.empresa }}</td><td class="small">{{ fecha(r.ingreso) }}</td><td class="small">{{ r.egreso ? fecha(r.egreso) : '—' }}</td><td><span class="badge" :class="estadoClase(r.estado)">{{ r.estado }}</span></td>
              <td class="text-end"><Link :href="route('motor.relaciones.show', r.id)" class="btn btn-sm btn-outline-primary">Ficha <i class="ri-arrow-right-line"></i></Link></td></tr>
          </tbody>
        </table></div>
        <div v-if="items.last_page > 1" class="card-footer d-flex justify-content-between align-items-center"><span class="text-muted small">{{ items.from }}–{{ items.to }} de {{ items.total }}</span><nav><ul class="pagination mb-0"><li v-for="l in items.links" :key="l.label" :class="['page-item', { active: l.active, disabled: !l.url }]"><Link v-if="l.url" :href="l.url" class="page-link" v-html="l.label" preserve-scroll preserve-state /><span v-else class="page-link" v-html="l.label" /></li></ul></nav></div>
      </div>
    </div>
  </div>
</template>
