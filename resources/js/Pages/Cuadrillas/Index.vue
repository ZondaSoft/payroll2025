<script setup>
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import FormHeader from '@/Components/FormHeader.vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { ref, onMounted, watch } from 'vue';

const props = defineProps({
    registro: { type: Object, default: () => ({ id: null, codigo: '', detalle: '' }) },
    agregar: { type: Boolean, required: true },
    edicion: { type: Boolean, required: true },
    empresa: { type: Object, required: true },
});

const form = useForm({
    id:        props.registro?.id ?? null,
    codigo:    props.registro?.codigo ?? '',
    detalle:   props.registro?.detalle ?? '',
    encargado: props.registro?.encargado ?? null,
});

const submit = () => {
    if (usePage().props.agregar) {
        form.post(route('cuadrillas.store'), { preserveScroll: true });
    } else {
        form.patch(route('cuadrillas.update', form.id), { preserveScroll: true });
    }
};

const borrar = () => {
    const id = props.registro?.id;
    if (!id) return;
    router.delete(route('cuadrillas.destroy', id), { preserveScroll: true, onSuccess: () => closeModal() });
};

const closeModal = () => { const e = document.querySelector('.modal-backdrop'); if (e) e.remove(); };

const txtcodigo = ref(null);
const txtdetalle = ref(null);
const setFocus = () => { if (props.agregar) txtcodigo.value?.focus(); else txtdetalle.value?.focus(); };
onMounted(() => setFocus());
watch(() => props.agregar, () => setFocus());
</script>

<template>
    <form @submit.prevent="submit">
        <div class="container-xxl flex-grow-1 container-p-y">
            <div class="app-ecommerce">
                <FormHeader
                    :agregar="agregar"
                    :edicion="edicion"
                    :form-id="form.id"
                    titulo="Cuadrilla"
                    :ruta-create="route('cuadrillas.create')"
                    :ruta-edit="form.id ? route('cuadrillas.edit', form.id) : null"
                    :ruta-first="route('cuadrillas.first')"
                    :ruta-previous="form.id ? route('cuadrillas.previous', form.id) : null"
                    :ruta-next="form.id ? route('cuadrillas.next', form.id) : null"
                    :ruta-last="route('cuadrillas.last')"
                    :ruta-search="route('cuadrillas.search')"
                    :ruta-index="route('cuadrillas.index')"
                    :on-submit="submit"
                />

                <div class="row">
                    <div class="col">
                        <div class="card mb-6">
                            <div class="card-header overflow-hidden">
                                <ul class="nav nav-tabs" role="tablist">
                                    <li class="nav-item">
                                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-main" role="tab" aria-selected="true">
                                            <span class="d-none d-sm-block">Información Principal</span>
                                        </button>
                                    </li>
                                </ul>
                            </div>
                            <div class="tab-content">
                                <div class="tab-pane fade active show" id="tab-main" role="tabpanel">
                                    <div class="row g-6">
                                        <div class="col-md-2">
                                            <div class="form-floating form-floating-outline">
                                                <TextInput id="codigo" name="codigo" type="text" v-model="form.codigo"
                                                    ref="txtcodigo" :disabled="!agregar" autocomplete="off" placeholder="Código" maxlength="4" />
                                                <label for="codigo">Código *</label>
                                                <InputError class="mt-2" :message="form.errors.codigo" />
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-floating form-floating-outline">
                                                <input type="text" id="detalle" ref="txtdetalle" class="form-control"
                                                    placeholder="Detalle" autocomplete="off" :disabled="!edicion" maxlength="35" v-model="form.detalle" />
                                                <label for="detalle">Detalle *</label>
                                                <InputError class="mt-2" :message="form.errors.detalle" />
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-floating form-floating-outline">
                                                <input type="number" min="0" id="encargado" class="form-control"
                                                    placeholder="Legajo del encargado" :disabled="!edicion" v-model.number="form.encargado" />
                                                <label for="encargado">Encargado (legajo)</label>
                                                <InputError class="mt-2" :message="form.errors.encargado" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

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
                            <div class="alert d-flex align-items-center alert-warning mb-0 h6" role="alert">
                                <span class="alert-icon rounded-4"><i class="ri-information-line ri-22px"></i></span>
                                <span><br>Esta seguro de eliminar el registro seleccionado? <br> No podra recuperar el registro borrado... <br><br></span>
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
