<script setup>
import { computed, onMounted, ref } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import axios from 'axios'
import Swal from 'sweetalert2'
import FloatMultiselect from '@/Components/FloatMultiselect.vue'
import Flash from '@/Components/Motor/Flash.vue'
import EstadoValidacionBadge from '@/Components/Motor/EstadoValidacionBadge.vue'
import { errorValidacion } from '@/Components/Motor/ConfirmarBaja.js'

const props = defineProps({ concepto: Object, incidencias_arca: Object, bases_zonda: Object, aplicabilidad: Array, catalogos: Object, versiones: Array })
const esEdicion = computed(() => !!props.concepto?.id)
const c = props.concepto

const form = useForm({
  codigo_concepto: c?.codigo_concepto ?? '', nombre_concepto: c?.nombre_concepto ?? '', alias_recibo: c?.alias_recibo ?? '', convenio_documental: c?.convenio_documental ?? '',
  tipo_concepto: c?.tipo_concepto ?? 'HABER', naturaleza: c?.naturaleza ?? 'REMUNERATIVO', signo: c?.signo ?? 'CREDITO', unidad_principal: c?.unidad_principal ?? '$',
  modo_calculo: c?.modo_calculo ?? 'FORMULA', modulo_responsable: c?.modulo_responsable ?? 'MOTOR_GENERAL', base_modulo: c?.base_modulo ?? '',
  codigo_parametro: c?.codigo_parametro ?? '', codigo_novedad_cantidad: c?.codigo_novedad_cantidad ?? '', codigo_novedad_importe: c?.codigo_novedad_importe ?? '',
  requiere_novedad: !!c?.requiere_novedad, editable_manual: !!c?.editable_manual, repetible: !!c?.repetible, permite_importe_cero: !!c?.permite_importe_cero, imprime_recibo: c ? !!c.imprime_recibo : true, es_concepto_sistema: !!c?.es_concepto_sistema, activo: c ? !!c.activo : true,
  tipo_repeticion: c?.tipo_repeticion ?? 'TODOS_LOS_MESES', intervalo_meses: c?.intervalo_meses ?? '', cantidad_meses: c?.cantidad_meses ?? '', meses_aplicacion: c?.meses_aplicacion ?? [], periodo_inicio: c?.periodo_inicio ?? '',
  tipos_liquidacion: c?.tipos_liquidacion ?? [], estrategia_calculo: c?.estrategia_calculo ?? '', fuente_cantidad: c?.fuente_cantidad ?? '', fuente_valor: c?.fuente_valor ?? '', fuente_importe: c?.fuente_importe ?? '',
  codigo_base_zonda: c?.codigo_base_zonda ?? '', factor_valor: c?.factor_valor ?? '', importe_fijo: c?.importe_fijo ?? '', regla_redondeo: c?.regla_redondeo ?? 'PARAMETRIZADA', politica_edicion: c?.politica_edicion ?? 'NO_EDITABLE',
  codigo_afip: c?.codigo_afip ?? '', clasificacion_funcional: c?.clasificacion_funcional ?? '', ausentismo_evitable: c?.ausentismo_evitable ?? 'NA', orden_calculo: c?.orden_calculo ?? '', orden_visual_sugerido: c?.orden_visual_sugerido ?? '',
  vigencia_desde: c?.vigencia_desde ?? new Date().toISOString().substring(0, 8) + '01', vigencia_hasta: c?.vigencia_hasta ?? '', observaciones_funcionales: c?.observaciones_funcionales ?? '',
  incidencias_arca: Object.fromEntries(props.catalogos.aplicaciones.map((a) => [a.clave, props.incidencias_arca?.[a.clave]?.incide ?? 'PENDIENTE'])),
  bases_zonda: Object.fromEntries(props.catalogos.bases_zonda.map((b) => [b.value, !!props.bases_zonda?.[b.value]?.integra])),
  aplicabilidad: (props.aplicabilidad ?? []).map((r) => ({ ...r })),
})

const TABS = [
  ['identidad', 'Identidad', 'ri-id-card-line'], ['naturaleza', 'Tipo y naturaleza', 'ri-price-tag-3-line'], ['aplicabilidad', 'Aplicabilidad', 'ri-group-line'], ['calculo', 'Configuración de cálculo', 'ri-calculator-line'],
  ['bases', 'Bases de Zonda', 'ri-stack-line'], ['comportamiento', 'Comportamiento', 'ri-settings-3-line'], ['participacion', 'Participación', 'ri-file-list-line'], ['periodicidad', 'Periodicidad', 'ri-calendar-line'],
  ['vigencia', 'Vigencia y versión', 'ri-history-line'], ['arca', 'ARCA / LSD', 'ri-government-line'],
]
const tab = ref('identidad')
const camposPorTab = {
  identidad: ['codigo_concepto', 'nombre_concepto', 'alias_recibo', 'convenio_documental', 'clasificacion_funcional', 'ausentismo_evitable'], naturaleza: ['tipo_concepto', 'naturaleza', 'signo', 'unidad_principal', 'modulo_responsable'],
  aplicabilidad: ['aplicabilidad'], calculo: ['modo_calculo', 'estrategia_calculo', 'fuente_cantidad', 'fuente_valor', 'fuente_importe', 'codigo_parametro', 'codigo_novedad_cantidad', 'codigo_novedad_importe', 'codigo_base_zonda', 'factor_valor', 'importe_fijo', 'base_modulo', 'regla_redondeo'],
  bases: ['bases_zonda'], comportamiento: ['requiere_novedad', 'editable_manual', 'repetible', 'permite_importe_cero', 'imprime_recibo', 'es_concepto_sistema', 'politica_edicion'], participacion: ['tipos_liquidacion'],
  periodicidad: ['tipo_repeticion', 'intervalo_meses', 'cantidad_meses', 'meses_aplicacion', 'periodo_inicio'], vigencia: ['vigencia_desde', 'vigencia_hasta', 'activo'], arca: ['codigo_afip', 'incidencias_arca'],
}
const tabConError = (k) => camposPorTab[k].some((f) => Object.keys(form.errors).some((e) => e === f || e.startsWith(f + '.')))
const irAPrimerError = () => { const t = TABS.find(([k]) => tabConError(k)); if (t) tab.value = t[0] }

const esModulo = computed(() => form.modo_calculo === 'MODULO_SISTEMA' || form.estrategia_calculo === 'MODULO_SISTEMA')
const consume = computed(() => form.codigo_base_zonda)
const rangoAfip = computed(() => props.catalogos.rangos_afip[form.naturaleza] ?? null)
const afipFueraDeRango = computed(() => form.codigo_afip && rangoAfip.value && (Number(form.codigo_afip) < rangoAfip.value[0] || Number(form.codigo_afip) > rangoAfip.value[1]))
const marcasObligatorias = ['SIPA|APORTE', 'SIPA|CONTRIBUCION', 'INSSJP|APORTE', 'INSSJP|CONTRIBUCION', 'OS|APORTE', 'OS|CONTRIBUCION', 'FSR|APORTE', 'FSR|CONTRIBUCION', 'AAFF|CONTRIBUCION', 'FNE|CONTRIBUCION', 'LRT|CONTRIBUCION']
const faltanR10 = computed(() => form.naturaleza === 'REMUNERATIVO' && form.tipo_concepto === 'HABER' ? marcasObligatorias.filter((m) => form.incidencias_arca[m] !== 'S') : [])
const precargar = (valor) => { for (const a of props.catalogos.aplicaciones) form.incidencias_arca[a.clave] = valor }
const precargaPorNaturaleza = () => precargar(form.naturaleza === 'REMUNERATIVO' ? 'S' : form.naturaleza === 'NO_REMUNERATIVO' ? 'N' : 'PENDIENTE')

const agregarRegla = () => form.aplicabilidad.push({ id: null, tipo_ambito: 'GENERAL', id_convenio: '', id_categoria: '', id_empresa: '', relacion_laboral_id: '', accion: 'INCLUIR', desde: form.vigencia_desde, hasta: '' })
const quitarRegla = (i) => form.aplicabilidad.splice(i, 1)
const nivel = { TRABAJADOR: 5, EMPRESA: 4, CATEGORIA: 3, CONVENIO: 2, GENERAL: 1 }

const guardar = () => {
  const onError = (e) => { irAPrimerError(); errorValidacion(e) }
  if (esEdicion.value) form.put(route('motor.conceptos.update', c.id), { onError, preserveScroll: true })
  else form.post(route('motor.conceptos.store'), { onError })
}
const validaciones = ref(null)
const validar = async () => {
  if (!esEdicion.value) { Swal.fire({ icon: 'info', title: 'Guardá primero', text: 'La validación completa corre sobre el concepto guardado.' }); return }
  const { data } = await axios.post(route('motor.conceptos.validar', c.id))
  validaciones.value = data.validaciones
  const errores = data.validaciones.filter((v) => v.nivel === 'ERROR').length
  Swal.fire({ icon: errores ? 'error' : data.validaciones.length ? 'warning' : 'success', title: errores ? `${errores} error(es)` : data.validaciones.length ? `${data.validaciones.length} aviso(s)` : 'Sin observaciones',
    html: data.validaciones.length ? `<div class="text-start small">${data.validaciones.map((v) => `• <b>${v.codigo}</b> ${v.mensaje}`).join('<br>')}</div>` : 'El concepto cumple las 20 validaciones.', confirmButtonText: 'Cerrar', buttonsStyling: false, customClass: { confirmButton: 'btn rounded-pill btn-primary' } })
}
const publicar = async () => {
  if (!esEdicion.value) return
  if (!form.isDirty ? false : !(await Swal.fire({ icon: 'warning', title: 'Hay cambios sin guardar', text: 'Guardá el borrador antes de publicar: se publica lo que está en la base.', showCancelButton: true, confirmButtonText: 'Publicar igual', cancelButtonText: 'Volver', reverseButtons: true, buttonsStyling: false, customClass: { confirmButton: 'btn rounded-pill btn-primary ms-2', cancelButton: 'btn rounded-pill btn-outline-secondary' } })).isConfirmed) return
  const { value } = await Swal.fire({
    title: 'Publicar versión', html: `<div class="text-start"><label class="form-label">Vigencia desde *</label><input id="p-desde" type="date" class="form-control" value="${form.vigencia_desde}"><label class="form-label mt-2">Vigencia hasta (baja futura, opcional)</label><input id="p-hasta" type="date" class="form-control" value="${form.vigencia_hasta || ''}"><label class="form-label mt-2">Tipo de cambio</label><select id="p-tipo" class="form-select">${props.catalogos.tipos_cambio.map((t) => `<option value="${t.value}">${t.label}</option>`).join('')}</select><label class="form-label mt-2">Motivo</label><input id="p-motivo" class="form-control" placeholder="Paritaria julio 2026…"></div>`,
    showCancelButton: true, reverseButtons: true, buttonsStyling: false, confirmButtonText: 'Publicar', cancelButtonText: 'Cancelar', customClass: { confirmButton: 'btn rounded-pill btn-primary ms-2', cancelButton: 'btn rounded-pill btn-outline-secondary' },
    preConfirm: () => ({ vigencia_desde: document.getElementById('p-desde').value, vigencia_hasta: document.getElementById('p-hasta').value || null, tipo_cambio: document.getElementById('p-tipo').value, motivo: document.getElementById('p-motivo').value }),
  })
  if (!value) return
  try {
    const { data } = await axios.post(route('motor.conceptos.publicar', c.id), value)
    await Swal.fire({ icon: 'success', title: data.sin_cambios ? 'Sin cambios: no se creó versión' : `Versión ${data.version.numero} publicada`, text: data.version ? `Vigencia ${data.version.vigencia}` : '', timer: 2500, showConfirmButton: false })
    router.visit(route('motor.conceptos.versiones', c.id))
  } catch (e) {
    const d = e.response?.data
    Swal.fire({ icon: 'error', title: 'No se pudo publicar', html: d?.errors ? Object.values(d.errors).flat().join('<br>') : d?.message || e.message, confirmButtonText: 'Cerrar', buttonsStyling: false, customClass: { confirmButton: 'btn rounded-pill btn-primary' } })
  }
}
const primero = ref(null)
onMounted(() => primero.value?.focus())
</script>

<template>
  <div class="container-xxl flex-grow-1 container-p-y">
    <Flash />
    <div class="app-ecommerce">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 row-gap-4">
        <div>
          <h4 class="mb-1">{{ esEdicion ? 'Editar concepto' : 'Nuevo concepto' }} <span v-if="esEdicion" class="font-monospace text-muted">{{ c.codigo_concepto }}</span></h4>
          <p class="mb-0 text-muted">Se edita el <b>borrador</b>. El motor liquida con la versión publicada vigente.
            <span v-if="esEdicion" class="badge ms-2" :class="c.estado_configuracion === 'PUBLICADO' ? 'bg-label-primary' : 'bg-label-secondary'">{{ c.estado_configuracion }}</span>
            <span v-if="esEdicion && !versiones.length" class="badge bg-label-danger ms-1">sin versión publicada</span></p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
          <Link v-if="esEdicion" :href="route('motor.conceptos.versiones', c.id)" class="btn btn-outline-info"><i class="ri-git-branch-line me-1"></i> Versiones ({{ versiones.length }})</Link>
          <button v-if="esEdicion" type="button" class="btn btn-outline-primary" @click="validar"><i class="ri-shield-check-line me-1"></i> Validar</button>
          <button v-if="esEdicion" type="button" class="btn btn-success" @click="publicar"><i class="ri-upload-cloud-line me-1"></i> Publicar</button>
          <Link :href="route('motor.conceptos.index')" class="btn btn-outline-secondary"><i class="ri-arrow-left-line me-1"></i> Volver</Link>
        </div>
      </div>

      <form @submit.prevent="guardar">
        <div class="card mb-4">
          <div class="card-header overflow-hidden">
            <ul class="nav nav-tabs" role="tablist">
              <li v-for="[k, label, icon] in TABS" :key="k" class="nav-item">
                <button type="button" class="nav-link" :class="{ active: tab === k }" @click="tab = k">
                  <i :class="icon" class="ri-20px d-sm-none"></i><span class="d-none d-sm-block">{{ label }}</span>
                  <span v-if="tabConError(k)" class="badge bg-danger rounded-pill ms-1" style="width: 8px; height: 8px; padding: 0">&nbsp;</span>
                </button>
              </li>
            </ul>
          </div>
          <div class="card-body">
            <!-- 1 Identidad -->
            <div v-show="tab === 'identidad'" class="row g-3">
              <div class="col-md-3"><label class="form-label">Código *</label><input ref="primero" v-model="form.codigo_concepto" class="form-control font-monospace" :class="{ 'is-invalid': form.errors.codigo_concepto }" :disabled="esEdicion" maxlength="50" style="text-transform: uppercase" /><div v-if="form.errors.codigo_concepto" class="invalid-feedback d-block">{{ form.errors.codigo_concepto }}</div><small class="text-muted">R8: letras, números y guión bajo. No se renombra una vez publicado.</small></div>
              <div class="col-md-6"><label class="form-label">Nombre *</label><input v-model="form.nombre_concepto" class="form-control" :class="{ 'is-invalid': form.errors.nombre_concepto }" /><div v-if="form.errors.nombre_concepto" class="invalid-feedback d-block">{{ form.errors.nombre_concepto }}</div></div>
              <div class="col-md-3"><label class="form-label">Alias en recibo</label><input v-model="form.alias_recibo" class="form-control" maxlength="100" /></div>
              <div class="col-md-3"><label class="form-label">Convenio documental</label><input v-model="form.convenio_documental" class="form-control" placeholder="371/03, 40/89, AMBOS" /></div>
              <div class="col-md-3"><label class="form-label">Clasificación funcional</label><select v-model="form.clasificacion_funcional" class="form-select"><option value="">—</option><option v-for="x in catalogos.clasificaciones" :key="x" :value="x">{{ x }}</option></select><small class="text-muted">R2: un concepto activo no puede quedar PENDIENTE_CLASIFICAR.</small></div>
              <div class="col-md-3" v-if="form.clasificacion_funcional === 'AUSENTISMO'"><label class="form-label">Ausentismo evitable *</label><select v-model="form.ausentismo_evitable" class="form-select"><option value="EVITABLE">Evitable</option><option value="NO_EVITABLE">No evitable</option></select></div>
              <div class="col-md-3"><label class="form-label">Orden visual</label><input v-model="form.orden_visual_sugerido" type="number" class="form-control" /></div>
              <div class="col-12"><label class="form-label">Observaciones funcionales</label><textarea v-model="form.observaciones_funcionales" class="form-control" rows="3"></textarea></div>
            </div>
            <!-- 2 Tipo y naturaleza -->
            <div v-show="tab === 'naturaleza'" class="row g-3">
              <div class="col-md-3"><label class="form-label">Tipo *</label><select v-model="form.tipo_concepto" class="form-select"><option v-for="o in catalogos.tipos" :key="o.value" :value="o.value">{{ o.label }}</option></select></div>
              <div class="col-md-3"><label class="form-label">Naturaleza *</label><select v-model="form.naturaleza" class="form-select"><option v-for="o in catalogos.naturalezas" :key="o.value" :value="o.value">{{ o.label }}</option></select><small class="text-muted">Define el rango ARCA y qué bases integra (R5/R10).</small></div>
              <div class="col-md-2"><label class="form-label">Signo *</label><select v-model="form.signo" class="form-select"><option v-for="o in catalogos.signos" :key="o.value" :value="o.value">{{ o.label }}</option></select><small v-if="form.tipo_concepto === 'HABER' && form.signo === 'DEBITO'" class="text-warning">HABER con DÉBITO: válido (inasistencia), verificá.</small></div>
              <div class="col-md-2"><label class="form-label">Unidad</label><select v-model="form.unidad_principal" class="form-select"><option v-for="u in ['$', '%', 'DIAS', 'HORAS', 'KM', 'UNIDADES']" :key="u" :value="u">{{ u }}</option></select></div>
              <div class="col-md-2"><label class="form-label">Módulo responsable</label><select v-model="form.modulo_responsable" class="form-select"><option v-for="m in catalogos.modulos" :key="m" :value="m">{{ m }}</option></select></div>
            </div>
            <!-- 3 Aplicabilidad -->
            <div v-show="tab === 'aplicabilidad'">
              <div class="alert alert-info small"><i class="ri-information-line me-1"></i>Precedencia: TRABAJADOR (5) &gt; EMPRESA (4) &gt; CATEGORÍA (3) &gt; CONVENIO (2) &gt; GENERAL (1). Gana la regla de mayor nivel; INCLUIR y EXCLUIR en el mismo nivel = conflicto = el motor bloquea. Sin regla coincidente el concepto no se liquida.</div>
              <table class="table table-sm align-middle">
                <thead class="table-light"><tr><th>Ámbito</th><th>Identificación</th><th>Acción</th><th>Desde</th><th>Hasta</th><th>Nivel</th><th></th></tr></thead>
                <tbody>
                  <tr v-if="!form.aplicabilidad.length"><td colspan="7" class="text-center text-muted py-3">Sin reglas: el concepto no aplica a nadie. Agregá al menos una (p. ej. GENERAL → INCLUIR).</td></tr>
                  <tr v-for="(r, i) in form.aplicabilidad" :key="i">
                    <td><select v-model="r.tipo_ambito" class="form-select form-select-sm"><option v-for="o in catalogos.ambitos" :key="o.value" :value="o.value">{{ o.label }}</option></select></td>
                    <td style="min-width: 260px">
                      <select v-if="r.tipo_ambito === 'CONVENIO'" v-model="r.id_convenio" class="form-select form-select-sm"><option value="">— convenio —</option><option v-for="o in catalogos.convenios" :key="o.value" :value="o.value">{{ o.label }}</option></select>
                      <select v-else-if="r.tipo_ambito === 'CATEGORIA'" v-model="r.id_categoria" class="form-select form-select-sm"><option value="">— categoría —</option><option v-for="o in catalogos.categorias" :key="o.value" :value="o.value">{{ o.label }}</option></select>
                      <select v-else-if="r.tipo_ambito === 'EMPRESA'" v-model="r.id_empresa" class="form-select form-select-sm"><option value="">— empresa —</option><option v-for="o in catalogos.empresas" :key="o.value" :value="o.value">{{ o.label }}</option></select>
                      <input v-else-if="r.tipo_ambito === 'TRABAJADOR'" v-model="r.relacion_laboral_id" type="number" class="form-control form-control-sm" placeholder="ID relación laboral" />
                      <span v-else class="text-muted small">Todos</span>
                    </td>
                    <td><select v-model="r.accion" class="form-select form-select-sm"><option value="INCLUIR">Incluir</option><option value="EXCLUIR">Excluir</option></select></td>
                    <td><input v-model="r.desde" type="date" class="form-control form-control-sm" /></td><td><input v-model="r.hasta" type="date" class="form-control form-control-sm" /></td>
                    <td><span class="badge bg-label-secondary">{{ nivel[r.tipo_ambito] }}</span></td>
                    <td><button type="button" class="btn btn-link text-danger p-0" @click="quitarRegla(i)"><i class="ri-delete-bin-line"></i></button></td>
                  </tr>
                </tbody>
              </table>
              <button type="button" class="btn btn-sm btn-outline-primary" @click="agregarRegla"><i class="ri-add-line me-1"></i>Agregar regla</button>
            </div>
            <!-- 4 Configuración de cálculo -->
            <div v-show="tab === 'calculo'" class="row g-3">
              <div class="col-md-3"><label class="form-label">Modo de cálculo *</label><select v-model="form.modo_calculo" class="form-select"><option v-for="o in catalogos.modos" :key="o.value" :value="o.value">{{ o.label }}</option></select></div>
              <div class="col-md-3"><label class="form-label">Estrategia *</label><select v-model="form.estrategia_calculo" class="form-select" :class="{ 'is-invalid': form.errors.estrategia_calculo }"><option value="">—</option><option v-for="o in catalogos.estrategias" :key="o.value" :value="o.value">{{ o.label }}</option></select><small class="text-muted">Sin DSL: cada estrategia es código probado.</small></div>
              <div class="col-md-3"><label class="form-label">Regla de redondeo</label><select v-model="form.regla_redondeo" class="form-select"><option v-for="o in catalogos.redondeos" :key="o.value" :value="o.value">{{ o.label }}</option></select></div>
              <div class="col-md-3" v-if="esModulo"><label class="form-label">Base ARCA del módulo</label><select v-model="form.base_modulo" class="form-select"><option value="">—</option><option v-for="o in catalogos.bases_arca" :key="o.value" :value="o.value">{{ o.label }}</option></select><small class="text-muted">APJUB→REM_1, APOSOC→REM_4, CONTSS→REM_2…</small></div>
              <template v-if="!esModulo">
                <div class="col-12"><hr class="my-1" /><small class="text-muted">Importe desdoblado en tres fuentes independientes (mockup v7.9.3): <b>cantidad × valor → importe</b>.</small></div>
                <div class="col-md-4"><label class="form-label">Fuente de cantidad</label><select v-model="form.fuente_cantidad" class="form-select"><option value="">—</option><option v-for="o in catalogos.fuentes" :key="o.value" :value="o.value">{{ o.label }}</option></select></div>
                <div class="col-md-4"><label class="form-label">Fuente de valor</label><select v-model="form.fuente_valor" class="form-select"><option value="">—</option><option v-for="o in catalogos.fuentes" :key="o.value" :value="o.value">{{ o.label }}</option></select></div>
                <div class="col-md-4"><label class="form-label">Fuente de importe</label><select v-model="form.fuente_importe" class="form-select"><option value="">—</option><option v-for="o in catalogos.fuentes" :key="o.value" :value="o.value">{{ o.label }}</option></select></div>
                <div class="col-md-4"><FloatMultiselect id="param" label="Parámetro" :options="catalogos.parametros" v-model="form.codigo_parametro" :error="form.errors.codigo_parametro" /></div>
                <div class="col-md-4"><FloatMultiselect id="novcant" label="Novedad (cantidad)" :options="catalogos.novedades" v-model="form.codigo_novedad_cantidad" :error="form.errors.codigo_novedad_cantidad" /></div>
                <div class="col-md-4"><FloatMultiselect id="novimp" label="Novedad (importe)" :options="catalogos.novedades" v-model="form.codigo_novedad_importe" :error="form.errors.codigo_novedad_importe" /></div>
                <div class="col-md-4"><FloatMultiselect id="bz" label="Base de Zonda que CONSUME" :options="catalogos.bases_zonda" v-model="form.codigo_base_zonda" :error="form.errors.codigo_base_zonda" /><small class="text-muted">Hora normal = base / horas mensuales. R13: no puede integrarla.</small></div>
                <div class="col-md-4"><label class="form-label">Factor de valor</label><input v-model="form.factor_valor" type="text" inputmode="decimal" class="form-control" placeholder="1.5" /></div>
                <div class="col-md-4"><label class="form-label">Importe fijo</label><input v-model="form.importe_fijo" type="text" inputmode="decimal" class="form-control" /></div>
              </template>
            </div>
            <!-- 5 Bases de Zonda -->
            <div v-show="tab === 'bases'">
              <div class="alert small" :class="consume ? 'alert-warning' : 'alert-info'"><i class="ri-information-line me-1"></i><span v-if="consume">Este concepto <b>consume</b> {{ consume }}: no puede integrarla (R13, circularidad directa).</span><span v-else>Marcá qué bases integra este concepto. Las bases responden a QUÉ se agrupa; la operación vive en la estrategia que consume la base.</span></div>
              <div class="row g-2">
                <div v-for="b in catalogos.bases_zonda" :key="b.value" class="col-md-6">
                  <div class="form-check form-switch p-2 border rounded" :class="{ 'border-danger': consume === b.value && form.bases_zonda[b.value] }">
                    <input :id="'bz' + b.value" v-model="form.bases_zonda[b.value]" class="form-check-input ms-0 me-2" type="checkbox" :disabled="consume === b.value" />
                    <label :for="'bz' + b.value" class="form-check-label"><b class="font-monospace">{{ b.value }}</b> <span class="text-muted">{{ b.label.split(' — ')[1] }}</span><span v-if="!b.activo" class="badge bg-label-secondary ms-1">inactiva</span></label>
                  </div>
                </div>
              </div>
            </div>
            <!-- 6 Comportamiento -->
            <div v-show="tab === 'comportamiento'" class="row g-3">
              <div v-for="[k, l, h] in [['requiere_novedad', 'Requiere novedad', 'Si no viene la novedad, el motor BLOQUEA (si no, el concepto se omite).'], ['editable_manual', 'Editable manual', 'Permite ajustar el importe en la liquidación.'], ['repetible', 'Repetible', 'Puede aparecer más de una vez en el recibo.'], ['permite_importe_cero', 'Permite importe cero', 'Si no, un resultado en cero no se liquida.'], ['imprime_recibo', 'Imprime en recibo', ''], ['es_concepto_sistema', 'Concepto de sistema', 'Protegido: solo lectura salvo activo/estado.']]" :key="k" class="col-md-4">
                <div class="form-check form-switch"><input :id="k" v-model="form[k]" class="form-check-input" type="checkbox" /><label :for="k" class="form-check-label">{{ l }}</label></div><small class="text-muted">{{ h }}</small>
              </div>
              <div class="col-md-4"><label class="form-label">Política de edición</label><select v-model="form.politica_edicion" class="form-select"><option v-for="o in catalogos.politicas" :key="o.value" :value="o.value">{{ o.label }}</option></select></div>
            </div>
            <!-- 7 Participación -->
            <div v-show="tab === 'participacion'">
              <p class="text-muted small">Tipos de liquidación en los que participa. Vacío = todos. (INDANT solo FINAL, por ejemplo.)</p>
              <div class="d-flex gap-4 flex-wrap"><div v-for="o in catalogos.tipos_liquidacion" :key="o.value" class="form-check"><input :id="'tl' + o.value" v-model="form.tipos_liquidacion" class="form-check-input" type="checkbox" :value="o.value" /><label :for="'tl' + o.value" class="form-check-label">{{ o.label }}</label></div></div>
            </div>
            <!-- 8 Periodicidad -->
            <div v-show="tab === 'periodicidad'" class="row g-3">
              <div class="col-md-4"><label class="form-label">Tipo de repetición</label><select v-model="form.tipo_repeticion" class="form-select"><option v-for="o in catalogos.repeticiones" :key="o.value" :value="o.value">{{ o.label }}</option></select></div>
              <div class="col-md-4" v-if="['CADA_N_MESES', 'DURANTE_N_MESES'].includes(form.tipo_repeticion)"><label class="form-label">Período de inicio *</label><input v-model="form.periodo_inicio" type="month" class="form-control" /></div>
              <div class="col-md-4" v-if="form.tipo_repeticion === 'CADA_N_MESES'"><label class="form-label">Intervalo (meses) *</label><input v-model="form.intervalo_meses" type="number" min="1" class="form-control" /></div>
              <div class="col-md-4" v-if="form.tipo_repeticion === 'DURANTE_N_MESES'"><label class="form-label">Cantidad de meses *</label><input v-model="form.cantidad_meses" type="number" min="1" class="form-control" /></div>
              <div class="col-12" v-if="form.tipo_repeticion === 'MESES_ESPECIFICOS'"><label class="form-label">Meses *</label><div class="d-flex flex-wrap gap-3"><div v-for="m in catalogos.meses" :key="m" class="form-check"><input :id="'m' + m" v-model="form.meses_aplicacion" class="form-check-input" type="checkbox" :value="m" /><label :for="'m' + m" class="form-check-label">{{ m }}</label></div></div></div>
            </div>
            <!-- 9 Vigencia -->
            <div v-show="tab === 'vigencia'" class="row g-3">
              <div class="col-md-3"><label class="form-label">Vigencia desde *</label><input v-model="form.vigencia_desde" type="date" class="form-control" :class="{ 'is-invalid': form.errors.vigencia_desde }" /><div v-if="form.errors.vigencia_desde" class="invalid-feedback d-block">{{ form.errors.vigencia_desde }}</div></div>
              <div class="col-md-3"><label class="form-label">Vigencia hasta</label><input v-model="form.vigencia_hasta" type="date" class="form-control" :class="{ 'is-invalid': form.errors.vigencia_hasta }" /><div v-if="form.errors.vigencia_hasta" class="invalid-feedback d-block">{{ form.errors.vigencia_hasta }}</div><small class="text-muted">Vacío = abierta.</small></div>
              <div class="col-md-3"><div class="form-check form-switch mt-4"><input id="activo" v-model="form.activo" class="form-check-input" type="checkbox" /><label for="activo" class="form-check-label">Activo</label></div></div>
              <div class="col-12" v-if="esEdicion">
                <h6>Versiones publicadas</h6>
                <span v-for="v in versiones" :key="v.numero" class="badge me-1" :class="v.usada ? 'bg-label-dark' : 'bg-label-primary'">v{{ v.numero }}: {{ v.desde }} → {{ v.hasta ?? '∞' }}{{ v.usada ? ' (usada)' : '' }}</span>
                <span v-if="!versiones.length" class="text-danger small">Sin versiones: publicá una para que el motor use este concepto.</span>
              </div>
            </div>
            <!-- 10 ARCA / LSD -->
            <div v-show="tab === 'arca'" class="row g-3">
              <div class="col-md-3"><label class="form-label">Código ARCA (LSD)</label><input v-model="form.codigo_afip" type="number" class="form-control font-monospace" :class="{ 'is-invalid': form.errors.codigo_afip || afipFueraDeRango }" /><div v-if="form.errors.codigo_afip" class="invalid-feedback d-block">{{ form.errors.codigo_afip }}</div><small class="text-muted" v-if="rangoAfip">R5: rango {{ form.naturaleza }} = {{ rangoAfip[0] }}–{{ rangoAfip[1] }}</small><small v-else class="text-muted">Este tipo no lleva código ARCA.</small></div>
              <div class="col-md-9">
                <div class="d-flex justify-content-between align-items-center mb-2"><h6 class="mb-0">Incidencia en las 13 aplicaciones oficiales</h6><div class="btn-group btn-group-sm"><button type="button" class="btn btn-outline-secondary" @click="precargaPorNaturaleza">Precargar por naturaleza</button><button type="button" class="btn btn-outline-secondary" @click="precargar('S')">Todas S</button><button type="button" class="btn btn-outline-secondary" @click="precargar('N')">Todas N</button></div></div>
                <div v-if="faltanR10.length" class="alert alert-warning small py-2"><b>R10:</b> un REMUNERATIVO debería integrar todas las bases (salvo regímenes diferenciales). Faltan: {{ faltanR10.join(', ') }}.</div>
                <table class="table table-sm align-middle">
                  <thead class="table-light"><tr><th>Col. LSD</th><th>Subsistema</th><th>Tipo</th><th>Base</th><th>Incide</th><th>Estado</th></tr></thead>
                  <tbody><tr v-for="a in catalogos.aplicaciones" :key="a.clave"><td>{{ a.columna }}</td><td><b>{{ a.subsistema }}</b></td><td>{{ a.tipo }}</td><td class="font-monospace">{{ a.base ?? '—' }}</td>
                    <td><div class="btn-group btn-group-sm"><button v-for="v in ['S', 'N', 'PENDIENTE']" :key="v" type="button" class="btn" :class="form.incidencias_arca[a.clave] === v ? (v === 'S' ? 'btn-success' : v === 'N' ? 'btn-secondary' : 'btn-warning') : 'btn-outline-secondary'" @click="form.incidencias_arca[a.clave] = v">{{ v === 'PENDIENTE' ? '?' : v }}</button></div></td>
                    <td><EstadoValidacionBadge :estado="incidencias_arca?.[a.clave]?.estado" small /></td></tr></tbody>
                </table>
                <small class="text-muted">PENDIENTE bloquea en PRODUCCIÓN y avisa en PRUEBA. La ausencia de fila no equivale a N.</small>
              </div>
            </div>
          </div>
          <div class="card-footer d-flex justify-content-end gap-2">
            <Link :href="route('motor.conceptos.index')" class="btn btn-outline-secondary">Cancelar</Link>
            <button type="submit" class="btn btn-primary" :disabled="form.processing"><span v-if="!form.processing">{{ esEdicion ? 'Guardar borrador' : 'Crear borrador' }}</span><span v-else><span class="spinner-border spinner-border-sm me-2"></span>Guardando...</span></button>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>

<style scoped>
.nav-tabs .nav-link.active { color: var(--bs-primary, #696cff); border-bottom: 2px solid var(--bs-primary, #696cff) !important; }
.tab-slider { display: none; }
</style>
