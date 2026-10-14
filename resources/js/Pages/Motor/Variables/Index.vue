<script setup>
import { Link, router } from '@inertiajs/vue3'
import Flash from '@/Components/Motor/Flash.vue'
import { confirmar, exito } from '@/Components/Motor/ConfirmarBaja.js'

const props = defineProps({ items: Array, del_contexto: Array })
const delContexto = (c) => props.del_contexto.includes(c)
const baja = async (v) => {
  if (!(await confirmar({ titulo: `¿Dar de baja ${v.codigo}?`, html: 'Baja lógica: las fórmulas publicadas que la usen van a bloquear con <code>VARIABLE_SIN_VALOR</code>, que es lo que corresponde.', confirmar: 'Sí, dar de baja', peligro: true }))) return
  router.delete(route('motor.variables.destroy', v.codigo), { preserveScroll: true, onSuccess: () => exito('Dada de baja') })
}
</script>

<template>
  <div class="container-xxl flex-grow-1 container-p-y">
    <Flash />
    <div class="app-ecommerce">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-6 row-gap-4">
        <div class="col-lg-9">
          <h4 class="mb-1">Variables del DSL</h4>
          <p class="mb-0 text-muted">Lo que las fórmulas leen con <code>VAR()</code>. Los <b>divisores del convenio</b> (DIVJOR, HMJORN, HSJORN, HDJORN) se cargan acá y son los que hacen falta para poner un convenio nuevo en marcha: sin ellos no hay feriado trabajado ni hora extra que se pueda calcular. Las demás las resuelve el contexto del trabajador.</p>
        </div>
        <Link :href="route('motor.variables.create')" class="btn btn-primary"><i class="ri-add-line me-1"></i> Nueva variable</Link>
      </div>
      <div class="card">
        <div class="table-responsive"><table class="table table-striped table-hover align-middle">
          <thead class="table-light"><tr><th>Código</th><th>Descripción</th><th>Unidad</th><th>Origen</th><th class="text-end">Valores</th><th>Quién la resuelve</th><th class="text-end">Acciones</th></tr></thead>
          <tbody>
            <tr v-for="v in items" :key="v.codigo" :class="{ 'text-muted': !v.activo }">
              <td class="font-monospace fw-bold">{{ v.codigo }}</td>
              <td>{{ v.descripcion }}</td>
              <td><span v-if="v.unidad" class="badge bg-label-secondary">{{ v.unidad }}</span><span v-else class="text-danger small" title="Sin unidad declarada el analizador no puede verificar el álgebra dimensional">sin declarar</span></td>
              <td class="small">{{ v.origen }}</td>
              <td class="text-end">
                <span v-if="v.se_carga" class="badge" :class="v.vigentes ? 'bg-label-success' : 'bg-label-danger'">{{ v.vigentes }} vigente(s)</span>
                <span v-else class="text-muted small">—</span>
              </td>
              <td class="small">
                <span v-if="delContexto(v.codigo)" class="text-muted">el contexto del trabajador</span>
                <span v-else-if="v.se_carga" class="text-primary">valor cargado por ámbito</span>
                <span v-else class="text-muted">—</span>
              </td>
              <td class="text-end">
                <Link :href="route('motor.variables.edit', v.codigo)" class="btn btn-link text-warning p-0 me-2"><i class="ri-edit-line" style="font-size: 1.25rem"></i></Link>
                <button v-if="v.activo" type="button" class="btn btn-link text-danger p-0" @click="baja(v)"><i class="ri-delete-bin-line" style="font-size: 1.25rem"></i></button>
              </td>
            </tr>
          </tbody>
        </table></div>
      </div>
    </div>
  </div>
</template>
