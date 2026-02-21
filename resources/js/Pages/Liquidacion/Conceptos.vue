<script setup>
import TextInput from '@/Components/TextInput.vue';
import Input from '@/Components/Input.vue';
import InputError from '@/Components/InputError.vue';
import FormHeader from '@/Components/FormHeader.vue';
import { router, Link, useForm, usePage } from '@inertiajs/vue3';
import { reactive, ref, onMounted, watch, nextTick, computed } from 'vue';

const props = defineProps({
    concepto: {
        type: Object,
        default: () => ({ 
            id: null, 
            codigo: '', 
            detalle: '',
            tipo: 1,
            formula: '',
            porcentaje: null,
            importe_fijo: null,
            imponible: true,
            afecta_sac: true,
            afecta_vacaciones: true,
            imprime_recibo: true,
            orden_impresion: null,
            activo: true,
            cuenta_contable: '',
            observaciones: '',
            sicoss_afecta: false,
            gcias_afecta: false,
        }),
    },
    agregar: {
        type: Boolean,
        required: true
    },
    edicion: {
        type: Boolean,
        required: true
    },
    empresa: {
        type: Object,
        required: true
    },
});

// Estado local para mantener el modo edición/agregar
const modoEdicion = ref(props.edicion);
const modoAgregar = ref(props.agregar);
// Variables reactivas para manejar el estado de enfoque y la opción seleccionada
const isFocused = ref(false);
const selectedOption = ref(null);

const form = useForm({
    id: props.concepto?.id ?? null,
    codigo: props.concepto?.codigo ?? 0,
    detalle: props.concepto?.detalle ?? '',
    tipo: props.concepto?.tipo ?? 0,
    formula: props.concepto?.formula ?? '',
    porcentaje: props.concepto?.porcentaje ?? null,
    importe_fijo: props.concepto?.importe_fijo ?? null,
    imponible: props.concepto?.imponible ?? true,
    afecta_sac: props.concepto?.afecta_sac ?? true,
    afecta_vacaciones: props.concepto?.afecta_vacaciones ?? true,
    imprime_recibo: props.concepto?.imprime_recibo ?? true,
    orden_impresion: props.concepto?.orden_impresion ?? null,
    activo: props.concepto?.activo ?? true,
    cuenta_contable: props.concepto?.cuenta_contable ?? '',
    observaciones: props.concepto?.observaciones ?? '',
    sicoss_afecta: props.concepto?.sicoss_afecta ?? false,
    gcias_afecta: props.concepto?.gcias_afecta ?? false,
    concepto_arca: props.concepto?.concepto_arca ?? null,
});

const TIPOS = {
    1: 'HABER',
    2: 'DESCUENTO',
    3: 'ASIGNACIONES',
    4: 'NO_REMUNERATIVO',
    5: 'GANANCIAS',
    6: 'DEVOLUCIÓN DE GANANCIA',
    7: 'REDONDEO',
    8: 'APORTES',
    9: 'AUXILIARES',
};

const submit = () => {
    // Validar que no haya error de rango
    if (modoAgregar.value && errorCodigo.value) {
        alert('No se puede guardar: ' + errorCodigo.value);
        return;
    }

    if (modoAgregar.value) {
        // Crear nuevo concepto
        form.post(route('liquidacion.conceptos.store'), {
            preserveScroll: true,
            onSuccess: () => {
                // Navegar al último registro creado para mostrar todos los botones de navegación y CRUD
                router.get(route('liquidacion.conceptos.last'));
            }
        });
    } else {
        // Actualizar concepto existente
        form.patch(route('liquidacion.conceptos.update', form.id), {
            preserveScroll: true,
            onSuccess: () => {
                // Volver al modo visualización después de guardar
                modoAgregar.value = false;
                modoEdicion.value = false;
            }
        });
    }
};

const borrar = () => {
    const id = props.concepto?.id
    if (!id) return

    router.delete(route('liquidacion.conceptos.destroy', id), {
        preserveScroll: true,
        onSuccess: () => {
            // Cerrar el modal y limpiar el backdrop
            closeModal();
            // Después de borrar, navegar al último registro creado
            router.get(route('liquidacion.conceptos.last'));
        }
    })
};

const closeModal = () => {
    // Cerrar el modal usando Bootstrap's Modal API
    const modalElement = document.getElementById('modalDelete');
    if (modalElement) {
        const modal = window.bootstrap.Modal.getInstance(modalElement);
        if (modal) {
            modal.hide();
        }
    }
    
    // Eliminar todos los backdrops residuales
    setTimeout(() => {
        const backdrops = document.querySelectorAll('.modal-backdrop');
        backdrops.forEach(backdrop => backdrop.remove());
        
        // Remover la clase que Bootstrap añade al body
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
    }, 300);
};

const txttipo = ref(null);
const txtcodigo = ref(null);
const txtdetalle = ref(null);

const errorCodigo = ref('');
const loading = ref(false);
const conceptosArca = ref([]);
const loadingConceptos = ref(false);
const showConceptoDropdown = ref(false);
const conceptoSearchInput = ref('');
const selectedConceptoIndex = ref(-1);

// Filtrar conceptos según búsqueda
const conceptosFiltrados = computed(() => {
    if (!conceptoSearchInput.value) {
        return conceptosArca.value;
    }
    const search = conceptoSearchInput.value.toLowerCase();
    return conceptosArca.value.filter(concepto => {
        return concepto.text.toLowerCase().includes(search) || 
               concepto.id.toLowerCase().includes(search);
    });
});

const setFocus = () => {
    if (modoAgregar.value) {
        txttipo.value?.focus();
    } else {
        txtdetalle.value?.focus();
    }
};

// Buscar próximo código disponible cuando cambia el tipo
const buscarProximoCodigo = async () => {
    if (!modoAgregar.value || !form.tipo) return;

    loading.value = true;
    errorCodigo.value = '';

    try {
        const response = await fetch(`/liquidacion/conceptos/proximoCodigo?tipo=${form.tipo}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
        });

        const data = await response.json();

        if (!response.ok) {
            errorCodigo.value = data.error || 'Error al buscar código disponible';
            form.codigo = null;
        } else {
            form.codigo = data.codigo;
            errorCodigo.value = '';
        }
    } catch (error) {
        errorCodigo.value = 'Error de conexión al buscar código';
        console.error('Error:', error);
    } finally {
        loading.value = false;
    }
};

// Validar que el código esté dentro del rango del tipo seleccionado
const validarCodigoEnRango = async () => {
    if (!modoAgregar.value || !form.tipo || !form.codigo) {
        errorCodigo.value = '';
        return;
    }

    try {
        const response = await fetch(`/liquidacion/conceptos/proximoCodigo?tipo=${form.tipo}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
        });

        const data = await response.json();

        if (response.ok) {
            // Validar que el código esté en el rango
            if (form.codigo < data.desde || form.codigo > data.hasta) {
                errorCodigo.value = `Código fuera de rango. Rango válido: ${data.desde} a ${data.hasta}`;
            } else {
                errorCodigo.value = '';
            }
        }
    } catch (error) {
        console.error('Error:', error);
    }
};

// Función para buscar conceptos Arca
const buscarConceptosArca = async (search) => {
    loadingConceptos.value = true;
    try {
        const searchTerm = search && search.length > 0 ? search : '';
        const response = await fetch(route('liquidacion.conceptos.buscarConceptosArca') + '?search=' + encodeURIComponent(searchTerm), {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            }
        });
        const data = await response.json();
        conceptosArca.value = data.results || [];
    } catch (error) {
        console.error('Error al buscar conceptos Arca:', error);
        conceptosArca.value = [];
    } finally {
        loadingConceptos.value = false;
    }
};

// Cargar concepto seleccionado en la entrada
const cargarConceptoSeleccionado = () => {
    if (form.concepto_arca) {
        // Buscar el concepto en la lista y mostrar su texto
        const concepto = conceptosArca.value.find(c => c.id === form.concepto_arca);
        if (concepto) {
            conceptoSearchInput.value = concepto.text;
        } else {
            conceptoSearchInput.value = form.concepto_arca;
        }
    }
};

// Cerrar el dropdown después de perder el foco
const cerrarDropdownConDelay = () => {
    setTimeout(() => {
        showConceptoDropdown.value = false;
        selectedConceptoIndex.value = -1;
    }, 200);
};

// Manejar navegación del dropdown con teclas de dirección
const manejarNavegacionDropdown = (evento) => {
    // Si el dropdown está cerrado y presionan ArrowDown, abrirlo
    if (!showConceptoDropdown.value && evento.key === 'ArrowDown') {
        evento.preventDefault();
        showConceptoDropdown.value = true;
        buscarConceptosArca('');
        selectedConceptoIndex.value = 0;
        return;
    }

    if (!showConceptoDropdown.value || conceptosFiltrados.value.length === 0) {
        return;
    }

    switch (evento.key) {
        case 'ArrowDown':
            evento.preventDefault();
            selectedConceptoIndex.value = Math.min(
                selectedConceptoIndex.value + 1,
                conceptosFiltrados.value.length - 1
            );
            break;
        case 'ArrowUp':
            evento.preventDefault();
            selectedConceptoIndex.value = Math.max(selectedConceptoIndex.value - 1, -1);
            break;
        case 'Enter':
            evento.preventDefault();
            if (selectedConceptoIndex.value >= 0) {
                const concepto = conceptosFiltrados.value[selectedConceptoIndex.value];
                form.concepto_arca = concepto.id;
                conceptoSearchInput.value = concepto.text;
                showConceptoDropdown.value = false;
                selectedConceptoIndex.value = -1;
            }
            break;
    }
};

// Función para reinicializar el tab-slider de Bootstrap
const reinitializeTabSlider = () => {
    nextTick(() => {
        const navTabs = document.querySelector('.nav-tabs');
        const activeTab = navTabs?.querySelector('.nav-link.active');
        const tabSlider = navTabs?.querySelector('.tab-slider');

        if (activeTab && tabSlider) {
            const left = activeTab.offsetLeft;
            const width = activeTab.offsetWidth;
            tabSlider.style.left = left + 'px';
            tabSlider.style.width = width + 'px';
        }
    });
};

onMounted(() => {
    // Inicializar con los valores de los props
    modoAgregar.value = props.agregar;
    modoEdicion.value = props.edicion;
    setFocus();

    // Cargar conceptos iniciales
    buscarConceptosArca('').then(() => {
        cargarConceptoSeleccionado();
    });
    
    // Reinicializar slider
    reinitializeTabSlider();
});

watch(() => props.agregar, () => {
    // Solo actualizar si no estamos en modo edición/agregar (navegación real)
    if (!modoEdicion.value && !modoAgregar.value) {
        modoAgregar.value = props.agregar;
        setFocus();
    }
});

watch(() => props.edicion, () => {
    // Solo actualizar si no estamos en modo edición/agregar (navegación real)
    if (!modoEdicion.value && !modoAgregar.value) {
        modoEdicion.value = props.edicion;
    }
});

// Watch para monitorear cambios en el tipo cuando se está agregando
watch(() => form.tipo, () => {
    if (modoAgregar.value) {
        buscarProximoCodigo();
    }
});

// Watch para validar el código cuando cambia
watch(() => form.codigo, () => {
    if (modoAgregar.value && form.codigo) {
        validarCodigoEnRango();
    }
});

// Función para pasar el foco del tipo al código
const moverFocoACodigoDesdeSelect = () => {
    setTimeout(() => {
        txtcodigo.value?.focus();
    }, 0);
};

</script>

<template>
    <form @submit.prevent="submit">
        <div class="container-xxl flex-grow-1 container-p-y">
            <!-- HEAD Y BOTONES -->
            <FormHeader
                :agregar="modoAgregar"
                :edicion="modoEdicion"
                :formId="form.id"
                :titulo="`Conceptos de Liquidación`"
                :rutaCreate="route('liquidacion.conceptos.create')"
                :rutaEdit="form.id ? route('liquidacion.conceptos.edit', form.id) : null"
                :rutaFirst="form.id ? route('liquidacion.conceptos.first') : null"
                :rutaPrevious="form.id ? route('liquidacion.conceptos.previous', form.id) : null"
                :rutaNext="form.id ? route('liquidacion.conceptos.next', form.id) : null"
                :rutaLast="form.id ? route('liquidacion.conceptos.last') : null"
                :rutaSearch="route('liquidacion.conceptos.search')"
                :rutaIndex="route('liquidacion.conceptos.index')"
                :onSubmit="submit"
                @edit="setFocus"
                @delete="() => {}"
                @cancelar="() => {}"
            />
            <!-- END HEAD Y BOTONES -->

            <div class="row">
                <div class="col">
                    <div class="card mb-6">
                        <div class="card-header overflow-hidden">
                            <ul class="nav nav-tabs" role="tablist">
                                <li class="nav-item">
                                    <button type="button" class="nav-link active" data-bs-toggle="tab" 
                                        data-bs-target="#form-tabs-info" role="tab">
                                        <span class="ri-information-line ri-20px d-sm-none"></span>
                                        <span class="d-none d-sm-block">Información General</span>
                                    </button>
                                </li>
                                <li class="nav-item">
                                    <button type="button" class="nav-link" data-bs-toggle="tab" 
                                        data-bs-target="#form-tabs-sicoss" role="tab">
                                        <span class="ri-settings-line ri-20px d-sm-none"></span>
                                        <span class="d-none d-sm-block">Sicoss</span>
                                    </button>
                                </li>
                                <li class="nav-item">
                                    <button type="button" class="nav-link" data-bs-toggle="tab" 
                                        data-bs-target="#form-tabs-config" role="tab">
                                        <span class="ri-settings-line ri-20px d-sm-none"></span>
                                        <span class="d-none d-sm-block">Configuración</span>
                                    </button>
                                </li>
                                <span class="tab-slider" style="bottom: 0px;"></span>
                            </ul>
                        </div>

                        <div class="tab-content">
                            <!-- TAB: Información General -->
                            <div class="tab-pane fade active show" id="form-tabs-info" role="tabpanel">
                                <!-- Mensaje de error si no hay códigos disponibles -->
                                <div v-if="errorCodigo" class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                                    <i class="ri-error-warning-line me-2"></i>
                                    {{ errorCodigo }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>

                                <div class="row g-6">
                                    <!-- Tipo -->
                                    <div class="col-md-3">
                                        <div class="form-floating form-floating-outline">
                                            <select
                                                id="tipo"
                                                name="tipo"
                                                class="form-control"
                                                v-model.number="form.tipo"
                                                v-bind:disabled="!modoEdicion"
                                                ref="txttipo"
                                                @change="moverFocoACodigoDesdeSelect"
                                                @keydown.enter="moverFocoACodigoDesdeSelect"
                                                required>
                                                <option value="" disabled>Seleccionar tipo</option>
                                                <option v-for="(label, key) in TIPOS" :key="key" :value="parseInt(key)">
                                                    {{ label }}
                                                </option>
                                            </select>
                                            <label for="tipo">Tipo *</label>
                                            <InputError class="mt-2" :message="form.errors.tipo" />
                                        </div>
                                    </div>

                                    <!-- Código -->
                                    <div class="col-md-3">
                                        <div class="form-floating form-floating-outline">
                                            <Input
                                                id="codigo"
                                                name="codigo"
                                                type="number"
                                                v-model.number="form.codigo"
                                                ref="txtcodigo"
                                                v-bind:disabled="!modoAgregar"
                                                autocomplete="off"
                                                placeholder="Código"
                                                :class="{ 'is-invalid': errorCodigo }"
                                                required
                                            />
                                            <label for="codigo">Código * 
                                                <span v-if="loading" class="spinner-border spinner-border-sm ms-2"></span>
                                            </label>
                                            <InputError class="mt-2" :message="form.errors.codigo || errorCodigo" />
                                        </div>
                                    </div>

                                    <!-- Activo -->
                                    <div class="col-md-3">
                                        <div class="form-check form-switch mt-4">
                                            <input
                                                type="checkbox"
                                                id="activo"
                                                name="activo"
                                                class="form-check-input"
                                                v-model="form.activo"
                                                v-bind:disabled="!modoEdicion"
                                            />
                                            <label class="form-check-label" for="activo">Activo</label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Detalle -->
                                <div class="row g-6 mt-2">
                                    <div class="col-md-12">
                                        <div class="form-floating form-floating-outline">
                                            <input
                                                id="detalle"
                                                name="detalle"
                                                ref="txtdetalle"
                                                class="form-control"
                                                placeholder="Descripción del concepto"
                                                autocomplete="off"
                                                v-bind:disabled="!modoEdicion"
                                                v-model="form.detalle"
                                                rows="2"
                                                maxlength="250"
                                                required
                                            >
                                            <label for="detalle">Descripción *</label>
                                            <InputError class="mt-2" :message="form.errors.detalle" />
                                        </div>
                                    </div>
                                </div>

                                <!-- Valores -->
                                <div class="row g-6 mt-2">
                                    <div class="col-md-4">
                                        <div class="form-floating form-floating-outline">
                                            <Input
                                                id="porcentaje"
                                                name="porcentaje"
                                                type="number"
                                                v-model.number="form.porcentaje"
                                                v-bind:disabled="!modoEdicion"
                                                placeholder="Porcentaje"
                                                step="0.01"
                                                min="0"
                                                max="100"
                                            />
                                            <label for="porcentaje">Porcentaje (%)</label>
                                            <InputError class="mt-2" :message="form.errors.porcentaje" />
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-floating form-floating-outline">
                                            <Input
                                                id="importe_fijo"
                                                name="importe_fijo"
                                                type="number"
                                                v-model.number="form.importe_fijo"
                                                v-bind:disabled="!modoEdicion"
                                                placeholder="Importe fijo"
                                                step="0.01"
                                                min="0"
                                            />
                                            <label for="importe_fijo">Importe Fijo ($)</label>
                                            <InputError class="mt-2" :message="form.errors.importe_fijo" />
                                        </div>
                                    </div>
                                </div>

                                <!-- Fórmula -->
                                <div class="row g-6 mt-2">
                                    <div class="col-md-12">
                                        <div class="form-floating form-floating-outline">
                                            <textarea
                                                id="formula"
                                                name="formula"
                                                class="form-control"
                                                placeholder="Fórmula de cálculo"
                                                v-bind:disabled="!modoEdicion"
                                                v-model="form.formula"
                                                rows="10"
                                                style="height: 101px;"
                                            ></textarea>
                                            <label for="formula">Fórmula de Cálculo</label>
                                            <InputError class="mt-2" :message="form.errors.formula" />
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6 mt-6">
                                    <div class="form-check form-switch">
                                        <input
                                            type="checkbox"
                                            id="imprime_recibo"
                                            name="imprime_recibo"
                                            class="form-check-input"
                                            v-model="form.imprime_recibo"
                                            v-bind:disabled="!modoEdicion"
                                        />
                                        <label class="form-check-label" for="imprime_recibo">Imprimir en recibo ?</label>
                                    </div>
                                </div>
                            </div>

                             <!-- TAB: Configuración -->
                            <div class="tab-pane fade" id="form-tabs-sicoss" role="tabpanel">
                                <div class="row g-6">
                                    <h6 class="mb-2">Sicoss</h6>
                                    
                                    <div class="col-md-6">
                                        <div class="form-check form-switch">
                                            <input
                                                type="checkbox"
                                                id="sicoss_afecta"
                                                name="sicoss_afecta"
                                                class="form-check-input"
                                                v-model="form.sicoss_afecta"
                                                v-bind:disabled="!modoEdicion"
                                            />
                                            <label class="form-check-label" for="sicoss_afecta">Afecta Sicoss</label>
                                        </div>
                                    </div>

                                    <div class="row"></div>
                                    
                                    <div class="col-md-6">
                                        <div class="form-check form-switch">
                                            <input
                                                type="checkbox"
                                                id="imponible"
                                                name="imponible"
                                                class="form-check-input"
                                                v-model="form.imponible"
                                                v-bind:disabled="!modoEdicion"
                                            />
                                            <label class="form-check-label" for="imponible">Afecta remuneración Imponible ?</label>
                                        </div>
                                    </div>

                                    <hr>
                                    <h6 class="mb-2">Libro de Sueldos Digital</h6>
                                    <div class="row"></div>

                                    <div class="col-md-6">
                                        <div class="form-floating form-floating-outline position-relative" v-if="modoEdicion">
                                            <input
                                                type="text"
                                                id="concepto_arca"
                                                class="form-control pe-5"
                                                v-model="conceptoSearchInput"
                                                @focus="() => { isFocused = true; showConceptoDropdown = true; buscarConceptosArca(''); }"
                                                @blur="() => { isFocused = false; cerrarDropdownConDelay(); }"
                                                @input="buscarConceptosArca(conceptoSearchInput)"
                                                @keydown="manejarNavegacionDropdown"
                                                placeholder=" "
                                                autocomplete="off"
                                                maxlength="6"
                                            />
                                            <!-- Botón limpiar -->
                                            <button
                                                v-if="conceptoSearchInput || form.concepto_arca"
                                                type="button"
                                                class="position-absolute end-0 top-50 translate-middle-y text-muted"
                                                @click="() => { conceptoSearchInput = ''; form.concepto_arca = null; }"
                                                style="z-index: 10; border: none; background: none; padding: 0.5rem; cursor: pointer;"
                                            >
                                                <i class="ri-close-line ri-20px"></i>
                                            </button>
                                            <label for="concepto_arca">Concepto Arca</label>
                                            
                                            <!-- Dropdown de resultados -->
                                            <div v-if="showConceptoDropdown && conceptosFiltrados.length > 0" class="position-absolute bg-white border border-secondary rounded mt-1 w-100 shadow-sm" style="z-index: 1000; max-height: 250px; overflow-y: auto; top: 100%;">
                                                <div
                                                    v-for="(concepto, index) in conceptosFiltrados"
                                                    :key="concepto.id"
                                                    class="px-3 py-2"
                                                    :class="{ 'bg-light': selectedConceptoIndex === index }"
                                                    @click="() => {
                                                        form.concepto_arca = concepto.id;
                                                        conceptoSearchInput = concepto.text;
                                                        showConceptoDropdown = false;
                                                        selectedConceptoIndex = -1;
                                                    }"
                                                    @mouseenter="selectedConceptoIndex = index"
                                                    @mouseleave="selectedConceptoIndex = -1"
                                                    style="cursor: pointer;"
                                                >
                                                    <div class="fw-bold">{{ concepto.id }}</div>
                                                    <div class="small text-muted">{{ concepto.text.split(' - ')[1] }}</div>
                                                </div>
                                            </div>
                                            <!-- Indicador de carga -->
                                            <div v-if="loadingConceptos" class="text-center py-2 text-muted">
                                                <small>Cargando...</small>
                                            </div>
                                            <InputError class="mt-2" :message="form.errors.concepto_arca" />
                                        </div>
                                        <!-- Modo visualización (no edición) -->
                                        <div v-else class="form-control bg-light text-muted" style="cursor: not-allowed;">
                                            <span v-if="form.concepto_arca">{{ form.concepto_arca }}</span>
                                            <span v-else class="text-muted">(vacío)</span>
                                        </div>
                                    </div>
                                    
                                    <div class="row"></div>
                                </div>

                                <!-- <div class="row g-6 mt-2">
                                    <div class="col-md-12">
                                        <div class="form-floating form-floating-outline">
                                            <textarea
                                                id="observaciones"
                                                name="observaciones"
                                                class="form-control"
                                                placeholder="Observaciones"
                                                v-bind:disabled="!edicion"
                                                v-model="form.observaciones"
                                                rows="3"
                                            ></textarea>
                                            <label for="observaciones">Observaciones</label>
                                            <InputError class="mt-2" :message="form.errors.observaciones" />
                                        </div>
                                    </div>
                                </div> -->
                            </div>

                            <!-- TAB: Configuración -->
                            <div class="tab-pane fade" id="form-tabs-config" role="tabpanel">
                                <div class="row g-6">
                                    <h6 class="mb-2">Configuraciones varias</h6>
                                    
                                    <div class="col-md-6">
                                        <div class="form-check form-switch">
                                            <input
                                                type="checkbox"
                                                id="afecta_sac"
                                                name="afecta_sac"
                                                class="form-check-input"
                                                v-model="form.afecta_sac"
                                                v-bind:disabled="!modoEdicion"
                                            />
                                            <label class="form-check-label" for="afecta_sac">Afecta SAC</label>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-check form-switch">
                                            <input
                                                type="checkbox"
                                                id="afecta_vacaciones"
                                                name="afecta_vacaciones"
                                                class="form-check-input"
                                                v-model="form.afecta_vacaciones"
                                                v-bind:disabled="!modoEdicion"
                                            />
                                            <label class="form-check-label" for="afecta_vacaciones">Afecta Vacaciones</label>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-check form-switch">
                                            <input
                                                type="checkbox"
                                                id="gcias_afecta"
                                                name="gcias_afecta"
                                                class="form-check-input"
                                                v-model="form.gcias_afecta"
                                                v-bind:disabled="!modoEdicion"
                                            />
                                            <label class="form-check-label" for="gcias_afecta">Afecta Ganancias</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-6 mt-4">
                                    <h6 class="mb-2">Información Contable</h6>

                                    <div class="col-md-6">
                                        <div class="form-floating form-floating-outline">
                                            <Input
                                                id="cuenta_contable"
                                                name="cuenta_contable"
                                                type="text"
                                                v-model="form.cuenta_contable"
                                                v-bind:disabled="!modoEdicion"
                                                placeholder="Cuenta contable"
                                                maxlength="50"
                                            />
                                            <label for="cuenta_contable">Cuenta Contable</label>
                                            <InputError class="mt-2" :message="form.errors.cuenta_contable" />
                                        </div>
                                    </div>

                                    <!-- <div class="col-md-6">
                                        <div class="form-floating form-floating-outline">
                                            <Input
                                                id="orden_impresion"
                                                name="orden_impresion"
                                                type="number"
                                                v-model.number="form.orden_impresion"
                                                v-bind:disabled="!edicion"
                                                placeholder="Orden"
                                                min="0"
                                            />
                                            <label for="orden_impresion">Orden de Impresión</label>
                                            <InputError class="mt-2" :message="form.errors.orden_impresion" />
                                        </div>
                                    </div> -->
                                </div>

                                <!-- <div class="row g-6 mt-2">
                                    <div class="col-md-12">
                                        <div class="form-floating form-floating-outline">
                                            <textarea
                                                id="observaciones"
                                                name="observaciones"
                                                class="form-control"
                                                placeholder="Observaciones"
                                                v-bind:disabled="!edicion"
                                                v-model="form.observaciones"
                                                rows="3"
                                            ></textarea>
                                            <label for="observaciones">Observaciones</label>
                                            <InputError class="mt-2" :message="form.errors.observaciones" />
                                        </div>
                                    </div>
                                </div> -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <a href="/liquidacion/conceptos/importar" type="button" class="btn btn-label-success waves-effect">
                <span class="tf-icons ri-file-excel-2-line ri-16px me-2"></span>Importar desde excel
            </a>
        </div>
    </form>

    <!-- MODAL DE CONFIRMACIÓN DE ELIMINACIÓN -->
    <div id="modalDelete" class="modal fade" data-bs-backdrop="static" tabindex="-1">
        <div class="modal-dialog">
            <form class="modal-content">
                <div class="modal-header">
                    <h4 class="onboarding-title text-body">¿Borrar registro?</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert d-flex align-items-center alert-warning mb-0" role="alert">
                        <span class="alert-icon rounded-4"><i class="ri-information-line ri-22px"></i></span>
                        <span>Esta seguro de eliminar este concepto? No podra recuperar el registro borrado...</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger waves-effect waves-light" 
                        style="color: white" @click="borrar">
                        Borrar
                    </button>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<style scoped>
    .form-control:focus {
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
    }
</style>
