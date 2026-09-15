<script setup>
import { Link } from '@inertiajs/vue3'
import axios from 'axios'
import Swal from 'sweetalert2'
import Flash from '@/Components/Motor/Flash.vue'
import { confirmar } from '@/Components/Motor/ConfirmarBaja.js'

defineProps({ catalogos: Array })

const importarSicoss = async () => {
  const ok = await confirmar({ titulo: 'Importar catálogos SICOSS', html: 'Carga en los catálogos ARCA del motor las actividades, condiciones, modalidades, situaciones y localidades que ya están validadas en el sistema. <b>No pisa</b> filas existentes.', confirmar: 'Importar' })
  if (!ok) return
  Swal.fire({ title: 'Importando…', didOpen: () => Swal.showLoading(), allowOutsideClick: false })
  try {
    const { data } = await axios.post(route('motor.importar.catalogos-sicoss'))
    const filas = Object.entries(data.insertadas).map(([k, v]) => `<tr><td>${k}</td><td class="text-end">${v}</td></tr>`).join('')
    await Swal.fire({ icon: 'success', title: 'Catálogos importados', html: `<table class="table table-sm text-start"><thead><tr><th>Catálogo</th><th class="text-end">Insertadas</th></tr></thead><tbody>${filas}</tbody></table>`, confirmButtonText: 'Cerrar', buttonsStyling: false, customClass: { confirmButton: 'btn rounded-pill btn-primary' } })
    window.location.reload()
  } catch (e) {
    Swal.fire({ icon: 'error', title: 'No se pudo importar', text: e.response?.data?.message || e.message })
  }
}
</script>

<template>
  <div class="container-xxl flex-grow-1 container-p-y">
    <Flash />
    <div class="app-ecommerce">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-6 row-gap-4">
        <div class="col-lg-9">
          <h4 class="mb-1">Normativa ARCA</h4>
          <p class="mb-0 text-muted">Catálogos y parámetros contributivos del motor. En PRODUCCIÓN solo los registros <b>CONFIRMADO</b> habilitan el cálculo.</p>
        </div>
        <button type="button" class="btn btn-outline-primary" @click="importarSicoss"><i class="ri-download-2-line me-1"></i> Importar catálogos SICOSS</button>
      </div>

      <div class="row g-4">
        <div v-for="c in catalogos" :key="c.ruta" class="col-md-6 col-xl-4">
          <div class="card h-100">
            <div class="card-body d-flex flex-column">
              <div class="d-flex align-items-center mb-3">
                <span class="avatar avatar-sm me-3"><span class="avatar-initial rounded bg-label-primary"><i :class="c.icono"></i></span></span>
                <div class="flex-grow-1">
                  <h6 class="mb-0">{{ c.titulo }}</h6>
                  <small class="text-muted">{{ c.total }} registros <span v-if="c.cerrado" class="ms-1"><i class="ri-lock-line"></i> cerrado</span></small>
                </div>
              </div>
              <div v-if="c.estados" class="d-flex gap-2 flex-wrap mb-3">
                <span v-if="c.estados.CONFIRMADO" class="badge bg-label-success">{{ c.estados.CONFIRMADO }} confirmados</span>
                <span v-if="c.estados.SIN_CONFIRMAR" class="badge bg-label-warning">{{ c.estados.SIN_CONFIRMAR }} sin confirmar</span>
                <span v-if="c.estados.SIN_DATO" class="badge bg-label-danger">{{ c.estados.SIN_DATO }} sin dato</span>
              </div>
              <Link :href="route(c.ruta + '.index')" class="btn btn-sm btn-outline-primary mt-auto align-self-start">Abrir <i class="ri-arrow-right-line ms-1"></i></Link>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
