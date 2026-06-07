<script setup>
// import InputError from '@/Components/InputError.vue';
// import InputLabel from '@/Components/InputLabel.vue';
// import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Input from '@/Components/Input.vue';
import InputError from '@/Components/InputError.vue';
import FormHeader from '@/Components/FormHeader.vue';
import { router, Link, useForm, usePage } from '@inertiajs/vue3';
import { reactive, ref, onMounted, watch } from 'vue';

// Recibir la prop agregar desde Laravel
const props = defineProps({
    legajo: {
        type: Object,
        default: () => ({
            id: null,
            codigo: '',
            detalle: '',
            fantasia: '',
            cuit: '',
            direccion_comercial: '',
            localidad_comercial: '',
            cod_pos_comercial: '',
            direccion_fiscal: '',
            localidad_fiscal: '',
            cod_pos_fiscal: '',
            telefono: '',
            email: '',
            web: '',
            tipo: '',
            actividad: '',
            tipo_empleador_lsd: '1',
            nom_arch: '',
            legajo_desde: null,
            legajo_hasta: null,
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
    tiposEmpleadorLsd: {
      type: Object,
      default: () => ({}),
    },
});

// Accediendo a las props pasadas desde el controlador
const user = usePage().props.legajo;

const form = useForm({
    id: props.legajo?.id ?? null,
    codigo: props.legajo?.codigo ?? '',
    detalle: props.legajo?.detalle ?? '',
    fantasia: props.legajo?.fantasia ?? '',
    cuit: props.legajo?.cuit ?? '',
    direccion_comercial: props.legajo?.direccion_comercial ?? '',
    localidad_comercial: props.legajo?.localidad_comercial ?? '',
    cod_pos_comercial: props.legajo?.cod_pos_comercial ?? '',
    direccion_fiscal: props.legajo?.direccion_fiscal ?? '',
    localidad_fiscal: props.legajo?.localidad_fiscal ?? '',
    cod_pos_fiscal: props.legajo?.cod_pos_fiscal ?? '',
    telefono: props.legajo?.telefono ?? '',
    email: props.legajo?.email ?? '',
    web: props.legajo?.web ?? '',
    tipo: props.legajo?.tipo ?? '',
    actividad: props.legajo?.actividad ?? '',
    tipo_empleador_lsd: props.legajo?.tipo_empleador_lsd ?? '1',
    nom_arch: props.legajo?.nom_arch ?? '',
    legajo_desde: props.legajo?.legajo_desde ?? null,
    legajo_hasta: props.legajo?.legajo_hasta ?? null,
});

// Función para determinar la ruta del formulario
const determineActionRoute = () => {
  if (props.agregar) {
    return '/grupos-empresarios';
  } else if (props.edicion) {
    return `/grupos-empresarios/${form.id}`;
  } else {
    return '/grupos-empresarios';
  }
};

const errors = usePage().props.errors;

// Funcion para grabar datos
const submit = () => {
    let ruta = 'grupos.empresarios.update';

    if (usePage().props.agregar) {
        ruta = 'grupos.empresarios.store';

        form.post(route(ruta), {
            preserveScroll: true,
            onSuccess: (response) => {
                //
            },
            onError: (errors) => {
                console.log('ERRORS', errors);
                console.log('FORM.ERRORES', form.errors);
            },
        });
    } else {
        form.patch(route(ruta, form.id), {
            preserveScroll: true,
            onSuccess: () => {
                //
            },
            onError: () => {
                //
            }
        });
    }
};

// Funcion para eliminar
const borrar = () => {
    const id = props.legajo?.id
    if (!id) return

    router.delete(route('grupos.empresarios.destroy', id), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    })
}

// Cerrar el modal
const closeModal = () => {
    const elementToRemove = document.querySelector('.modal-backdrop');
    if (elementToRemove) {
        elementToRemove.remove();
    }
};

// Crear una referencia para el input que recibira el foco
const txtcodigo = ref(null);
const txtdetalle = ref(null);

// Función que establece el foco basado en la prop agregar
const setFocus = () => {
    if (props.agregar) {
        txtcodigo.value?.focus();
    } else {
        txtdetalle.value?.focus();
    }
};

// Establecer el foco al montar el componente
onMounted(() => {
    setFocus();
});

// Observar cambios en la prop agregar
watch(() => props.agregar, () => {
    setFocus();
});

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
                    titulo="Grupos Empresarios"
                    :ruta-create="route('grupos.empresarios.create')"
                    :ruta-edit="form.id ? route('grupos.empresarios.edit', form.id) : null"
                    :ruta-first="route('grupos.empresarios.first')"
                    :ruta-previous="form.id ? route('grupos.empresarios.previous', form.id) : null"
                    :ruta-next="form.id ? route('grupos.empresarios.next', form.id) : null"
                    :ruta-last="route('grupos.empresarios.last')"
                    :ruta-search="route('grupos.empresarios.search')"
                    :ruta-index="route('grupos.empresarios.index')"
                    :on-submit="submit"
                    @delete="() => {}"
                />
                <!-- END HEAD Y BOTONES -->

                <div class="row">
                    <!-- Form with Tabs -->
                    <div class="row">
                        <div class="col">
                            <div class="card mb-6">
                                <div class="card-header overflow-hidden">
                                    <ul class="nav nav-tabs" role="tablist">
                                        <li class="nav-item">
                                            <button
                                                class="nav-link active"
                                                data-bs-toggle="tab"
                                                data-bs-target="#form-tabs-personal"
                                                role="tab"
                                                aria-selected="true">
                                                <span class="ri-building-line ri-20px d-sm-none"></span
                                                ><span class="d-none d-sm-block">Información Principal</span>
                                            </button>
                                        </li>
                                    </ul>
                                </div>

                                <div class="tab-content">
                                    <div class="tab-pane fade active show" id="form-tabs-personal" role="tabpanel">

                                        <div class="row g-6">
                                            <div class="col-md-2">
                                                <div class="form-floating form-floating-outline">
                                                    <Input
                                                        id="codigo"
                                                        name="codigo"
                                                        type="text"
                                                        v-model="form.codigo"
                                                        ref="txtcodigo"
                                                        v-bind:disabled="!agregar"
                                                        autocomplete="off"
                                                        placeholder="2 caracteres max"
                                                        maxlength="2"
                                                    />

                                                    <label for="codigo">Código</label>

                                                    <InputError class="mt-2" :message="form.errors.codigo" />
                                                </div>
                                            </div>

                                            <div class="row mt-6 col-md-12">
                                                <div class="col-md-6">
                                                    <div class="form-floating form-floating-outline">
                                                        <input
                                                            type="text"
                                                            id="detalle" name="detalle" ref="txtdetalle"
                                                            class="form-control"
                                                            placeholder="Razón social"
                                                            autocomplete="off"
                                                            v-bind:disabled="!edicion"
                                                            v-model="form.detalle"
                                                            maxlength="40"/>

                                                        <label for="detalle">Razón social *</label>

                                                        <InputError class="mt-2" :message="form.errors.detalle" />
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="form-floating form-floating-outline">
                                                        <input
                                                            type="text"
                                                            id="fantasia" name="fantasia"
                                                            class="form-control"
                                                            placeholder="Nombre fantasía"
                                                            autocomplete="off"
                                                            v-bind:disabled="!edicion"
                                                            v-model="form.fantasia"
                                                            maxlength="100"/>

                                                        <label for="fantasia">Nombre fantasía</label>

                                                        <InputError class="mt-2" :message="form.errors.fantasia" />
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row mt-6 col-md-12">
                                                <div class="col-md-3">
                                                    <div class="form-floating form-floating-outline">
                                                        <input
                                                            type="text"
                                                            id="cuit" name="cuit"
                                                            class="form-control"
                                                            placeholder="CUIT"
                                                            autocomplete="off"
                                                            v-bind:disabled="!edicion"
                                                            v-model="form.cuit"
                                                            maxlength="100"/>

                                                        <label for="cuit">CUIT</label>

                                                        <InputError class="mt-2" :message="form.errors.cuit" />
                                                    </div>
                                                </div>

                                                <div class="col-md-2">
                                                    <div class="form-floating form-floating-outline">
                                                        <input
                                                            type="text"
                                                            id="tipo" name="tipo"
                                                            class="form-control"
                                                            placeholder="Tipo"
                                                            autocomplete="off"
                                                            v-bind:disabled="!edicion"
                                                            v-model="form.tipo"
                                                            maxlength="3"/>

                                                        <label for="tipo">Tipo</label>

                                                        <InputError class="mt-2" :message="form.errors.tipo" />
                                                    </div>
                                                </div>

                                                <div class="col-md-7">
                                                    <div class="form-floating form-floating-outline">
                                                        <input
                                                            type="text"
                                                            id="actividad" name="actividad"
                                                            class="form-control"
                                                            placeholder="Actividad"
                                                            autocomplete="off"
                                                            v-bind:disabled="!edicion"
                                                            v-model="form.actividad"
                                                            maxlength="150"/>

                                                        <label for="actividad">Actividad</label>

                                                        <InputError class="mt-2" :message="form.errors.actividad" />
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row mt-6 col-md-12">
                                                <div class="col-md-8">
                                                    <div class="form-floating form-floating-outline">
                                                        <select
                                                            id="tipo_empleador_lsd"
                                                            name="tipo_empleador_lsd"
                                                            class="select2 form-select browser-default"
                                                            v-bind:disabled="!edicion"
                                                            v-model="form.tipo_empleador_lsd">
                                                            <option v-for="(label, value) in tiposEmpleadorLsd" :key="value" :value="String(value)">
                                                                {{ value }} - {{ label }}
                                                            </option>
                                                        </select>
                                                        <label for="tipo_empleador_lsd">Tipo de empleador LSD (Dec. 814/01)</label>
                                                        <InputError class="mt-2" :message="form.errors.tipo_empleador_lsd" />
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row mt-6 col-md-12">
                                                <div class="col-md-6">
                                                    <div class="form-floating form-floating-outline">
                                                        <input
                                                            type="text"
                                                            id="direccion_comercial" name="direccion_comercial"
                                                            class="form-control"
                                                            placeholder="Dirección comercial"
                                                            autocomplete="off"
                                                            v-bind:disabled="!edicion"
                                                            v-model="form.direccion_comercial"
                                                            maxlength="100"/>

                                                        <label for="direccion_comercial">Dirección comercial</label>

                                                        <InputError class="mt-2" :message="form.errors.direccion_comercial" />
                                                    </div>
                                                </div>

                                                <div class="col-md-4">
                                                    <div class="form-floating form-floating-outline">
                                                        <input
                                                            type="text"
                                                            id="localidad_comercial" name="localidad_comercial"
                                                            class="form-control"
                                                            placeholder="Localidad comercial"
                                                            autocomplete="off"
                                                            v-bind:disabled="!edicion"
                                                            v-model="form.localidad_comercial"
                                                            maxlength="100"/>

                                                        <label for="localidad_comercial">Localidad comercial</label>

                                                        <InputError class="mt-2" :message="form.errors.localidad_comercial" />
                                                    </div>
                                                </div>

                                                <div class="col-md-2">
                                                    <div class="form-floating form-floating-outline">
                                                        <input
                                                            type="text"
                                                            id="cod_pos_comercial" name="cod_pos_comercial"
                                                            class="form-control"
                                                            placeholder="CP"
                                                            autocomplete="off"
                                                            v-bind:disabled="!edicion"
                                                            v-model="form.cod_pos_comercial"
                                                            maxlength="100"/>

                                                        <label for="cod_pos_comercial">CP comercial</label>

                                                        <InputError class="mt-2" :message="form.errors.cod_pos_comercial" />
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row mt-6 col-md-12">
                                                <div class="col-md-6">
                                                    <div class="form-floating form-floating-outline">
                                                        <input
                                                            type="text"
                                                            id="direccion_fiscal" name="direccion_fiscal"
                                                            class="form-control"
                                                            placeholder="Dirección fiscal"
                                                            autocomplete="off"
                                                            v-bind:disabled="!edicion"
                                                            v-model="form.direccion_fiscal"
                                                            maxlength="100"/>

                                                        <label for="direccion_fiscal">Dirección fiscal</label>

                                                        <InputError class="mt-2" :message="form.errors.direccion_fiscal" />
                                                    </div>
                                                </div>

                                                <div class="col-md-4">
                                                    <div class="form-floating form-floating-outline">
                                                        <input
                                                            type="text"
                                                            id="localidad_fiscal" name="localidad_fiscal"
                                                            class="form-control"
                                                            placeholder="Localidad fiscal"
                                                            autocomplete="off"
                                                            v-bind:disabled="!edicion"
                                                            v-model="form.localidad_fiscal"
                                                            maxlength="100"/>

                                                        <label for="localidad_fiscal">Localidad fiscal</label>

                                                        <InputError class="mt-2" :message="form.errors.localidad_fiscal" />
                                                    </div>
                                                </div>

                                                <div class="col-md-2">
                                                    <div class="form-floating form-floating-outline">
                                                        <input
                                                            type="text"
                                                            id="cod_pos_fiscal" name="cod_pos_fiscal"
                                                            class="form-control"
                                                            placeholder="CP"
                                                            autocomplete="off"
                                                            v-bind:disabled="!edicion"
                                                            v-model="form.cod_pos_fiscal"
                                                            maxlength="100"/>

                                                        <label for="cod_pos_fiscal">CP fiscal</label>

                                                        <InputError class="mt-2" :message="form.errors.cod_pos_fiscal" />
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row mt-6 col-md-12">
                                                <div class="col-md-3">
                                                    <div class="form-floating form-floating-outline">
                                                        <input
                                                            type="text"
                                                            id="telefono" name="telefono"
                                                            class="form-control"
                                                            placeholder="Teléfono"
                                                            autocomplete="off"
                                                            v-bind:disabled="!edicion"
                                                            v-model="form.telefono"
                                                            maxlength="100"/>

                                                        <label for="telefono">Teléfono</label>

                                                        <InputError class="mt-2" :message="form.errors.telefono" />
                                                    </div>
                                                </div>

                                                <div class="col-md-4">
                                                    <div class="form-floating form-floating-outline">
                                                        <input
                                                            type="email"
                                                            id="email" name="email"
                                                            class="form-control"
                                                            placeholder="Email"
                                                            autocomplete="off"
                                                            v-bind:disabled="!edicion"
                                                            v-model="form.email"
                                                            maxlength="100"/>

                                                        <label for="email">Email</label>

                                                        <InputError class="mt-2" :message="form.errors.email" />
                                                    </div>
                                                </div>

                                                <div class="col-md-5">
                                                    <div class="form-floating form-floating-outline">
                                                        <input
                                                            type="text"
                                                            id="web" name="web"
                                                            class="form-control"
                                                            placeholder="Web"
                                                            autocomplete="off"
                                                            v-bind:disabled="!edicion"
                                                            v-model="form.web"
                                                            maxlength="100"/>

                                                        <label for="web">Web</label>

                                                        <InputError class="mt-2" :message="form.errors.web" />
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row mt-6 col-md-12">
                                                <div class="col-md-3">
                                                    <div class="form-floating form-floating-outline">
                                                        <input
                                                            type="number"
                                                            id="legajo_desde" name="legajo_desde"
                                                            class="form-control"
                                                            placeholder="Legajo desde"
                                                            autocomplete="off"
                                                            v-bind:disabled="!edicion"
                                                            v-model.number="form.legajo_desde"/>

                                                        <label for="legajo_desde">Legajo desde</label>

                                                        <InputError class="mt-2" :message="form.errors.legajo_desde" />
                                                    </div>
                                                </div>

                                                <div class="col-md-3">
                                                    <div class="form-floating form-floating-outline">
                                                        <input
                                                            type="number"
                                                            id="legajo_hasta" name="legajo_hasta"
                                                            class="form-control"
                                                            placeholder="Legajo hasta"
                                                            autocomplete="off"
                                                            v-bind:disabled="!edicion"
                                                            v-model.number="form.legajo_hasta"/>

                                                        <label for="legajo_hasta">Legajo hasta</label>

                                                        <InputError class="mt-2" :message="form.errors.legajo_hasta" />
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="form-floating form-floating-outline">
                                                        <input
                                                            type="text"
                                                            id="nom_arch" name="nom_arch"
                                                            class="form-control"
                                                            placeholder="Nombre de archivo"
                                                            autocomplete="off"
                                                            v-bind:disabled="!edicion"
                                                            v-model="form.nom_arch"
                                                            maxlength="255"/>

                                                        <label for="nom_arch">Nombre de archivo</label>

                                                        <InputError class="mt-2" :message="form.errors.nom_arch" />
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row"></div>

                                            <div class="col-md-6">
                                                <div class="form-floating form-floating-outline">
                                                    <br>
                                                </div>
                                            </div>
                                        </div>

                                    </div>

                                </div>
                            </div>

                            <br><br>

                        </div>
                    </div>
                </div>

            </div>
        </div>
    </form>


    <!-- MODAL -->
    <div class="col-lg-4 col-md-3">
        <div class="mt-4">
            <!-- Modal -->
            <div id="modalDelete" class="modal fade" data-bs-backdrop="static" tabindex="-1">
                <div class="modal-dialog">
                    <form class="modal-content">
                        <div class="modal-header">
                            <h4 class="onboarding-title text-body">Borrar registro ?</h4>
                            <button
                                type="button"
                                class="btn-close"
                                data-bs-dismiss="modal"
                                aria-label="Close">
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                            <div class="col mb-6 mt-1">

                                <div class="alert d-flex align-items-center alert-warning mb-0 h6" role="alert">
                                <span class="alert-icon rounded-4"><i class="ri-information-line ri-22px"></i></span>

                                <span>  <br>Esta seguro de eliminar el registro seleccionado? <br> No podra recuperar el registro borrado...  <br> <br></span>
                                </div>

                            </div>
                            </div>
                            <div class="row g-4">
                            <br>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button
                                type="button"
                                class="btn btn-danger waves-effect waves-light"
                                style="color: white"
                                @click="borrar"
                                >Borrar
                            </button>

                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            Cancelar
                            </button>

                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</template>
