<script setup>
import { Link, router } from '@inertiajs/vue3'
import Flash from '@/Components/Motor/Flash.vue'
import { confirmar, exito } from '@/Components/Motor/ConfirmarBaja.js'
import { fecha } from '@/Components/Motor/formato.js'

defineProps({ items: Array, precedencias: Object, ambiguos: Array })
const baja = async (p) => {
  if (!(await confirmar({ titulo: `¿Dar de baja ${p.codigo}?`, html: `Baja lógica. Los <b>${p.reglas}</b> concepto(s) que declara dejan de tener regla en este nivel: si ningún otro perfil se pronuncia, el motor los bloquea con <code>PERFIL_SIN_REGLA</code>.`, confirmar: 'Sí, dar de baja', peligro: true }))) return
  router.delete(route('motor.perfiles.destroy', p.id), { preserveScroll: true, onSuccess: () => exito('Dado de baja') })
}
</script>

<template>
  <div class="container-xxl flex-grow-1 container-p-y">
    <Flash />
    <div class="app-ecommerce">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-6 row-gap-4">
        <div class="col-lg-9">
          <h4 class="mb-1">Perfiles de liquidación</h4>
          <p class="mb-0 text-muted">Qué conceptos corresponden a cada ámbito <b>y con qué variante</b> se liquidan. Gana la regla del nivel más específico que se pronuncie: <b>Trabajador (1) → Empresa (2) → Categoría (3) → Convenio (4) → General (5)</b>. Un nivel que no declara el concepto no vota, y la búsqueda sigue hacia los más generales.</p>
        </div>
        <Link :href="route('motor.perfiles.create')" class="btn btn-primary"><i class="ri-add-line me-1"></i> Nuevo perfil</Link>
      </div>

      <div v-if="ambiguos.length" class="alert alert-danger" role="alert">
        <h6 class="alert-heading mb-2"><i class="ri-error-warning-line me-1"></i> Perfiles ambiguos: {{ ambiguos.length }} concepto(s) bloqueados</h6>
        <p class="mb-2 small">Dos perfiles del mismo nivel se contradicen. El motor no elige por su cuenta: bloquea ese concepto con <code>PERFIL_AMBIGUO</code> y liquida el resto.</p>
        <ul class="mb-0 small">
          <li v-for="(a, i) in ambiguos" :key="i"><span class="font-monospace fw-bold">{{ a.concepto }}</span> — nivel {{ a.nivel }}: {{ a.perfiles.join(', ') }}</li>
        </ul>
      </div>

      <div class="card">
        <div class="table-responsive"><table class="table table-striped table-hover align-middle">
          <thead class="table-light"><tr><th>Código</th><th>Nombre</th><th>Nivel</th><th>Ámbito</th><th>Vigencia</th><th class="text-end">Incluye</th><th class="text-end">Excluye</th><th></th><th class="text-end">Acciones</th></tr></thead>
          <tbody>
            <tr v-if="!items.length"><td colspan="9" class="text-center text-muted py-4">Sin perfiles: todos los conceptos quedan bloqueados con <code>PERFIL_SIN_REGLA</code>. Empezá por uno de nivel Convenio.</td></tr>
            <tr v-for="p in items" :key="p.id" :class="{ 'text-muted': !p.activo }">
              <td class="font-monospace fw-bold">{{ p.codigo }}</td>
              <td>{{ p.nombre }}</td>
              <td><span class="badge bg-label-primary">{{ p.nivel }} ({{ p.precedencia }})</span></td>
              <td class="small">{{ p.ambito }}</td>
              <td class="small">{{ fecha(p.desde) }} → {{ p.hasta ? fecha(p.hasta) : '∞' }}</td>
              <td class="text-end"><span class="badge bg-label-success">{{ p.incluye }}</span></td>
              <td class="text-end"><span class="badge bg-label-secondary">{{ p.excluye }}</span></td>
              <td><span v-if="!p.activo" class="badge bg-label-dark">inactivo</span></td>
              <td class="text-end">
                <Link :href="route('motor.perfiles.edit', p.id)" class="btn btn-link text-warning p-0 me-2"><i class="ri-edit-line" style="font-size: 1.25rem"></i></Link>
                <button v-if="p.activo" type="button" class="btn btn-link text-danger p-0" @click="baja(p)"><i class="ri-delete-bin-line" style="font-size: 1.25rem"></i></button>
              </td>
            </tr>
          </tbody>
        </table></div>
      </div>
    </div>
  </div>
</template>
