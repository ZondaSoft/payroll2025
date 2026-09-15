<script setup>
import { Link } from '@inertiajs/vue3'
import Flash from '@/Components/Motor/Flash.vue'
import { fecha } from '@/Components/Motor/formato.js'

defineProps({ concepto: Object, versiones: Array, diff_borrador: Object })
const valor = (v) => (v === null || v === undefined ? '∅' : typeof v === 'object' ? JSON.stringify(v) : String(v))
</script>

<template>
  <div class="container-xxl flex-grow-1 container-p-y">
    <Flash />
    <div class="app-ecommerce">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-6 row-gap-4">
        <div><h4 class="mb-1">Versiones — <span class="font-monospace">{{ concepto.codigo }}</span> · {{ concepto.nombre }}</h4><p class="mb-0 text-muted">Cada versión congela la definición completa (snapshot). Publicar una nueva cierra la anterior al día previo; una versión usada en liquidación no se modifica.</p></div>
        <div class="d-flex gap-2"><Link :href="route('motor.conceptos.edit', concepto.id)" class="btn btn-outline-secondary"><i class="ri-edit-line me-1"></i> Editar borrador</Link><Link :href="route('motor.conceptos.index')" class="btn btn-outline-secondary"><i class="ri-arrow-left-line me-1"></i> Volver</Link></div>
      </div>

      <div class="card mb-4">
        <div class="card-header"><h5 class="card-title mb-0">Línea de tiempo</h5></div>
        <div class="table-responsive"><table class="table table-striped align-middle mb-0">
          <thead class="table-light"><tr><th>Versión</th><th>Vigencia</th><th>Motivo</th><th>Tipo de cambio</th><th>Publicada</th><th>Hash</th><th>Estado</th></tr></thead>
          <tbody>
            <tr v-if="!versiones.length"><td colspan="7" class="text-center text-muted py-4">Sin versiones publicadas: el motor bloquea este concepto (CONCEPTO_SIN_VERSION) hasta que publiques una.</td></tr>
            <tr v-for="v in versiones" :key="v.id" :class="{ 'table-success': v.vigente_hoy }">
              <td><b>v{{ v.numero }}</b></td><td>{{ fecha(v.desde) }} → {{ v.hasta ? fecha(v.hasta) : '∞' }}</td><td>{{ v.motivo || '—' }}</td><td><span v-if="v.tipo_cambio" class="badge bg-label-info">{{ v.tipo_cambio }}</span></td><td class="small">{{ v.publicada }}</td><td class="font-monospace small">{{ v.hash }}</td>
              <td><span v-if="v.vigente_hoy" class="badge bg-success">vigente hoy</span> <span v-if="v.usada" class="badge bg-label-dark" title="RT-006: una versión usada en liquidación no se modifica">usada</span></td>
            </tr>
          </tbody>
        </table></div>
      </div>

      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center"><h5 class="card-title mb-0">Borrador vs versión vigente {{ diff_borrador.version_base ? `(v${diff_borrador.version_base})` : '' }}</h5><span class="badge" :class="diff_borrador.identicos ? 'bg-label-success' : 'bg-label-warning'">{{ diff_borrador.identicos ? 'Sin cambios pendientes' : `${diff_borrador.cambios.length} diferencia(s)` }}</span></div>
        <div v-if="!diff_borrador.identicos" class="table-responsive"><table class="table table-sm mb-0">
          <thead class="table-light"><tr><th>Campo</th><th>Vigente</th><th>Borrador</th></tr></thead>
          <tbody><tr v-for="c in diff_borrador.cambios" :key="c.ruta"><td class="font-monospace small">{{ c.ruta }}</td><td class="small text-danger"><s>{{ valor(c.antes) }}</s></td><td class="small text-success">{{ valor(c.despues) }}</td></tr></tbody>
        </table></div>
        <div v-else class="card-body text-muted">El borrador coincide con la versión vigente.</div>
      </div>
    </div>
  </div>
</template>
