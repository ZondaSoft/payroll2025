<script setup>
import { router, Link } from '@inertiajs/vue3'
import Swal from 'sweetalert2'

defineProps({
  importes: Object,
})

const formatPeriodo = (yyyymm) => {
  if (!yyyymm || yyyymm.length !== 6) return yyyymm
  return `${yyyymm.substring(0, 4)}/${yyyymm.substring(4, 6)}`
}

const formatImporte = (n) => {
  return new Intl.NumberFormat('es-AR', {
    style: 'currency',
    currency: 'ARS',
    minimumFractionDigits: 2,
  }).format(n)
}

const formatFecha = (f) => {
  if (!f) return '-'
  return new Date(f).toLocaleDateString('es-AR')
}

const eliminar = async (id, periodo) => {
  const result = await Swal.fire({
    icon: 'warning',
    title: '¿Eliminar este importe?',
    html: `Período <strong>${formatPeriodo(periodo)}</strong>. Esta acción no se puede deshacer.`,
    showCancelButton: true,
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#dc3545',
  })
  if (!result.isConfirmed) return

  router.delete(route('sicoss.importes-detraer.destroy', id), {
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
          <h4 class="mb-1">Importes a detraer (Ley 27.430)</h4>
          <p class="mb-0 text-muted">Histórico de importes vigentes por período. El más reciente con <code>periodo_desde &le; período</code> es el que se aplica.</p>
        </div>
        <Link :href="route('sicoss.importes-detraer.create')" class="btn btn-primary">
          <i class="ri-add-line me-1"></i> Nuevo importe
        </Link>
      </div>

      <div class="card">
        <div class="table-responsive">
          <table class="table table-striped">
            <thead>
              <tr>
                <th>Vigente desde</th>
                <th class="text-end">Importe</th>
                <th>Normativa</th>
                <th>Observaciones</th>
                <th>Cargado por</th>
                <th>Fecha</th>
                <th class="text-end">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="importes.data.length === 0">
                <td colspan="7" class="text-center text-muted py-4">No hay importes cargados.</td>
              </tr>
              <tr v-for="imp in importes.data" :key="imp.id">
                <td><strong>{{ formatPeriodo(imp.periodo_desde) }}</strong></td>
                <td class="text-end">{{ formatImporte(imp.importe) }}</td>
                <td>{{ imp.normativa || '-' }}</td>
                <td>
                  <span v-if="imp.observaciones" :title="imp.observaciones">
                    {{ imp.observaciones.length > 50 ? imp.observaciones.substring(0, 50) + '…' : imp.observaciones }}
                  </span>
                  <span v-else>-</span>
                </td>
                <td>{{ imp.usuario?.name || '-' }}</td>
                <td>{{ formatFecha(imp.updated_at) }}</td>
                <td class="text-end">
                  <Link :href="route('sicoss.importes-detraer.edit', imp.id)"
                        class="btn btn-link text-warning p-0 me-2"
                        title="Editar">
                    <i class="ri-edit-line" style="font-size: 1.25rem;"></i>
                  </Link>
                  <button type="button"
                          class="btn btn-link text-danger p-0"
                          @click="eliminar(imp.id, imp.periodo_desde)"
                          title="Eliminar">
                    <i class="ri-delete-bin-line" style="font-size: 1.25rem;"></i>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="importes.last_page > 1" class="card-footer d-flex justify-content-between align-items-center">
          <span class="text-muted small">
            Mostrando {{ importes.from }}-{{ importes.to }} de {{ importes.total }}
          </span>
          <nav>
            <ul class="pagination mb-0">
              <li v-for="link in importes.links" :key="link.label"
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
