<script setup>
// import InputError from '@/Components/InputError.vue';
// import InputLabel from '@/Components/InputLabel.vue';
// import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
//import Input from '@/Components/Input.vue';
import InputError from '@/Components/InputError.vue';
import FormHeader from '@/Components/FormHeader.vue';
import { router, Link, useForm, usePage } from '@inertiajs/vue3';
import { reactive, ref, onMounted, watch } from 'vue';
import { PAISES } from '@/Data/paises';

// Recibir la prop agregar desde Laravel
const props = defineProps({
    legajo: {
        type: Object,
        default: () => ({ id: null, codigo: '', detalle: '' }),
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

// Accediendo a las props pasadas desde el controlador
const user = usePage().props.legajo;

const form = useForm({
    id: props.legajo?.id ?? null,
    codigo: props.legajo?.codigo ?? '',
    detalle: props.legajo?.detalle ?? '',
});

// Función para determinar la ruta del formulario
const determineActionRoute = () => {
  if (props.agregar) {
    //console.log(usePage().props.agregar)
    return '/sicoss/obras/add';
  } else if (props.edicion) {
    return `/sicoss/obras/edit/${form.id}`;
  } else {
    return '/sicoss/obras'; // Ruta predeterminada o de error
  }
};

const errors = usePage().props.errors;

// Funcion para grabar datos
const submit = () => {
    let ruta = 'sicoss.obras.update';

    if (usePage().props.agregar) {
        ruta = 'sicoss.obras.store';

        form.post(route(ruta), {
            preserveScroll: true,
            onSuccess: (response) => {
                // Manejar el éxito (por ejemplo, limpiar el formulario, mostrar un mensaje, etc.)

                //console.log(form);

                //console.log(response.data);
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
                // Manejar el éxito (por ejemplo, limpiar el formulario, mostrar un mensaje, etc.)
                //console.log(form);
            },
            onError: () => {
                // Manejar los errores de validación
            }
        });
    }


};

// Funcion para eliminar
const borrar = () => {
    const id = props.legajo?.id
    if (!id) return

    router.delete(route('sicoss.obras.destroy', id), {
        preserveScroll: true,
        onSuccess: () => closeModal(), // cerrar modal cuando borró ok
    })
}

// Cerrar el modal
const closeModal = () => {
    // Seleccionar el elemento por su clase y eliminarlo
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
        txtcodigo.value.focus(); // Si agregar es true, enfoca el txtcodigo
    } else {
        txtdetalle.value.focus(); // Si agregar es false, enfoca el txtdetalle
    }
};

// Establecer el foco al montar el componente (solo campo MODIFICAR????)
onMounted(() => {
    setFocus();
});

// Opcional: Observar cambios en la prop agregar (si la prop puede cambiar después de la carga) - (solo campo AGREGAR????)
watch(() => props.agregar, () => {
    setFocus();
});

</script>

<template>
    <!-- < form id="formRubros" name="formRubros" method="post" :action="determineActionRoute()" enctype="multipart/form-data"> -->
    <form @submit.prevent="submit">
        <!-- <meta name="csrf-token" content="{{ csrf_token() }}"> -->
        <div class="container-xxl flex-grow-1 container-p-y">
            <div class="app-ecommerce">
                <!-- HEAD Y BOTONES -->
                <FormHeader
                    :agregar="agregar"
                    :edicion="edicion"
                    :form-id="form.id"
                    titulo="Sicoss - Obras sociales"
                    :ruta-create="route('sicoss.obras.create')"
                    :ruta-edit="form.id ? route('sicoss.obras.edit', form.id) : null"
                    :ruta-first="route('sicoss.obras.first')"
                    :ruta-previous="form.id ? route('sicoss.obras.previous', form.id) : null"
                    :ruta-next="form.id ? route('sicoss.obras.next', form.id) : null"
                    :ruta-last="route('sicoss.obras.last')"
                    :ruta-search="route('sicoss.obras.search')"
                    :ruta-index="route('sicoss.obras.index')"
                    :on-submit="submit"
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
                                                <span class="ri-user-line ri-20px d-sm-none"></span
                                                ><span class="d-none d-sm-block">Información Principal</span>
                                            </button>
                                        </li>
                                    </ul>
                                </div>

                                <div class="tab-content">
                                    <div class="tab-pane fade active show" id="form-tabs-personal" role="tabpanel">

                                        <!-- MENSAJES DE ERRORES -->
                                        <!-- @ if ($errors->any())
                                            <div class="alert alert-danger">
                                                <ul>
                                                    @ foreach ($errors->all() as $error)
                                                        <li>{{ $error }}</li>
                                                    @ endforeach
                                                </ul>
                                            </div>
                                        @ endif -->

                                        <div class="row g-6">
                                            <div class="col-md-2">
                                                <div class="form-floating form-floating-outline">
                                                    <TextInput
                                                        id="codigo"
                                                        name="codigo"
                                                        type="text"
                                                        v-model="form.codigo"
                                                        ref="txtcodigo"
                                                        v-bind:disabled="!agregar"
                                                        autocomplete="off"
                                                        placeholder="6 caracteres max"
                                                        maxlength="6"
                                                    />

                                                    <label for="codigo">Código</label>

                                                    <InputError class="mt-2" :message="form.errors.codigo" />
                                                </div>

                                            </div>

                                            <div class="row mt-6 col-md-12">
                                                <div class="col-md-8    ">
                                                    <div class="form-floating form-floating-outline">
                                                        <input
                                                            type="text"
                                                            id="detalle" name="detalle" ref="txtdetalle"
                                                            class="form-control"
                                                            placeholder="Nombre de la obra social"
                                                            autocomplete="off"
                                                            v-bind:disabled="!edicion"
                                                            v-model="form.detalle"/>

                                                        <label for="detalle">Detalle *</label>

                                                        <InputError class="mt-2" :message="form.errors.detalle" />
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
                            <a href="/import-rubros" type="button" class="btn btn-label-success waves-effect">
                                <span class="tf-icons ri-file-excel-2-line ri-16px me-2"></span>Importar desde excel
                            </a>

                        </div>
                    </div>
                </div>

            </div>
        </div>
    </form>


    <!-- MODAL -->
    <div class="col-lg-4 col-md-3">
        <!-- <small class="text-light fw-medium">Backdrop</small> -->
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

    <!-- Modal -->
    <!-- <div class="modal fade" id="youTubeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
            <iframe height="350" src="https://www.youtube.com/embed/EngW7tLk6R8"></iframe>
            </div>
        </div>
    </div> -->
</template>
