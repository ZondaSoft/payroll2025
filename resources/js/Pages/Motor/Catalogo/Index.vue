<script setup>
import { ref, watch } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import EstadoValidacionBadge from '@/Components/Motor/EstadoValidacionBadge.vue'
import Flash from '@/Components/Motor/Flash.vue'
import { confirmar, exito } from '@/Components/Motor/ConfirmarBaja.js'
import { importe, porcentaje, fecha, periodo } from '@/Components/Motor/formato.js'

const props = defineProps({ definicion: Object, items: Object, filtros: Object, opciones: Object })

const buscar = ref(props.filtros?.buscar ?? '')
const filtros = ref({ ...(props.filtros ?? {}) })
let t = null
watch(buscar, (v) => { clearTimeout(t); t = setTimeout(() => aplicar({ buscar: v }), 300) })
const aplicar = (extra = {}) => router.get(route(props.definicion.ruta + '.index'), { ...filtros.value, ...extra }, { preserveState: true, preserveScroll: true, replace: true })

const columnas = props.definicion.campos.filter((c) => c.enLista)
const etiquetaOpcion = (campo, valor) => {
  const lista = campo.opciones || props.opciones?.[campo.nombre]
  const o = lista?.find((x) => String(x.value) === String(valor))
  return o ? o.label : valor
}
const mostrar = (campo, item) => {
  const v = item[campo.nombre]
  if (campo.nombre === 'id_subsistema' && item.codigo_subsistema) return item.codigo_subsistema
  if (v === null || v === undefined || v === '') return '—'
  switch (campo.tipo) {
    case 'booleano': return v ? 'Sí' : 'No'
    case 'decimal': return importe(v, 2)
    case 'porcentaje': return porcentaje(v)
    case 'fecha': return fecha(v)
    case 'periodo': return periodo(v)
    case 'select': return etiquetaOpcion(campo, v)
    default: return v
  }
}
const claveDe = (item) => item[Object.keys(item)[0]]

const baja = async (item) => {
  const d = props.definicion
  const ok = await confirmar({ titulo: d.baja_logica ? `¿Dar de baja este ${d.titulo.toLowerCase()}?` : `¿Eliminar este ${d.titulo.toLowerCase()}?`,
    html: d.baja_logica ? 'Queda inactivo (baja lógica): los registros usados en liquidaciones no se borran.' : 'Esta acción no se puede deshacer.', confirmar: d.baja_logica ? 'Sí, dar de baja' : 'Sí, eliminar', peligro: true })
  if (!ok) return
  router.delete(route(d.ruta + '.destroy', claveDe(item)), { preserveScroll: true, onSuccess: () => exito(d.baja_logica ? 'Dado de baja' : 'Eliminado') })
}
</script>

<template>
  <div class="container-xxl flex-grow-1 container-p-y">
    <Flash />
    <div class="app-ecommerce">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-6 row-gap-4">
        <div class="col-lg-9">
          <h4 class="mb-1">{{ definicion.titulo_plural }}</h4>
          <p class="mb-0 text-muted">{{ definicion.descripcion }}</p>
        </div>
        <div class="d-flex gap-2">
          <Link :href="route('motor.normativa.index')" class="btn btn-outline-secondary"><i class="ri-arrow-left-line me-1"></i> Normativa</Link>
          <Link v-if="!definicion.cerrado" :href="route(definicion.ruta + '.create')" class="btn btn-primary"><i class="ri-add-line me-1"></i> Nuevo</Link>
          <span v-else class="badge bg-label-secondary align-self-center" title="Catálogo cerrado: ninguna empresa crea, edita ni elimina filas"><i class="ri-lock-line me-1"></i>Catálogo cerrado</span>
        </div>
      </div>

      <div class="card">
        <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
          <div class="d-flex align-items-center gap-3 flex-wrap">
            <input v-model="buscar" type="text" class="form-control form-control-sm" style="width: 280px" placeholder="Buscar…" />
            <template v-for="f in definicion.filtros" :key="f">
              <select v-if="definicion.campos.find((c) => c.nombre === f)?.tipo === 'estado_validacion'" v-model="filtros[f]" class="form-select form-select-sm" style="width: 190px" @change="aplicar()">
                <option value="">Todos los estados</option>
                <option value="SIN_DATO">Sin dato</option>
                <option value="SIN_CONFIRMAR">Sin confirmar</option>
                <option value="CONFIRMADO">Confirmado</option>
              </select>
              <select v-else-if="definicion.campos.find((c) => c.nombre === f)?.opciones || opciones?.[f]" v-model="filtros[f]" class="form-select form-select-sm" style="width: 220px" @change="aplicar()">
                <option value="">{{ definicion.campos.find((c) => c.nombre === f)?.etiqueta }}: todos</option>
                <option v-for="o in definicion.campos.find((c) => c.nombre === f)?.opciones || opciones?.[f]" :key="o.value" :value="o.value">{{ o.label }}</option>
              </select>
              <input v-else v-model="filtros[f]" type="text" class="form-control form-control-sm" style="width: 160px" :placeholder="definicion.campos.find((c) => c.nombre === f)?.etiqueta" @change="aplicar()" />
            </template>
            <span class="badge bg-label-primary">{{ items.total }} registros</span>
          </div>
        </div>
        <div class="table-responsive text-nowrap">
          <table class="table table-striped table-hover align-middle">
            <thead class="table-light">
              <tr>
                <th v-for="c in columnas" :key="c.nombre" :class="{ 'text-end': ['decimal', 'porcentaje', 'entero'].includes(c.tipo) }">{{ c.etiqueta }}</th>
                <th class="text-end">Acciones</th>
              </tr>
            </thead>
            <tbody class="table-border-bottom-0">
              <tr v-if="items.data.length === 0"><td :colspan="columnas.length + 1" class="text-center text-muted py-4">No hay registros.</td></tr>
              <tr v-for="item in items.data" :key="claveDe(item)" :class="{ 'text-muted': item.activo === false || item.activo === 0 }">
                <td v-for="c in columnas" :key="c.nombre" :class="{ 'text-end': ['decimal', 'porcentaje', 'entero'].includes(c.tipo) }">
                  <EstadoValidacionBadge v-if="c.tipo === 'estado_validacion'" :estado="item[c.nombre]" />
                  <span v-else-if="c.tipo === 'booleano'" class="badge" :class="item[c.nombre] ? 'bg-label-success' : 'bg-label-secondary'">{{ item[c.nombre] ? 'Sí' : 'No' }}</span>
                  <span v-else :class="{ 'font-monospace': c.nombre === definicion.clave_natural }">{{ mostrar(c, item) }}</span>
                </td>
                <td class="text-end">
                  <Link :href="route(definicion.ruta + '.edit', claveDe(item))" class="btn btn-link text-warning p-0 me-2" title="Editar"><i class="ri-edit-line" style="font-size: 1.25rem"></i></Link>
                  <button v-if="!definicion.cerrado" type="button" class="btn btn-link text-danger p-0" :title="definicion.baja_logica ? 'Dar de baja' : 'Eliminar'" @click="baja(item)"><i class="ri-delete-bin-line" style="font-size: 1.25rem"></i></button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-if="items.last_page > 1" class="card-footer d-flex justify-content-between align-items-center">
          <span class="text-muted small">Mostrando {{ items.from }}–{{ items.to }} de {{ items.total }}</span>
          <nav><ul class="pagination mb-0">
            <li v-for="link in items.links" :key="link.label" :class="['page-item', { active: link.active, disabled: !link.url }]">
              <Link v-if="link.url" :href="link.url" class="page-link" v-html="link.label" preserve-scroll preserve-state />
              <span v-else class="page-link" v-html="link.label" />
            </li>
          </ul></nav>
        </div>
      </div>
    </div>
  </div>
</template>
