<script setup>
// import InputError from '@/Components/InputError.vue';
// import InputLabel from '@/Components/InputLabel.vue';
// import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { reactive, ref, onMounted, watch } from 'vue';
import { PAISES } from '@/Data/paises';

// Recibir la prop agregar desde Laravel
const props = defineProps({
    legajo: {
      type: Object,
      required: true
    },
    agregar: {
      type: Boolean,
      required: true
    },
    edicion: {
      type: Boolean,
      required: true
    },
    provincias: {
        type: Array,
        required: true,
    },
    grupos: {
        type: Array,
        required: true,
    },
    jerarquias: {
        type: Array,
        required: true,
    },
    categorias: {
        type: Array,
        required: true,
    },
    ccostos: {
        type: Array,
        required: true,
    },
    sectores: {
        type: Array,
        required: true,
    },
    cuadrillas: {
        type: Array,
        required: true,
    },
    obras: {
        type: Array,
        required: true,
    },
    sindicatos: {
        type: Array,
        required: true,
    },
    situacionesLab: {
        type: Array,
        required: true,
    },
    convenios: {
        type: Array,
        required: true,
    },
    contrataciones: {
        type: Object,
        required: true,
    }
});

// Accediendo a las props pasadas desde el controlador
const user = usePage().props.legajo;

const form = useForm({
    // id: user.id,
    id: props.legajo?.id || null,
    codigo: user.codigo,
    cuil: user.cuil,
    detalle: user.detalle,
    nombres: user.nombres,
    nacionali: props.legajo?.nacionali ?? '',
    provin: props.legajo?.provin ?? '',
    salud: props.legajo?.salud ?? '',
    sexo: props.legajo?.sexo ?? '',
    grupo_emp: props.legajo?.grupo_emp ?? '',
    cod_centro: props.legajo?.cod_centro ?? '',
    cod_jerarq: props.legajo?.cod_jerarq ?? '',
    codsector: props.legajo?.codsector ?? '',
    cuadrilla: props.legajo?.cuadrilla ?? '',
    cod_obsoc: props.legajo?.cod_obsoc ?? '',
    cod_sindic: props.legajo?.cod_sindic ?? '',
    situacion: props.legajo?.situacion ?? '',
    cod_contra: props.legajo?.cod_contra ?? '',
    jornada_id: props.legajo?.jornada_id ?? '',
    convenio: props.legajo?.convenio ?? '',
    bruto: props.legajo?.bruto ?? 0,
    bruto_azul: props.legajo?.bruto_azul ?? 0,
    sicoss_activ: props.legajo?.sicoss_activ ?? '',
    sicoss_condi: props.legajo?.sicoss_condi ?? '',
    sicoss_modal: props.legajo?.sicoss_modal ?? '',
    sicoss_situa: props.legajo?.sicoss_situa ?? '',
    sicoss_ooss: props.legajo?.sicoss_ooss ?? '',
    sicoss_zona: props.legajo?.sicoss_zona ?? '',
    sicoss_sini: props.legajo?.sicoss_sini ?? '',
});

// Función para determinar la ruta del formulario
const determineActionRoute = () => {
  if (props.agregar) {
    //console.log(usePage().props.agregar)
    return '/legajo/add';
  } else if (props.edicion) {
    return `/legajo/edit/${form.id}`;
  } else {
    return '/legajo'; // Ruta predeterminada o de error
  }
};

const errors = usePage().props.errors;

// Funcion para grabar datos
const submit = () => {
    let ruta = 'bajas.update';

    if (usePage().props.agregar) {
        ruta = 'bajas.add';

        form.put(route(ruta), {
            onSuccess: (response) => {
                // Manejar el éxito (por ejemplo, limpiar el formulario, mostrar un mensaje, etc.)

                //alert(response)

                //console.log(form);

                //console.log(response.data);
            },
            onError: () => {
                // Manejar los errores de validación
            }
        });
    } else {
        form.patch(route(ruta, form.id), {
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
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-6 row-gap-4">
                    <!-- Sección de agregar -->
                    <div v-if="agregar" class="d-flex flex-column justify-content-center">
                        <h4 class="mb-1">Agregar empleado de baja</h4>
                    </div>
                    <!-- Sección de edición -->
                    <div v-if="!agregar && edicion" class="d-flex flex-column justify-content-center">
                        <h4 class="mb-1">Modificar empleado de baja</h4>
                    </div>
                    <!-- Si ni agregar ni edicion están activados, muestra este mensaje -->
                    <div v-if="!agregar && !edicion" class="d-flex flex-column justify-content-center">
                        <h4 class="mb-1">Empleados de baja</h4>
                    </div>

                    <div class="d-flex flex-column justify-content-center" v-if="!agregar && !edicion && form.id">
                        <div class="btn-group" role="group" aria-label="First group">
                            <Link
                                :href="route('bajas.first')"
                                class="btn btn-outline-secondary waves-effect"
                                data-bs-toggle="tooltip"
                                data-bs-placement="top"
                                data-bs-original-title="ir al primer registro"
                                >
                                <i class="ri-arrow-left-double-line"></i>
                            </Link>
                            <Link
                                :href="route('bajas.previous', form.id)"
                                class="btn btn-outline-secondary waves-effect"
                                data-bs-toggle="tooltip"
                                data-bs-placement="top"
                                data-bs-original-title="ir al registro anterior"
                                >
                                <i class="ri-arrow-left-line"></i>
                            </Link>
                            <Link
                                :href="route('bajas.next', form.id)"
                                class="btn btn-outline-secondary waves-effect"
                                data-bs-toggle="tooltip"
                                data-bs-placement="top"
                                data-bs-original-title="ir al registro siguiente"
                                >
                                <i class="ri-arrow-right-line"></i>
                            </Link>
                            <Link
                                :href="route('bajas.last')"
                                class="btn btn-outline-secondary waves-effect"
                                data-bs-toggle="tooltip"
                                data-bs-placement="top"
                                data-bs-original-title="ir al ultimo registro"
                                >
                                <i class="ri-arrow-right-double-fill"></i>
                            </Link>
                            <a type="button" href="/bajas/search" class="btn btn-outline-secondary waves-effect" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Buscar ...">
                                <i class="ri-search-line"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Botones de agregar/Grabar -->
                    <div v-if="agregar || edicion" class="d-flex align-content-center flex-wrap gap-4">
                        <button type="submit" class="btn btn-primary">Grabar</button>
                        <!-- <a href="/bajas" class="btn btn-outline-secondary">Cancelar 1</a> -->

                        <Link
                            v-if="agregar || edicion"
                            :href="route('bajas')"
                            class="btn btn-outline-secondary"
                            >
                            Cancelar
                        </Link>
                    </div>
                    <!-- Botones de CRUD -->
                    <div v-else class="d-flex align-content-center flex-wrap gap-4">
                        <Link
                            :href="route('bajas.create')"
                            class="btn btn-info waves-effect waves-light"
                            >
                            Restaurar
                        </Link>
                        <!-- <a href="/bajas/edit/{{form.id}}" class="btn btn-outline-secondary">Modificar2</a> -->
                    </div>
                </div>
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
                                        <li class="nav-item">
                                            <button
                                                class="nav-link"
                                                data-bs-toggle="tab"
                                                data-bs-target="#form-tabs-categorias"
                                                role="tab"
                                                aria-selected="false">
                                                <span class="ri-folder-user-line ri-20px d-sm-none"></span
                                                ><span class="d-none d-sm-block">Categorización</span>
                                            </button>
                                        </li>
                                        <li class="nav-item">
                                            <button
                                                class="nav-link"
                                                data-bs-toggle="tab"
                                                data-bs-target="#form-tabs-sicoss"
                                                role="tab"
                                                aria-selected="false">
                                                <span class="ri-file-line ri-20px d-sm-none"></span
                                                ><span class="d-none d-sm-block">Sicoss</span>
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

                                                    <label for="codigo">N° legajo</label>

                                                    <InputError class="mt-2" :message="form.errors.codigo" />
                                                </div>

                                            </div>

                                            <div class="col-md-3">
                                                <div class="form-floating form-floating-outline">
                                                    <TextInput
                                                        id="cuil"
                                                        name="cuil"
                                                        type="text"
                                                        v-model="form.cuil"
                                                        ref="txtcuil"
                                                        v-bind:disabled="!agregar"
                                                        autocomplete="off"
                                                        placeholder="99-99999999-9"
                                                        maxlength="13"
                                                    />

                                                    <label for="cuil">CUIL</label>

                                                    <InputError class="mt-2" :message="form.errors.cuil" />
                                                </div>

                                            </div>

                                            <div class="row mt-6 col-md-12">
                                                <div class="col-md-6">
                                                    <div class="form-floating form-floating-outline">
                                                        <input
                                                            type="text"
                                                            id="detalle" name="detalle" ref="txtdetalle"
                                                            class="form-control"
                                                            placeholder="Descripcion"
                                                            autocomplete="off"
                                                            v-bind:disabled="!edicion"
                                                            v-model="form.detalle"/>

                                                        <label for="detalle">Apellidos *</label>

                                                        <InputError class="mt-2" :message="form.errors.detalle" />
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="form-floating form-floating-outline">
                                                        <input
                                                            type="text"
                                                            id="nombres" name="nombres" ref="txtnombres"
                                                            class="form-control"
                                                            placeholder="Descripcion"
                                                            autocomplete="off"
                                                            v-bind:disabled="!edicion"
                                                            v-model="form.nombres"/>

                                                        <label for="nombres">Nombres *</label>

                                                        <InputError class="mt-2" :message="form.errors.nombres" />
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-2 select2-primary">
                                                <div class="form-floating form-floating-outline">
                                                <input
                                                    type="date"
                                                    id="alta"
                                                    name="alta"
                                                    class="form-control"
                                                    placeholder="dd/mm/aaaa"
                                                    maxlength="15" autocomplete=""
                                                    v-bind:disabled="!edicion"
                                                    v-model="form.alta" />
                                                <label for="alta">Fecha de alta</label>
                                                </div>
                                            </div>
                                            <div class="col-md-1 select2-primary">
                                                <div class="form-floating {{ $outline }}">
                                                <input type="text" id="antiguedad" name="antiguedad" class="form-control" placeholder="Codigo contable" maxlength="25" autocomplete=""
                                                    v-model="form.antiguedad" />
                                                <label for="antiguedad">Antiguedad</label>
                                                </div>
                                            </div>

                                            <div class="col-md-2 select2-primary">
                                                <div class="form-floating form-floating-outline">
                                                <input
                                                    type="date"
                                                    id="fecha_naci"
                                                    name="fecha_naci"
                                                    class="form-control"
                                                    placeholder="dd/mm/aaaa"
                                                    maxlength="15" autocomplete=""
                                                    v-bind:disabled="!edicion"
                                                    v-model="form.fecha_naci" />
                                                <label for="fecha_naci">Fecha nacimiento</label>
                                                </div>
                                            </div>
                                            <div class="col-md-1 select2-primary">
                                                <div class="form-floating {{ $outline }}">
                                                <input type="text" id="edad" name="edad" class="form-control" placeholder="Edad" maxlength="12" autocomplete=""
                                                    v-model="form.edad" />
                                                <label for="edad">Edad</label>
                                                </div>
                                            </div>

                                            <div class="row"></div>

                                            <div class="col-md-3">
                                                <div class="form-floating form-floating-outline">
                                                    <select
                                                        id="est_civil"
                                                        name="est_civil"
                                                        class="select2 form-select browser-default"
                                                        v-bind:disabled="!edicion"
                                                        v-model="form.est_civil">

                                                        <option disabled value="S">Soltero(a)</option>
                                                        <option value="C">Casado(a)</option>
                                                        <option value="V">Viudo(a)</option>
                                                        <option value="D">Divorciado(a)</option>
                                                        <option value="O">Otro(a)</option>

                                                    </select>
                                                    <label for="est_civil">Estado Civil</label>
                                                    <div class="red-text" v-if="form.errors.est_civil">
                                                        {{ form.errors.est_civil }}
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-3">
                                                <div class="form-floating form-floating-outline">
                                                    <select
                                                        id="salud"
                                                        name="salud"
                                                        class="select2 form-select browser-default"
                                                        v-bind:disabled="!edicion"
                                                        v-model="form.salud">

                                                        <option disabled value="N">Normal</option>
                                                        <option value="I">Incapacitado(a)</option>

                                                    </select>
                                                    <label for="salud">Salud</label>
                                                    <div class="red-text" v-if="form.errors.salud">
                                                        {{ form.errors.salud }}
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-3">
                                                <div class="form-floating form-floating-outline">
                                                    <select
                                                        id="sexo"
                                                        name="sexo"
                                                        class="select2 form-select browser-default"
                                                        v-bind:disabled="!edicion"
                                                        v-model="form.sexo">

                                                        <option disabled value="M">Masculino</option>
                                                        <option value="F">Femenino</option>
                                                        <option value="O">Otro</option>

                                                    </select>
                                                    <label for="sexo">Género</label>

                                                    <div class="invalid-feedback" v-if="form.errors.sexo">
                                                        {{ form.errors.sexo }}
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row"></div>

                                            <div class="col-md-4">
                                                <div class="form-floating form-floating-outline">
                                                <select
                                                    id="nacionali"
                                                    name="nacionali"
                                                    class="select2 form-select"
                                                    data-allow-clear="true"
                                                    v-bind:disabled="!edicion"
                                                    v-model="form.nacionali">

                                                    <option disabled value="">(Seleccione una nacionalidad)</option>
                                                    <option v-for="pais in PAISES"
                                                        :key="pais"
                                                        :value="pais"
                                                    >
                                                        {{ pais }}
                                                    </option>
                                                </select>
                                                <label for="nacionali">País</label>
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="form-floating form-floating-outline">
                                                    <select
                                                        id="provin"
                                                        name="provin"
                                                        class="select2 form-select browser-default"
                                                        v-bind:disabled="!edicion"
                                                        v-model="form.provin">

                                                        <option disabled value="">(Seleccione una provincia)</option>
                                                        <option
                                                            v-for="p in provincias"
                                                            :key="p.codigo"
                                                            :value="p.codigo"
                                                        >
                                                            {{ p.detalle }}
                                                        </option>
                                                    </select>
                                                    <label for="provin">Provincia</label>
                                                    <div class="red-text" v-if="form.errors.provin">
                                                        {{ form.errors.provin }}
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="form-floating form-floating-outline">
                                                    <input
                                                        type="text"
                                                        id="locali" name="locali" ref="txtdomici"
                                                        class="form-control"
                                                        placeholder="Descripcion"
                                                        autocomplete="off"
                                                        v-bind:disabled="!edicion"
                                                        v-model="form.locali"/>

                                                    <label for="locali">Localidad</label>

                                                    <InputError class="mt-2" :message="form.errors.locali" />
                                                </div>
                                            </div>

                                            <div class="row"></div>

                                            <div class="col-md-6">
                                                <div class="form-floating form-floating-outline">
                                                    <input
                                                        type="text"
                                                        id="domici" name="domici" ref="txtdomici"
                                                        class="form-control"
                                                        placeholder="Descripcion"
                                                        autocomplete="off"
                                                        v-bind:disabled="!edicion"
                                                        v-model="form.domici"/>

                                                    <label for="domici">Domicilio *</label>

                                                    <InputError class="mt-2" :message="form.errors.domici" />
                                                </div>
                                            </div>

                                            <div class="col-md-1">
                                                <div class="form-floating form-floating-outline">
                                                    <input
                                                        type="text"
                                                        id="nro" name="nro" ref="txtnro"
                                                        class="form-control"
                                                        placeholder="Descripcion"
                                                        autocomplete="off"
                                                        v-bind:disabled="!edicion"
                                                        v-model="form.nro"/>

                                                    <label for="nro">Nro</label>

                                                    <InputError class="mt-2" :message="form.errors.nro" />
                                                </div>
                                            </div>

                                            <div class="col-md-1">
                                                <div class="form-floating form-floating-outline">
                                                    <input
                                                        type="text"
                                                        id="piso" name="piso" ref="txtpiso"
                                                        class="form-control"
                                                        placeholder="Descripcion"
                                                        autocomplete="off"
                                                        v-bind:disabled="!edicion"
                                                        v-model="form.piso"/>

                                                    <label for="piso">Piso</label>

                                                    <InputError class="mt-2" :message="form.errors.piso" />
                                                </div>
                                            </div>

                                            <div class="col-md-1">
                                                <div class="form-floating form-floating-outline">
                                                    <input
                                                        type="text"
                                                        id="dpto" name="dpto" ref="txtdpto"
                                                        class="form-control"
                                                        placeholder="Descripcion"
                                                        autocomplete="off"
                                                        v-bind:disabled="!edicion"
                                                        v-model="form.dpto"/>

                                                    <label for="dpto">Departamento</label>

                                                    <InputError class="mt-2" :message="form.errors.dpto" />
                                                </div>
                                            </div>

                                            <div class="row"></div>

                                            <div class="col-md-4">
                                                <div class="form-floating form-floating-outline">
                                                    <input
                                                        type="text"
                                                        id="tel1" name="tel1" ref="txttel1"
                                                        class="form-control"
                                                        placeholder="Nº telefonico"
                                                        autocomplete="off"
                                                        v-bind:disabled="!edicion"
                                                        v-model="form.tel1"/>

                                                    <label for="tel1">Telefono 1</label>

                                                    <InputError class="mt-2" :message="form.errors.tel1" />
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="form-floating form-floating-outline">
                                                    <input
                                                        type="text"
                                                        id="tel2" name="tel2" ref="txttel2"
                                                        class="form-control"
                                                        placeholder="Nº telefonico"
                                                        autocomplete="off"
                                                        v-bind:disabled="!edicion"
                                                        v-model="form.tel2"/>

                                                    <label for="tel2">Telefono 2</label>

                                                    <InputError class="mt-2" :message="form.errors.tel2" />
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="form-floating form-floating-outline">
                                                    <input
                                                        type="text"
                                                        id="tel3" name="tel3" ref="txttel3"
                                                        class="form-control"
                                                        placeholder="Nº telefonico"
                                                        autocomplete="off"
                                                        v-bind:disabled="!edicion"
                                                        v-model="form.tel3"/>

                                                    <label for="tel3">Telefono 3</label>

                                                    <InputError class="mt-2" :message="form.errors.tel3" />
                                                </div>
                                            </div>

                                            <div class="row"></div>

                                            <div class="col-md-4">
                                                <div class="form-floating form-floating-outline">
                                                    <input
                                                        type="text"
                                                        id="email" name="email" ref="txtemail"
                                                        class="form-control"
                                                        placeholder="Correo electrónico"
                                                        autocomplete="off"
                                                        v-bind:disabled="!edicion"
                                                        v-model="form.email"/>

                                                    <label for="email">Correo electrónico</label>

                                                    <InputError class="mt-2" :message="form.errors.email" />
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="form-floating form-floating-outline">
                                                    <input
                                                        type="text"
                                                        id="web" name="web" ref="txtweb"
                                                        class="form-control"
                                                        placeholder="https://ejemplo.com"
                                                        autocomplete="off"
                                                        v-bind:disabled="!edicion"
                                                        v-model="form.web"/>

                                                    <label for="web">Página Web</label>

                                                    <InputError class="mt-2" :message="form.errors.web" />
                                                </div>
                                            </div>


                                            <div class="col-md-6">
                                                <div class="form-floating form-floating-outline">
                                                    <br>
                                                </div>
                                            </div>
                                        </div>

                                    </div>

                                    <div class="tab-pane fade active show" id="form-tabs-categorias" role="tabpanel">

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
                                            <div class="col-md-4">
                                                <div class="form-floating form-floating-outline">
                                                <select
                                                    id="grupo_emp"
                                                    name="grupo_emp"
                                                    class="select2 form-select"
                                                    data-allow-clear="true"
                                                    v-bind:disabled="!edicion"
                                                    v-model="form.grupo_emp">

                                                    <option disabled value="">(Seleccione una empresa)</option>
                                                    <option
                                                            v-for="p in grupos"
                                                            :key="p.codigo"
                                                            :value="p.codigo"
                                                        >
                                                            {{ p.codigo }} - {{ p.detalle }}
                                                    </option>
                                                </select>
                                                <label for="grupo_emp">Grupo empresario</label>
                                                </div>
                                            </div>

                                            <div class="row"></div>


                                            <div class="col-md-4">
                                                <div class="form-floating form-floating-outline">
                                                <select
                                                    id="cod_centro"
                                                    name="cod_centro"
                                                    class="select2 form-select"
                                                    data-allow-clear="true"
                                                    v-bind:disabled="!edicion"
                                                    v-model="form.cod_centro">

                                                    <option disabled value="">(Seleccione un centro de costo)</option>
                                                    <option
                                                            v-for="p in ccostos"
                                                            :key="p.codigo"
                                                            :value="p.codigo"
                                                        >
                                                            {{ p.codigo }} - {{ p.detalle }}
                                                    </option>
                                                </select>
                                                <label for="cod_centro">Centro de costo</label>
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="form-floating form-floating-outline">
                                                <select
                                                    id="cod_jerarq"
                                                    name="cod_jerarq"
                                                    class="select2 form-select"
                                                    data-allow-clear="true"
                                                    v-bind:disabled="!edicion"
                                                    v-model="form.cod_jerarq">

                                                    <option disabled value="">(Seleccione un grupo jerarquico)</option>
                                                    <option
                                                            v-for="p in jerarquias"
                                                            :key="p.codigo"
                                                            :value="p.codigo"
                                                        >
                                                            {{ p.codigo }} - {{ p.detalle }}
                                                    </option>
                                                </select>
                                                <label for="cod_jerarq">Jerarquia</label>
                                                </div>
                                            </div>


                                            <div class="row"></div>

                                            <div class="col-md-4">
                                                <div class="form-floating form-floating-outline">
                                                <select
                                                    id="codsector"
                                                    name="codsector"
                                                    class="select2 form-select"
                                                    data-allow-clear="true"
                                                    v-bind:disabled="!edicion"
                                                    v-model="form.codsector">

                                                    <option disabled value="">(Seleccione un sector)</option>
                                                    <option
                                                            v-for="p in sectores"
                                                            :key="p.codigo"
                                                            :value="p.codigo"
                                                        >
                                                            {{ p.codigo }} - {{ p.detalle }}
                                                    </option>
                                                </select>
                                                <label for="codsector">Sector</label>
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-floating form-floating-outline">
                                                    <input
                                                        type="text"
                                                        id="tarea" name="tarea" ref="txttarea"
                                                        class="form-control"
                                                        placeholder="Descripcion"
                                                        autocomplete="off"
                                                        v-bind:disabled="!edicion"
                                                        v-model="form.tarea"/>

                                                    <label for="tarea">Tarea</label>

                                                    <InputError class="mt-2" :message="form.errors.tarea" />
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="form-floating form-floating-outline">
                                                <select
                                                    id="cuadrilla"
                                                    name="cuadrilla"
                                                    class="select2 form-select"
                                                    data-allow-clear="true"
                                                    v-bind:disabled="!edicion"
                                                    v-model="form.cuadrilla">

                                                    <option disabled value="">(Seleccione una cuadrilla)</option>
                                                    <option
                                                            v-for="p in cuadrillas"
                                                            :key="p.codigo"
                                                            :value="p.codigo"
                                                        >
                                                            {{ p.codigo }} - {{ p.detalle }}
                                                    </option>
                                                </select>
                                                <label for="cuadrilla">Cuadrilla</label>
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="form-floating form-floating-outline">
                                                <select
                                                    id="cod_obsoc"
                                                    name="cod_obsoc"
                                                    class="select2 form-select"
                                                    data-allow-clear="true"
                                                    v-bind:disabled="!edicion"
                                                    v-model="form.cod_obsoc">

                                                    <option disabled value="">(Seleccione una obra social)</option>
                                                    <option
                                                            v-for="p in obras"
                                                            :key="p.codigo"
                                                            :value="p.codigo"
                                                        >
                                                            {{ p.codigo }} - {{ p.detalle }}
                                                    </option>
                                                </select>
                                                <label for="cod_obsoc">Obra Social</label>
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="form-floating form-floating-outline">
                                                <select
                                                    id="cod_sindic"
                                                    name="cod_sindic"
                                                    class="select2 form-select"
                                                    data-allow-clear="true"
                                                    v-bind:disabled="!edicion"
                                                    v-model="form.cod_sindic">

                                                    <option disabled value="">(Seleccione un sindicato)</option>
                                                    <option
                                                            v-for="p in sindicatos"
                                                            :key="p.codigo"
                                                            :value="p.codigo"
                                                        >
                                                            {{ p.codigo }} - {{ p.detalle }}
                                                    </option>
                                                </select>
                                                <label for="cod_sindic">Sindicato</label>
                                                </div>
                                            </div>


                                            <div class="col-md-4">
                                                <div class="form-floating form-floating-outline">
                                                <select
                                                    id="situacion"
                                                    name="situacion"
                                                    class="select2 form-select"
                                                    data-allow-clear="true"
                                                    v-bind:disabled="!edicion"
                                                    v-model="form.situacion">

                                                    <option disabled value="">(Situación de contratación)</option>
                                                    <option
                                                            v-for="p in situacionesLab"
                                                            :key="p.codigo"
                                                            :value="p.codigo"
                                                        >
                                                            {{ p.codigo }} - {{ p.detalle }}
                                                    </option>
                                                </select>
                                                <label for="situacion">Situación</label>
                                                </div>
                                            </div>


                                            <div class="row"></div>

                                            <div class="col-md-3">
                                                <div class="form-floating form-floating-outline">
                                                <select
                                                    id="cod_contra"
                                                    name="cod_contra"
                                                    class="select2 form-select"
                                                    data-allow-clear="true"
                                                    v-bind:disabled="!edicion"
                                                    v-model="form.cod_contra">

                                                    <option disabled value="">(Seleccione el tipo de contrato)</option>
                                                    <option
                                                            v-for="p in contrataciones"
                                                            :key="p.codigo"
                                                            :value="p.codigo"
                                                        >
                                                            {{ p.codigo }} - {{ p.detalle }}
                                                    </option>
                                                </select>
                                                <label for="cod_contra">Tipo de contrato</label>
                                                </div>
                                            </div>

                                            <div class="col-md-2 select2-primary">
                                                <div class="form-floating form-floating-outline">
                                                <input
                                                    type="date"
                                                    id="fecha_vto"
                                                    name="fecha_vto"
                                                    class="form-control"
                                                    placeholder="dd/mm/aaaa"
                                                    maxlength="15" autocomplete=""
                                                    v-bind:disabled="!edicion"
                                                    v-model="form.fecha_vto" />
                                                <label for="fecha_vto">Vencimiento contrato</label>
                                                </div>
                                            </div>


                                            <div class="col-md-3">
                                                <div class="form-floating form-floating-outline">
                                                <select
                                                    id="jornada_id"
                                                    name="jornada_id"
                                                    class="select2 form-select"
                                                    data-allow-clear="true"
                                                    v-bind:disabled="!edicion"
                                                    v-model="form.jornada_id">

                                                    <option disabled value="">(Seleccione el tipo de Jornada)</option>
                                                    <option value="1" selected="">Completa</option>
                                                    <option value="2">Media jornada</option>
                                                </select>
                                                <label for="jornada_id">Jornada laboral</label>
                                                </div>
                                            </div>

                                            <div class="col-md-3">
                                                <div class="form-floating form-floating-outline">
                                                <select
                                                    id="convenio"
                                                    name="convenio"
                                                    class="select2 form-select"
                                                    data-allow-clear="true"
                                                    v-bind:disabled="!edicion"
                                                    v-model="form.convenio">

                                                    <option disabled value="">(Seleccione el convenio colectivo)</option>
                                                    <option
                                                            v-for="p in convenios"
                                                            :key="p.codigo"
                                                            :value="p.codigo"
                                                        >
                                                            {{ p.codigo }} - {{ p.detalle }}
                                                    </option>
                                                </select>
                                                <label for="convenio">Convenio colectivo</label>
                                                </div>
                                            </div>

                                            <div class="row"></div>

                                            <div class="col-md-3">
                                                <div class="form-floating form-floating-outline">
                                                <select
                                                    id="cod_categ"
                                                    name="cod_categ"
                                                    class="select2 form-select"
                                                    data-allow-clear="true"
                                                    v-bind:disabled="!edicion"
                                                    v-model="form.cod_categ">

                                                    <option disabled value="">(Seleccione la categoria)</option>
                                                    <option
                                                            v-for="p in categorias"
                                                            :key="p.codigo"
                                                            :value="p.codigo"
                                                        >
                                                            {{ p.codigo }} - {{ p.detalle }}
                                                    </option>
                                                </select>
                                                <label for="cod_categ">Categoría</label>
                                                </div>
                                            </div>

                                            <div class="col-md-2">
                                                <div class="form-floating form-floating-outline">
                                                    <input
                                                        type="text"
                                                        id="bruto" name="bruto" ref="txtbruto"
                                                        class="form-control"
                                                        placeholder="Sueldo bruto"
                                                        autocomplete="off"
                                                        v-bind:disabled="!edicion"
                                                        v-model="form.bruto"/>

                                                    <label for="bruto">Sueldo Bruto</label>

                                                    <InputError class="mt-2" :message="form.errors.bruto" />
                                                </div>
                                            </div>

                                            <div class="col-md-2">
                                                <div class="form-floating form-floating-outline">
                                                    <input
                                                        type="text"
                                                        id="bruto_azul" name="bruto_azul" ref="txtbruto_azul"
                                                        class="form-control"
                                                        placeholder="Neto alternativo (Azul)"
                                                        autocomplete="off"
                                                        v-bind:disabled="!edicion"
                                                        v-model="form.bruto_azul"/>

                                                    <label for="bruto_azul">Neto alternativo (Azul)</label>

                                                    <InputError class="mt-2" :message="form.errors.bruto_azul" />
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-floating form-floating-outline">
                                                    <br>
                                                </div>
                                            </div>
                                        </div>

                                    </div>

                                    <div class="tab-pane fade active show" id="form-tabs-sicoss" role="tabpanel">

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
                                            <div class="col-md-6">
                                                <div class="form-floating form-floating-outline">
                                                    <input
                                                        type="text"
                                                        id="tarea" name="tarea" ref="txttarea"
                                                        class="form-control"
                                                        placeholder="Situacion de revista"
                                                        autocomplete="off"
                                                        v-bind:disabled="!edicion"
                                                        v-model="form.tarea"/>

                                                    <label for="tarea">Situacion de revista</label>

                                                    <InputError class="mt-2" :message="form.errors.tarea" />
                                                </div>
                                            </div>

                                            <div class="row"></div>
                                            <div class="col-md-6">
                                                <div class="form-floating form-floating-outline">
                                                <select
                                                    id="cod_centro"
                                                    name="cod_centro"
                                                    class="select2 form-select"
                                                    data-allow-clear="true"
                                                    v-bind:disabled="!edicion"
                                                    v-model="form.cod_centro">

                                                    <option disabled value="">(Seleccione una condición)</option>
                                                    <option
                                                            v-for="p in ccostos"
                                                            :key="p.codigo"
                                                            :value="p.codigo"
                                                        >
                                                            {{ p.codigo }} - {{ p.detalle }}
                                                    </option>
                                                </select>
                                                <label for="cod_centro">Condición</label>
                                                </div>
                                            </div>

                                            <div class="row"></div>
                                            <div class="col-md-6">
                                                <div class="form-floating form-floating-outline">
                                                <select
                                                    id="sicoss_activ"
                                                    name="sicoss_activ"
                                                    class="select2 form-select"
                                                    data-allow-clear="true"
                                                    v-bind:disabled="!edicion"
                                                    v-model="form.sicoss_activ">

                                                    <option disabled value="">(Seleccione una actividad)</option>
                                                    <option
                                                            v-for="p in grupos"
                                                            :key="p.codigo"
                                                            :value="p.codigo"
                                                        >
                                                            {{ p.codigo }} - {{ p.detalle }}
                                                    </option>
                                                </select>
                                                <label for="sicoss_activ">01 - Actividad</label>
                                                </div>
                                            </div>

                                            <div class="row"></div>
                                            <div class="col-md-6">
                                                <div class="form-floating form-floating-outline">
                                                <select
                                                    id="cod_jerarq"
                                                    name="cod_jerarq"
                                                    class="select2 form-select"
                                                    data-allow-clear="true"
                                                    v-bind:disabled="!edicion"
                                                    v-model="form.cod_jerarq">

                                                    <option disabled value="">(Seleccione la modalidad)</option>
                                                    <option
                                                            v-for="p in jerarquias"
                                                            :key="p.codigo"
                                                            :value="p.codigo"
                                                        >
                                                            {{ p.codigo }} - {{ p.detalle }}
                                                    </option>
                                                </select>
                                                <label for="cod_jerarq">08 - Modalidad de Contratacion</label>
                                                </div>
                                            </div>

                                            <div class="row"></div>
                                            <div class="col-md-6">
                                                <div class="form-floating form-floating-outline">
                                                <select
                                                    id="sicoss_sini"
                                                    name="sicoss_sini"
                                                    class="select2 form-select"
                                                    data-allow-clear="true"
                                                    v-bind:disabled="!edicion"
                                                    v-model="form.sicoss_sini">

                                                    <option disabled value="">(Seleccione un cód.siniestro)</option>
                                                    <option
                                                            v-for="p in sindicatos"
                                                            :key="p.codigo"
                                                            :value="p.codigo"
                                                        >
                                                            {{ p.codigo }} - {{ p.detalle }}
                                                    </option>
                                                </select>
                                                <label for="sicoss_sini">Codigo de siniestrado</label>
                                                </div>
                                            </div>

                                            <div class="row"></div>
                                            <div class="col-md-6">
                                                <div class="form-floating form-floating-outline">
                                                <select
                                                    id="cod_obsoc"
                                                    name="cod_obsoc"
                                                    class="select2 form-select"
                                                    data-allow-clear="true"
                                                    v-bind:disabled="!edicion"
                                                    v-model="form.cod_obsoc">

                                                    <option disabled value="">(Seleccione una localidad)</option>
                                                    <option
                                                            v-for="p in obras"
                                                            :key="p.codigo"
                                                            :value="p.codigo"
                                                        >
                                                            {{ p.codigo }} - {{ p.detalle }}
                                                    </option>
                                                </select>
                                                <label for="cod_obsoc">Localidad</label>
                                                </div>
                                            </div>

                                            <div class="row"></div>
                                            <div class="col-md-6">
                                                <div class="form-floating form-floating-outline">
                                                <select
                                                    id="cuadrilla"
                                                    name="cuadrilla"
                                                    class="select2 form-select"
                                                    data-allow-clear="true"
                                                    v-bind:disabled="!edicion"
                                                    v-model="form.cuadrilla">

                                                    <option disabled value="">(Seleccione una cuadrilla)</option>
                                                    <option
                                                            v-for="p in cuadrillas"
                                                            :key="p.codigo"
                                                            :value="p.codigo"
                                                        >
                                                            {{ p.codigo }} - {{ p.detalle }}
                                                    </option>
                                                </select>
                                                <label for="cuadrilla">Obra Social</label>
                                                </div>
                                            </div>

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
                            <Link
                                type="button"
                                :href="route('bajas.destroy', form.id)"
                                class="btn btn-danger waves-effect waves-light"
                                style="color: white"
                                @click="closeModal"
                                >Borrar
                            </Link>

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
