<script setup>
import { router, Link } from '@inertiajs/vue3'
import Swal from 'sweetalert2'

defineProps({
  topes: Object,
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

const formatIpc = (n) => {
  if (n === null || n === undefined || n === '') return '-'
  const v = Number(n)
  return `${v > 0 ? '+' : ''}${v.toLocaleString('es-AR', { minimumFractionDigits: 2 })}%`
}

const eliminar = async (id, periodo) => {
  const result = await Swal.fire({
    icon: 'warning',
    title: '¿Eliminar este tope?',
    html: `Período <strong>${formatPeriodo(periodo)}</strong>. Esta acción no se puede deshacer.`,
    showCancelButton: true,
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#dc3545',
  })
  if (!result.isConfirmed) return

  router.delete(route('sicoss.topes.destroy', id), {
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
        <div class="col-lg-9">
          <h4 class="mb-1">Topes máximos de base imponible (aportes)</h4>
          <p class="mb-0 text-muted">Tope de la base imponible de aportes (BI 1/4/5: jubilación, INSSJyP y obra social). El más reciente con <code>periodo_desde &le; período</code> es el que se aplica. Las contribuciones no tienen tope.</p>
        </div>
        <Link :href="route('sicoss.topes.create')" class="btn btn-primary">
          <i class="ri-add-line me-1"></i> Agregar tope
        </Link>
      </div>

      <div class="card">
        <div class="table-responsive">
          <table class="table table-striped">
            <thead>
              <tr>
                <th>Período</th>
                <th class="text-end">Mínima</th>
                <th class="text-end">Máxima (tope)</th>
                <th>Resolución</th>
                <th class="text-end">% IPC</th>
                <th class="text-end">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="topes.data.length === 0">
                <td colspan="6" class="text-center text-muted py-4">No hay topes cargados.</td>
              </tr>
              <tr v-for="t in topes.data" :key="t.id">
                <td><strong>{{ formatPeriodo(t.periodo_desde) }}</strong></td>
                <td class="text-end">{{ t.base_minima ? formatImporte(t.base_minima) : '-' }}</td>
                <td class="text-end">{{ formatImporte(t.tope_aportes) }}</td>
                <td>{{ t.normativa || '-' }}</td>
                <td class="text-end">{{ formatIpc(t.ipc_porcentaje) }}</td>
                <td class="text-end">
                  <Link :href="route('sicoss.topes.edit', t.id)"
                        class="btn btn-link text-warning p-0 me-2"
                        title="Editar">
                    <i class="ri-edit-line" style="font-size: 1.25rem;"></i>
                  </Link>
                  <button type="button"
                          class="btn btn-link text-danger p-0"
                          @click="eliminar(t.id, t.periodo_desde)"
                          title="Eliminar">
                    <i class="ri-delete-bin-line" style="font-size: 1.25rem;"></i>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="topes.last_page > 1" class="card-footer d-flex justify-content-between align-items-center">
          <span class="text-muted small">
            Mostrando {{ topes.from }}-{{ topes.to }} de {{ topes.total }}
          </span>
          <nav>
            <ul class="pagination mb-0">
              <li v-for="link in topes.links" :key="link.label"
                  :class="['page-item', { active: link.active, disabled: !link.url }]">
                <Link v-if="link.url" :href="link.url" class="page-link" v-html="link.label" preserve-scroll />
                <span v-else class="page-link" v-html="link.label" />
              </li>
            </ul>
          </nav>
        </div>
      </div>

      <div class="mt-4">
        <a href="https://www.argentina.gob.ar/trabajo/seguridadsocial/imss"
           target="_blank"
           rel="noopener noreferrer"
           class="btn btn-topes-ss">
          <i class="ri-earth-line me-1"></i> Topes de la Seguridad Social
        </a>
      </div>
    </div>
  </div>
</template>

<style scoped>
.btn-topes-ss {
  background-color: #7e3ff2;
  border-color: #7e3ff2;
  color: #fff;
}
.btn-topes-ss:hover,
.btn-topes-ss:focus {
  background-color: #6a2fd6;
  border-color: #6a2fd6;
  color: #fff;
}
</style>
