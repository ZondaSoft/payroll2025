<script setup>
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import FormHeader from '@/Components/FormHeader.vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { ref, onMounted, watch } from 'vue';

const props = defineProps({
    convenio: {
        type: Object,
        default: () => ({ id: null, codigo: '', detalle: '' }),
    },
    agregar: { type: Boolean, required: true },
    edicion: { type: Boolean, required: true },
    empresa: { type: Object, required: true },
});

const form = useForm({
    id:                       props.convenio?.id ?? null,
    codigo:                   props.convenio?.codigo ?? '',
    detalle:                  props.convenio?.detalle ?? '',
    hs_normales_diarias:      props.convenio?.hs_normales_diarias ?? null,
    hs_normales_semanales:    props.convenio?.hs_normales_semanales ?? null,
    porc_tarea_dif:           props.convenio?.porc_tarea_dif ?? null,
    noct_100:                 !!props.convenio?.noct_100,
    forzar50:                 props.convenio?.forzar50 ?? '',
    // Banco de horas
    bh_habilitado:            !!props.convenio?.bh_habilitado,
    bh_tope_saldo_positivo:   props.convenio?.bh_tope_saldo_positivo ?? null,
    bh_meses_vencimiento:     props.convenio?.bh_meses_vencimiento ?? null,
    bh_al_vencer:             props.convenio?.bh_al_vencer ?? 'pierde',
    bh_convierte_a_extra_pct: props.convenio?.bh_convierte_a_extra_pct ?? null,
    bh_cod_nov_franco:        props.convenio?.bh_cod_nov_franco ?? '',
    bh_cod_nov_paga_extra:    props.convenio?.bh_cod_nov_paga_extra ?? '',
    // Jornada extendida
    je_habilitada:            !!props.convenio?.je_habilitada,
    je_hs_normales:           props.convenio?.je_hs_normales ?? null,
    je_hs_dobles:             props.convenio?.je_hs_dobles ?? null,
    je_cod_nov_doble:         props.convenio?.je_cod_nov_doble ?? '',
});

// Funcion para grabar datos
const submit = () => {
    if (usePage().props.agregar) {
        form.post(route('convenios.store'), { preserveScroll: true });
    } else {
        form.patch(route('convenios.update', form.id), { preserveScroll: true });
    }
};

// Funcion para eliminar
const borrar = () => {
    const id = props.convenio?.id;
    if (!id) return;
    router.delete(route('convenios.destroy', id), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
};

const closeModal = () => {
    const elementToRemove = document.querySelector('.modal-backdrop');
    if (elementToRemove) elementToRemove.remove();
};

// Foco inicial
const txtcodigo = ref(null);
const txtdetalle = ref(null);

const setFocus = () => {
    if (props.agregar) {
        txtcodigo.value?.focus();
    } else {
        txtdetalle.value?.focus();
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
                    titulo="Convenio Colectivo (CCT)"
                    :ruta-create="route('convenios.create')"
                    :ruta-edit="form.id ? route('convenios.edit', form.id) : null"
                    :ruta-first="route('convenios.first')"
                    :ruta-previous="form.id ? route('convenios.previous', form.id) : null"
                    :ruta-next="form.id ? route('convenios.next', form.id) : null"
                    :ruta-last="route('convenios.last')"
                    :ruta-search="route('convenios.search')"
                    :ruta-index="route('convenios.index')"
                    :on-submit="submit"
                />
                <!-- END HEAD Y BOTONES -->

                <div class="row">
                    <div class="col">
                        <div class="card mb-6">
                            <div class="card-header overflow-hidden">
                                <ul class="nav nav-tabs" role="tablist">
                                    <li class="nav-item">
                                        <button class="nav-link active" data-bs-toggle="tab"
                                            data-bs-target="#tab-principal" role="tab" aria-selected="true">
                                            <span class="ri-file-list-3-line ri-20px d-sm-none"></span>
                                            <span class="d-none d-sm-block">Información Principal</span>
                                        </button>
                                    </li>
                                    <li class="nav-item">
                                        <button class="nav-link" data-bs-toggle="tab"
                                            data-bs-target="#tab-banco-horas" role="tab" aria-selected="false">
                                            <span class="ri-time-line ri-20px d-sm-none"></span>
                                            <span class="d-none d-sm-block">Banco de Horas</span>
                                        </button>
                                    </li>
                                    <li class="nav-item">
                                        <button class="nav-link" data-bs-toggle="tab"
                                            data-bs-target="#tab-jornada" role="tab" aria-selected="false">
                                            <span class="ri-timer-line ri-20px d-sm-none"></span>
                                            <span class="d-none d-sm-block">Jornada Extendida</span>
                                        </button>
                                    </li>
                                </ul>
                            </div>

                            <div class="tab-content">
                                <!-- ============ TAB PRINCIPAL ============ -->
                                <div class="tab-pane fade active show" id="tab-principal" role="tabpanel">
                                    <div class="row g-6">
                                        <div class="col-md-2">
                                            <div class="form-floating form-floating-outline">
                                                <TextInput
                                                    id="codigo" name="codigo" type="text"
                                                    v-model="form.codigo" ref="txtcodigo"
                                                    :disabled="!agregar" autocomplete="off"
                                                    placeholder="Código" maxlength="5" />
                                                <label for="codigo">Código *</label>
                                                <InputError class="mt-2" :message="form.errors.codigo" />
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-floating form-floating-outline">
                                                <input type="text" id="detalle" name="detalle" ref="txtdetalle"
                                                    class="form-control" placeholder="Nombre del convenio"
                                                    autocomplete="off" :disabled="!edicion" maxlength="30"
                                                    v-model="form.detalle" />
                                                <label for="detalle">Detalle *</label>
                                                <InputError class="mt-2" :message="form.errors.detalle" />
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="form-floating form-floating-outline">
                                                <input type="number" step="0.01" min="0" max="100" id="porc_tarea_dif"
                                                    class="form-control" placeholder="0.00" :disabled="!edicion"
                                                    v-model.number="form.porc_tarea_dif" />
                                                <label for="porc_tarea_dif">Contribución tarea diferencial (Reg 04) %</label>
                                                <InputError class="mt-2" :message="form.errors.porc_tarea_dif" />
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="form-floating form-floating-outline">
                                                <input type="number" min="0" max="24" id="hs_normales_diarias"
                                                    class="form-control" placeholder="0" :disabled="!edicion"
                                                    v-model.number="form.hs_normales_diarias" />
                                                <label for="hs_normales_diarias">Horas normales diarias</label>
                                                <InputError class="mt-2" :message="form.errors.hs_normales_diarias" />
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="form-floating form-floating-outline">
                                                <input type="number" min="0" max="168" id="hs_normales_semanales"
                                                    class="form-control" placeholder="0" :disabled="!edicion"
                                                    v-model.number="form.hs_normales_semanales" />
                                                <label for="hs_normales_semanales">Horas normales semanales</label>
                                                <InputError class="mt-2" :message="form.errors.hs_normales_semanales" />
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="form-floating form-floating-outline">
                                                <input type="text" id="forzar50" class="form-control" placeholder=""
                                                    autocomplete="off" :disabled="!edicion" maxlength="40"
                                                    v-model="form.forzar50" />
                                                <label for="forzar50">Forzar 50%</label>
                                                <InputError class="mt-2" :message="form.errors.forzar50" />
                                            </div>
                                        </div>

                                        <div class="col-md-3 d-flex align-items-center">
                                            <div class="form-check form-switch mt-3">
                                                <input class="form-check-input" type="checkbox" id="noct_100"
                                                    :disabled="!edicion" v-model="form.noct_100" />
                                                <label class="form-check-label" for="noct_100">Nocturnidad al 100%</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- ============ TAB BANCO DE HORAS ============ -->
                                <div class="tab-pane fade" id="tab-banco-horas" role="tabpanel">
                                    <div class="row g-6">
                                        <div class="col-md-12">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="bh_habilitado"
                                                    :disabled="!edicion" v-model="form.bh_habilitado" />
                                                <label class="form-check-label" for="bh_habilitado">Banco de horas habilitado</label>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="form-floating form-floating-outline">
                                                <input type="number" step="0.01" min="0" id="bh_tope_saldo_positivo"
                                                    class="form-control" placeholder="0.00"
                                                    :disabled="!edicion || !form.bh_habilitado"
                                                    v-model.number="form.bh_tope_saldo_positivo" />
                                                <label for="bh_tope_saldo_positivo">Tope saldo positivo (hs)</label>
                                                <InputError class="mt-2" :message="form.errors.bh_tope_saldo_positivo" />
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="form-floating form-floating-outline">
                                                <input type="number" min="0" id="bh_meses_vencimiento"
                                                    class="form-control" placeholder="0"
                                                    :disabled="!edicion || !form.bh_habilitado"
                                                    v-model.number="form.bh_meses_vencimiento" />
                                                <label for="bh_meses_vencimiento">Meses para vencimiento</label>
                                                <InputError class="mt-2" :message="form.errors.bh_meses_vencimiento" />
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="form-floating form-floating-outline">
                                                <select id="bh_al_vencer" class="form-select"
                                                    :disabled="!edicion || !form.bh_habilitado"
                                                    v-model="form.bh_al_vencer">
                                                    <option value="pierde">Pierde</option>
                                                    <option value="paga_extra">Paga extra</option>
                                                </select>
                                                <label for="bh_al_vencer">Al vencer</label>
                                                <InputError class="mt-2" :message="form.errors.bh_al_vencer" />
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="form-floating form-floating-outline">
                                                <input type="number" step="0.01" min="0" max="100" id="bh_convierte_a_extra_pct"
                                                    class="form-control" placeholder="0.00"
                                                    :disabled="!edicion || !form.bh_habilitado"
                                                    v-model.number="form.bh_convierte_a_extra_pct" />
                                                <label for="bh_convierte_a_extra_pct">% que convierte a extra</label>
                                                <InputError class="mt-2" :message="form.errors.bh_convierte_a_extra_pct" />
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="form-floating form-floating-outline">
                                                <input type="text" id="bh_cod_nov_franco" class="form-control"
                                                    placeholder="" autocomplete="off" maxlength="6"
                                                    :disabled="!edicion || !form.bh_habilitado"
                                                    v-model="form.bh_cod_nov_franco" />
                                                <label for="bh_cod_nov_franco">Cód. novedad franco</label>
                                                <InputError class="mt-2" :message="form.errors.bh_cod_nov_franco" />
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="form-floating form-floating-outline">
                                                <input type="text" id="bh_cod_nov_paga_extra" class="form-control"
                                                    placeholder="" autocomplete="off" maxlength="6"
                                                    :disabled="!edicion || !form.bh_habilitado"
                                                    v-model="form.bh_cod_nov_paga_extra" />
                                                <label for="bh_cod_nov_paga_extra">Cód. novedad paga extra</label>
                                                <InputError class="mt-2" :message="form.errors.bh_cod_nov_paga_extra" />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- ============ TAB JORNADA EXTENDIDA ============ -->
                                <div class="tab-pane fade" id="tab-jornada" role="tabpanel">
                                    <div class="row g-6">
                                        <div class="col-md-12">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="je_habilitada"
                                                    :disabled="!edicion" v-model="form.je_habilitada" />
                                                <label class="form-check-label" for="je_habilitada">Jornada extendida habilitada</label>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="form-floating form-floating-outline">
                                                <input type="number" step="0.01" min="0" id="je_hs_normales"
                                                    class="form-control" placeholder="0.00"
                                                    :disabled="!edicion || !form.je_habilitada"
                                                    v-model.number="form.je_hs_normales" />
                                                <label for="je_hs_normales">Horas normales</label>
                                                <InputError class="mt-2" :message="form.errors.je_hs_normales" />
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="form-floating form-floating-outline">
                                                <input type="number" step="0.01" min="0" id="je_hs_dobles"
                                                    class="form-control" placeholder="0.00"
                                                    :disabled="!edicion || !form.je_habilitada"
                                                    v-model.number="form.je_hs_dobles" />
                                                <label for="je_hs_dobles">Horas dobles</label>
                                                <InputError class="mt-2" :message="form.errors.je_hs_dobles" />
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="form-floating form-floating-outline">
                                                <input type="text" id="je_cod_nov_doble" class="form-control"
                                                    placeholder="" autocomplete="off" maxlength="6"
                                                    :disabled="!edicion || !form.je_habilitada"
                                                    v-model="form.je_cod_nov_doble" />
                                                <label for="je_cod_nov_doble">Cód. novedad doble</label>
                                                <InputError class="mt-2" :message="form.errors.je_cod_nov_doble" />
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
                                        <span><br>Esta seguro de eliminar el convenio seleccionado? <br> No podra recuperar el registro borrado... <br><br></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-danger waves-effect waves-light"
                                style="color: white" @click="borrar">Borrar</button>
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>
