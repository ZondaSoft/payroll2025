<script setup>
import { ref } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import FloatMultiselect from '@/Components/FloatMultiselect.vue'
import Flash from '@/Components/Motor/Flash.vue'
import EstadoValidacionBadge from '@/Components/Motor/EstadoValidacionBadge.vue'
import { errorValidacion } from '@/Components/Motor/ConfirmarBaja.js'
import { fecha } from '@/Components/Motor/formato.js'

const props = defineProps({ relacion: Object, categorias: Array, encuadre: Object, regimenes: Array, situaciones: Array, novedades: Array, periodo: String, catalogos: Object })
const tab = ref('datos')
const form = useForm({
  estado: props.relacion.estado, fecha_egreso: props.relacion.egreso ?? '', fecha_antiguedad: props.relacion.antiguedad ?? '',
  categorias: props.categorias.map((c) => ({ id: c.id, estado: c.estado, hasta: c.hasta ?? '' })), nueva_categoria: null,
  encuadre: props.encuadre ? { codigo_condicion: props.encuadre.codigo_condicion, codigo_modalidad: props.encuadre.codigo_modalidad, codigo_actividad_arca: props.encuadre.codigo_actividad_arca ?? '', codigo_localidad_arca: props.encuadre.codigo_localidad_arca ?? '', actividad_interna: props.encuadre.actividad_interna ?? '', estado: props.encuadre.estado } : { codigo_condicion: '1', codigo_modalidad: '8', codigo_actividad_arca: '', codigo_localidad_arca: '', actividad_interna: '', estado: 'SIN_CONFIRMAR' },
  regimenes: props.regimenes.map((r) => ({ id: r.id, id_regimen: r.id_regimen, desde: r.desde, hasta: r.hasta ?? '', estado: r.estado, documento: r.documento ?? '' })),
})
const cambiarCategoria = () => (form.nueva_categoria = { id_categoria: '', desde: new Date().toISOString().substring(0, 8) + '01' })
const agregarRegimen = () => form.regimenes.push({ id: null, id_regimen: '', desde: props.relacion.ingreso, hasta: '', estado: 'SIN_CONFIRMAR', documento: '' })
const guardar = () => form.put(route('motor.relaciones.update', props.relacion.id), { preserveScroll: true, onError: (e) => errorValidacion(e) })
const estadoClase = (e) => ({ VIGENTE: 'bg-success', BAJA: 'bg-secondary', INCONSISTENTE_ORIGEN: 'bg-danger' }[e] || 'bg-secondary')
</script>

<template>
  <div class="container-xxl flex-grow-1 container-p-y">
    <Flash />
    <div class="app-ecommerce">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 row-gap-4">
        <div><h4 class="mb-1">Legajo <span class="font-monospace">{{ relacion.legajo }}</span> — {{ relacion.nombre }} <span class="badge ms-2" :class="estadoClase(relacion.estado)">{{ relacion.estado }}</span></h4><p class="mb-0 text-muted">{{ relacion.empresa }} · CUIL {{ relacion.cuil ?? '—' }} · ingreso {{ fecha(relacion.ingreso) }} · {{ relacion.anios }} años de antigüedad · relación #{{ relacion.id }} (sue001s.id {{ relacion.id_legajo }})</p></div>
        <div class="d-flex gap-2"><Link :href="route('motor.simulador.index')" class="btn btn-outline-primary"><i class="ri-play-line me-1"></i> Simular</Link><Link :href="route('motor.relaciones.index')" class="btn btn-outline-secondary"><i class="ri-arrow-left-line me-1"></i> Volver</Link></div>
      </div>
      <div v-if="relacion.estado === 'INCONSISTENTE_ORIGEN'" class="alert alert-danger"><i class="ri-error-warning-line me-1"></i>El legajo cambió de empresa en el origen. Esta relación no se mueve automáticamente: dala de BAJA y sincronizá para crear la nueva (otro empleador = otra relación laboral).</div>

      <form @submit.prevent="guardar">
        <div class="card mb-4">
          <div class="card-header overflow-hidden"><ul class="nav nav-tabs" role="tablist">
            <li v-for="[k, l] in [['datos', 'Datos'], ['categoria', 'Categoría'], ['arca', 'Encuadre ARCA'], ['regimenes', 'Regímenes'], ['situacion', 'Situación de revista'], ['novedades', 'Novedades ' + periodo]]" :key="k" class="nav-item"><button type="button" class="nav-link" :class="{ active: tab === k }" @click="tab = k">{{ l }}</button></li>
          </ul></div>
          <div class="card-body">
            <div v-show="tab === 'datos'" class="row g-3">
              <div class="col-md-3"><label class="form-label">Estado</label><select v-model="form.estado" class="form-select"><option v-for="e in catalogos.estados" :key="e.value" :value="e.value">{{ e.label }}</option></select></div>
              <div class="col-md-3"><label class="form-label">Fecha de egreso</label><input v-model="form.fecha_egreso" type="date" class="form-control" /></div>
              <div class="col-md-3"><label class="form-label">Antigüedad reconocida</label><input v-model="form.fecha_antiguedad" type="date" class="form-control" /><small class="text-muted">Si difiere del ingreso (sue001s.fecha_antiguedad_reconocida).</small></div>
              <div class="col-12 small text-muted">Los datos personales, jornada y sindicato se leen del legajo legacy en cada corrida. Empresa: <b>{{ relacion.empresa }}</b> (no editable: otra empresa = otra relación).</div>
            </div>
            <div v-show="tab === 'categoria'">
              <table class="table table-sm align-middle"><thead class="table-light"><tr><th>Categoría</th><th>Convenio</th><th>Desde</th><th>Hasta</th><th>Estado</th></tr></thead>
                <tbody><tr v-for="(c, i) in categorias" :key="c.id"><td><b>{{ c.codigo }}</b> {{ c.descripcion }}</td><td>{{ c.convenio ?? '—' }}</td><td>{{ fecha(c.desde) }}</td><td><input v-model="form.categorias[i].hasta" type="date" class="form-control form-control-sm" style="width: 170px" /></td><td><select v-model="form.categorias[i].estado" class="form-select form-select-sm" style="width: 160px"><option value="SIN_DATO">Sin dato</option><option value="SIN_CONFIRMAR">Sin confirmar</option><option value="CONFIRMADO">Confirmado</option></select></td></tr></tbody></table>
              <div v-if="form.nueva_categoria" class="row g-2 align-items-end border rounded p-2"><div class="col-md-6"><FloatMultiselect id="nc" label="Nueva categoría" :options="catalogos.categorias" v-model="form.nueva_categoria.id_categoria" /></div><div class="col-md-3"><label class="form-label">Desde</label><input v-model="form.nueva_categoria.desde" type="date" class="form-control" /></div><div class="col-md-3"><button type="button" class="btn btn-outline-secondary" @click="form.nueva_categoria = null">Cancelar</button></div><div class="col-12 small text-muted">La categoría vigente se cierra el día anterior (historización).</div></div>
              <button v-else type="button" class="btn btn-sm btn-outline-primary" @click="cambiarCategoria"><i class="ri-add-line me-1"></i>Cambio de categoría</button>
            </div>
            <div v-show="tab === 'arca'" class="row g-3">
              <div class="col-md-3"><label class="form-label">Condición ARCA *</label><select v-model="form.encuadre.codigo_condicion" class="form-select"><option v-for="c in catalogos.condiciones" :key="c.value" :value="c.value">{{ c.label }}</option></select></div>
              <div class="col-md-3"><label class="form-label">Modalidad *</label><select v-model="form.encuadre.codigo_modalidad" class="form-select"><option v-for="c in catalogos.modalidades" :key="c.value" :value="c.value">{{ c.label }}</option></select><small class="text-muted">710 = RIFL (asignar también el régimen).</small></div>
              <div class="col-md-3"><label class="form-label">Actividad ARCA</label><select v-model="form.encuadre.codigo_actividad_arca" class="form-select"><option value="">—</option><option v-for="c in catalogos.actividades" :key="c.value" :value="c.value">{{ c.label }}</option></select></div>
              <div class="col-md-3"><label class="form-label">Localidad ARCA</label><select v-model="form.encuadre.codigo_localidad_arca" class="form-select"><option value="">—</option><option v-for="c in catalogos.localidades" :key="c.value" :value="c.value">{{ c.label }}</option></select></div>
              <div class="col-md-4"><label class="form-label">Actividad interna</label><input v-model="form.encuadre.actividad_interna" class="form-control" placeholder="GENERAL, CONDUCTOR_CARGAS, TALLER" /></div>
              <div class="col-md-3"><label class="form-label">Estado</label><select v-model="form.encuadre.estado" class="form-select"><option value="SIN_DATO">Sin dato</option><option value="SIN_CONFIRMAR">Sin confirmar</option><option value="CONFIRMADO">Confirmado</option></select></div>
            </div>
            <div v-show="tab === 'regimenes'">
              <p class="small text-muted">Siempre exactamente un régimen BASE (GENERAL). SUSTITUTIVO reemplaza (RIFL 5 %); ADICIONAL suma (transporte 2 %); DESTINO redirige.</p>
              <table class="table table-sm align-middle"><thead class="table-light"><tr><th>Régimen</th><th>Desde</th><th>Hasta</th><th>Estado</th><th>Documento</th><th></th></tr></thead>
                <tbody><tr v-for="(r, i) in form.regimenes" :key="i"><td><select v-model="r.id_regimen" class="form-select form-select-sm"><option value="">—</option><option v-for="o in catalogos.regimenes" :key="o.value" :value="o.value">{{ o.label }}</option></select></td><td><input v-model="r.desde" type="date" class="form-control form-control-sm" /></td><td><input v-model="r.hasta" type="date" class="form-control form-control-sm" /></td><td><select v-model="r.estado" class="form-select form-select-sm"><option value="SIN_DATO">Sin dato</option><option value="SIN_CONFIRMAR">Sin confirmar</option><option value="CONFIRMADO">Confirmado</option></select></td><td><input v-model="r.documento" class="form-control form-control-sm" placeholder="Constancia…" /></td><td><button type="button" class="btn btn-link text-danger p-0" @click="form.regimenes.splice(i, 1)"><i class="ri-close-line"></i></button></td></tr></tbody></table>
              <button type="button" class="btn btn-sm btn-outline-primary" @click="agregarRegimen"><i class="ri-add-line me-1"></i>Agregar régimen</button>
            </div>
            <div v-show="tab === 'situacion'">
              <table class="table table-sm"><thead class="table-light"><tr><th>Período</th><th>Orden</th><th>Situación</th><th>Días</th><th>Origen</th></tr></thead><tbody><tr v-if="!situaciones.length"><td colspan="5" class="text-center text-muted py-3">Sin situaciones registradas.</td></tr><tr v-for="s in situaciones" :key="s.periodo + s.orden"><td>{{ s.periodo }}</td><td>{{ s.orden }}</td><td>{{ s.codigo }}</td><td>{{ s.dia_desde }}–{{ s.dia_hasta }}</td><td class="small text-muted">{{ s.origen }}</td></tr></tbody></table>
            </div>
            <div v-show="tab === 'novedades'">
              <table class="table table-sm"><thead class="table-light"><tr><th>Tipo</th><th class="text-end">Cantidad</th><th class="text-end">Importe</th><th>Origen</th></tr></thead><tbody><tr v-if="!novedades.length"><td colspan="4" class="text-center text-muted py-3">Sin novedades normalizadas para {{ periodo }}.</td></tr><tr v-for="(n, i) in novedades" :key="i"><td class="font-monospace">{{ n.codigo }}</td><td class="text-end">{{ n.cantidad ?? '' }}</td><td class="text-end">{{ n.valor ?? '' }}</td><td class="small">{{ n.origen }}</td></tr></tbody></table>
            </div>
          </div>
          <div class="card-footer d-flex justify-content-end gap-2"><Link :href="route('motor.relaciones.index')" class="btn btn-outline-secondary">Cancelar</Link><button type="submit" class="btn btn-primary" :disabled="form.processing">Guardar</button></div>
        </div>
      </form>
    </div>
  </div>
</template>

<style scoped>
.nav-tabs .nav-link.active { color: var(--bs-primary, #696cff); border-bottom: 2px solid var(--bs-primary, #696cff) !important; }
.tab-slider { display: none; }
</style>
