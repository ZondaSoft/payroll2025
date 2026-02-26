<script setup>
import FormHeader from '@/Components/FormHeader.vue';
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    empleado: {
        type: Object,
        default: null,
    },
    conceptos: {
        type: Array,
        default: () => [],
    },
    periodo: {
        type: String,
        default: '',
    },
    empresa: {
        type: Object,
        default: () => ({}),
    },
    legajos: {
        type: Array,
        default: () => [],
    },
    periodos: {
        type: Array,
        default: () => [],
    },
    legajoId: {
        type: Number,
        default: null,
    },
});

// Filtros locales
const selectedLegajoId = ref(props.legajoId ?? '');
const selectedPeriodo  = ref(props.periodo ?? '');

const buscar = () => {
    router.get(route('liquidacion.individual.index'), {
        legajo_id: selectedLegajoId.value || undefined,
        periodo:   selectedPeriodo.value  || undefined,
    }, { preserveScroll: true });
};

// Nombre completo del empleado
const nombreCompleto = computed(() => {
    const detalle = props.empleado?.detalle ?? '';
    const nombres = props.empleado?.nombres ?? '';
    return [detalle, nombres].filter(Boolean).join(', ');
});

// Formateo de moneda
const formatCurrency = (value) => {
    if (!value && value !== 0) return '';
    return new Intl.NumberFormat('es-AR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(value);
};

// Totales calculados
const totales = computed(() => {
    return props.conceptos.reduce(
        (acc, item) => {
            acc.haberes         += Number(item.haberes         ?? 0);
            acc.retenciones     += Number(item.retenciones     ?? 0);
            acc.asignaciones    += Number(item.asignaciones    ?? 0);
            acc.no_remunerativo += Number(item.no_remunerativo ?? 0);
            return acc;
        },
        { haberes: 0, retenciones: 0, asignaciones: 0, no_remunerativo: 0 }
    );
});

const netoTotal = computed(() =>
    totales.value.haberes
    - totales.value.retenciones
    + totales.value.asignaciones
    + totales.value.no_remunerativo
);
</script>

<template>
    <div class="container-xxl flex-grow-1 container-p-y">

        <!-- HEAD Y BOTONES -->
        <div class="row mb-4">
            <div class="col">
                <h4 class="fw-bold mb-0">
                    <span class="text-muted fw-light">Liquidación /</span> Recibo Individual
                </h4>
                <small class="text-muted">
                    Empresa: <strong>{{ empresa?.detalle ?? '—' }}</strong>
                    &nbsp;|&nbsp; Período: <strong>{{ periodo || '—' }}</strong>
                </small>
            </div>
        </div>

        <!-- FILTROS -->
        <div class="row mb-4">
            <div class="col">
                <div class="card">
                    <div class="card-body py-3">
                        <div class="row g-3 align-items-end">
                            <!-- Selector de empleado -->
                            <div class="col-12 col-md-5">
                                <label class="form-label mb-1">Empleado</label>
                                <select class="form-select" v-model="selectedLegajoId">
                                    <option value="">— Seleccionar empleado —</option>
                                    <option v-for="leg in legajos" :key="leg.id" :value="leg.id">
                                        {{ leg.codigo }} — {{ leg.detalle }}, {{ leg.nombres }}
                                    </option>
                                </select>
                            </div>
                            <!-- Selector de período -->
                            <div class="col-6 col-md-3">
                                <label class="form-label mb-1">Período</label>
                                <select class="form-select" v-model="selectedPeriodo">
                                    <option value="">— Seleccionar período —</option>
                                    <option v-for="per in periodos" :key="per.id" :value="per.periodo">
                                        {{ per.periodo }}
                                    </option>
                                </select>
                            </div>
                            <!-- Botón buscar -->
                            <div class="col-6 col-md-2">
                                <button type="button" class="btn btn-primary w-100" @click="buscar">
                                    <i class="ri-search-line me-1"></i> Buscar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- DATOS DEL EMPLEADO -->
        <div class="row mb-4" v-if="empleado">
            <div class="col">
                <div class="card">
                    <div class="card-header py-3">
                        <div class="d-flex align-items-center gap-2">
                            <i class="ri-user-line ri-20px text-primary"></i>
                            <h6 class="mb-0">Datos del Empleado</h6>
                        </div>
                    </div>
                    <div class="card-body py-3">
                        <div class="row g-4">
                            <!-- Legajo -->
                            <div class="col-6 col-md-2">
                                <small class="text-muted d-block">Legajo</small>
                                <span class="fw-semibold">{{ empleado?.codigo ?? '—' }}</span>
                            </div>
                            <!-- Apellido y Nombre -->
                            <div class="col-12 col-md-5">
                                <small class="text-muted d-block">Apellido y Nombre</small>
                                <span class="fw-semibold">{{ nombreCompleto || '—' }}</span>
                            </div>
                            <!-- CUIL -->
                            <div class="col-6 col-md-3">
                                <small class="text-muted d-block">CUIL</small>
                                <span class="fw-semibold">{{ empleado?.cuil ?? '—' }}</span>
                            </div>
                            <!-- DNI -->
                            <div class="col-6 col-md-2">
                                <small class="text-muted d-block">DNI</small>
                                <span class="fw-semibold">{{ empleado?.num_doc ?? '—' }}</span>
                            </div>
                            <!-- Función / Cargo -->
                            <div class="col-6 col-md-2">
                                <small class="text-muted d-block">Función</small>
                                <span class="fw-semibold">{{ empleado?.funcion ?? '—' }}</span>
                            </div>
                            <!-- Sector -->
                            <div class="col-6 col-md-3">
                                <small class="text-muted d-block">Sector</small>
                                <span class="fw-semibold">{{ empleado?.sector?.detalle ?? '—' }}</span>
                            </div>
                            <!-- Jerarquía -->
                            <div class="col-6 col-md-3">
                                <small class="text-muted d-block">Jerarquía</small>
                                <span class="fw-semibold">{{ empleado?.jerarquia?.detalle ?? '—' }}</span>
                            </div>
                            <!-- Obra Social -->
                            <div class="col-6 col-md-2">
                                <small class="text-muted d-block">Obra Social</small>
                                <span class="fw-semibold">{{ empleado?.cod_obraso ?? '—' }}</span>
                            </div>
                            <!-- Ingreso -->
                            <div class="col-6 col-md-2">
                                <small class="text-muted d-block">Ingreso</small>
                                <span class="fw-semibold">{{ empleado?.alta ?? '—' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TABLA DE CONCEPTOS -->
        <div class="row">
            <div class="col">
                <div class="card">
                    <div class="card-header py-3">
                        <div class="d-flex align-items-center gap-2">
                            <i class="ri-file-list-3-line ri-20px text-primary"></i>
                            <h6 class="mb-0">Conceptos de Liquidación</h6>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center" style="width: 80px;">Código</th>
                                        <th>Descripción</th>
                                        <th class="text-end" style="width: 100px;">Cantidad</th>
                                        <th class="text-end" style="width: 120px;">Valores</th>
                                        <th class="text-end" style="width: 130px;">Haberes</th>
                                        <th class="text-end" style="width: 130px;">Retenciones</th>
                                        <th class="text-end" style="width: 130px;">Asignaciones</th>
                                        <th class="text-end" style="width: 140px;">No Remunerativo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-if="conceptos && conceptos.length > 0">
                                        <tr v-for="(item, index) in conceptos" :key="index">
                                            <td class="text-center fw-semibold">{{ item.codigo }}</td>
                                            <td>{{ item.detalle ?? item.descripcion }}</td>
                                            <td class="text-end">{{ item.cantidad != null ? formatCurrency(item.cantidad) : '' }}</td>
                                            <td class="text-end">{{ item.valores != null ? formatCurrency(item.valores) : '' }}</td>
                                            <td class="text-end text-success">
                                                <span v-if="item.haberes">{{ formatCurrency(item.haberes) }}</span>
                                            </td>
                                            <td class="text-end text-danger">
                                                <span v-if="item.retenciones">{{ formatCurrency(item.retenciones) }}</span>
                                            </td>
                                            <td class="text-end text-info">
                                                <span v-if="item.asignaciones">{{ formatCurrency(item.asignaciones) }}</span>
                                            </td>
                                            <td class="text-end text-warning">
                                                <span v-if="item.no_remunerativo">{{ formatCurrency(item.no_remunerativo) }}</span>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr v-else>
                                        <td colspan="8" class="text-center text-muted py-4">
                                            <i class="ri-inbox-line ri-24px me-2"></i>
                                            No hay conceptos registrados para este período.
                                        </td>
                                    </tr>
                                </tbody>

                                <!-- FILA DE TOTALES -->
                                <tfoot class="table-secondary">
                                    <tr class="fw-bold">
                                        <td colspan="4" class="text-end pe-3">Totales</td>
                                        <td class="text-end text-success">
                                            {{ formatCurrency(totales.haberes) }}
                                        </td>
                                        <td class="text-end text-danger">
                                            {{ formatCurrency(totales.retenciones) }}
                                        </td>
                                        <td class="text-end text-info">
                                            {{ formatCurrency(totales.asignaciones) }}
                                        </td>
                                        <td class="text-end text-warning">
                                            {{ formatCurrency(totales.no_remunerativo) }}
                                        </td>
                                    </tr>
                                    <tr class="fw-bold fs-6">
                                        <td colspan="7" class="text-end pe-3">
                                            Neto a Pagar
                                        </td>
                                        <td class="text-end">
                                            <span :class="netoTotal >= 0 ? 'text-success' : 'text-danger'">
                                                {{ formatCurrency(netoTotal) }}
                                            </span>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>

<style scoped>
.table th {
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    white-space: nowrap;
}
.table td {
    font-size: 0.875rem;
}
tfoot tr td {
    border-top: 2px solid #dee2e6;
}
</style>
