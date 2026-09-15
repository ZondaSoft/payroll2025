<script setup>
import { router, Link } from '@inertiajs/vue3'
import Flash from '@/Components/Motor/Flash.vue'
import EstadoValidacionBadge from '@/Components/Motor/EstadoValidacionBadge.vue'
import { confirmar, exito } from '@/Components/Motor/ConfirmarBaja.js'
import { porcentaje } from '@/Components/Motor/formato.js'

defineProps({ items: Array })
const baja = async (m) => { if (!(await confirmar({ titulo: `¿Dar de baja ${m.codigo}?`, html: m.convenios ? `${m.convenios} convenio(s) lo usan.` : 'Baja lógica.', confirmar: 'Sí, dar de baja', peligro: true }))) return; router.delete(route('motor.escalas.antiguedad.destroy', m.id), { preserveScroll: true, onSuccess: () => exito('Dado de baja') }) }
</script>

<template>
  <div class="container-xxl flex-grow-1 container-p-y">
    <Flash />
    <div class="app-ecommerce">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-6 row-gap-4">
        <div class="col-lg-8"><h4 class="mb-1">Modelos de antigüedad</h4><p class="mb-0 text-muted">ESCALA: porcentaje por tramo de años. LINEAL: % por año, sin tope si el máximo está vacío. Una versión usada en liquidación no se modifica: se crea otra.</p></div>
        <div class="d-flex gap-2"><Link :href="route('motor.escalas.basicos.index')" class="btn btn-outline-secondary"><i class="ri-arrow-left-line me-1"></i> Escalas</Link><Link :href="route('motor.escalas.antiguedad.create')" class="btn btn-primary"><i class="ri-add-line me-1"></i> Nuevo modelo</Link></div>
      </div>
      <div class="card"><div class="table-responsive"><table class="table table-striped align-middle mb-0">
        <thead class="table-light"><tr><th>Código</th><th>Descripción</th><th>Método</th><th class="text-end">% por año</th><th class="text-end">Tope</th><th class="text-end">Tramos</th><th class="text-end">Convenios</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
        <tbody><tr v-for="m in items" :key="m.id" :class="{ 'text-muted': !m.activo }"><td class="font-monospace fw-bold">{{ m.codigo }} <small class="text-muted">v{{ m.version }}</small></td><td>{{ m.descripcion }}</td><td><span class="badge bg-label-info">{{ m.metodo }}</span></td><td class="text-end">{{ m.porcentaje_por_anio ? porcentaje(m.porcentaje_por_anio) : '—' }}</td><td class="text-end">{{ m.porcentaje_maximo ? porcentaje(m.porcentaje_maximo, 0) : 'sin tope' }}</td><td class="text-end">{{ m.tramos }}</td><td class="text-end">{{ m.convenios }}</td><td><EstadoValidacionBadge :estado="m.estado" /></td>
          <td class="text-end"><Link :href="route('motor.escalas.antiguedad.edit', m.id)" class="btn btn-link text-warning p-0 me-2"><i class="ri-edit-line" style="font-size: 1.25rem"></i></Link><button v-if="m.activo" type="button" class="btn btn-link text-danger p-0" @click="baja(m)"><i class="ri-delete-bin-line" style="font-size: 1.25rem"></i></button></td></tr></tbody>
      </table></div></div>
    </div>
  </div>
</template>
