<script setup>
import { router, Link } from '@inertiajs/vue3'
import Flash from '@/Components/Motor/Flash.vue'
import { confirmar, exito } from '@/Components/Motor/ConfirmarBaja.js'

defineProps({ mapeos: Array, sin_mapear: Array, resumen_if: Object, tipos: Array, columnas: Array })
const baja = async (m) => { if (!(await confirmar({ titulo: `¿Desactivar el mapeo ${m.cod_nov}:${m.columna}?`, html: 'Las próximas normalizaciones lo tratarán como SIN_MAPEO.', confirmar: 'Sí, desactivar', peligro: true }))) return; router.delete(route('motor.novedades-mapeo.destroy', m.id), { preserveScroll: true, onSuccess: () => exito('Desactivado') }) }
</script>

<template>
  <div class="container-xxl flex-grow-1 container-p-y">
    <Flash />
    <div class="app-ecommerce">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-6 row-gap-4">
        <div class="col-lg-8"><h4 class="mb-1">Mapeo de novedades</h4><p class="mb-0 text-muted">Traduce cada código legacy (<code>sue031s</code>) y columna de <code>sue028s</code> a un tipo de novedad del motor. Resuelve que <code>cantidad</code> sea días, horas, km o pesos según el código.</p></div>
        <Link :href="route('motor.novedades-mapeo.create')" class="btn btn-primary"><i class="ri-add-line me-1"></i> Nuevo mapeo</Link>
      </div>
      <div class="row g-4">
        <div class="col-lg-7">
          <div class="card"><div class="card-header d-flex justify-content-between"><h5 class="card-title mb-0">Mapeos cargados ({{ mapeos.length }})</h5><div class="d-flex gap-1"><span v-for="(n, e) in resumen_if" :key="e" class="badge bg-label-secondary">{{ e }}: {{ n }}</span></div></div>
            <div class="table-responsive"><table class="table table-sm table-striped align-middle mb-0">
              <thead class="table-light"><tr><th>Cód. legacy</th><th>Columna</th><th>Tipo motor</th><th>Unidad</th><th>Importe</th><th class="text-end">Factor</th><th>Acción</th><th></th></tr></thead>
              <tbody><tr v-if="!mapeos.length"><td colspan="8" class="text-center text-muted py-3">Sin mapeos: ninguna novedad legacy llega al motor todavía.</td></tr>
                <tr v-for="m in mapeos" :key="m.id" :class="{ 'text-muted': !m.activo }"><td class="font-monospace fw-bold">{{ m.cod_nov }}</td><td class="font-monospace small">{{ m.columna }}</td><td class="font-monospace">{{ m.tipo }}</td><td>{{ m.unidad }}</td><td><span v-if="m.es_importe" class="badge bg-label-warning">$</span></td><td class="text-end">{{ m.factor }}</td><td><span class="badge" :class="m.accion === 'IGNORAR' ? 'bg-label-secondary' : 'bg-label-success'">{{ m.accion }}</span></td>
                  <td class="text-end"><Link :href="route('motor.novedades-mapeo.edit', m.id)" class="btn btn-link text-warning p-0 me-2"><i class="ri-edit-line"></i></Link><button v-if="m.activo" type="button" class="btn btn-link text-danger p-0" @click="baja(m)"><i class="ri-delete-bin-line"></i></button></td></tr></tbody>
            </table></div></div>
        </div>
        <div class="col-lg-5">
          <div class="card"><div class="card-header"><h5 class="card-title mb-0">Usados en sue028s sin mapear ({{ sin_mapear.length }})</h5></div>
            <div class="table-responsive" style="max-height: 520px"><table class="table table-sm align-middle mb-0">
              <thead class="table-light"><tr><th>Código</th><th>Detalle (sue031s)</th><th class="text-end">Usos</th><th></th></tr></thead>
              <tbody><tr v-if="!sin_mapear.length"><td colspan="4" class="text-center text-success py-3"><i class="ri-checkbox-circle-line me-1"></i>Todo mapeado.</td></tr>
                <tr v-for="s in sin_mapear" :key="s.cod_nov"><td class="font-monospace fw-bold">{{ s.cod_nov }}</td><td class="small">{{ s.detalle }}</td><td class="text-end">{{ s.usos }}</td><td class="text-end"><Link :href="route('motor.novedades-mapeo.create', { cod_nov: s.cod_nov })" class="btn btn-sm btn-outline-primary">Mapear</Link></td></tr></tbody>
            </table></div></div>
        </div>
      </div>
    </div>
  </div>
</template>
