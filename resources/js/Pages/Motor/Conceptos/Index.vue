<script setup>
import { ref, watch } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import Flash from '@/Components/Motor/Flash.vue'
import { confirmar, exito } from '@/Components/Motor/ConfirmarBaja.js'

const props = defineProps({ items: Object, filtros: Object, resumen: Object, tipos: Array })
const f = ref({ buscar: props.filtros?.buscar ?? '', tipo: props.filtros?.tipo ?? '', activo: props.filtros?.activo ?? '' })
let t = null
watch(() => f.value.buscar, () => { clearTimeout(t); t = setTimeout(aplicar, 300) })
const aplicar = () => router.get(route('motor.conceptos.index'), f.value, { preserveState: true, preserveScroll: true, replace: true })
const semaforo = (s) => ({ ok: 'bg-success', aviso: 'bg-warning', error: 'bg-danger' }[s])
const baja = async (c) => {
  if (!(await confirmar({ titulo: `¿Dar de baja ${c.codigo}?`, html: 'Baja lógica: el concepto queda inactivo, sus versiones e historial se conservan (RT-010).', confirmar: 'Sí, dar de baja', peligro: true }))) return
  router.delete(route('motor.conceptos.destroy', c.id), { preserveScroll: true, onSuccess: () => exito('Dado de baja') })
}
</script>

<template>
  <div class="container-xxl flex-grow-1 container-p-y">
    <Flash />
    <div class="app-ecommerce">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-6 row-gap-4">
        <div class="col-lg-9">
          <h4 class="mb-1">Conceptos de liquidación</h4>
          <p class="mb-0 text-muted">Maestro del motor. El liquidador usa la <b>versión publicada vigente</b> de cada concepto, nunca el borrador.</p>
        </div>
        <Link :href="route('motor.conceptos.create')" class="btn btn-primary"><i class="ri-add-line me-1"></i> Nuevo concepto</Link>
      </div>

      <div class="row g-4 mb-4">
        <div class="col-6 col-md-3"><div class="card"><div class="card-body py-3"><h4 class="mb-0">{{ resumen.total }}</h4><small class="text-muted">conceptos</small></div></div></div>
        <div class="col-6 col-md-3"><div class="card"><div class="card-body py-3"><h4 class="mb-0 text-success">{{ resumen.activos }}</h4><small class="text-muted">activos</small></div></div></div>
        <div class="col-6 col-md-3"><div class="card"><div class="card-body py-3"><h4 class="mb-0" :class="resumen.sin_afip ? 'text-warning' : ''">{{ resumen.sin_afip }}</h4><small class="text-muted">sin código ARCA</small></div></div></div>
        <div class="col-6 col-md-3"><div class="card"><div class="card-body py-3"><h4 class="mb-0" :class="resumen.sin_version ? 'text-danger' : ''">{{ resumen.sin_version }}</h4><small class="text-muted">publicados sin versión</small></div></div></div>
      </div>

      <div class="card">
        <div class="card-header d-flex flex-wrap gap-3 align-items-center">
          <input v-model="f.buscar" type="text" class="form-control form-control-sm" style="width: 260px" placeholder="Código o nombre…" />
          <select v-model="f.tipo" class="form-select form-select-sm" style="width: 180px" @change="aplicar"><option value="">Todos los tipos</option><option v-for="t in tipos" :key="t.value" :value="t.value">{{ t.label }}</option></select>
          <select v-model="f.activo" class="form-select form-select-sm" style="width: 150px" @change="aplicar"><option value="">Activos e inactivos</option><option value="1">Solo activos</option><option value="0">Solo inactivos</option></select>
          <span class="badge bg-label-primary">{{ items.total }} registros</span>
        </div>
        <div class="table-responsive text-nowrap">
          <table class="table table-striped table-hover align-middle">
            <thead class="table-light"><tr><th></th><th>Código</th><th>Nombre</th><th>Tipo / naturaleza</th><th>Estrategia</th><th>ARCA</th><th>Versión</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
            <tbody>
              <tr v-if="!items.data.length"><td colspan="9" class="text-center text-muted py-4">No hay conceptos.</td></tr>
              <tr v-for="c in items.data" :key="c.id" :class="{ 'text-muted': !c.activo }">
                <td><span class="badge rounded-pill" :class="semaforo(c.semaforo)" style="width: 10px; height: 10px; padding: 0" :title="`${c.errores} errores, ${c.avisos} avisos`">&nbsp;</span></td>
                <td class="font-monospace fw-bold">{{ c.codigo }}</td>
                <td>{{ c.nombre }}</td>
                <td><small>{{ c.tipo }}<br /><span class="text-muted">{{ c.naturaleza }}</span></small></td>
                <td><small>{{ c.estrategia ?? c.modo }}</small></td>
                <td><span v-if="c.codigo_afip" class="font-monospace">{{ c.codigo_afip }}</span><span v-else class="text-warning" title="Sin código ARCA: no se exporta al LSD">—</span></td>
                <td><span v-if="c.vigente" class="badge bg-label-success">v{{ c.vigente }} vigente</span><span v-else-if="c.versiones" class="badge bg-label-secondary">{{ c.versiones }} versiones</span><span v-else class="badge bg-label-danger">sin versión</span></td>
                <td><span class="badge" :class="c.estado_configuracion === 'PUBLICADO' ? 'bg-label-primary' : 'bg-label-secondary'">{{ c.estado_configuracion }}</span><span v-if="!c.activo" class="badge bg-label-dark ms-1">inactivo</span></td>
                <td class="text-end">
                  <Link :href="route('motor.conceptos.versiones', c.id)" class="btn btn-link text-info p-0 me-2" title="Versiones"><i class="ri-git-branch-line" style="font-size: 1.25rem"></i></Link>
                  <Link :href="route('motor.conceptos.edit', c.id)" class="btn btn-link text-warning p-0 me-2" title="Editar"><i class="ri-edit-line" style="font-size: 1.25rem"></i></Link>
                  <button v-if="c.activo" type="button" class="btn btn-link text-danger p-0" title="Dar de baja" @click="baja(c)"><i class="ri-delete-bin-line" style="font-size: 1.25rem"></i></button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-if="items.last_page > 1" class="card-footer d-flex justify-content-between align-items-center">
          <span class="text-muted small">Mostrando {{ items.from }}–{{ items.to }} de {{ items.total }}</span>
          <nav><ul class="pagination mb-0"><li v-for="l in items.links" :key="l.label" :class="['page-item', { active: l.active, disabled: !l.url }]"><Link v-if="l.url" :href="l.url" class="page-link" v-html="l.label" preserve-scroll preserve-state /><span v-else class="page-link" v-html="l.label" /></li></ul></nav>
        </div>
      </div>
    </div>
  </div>
</template>
