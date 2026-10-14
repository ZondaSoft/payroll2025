<script setup>
import { computed, ref, watch } from 'vue'
import axios from 'axios'

/**
 * Editor de una versión de fórmula con validación dimensional en vivo.
 *
 * Lo que valida es el mismo ValidadorVersion que corre al publicar: lo que acá sale
 * en verde es exactamente lo que el motor va a dejar publicar. En modo EDICION los
 * avisos de migración y la unidad sin declarar son advertencias; al publicar
 * bloquean, y el panel lo dice.
 */
const props = defineProps({
  modelValue: { type: Object, required: true },
  catalogo: { type: Object, default: null },
  fecha: { type: String, default: null },
})
const emit = defineEmits(['update:modelValue', 'analisis'])

const v = computed(() => props.modelValue)
const set = (k, val) => emit('update:modelValue', { ...v.value, [k]: val })

const analisis = ref(null)
const analizando = ref(false)
const modo = ref('EDICION')

const COMPONENTES = [
  ['expr_condicion', 'Condición', 'Si no se cumple, el concepto queda NO_APLICA. Tiene que devolver un valor lógico.'],
  ['expr_cantidad', 'Cantidad (CANT)', 'La cantidad del recibo: horas, días, kilómetros. Su unidad se declara abajo.'],
  ['expr_valor_unitario', 'Valor unitario (VALUNI)', 'El precio de una unidad. Puede ser compuesto: ARS/HORAS.'],
  ['expr_importe', 'Importe *', 'Lo que va al recibo. Debe devolver ARS. Puede usar CANT y VALUNI.'],
]

let t = null
const analizar = async () => {
  clearTimeout(t)
  t = setTimeout(async () => {
    analizando.value = true
    try {
      const { data } = await axios.post(route('motor.formulas.analizar'), { ...v.value, modo: modo.value, fecha: props.fecha })
      analisis.value = data
      emit('analisis', data)
    } catch (e) {
      analisis.value = { publica: false, errores: [{ codigo: 'ERROR', mensaje: e.response?.data?.message ?? e.message }], advertencias: [], codigos: [], dependencias: [], unidades: {} }
    } finally { analizando.value = false }
  }, 400)
}
watch([() => v.value.expr_condicion, () => v.value.expr_cantidad, () => v.value.expr_valor_unitario, () => v.value.expr_importe,
  () => v.value.unidad_cantidad, () => v.value.metodo, () => v.value.codigo_modulo, () => v.value.novedades, modo],
  analizar, { immediate: true, deep: true })

const unidadDe = (k) => analisis.value?.unidades?.[{ expr_condicion: 'condicion', expr_cantidad: 'cantidad', expr_valor_unitario: 'valor', expr_importe: 'importe' }[k]]
const erroresDe = (etiqueta) => (analisis.value?.errores ?? []).filter((e) => e.mensaje.startsWith(etiqueta + ':'))

const novedadesUsadas = computed(() => (analisis.value?.dependencias ?? []).filter((d) => d.tipo === 'NOVEDAD').map((d) => d.codigo))
const declaradas = computed(() => Object.keys(v.value.novedades ?? {}))
const sinDeclarar = computed(() => novedadesUsadas.value.filter((c) => !declaradas.value.includes(c)))
const declararNovedad = (codigo, obligatoriedad) => set('novedades', { ...(v.value.novedades ?? {}), [codigo]: obligatoriedad })
const quitarNovedad = (codigo) => { const n = { ...(v.value.novedades ?? {}) }; delete n[codigo]; set('novedades', n) }

const literalesPendientes = computed(() => (analisis.value?.errores ?? [])
  .filter((e) => e.codigo === 'DSL_LITERAL_SIN_JUSTIFICAR')
  .map((e) => e.mensaje.match(/literal (\S+)/)?.[1]).filter(Boolean))
const justificar = (literal, texto) => set('justificaciones', { ...(v.value.justificaciones ?? {}), [literal]: texto })

const aristas = computed(() => (analisis.value?.dependencias ?? []).filter((d) => d.arista))
const entradas = computed(() => (analisis.value?.dependencias ?? []).filter((d) => !d.arista))
const insertar = (texto) => {
  const campo = document.activeElement
  if (campo?.tagName === 'TEXTAREA' || campo?.tagName === 'INPUT') {
    const i = campo.selectionStart ?? campo.value.length
    campo.setRangeText(texto, i, campo.selectionEnd ?? i, 'end')
    campo.dispatchEvent(new Event('input'))
  }
}
</script>

<template>
  <div class="row g-4">
    <div class="col-xl-7">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h6 class="card-title mb-0">Expresiones</h6>
          <div class="d-flex align-items-center gap-2">
            <select v-model="v.metodo" class="form-select form-select-sm" style="width: auto" @change="set('metodo', $event.target.value)">
              <option value="FORMULA">Fórmula</option>
              <option value="MODULO">Módulo del motor</option>
            </select>
          </div>
        </div>
        <div class="card-body">
          <div v-if="v.metodo === 'MODULO'" class="row g-3">
            <div class="col-12">
              <label class="form-label">Módulo responsable *</label>
              <select :value="v.codigo_modulo" class="form-select font-monospace" @change="set('codigo_modulo', $event.target.value)">
                <option value="">—</option>
                <option v-for="m in catalogo?.MOD ?? []" :key="m.codigo" :value="m.codigo">{{ m.codigo }} ({{ m.unidad }})</option>
              </select>
              <div class="form-text">El importe lo produce el módulo, no una expresión. Es el punto de extensión del contrato: la mecánica nueva entra como módulo y no como sintaxis del DSL.</div>
            </div>
          </div>

          <div v-else class="row g-3">
            <div v-for="[campo, etiqueta, ayuda] in COMPONENTES" :key="campo" class="col-12">
              <div class="d-flex justify-content-between align-items-end">
                <label class="form-label mb-1">{{ etiqueta }}</label>
                <span v-if="unidadDe(campo)" class="badge" :class="erroresDe(etiqueta.replace(/ \(.*/, '').replace(' *', '')).length ? 'bg-label-danger' : 'bg-label-success'">{{ unidadDe(campo) }}</span>
              </div>
              <textarea :value="v[campo]" class="form-control font-monospace" rows="2" spellcheck="false"
                :class="{ 'is-invalid': erroresDe(etiqueta.replace(/ \(.*/, '').replace(' *', '')).length }"
                @input="set(campo, $event.target.value)"></textarea>
              <div class="form-text">{{ ayuda }}</div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Unidad de la cantidad</label>
              <select :value="v.unidad_cantidad" class="form-select" @change="set('unidad_cantidad', $event.target.value)">
                <option value="">—</option>
                <option v-for="u in ['HORAS', 'DIAS', 'KM', 'UNIDADES', 'ARS', 'NUMERIC']" :key="u" :value="u">{{ u }}</option>
              </select>
              <div class="form-text">Al publicar tiene que coincidir con lo que devuelve la expresión de cantidad.</div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Redondeo de la versión</label>
              <select :value="v.regla_redondeo" class="form-select" @change="set('regla_redondeo', $event.target.value)">
                <option value="SIN_REDONDEO">Sin redondeo (escala completa)</option>
                <option value="DECIMAL">Dos decimales</option>
                <option value="ENTERO">Entero</option>
              </select>
              <div class="form-text">Es el importe redondeado el que integra las bases.</div>
            </div>
          </div>
        </div>
      </div>

      <div v-if="v.metodo !== 'MODULO'" class="card mt-4">
        <div class="card-header"><h6 class="card-title mb-0">Novedades declaradas</h6></div>
        <div class="card-body">
          <p class="text-muted small mb-3">La fórmula y la declaración tienen que coincidir en los dos sentidos: una novedad usada sin declarar no publica, y una declarada que ninguna expresión usa tampoco. Una <b>REQUERIDA</b> ausente deja el concepto <code>NO_APLICA</code>; una <b>OPCIONAL</b> ausente vale cero.</p>
          <div v-if="sinDeclarar.length" class="alert alert-warning py-2 px-3 small mb-3">
            Sin declarar: 
            <span v-for="c in sinDeclarar" :key="c" class="me-2">
              <code>{{ c }}</code>
              <button type="button" class="btn btn-sm btn-link p-0 ms-1" @click="declararNovedad(c, 'REQUERIDA')">requerida</button> ·
              <button type="button" class="btn btn-sm btn-link p-0" @click="declararNovedad(c, 'OPCIONAL')">opcional</button>
            </span>
          </div>
          <table v-if="declaradas.length" class="table table-sm align-middle mb-0">
            <tbody>
              <tr v-for="c in declaradas" :key="c">
                <td class="font-monospace">{{ c }}</td>
                <td style="width: 160px">
                  <select :value="v.novedades[c]" class="form-select form-select-sm" @change="declararNovedad(c, $event.target.value)">
                    <option value="REQUERIDA">REQUERIDA</option>
                    <option value="OPCIONAL">OPCIONAL</option>
                  </select>
                </td>
                <td class="text-end"><button type="button" class="btn btn-link text-danger p-0" @click="quitarNovedad(c)"><i class="ri-delete-bin-line"></i></button></td>
              </tr>
            </tbody>
          </table>
          <p v-else class="text-muted small mb-0">Ninguna declarada.</p>
        </div>
      </div>

      <div v-if="literalesPendientes.length" class="card mt-4 border-warning">
        <div class="card-header"><h6 class="card-title mb-0 text-warning"><i class="ri-alert-line me-1"></i> Literales sin justificar</h6></div>
        <div class="card-body">
          <p class="text-muted small mb-3">Un número distinto de 0 o 1 necesita justificación declarada para publicarse: el 1,5 de las horas extras al 50 % no es un número cualquiera, es el recargo del art. 201 de la LCT.</p>
          <div v-for="l in literalesPendientes" :key="l" class="mb-2">
            <label class="form-label mb-1">Literal <code>{{ l }}</code></label>
            <input :value="v.justificaciones?.[l] ?? ''" class="form-control form-control-sm" placeholder="LCT art. 201 — recargo del 50 %" @input="justificar(l, $event.target.value)" />
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-5">
      <div class="card sticky-top" style="top: 1rem">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h6 class="card-title mb-0">
            Validación
            <span v-if="analizando" class="spinner-border spinner-border-sm ms-2"></span>
          </h6>
          <select v-model="modo" class="form-select form-select-sm" style="width: auto">
            <option value="EDICION">Editando</option>
            <option value="PUBLICACION">Al publicar</option>
          </select>
        </div>
        <div class="card-body">
          <div v-if="analisis" class="d-flex align-items-center gap-2 mb-3">
            <span class="badge" :class="analisis.publica ? 'bg-success' : 'bg-danger'">
              <i :class="analisis.publica ? 'ri-check-line' : 'ri-close-line'" class="me-1"></i>
              {{ analisis.publica ? (modo === 'PUBLICACION' ? 'Publica' : 'Sin errores') : 'No publica' }}
            </span>
            <span v-if="analisis.unidad_importe" class="badge bg-label-primary">importe: {{ analisis.unidad_importe }}</span>
          </div>

          <div v-if="analisis?.errores?.length" class="mb-3">
            <h6 class="small text-danger mb-2">Errores</h6>
            <ul class="list-unstyled mb-0 small">
              <li v-for="(e, i) in analisis.errores" :key="i" class="mb-2">
                <code class="text-danger">{{ e.codigo }}</code><br />{{ e.mensaje }}
              </li>
            </ul>
          </div>

          <div v-if="analisis?.advertencias?.length" class="mb-3">
            <h6 class="small text-warning mb-2">Advertencias <span class="text-muted fw-normal">(bloquean al publicar)</span></h6>
            <ul class="list-unstyled mb-0 small">
              <li v-for="(a, i) in analisis.advertencias" :key="i" class="mb-2">
                <code class="text-warning">{{ a.codigo }}</code><br />{{ a.mensaje }}
              </li>
            </ul>
          </div>

          <div v-if="aristas.length" class="mb-3">
            <h6 class="small mb-2">Depende de <span class="text-muted fw-normal">(entra al grafo)</span></h6>
            <span v-for="(d, i) in aristas" :key="i" class="badge bg-label-dark me-1 mb-1 font-monospace">{{ d.tipo === 'CONCEPTO' ? 'CONC' : d.tipo === 'MODULO' ? 'MOD' : d.tipo }}({{ d.codigo }})</span>
          </div>

          <div v-if="entradas.length" class="mb-3">
            <h6 class="small mb-2">Lee <span class="text-muted fw-normal">(no genera orden)</span></h6>
            <span v-for="(d, i) in entradas" :key="i" class="badge bg-label-secondary me-1 mb-1 font-monospace">{{ { PARAMETRO: 'PARAM', NOVEDAD: 'NOV', VARIABLE: 'VAR', VALCAT: 'VALCAT' }[d.tipo] ?? d.tipo }}({{ d.codigo }})</span>
          </div>

          <div v-if="analisis?.sin_unidad?.length" class="alert alert-warning py-2 px-3 small mb-0">
            El catálogo no declara la unidad de: <code>{{ analisis.sin_unidad.join(', ') }}</code>. Sin eso no se puede verificar que el importe sea ARS.
          </div>
        </div>

        <div v-if="catalogo" class="card-footer">
          <h6 class="small mb-2">Referencias disponibles</h6>
          <div class="accordion accordion-flush" id="refs">
            <div v-for="pref in ['PARAM', 'NOV', 'VAR', 'VALCAT', 'CONC', 'BASE', 'MOD']" :key="pref" class="accordion-item">
              <h2 class="accordion-header"><button class="accordion-button collapsed py-2 small" type="button" data-bs-toggle="collapse" :data-bs-target="`#ref-${pref}`">{{ pref }} <span class="text-muted ms-2">{{ (catalogo[pref] ?? []).length }}</span></button></h2>
              <div :id="`ref-${pref}`" class="accordion-collapse collapse" data-bs-parent="#refs">
                <div class="accordion-body py-2" style="max-height: 220px; overflow-y: auto">
                  <button v-for="r in catalogo[pref] ?? []" :key="r.codigo" type="button" class="btn btn-sm btn-outline-secondary me-1 mb-1 font-monospace" style="font-size: 0.7rem" @click="insertar(`${pref}(${r.codigo})`)">
                    {{ r.codigo }}<span v-if="r.unidad" class="text-muted ms-1">{{ r.unidad }}</span>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
