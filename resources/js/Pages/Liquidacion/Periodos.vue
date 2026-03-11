<script setup>
import FormHeader from '@/Components/FormHeader.vue';
import InputError from '@/Components/InputError.vue';
import { router, Link, useForm } from '@inertiajs/vue3';
import { ref, onMounted, watch } from 'vue';

const props = defineProps({
    periodo: {
        type: Object,
        default: () => ({
            id: null,
            periodo: '',
            tipoliq: 1,
            fecha: '',
            fecha_pago: '',
            banco: '',
            fecha_aportes: '',
            periodo_aporte: '',
            comentarios: '',
            estado: '',
        }),
    },
    agregar: {
        type: Boolean,
        required: true,
    },
    edicion: {
        type: Boolean,
        required: true,
    },
});

const modoEdicion = ref(props.edicion);
const modoAgregar = ref(props.agregar);

const TIPOS = {
    1: 'Mensual',
    2: 'SAC 1er semestre',
    3: 'SAC 2do semestre',
    4: 'Vacaciones',
    5: 'Otro',
};

const form = useForm({
    id:             props.periodo?.id ?? null,
    periodo:        props.periodo?.periodo ?? '',
    tipoliq:        props.periodo?.tipoliq ?? 1,
    fecha:          props.periodo?.fecha ? props.periodo.fecha.substring(0, 10) : '',
    fecha_pago:     props.periodo?.fecha_pago ? props.periodo.fecha_pago.substring(0, 10) : '',
    banco:          props.periodo?.banco ?? '',
    fecha_aportes:  props.periodo?.fecha_aportes ? props.periodo.fecha_aportes.substring(0, 10) : '',
    periodo_aporte: props.periodo?.periodo_aporte ?? '',
    comentarios:    props.periodo?.comentarios ?? '',
    estado:         props.periodo?.estado ?? '',
});

const txtperiodo = ref(null);

const setFocus = () => {
    txtperiodo.value?.focus();
};

const submit = () => {
    if (modoAgregar.value) {
        form.post(route('liquidacion.periodos.store'), {
            preserveScroll: true,
            onSuccess: () => {
                router.get(route('liquidacion.periodos.last'));
            },
        });
    } else {
        form.patch(route('liquidacion.periodos.update', form.id), {
            preserveScroll: true,
            onSuccess: () => {
                modoAgregar.value = false;
                modoEdicion.value = false;
            },
        });
    }
};

const borrar = () => {
    const id = props.periodo?.id;
    if (!id) return;

    router.delete(route('liquidacion.periodos.destroy', id), {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            router.get(route('liquidacion.periodos.last'));
        },
    });
};

const closeModal = () => {
    const modalElement = document.getElementById('modalDelete');
    if (modalElement) {
        const modal = window.bootstrap.Modal.getInstance(modalElement);
        if (modal) modal.hide();
    }
    setTimeout(() => {
        document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
    }, 300);
};

onMounted(() => {
    modoAgregar.value = props.agregar;
    modoEdicion.value = props.edicion;
    setFocus();
});

watch(() => props.agregar, () => {
    if (!modoEdicion.value && !modoAgregar.value) {
        modoAgregar.value = props.agregar;
        setFocus();
    }
});

watch(() => props.edicion, () => {
    if (!modoEdicion.value && !modoAgregar.value) {
        modoEdicion.value = props.edicion;
    }
});

watch(() => props.periodo, (nuevo) => {
    if (!nuevo) return;
    form.id             = nuevo.id ?? null;
    form.periodo        = nuevo.periodo ?? '';
    form.tipoliq        = nuevo.tipoliq ?? 1;
    form.fecha          = nuevo.fecha ? nuevo.fecha.substring(0, 10) : '';
    form.fecha_pago     = nuevo.fecha_pago ? nuevo.fecha_pago.substring(0, 10) : '';
    form.banco          = nuevo.banco ?? '';
    form.fecha_aportes  = nuevo.fecha_aportes ? nuevo.fecha_aportes.substring(0, 10) : '';
    form.periodo_aporte = nuevo.periodo_aporte ?? '';
    form.comentarios    = nuevo.comentarios ?? '';
    form.estado         = nuevo.estado ?? '';
}, { deep: true });
</script>

<template>
    <form @submit.prevent="submit">
        <div class="container-xxl flex-grow-1 container-p-y">

            <!-- HEAD Y BOTONES -->
            <FormHeader
                :agregar="modoAgregar"
                :edicion="modoEdicion"
                :formId="form.id"
                titulo="Período de Liquidación"
                :rutaCreate="route('liquidacion.periodos.create')"
                :rutaEdit="form.id ? route('liquidacion.periodos.edit', form.id) : null"
                :rutaFirst="form.id ? route('liquidacion.periodos.first') : null"
                :rutaPrevious="form.id ? route('liquidacion.periodos.previous', form.id) : null"
                :rutaNext="form.id ? route('liquidacion.periodos.next', form.id) : null"
                :rutaLast="form.id ? route('liquidacion.periodos.last') : null"
                :rutaSearch="route('liquidacion.periodos.search')"
                :rutaIndex="route('liquidacion.periodos.index')"
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
                                        data-bs-target="#tab-general" role="tab">
                                        <span class="d-none d-sm-block">Información General</span>
                                    </button>
                                </li>
                                <li class="nav-item">
                                    <button type="button" class="nav-link" data-bs-toggle="tab"
                                        data-bs-target="#tab-aportes" role="tab">
                                        <span class="d-none d-sm-block">Aportes</span>
                                    </button>
                                </li>
                            </ul>
                        </div>

                        <div class="card-body">
                            <div class="tab-content pt-2">

                                <!-- TAB INFORMACIÓN GENERAL -->
                                <div class="tab-pane fade show active" id="tab-general" role="tabpanel">
                                    <div class="row g-4 mt-2">

                                        <!-- Período -->
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">Período <span class="text-danger">*</span></label>
                                            <input
                                                ref="txtperiodo"
                                                v-model="form.periodo"
                                                type="text"
                                                class="form-control"
                                                placeholder="YYYYMM"
                                                maxlength="6"
                                                :disabled="!modoEdicion && !modoAgregar"
                                                :class="{ 'is-invalid': form.errors.periodo }"
                                            />
                                            <InputError :message="form.errors.periodo" />
                                            <small class="text-muted">Formato: YYYYMM (ej: 202603)</small>
                                        </div>

                                        <!-- Tipo de liquidación -->
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">Tipo de liquidación <span class="text-danger">*</span></label>
                                            <select
                                                v-model="form.tipoliq"
                                                class="form-select"
                                                :disabled="!modoEdicion && !modoAgregar"
                                                :class="{ 'is-invalid': form.errors.tipoliq }"
                                            >
                                                <option v-for="(label, value) in TIPOS" :key="value" :value="parseInt(value)">
                                                    {{ label }}
                                                </option>
                                            </select>
                                            <InputError :message="form.errors.tipoliq" />
                                        </div>

                                        <!-- Estado -->
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">Estado</label>
                                            <input
                                                v-model="form.estado"
                                                type="text"
                                                class="form-control"
                                                maxlength="20"
                                                :disabled="!modoEdicion && !modoAgregar"
                                                :class="{ 'is-invalid': form.errors.estado }"
                                            />
                                            <InputError :message="form.errors.estado" />
                                        </div>

                                    </div>

                                    <div class="row g-4 mt-1">

                                        <!-- Fecha -->
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">Fecha <span class="text-danger">*</span></label>
                                            <input
                                                v-model="form.fecha"
                                                type="date"
                                                class="form-control"
                                                :readonly="!modoEdicion && !modoAgregar"
                                                v-bind:disabled="!modoEdicion && !modoAgregar"
                                                :class="{ 'is-invalid': form.errors.fecha }"
                                            />
                                            <InputError :message="form.errors.fecha" />
                                        </div>

                                        <!-- Fecha de pago -->
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">Fecha de pago</label>
                                            <input
                                                v-model="form.fecha_pago"
                                                type="date"
                                                class="form-control"
                                                :disabled="!modoEdicion && !modoAgregar"
                                                :class="{ 'is-invalid': form.errors.fecha_pago }"
                                            />
                                            <InputError :message="form.errors.fecha_pago" />
                                        </div>

                                        <!-- Banco -->
                                        <div class="col-md-2">
                                            <label class="form-label fw-semibold">Banco</label>
                                            <input
                                                v-model="form.banco"
                                                type="text"
                                                class="form-control"
                                                maxlength="5"
                                                :disabled="!modoEdicion && !modoAgregar"
                                                :class="{ 'is-invalid': form.errors.banco }"
                                            />
                                            <InputError :message="form.errors.banco" />
                                        </div>

                                    </div>

                                    <div class="row g-4 mt-1">
                                        <!-- Comentarios -->
                                        <div class="col-md-9">
                                            <label class="form-label fw-semibold">Comentarios</label>
                                            <textarea
                                                v-model="form.comentarios"
                                                class="form-control"
                                                rows="3"
                                                maxlength="255"
                                                :disabled="!modoEdicion && !modoAgregar"
                                                :class="{ 'is-invalid': form.errors.comentarios }"
                                            ></textarea>
                                            <InputError :message="form.errors.comentarios" />
                                        </div>
                                    </div>
                                </div>
                                <!-- END TAB INFORMACIÓN GENERAL -->

                                <!-- TAB APORTES -->
                                <div class="tab-pane fade" id="tab-aportes" role="tabpanel">
                                    <div class="row g-4 mt-2">

                                        <!-- Fecha de aportes -->
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">Fecha de aportes</label>
                                            <input
                                                v-model="form.fecha_aportes"
                                                type="date"
                                                class="form-control"
                                                :disabled="!modoEdicion && !modoAgregar"
                                                :class="{ 'is-invalid': form.errors.fecha_aportes }"
                                            />
                                            <InputError :message="form.errors.fecha_aportes" />
                                        </div>

                                        <!-- Período de aportes -->
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">Período de aportes</label>
                                            <input
                                                v-model="form.periodo_aporte"
                                                type="text"
                                                class="form-control"
                                                placeholder="YYYYMM"
                                                maxlength="6"
                                                :disabled="!modoEdicion && !modoAgregar"
                                                :class="{ 'is-invalid': form.errors.periodo_aporte }"
                                            />
                                            <InputError :message="form.errors.periodo_aporte" />
                                        </div>

                                    </div>
                                </div>
                                <!-- END TAB APORTES -->

                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Modal confirmación borrar -->
        <div class="modal fade" id="modalDelete" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirmar eliminación</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        ¿Está seguro que desea eliminar el período
                        <strong>{{ form.periodo }}</strong>?
                        Esta acción no se puede deshacer.
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-danger" @click="borrar">Eliminar</button>
                    </div>
                </div>
            </div>
        </div>

    </form>
</template>
