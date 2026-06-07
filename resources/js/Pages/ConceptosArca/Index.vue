<script setup>
import InputError from '@/Components/InputError.vue';
import FormHeader from '@/Components/FormHeader.vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { ref, onMounted, watch } from 'vue';

const props = defineProps({
    legajo: {
        type: Object,
        default: () => ({}),
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

// Alícuotas (decimal 9,3) — se renderizan con v-for en la pestaña "Alícuotas"
const tasas = [
    { key: 'marca_repetible', label: 'Marca repetible' },
    { key: 'aportes_sipa', label: 'Aportes SIPA' },
    { key: 'contribuciones_sipa', label: 'Contribuciones SIPA' },
    { key: 'aportes_inssjyp', label: 'Aportes INSSJYP' },
    { key: 'contribuciones_inssjyp', label: 'Contribuciones INSSJYP' },
    { key: 'aportes_obra_social', label: 'Aportes Obra Social' },
    { key: 'contribuciones_obra_social', label: 'Contribuciones Obra Social' },
    { key: 'aportes_fsr', label: 'Aportes FSR' },
    { key: 'contribuciones_fsr', label: 'Contribuciones FSR' },
    { key: 'aportes_renatea', label: 'Aportes RENATEA' },
    { key: 'contribuciones_renatea', label: 'Contribuciones RENATEA' },
    { key: 'contribuciones_aaff', label: 'Contribuciones AAFF' },
    { key: 'contribuciones_fne', label: 'Contribuciones FNE' },
    { key: 'contribuciones_lrt', label: 'Contribuciones LRT' },
    { key: 'aportes_diferenciales', label: 'Aportes Diferenciales' },
    { key: 'aportes_especiales', label: 'Aportes Especiales' },
];

const form = useForm({
    id: props.legajo?.id ?? null,
    codigo_contribuyente: props.legajo?.codigo_contribuyente ?? '',
    descripcion_contribuyente: props.legajo?.descripcion_contribuyente ?? '',
    codigo_afip: props.legajo?.codigo_afip ?? '',
    descripcion: props.legajo?.descripcion ?? '',
    marca_repetible: props.legajo?.marca_repetible ?? '',
    aportes_sipa: props.legajo?.aportes_sipa ?? '',
    contribuciones_sipa: props.legajo?.contribuciones_sipa ?? '',
    aportes_inssjyp: props.legajo?.aportes_inssjyp ?? '',
    contribuciones_inssjyp: props.legajo?.contribuciones_inssjyp ?? '',
    aportes_obra_social: props.legajo?.aportes_obra_social ?? '',
    contribuciones_obra_social: props.legajo?.contribuciones_obra_social ?? '',
    aportes_fsr: props.legajo?.aportes_fsr ?? '',
    contribuciones_fsr: props.legajo?.contribuciones_fsr ?? '',
    aportes_renatea: props.legajo?.aportes_renatea ?? '',
    contribuciones_renatea: props.legajo?.contribuciones_renatea ?? '',
    contribuciones_aaff: props.legajo?.contribuciones_aaff ?? '',
    contribuciones_fne: props.legajo?.contribuciones_fne ?? '',
    contribuciones_lrt: props.legajo?.contribuciones_lrt ?? '',
    aportes_diferenciales: props.legajo?.aportes_diferenciales ?? '',
    aportes_especiales: props.legajo?.aportes_especiales ?? '',
});

const submit = () => {
    if (usePage().props.agregar) {
        form.post(route('arca.conceptos.store'), { preserveScroll: true });
    } else {
        form.patch(route('arca.conceptos.update', form.id), { preserveScroll: true });
    }
};

const borrar = () => {
    const id = props.legajo?.id;
    if (!id) return;

    router.delete(route('arca.conceptos.destroy', id), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
};

const closeModal = () => {
    document.querySelector('.modal-backdrop')?.remove();
};

const txtcodigo = ref(null);
const txtdescripcion = ref(null);

const setFocus = () => {
    if (props.agregar) {
        txtcodigo.value?.focus();
    } else {
        txtdescripcion.value?.focus();
    }
};

onMounted(() => setFocus());
watch(() => props.agregar, () => setFocus());
</script>

<template>
    <form @submit.prevent="submit">
        <div class="container-xxl flex-grow-1 container-p-y">
            <div class="app-ecommerce">
                <!-- HEAD Y BOTONES -->
                <FormHeader
                    :agregar="agregar"
                    :edicion="edicion"
                    :form-id="form.id"
                    titulo="Conceptos ARCA"
                    :ruta-create="route('arca.conceptos.create')"
                    :ruta-edit="form.id ? route('arca.conceptos.edit', form.id) : null"
                    :ruta-first="route('arca.conceptos.first')"
                    :ruta-previous="form.id ? route('arca.conceptos.previous', form.id) : null"
                    :ruta-next="form.id ? route('arca.conceptos.next', form.id) : null"
                    :ruta-last="route('arca.conceptos.last')"
                    :ruta-search="route('arca.conceptos.search')"
                    :ruta-index="route('arca.conceptos.index')"
                    :on-submit="submit"
                    @delete="() => {}"
                />
                <!-- END HEAD Y BOTONES -->

                <div class="row">
                    <div class="col">
                        <div class="card mb-6">
                            <div class="card-header overflow-hidden">
                                <ul class="nav nav-tabs" role="tablist">
                                    <li class="nav-item">
                                        <button
                                            type="button"
                                            class="nav-link active"
                                            data-bs-toggle="tab"
                                            data-bs-target="#tab-concepto"
                                            role="tab"
                                            aria-selected="true">
                                            <span class="ri-price-tag-3-line ri-20px d-sm-none"></span>
                                            <span class="d-none d-sm-block">Datos del concepto</span>
                                        </button>
                                    </li>
                                    <li class="nav-item">
                                        <button
                                            type="button"
                                            class="nav-link"
                                            data-bs-toggle="tab"
                                            data-bs-target="#tab-tasas"
                                            role="tab"
                                            aria-selected="false">
                                            <span class="ri-percent-line ri-20px d-sm-none"></span>
                                            <span class="d-none d-sm-block">Alícuotas</span>
                                        </button>
                                    </li>
                                </ul>
                            </div>

                            <div class="tab-content">
                                <!-- TAB: Datos del concepto -->
                                <div class="tab-pane fade active show" id="tab-concepto" role="tabpanel">
                                    <div class="row g-6">
                                        <div class="col-md-3">
                                            <div class="form-floating form-floating-outline">
                                                <input
                                                    type="number"
                                                    id="codigo_contribuyente"
                                                    name="codigo_contribuyente"
                                                    ref="txtcodigo"
                                                    class="form-control"
                                                    placeholder="Código contribuyente"
                                                    autocomplete="off"
                                                    :disabled="!agregar"
                                                    v-model="form.codigo_contribuyente" />
                                                <label for="codigo_contribuyente">Código contribuyente *</label>
                                                <InputError class="mt-2" :message="form.errors.codigo_contribuyente" />
                                            </div>
                                        </div>

                                        <div class="col-md-9">
                                            <div class="form-floating form-floating-outline">
                                                <input
                                                    type="text"
                                                    id="descripcion_contribuyente"
                                                    name="descripcion_contribuyente"
                                                    ref="txtdescripcion"
                                                    class="form-control"
                                                    placeholder="Descripción del contribuyente"
                                                    autocomplete="off"
                                                    :disabled="!edicion"
                                                    v-model="form.descripcion_contribuyente"
                                                    maxlength="80" />
                                                <label for="descripcion_contribuyente">Descripción contribuyente</label>
                                                <InputError class="mt-2" :message="form.errors.descripcion_contribuyente" />
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="form-floating form-floating-outline">
                                                <input
                                                    type="number"
                                                    id="codigo_afip"
                                                    name="codigo_afip"
                                                    class="form-control"
                                                    placeholder="Código ARCA"
                                                    autocomplete="off"
                                                    :disabled="!edicion"
                                                    v-model="form.codigo_afip" />
                                                <label for="codigo_afip">Código ARCA</label>
                                                <InputError class="mt-2" :message="form.errors.codigo_afip" />
                                            </div>
                                        </div>

                                        <div class="col-md-9">
                                            <div class="form-floating form-floating-outline">
                                                <input
                                                    type="text"
                                                    id="descripcion"
                                                    name="descripcion"
                                                    class="form-control"
                                                    placeholder="Descripción AFIP"
                                                    autocomplete="off"
                                                    :disabled="!edicion"
                                                    v-model="form.descripcion"
                                                    maxlength="80" />
                                                <label for="descripcion">Descripción AFIP</label>
                                                <InputError class="mt-2" :message="form.errors.descripcion" />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- TAB: Alícuotas -->
                                <div class="tab-pane fade" id="tab-tasas" role="tabpanel">
                                    <div class="row g-6">
                                        <div class="col-md-3" v-for="t in tasas" :key="t.key">
                                            <div class="form-floating form-floating-outline">
                                                <input
                                                    type="number"
                                                    step="0.001"
                                                    :id="t.key"
                                                    :name="t.key"
                                                    class="form-control"
                                                    :placeholder="t.label"
                                                    autocomplete="off"
                                                    :disabled="!edicion"
                                                    v-model="form[t.key]" />
                                                <label :for="t.key">{{ t.label }}</label>
                                                <InputError class="mt-2" :message="form.errors[t.key]" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Botón importar desde excel -->
                        <a :href="route('arca.importar')" type="button" class="btn btn-label-success waves-effect">
                            <span class="tf-icons ri-file-excel-2-line ri-16px me-2"></span>
                            Importar
                        </a>

                        <br><br>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- MODAL BORRAR -->
    <div class="col-lg-4 col-md-3">
        <div class="mt-4">
            <div id="modalDelete" class="modal fade" data-bs-backdrop="static" tabindex="-1">
                <div class="modal-dialog">
                    <form class="modal-content">
                        <div class="modal-header">
                            <h4 class="onboarding-title text-body">Borrar registro ?</h4>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col mb-6 mt-1">
                                    <div class="alert d-flex align-items-center alert-warning mb-0 h6" role="alert">
                                        <span class="alert-icon rounded-4"><i class="ri-information-line ri-22px"></i></span>
                                        <span><br>Esta seguro de eliminar el registro seleccionado? <br> No podra recuperar el registro borrado...<br><br></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-danger waves-effect waves-light" style="color: white" @click="borrar">Borrar</button>
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>
