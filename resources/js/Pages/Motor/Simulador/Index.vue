<script setup>
import { computed, reactive, ref } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'
import FloatMultiselect from '@/Components/FloatMultiselect.vue'
import Flash from '@/Components/Motor/Flash.vue'
import EstadoValidacionBadge from '@/Components/Motor/EstadoValidacionBadge.vue'
import { importe } from '@/Components/Motor/formato.js'

const props = defineProps({ empresas: Array, relaciones: Array, entornoDefault: String, tiposLiquidacion: Array, periodoDefault: String })

const form = reactive({ empresa_id: props.empresas[0]?.value ?? '', relacion_laboral_id: '', periodo: props.periodoDefault, tipo_liquidacion: 'NORMAL', entorno: props.entornoDefault, dias_liquidados: 30, factor_jornada: '1' })
const relacionesEmpresa = computed(() => props.relaciones.filter((r) => String(r.empresa_id) === String(form.empresa_id)))
const cargando = ref(false)
const resultado = ref(null)
const abiertas = ref({})

// Haberes calculados con las fórmulas publicadas: la traza de cada importe y el
// orden que resolvió el grafo. Es lo que responde «de dónde salió este número».
const haberes = ref(null)
const cargandoHaberes = ref(false)
const diasEfectivos = ref('')
const trazaAbierta = ref({})
const calcularHaberes = async () => {
  if (!form.relacion_laboral_id) {
    Swal.fire({ icon: 'warning', title: 'Faltan datos', text: 'Elegí la relación laboral a simular.', confirmButtonText: 'Entendido' })
    return
  }
  cargandoHaberes.value = true
  haberes.value = null
  try {
    const { data } = await axios.post(route('motor.simulador.haberes'), {
      relacion_laboral_id: form.relacion_laboral_id, periodo: form.periodo,
      dias_liquidados: form.dias_liquidados, dias_efectivos: diasEfectivos.value || null,
    })
    haberes.value = data
  } catch (e) {
    const errores = e.response?.data?.errors
    Swal.fire({ icon: 'error', title: 'No se pudo calcular', html: errores ? Object.values(errores).flat().join('<br>') : e.response?.data?.message || e.message })
  } finally { cargandoHaberes.value = false }
}
const estadoConceptoClase = (e) => ({ CALCULADO: 'bg-label-success', NO_APLICA: 'bg-label-secondary', BLOQUEADO: 'bg-label-danger', NO_MODELADO: 'bg-label-warning' }[e] ?? 'bg-label-secondary')

const simular = async () => {
  if (!form.relacion_laboral_id) {
    Swal.fire({ icon: 'warning', title: 'Faltan datos', text: 'Elegí la relación laboral a simular.', confirmButtonText: 'Entendido' })
    return
  }
  cargando.value = true
  resultado.value = null
  try {
    const { data } = await axios.post(route('motor.simulador.simular'), form)
    resultado.value = data
  } catch (e) {
    const errores = e.response?.data?.errors
    Swal.fire({ icon: 'error', title: 'No se pudo simular', html: errores ? Object.values(errores).flat().join('<br>') : e.response?.data?.message || e.message })
  } finally {
    cargando.value = false
  }
}
const estadoClase = (e) => ({ OK: 'bg-success', CON_AVISOS: 'bg-warning', BLOQUEADO: 'bg-danger' }[e] || 'bg-secondary')
const etapaClase = (e) => ({ OK: 'bg-label-success', BLOQUEADA: 'bg-label-danger', OMITIDA: 'bg-label-secondary' }[e] || 'bg-label-secondary')
const toggle = (k) => (abiertas.value[k] = !abiertas.value[k])
const porEtapa = computed(() => {
  const g = {}
  for (const b of resultado.value?.bloqueos ?? []) (g[b.etapa] ??= []).push(b)
  return g
})
</script>

<template>
  <div class="container-xxl flex-grow-1 container-p-y">
    <Flash />
    <div class="app-ecommerce">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-6 row-gap-4">
        <div class="col-lg-9">
          <h4 class="mb-1">Simulador de liquidación</h4>
          <p class="mb-0 text-muted">Ejecuta el motor sobre una relación laboral y un período. <b>No persiste</b> el resultado: es una prueba con traza completa. En PRODUCCIÓN solo los datos CONFIRMADO habilitan el cálculo.</p>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-body">
          <div class="row g-3 align-items-end">
            <div class="col-md-3"><FloatMultiselect id="empresa" label="Empresa" :options="empresas" v-model="form.empresa_id" @update:model-value="form.relacion_laboral_id = ''" /></div>
            <div class="col-md-4"><FloatMultiselect id="relacion" label="Relación laboral (legajo)" :options="relacionesEmpresa" v-model="form.relacion_laboral_id" /></div>
            <div class="col-md-2"><label class="form-label">Período</label><input v-model="form.periodo" type="month" class="form-control" /></div>
            <div class="col-md-3"><label class="form-label">Tipo de liquidación</label>
              <select v-model="form.tipo_liquidacion" class="form-select"><option v-for="t in tiposLiquidacion" :key="t.value" :value="t.value">{{ t.label }}</option></select></div>
            <div class="col-md-3"><label class="form-label">Entorno</label>
              <select v-model="form.entorno" class="form-select"><option value="PRUEBA">PRUEBA (SIN_CONFIRMAR con aviso)</option><option value="PRODUCCION">PRODUCCIÓN (solo CONFIRMADO)</option></select></div>
            <div class="col-md-2"><label class="form-label">Días liquidados</label><input v-model="form.dias_liquidados" type="number" min="1" max="31" class="form-control" /></div>
            <div class="col-md-2"><label class="form-label">Factor jornada</label><input v-model="form.factor_jornada" type="text" inputmode="decimal" class="form-control" /><small class="text-muted">1 = completa</small></div>
            <div class="col-md-5 d-flex justify-content-end">
              <button type="button" class="btn btn-primary" :disabled="cargando" @click="simular">
                <span v-if="!cargando"><i class="ri-play-line me-1"></i> Simular</span>
                <span v-else><span class="spinner-border spinner-border-sm me-2"></span>Calculando…</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <div>
            <h5 class="card-title mb-1">Haberes con las fórmulas publicadas</h5>
            <p class="mb-0 text-muted small">Evalúa las <code>expr_*</code> de cada versión vigente, en el orden que deriva el grafo de las dependencias. Muestra la traza de cada importe: qué entró, qué dio y de qué depende.</p>
          </div>
          <div class="d-flex align-items-end gap-2">
            <div><label class="form-label small mb-1">Días efectivos</label><input v-model="diasEfectivos" type="number" min="0" max="31" class="form-control form-control-sm" style="width: 110px" :placeholder="String(form.dias_liquidados)" /></div>
            <button type="button" class="btn btn-outline-primary" :disabled="cargandoHaberes" @click="calcularHaberes">
              <span v-if="!cargandoHaberes"><i class="ri-function-line me-1"></i> Calcular haberes</span>
              <span v-else><span class="spinner-border spinner-border-sm me-2"></span>Calculando…</span>
            </button>
          </div>
        </div>
        <div v-if="haberes" class="card-body">
          <div class="d-flex flex-wrap gap-3 align-items-center mb-4">
            <span class="badge bg-label-success">{{ haberes.resumen.calculados }} calculados</span>
            <span class="badge bg-label-secondary">{{ haberes.resumen.no_aplican }} no aplican</span>
            <span v-if="haberes.resumen.bloqueados" class="badge bg-label-danger">{{ haberes.resumen.bloqueados }} bloqueados</span>
            <span v-if="haberes.resumen.no_modelados" class="badge bg-label-warning">{{ haberes.resumen.no_modelados }} sin fórmula</span>
            <span class="badge bg-label-info">{{ haberes.resumen.bases_ok }} bases armadas</span>
            <span v-if="haberes.resumen.bases_bloqueadas" class="badge bg-label-danger">{{ haberes.resumen.bases_bloqueadas }} bases bloqueadas</span>
            <div class="ms-auto"><small class="text-muted d-block">Neto de lo que impacta</small><b class="font-monospace">{{ importe(haberes.neto) }}</b></div>
          </div>

          <h6 class="mb-2">Conceptos</h6>
          <div class="table-responsive mb-4"><table class="table table-sm align-middle">
            <thead class="table-light"><tr><th>Concepto</th><th>Variante</th><th>Estado</th><th class="text-end">Cantidad</th><th class="text-end">Valor unitario</th><th class="text-end">Importe</th><th></th></tr></thead>
            <tbody>
              <template v-for="c in haberes.conceptos" :key="c.codigo">
                <tr>
                  <td class="font-monospace fw-bold">{{ c.codigo }}</td>
                  <td><span v-if="c.variante" class="badge bg-label-info font-monospace">{{ c.variante }}</span></td>
                  <td>
                    <span class="badge" :class="estadoConceptoClase(c.estado)">{{ c.estado }}</span>
                    <code v-if="c.codigo_error" class="small text-danger ms-1">{{ c.codigo_error }}</code>
                  </td>
                  <td class="text-end font-monospace small">{{ c.cantidad }}</td>
                  <td class="text-end font-monospace small">{{ c.valor_unitario }}</td>
                  <td class="text-end font-monospace" :class="{ 'text-danger': c.factor_signo < 0 }">{{ c.importe ? importe(c.importe) : '' }}</td>
                  <td class="text-end">
                    <button v-if="c.traza.length || c.motivo" type="button" class="btn btn-link p-0 small" @click="trazaAbierta[c.codigo] = !trazaAbierta[c.codigo]">
                      <i :class="trazaAbierta[c.codigo] ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line'"></i> traza
                    </button>
                  </td>
                </tr>
                <tr v-if="trazaAbierta[c.codigo]" class="table-light">
                  <td colspan="7" class="small">
                    <div v-if="c.motivo" class="mb-2"><b>Motivo:</b> {{ c.motivo }}</div>
                    <div v-if="c.traza.length">
                      <b>De dónde salió:</b>
                      <ul class="mb-0 font-monospace">
                        <li v-for="(t, i) in c.traza" :key="i">{{ t }}</li>
                      </ul>
                    </div>
                    <div v-if="c.importe_bruto && c.importe_bruto !== c.importe" class="mt-2 text-muted">
                      Bruto antes del redondeo de la versión: <span class="font-monospace">{{ c.importe_bruto }}</span>
                      <span v-if="!c.impacta_neto" class="badge bg-label-secondary ms-2">no impacta el neto</span>
                    </div>
                  </td>
                </tr>
              </template>
            </tbody>
          </table></div>

          <h6 class="mb-2">Bases de Zonda</h6>
          <div class="table-responsive mb-4"><table class="table table-sm align-middle">
            <thead class="table-light"><tr><th>Base</th><th>Estado</th><th class="text-end">Importe</th><th>Integrantes</th><th>Excluidos</th></tr></thead>
            <tbody>
              <tr v-for="b in haberes.bases" :key="b.codigo">
                <td class="font-monospace fw-bold">{{ b.codigo }}</td>
                <td><span class="badge" :class="b.estado === 'OK' ? 'bg-label-success' : 'bg-label-danger'">{{ b.estado }}</span></td>
                <td class="text-end font-monospace">{{ b.importe ? importe(b.importe) : '' }}</td>
                <td class="small">
                  <span v-for="(imp, cod) in b.incluidos" :key="cod" class="badge bg-label-secondary me-1 mb-1 font-monospace" :title="imp">{{ cod }}</span>
                </td>
                <td class="small text-muted">
                  <div v-if="b.motivo" class="text-danger">{{ b.motivo }}</div>
                  <span v-for="(m, cod) in b.excluidos" :key="cod" class="d-block">{{ cod }}: {{ m }}</span>
                </td>
              </tr>
            </tbody>
          </table></div>

          <h6 class="mb-2">Orden de cálculo</h6>
          <p class="text-muted small">No está declarado en ninguna parte: lo deriva el grafo de las dependencias <code>CONC</code>/<code>BASE</code>/<code>MOD</code>. Agregar un concepto que consuma una base nueva reordena la corrida sin tocar código.</p>
          <div class="d-flex flex-wrap gap-1 align-items-center">
            <template v-for="(n, i) in haberes.orden" :key="n.nodo">
              <i v-if="i" class="ri-arrow-right-s-line text-muted"></i>
              <span class="badge font-monospace" :class="n.tipo === 'BASE' ? 'bg-label-primary' : 'bg-label-dark'">{{ n.codigo }}</span>
            </template>
          </div>
        </div>
      </div>

      <template v-if="resultado">
        <div class="card mb-4">
          <div class="card-body d-flex flex-wrap gap-4 align-items-center">
            <span class="badge fs-6" :class="estadoClase(resultado.estado)">{{ resultado.estado }}</span>
            <div><small class="text-muted d-block">Legajo</small><b>{{ resultado.cabecera.legajo }}</b> — {{ resultado.cabecera.nombre }}</div>
            <div><small class="text-muted d-block">Empresa</small>{{ resultado.cabecera.empresa }} <span class="badge bg-label-secondary ms-1">Encuadr. {{ resultado.cabecera.encuadramiento ?? '—' }}</span></div>
            <div><small class="text-muted d-block">Categoría / convenio</small>{{ resultado.cabecera.categoria ?? resultado.cabecera.codigo_categoria ?? '—' }} · {{ resultado.cabecera.convenio ?? '—' }}</div>
            <div><small class="text-muted d-block">Antigüedad</small>{{ resultado.cabecera.antiguedad_anios }} años (ingreso {{ resultado.cabecera.fecha_ingreso }})</div>
            <div><small class="text-muted d-block">Jornada</small>{{ resultado.cabecera.jornada }}</div>
            <div><small class="text-muted d-block">Regímenes</small>{{ (resultado.cabecera.regimenes || []).join(', ') || '—' }}</div>
            <div class="ms-auto d-flex gap-2 flex-wrap">
              <span v-for="(v, k) in resultado.etapas" :key="k" class="badge" :class="etapaClase(v)">{{ k }}: {{ v }}</span>
            </div>
          </div>
        </div>

        <div v-if="resultado.bloqueos.length" class="alert alert-danger" role="alert">
          <h6 class="alert-heading"><i class="ri-error-warning-line me-1"></i> {{ resultado.bloqueos.length }} bloqueo(s) — el motor no supone ni arrastra: cada uno indica qué falta parametrizar</h6>
          <div v-for="(lista, etapa) in porEtapa" :key="etapa" class="mt-2">
            <b class="text-uppercase small">{{ etapa }}</b>
            <ul class="mb-1">
              <li v-for="(b, i) in lista" :key="i"><code>{{ b.codigo }}</code> <span v-if="b.concepto" class="badge bg-label-danger">{{ b.concepto }}</span> {{ b.motivo }} <small v-if="b.entidad" class="text-muted">({{ b.entidad }}<span v-if="b.campo">.{{ b.campo }}</span>)</small><span v-if="b.fatal" class="badge bg-danger ms-1">fatal</span></li>
            </ul>
          </div>
        </div>
        <div v-if="resultado.avisos.length" class="alert alert-warning" role="alert">
          <h6 class="alert-heading"><i class="ri-alert-line me-1"></i> {{ resultado.avisos.length }} aviso(s)</h6>
          <ul class="mb-0"><li v-for="(a, i) in resultado.avisos" :key="i"><code>{{ a.codigo }}</code> {{ a.mensaje }}</li></ul>
        </div>

        <div class="row g-4">
          <div class="col-lg-7">
            <div class="card h-100">
              <div class="card-header"><h5 class="card-title mb-0">Haberes ejecutados</h5></div>
              <div class="table-responsive">
                <table class="table table-sm table-striped align-middle mb-0">
                  <thead class="table-light"><tr><th>Concepto</th><th>Estrategia</th><th class="text-end">Cantidad</th><th class="text-end">Valor unit.</th><th class="text-end">Importe</th><th>v</th></tr></thead>
                  <tbody>
                    <tr v-if="!resultado.haberes.length"><td colspan="6" class="text-center text-muted py-3">Sin haberes ejecutados.</td></tr>
                    <tr v-for="h in resultado.haberes" :key="h.codigo" :class="{ 'text-muted': h.naturaleza === 'NO_REMUNERATIVO' }">
                      <td><b class="font-monospace">{{ h.codigo }}</b> <small class="d-block text-muted">{{ h.nombre }} · {{ h.naturaleza }}</small></td>
                      <td><small>{{ h.estrategia }}</small><a href="#" class="ms-1 small" @click.prevent="toggle(h.codigo)">traza</a>
                        <pre v-if="abiertas[h.codigo]" class="small bg-light p-2 mt-1 mb-0" style="white-space: pre-wrap">{{ JSON.stringify(h.detalle, null, 1) }}</pre></td>
                      <td class="text-end">{{ h.cantidad ?? '' }}</td><td class="text-end">{{ h.valor_unitario ? importe(h.valor_unitario, 4) : '' }}</td>
                      <td class="text-end fw-bold">{{ importe(h.importe) }}</td><td><span class="badge bg-label-secondary">v{{ h.version }}</span></td>
                    </tr>
                  </tbody>
                  <tfoot v-if="resultado.haberes.length" class="table-light fw-bold">
                    <tr><td colspan="4">Remunerativo</td><td class="text-end">{{ importe(resultado.totales.remunerativo) }}</td><td></td></tr>
                    <tr><td colspan="4">No remunerativo</td><td class="text-end">{{ importe(resultado.totales.no_remunerativo) }}</td><td></td></tr>
                    <tr><td colspan="4">Total haberes</td><td class="text-end">{{ importe(resultado.totales.haberes) }}</td><td></td></tr>
                    <tr><td colspan="4">Aportes</td><td class="text-end text-danger">−{{ importe(resultado.totales.aportes) }}</td><td></td></tr>
                    <tr class="fs-6"><td colspan="4">NETO</td><td class="text-end">{{ importe(resultado.totales.neto) }}</td><td></td></tr>
                  </tfoot>
                </table>
              </div>
            </div>
          </div>
          <div class="col-lg-5">
            <div class="card mb-4">
              <div class="card-header"><h5 class="card-title mb-0">Bases ARCA (REM_n)</h5></div>
              <div class="table-responsive">
                <table class="table table-sm mb-0">
                  <thead class="table-light"><tr><th>Base</th><th class="text-end">Antes</th><th class="text-end">Ajuste tope</th><th class="text-end">Aplicada</th></tr></thead>
                  <tbody>
                    <tr v-for="(b, k) in resultado.bases_arca" :key="k">
                      <td><b>{{ k }}</b><a href="#" class="ms-1 small" @click.prevent="toggle('b' + k)">detalle</a>
                        <div v-if="abiertas['b' + k]" class="small text-muted">incluye: {{ Object.keys(b.incluidos).join(', ') || '—' }}<br />excluye: {{ Object.entries(b.excluidos).map(([c, m]) => `${c} (${m})`).join(', ') || '—' }}</div></td>
                      <td class="text-end">{{ importe(b.importe_antes_ajustes) }}</td>
                      <td class="text-end" :class="{ 'text-danger': Number(b.ajuste_tope) < 0 }">{{ Number(b.ajuste_tope) ? importe(b.ajuste_tope) : '' }}<small v-if="b.tope_aplicado" class="d-block text-muted">{{ b.tope_aplicado }}</small></td>
                      <td class="text-end fw-bold">{{ importe(b.importe_aplicado) }}</td>
                    </tr>
                    <tr v-if="!Object.keys(resultado.bases_arca).length"><td colspan="4" class="text-center text-muted py-3">Sin bases (etapa bloqueada u omitida).</td></tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div class="card mb-4">
              <div class="card-header"><h5 class="card-title mb-0">Aportes del trabajador</h5></div>
              <div class="table-responsive"><table class="table table-sm mb-0">
                <thead class="table-light"><tr><th>Concepto</th><th>Subsist.</th><th class="text-end">Base</th><th class="text-end">%</th><th class="text-end">Importe</th></tr></thead>
                <tbody>
                  <tr v-for="a in resultado.aportes" :key="a.concepto"><td><b>{{ a.concepto }}</b></td><td>{{ a.subsistema }} <small class="text-muted">{{ a.base }}</small></td><td class="text-end">{{ importe(a.base_importe) }}</td><td class="text-end">{{ a.porcentaje }}</td><td class="text-end fw-bold">{{ importe(a.importe) }}</td></tr>
                  <tr v-if="!resultado.aportes.length"><td colspan="5" class="text-center text-muted py-3">Sin aportes.</td></tr>
                </tbody></table></div>
            </div>
            <div class="card">
              <div class="card-header"><h5 class="card-title mb-0">Contribuciones patronales</h5></div>
              <div class="table-responsive"><table class="table table-sm mb-0">
                <thead class="table-light"><tr><th>Subsist.</th><th>Regla</th><th class="text-end">Base</th><th class="text-end">%</th><th class="text-end">Importe</th></tr></thead>
                <tbody>
                  <tr v-for="(c, i) in resultado.contribuciones" :key="i"><td><b>{{ c.subsistema }}</b> <small class="text-muted">{{ c.base }}</small></td><td><span class="badge bg-label-info">{{ c.regla }}</span> <small>{{ c.regimen }}</small></td><td class="text-end">{{ importe(c.base_importe) }}</td><td class="text-end">{{ c.porcentaje }}</td><td class="text-end fw-bold">{{ importe(c.importe) }}</td></tr>
                  <tr v-if="!resultado.contribuciones.length"><td colspan="5" class="text-center text-muted py-3">Sin contribuciones.</td></tr>
                </tbody>
                <tfoot v-if="resultado.contribuciones.length" class="table-light fw-bold"><tr><td colspan="4">Total contribuciones</td><td class="text-end">{{ importe(resultado.totales.contribuciones) }}</td></tr></tfoot>
              </table></div>
            </div>
          </div>
        </div>

        <div class="card mt-4">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Conceptos no ejecutados ({{ resultado.no_ejecutados.length }}) y parámetros resueltos</h5>
            <a href="#" class="small" @click.prevent="toggle('ne')">{{ abiertas.ne ? 'ocultar' : 'mostrar' }}</a>
          </div>
          <div v-if="abiertas.ne" class="card-body">
            <div class="row">
              <div class="col-md-7"><ul class="small mb-0"><li v-for="n in resultado.no_ejecutados" :key="n.codigo"><b class="font-monospace">{{ n.codigo }}</b>: {{ n.motivo }}</li></ul></div>
              <div class="col-md-5"><table class="table table-sm small mb-0"><thead><tr><th>Parámetro</th><th>Valor</th><th>Alcance</th><th></th></tr></thead>
                <tbody><tr v-for="(p, k) in resultado.traza.parametros" :key="k"><td class="font-monospace">{{ k }}</td><td>{{ p.valor }}</td><td>{{ p.alcance }}</td><td><EstadoValidacionBadge :estado="p.estado" small /></td></tr></tbody></table></div>
            </div>
          </div>
        </div>
      </template>
    </div>
  </div>
</template>
