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
    localidad: props.registro?.localidad ?? '',
    cp:        props.registro?.cp ?? '',
    tel1:      props.registro?.tel1 ?? '',
    tel2:      props.registro?.tel2 ?? '',
    tel3:      props.registro?.tel3 ?? '',
    email:     props.registro?.email ?? '',
    web:       props.registro?.web ?? '',
    contacto:  props.registro?.contacto ?? '',
    porce_con: props.registro?.porce_con ?? null,
    porce_apo: props.registro?.porce_apo ?? null,
    fijo_apo:  props.registro?.fijo_apo ?? null,
    fijo_con:  props.registro?.fijo_con ?? null,
});

const submit = () => {
    if (usePage().props.agregar) {
        form.post(route('sindicatos.store'), { preserveScroll: true });
    } else {
        form.patch(route('sindicatos.update', form.id), { preserveScroll: true });
    }
};

const borrar = () => {
    const id = props.registro?.id;
    if (!id) return;
    router.delete(route('sindicatos.destroy', id), { preserveScroll: true, onSuccess: () => closeModal() });
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
                    titulo="Sindicato"
                    :ruta-create="route('sindicatos.create')"
                    :ruta-edit="form.id ? route('sindicatos.edit', form.id) : null"
                    :ruta-first="route('sindicatos.first')"
                    :ruta-previous="form.id ? route('sindicatos.previous', form.id) : null"
                    :ruta-next="form.id ? route('sindicatos.next', form.id) : null"
                    :ruta-last="route('sindicatos.last')"
                    :ruta-search="route('sindicatos.search')"
                    :ruta-index="route('sindicatos.index')"
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
                                                    ref="txtcodigo" :disabled="!agregar" autocomplete="off" placeholder="Código" maxlength="2" />
                                                <label for="codigo">Código *</label>
                                                <InputError class="mt-2" :message="form.errors.codigo" />
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-floating form-floating-outline">
                                                <input type="text" id="detalle" ref="txtdetalle" class="form-control"
                                                    placeholder="Detalle" autocomplete="off" :disabled="!edicion" maxlength="30" v-model="form.detalle" />
                                                <label for="detalle">Detalle *</label>
                                                <InputError class="mt-2" :message="form.errors.detalle" />
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-floating form-floating-outline">
                                                <input type="text" id="contacto" class="form-control"
                                                    placeholder="Contacto" autocomplete="off" :disabled="!edicion" maxlength="45" v-model="form.contacto" />
                                                <label for="contacto">Contacto</label>
                                                <InputError class="mt-2" :message="form.errors.contacto" />
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-floating form-floating-outline">
                                                <input type="text" id="localidad" class="form-control"
                                                    placeholder="Localidad" autocomplete="off" :disabled="!edicion" maxlength="25" v-model="form.localidad" />
                                                <label for="localidad">Localidad</label>
                                                <InputError class="mt-2" :message="form.errors.localidad" />
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-floating form-floating-outline">
                                                <input type="text" id="cp" class="form-control"
                                                    placeholder="CP" autocomplete="off" :disabled="!edicion" maxlength="10" v-model="form.cp" />
                                                <label for="cp">CP</label>
                                                <InputError class="mt-2" :message="form.errors.cp" />
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-floating form-floating-outline">
                                                <input type="email" id="email" class="form-control"
                                                    placeholder="Email" autocomplete="off" :disabled="!edicion" maxlength="45" v-model="form.email" />
                                                <label for="email">Email</label>
                                                <InputError class="mt-2" :message="form.errors.email" />
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="form-floating form-floating-outline">
                                                <input type="text" id="tel1" class="form-control"
                                                    placeholder="Teléfono 1" autocomplete="off" :disabled="!edicion" maxlength="20" v-model="form.tel1" />
                                                <label for="tel1">Teléfono 1</label>
                                                <InputError class="mt-2" :message="form.errors.tel1" />
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-floating form-floating-outline">
                                                <input type="text" id="tel2" class="form-control"
                                                    placeholder="Teléfono 2" autocomplete="off" :disabled="!edicion" maxlength="20" v-model="form.tel2" />
                                                <label for="tel2">Teléfono 2</label>
                                                <InputError class="mt-2" :message="form.errors.tel2" />
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-floating form-floating-outline">
                                                <input type="text" id="tel3" class="form-control"
                                                    placeholder="Teléfono 3" autocomplete="off" :disabled="!edicion" maxlength="20" v-model="form.tel3" />
                                                <label for="tel3">Teléfono 3</label>
                                                <InputError class="mt-2" :message="form.errors.tel3" />
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-floating form-floating-outline">
                                                <input type="text" id="web" class="form-control"
                                                    placeholder="Web" autocomplete="off" :disabled="!edicion" maxlength="45" v-model="form.web" />
                                                <label for="web">Web</label>
                                                <InputError class="mt-2" :message="form.errors.web" />
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="form-floating form-floating-outline">
                                                <input type="number" step="0.01" min="0" id="porce_apo" class="form-control"
                                                    placeholder="0.00" :disabled="!edicion" v-model.number="form.porce_apo" />
                                                <label for="porce_apo">% Aporte</label>
                                                <InputError class="mt-2" :message="form.errors.porce_apo" />
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-floating form-floating-outline">
                                                <input type="number" step="0.01" min="0" id="porce_con" class="form-control"
                                                    placeholder="0.00" :disabled="!edicion" v-model.number="form.porce_con" />
                                                <label for="porce_con">% Contribución</label>
                                                <InputError class="mt-2" :message="form.errors.porce_con" />
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-floating form-floating-outline">
                                                <input type="number" step="0.01" min="0" id="fijo_apo" class="form-control"
                                                    placeholder="0.00" :disabled="!edicion" v-model.number="form.fijo_apo" />
                                                <label for="fijo_apo">Aporte fijo</label>
                                                <InputError class="mt-2" :message="form.errors.fijo_apo" />
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-floating form-floating-outline">
                                                <input type="number" step="0.01" min="0" id="fijo_con" class="form-control"
                                                    placeholder="0.00" :disabled="!edicion" v-model.number="form.fijo_con" />
                                                <label for="fijo_con">Contribución fija</label>
                                                <InputError class="mt-2" :message="form.errors.fijo_con" />
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
