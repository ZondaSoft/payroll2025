<script setup>
import TextInput from '@/Components/TextInput.vue';
import Input from '@/Components/Input.vue';
import InputError from '@/Components/InputError.vue';
import { router, Link, useForm, usePage } from '@inertiajs/vue3';
import { reactive, ref, onMounted, watch } from 'vue';

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
    if (props.agregar && errorCodigo.value) {
        alert('No se puede guardar: ' + errorCodigo.value);
        return;
    }

    let ruta = 'liquidacion.conceptos.update';

    if (usePage().props.agregar) {
        ruta = 'liquidacion.conceptos.store';
        form.post(route(ruta), {
            preserveScroll: true,
        });
    } else {
        form.patch(route(ruta, form.id), {
            preserveScroll: true,
        });
    }
};

const borrar = () => {
    const id = props.concepto?.id
    if (!id) return

    router.delete(route('liquidacion.conceptos.destroy', id), {
        preserveScroll: true,
    })
};

const closeModal = () => {
    const elementToRemove = document.querySelector('.modal-backdrop');
    if (elementToRemove) {
        elementToRemove.remove();
    }
};

const txttipo = ref(null);
const txtcodigo = ref(null);
const txtdetalle = ref(null);

const errorCodigo = ref('');
const loading = ref(false);

const setFocus = () => {
    if (props.agregar) {
        txttipo.value?.focus();
    } else {
        txtdetalle.value?.focus();
    }
};

// Buscar próximo código disponible cuando cambia el tipo
const buscarProximoCodigo = async () => {
    if (!props.agregar || !form.tipo) return;

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
    if (!props.agregar || !form.tipo || !form.codigo) {
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

onMounted(() => {
    setFocus();
});

watch(() => props.agregar, () => {
    setFocus();
});

// Watch para monitorear cambios en el tipo cuando se está agregando
watch(() => form.tipo, () => {
    if (props.agregar) {
        buscarProximoCodigo();
    }
});

// Watch para validar el código cuando cambia
watch(() => form.codigo, () => {
    if (props.agregar && form.codigo) {
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
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-6 row-gap-4">
                <!-- Sección de agregar -->
                <div v-if="agregar" class="d-flex flex-column justify-content-center">
                    <h4 class="mb-1">Agregar Concepto de Liquidación</h4>
                </div>
                <!-- Sección de edición -->
                <div v-if="!agregar && edicion" class="d-flex flex-column justify-content-center">
                    <h4 class="mb-1">Modificar Concepto de Liquidación</h4>
                </div>
                <!-- Si ni agregar ni edicion están activados -->
                <div v-if="!agregar && !edicion" class="d-flex flex-column justify-content-center">
                    <h4 class="mb-1">Conceptos de Liquidación</h4>
                </div>

                <!-- Botones de navegación -->
                <div class="d-flex flex-column justify-content-center" v-if="!agregar && !edicion && form.id">
                    <div class="btn-group" role="group">
                        <Link
                            :href="route('liquidacion.conceptos.first')"
                            class="btn btn-outline-secondary waves-effect"
                            data-bs-toggle="tooltip"
                            data-bs-placement="top"
                            data-bs-original-title="ir al primer registro">
                            <i class="ri-arrow-left-double-line"></i>
                        </Link>
                        <Link
                            :href="route('liquidacion.conceptos.previous', form.id)"
                            class="btn btn-outline-secondary waves-effect"
                            data-bs-toggle="tooltip"
                            data-bs-placement="top"
                            data-bs-original-title="ir al registro anterior">
                            <i class="ri-arrow-left-line"></i>
                        </Link>
                        <Link
                            :href="route('liquidacion.conceptos.next', form.id)"
                            class="btn btn-outline-secondary waves-effect"
                            data-bs-toggle="tooltip"
                            data-bs-placement="top"
                            data-bs-original-title="ir al registro siguiente">
                            <i class="ri-arrow-right-line"></i>
                        </Link>
                        <Link
                            :href="route('liquidacion.conceptos.last')"
                            class="btn btn-outline-secondary waves-effect"
                            data-bs-toggle="tooltip"
                            data-bs-placement="top"
                            data-bs-original-title="ir al ultimo registro">
                            <i class="ri-arrow-right-double-fill"></i>
                        </Link>
                        <a href="/liquidacion/conceptos/search" class="btn btn-outline-secondary waves-effect" 
                            data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Buscar">
                            <i class="ri-search-line"></i>
                        </a>
                    </div>
                </div>

                <!-- Botones de agregar/Grabar -->
                <div v-if="agregar || edicion" class="d-flex align-content-center flex-wrap gap-4">
                    <button type="submit" class="btn btn-primary">Grabar</button>
                    <Link
                        :href="route('liquidacion.conceptos.index')"
                        class="btn btn-outline-secondary">
                        Cancelar
                    </Link>
                </div>
                <!-- Botones de CRUD -->
                <div v-else class="d-flex align-content-center flex-wrap gap-4">
                    <Link
                        :href="route('liquidacion.conceptos.create')"
                        class="btn btn-info waves-effect waves-light">
                        Agregar
                    </Link>
                    <Link
                        v-if="props.concepto?.id"
                        :href="route('liquidacion.conceptos.edit', props.concepto.id)"
                        class="btn btn-outline-secondary"
                        @click="setFocus">
                        Modificar
                    </Link>
                    <a type="button" 
                        class="btn btn-danger waves-effect waves-light" 
                        style="color: white"
                        data-bs-toggle="modal" 
                        data-bs-target="#modalDelete">
                        Borrar
                    </a>
                </div>
            </div>
            <!-- END HEAD Y BOTONES -->

            <div class="row">
                <div class="col">
                    <div class="card mb-6">
                        <div class="card-header overflow-hidden">
                            <ul class="nav nav-tabs" role="tablist">
                                <li class="nav-item">
                                    <button class="nav-link active" data-bs-toggle="tab" 
                                        data-bs-target="#form-tabs-info" role="tab">
                                        <span class="ri-information-line ri-20px d-sm-none"></span>
                                        <span class="d-none d-sm-block">Información General</span>
                                    </button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link" data-bs-toggle="tab" 
                                        data-bs-target="#form-tabs-config" role="tab">
                                        <span class="ri-settings-line ri-20px d-sm-none"></span>
                                        <span class="d-none d-sm-block">Configuración</span>
                                    </button>
                                </li>
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
                                                v-bind:disabled="!edicion"
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
                                                v-bind:disabled="!agregar"
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
                                                v-bind:disabled="!edicion"
                                            />
                                            <label class="form-check-label" for="activo">Activo</label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Detalle -->
                                <div class="row g-6 mt-2">
                                    <div class="col-md-12">
                                        <div class="form-floating form-floating-outline">
                                            <textarea
                                                id="detalle"
                                                name="detalle"
                                                ref="txtdetalle"
                                                class="form-control"
                                                placeholder="Descripción del concepto"
                                                autocomplete="off"
                                                v-bind:disabled="!edicion"
                                                v-model="form.detalle"
                                                rows="2"
                                                maxlength="250"
                                                required
                                            ></textarea>
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
                                                v-bind:disabled="!edicion"
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
                                                v-bind:disabled="!edicion"
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
                                                v-bind:disabled="!edicion"
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
                                            v-bind:disabled="!edicion"
                                        />
                                        <label class="form-check-label" for="imprime_recibo">Imprimir en recibo ?</label>
                                    </div>
                                </div>
                            </div>

                            <!-- TAB: Configuración -->
                            <div class="tab-pane fade" id="form-tabs-config" role="tabpanel">
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
                                                v-bind:disabled="!edicion"
                                            />
                                            <label class="form-check-label" for="sicoss_afecta">Afecta Sicoss</label>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="form-check form-switch">
                                            <input
                                                type="checkbox"
                                                id="imponible"
                                                name="imponible"
                                                class="form-check-input"
                                                v-model="form.imponible"
                                                v-bind:disabled="!edicion"
                                            />
                                            <label class="form-check-label" for="imponible">Afecta remuneración Imponible ?</label>
                                        </div>
                                    </div>

                                    <hr>

                                    <div class="col-md-6">
                                        <div class="form-check form-switch">
                                            <input
                                                type="checkbox"
                                                id="afecta_sac"
                                                name="afecta_sac"
                                                class="form-check-input"
                                                v-model="form.afecta_sac"
                                                v-bind:disabled="!edicion"
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
                                                v-bind:disabled="!edicion"
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
                                                v-bind:disabled="!edicion"
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
                                                v-bind:disabled="!edicion"
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
