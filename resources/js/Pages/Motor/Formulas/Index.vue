<script setup>
import { computed, onMounted, ref } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import axios from 'axios'
import Flash from '@/Components/Motor/Flash.vue'
import EditorFormula from '@/Components/Motor/EditorFormula.vue'
import { errorValidacion } from '@/Components/Motor/ConfirmarBaja.js'

const props = defineProps({ items: Array, fecha: String, resumen: Object })

const fecha = ref(props.fecha)
const recargar = () => router.get(route('motor.formulas.index'), { fecha: fecha.value }, { preserveState: true, replace: true })

const catalogo = ref(null)
onMounted(async () => { const { data } = await axios.get(route('motor.formulas.catalogo'), { params: { fecha: props.fecha } }); catalogo.value = data })

const editando = ref(null)
const borrador = ref(null)
const analisis = ref(null)

const form = useForm({
  codigo_concepto: '', variante: 'UNICA', metodo: 'FORMULA',
  expr_condicion: '', expr_cantidad: '', expr_valor_unitario: '', expr_importe: '',
  unidad_cantidad: '', codigo_modulo: '', regla_redondeo: 'SIN_REDONDEO',
  novedades: {}, justificaciones: {},
  vigencia_desde: new Date().toISOString().substring(0, 8) + '01', motivo: '', fuente_normativa: '',
})

// Se edita una copia de la versión vigente: la vigente no se toca nunca.
const abrir = (f) => {
  editando.value = f
  borrador.value = JSON.parse(JSON.stringify(f.version))
  form.motivo = ''
  setTimeout(() => document.getElementById('editor-formula')?.scrollIntoView({ behavior: 'smooth' }), 50)
}
const cerrar = () => { editando.value = null; borrador.value = null; analisis.value = null }

const publicar = () => {
  Object.assign(form, borrador.value, {
    vigencia_desde: form.vigencia_desde, motivo: form.motivo, fuente_normativa: form.fuente_normativa,
  })
  form.post(route('motor.formulas.publicar'), { onError: (e) => errorValidacion(e), onSuccess: () => cerrar() })
}

const conProblemas = computed(() => props.items.filter((f) => !f.publica))
</script>

<template>
  <div class="container-xxl flex-grow-1 container-p-y">
    <Flash />
    <div class="app-ecommerce">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-6 row-gap-4">
        <div class="col-lg-8">
          <h4 class="mb-1">Fórmulas publicadas</h4>
          <p class="mb-0 text-muted">Las fórmulas son <b>datos versionados</b>, no código: viven en las columnas <code>expr_*</code> de cada versión, con su vigencia y su variante. Una paritaria se publica como versión nueva, sin desplegar nada.</p>
        </div>
        <div class="d-flex align-items-end gap-2">
          <div><label class="form-label small mb-1">Vigentes al</label><input v-model="fecha" type="date" class="form-control form-control-sm" @change="recargar" /></div>
        </div>
      </div>

      <div class="row g-4 mb-4">
        <div class="col-md-4"><div class="card"><div class="card-body text-center"><h3 class="mb-0">{{ resumen.total }}</h3><span class="text-muted small">versiones vigentes</span></div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body text-center"><h3 class="mb-0 text-success">{{ resumen.publican }}</h3><span class="text-muted small">pasan la validación</span></div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body text-center"><h3 class="mb-0" :class="resumen.con_problemas ? 'text-danger' : 'text-muted'">{{ resumen.con_problemas }}</h3><span class="text-muted small">con problemas</span></div></div></div>
      </div>

      <div v-if="conProblemas.length" class="alert alert-warning">
        <h6 class="alert-heading mb-2"><i class="ri-alert-line me-1"></i> Hay fórmulas publicadas que hoy no validarían</h6>
        <p class="small mb-2">Suele ser configuración que falta, no la fórmula: un parámetro que nadie creó, una unidad que el catálogo no declara, un valor por categoría sin cargar.</p>
        <ul class="small mb-0">
          <li v-for="f in conProblemas" :key="f.id"><span class="font-monospace fw-bold">{{ f.concepto }}/{{ f.variante }}</span> — {{ f.codigos.join(', ') }}</li>
        </ul>
      </div>

      <div class="card">
        <div class="table-responsive"><table class="table table-striped table-hover align-middle">
          <thead class="table-light"><tr><th>Concepto</th><th>Variante</th><th>Método</th><th>Importe</th><th>Unidad</th><th class="text-end">Problemas</th><th class="text-end">Acciones</th></tr></thead>
          <tbody>
            <tr v-for="f in items" :key="f.id">
              <td class="font-monospace fw-bold">{{ f.concepto }}</td>
              <td><span class="badge bg-label-info font-monospace">{{ f.variante }}</span></td>
              <td class="small">{{ f.metodo }}</td>
              <td class="font-monospace small" style="max-width: 420px">{{ f.version.expr_importe || f.version.codigo_modulo }}</td>
              <td><span class="badge" :class="f.unidad === 'ARS' ? 'bg-label-success' : 'bg-label-warning'">{{ f.unidad }}</span></td>
              <td class="text-end">
                <span v-if="f.errores" class="badge bg-label-danger">{{ f.errores }}</span>
                <span v-else-if="f.advertencias" class="badge bg-label-warning">{{ f.advertencias }}</span>
                <span v-else class="badge bg-label-success"><i class="ri-check-line"></i></span>
              </td>
              <td class="text-end"><button type="button" class="btn btn-sm btn-outline-primary" @click="abrir(f)"><i class="ri-edit-line me-1"></i> Nueva versión</button></td>
            </tr>
          </tbody>
        </table></div>
      </div>

      <div v-if="borrador" id="editor-formula" class="card mt-4 border-primary">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0">Nueva versión de <span class="font-monospace">{{ borrador.codigo_concepto }}/{{ borrador.variante }}</span></h5>
          <button type="button" class="btn-close" @click="cerrar"></button>
        </div>
        <div class="card-body">
          <div class="alert alert-info py-2 px-3 small">La versión vigente no se edita: se publica una nueva con su vigencia, y la anterior se cierra el día previo. Lo liquidado con ella tiene que poder reproducirse.</div>
          <EditorFormula v-model="borrador" :catalogo="catalogo" :fecha="fecha" @analisis="analisis = $event" />
          <div class="row g-3 mt-4 border-top pt-4">
            <div class="col-md-3"><label class="form-label">Vigencia desde *</label><input v-model="form.vigencia_desde" type="date" class="form-control" :class="{ 'is-invalid': form.errors.vigencia_desde }" /><div v-if="form.errors.vigencia_desde" class="invalid-feedback d-block">{{ form.errors.vigencia_desde }}</div></div>
            <div class="col-md-9"><label class="form-label">Motivo del cambio *</label><input v-model="form.motivo" class="form-control" :class="{ 'is-invalid': form.errors.motivo }" placeholder="Paritaria 08/2026: el adicional pasa del 8 % al 10 %." /><div v-if="form.errors.motivo" class="invalid-feedback d-block">{{ form.errors.motivo }}</div></div>
            <div class="col-12"><label class="form-label">Fuente normativa</label><input v-model="form.fuente_normativa" class="form-control" placeholder="CCT 371/03 art. 13 · acta paritaria del 15/07/2026" /></div>
            <div v-if="form.errors.expr_importe" class="col-12"><div class="alert alert-danger py-2 px-3 small mb-0">{{ form.errors.expr_importe }}</div></div>
            <div class="col-12 d-flex justify-content-end gap-2">
              <button type="button" class="btn btn-outline-secondary" @click="cerrar">Cancelar</button>
              <button type="button" class="btn btn-primary" :disabled="form.processing || !analisis?.publica" @click="publicar">
                <i class="ri-upload-line me-1"></i> Publicar versión
              </button>
            </div>
            <div v-if="analisis && !analisis.publica" class="col-12 text-end"><span class="text-muted small">El botón se habilita cuando la validación pasa en modo «Al publicar».</span></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
