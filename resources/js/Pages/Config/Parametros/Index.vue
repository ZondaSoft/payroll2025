<script setup>
import { router, Link } from '@inertiajs/vue3'
import Swal from 'sweetalert2'

defineProps({
  parametros: Object,
})

const TIPOREM_LABELS = {
  H: 'Haberes',
  D: 'Descuentos',
  A: 'Asignaciones',
  NR: 'No remunerativos',
  GA: 'Ganancias',
  DG: 'Devolución de Ganancias',
  RE: 'Redondeo',
  AP: 'Aportes',
  AU: 'Auxiliares',
}

const tipoLabel = (codigo) => TIPOREM_LABELS[codigo] || codigo

const eliminar = async (id, codigo) => {
  const result = await Swal.fire({
    icon: 'warning',
    title: '¿Eliminar este parámetro?',
    html: `Tipo <strong>${tipoLabel(codigo)}</strong>. Esta acción no se puede deshacer.`,
    showCancelButton: true,
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#dc3545',
  })
  if (!result.isConfirmed) return

  router.delete(route('config.parametros.destroy', id), {
    preserveScroll: true,
    onSuccess: () => {
      Swal.fire({
        icon: 'success',
        title: 'Eliminado',
        timer: 1500,
        showConfirmButton: false,
      })
    },
  })
}
</script>

<template>
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="app-ecommerce">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-6 row-gap-4">
        <div>
          <h4 class="mb-1">Rangos de conceptos</h4>
          <p class="mb-0 text-muted">Define el tipo de cada rango de códigos de concepto (<code>desde</code> – <code>hasta</code>).</p>
        </div>
        <Link :href="route('config.parametros.create')" class="btn btn-primary">
          <i class="ri-add-line me-1"></i> Nuevo rango
        </Link>
      </div>

      <div class="card">
        <div class="table-responsive">
          <table class="table table-striped">
            <thead>
              <tr>
                <th>Tipo</th>
                <th class="text-end">Desde</th>
                <th class="text-end">Hasta</th>
                <th class="text-end">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="parametros.data.length === 0">
                <td colspan="4" class="text-center text-muted py-4">No hay parámetros cargados.</td>
              </tr>
              <tr v-for="p in parametros.data" :key="p.id">
                <td><strong>{{ tipoLabel(p.tiporem) }}</strong></td>
                <td class="text-end">{{ p.desde }}</td>
                <td class="text-end">{{ p.hasta }}</td>
                <td class="text-end">
                  <Link :href="route('config.parametros.edit', p.id)"
                        class="btn btn-link text-warning p-0 me-2"
                        title="Editar">
                    <i class="ri-edit-line" style="font-size: 1.25rem;"></i>
                  </Link>
                  <button type="button"
                          class="btn btn-link text-danger p-0"
                          @click="eliminar(p.id, p.tiporem)"
                          title="Eliminar">
                    <i class="ri-delete-bin-line" style="font-size: 1.25rem;"></i>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="parametros.last_page > 1" class="card-footer d-flex justify-content-between align-items-center">
          <span class="text-muted small">
            Mostrando {{ parametros.from }}-{{ parametros.to }} de {{ parametros.total }}
          </span>
          <nav>
            <ul class="pagination mb-0">
              <li v-for="link in parametros.links" :key="link.label"
                  :class="['page-item', { active: link.active, disabled: !link.url }]">
                <Link v-if="link.url" :href="link.url" class="page-link" v-html="link.label" preserve-scroll />
                <span v-else class="page-link" v-html="link.label" />
              </li>
            </ul>
          </nav>
        </div>
      </div>
    </div>
  </div>
</template>
