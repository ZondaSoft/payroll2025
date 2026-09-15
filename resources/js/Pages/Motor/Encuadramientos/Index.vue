<script setup>
import { router, Link } from '@inertiajs/vue3'
import Flash from '@/Components/Motor/Flash.vue'
import EstadoValidacionBadge from '@/Components/Motor/EstadoValidacionBadge.vue'
import { confirmar, exito } from '@/Components/Motor/ConfirmarBaja.js'
import { fecha } from '@/Components/Motor/formato.js'

defineProps({ items: Array, sin_encuadramiento: Array, empresas: Array })
const eliminar = async (x) => { if (!(await confirmar({ titulo: '¿Eliminar este encuadramiento?', html: 'Si una liquidación lo usó, cerrá la vigencia en lugar de borrarlo.', confirmar: 'Sí, eliminar', peligro: true }))) return; router.delete(route('motor.encuadramientos.destroy', x.id), { preserveScroll: true, onSuccess: () => exito('Eliminado') }) }
</script>

<template>
  <div class="container-xxl flex-grow-1 container-p-y">
    <Flash />
    <div class="app-ecommerce">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-6 row-gap-4">
        <div class="col-lg-8"><h4 class="mb-1">Encuadramiento patronal de las empresas</h4><p class="mb-0 text-muted">Línea de tiempo A/B (Ley 27.541). A = 20,40 % · B = 18 %. El motor busca el encuadramiento vigente del empleador antes de mirar el legajo; sin él, bloquea las contribuciones.</p></div>
        <Link :href="route('motor.encuadramientos.create')" class="btn btn-primary"><i class="ri-add-line me-1"></i> Registrar encuadramiento</Link>
      </div>
      <div v-if="sin_encuadramiento.length" class="alert alert-warning"><i class="ri-alert-line me-1"></i>Sin encuadramiento vigente: <b v-for="e in sin_encuadramiento" :key="e.id" class="me-2">{{ e.label }}</b></div>
      <div class="card"><div class="table-responsive"><table class="table table-striped align-middle mb-0">
        <thead class="table-light"><tr><th>Empresa</th><th>Encuadr.</th><th>Vigencia</th><th>Detalle</th><th>Certificado MiPyME</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
        <tbody>
          <tr v-if="!items.length"><td colspan="7" class="text-center text-muted py-4">Sin encuadramientos. La spec lo deja pendiente de relevamiento: cargalo por empresa.</td></tr>
          <tr v-for="x in items" :key="x.id" :class="{ 'table-success': x.vigente }"><td>{{ x.empresa }}</td><td><span class="badge fs-6" :class="x.encuadramiento === 'A' ? 'bg-primary' : 'bg-info'">{{ x.encuadramiento }}</span></td><td class="small">{{ fecha(x.desde) }} → {{ x.hasta ? fecha(x.hasta) : '∞' }}</td><td class="small">{{ x.detalle }}</td><td class="small">{{ x.certificado }} <span v-if="x.vencimiento" class="text-muted">(vence {{ fecha(x.vencimiento) }})</span></td><td><EstadoValidacionBadge :estado="x.estado" /></td>
            <td class="text-end"><Link :href="route('motor.encuadramientos.edit', x.id)" class="btn btn-link text-warning p-0 me-2"><i class="ri-edit-line" style="font-size: 1.25rem"></i></Link><button type="button" class="btn btn-link text-danger p-0" @click="eliminar(x)"><i class="ri-delete-bin-line" style="font-size: 1.25rem"></i></button></td></tr>
        </tbody>
      </table></div></div>
    </div>
  </div>
</template>
