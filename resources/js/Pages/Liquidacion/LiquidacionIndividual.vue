<script setup>
import FormHeader from '@/Components/FormHeader.vue';
import { computed, ref, watch, nextTick, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';
import { avisarAjustesTipos } from '@/utils/avisarAjustesTipos';

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
    ajustesTipos: {
        type: Array,
        default: () => [],
    },
    historial: {
        type: Array,
        default: () => [],
    },
});

onMounted(() => avisarAjustesTipos(props.ajustesTipos));

// Copia reactiva de los conceptos (para edición inline + totales). Se resincroniza con la prop
// cada vez que cambia (recarga por legajo/período o tras guardar una edición).
const conceptosLocal = ref([]);
watch(() => props.conceptos, (nuevos) => {
    conceptosLocal.value = (nuevos || []).map(c => ({ ...c }));
}, { immediate: true, deep: true });

// Fila seleccionada en la tabla de conceptos
const selectedRowIndex = ref(props.conceptos?.length > 0 ? 0 : null);

// ---- Edición inline de celdas (doble-click), estilo Excel ----
const editando = ref({ index: null, campo: null });
const valorEdit = ref('');
const guardandoCelda = ref(false);

// Columna de monto editable según el tipo del concepto.
const campoMontoPorTipo = (tipo) => {
    if (tipo === 'H') return 'haberes';
    if (tipo === 'D') return 'retenciones';
    if (tipo === 'AS') return 'asignaciones';
    return 'no_remunerativo';
};

// Mapea la celda visual al campo real de sue090s.
const campoBackend = (campo) => {
    if (campo === 'cantidad') return 'cantidad';
    if (campo === 'valores') return 'valor';
    return 'importe'; // cualquier columna de monto
};

const esCeldaEditable = (item, campo) => {
    if (campo === 'cantidad' || campo === 'valores') return true;
    return campo === campoMontoPorTipo(item.tipo); // solo la columna de monto que corresponde al tipo
};

const valorCrudo = (item, campo) => {
    if (campo === 'cantidad') return item.cantidad ?? '';
    if (campo === 'valores') return item.valores ?? '';
    return item[campoMontoPorTipo(item.tipo)] ?? item.importe ?? '';
};

const iniciarEdicion = (index, campo, item) => {
    if (!props.empleado || !props.periodo) return;
    if (!esCeldaEditable(item, campo)) return;
    editando.value = { index, campo };
    valorEdit.value = String(valorCrudo(item, campo));
    nextTick(() => {
        const el = document.querySelector('.celda-edit-input');
        if (el) { el.focus(); el.select(); }
    });
};

const cancelarEdicion = () => {
    editando.value = { index: null, campo: null };
    valorEdit.value = '';
};

const estaEditando = (index, campo) => editando.value.index === index && editando.value.campo === campo;

const guardarCelda = async (item) => {
    const campo = editando.value.campo;
    if (campo === null || guardandoCelda.value) return;
    const nuevo = parseFloat(String(valorEdit.value).replace(',', '.'));
    if (Number.isNaN(nuevo)) { cancelarEdicion(); return; }

    const original = Number(valorCrudo(item, campo) ?? 0);
    if (nuevo === original) { cancelarEdicion(); return; } // sin cambios

    const [periodoStr, tipoliqStr] = String(selectedPeriodoKey.value || '').split('|');
    guardandoCelda.value = true;
    try {
        await axios.post(route('liquidacion.individual.actualizarConcepto'), {
            legajo_codigo: props.empleado.codigo,
            periodo: periodoStr || props.periodo,
            tipoliq: tipoliqStr || props.tipoliq,
            concepto: item.codigo,
            campo: campoBackend(campo),
            valor: nuevo,
        });
        cancelarEdicion();
        Swal.fire({ icon: 'success', title: 'Guardado', timer: 1200, showConfirmButton: false, toast: true, position: 'top-end' });
        // Refresca conceptos (valores/totales) e historial.
        router.reload({ only: ['conceptos', 'historial'], preserveScroll: true, preserveState: true });
    } catch (e) {
        Swal.fire({ icon: 'error', title: 'No se pudo guardar', text: e.response?.data?.message || e.message });
        cancelarEdicion();
    } finally {
        guardandoCelda.value = false;
    }
};

// ---- Menú contextual (click derecho) sobre Código / Descripción ----
const menuContextual = ref({ visible: false, x: 0, y: 0, item: null });

const abrirMenuContextual = (e, item) => {
    menuContextual.value = { visible: true, x: e.clientX, y: e.clientY, item };
};

const cerrarMenuContextual = () => {
    menuContextual.value = { visible: false, x: 0, y: 0, item: null };
};

const verConcepto = () => {
    const item = menuContextual.value.item;
    cerrarMenuContextual();
    if (item?.concepto_id) {
        window.open(route('liquidacion.conceptos.show', item.concepto_id), '_blank', 'noopener');
    }
};

// Estado del botón de filtro de períodos (true = activo, false = inactivo)
const filtrarPeriodosActivo = ref(true);

// Filtros locales
const selectedLegajoId = ref(props.legajoId ?? '');
const selectedPeriodoKey  = ref(
    props.periodo
        ? `${props.periodo}|${props.tipoliq ?? ''}`
        : ''
);

// Fecha de baja del empleado formateada dd/mm/aaaa (vacío si está activo).
const bajaEmpleado = computed(() => {
    const b = props.empleado?.baja;
    if (!b) return '';
    const [y, m, d] = String(b).slice(0, 10).split('-');
    return (y && m && d) ? `${d}/${m}/${y}` : String(b);
});

// Búsqueda progresiva de empleados
const tablaLimpia = ref(false);
const busquedaEmpleado = ref('');
const dropdownAbierto = ref(false);

// Inicializar texto del input si hay empleado seleccionado
if (props.legajoId && props.legajos.length) {
    const leg = props.legajos.find(l => l.id === props.legajoId);
    if (leg) busquedaEmpleado.value = `${leg.codigo} — ${leg.detalle}, ${leg.nombres}`;
}

watch(busquedaEmpleado, () => { indiceResaltado.value = -1; });

const legajosFiltrados = computed(() => {
    const q = busquedaEmpleado.value.toLowerCase().trim();
    if (!q) return props.legajos;
    return props.legajos.filter(leg =>
        (leg.codigo || '').toString().toLowerCase().includes(q) ||
        (leg.detalle || '').toLowerCase().includes(q) ||
        (leg.nombres || '').toLowerCase().includes(q) ||
        (leg.cuil || '').toLowerCase().includes(q)
    );
});

const indiceResaltado = ref(-1);

const seleccionarLegajo = (leg) => {
    selectedLegajoId.value = leg.id;
    busquedaEmpleado.value = `${leg.codigo} — ${leg.detalle}, ${leg.nombres}`;
    dropdownAbierto.value = false;
    indiceResaltado.value = -1;
    tablaLimpia.value = false;
    buscar();
};

const onFocusBusqueda = () => {
    dropdownAbierto.value = true;
    indiceResaltado.value = -1;
    busquedaEmpleado.value = '';
    selectedLegajoId.value = '';
};

const onBlurBusqueda = () => {
    setTimeout(() => { dropdownAbierto.value = false; indiceResaltado.value = -1; }, 200);
};

const onKeydownBusqueda = (e) => {
    if (!dropdownAbierto.value || legajosFiltrados.value.length === 0) return;

    if (e.key === 'ArrowDown') {
        e.preventDefault();
        indiceResaltado.value = (indiceResaltado.value + 1) % legajosFiltrados.value.length;
        scrollAlElemento();
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        indiceResaltado.value = indiceResaltado.value <= 0
            ? legajosFiltrados.value.length - 1
            : indiceResaltado.value - 1;
        scrollAlElemento();
    } else if (e.key === 'Enter') {
        e.preventDefault();
        if (indiceResaltado.value >= 0 && indiceResaltado.value < legajosFiltrados.value.length) {
            seleccionarLegajo(legajosFiltrados.value[indiceResaltado.value]);
        }
    } else if (e.key === 'Escape') {
        dropdownAbierto.value = false;
        indiceResaltado.value = -1;
    }
};

const scrollAlElemento = () => {
    nextTick(() => {
        const item = document.querySelector('.dropdown-legajos .item-resaltado');
        if (item) item.scrollIntoView({ block: 'nearest' });
    });
};

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
    if (selectedLegajoId.value) buscar();
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

// Fecha+hora del historial (dd/mm/aaaa HH:mm) sin parsear con Date (evita corrimiento de timezone).
const formatFechaHora = (f) => {
    if (!f) return '—';
    const [fecha, hora] = String(f).split(' ');
    const [y, m, d] = (fecha || '').split('-');
    return (y && m && d) ? `${d}/${m}/${y}${hora ? ' ' + hora.substring(0, 5) : ''}` : String(f);
};

const origenLabel = (o) => ({
    ajuste_aportes_lsd: 'Ajuste de aportes (LSD)',
    liquidacion_individual: 'Liquidación individual',
    liquidacion_global: 'Liquidación global',
    generador_txt: 'Generador LSD',
}[o] || (o || '—'));

const TIPOS_LIQ = {
    1: 'Normal',
    2: '1er. Quincena',
    3: '2da. Quincena',
    4: 'SAC',
    5: 'Liq. Final',
    6: 'DIF.HAB.',
};

const formatPeriodoConTipo = (periodoObj) => {
    const periodoFmt = formatPeriodo(periodoObj?.periodo);
    const tipo = TIPOS_LIQ[Number(periodoObj?.tipoliq)] ?? '';
    return tipo ? `${periodoFmt} - ${tipo}` : periodoFmt;
};

// Totales calculados
const totales = computed(() => {
    return conceptosLocal.value.reduce(
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

// Modal confirmación de eliminación
const mostrarModalEliminar = ref(false);
const eliminando = ref(false);

const abrirModalEliminar = () => {
    if (!props.empleado || !props.periodo) return;
    mostrarModalEliminar.value = true;
};

const confirmarEliminar = async () => {
    eliminando.value = true;
    try {
        const [periodoStr, tipoliqStr] = String(selectedPeriodoKey.value || '').split('|');
        await axios.delete(route('liquidacion.individual.eliminar'), {
            data: {
                legajo_codigo: props.empleado.codigo,
                periodo: periodoStr || props.periodo,
                tipoliq: tipoliqStr || props.tipoliq,
            }
        });
        mostrarModalEliminar.value = false;
        tablaLimpia.value = true;
        // Recargar para reflejar los cambios
        buscar();
    } catch (e) {
        alert('Error al eliminar: ' + (e.response?.data?.message || e.message));
    } finally {
        eliminando.value = false;
    }
};
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
                            <!-- Selector de empleado con búsqueda progresiva -->
                            <div class="col-12 col-md-5 mt-1" style="position: relative;">
                                <i class="ri-user-line ri-20px text-primary"></i>
                                <label class="form-label mb-1">&nbsp;Empleado</label>
                                <div style="position: relative;">
                                    <input
                                        type="text"
                                        class="form-control"
                                        style="font-size: 0.75rem; max-height: 39px; padding-right: 28px;"
                                        v-model="busquedaEmpleado"
                                        @focus="onFocusBusqueda"
                                        @blur="onBlurBusqueda"
                                        @keydown="onKeydownBusqueda"
                                        placeholder="Buscar por legajo, apellido, nombre o CUIL..."
                                        autocomplete="off"
                                    >
                                    <i
                                        v-if="busquedaEmpleado"
                                        class="ri-close-line"
                                        style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); cursor: pointer; font-size: 1rem; color: #999;"
                                        @mousedown.prevent="busquedaEmpleado = ''; selectedLegajoId = ''; dropdownAbierto = false; tablaLimpia = true;"
                                    ></i>
                                </div>
                                <ul
                                    v-if="dropdownAbierto && legajosFiltrados.length > 0"
                                    class="list-group shadow dropdown-legajos"
                                    style="position: absolute; z-index: 1050; width: calc(100% - 24px); max-height: 250px; overflow-y: auto; font-size: 0.75rem; background-color: #fff;"
                                >
                                    <li
                                        v-for="(leg, idx) in legajosFiltrados"
                                        :key="leg.id"
                                        class="list-group-item list-group-item-action py-1 px-2"
                                        :class="{ 'active item-resaltado': idx === indiceResaltado }"
                                        style="cursor: pointer;"
                                        @mousedown.prevent="seleccionarLegajo(leg)"
                                    >
                                        <strong>{{ leg.codigo }}</strong> — {{ leg.detalle }}, {{ leg.nombres }}
                                        <span class="text-muted ms-2">{{ leg.cuil }}</span>
                                    </li>
                                </ul>
                            </div>
                            <!-- Foto del empleado -->
                            <div class="col-auto d-flex align-items-end mt-1">
                                <img
                                    :src="empleado?.foto_url || '/img/avatars/1.png'"
                                    alt="Foto empleado"
                                    class="rounded-circle border"
                                    style="width: 55px; height: 55px; object-fit: cover;"
                                >
                            </div>
                            <!-- Selector de período -->
                            <div class="col-6 col-md-3 mt-1 ms-9">
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

                            <!-- Pastilla de baja: pegada al margen derecho, a la altura del selector de período -->
                            <div class="col-auto ms-auto d-flex align-items-center">
                                <span v-if="bajaEmpleado" class="badge bg-danger rounded-pill">
                                    <i class="ri-user-unfollow-line me-1"></i> Baja el {{ bajaEmpleado }}
                                </span>
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
                            <li class="nav-item" role="presentation">
                                <button
                                    class="nav-link"
                                    data-bs-toggle="tab"
                                    data-bs-target="#form-tabs-historial"
                                    role="tab"
                                    aria-selected="false"
                                >
                                    <span class="ri-history-line ri-20px d-sm-none"></span>
                                    <span class="d-none d-sm-block">
                                        Historial de cambios
                                        <span v-if="historial.length" class="badge bg-label-secondary ms-1">{{ historial.length }}</span>
                                    </span>
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
                                    class="btn btn-icon btn-sm btn-eliminar-liq"
                                    title="Eliminar"
                                    data-bs-toggle="tooltip"
                                    data-bs-placement="bottom"
                                    @click="abrirModalEliminar"
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
                                            <template v-if="!tablaLimpia && conceptosLocal && conceptosLocal.length > 0">
                                                <tr
                                                    v-for="(item, index) in conceptosLocal"
                                                    :key="item.id ?? index"
                                                    :class="{ 'table-primary': selectedRowIndex === index }"
                                                    style="cursor: pointer;"
                                                    @click="selectedRowIndex = index"
                                                >
                                                    <td class="text-center fw-semibold" @contextmenu.prevent="abrirMenuContextual($event, item)">{{ item.codigo }}</td>
                                                    <td @contextmenu.prevent="abrirMenuContextual($event, item)">{{ item.detalle ?? item.descripcion }}</td>
                                                    <!-- Cantidad (editable) -->
                                                    <td class="text-end celda-editable" @dblclick.stop="iniciarEdicion(index, 'cantidad', item)" title="Doble-click para editar">
                                                        <input v-if="estaEditando(index, 'cantidad')" class="celda-edit-input form-control form-control-sm text-end py-0" v-model="valorEdit"
                                                               @click.stop @keydown.enter.prevent="guardarCelda(item)" @keydown.esc.prevent="cancelarEdicion" @blur="guardarCelda(item)" :disabled="guardandoCelda">
                                                        <span v-else>{{ item.cantidad != null ? formatCurrency(item.cantidad) : '' }}</span>
                                                    </td>
                                                    <!-- Valores (editable) -->
                                                    <td class="text-end celda-editable" @dblclick.stop="iniciarEdicion(index, 'valores', item)" title="Doble-click para editar">
                                                        <input v-if="estaEditando(index, 'valores')" class="celda-edit-input form-control form-control-sm text-end py-0" v-model="valorEdit"
                                                               @click.stop @keydown.enter.prevent="guardarCelda(item)" @keydown.esc.prevent="cancelarEdicion" @blur="guardarCelda(item)" :disabled="guardandoCelda">
                                                        <span v-else>{{ item.valores != null ? formatCurrency(item.valores) : '' }}</span>
                                                    </td>
                                                    <!-- Haberes (editable solo si tipo H) -->
                                                    <td class="text-end text-success" :class="{ 'celda-editable': esCeldaEditable(item, 'haberes') }" @dblclick.stop="iniciarEdicion(index, 'haberes', item)" :title="esCeldaEditable(item, 'haberes') ? 'Doble-click para editar' : ''">
                                                        <input v-if="estaEditando(index, 'haberes')" class="celda-edit-input form-control form-control-sm text-end py-0" v-model="valorEdit"
                                                               @click.stop @keydown.enter.prevent="guardarCelda(item)" @keydown.esc.prevent="cancelarEdicion" @blur="guardarCelda(item)" :disabled="guardandoCelda">
                                                        <span v-else-if="item.haberes">{{ formatCurrency(item.haberes) }}</span>
                                                    </td>
                                                    <!-- Retenciones (editable solo si tipo D) -->
                                                    <td class="text-end text-danger" :class="{ 'celda-editable': esCeldaEditable(item, 'retenciones') }" @dblclick.stop="iniciarEdicion(index, 'retenciones', item)" :title="esCeldaEditable(item, 'retenciones') ? 'Doble-click para editar' : ''">
                                                        <input v-if="estaEditando(index, 'retenciones')" class="celda-edit-input form-control form-control-sm text-end py-0" v-model="valorEdit"
                                                               @click.stop @keydown.enter.prevent="guardarCelda(item)" @keydown.esc.prevent="cancelarEdicion" @blur="guardarCelda(item)" :disabled="guardandoCelda">
                                                        <span v-else-if="item.retenciones">{{ formatCurrency(item.retenciones) }}</span>
                                                    </td>
                                                    <!-- Asignaciones (editable solo si tipo AS) -->
                                                    <td class="text-end text-info" :class="{ 'celda-editable': esCeldaEditable(item, 'asignaciones') }" @dblclick.stop="iniciarEdicion(index, 'asignaciones', item)" :title="esCeldaEditable(item, 'asignaciones') ? 'Doble-click para editar' : ''">
                                                        <input v-if="estaEditando(index, 'asignaciones')" class="celda-edit-input form-control form-control-sm text-end py-0" v-model="valorEdit"
                                                               @click.stop @keydown.enter.prevent="guardarCelda(item)" @keydown.esc.prevent="cancelarEdicion" @blur="guardarCelda(item)" :disabled="guardandoCelda">
                                                        <span v-else-if="item.asignaciones">{{ formatCurrency(item.asignaciones) }}</span>
                                                    </td>
                                                    <!-- No Remunerativo (editable si no es H/D/AS) -->
                                                    <td class="text-end text-warning" :class="{ 'celda-editable': esCeldaEditable(item, 'no_remunerativo') }" @dblclick.stop="iniciarEdicion(index, 'no_remunerativo', item)" :title="esCeldaEditable(item, 'no_remunerativo') ? 'Doble-click para editar' : ''">
                                                        <input v-if="estaEditando(index, 'no_remunerativo')" class="celda-edit-input form-control form-control-sm text-end py-0" v-model="valorEdit"
                                                               @click.stop @keydown.enter.prevent="guardarCelda(item)" @keydown.esc.prevent="cancelarEdicion" @blur="guardarCelda(item)" :disabled="guardandoCelda">
                                                        <span v-else-if="item.no_remunerativo">{{ formatCurrency(item.no_remunerativo) }}</span>
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
                                        <tfoot v-if="!tablaLimpia" class="table-secondary">
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
                        <div class="tab-pane fade pt-0" id="form-tabs-historial" role="tabpanel">
                            <div class="card-body p-0" style="font-size: 0.875rem;">
                                <div class="table-responsive" style="margin-left: 35.78px; width: calc(100% - 35.78px);">
                                    <table class="table table-hover table-bordered mb-0 align-middle tabla-conceptos-compacta">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="text-nowrap">Fecha y hora</th>
                                                <th class="text-center">Concepto</th>
                                                <th class="text-end">Importe anterior</th>
                                                <th class="text-end">Importe nuevo</th>
                                                <th class="text-end">Diferencia</th>
                                                <th>Motivo</th>
                                                <th>Origen</th>
                                                <th>Usuario</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="h in historial" :key="h.id">
                                                <td class="text-nowrap">{{ formatFechaHora(h.fecha) }}</td>
                                                <td class="text-center">{{ h.concepto }}<span v-if="h.concepto_arca" class="text-muted"> ({{ h.concepto_arca }})</span></td>
                                                <td class="text-end">{{ formatCurrency(h.importe_anterior) }}</td>
                                                <td class="text-end">{{ formatCurrency(h.importe_nuevo) }}</td>
                                                <td class="text-end" :class="h.diferencia < 0 ? 'text-danger' : 'text-success'">{{ formatCurrency(h.diferencia) }}</td>
                                                <td>{{ h.motivo }}</td>
                                                <td><span class="badge bg-label-info">{{ origenLabel(h.origen) }}</span></td>
                                                <td>{{ h.usuario || '—' }}</td>
                                            </tr>
                                            <tr v-if="!historial.length">
                                                <td colspan="8" class="text-center text-muted py-4">
                                                    <i class="ri-history-line ri-24px me-2"></i>
                                                    Sin modificaciones registradas para este período.
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de confirmación de eliminación -->
        <div v-if="mostrarModalEliminar" class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Eliminar liquidación</h5>
                        <button type="button" class="btn-close" @click="mostrarModalEliminar = false"></button>
                    </div>
                    <div class="modal-body">
                        <div class="d-flex align-items-center gap-3">
                            <i class="ri-error-warning-line ri-24px text-warning"></i>
                            <div>
                                <p class="mb-1">¿Está seguro que desea eliminar todos los conceptos de la liquidación?</p>
                                <p class="mb-0 text-muted" style="font-size: 0.85rem;">
                                    <strong>Empleado:</strong> {{ empleado?.codigo }} — {{ empleado?.detalle }}, {{ empleado?.nombres }}<br>
                                    <strong>Período:</strong> {{ formatPeriodo(periodo) }}
                                </p>
                                <p class="mt-2 mb-0 text-danger" style="font-size: 0.85rem;">Esta acción no se puede deshacer.</p>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" @click="mostrarModalEliminar = false">Cancelar</button>
                        <button type="button" class="btn btn-danger" :disabled="eliminando" @click="confirmarEliminar">
                            <i class="ri-delete-bin-line me-1"></i>
                            {{ eliminando ? 'Eliminando...' : 'Eliminar' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Menú contextual (click derecho sobre Código / Descripción) -->
        <template v-if="menuContextual.visible">
            <div class="menu-contextual-backdrop" @click="cerrarMenuContextual" @contextmenu.prevent="cerrarMenuContextual"></div>
            <ul class="menu-contextual" :style="{ top: menuContextual.y + 'px', left: menuContextual.x + 'px' }">
                <li
                    :class="{ 'menu-contextual-disabled': !menuContextual.item?.concepto_id }"
                    @click="verConcepto"
                >
                    <i class="ri-eye-line me-2"></i> Ver concepto
                </li>
            </ul>
        </template>

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
.btn-eliminar-liq:hover i,
.btn-eliminar-liq:focus i {
    color: #dc3545 !important;
}
/* Subrayado azul de la pestaña activa por CSS (el slider JS del template no sigue los clicks
   de las tabs de Bootstrap/Inertia, así que la pestaña recién activada se quedaba sin la raya). */
.nav-tabs .nav-link.active {
    color: var(--bs-primary, #696cff);
    border-bottom: 2px solid var(--bs-primary, #696cff) !important;
}
.tab-slider {
    display: none;
}

/* Menú contextual (click derecho) */
.menu-contextual-backdrop {
    position: fixed;
    inset: 0;
    z-index: 1090;
}
.menu-contextual {
    position: fixed;
    z-index: 1091;
    min-width: 180px;
    margin: 0;
    padding: 4px 0;
    list-style: none;
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.18);
}
.menu-contextual li {
    padding: 8px 16px;
    font-size: 0.875rem;
    cursor: pointer;
    white-space: nowrap;
    display: flex;
    align-items: center;
}
.menu-contextual li:hover {
    background: var(--bs-primary, #696cff);
    color: #fff;
}
.menu-contextual li.menu-contextual-disabled {
    color: #adb5bd;
    cursor: not-allowed;
    pointer-events: none;
}

/* Celdas editables (estilo Excel) */
.celda-editable {
    cursor: cell;
}
.celda-editable:hover {
    background-color: rgba(105, 108, 255, 0.08);
    outline: 1px dashed rgba(105, 108, 255, 0.45);
    outline-offset: -1px;
}
.celda-edit-input {
    height: 28px;
    min-width: 70px;
    display: inline-block;
}
</style>
