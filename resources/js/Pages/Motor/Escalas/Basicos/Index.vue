<script setup>
import { ref } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import Flash from '@/Components/Motor/Flash.vue'
import EstadoValidacionBadge from '@/Components/Motor/EstadoValidacionBadge.vue'
import { confirmar, exito } from '@/Components/Motor/ConfirmarBaja.js'
import { importe, fecha } from '@/Components/Motor/formato.js'

const props = defineProps({ items: Object, filtros: Object, categorias: Array })
const f = ref({ categoria: props.filtros?.categoria ?? '', vigentes: props.filtros?.vigentes ?? '' })
const aplicar = () => router.get(route('motor.escalas.basicos.index'), f.value, { preserveState: true, replace: true })
const eliminar = async (b) => { if (!(await confirmar({ titulo: '¿Eliminar este básico?', html: 'Si una liquidación lo usó, cerrá la vigencia en lugar de borrarlo (RT-006).', confirmar: 'Sí, eliminar', peligro: true }))) return; router.delete(route('motor.escalas.basicos.destroy', b.id), { preserveScroll: true, onSuccess: () => exito('Eliminado') }) }
</script>

<template>
  <div class="container-xxl flex-grow-1 container-p-y">
    <Flash />
    <div class="app-ecommerce">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-6 row-gap-4">
        <div class="col-lg-8"><h4 class="mb-1">Escalas y básicos</h4><p class="mb-0 text-muted">Sueldo básico por categoría y vigencia (escala salarial). Sin básico vigente el motor bloquea: no lo toma de sue001s.bruto.</p></div>
        <div class="d-flex gap-2 flex-wrap">
          <Link :href="route('motor.escalas.antiguedad.index')" class="btn btn-outline-secondary">Antigüedad</Link><Link :href="route('motor.escalas.vacaciones.index')" class="btn btn-outline-secondary">Vacaciones</Link><Link :href="route('motor.escalas.asignaciones.index')" class="btn btn-outline-secondary">Asignación a convenios</Link>
          <Link :href="route('motor.escalas.basicos.create')" class="btn btn-primary"><i class="ri-add-line me-1"></i> Nuevo básico</Link>
        </div>
      </div>
      <div class="card">
        <div class="card-header d-flex gap-3 flex-wrap"><select v-model="f.categoria" class="form-select form-select-sm" style="width: 320px" @change="aplicar"><option value="">Todas las categorías</option><option v-for="c in categorias" :key="c.value" :value="c.value">{{ c.label }}</option></select><div class="form-check"><input id="vig" v-model="f.vigentes" class="form-check-input" type="checkbox" true-value="1" false-value="" @change="aplicar" /><label for="vig" class="form-check-label">Solo vigentes</label></div></div>
        <div class="table-responsive"><table class="table table-striped align-middle">
          <thead class="table-light"><tr><th>Categoría</th><th>Convenio</th><th>Vigencia</th><th class="text-end">Básico</th><th>Estado</th><th>Fuente</th><th class="text-end">Acciones</th></tr></thead>
          <tbody>
            <tr v-if="!items.data.length"><td colspan="7" class="text-center text-muted py-4">Sin básicos cargados.</td></tr>
            <tr v-for="b in items.data" :key="b.id"><td>{{ b.categoria }}</td><td>{{ b.convenio ?? '—' }}</td><td class="small">{{ fecha(b.desde) }} → {{ b.hasta ? fecha(b.hasta) : '∞' }}</td><td class="text-end fw-bold">{{ importe(b.importe) }}</td><td><EstadoValidacionBadge :estado="b.estado" /></td><td class="small text-muted">{{ b.fuente }}</td>
              <td class="text-end"><Link :href="route('motor.escalas.basicos.edit', b.id)" class="btn btn-link text-warning p-0 me-2"><i class="ri-edit-line" style="font-size: 1.25rem"></i></Link><button type="button" class="btn btn-link text-danger p-0" @click="eliminar(b)"><i class="ri-delete-bin-line" style="font-size: 1.25rem"></i></button></td></tr>
          </tbody>
        </table></div>
        <div v-if="items.last_page > 1" class="card-footer d-flex justify-content-between align-items-center"><span class="text-muted small">{{ items.from }}–{{ items.to }} de {{ items.total }}</span><nav><ul class="pagination mb-0"><li v-for="l in items.links" :key="l.label" :class="['page-item', { active: l.active, disabled: !l.url }]"><Link v-if="l.url" :href="l.url" class="page-link" v-html="l.label" preserve-scroll preserve-state /><span v-else class="page-link" v-html="l.label" /></li></ul></nav></div>
      </div>
    </div>
  </div>
</template>
