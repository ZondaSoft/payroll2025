<script setup>
import { router, Link } from '@inertiajs/vue3'
import Flash from '@/Components/Motor/Flash.vue'
import EstadoValidacionBadge from '@/Components/Motor/EstadoValidacionBadge.vue'
import { confirmar, exito } from '@/Components/Motor/ConfirmarBaja.js'
import { fecha } from '@/Components/Motor/formato.js'

defineProps({ antiguedad: Array, vacaciones: Array })
const eliminar = async (a) => { if (!(await confirmar({ titulo: '¿Eliminar la asignación?', html: 'El convenio quedará sin modelo: el motor bloquea ANTIG.', confirmar: 'Sí, eliminar', peligro: true }))) return; router.delete(route('motor.escalas.asignaciones.destroy', a.id), { data: { tipo: a.tipo }, preserveScroll: true, onSuccess: () => exito('Eliminada') }) }
</script>

<template>
  <div class="container-xxl flex-grow-1 container-p-y">
    <Flash />
    <div class="app-ecommerce">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-6 row-gap-4">
        <div class="col-lg-8"><h4 class="mb-1">Asignación de modelos a convenios</h4><p class="mb-0 text-muted">Qué modelo de antigüedad y de vacaciones usa cada convenio (sue007s), con vigencia. Sin asignación vigente, el concepto ANTIG bloquea.</p></div>
        <div class="d-flex gap-2"><Link :href="route('motor.escalas.basicos.index')" class="btn btn-outline-secondary"><i class="ri-arrow-left-line me-1"></i> Escalas</Link><Link :href="route('motor.escalas.asignaciones.create', { tipo: 'ANTIGUEDAD' })" class="btn btn-primary"><i class="ri-add-line me-1"></i> Antigüedad</Link><Link :href="route('motor.escalas.asignaciones.create', { tipo: 'VACACIONES' })" class="btn btn-outline-primary"><i class="ri-add-line me-1"></i> Vacaciones</Link></div>
      </div>
      <div class="row g-4">
        <div v-for="[titulo, lista] in [['Antigüedad', antiguedad], ['Vacaciones', vacaciones]]" :key="titulo" class="col-lg-6">
          <div class="card h-100"><div class="card-header"><h5 class="card-title mb-0">{{ titulo }}</h5></div>
            <div class="table-responsive"><table class="table table-sm align-middle mb-0">
              <thead class="table-light"><tr><th>Convenio</th><th>Modelo</th><th>Vigencia</th><th>Estado</th><th></th></tr></thead>
              <tbody>
                <tr v-if="!lista.length"><td colspan="5" class="text-center text-muted py-3">Sin asignaciones.</td></tr>
                <tr v-for="a in lista" :key="a.id"><td>{{ a.convenio }}</td><td class="font-monospace">{{ a.modelo }}</td><td class="small">{{ fecha(a.desde) }} → {{ a.hasta ? fecha(a.hasta) : '∞' }}</td><td><EstadoValidacionBadge v-if="a.estado" :estado="a.estado" small /><small v-else class="text-muted">{{ a.fuente }}</small></td>
                  <td class="text-end"><Link :href="route('motor.escalas.asignaciones.edit', { asignacion: a.id, tipo: a.tipo })" class="btn btn-link text-warning p-0 me-2"><i class="ri-edit-line"></i></Link><button type="button" class="btn btn-link text-danger p-0" @click="eliminar(a)"><i class="ri-delete-bin-line"></i></button></td></tr>
              </tbody>
            </table></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
