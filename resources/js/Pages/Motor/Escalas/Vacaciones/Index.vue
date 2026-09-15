<script setup>
import { router, Link } from '@inertiajs/vue3'
import Flash from '@/Components/Motor/Flash.vue'
import { confirmar, exito } from '@/Components/Motor/ConfirmarBaja.js'

defineProps({ items: Array })
const baja = async (m) => { if (!(await confirmar({ titulo: `¿Dar de baja ${m.codigo}?`, html: 'Baja lógica.', confirmar: 'Sí, dar de baja', peligro: true }))) return; router.delete(route('motor.escalas.vacaciones.destroy', m.id), { preserveScroll: true, onSuccess: () => exito('Dado de baja') }) }
</script>

<template>
  <div class="container-xxl flex-grow-1 container-p-y">
    <Flash />
    <div class="app-ecommerce">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-6 row-gap-4">
        <div class="col-lg-8"><h4 class="mb-1">Modelos de vacaciones</h4><p class="mb-0 text-muted">Días de vacaciones por tramo de antigüedad (LCT art. 150: 14/21/28/35).</p></div>
        <div class="d-flex gap-2"><Link :href="route('motor.escalas.basicos.index')" class="btn btn-outline-secondary"><i class="ri-arrow-left-line me-1"></i> Escalas</Link><Link :href="route('motor.escalas.vacaciones.create')" class="btn btn-primary"><i class="ri-add-line me-1"></i> Nuevo modelo</Link></div>
      </div>
      <div class="card"><div class="table-responsive"><table class="table table-striped align-middle mb-0">
        <thead class="table-light"><tr><th>Código</th><th>Descripción</th><th>Versión</th><th>Fuente</th><th class="text-end">Tramos</th><th class="text-end">Acciones</th></tr></thead>
        <tbody><tr v-for="m in items" :key="m.id" :class="{ 'text-muted': !m.activo }"><td class="font-monospace fw-bold">{{ m.codigo }}</td><td>{{ m.descripcion }}</td><td>v{{ m.version }} <small class="text-muted">{{ m.estado }}</small></td><td class="small">{{ m.fuente }}</td><td class="text-end">{{ m.tramos }}</td>
          <td class="text-end"><Link :href="route('motor.escalas.vacaciones.edit', m.id)" class="btn btn-link text-warning p-0 me-2"><i class="ri-edit-line" style="font-size: 1.25rem"></i></Link><button v-if="m.activo" type="button" class="btn btn-link text-danger p-0" @click="baja(m)"><i class="ri-delete-bin-line" style="font-size: 1.25rem"></i></button></td></tr></tbody>
      </table></div></div>
    </div>
  </div>
</template>
