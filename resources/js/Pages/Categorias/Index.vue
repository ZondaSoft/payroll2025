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
    sue_bas:   props.registro?.sue_bas ?? null,
    hsnormal:  props.registro?.hsnormal ?? null,
    hsmin:     props.registro?.hsmin ?? null,
    hsmax:     props.registro?.hsmax ?? null,
    cod_conve: props.registro?.cod_conve ?? '',
});

const submit = () => {
    if (usePage().props.agregar) {
        form.post(route('categorias.store'), { preserveScroll: true });
    } else {
        form.patch(route('categorias.update', form.id), { preserveScroll: true });
    }
};

const borrar = () => {
    const id = props.registro?.id;
    if (!id) return;
    router.delete(route('categorias.destroy', id), { preserveScroll: true, onSuccess: () => closeModal() });
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
                    titulo="Categoría"
                    :ruta-create="route('categorias.create')"
                    :ruta-edit="form.id ? route('categorias.edit', form.id) : null"
                    :ruta-first="route('categorias.first')"
                    :ruta-previous="form.id ? route('categorias.previous', form.id) : null"
                    :ruta-next="form.id ? route('categorias.next', form.id) : null"
                    :ruta-last="route('categorias.last')"
                    :ruta-search="route('categorias.search')"
                    :ruta-index="route('categorias.index')"
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
                                        <div class="col-md-7">
                                            <div class="form-floating form-floating-outline">
                                                <input type="text" id="detalle" ref="txtdetalle" class="form-control"
                                                    placeholder="Detalle" autocomplete="off" :disabled="!edicion" maxlength="100" v-model="form.detalle" />
                                                <label for="detalle">Detalle *</label>
                                                <InputError class="mt-2" :message="form.errors.detalle" />
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-floating form-floating-outline">
                                                <input type="text" id="cod_conve" class="form-control"
                                                    placeholder="Convenio" autocomplete="off" :disabled="!edicion" maxlength="5" v-model="form.cod_conve" />
                                                <label for="cod_conve">Cód. convenio</label>
                                                <InputError class="mt-2" :message="form.errors.cod_conve" />
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="form-floating form-floating-outline">
                                                <input type="number" min="0" id="sue_bas" class="form-control"
                                                    placeholder="0" :disabled="!edicion" v-model.number="form.sue_bas" />
                                                <label for="sue_bas">Sueldo básico</label>
                                                <InputError class="mt-2" :message="form.errors.sue_bas" />
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-floating form-floating-outline">
                                                <input type="number" min="0" id="hsnormal" class="form-control"
                                                    placeholder="0" :disabled="!edicion" v-model.number="form.hsnormal" />
                                                <label for="hsnormal">Horas normales</label>
                                                <InputError class="mt-2" :message="form.errors.hsnormal" />
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-floating form-floating-outline">
                                                <input type="number" min="0" id="hsmin" class="form-control"
                                                    placeholder="0" :disabled="!edicion" v-model.number="form.hsmin" />
                                                <label for="hsmin">Horas mín.</label>
                                                <InputError class="mt-2" :message="form.errors.hsmin" />
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-floating form-floating-outline">
                                                <input type="number" min="0" id="hsmax" class="form-control"
                                                    placeholder="0" :disabled="!edicion" v-model.number="form.hsmax" />
                                                <label for="hsmax">Horas máx.</label>
                                                <InputError class="mt-2" :message="form.errors.hsmax" />
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
