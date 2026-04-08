<script setup>
import FormHeader from '@/Components/FormHeader.vue';
import { computed, ref, watch } from 'vue';
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
    tipoliq: {
        type: [Number, String, null],
        default: null,
    },
    legajoId: {
        type: Number,
        default: null,
    },
});

// Fila seleccionada en la tabla de conceptos
const selectedRowIndex = ref(props.conceptos?.length > 0 ? 0 : null);

// Estado del botón de filtro de períodos (true = activo, false = inactivo)
const filtrarPeriodosActivo = ref(true);

// Filtros locales
const selectedLegajoId = ref(props.legajoId ?? '');
const selectedPeriodoKey  = ref(
    props.periodo
        ? `${props.periodo}|${props.tipoliq ?? ''}`
        : ''
);

// Recarga parcial de legajos según período y estado del filtro
const recargarLegajos = () => {
    const [periodo, tipoliq] = String(selectedPeriodoKey.value || '').split('|');
    router.reload({
        only: ['legajos'],
        data: {
            legajo_id:         selectedLegajoId.value || undefined,
            periodo:           periodo || undefined,
            tipoliq:           tipoliq || undefined,
            filter_by_periodo: filtrarPeriodosActivo.value ? 1 : undefined,
        },
        preserveScroll: true,
        preserveState:  true,
    });
};

watch(selectedPeriodoKey, () => {
    recargarLegajos();
});

watch(filtrarPeriodosActivo, () => {
    recargarLegajos();
});

const buscar = () => {
    const [periodoSeleccionado, tipoliqSeleccionado] = String(selectedPeriodoKey.value || '').split('|');

    router.get(route('liquidacion.individual.index'), {
        legajo_id: selectedLegajoId.value || undefined,
        periodo:   periodoSeleccionado || undefined,
        tipoliq:   tipoliqSeleccionado || undefined,
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

const formatDate = (value) => {
    if (!value) return '—';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value;
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    return `${day}/${month}/${year}`;
};

const formatPeriodo = (value) => {
    if (!value) return '';
    const periodo = String(value);
    if (periodo.length === 6) {
        return `${periodo.substring(0, 4)}/${periodo.substring(4, 6)}`;
    }
    return periodo;
};

const TIPOS_LIQ = {
    1: 'Normal',
    2: '1er. Quincena',
    3: '2da. Quincena',
    4: 'SAC',
    5: 'Liq. Final',
};

const formatPeriodoConTipo = (periodoObj) => {
    const periodoFmt = formatPeriodo(periodoObj?.periodo);
    const tipo = TIPOS_LIQ[Number(periodoObj?.tipoliq)] ?? '';
    return tipo ? `${periodoFmt} - ${tipo}` : periodoFmt;
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

const brutoTotal = computed(() =>
    totales.value.haberes + totales.value.asignaciones
);

const subtotalConNoRemunerativo = computed(() =>
    brutoTotal.value + totales.value.no_remunerativo
);

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
            </div>
        </div>

        <!-- FILTROS -->
        <div class="row mb-4">
            <div class="col">
                <div class="card">
                    <div class="card-body py-3" style="font-size: 0.875rem;">
                        <div class="row g-3 align-items-end">
                            <!-- Selector de empleado -->
                            <div class="col-12 col-md-5 mt-1">
                                <i class="ri-user-line ri-20px text-primary"></i>
                                <label class="form-label mb-1">&nbsp;Empleado</label>
                                <select class="form-select" style="font-size: 0.75rem; max-height: 39px;" v-model="selectedLegajoId">
                                    <option value="">— Seleccionar empleado —</option>
                                    <option v-for="leg in legajos" :key="leg.id" :value="leg.id">
                                        {{ leg.codigo }} — {{ leg.detalle }}, {{ leg.nombres }}
                                    </option>
                                </select>
                            </div>
                            <!-- Selector de período -->
                            <div class="col-6 col-md-3 mt-1">
                                <label class="form-label mb-1 d-flex align-items-center gap-1">
                                    Período
                                    <button
                                        id="btnFiltrarLegajos"
                                        name="btnFiltrarLegajos"
                                        type="button"
                                        class="btn btn-icon btn-xs p-0"
                                        :title="filtrarPeriodosActivo ? 'Filtro activo' : 'Filtro inactivo'"
                                        data-bs-toggle="tooltip"
                                        data-bs-placement="top"
                                        style="line-height:1;"
                                        @click="filtrarPeriodosActivo = !filtrarPeriodosActivo"
                                    >
                                        <i
                                            :class="[
                                                filtrarPeriodosActivo ? 'ri-filter-line' : 'ri-filter-off-line',
                                                'ri-16px',
                                                filtrarPeriodosActivo ? 'text-primary' : 'text-muted'
                                            ]"
                                        ></i>
                                    </button>
                                </label>
                                <select class="form-select" style="font-size: 0.75rem; max-height: 39px;" v-model="selectedPeriodoKey">
                                    <option value="">— Seleccionar período —</option>
                                    <option v-for="per in periodos" :key="per.id" :value="`${per.periodo}|${per.tipoliq ?? ''}`">
                                        {{ formatPeriodoConTipo(per) }}
                                    </option>
                                </select>
                            </div>
                            <!-- Botón buscar -->
                            <div class="col-6 col-md-2 align-self-center pt-5">
                                <button type="button" class="btn btn-primary w-100" style="font-size: 0.75rem;" @click="buscar">
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
                    <div class="card-body py-3" style="font-size: 0.875rem;">
                        <div class="row g-4">
                            <!-- Legajo -->
                            <!-- <div class="col-6 col-md-2">
                                <small class="text-muted d-block">Legajo</small>
                                <span class="fw-semibold">{{ empleado?.codigo ?? '—' }}</span>
                            </div> -->
                            <!-- Empresa -->
                            <div class="col-2 col-md-4">
                                <small class="text-muted d-block">Empresa</small>
                                <span class="fw-semibold">{{ empleado?.grupo_empresario?.detalle ?? empleado?.grupo_emp ?? '—' }}</span>
                            </div>
                            <!-- CUIL -->
                            <div class="col-1 col-md-2">
                                <small class="text-muted d-block">CUIL</small>
                                <span class="fw-semibold">{{ empleado?.cuil ?? '—' }}</span>
                            </div>
                            <!-- Convenio -->
                            <div class="col-4 col-md-3">
                                <small class="text-muted d-block">Convenio</small>
                                <span class="fw-semibold">
                                    {{ empleado?.convenio
                                        ? (empleado?.convenios?.detalle
                                            ? `${empleado.convenio} - ${empleado.convenios.detalle}`
                                            : empleado.convenio)
                                        : '—' }}
                                </span>
                            </div>
                            <!-- Función / Cargo -->
                            <div class="col-6 col-md-3">
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
                                    <span class="fw-semibold">
                                        {{ empleado?.obra_sijp?.codigo
                                            ? `${empleado.obra_sijp.codigo} - ${empleado.obra_sijp.detalle ?? ''}`.substring(0, 22)
                                            : '—' }}
                                    </span>
                            </div>
                            <!-- Ingreso -->
                            <div class="col-6 col-md-2">
                                <small class="text-muted d-block">Ingreso</small>
                                <span class="fw-semibold">{{ formatDate(empleado?.alta) }}</span>
                            </div>
                            <!-- Egreso -->
                            <div class="col-6 col-md-2">
                                <small class="text-muted d-block">Egreso</small>
                                <span class="fw-semibold">{{ formatDate(empleado?.baja) }}</span>
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
                    <div class="card-header overflow-hidden">
                        <ul class="nav nav-tabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button
                                    class="nav-link active"
                                    data-bs-toggle="tab"
                                    data-bs-target="#form-tabs-conceptos"
                                    role="tab"
                                    aria-selected="true"
                                >
                                    <span class="ri-file-list-3-line ri-20px d-sm-none"></span>
                                    <span class="d-none d-sm-block">Conceptos de Liquidación</span>
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button
                                    class="nav-link"
                                    data-bs-toggle="tab"
                                    data-bs-target="#form-tabs-ganancias"
                                    role="tab"
                                    aria-selected="false"
                                >
                                    <span class="ri-money-dollar-circle-line ri-20px d-sm-none"></span>
                                    <span class="d-none d-sm-block">Impuesto a las Ganancias</span>
                                </button>
                            </li>

                            <!-- Botones de acción alineados a la derecha -->
                            <li class="nav-item ms-auto d-flex align-items-center gap-1 pe-1">
                                <button
                                    type="button"
                                    class="btn btn-icon btn-sm"
                                    title="Grabar"
                                    data-bs-toggle="tooltip"
                                    data-bs-placement="bottom"
                                >
                                    <i class="ri-save-line ri-20px text-secondary"></i>
                                </button>
                                <button
                                    type="button"
                                    class="btn btn-icon btn-sm"
                                    title="Cancelar"
                                    data-bs-toggle="tooltip"
                                    data-bs-placement="bottom"
                                >
                                    <i class="ri-close-circle-line ri-20px text-secondary"></i>
                                </button>
                                <button
                                    type="button"
                                    class="btn btn-icon btn-sm"
                                    title="Eliminar"
                                    data-bs-toggle="tooltip"
                                    data-bs-placement="bottom"
                                >
                                    <i class="ri-delete-bin-line ri-20px text-secondary"></i>
                                </button>
                                <button
                                    type="button"
                                    class="btn btn-icon btn-sm"
                                    title="Recalcular"
                                    data-bs-toggle="tooltip"
                                    data-bs-placement="bottom"
                                >
                                    <i class="ri-refresh-line ri-20px text-secondary"></i>
                                </button>
                            </li>

                            <span class="tab-slider" style="bottom: 0px;"></span>
                        </ul>
                    </div>
                    <div class="tab-content pt-0" style="padding-left: 0px;">
                        <div class="tab-pane fade active show" id="form-tabs-conceptos" role="tabpanel">
                            <div class="card-body p-0" style="font-size: 0.875rem;">
                                <div class="d-flex">
                                    <div class="barra-acciones-conceptos d-flex flex-column align-items-center py-2">
                                        <button
                                            type="button"
                                            class="btn btn-icon btn-sm mb-2"
                                            title="Nuevo"
                                            data-bs-toggle="tooltip"
                                        >
                                            <i class="ri-add-line ri-20px text-primary"></i>
                                        </button>
                                        <button
                                            type="button"
                                            class="btn btn-icon btn-sm mb-2"
                                            title="Editar"
                                            data-bs-toggle="tooltip"
                                        >
                                            <i class="ri-edit-line ri-20px text-primary"></i>
                                        </button>
                                        <button
                                            type="button"
                                            class="btn btn-icon btn-sm"
                                            title="Eliminar"
                                            data-bs-toggle="tooltip"
                                        >
                                            <i class="ri-delete-bin-line ri-20px text-primary"></i>
                                        </button>
                                    </div>
                                    <div class="table-responsive flex-grow-1 contenedor-tabla-conceptos">
                                        <table class="table table-hover table-bordered mb-0 align-middle tabla-conceptos-compacta">
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
                                                <tr
                                                    v-for="(item, index) in conceptos"
                                                    :key="index"
                                                    :class="{ 'table-primary': selectedRowIndex === index }"
                                                    style="cursor: pointer;"
                                                    @click="selectedRowIndex = index"
                                                >
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
                                            <tr class="fw-bold fs-6 table-light">
                                                <td colspan="7" class="text-end pe-3">
                                                    Subtotal (Bruto + No Remunerativo)
                                                </td>
                                                <td class="text-end text-primary">
                                                    {{ formatCurrency(subtotalConNoRemunerativo) }}
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
                        <div class="tab-pane fade pt-0" id="form-tabs-ganancias" role="tabpanel">
                            <div class="card-body py-4 text-muted text-center">
                                Sin datos para mostrar.
                            </div>
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
.tabla-conceptos-compacta th,
.tabla-conceptos-compacta td {
    padding-top: 0.4rem;
    padding-bottom: 0.4rem;
}
.barra-acciones-conceptos {
    min-width: 35px;
    border-right: 1px solid #dee2e6;
    background-color: #fff;
    padding-right: 0px !important;
    padding-left: 4px !important;
}
.contenedor-tabla-conceptos {
    margin-left: -1px;
}
tfoot tr td {
    border-top: 2px solid #dee2e6;
}
</style>
