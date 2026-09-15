<script setup>
import { Link } from '@inertiajs/vue3'
import Flash from '@/Components/Motor/Flash.vue'

defineProps({ reporte: Object })
const estadoClase = (e) => ({ OK: 'bg-label-success', FALTA: 'bg-label-danger', INACTIVO: 'bg-label-danger', SIN_PUBLICAR: 'bg-label-warning' }[e] || 'bg-label-secondary')
</script>

<template>
  <div class="container-xxl flex-grow-1 container-p-y">
    <Flash />
    <div class="app-ecommerce">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-6 row-gap-4">
        <div class="col-lg-9">
          <h4 class="mb-1">Integridad del motor</h4>
          <p class="mb-0 text-muted">Qué falta para poder liquidar. Este panel controla la configuración; la habilitación real la decide cada corrida con su gate de validación.</p>
        </div>
        <span class="badge fs-6" :class="reporte.habilitado ? 'bg-success' : 'bg-danger'">{{ reporte.habilitado ? 'Conceptos mínimos presentes' : 'Faltan conceptos mínimos' }}</span>
      </div>

      <div class="row g-4">
        <div class="col-md-3"><div class="card h-100"><div class="card-body text-center"><h2 class="mb-0" :class="reporte.incidenciasPendientes ? 'text-warning' : 'text-success'">{{ reporte.incidenciasPendientes }}</h2><small class="text-muted">incidencias ARCA pendientes</small><div class="mt-2"><Link :href="route('motor.incidencias-arca.index')" class="btn btn-sm btn-outline-primary">Ver matriz</Link></div></div></div></div>
        <div class="col-md-3"><div class="card h-100"><div class="card-body text-center"><h2 class="mb-0" :class="reporte.conceptosSinVersion ? 'text-danger' : 'text-success'">{{ reporte.conceptosSinVersion }}</h2><small class="text-muted">conceptos publicados sin versión</small><div class="mt-2"><Link :href="route('motor.conceptos.index')" class="btn btn-sm btn-outline-primary">Conceptos</Link></div></div></div></div>
        <div class="col-md-3"><div class="card h-100"><div class="card-body text-center"><h2 class="mb-0" :class="Object.keys(reporte.novedadesSinMapeo).length ? 'text-warning' : 'text-success'">{{ Object.keys(reporte.novedadesSinMapeo).length }}</h2><small class="text-muted">códigos de novedad sin mapear</small><div class="mt-2"><Link :href="route('motor.novedades-mapeo.index')" class="btn btn-sm btn-outline-primary">Mapeo</Link></div></div></div></div>
        <div class="col-md-3"><div class="card h-100"><div class="card-body text-center"><h2 class="mb-0" :class="reporte.legajosActivosSinRelacion ? 'text-warning' : 'text-success'">{{ reporte.legajosActivosSinRelacion }}</h2><small class="text-muted">legajos activos sin relación laboral</small><div class="mt-2"><Link :href="route('motor.relaciones.index')" class="btn btn-sm btn-outline-primary">Sincronizar</Link></div></div></div></div>

        <div class="col-lg-6">
          <div class="card h-100">
            <div class="card-header"><h5 class="card-title mb-0">Conceptos mínimos de sistema</h5></div>
            <div class="table-responsive"><table class="table table-sm mb-0">
              <thead class="table-light"><tr><th>Código</th><th>Exigencia</th><th>Estado</th><th>Detalle</th></tr></thead>
              <tbody><tr v-for="c in reporte.conceptosMinimos" :key="c.codigo"><td class="font-monospace">{{ c.codigo }}</td><td><span class="badge" :class="c.exigencia === 'OBLIGATORIO' ? 'bg-label-primary' : 'bg-label-secondary'">{{ c.exigencia }}</span></td><td><span class="badge" :class="estadoClase(c.estado)">{{ c.estado }}</span></td><td class="small text-muted">{{ c.detalle || c.descripcion }}</td></tr></tbody>
            </table></div>
            <div class="card-footer small text-muted">Los CONDICIONALES solo se exigen si aplican (p. ej. RENATRE, solo a empleadores rurales).</div>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="card mb-4">
            <div class="card-header"><h5 class="card-title mb-0">Circularidad entre bases de Zonda</h5></div>
            <div class="card-body">
              <p v-if="!reporte.circularidades.length" class="text-success mb-0"><i class="ri-checkbox-circle-line me-1"></i>Sin ciclos: ningún concepto consume una base que integra (R13).</p>
              <ul v-else class="mb-0 text-danger"><li v-for="(c, i) in reporte.circularidades" :key="i" class="font-monospace">{{ c.join(' → ') }}</li></ul>
            </div>
          </div>
          <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Novedades del legacy (trazabilidad)</h5></div>
            <div class="card-body d-flex gap-2 flex-wrap">
              <span v-for="(n, e) in reporte.sinDato" :key="e" class="badge" :class="{ NORMALIZADA: 'bg-label-success', SIN_MAPEO: 'bg-label-warning', IGNORADA: 'bg-label-secondary', SIN_RELACION_LABORAL: 'bg-label-danger', ANULADA: 'bg-label-dark' }[e] || 'bg-label-secondary'">{{ e }}: {{ n }}</span>
              <span v-if="!Object.keys(reporte.sinDato).length" class="text-muted">Todavía no se normalizó ningún período.</span>
            </div>
          </div>
        </div>

        <div class="col-12" v-if="Object.keys(reporte.novedadesSinMapeo).length">
          <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Códigos de novedad usados en sue028s sin mapeo ({{ Object.keys(reporte.novedadesSinMapeo).length }})</h5></div>
            <div class="card-body d-flex flex-wrap gap-2">
              <Link v-for="(n, cod) in reporte.novedadesSinMapeo" :key="cod" :href="route('motor.novedades-mapeo.create', { cod_nov: cod })" class="badge bg-label-warning text-decoration-none">{{ cod }} <small>({{ n }})</small></Link>
            </div>
          </div>
        </div>

        <div class="col-12" v-if="reporte.validacionesConceptos.length">
          <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Validaciones de conceptos activos ({{ reporte.validacionesConceptos.length }})</h5></div>
            <div class="table-responsive" style="max-height: 420px"><table class="table table-sm mb-0">
              <thead class="table-light"><tr><th>Concepto</th><th>Regla</th><th>Nivel</th><th>Mensaje</th></tr></thead>
              <tbody><tr v-for="(v, i) in reporte.validacionesConceptos" :key="i"><td><Link :href="route('motor.conceptos.index', { buscar: v.concepto })" class="font-monospace">{{ v.concepto }}</Link></td><td><code>{{ v.codigo }}</code></td><td><span class="badge" :class="v.nivel === 'ERROR' ? 'bg-label-danger' : 'bg-label-warning'">{{ v.nivel }}</span></td><td class="small">{{ v.mensaje }}</td></tr></tbody>
            </table></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
