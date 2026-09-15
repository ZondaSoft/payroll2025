<script setup>
import { router, Link } from '@inertiajs/vue3'
import Flash from '@/Components/Motor/Flash.vue'
import EstadoValidacionBadge from '@/Components/Motor/EstadoValidacionBadge.vue'
import { confirmar, exito } from '@/Components/Motor/ConfirmarBaja.js'

defineProps({ items: Array, circularidades: Object })
const baja = async (b) => {
  if (!(await confirmar({ titulo: `¿Dar de baja ${b.codigo}?`, html: 'Baja lógica. Su composición histórica se conserva.', confirmar: 'Sí, dar de baja', peligro: true }))) return
  router.delete(route('motor.bases-zonda.destroy', b.id), { preserveScroll: true, onSuccess: () => exito('Dada de baja') })
}
</script>

<template>
  <div class="container-xxl flex-grow-1 container-p-y">
    <Flash />
    <div class="app-ecommerce">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-6 row-gap-4">
        <div class="col-lg-9"><h4 class="mb-1">Bases de cálculo de Zonda</h4><p class="mb-0 text-muted">Agrupaciones reutilizables de conceptos que una estrategia consume (p. ej. BASE_HORA_NORMAL / horas = hora normal). No son las bases ARCA: coexisten sin mezclarse.</p></div>
        <Link :href="route('motor.bases-zonda.create')" class="btn btn-primary"><i class="ri-add-line me-1"></i> Nueva base</Link>
      </div>
      <div v-if="circularidades.directas.length || circularidades.ciclos.length" class="alert alert-danger">
        <h6 class="alert-heading"><i class="ri-error-warning-line me-1"></i>Circularidad detectada (R13)</h6>
        <ul class="mb-0"><li v-for="c in circularidades.directas" :key="c"><b class="font-monospace">{{ c }}</b> consume una base que a la vez integra.</li><li v-for="(c, i) in circularidades.ciclos" :key="'c' + i" class="font-monospace">{{ c.join(' → ') }}</li></ul>
      </div>
      <div class="row g-4">
        <div v-for="b in items" :key="b.id" class="col-md-6">
          <div class="card h-100" :class="{ 'opacity-50': !b.activo }">
            <div class="card-header d-flex justify-content-between align-items-start">
              <div><h5 class="mb-0 font-monospace">{{ b.codigo }}</h5><small class="text-muted">{{ b.nombre }}</small></div>
              <div class="d-flex gap-2 align-items-center"><EstadoValidacionBadge :estado="b.estado_validacion" /><Link :href="route('motor.bases-zonda.edit', b.id)" class="btn btn-link text-warning p-0" title="Editar"><i class="ri-edit-line" style="font-size: 1.25rem"></i></Link><button v-if="b.activo" type="button" class="btn btn-link text-danger p-0" @click="baja(b)"><i class="ri-delete-bin-line" style="font-size: 1.25rem"></i></button></div>
            </div>
            <div class="card-body">
              <p class="small text-muted">{{ b.descripcion }}</p>
              <div class="mb-2"><small class="text-muted d-block">Integran ({{ b.integrantes.length }})</small><span v-for="c in b.integrantes" :key="c" class="badge bg-label-primary me-1 mb-1 font-monospace">{{ c }}</span><span v-if="!b.integrantes.length" class="text-warning small">Sin composición vigente: el motor bloquea si alguien la consume.</span></div>
              <div><small class="text-muted d-block">Consumen ({{ b.consumidores.length }})</small><span v-for="c in b.consumidores" :key="c" class="badge bg-label-info me-1 mb-1 font-monospace">{{ c }}</span><span v-if="!b.consumidores.length" class="text-muted small">—</span></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
