<script setup>
import { ref, watch } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import Flash from '@/Components/Motor/Flash.vue'
import { confirmar, exito } from '@/Components/Motor/ConfirmarBaja.js'

const props = defineProps({ items: Object, filtros: Object })
const buscar = ref(props.filtros?.buscar ?? '')
let t = null
watch(buscar, (v) => { clearTimeout(t); t = setTimeout(() => router.get(route('motor.parametros.index'), { buscar: v }, { preserveState: true, replace: true }), 300) })
const baja = async (p) => {
  if (!(await confirmar({ titulo: `¿Dar de baja ${p.codigo}?`, html: p.conceptos ? `<b>${p.conceptos}</b> concepto(s) lo referencian: el motor bloqueará si intenta usarlo.` : 'Baja lógica.', confirmar: 'Sí, dar de baja', peligro: true }))) return
  router.delete(route('motor.parametros.destroy', p.id), { preserveScroll: true, onSuccess: () => exito('Dado de baja') })
}
</script>

<template>
  <div class="container-xxl flex-grow-1 container-p-y">
    <Flash />
    <div class="app-ecommerce">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-6 row-gap-4">
        <div class="col-lg-9"><h4 class="mb-1">Parámetros del motor</h4><p class="mb-0 text-muted">Datos de configuración que una estrategia usa como entrada, con valores por alcance (GENERAL → CONVENIO → CATEGORÍA → EMPRESA → TRABAJADOR) y vigencia. Sin valor vigente el motor bloquea.</p></div>
        <Link :href="route('motor.parametros.create')" class="btn btn-primary"><i class="ri-add-line me-1"></i> Nuevo parámetro</Link>
      </div>
      <div class="card">
        <div class="card-header"><input v-model="buscar" class="form-control form-control-sm" style="width: 280px" placeholder="Buscar…" /></div>
        <div class="table-responsive"><table class="table table-striped table-hover align-middle">
          <thead class="table-light"><tr><th>Código</th><th>Descripción</th><th>Tipo</th><th>Unidad</th><th class="text-end">Valores</th><th class="text-end">Conceptos</th><th></th><th class="text-end">Acciones</th></tr></thead>
          <tbody>
            <tr v-for="p in items.data" :key="p.id" :class="{ 'text-muted': !p.activo }">
              <td class="font-monospace fw-bold">{{ p.codigo }} <span v-if="p.es_tecnico" class="badge bg-label-info" title="Parámetro técnico del motor">técnico</span></td><td>{{ p.descripcion }}</td><td>{{ p.tipo }}</td><td>{{ p.unidad }}</td>
              <td class="text-end"><span class="badge" :class="p.valores ? 'bg-label-success' : 'bg-label-danger'">{{ p.valores }}</span></td><td class="text-end">{{ p.conceptos }}</td><td><span v-if="!p.activo" class="badge bg-label-dark">inactivo</span></td>
              <td class="text-end"><Link :href="route('motor.parametros.edit', p.id)" class="btn btn-link text-warning p-0 me-2"><i class="ri-edit-line" style="font-size: 1.25rem"></i></Link><button v-if="p.activo && !p.es_tecnico" type="button" class="btn btn-link text-danger p-0" @click="baja(p)"><i class="ri-delete-bin-line" style="font-size: 1.25rem"></i></button></td>
            </tr>
          </tbody>
        </table></div>
        <div v-if="items.last_page > 1" class="card-footer d-flex justify-content-between align-items-center"><span class="text-muted small">{{ items.from }}–{{ items.to }} de {{ items.total }}</span><nav><ul class="pagination mb-0"><li v-for="l in items.links" :key="l.label" :class="['page-item', { active: l.active, disabled: !l.url }]"><Link v-if="l.url" :href="l.url" class="page-link" v-html="l.label" preserve-scroll preserve-state /><span v-else class="page-link" v-html="l.label" /></li></ul></nav></div>
      </div>
    </div>
  </div>
</template>
