<script setup>
// import InputError from '@/Components/InputError.vue';
// import InputLabel from '@/Components/InputLabel.vue';
// import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import FloatMultiselect from '@/Components/FloatMultiselect.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { reactive, ref, onMounted, watch, computed } from 'vue';
import { PAISES } from '@/Data/paises';
import Swal from 'sweetalert2';

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
        type: Array,
        required: true,
    },
    contratos: {
        type: Array,
        default: () => [],
    },
    jornadas: {
        type: Array,
        default: () => [],
    },
    sicossOrigen: {
        type: Object,
        default: null,
    },
    actividades: {
        type: Array,
        required: true,
    },
    condiciones: {
        type: Array,
        required: true,
    },
    zonas: {
        type: Array,
        required: true,
    },
    situaciones: {
        type: Array,
        required: true,
    },
    sinie: {
        type: Array,
        required: true,
    },
});

// Accediendo a las props pasadas desde el controlador
const user = usePage().props.legajo;

// Fecha de baja del legajo formateada dd/mm/aaaa (vacío si el empleado está activo).
const legajoBaja = computed(() => {
    const b = user?.baja;
    if (!b) return '';
    const [y, m, d] = String(b).slice(0, 10).split('-');
    return (y && m && d) ? `${d}/${m}/${y}` : String(b);
});

// Datos de la baja (solo lectura) para mostrar en el formulario cuando el empleado está de baja.
const sicossBaja = ref(user?.sicoss_baja != null ? String(user.sicoss_baja) : '');
const bajaDet = ref(user?.baja_det ?? '');

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
    obra_sijp: (props.legajo?.obra_sijp ?? '') !== ''
        ? String(props.legajo.obra_sijp).padStart(6, '0')
        : '',
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
    sicoss_conyuge:    props.legajo?.sicoss_conyuge    ?? false,
    sicoss_hijos:      props.legajo?.sicoss_hijos      ?? false,
    sicoss_adherentes: props.legajo?.sicoss_adherentes ?? 0,
    alta:        props.legajo?.alta        ?? '',
    fecha_naci:  props.legajo?.fecha_naci  ?? '',
    est_civil:   props.legajo?.est_civil   ?? '',
    antiguedad:  props.legajo?.antiguedad  ?? '',
    edad:        props.legajo?.edad        ?? '',
    domici:      props.legajo?.domici      ?? '',
    nro:         props.legajo?.nro         ?? '',
    piso:        props.legajo?.piso        ?? '',
    dpto:        props.legajo?.dpto        ?? '',
    locali:      props.legajo?.locali      ?? '',
    tel1:        props.legajo?.tel1        ?? '',
    tel2:        props.legajo?.tel2        ?? '',
    tel3:        props.legajo?.tel3        ?? '',
    email:       props.legajo?.email       ?? '',
    web:         props.legajo?.web         ?? '',
    tarea:       props.legajo?.tarea       ?? '',
    fecha_vto:   props.legajo?.fecha_vto   ?? '',
    cod_categ:   props.legajo?.cod_categ   ?? '',
    activo:      props.legajo?.activo      ?? false,
});

// ---- Importar datos SICOSS desde otro legajo del mismo CUIL (baja anterior o pluriempleo) ----
const sicossDefs = [
    { key: 'sicoss_situa', label: 'Situación de revista', zeroBlank: true },
    { key: 'sicoss_condi', label: 'Condición de contratación', zeroBlank: true },
    { key: 'sicoss_activ', label: 'Actividad', zeroBlank: true },
    { key: 'sicoss_modal', label: 'Modalidad de contratación', zeroBlank: true },
    { key: 'obra_sijp',    label: 'Obra social', zeroBlank: true },
    { key: 'sicoss_sini',  label: 'Código de siniestrado', zeroBlank: false }, // 0 = "no siniestrado" es válido
    { key: 'sicoss_zona',  label: 'Localidad', zeroBlank: true },
];

const esBlankSicoss = (v, zeroBlank) => {
    if (v === null || v === undefined || String(v).trim() === '') return true;
    if (zeroBlank && Number(v) === 0) return true;
    return false;
};

const camposSicossEnBlanco = computed(() =>
    sicossDefs.filter(d => esBlankSicoss(form[d.key], d.zeroBlank))
);

const puedeImportarSicoss = computed(() =>
    props.edicion && !!props.sicossOrigen && camposSicossEnBlanco.value.length > 0
);

const aplicarImportSicoss = (soloEnBlanco) => {
    const o = props.sicossOrigen;
    if (!o) return;
    const keys = soloEnBlanco ? camposSicossEnBlanco.value.map(d => d.key) : sicossDefs.map(d => d.key);
    keys.forEach(key => {
        if (key === 'obra_sijp') {
            form.obra_sijp = (o.obra_sijp ?? '') !== '' ? String(o.obra_sijp).padStart(6, '0') : '';
            if (o.cod_obsoc !== null && o.cod_obsoc !== undefined) form.cod_obsoc = o.cod_obsoc;
        } else {
            form[key] = o[key] ?? '';
        }
    });
};

const abrirImportarSicoss = () => {
    const o = props.sicossOrigen;
    if (!o) return;
    const fmtFecha = (d) => {
        if (!d) return '—';
        const [y, m, dd] = String(d).slice(0, 10).split('-');
        return (y && m && dd) ? `${dd}/${m}/${y}` : String(d);
    };
    const enBlanco = camposSicossEnBlanco.value.length;
    Swal.fire({
        title: 'Importar datos SICOSS',
        html: `
            <div style="text-align:left;font-size:14px;">
              <p class="mb-2">Se tomarán los datos SICOSS del legajo de origen:</p>
              <table style="width:100%;border-collapse:collapse;font-size:13px;">
                <tr><td style="padding:3px 6px;color:#6c757d;">Legajo</td><td style="padding:3px 6px;"><strong>${o.legajo}</strong></td></tr>
                <tr><td style="padding:3px 6px;color:#6c757d;">Apellido y Nombre</td><td style="padding:3px 6px;"><strong>${o.nombre || '—'}</strong></td></tr>
                <tr><td style="padding:3px 6px;color:#6c757d;">Empresa</td><td style="padding:3px 6px;"><strong>${o.empresa || '—'}</strong></td></tr>
                <tr><td style="padding:3px 6px;color:#6c757d;">Fecha de alta</td><td style="padding:3px 6px;">${fmtFecha(o.alta)}</td></tr>
                <tr><td style="padding:3px 6px;color:#6c757d;">Fecha de baja</td><td style="padding:3px 6px;">${fmtFecha(o.baja)}</td></tr>
              </table>
              <p class="mt-3 mb-0">¿Importás <strong>todos</strong> los datos SICOSS o <strong>solo los ${enBlanco} campo(s) en blanco</strong>?</p>
            </div>`,
        width: 640,
        showCancelButton: true,
        showDenyButton: true,
        confirmButtonText: 'Importar todos',
        denyButtonText: 'Solo los campos en blanco',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
        confirmButtonColor: '#696cff',
        denyButtonColor: '#03c3ec',
        focusConfirm: false,
        didOpen: () => {
            const cont = Swal.getContainer();
            if (cont) cont.style.zIndex = '99999';
            [Swal.getConfirmButton(), Swal.getDenyButton(), Swal.getCancelButton()].forEach(b => {
                if (b) b.style.fontSize = '0.85rem';
            });
        },
    }).then(result => {
        if (result.isConfirmed) {
            aplicarImportSicoss(false);
        } else if (result.isDenied) {
            aplicarImportSicoss(true);
        }
    });
};

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

// Opciones formateadas para el multiselect de obras sociales
// El código de obra social es de 6 dígitos con ceros a la izquierda (varchar(6)).
// Se normaliza con padStart por si llega como número y perdió los ceros.
const obrasOptions = computed(() =>
    props.obras.map(p => {
        const cod = String(p.codigo ?? '').padStart(6, '0');
        return { value: cod, label: `${cod} - ${p.detalle}` };
    })
);

const actividadesOptions = computed(() =>
    props.actividades.map(p => ({ value: p.codigo, label: `${p.codigo} - ${p.detalle}` }))
);

const situacionesOptions = computed(() =>
    props.situaciones.map(p => ({ value: p.codigo, label: `${p.codigo} - ${p.detalle}` }))
);

const sinieOptions = computed(() =>
    props.sinie.map(p => ({ value: p.codigo, label: `${p.codigo} - ${p.detalle}` }))
);

const contratacionesOptions = computed(() =>
    props.contrataciones.map(p => ({ value: p.codigo, label: `${p.codigo} - ${p.detalle}` }))
);

const zonasOptions = computed(() =>
    props.zonas.map(p => ({ value: p.codigo, label: `${p.codigo} - ${p.detalle}` }))
);

// Errores por tab para mostrar indicadores visuales
const tabErrors = computed(() => ({
    personal: !!(form.errors.codigo || form.errors.cuil || form.errors.detalle || form.errors.nombres ||
                 form.errors.est_civil || form.errors.salud || form.errors.sexo || form.errors.provin ||
                 form.errors.locali || form.errors.domici || form.errors.nro || form.errors.piso ||
                 form.errors.dpto || form.errors.tel1 || form.errors.tel2 || form.errors.tel3 ||
                 form.errors.email || form.errors.web),
    categorias: !!(form.errors.tarea || form.errors.obra_sijp || form.errors.bruto || form.errors.bruto_azul),
    familia: !!(form.errors.sicoss_adherentes),
    sicoss: !!(form.errors.sicoss_situa),
}));

// Navega automáticamente al primer tab que tenga errores
const goToFirstErrorTab = () => {
    const tabOrder = [
        { key: 'personal',   target: '#form-tabs-personal' },
        { key: 'categorias', target: '#form-tabs-categorias' },
        { key: 'familia',    target: '#form-tabs-cargas-familia' },
        { key: 'sicoss',     target: '#form-tabs-sicoss' },
    ];
    for (const t of tabOrder) {
        if (tabErrors.value[t.key]) {
            const btn = document.querySelector(`[data-bs-target="${t.target}"]`);
            if (btn) window.bootstrap.Tab.getOrCreateInstance(btn).show();
            break;
        }
    }
};

// Funcion para grabar datos
const submit = () => {
    let ruta = 'legajos.update';

    if (usePage().props.agregar) {
        ruta = 'legajos.add';

        form.put(route(ruta), {
            onSuccess: (response) => {
                // Manejar el éxito (por ejemplo, limpiar el formulario, mostrar un mensaje, etc.)

                //alert(response)

                //console.log(form);

                //console.log(response.data);
            },
            onError: () => {
                goToFirstErrorTab();
            }
        });
    } else {
        form.patch(route(ruta, form.id), {
            preserveScroll: true,
            onSuccess: () => {},
            onError: (errors) => {
                console.log('Errores de validación:', errors);
                goToFirstErrorTab();
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

// Se completó al menos un campo SICOSS con los defaults al entrar por #sicoss:
// muestra el cartel informativo junto a "Situación de revista".
const defaultsSicossAplicados = ref(false);

// Establecer el foco al montar el componente (solo campo MODIFICAR????)
onMounted(() => {
    setFocus();
    // Si se entró desde el modal de inconsistencias del LSD (link con #sicoss), abrir directamente la
    // pestaña SICOSS. En la edición normal (sin hash) se respeta la pestaña por defecto (Personal).
    if (window.location.hash === '#sicoss') {
        setTimeout(() => {
            const btn = document.querySelector('[data-bs-target="#form-tabs-sicoss"]');
            if (btn && window.bootstrap?.Tab) {
                window.bootstrap.Tab.getOrCreateInstance(btn).show();
            }
        }, 0);

        // Precarga de defaults para acelerar la corrección: solo los campos SICOSS que están
        // vacíos toman el valor habitual del cliente. Nada se graba hasta pulsar Actualizar.
        // Situación 1 (Activo), Condición 1 (Serv. Comunes >18), Actividad 49 (No clasificadas),
        // Modalidad 8 (Tiempo completo indeterminado), Siniestrado 0 (No siniestrado), Zona 61.
        const defaultsSicoss = [
            ['sicoss_situa', 1, true],
            ['sicoss_condi', 1, true],
            ['sicoss_activ', 49, true],
            ['sicoss_modal', 8, true],
            ['sicoss_sini', 0, false], // 0 = "No siniestrado" es un valor válido: solo se completa si está vacío
            ['sicoss_zona', 61, true],
        ];
        for (const [campo, valorDefault, zeroBlank] of defaultsSicoss) {
            if (esBlankSicoss(form[campo], zeroBlank)) {
                form[campo] = valorDefault;
                defaultsSicossAplicados.value = true;
            }
        }
    }
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
                        <h4 class="mb-1">Agregar empleado activo</h4>
                    </div>
                    <!-- Sección de edición -->
                    <div v-if="!agregar && edicion" class="d-flex flex-column justify-content-center">
                        <h4 class="mb-1">Modificar empleado activo</h4>
                    </div>
                    <!-- Si ni agregar ni edicion están activados, muestra este mensaje -->
                    <div v-if="!agregar && !edicion" class="d-flex flex-column justify-content-center">
                        <h4 class="mb-1">Empleados</h4>
                    </div>

                    <div class="d-flex flex-column justify-content-center" v-if="!agregar && !edicion && form.id">
                        <div class="btn-group" role="group" aria-label="First group">
                            <Link
                                :href="route('legajos.first')"
                                class="btn btn-outline-secondary waves-effect"
                                data-bs-toggle="tooltip"
                                data-bs-placement="top"
                                data-bs-original-title="ir al primer registro"
                                >
                                <i class="ri-arrow-left-double-line"></i>
                            </Link>
                            <Link
                                :href="route('legajos.previous', form.id)"
                                class="btn btn-outline-secondary waves-effect"
                                data-bs-toggle="tooltip"
                                data-bs-placement="top"
                                data-bs-original-title="ir al registro anterior"
                                >
                                <i class="ri-arrow-left-line"></i>
                            </Link>
                            <Link
                                :href="route('legajos.next', form.id)"
                                class="btn btn-outline-secondary waves-effect"
                                data-bs-toggle="tooltip"
                                data-bs-placement="top"
                                data-bs-original-title="ir al registro siguiente"
                                >
                                <i class="ri-arrow-right-line"></i>
                            </Link>
                            <Link
                                :href="route('legajos.last')"
                                class="btn btn-outline-secondary waves-effect"
                                data-bs-toggle="tooltip"
                                data-bs-placement="top"
                                data-bs-original-title="ir al ultimo registro"
                                >
                                <i class="ri-arrow-right-double-fill"></i>
                            </Link>
                            <a type="button" :href="route('legajos.search')" class="btn btn-outline-secondary waves-effect" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Buscar ...">
                                <i class="ri-search-line"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Botones de agregar/Grabar -->
                    <div v-if="agregar || edicion" class="d-flex align-content-center flex-wrap gap-4">
                        <button
                            type="submit"
                            class="btn"
                            :class="form.processing ? 'btn-secondary' : 'btn-primary'"
                            :disabled="form.processing"
                        >
                            <span v-if="form.processing" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            {{ form.processing ? 'Grabando...' : 'Grabar' }}
                        </button>
                        <!-- <a href="/legajos" class="btn btn-outline-secondary">Cancelar 1</a> -->

                        <Link
                            v-if="agregar || edicion"
                            :href="route('legajos.index')"
                            class="btn btn-outline-secondary"
                            >
                            Cancelar
                        </Link>
                    </div>
                    <!-- Botones de CRUD -->
                    <div v-else class="d-flex align-content-center flex-wrap gap-4">
                        <Link
                            :href="route('legajos.create')"
                            class="btn btn-info waves-effect waves-light"
                            >
                            Agregar
                        </Link>
                        <Link
                            :href="form.id ? route('legajos.edit', form.id) : '#'"
                            class="btn btn-outline-secondary"
                            @click="setFocus"
                            >
                            Modificar
                        </Link>
                        <!-- <a href="/legajos/edit/{{form.id}}" class="btn btn-outline-secondary">Modificar2</a> -->

                        <a
                            type="button"
                            class="btn waves-effect waves-light"
                            :class="legajoBaja ? 'btn-secondary disabled' : 'btn-danger'"
                            style="color: white"
                            :data-bs-toggle="legajoBaja ? null : 'modal'"
                            :data-bs-target="legajoBaja ? null : '#modalDelete'"
                            :aria-disabled="legajoBaja ? 'true' : null"
                            :tabindex="legajoBaja ? -1 : null"
                            :title="legajoBaja ? 'El empleado ya está de baja' : 'Dar de baja'"
                        >Baja</a>
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
                                                type="button"
                                                class="nav-link active"
                                                data-bs-toggle="tab"
                                                data-bs-target="#form-tabs-personal"
                                                role="tab"
                                                aria-selected="true">
                                                <span class="ri-user-line ri-20px d-sm-none"></span
                                                ><span class="d-none d-sm-block">Información Principal</span>
                                                <span v-if="tabErrors.personal" class="badge bg-danger rounded-pill ms-1" style="width:8px;height:8px;padding:0;">&nbsp;</span>
                                            </button>
                                        </li>
                                        <li class="nav-item">
                                            <button
                                                type="button"
                                                class="nav-link"
                                                data-bs-toggle="tab"
                                                data-bs-target="#form-tabs-categorias"
                                                role="tab"
                                                aria-selected="false">
                                                <span class="ri-folder-user-line ri-20px d-sm-none"></span
                                                ><span class="d-none d-sm-block">Categorización</span>
                                                <span v-if="tabErrors.categorias" class="badge bg-danger rounded-pill ms-1" style="width:8px;height:8px;padding:0;">&nbsp;</span>
                                            </button>
                                        </li>
                                        <li class="nav-item">
                                            <button
                                                type="button"
                                                class="nav-link"
                                                data-bs-toggle="tab"
                                                data-bs-target="#form-tabs-cargas-familia"
                                                role="tab"
                                                aria-selected="false">
                                                <span class="ri-folder-user-line ri-20px d-sm-none"></span
                                                ><span class="d-none d-sm-block">Familiares</span>
                                                <span v-if="tabErrors.familia" class="badge bg-danger rounded-pill ms-1" style="width:8px;height:8px;padding:0;">&nbsp;</span>
                                            </button>
                                        </li>
                                        <li class="nav-item">
                                            <button
                                                type="button"
                                                class="nav-link"
                                                data-bs-toggle="tab"
                                                data-bs-target="#form-tabs-sicoss"
                                                role="tab"
                                                aria-selected="false">
                                                <span class="ri-file-line ri-20px d-sm-none"></span
                                                ><span class="d-none d-sm-block">Sicoss</span>
                                                <span v-if="tabErrors.sicoss" class="badge bg-danger rounded-pill ms-1" style="width:8px;height:8px;padding:0;">&nbsp;</span>
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
                                                        :class="{'is-invalid': form.errors.codigo}"
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
                                                        :class="{'is-invalid': form.errors.cuil}"
                                                        v-bind:disabled="!agregar"
                                                        autocomplete="off"
                                                        placeholder="99-99999999-9"
                                                        maxlength="13"
                                                    />

                                                    <label for="cuil">CUIL</label>

                                                    <InputError class="mt-2" :message="form.errors.cuil" />
                                                </div>

                                            </div>

                                            <!-- Pastilla de baja: a la derecha, a la altura del CUIL -->
                                            <div class="col-md-7 d-flex justify-content-end align-items-center">
                                                <span v-if="legajoBaja" class="badge bg-danger rounded-pill fs-6">
                                                    <i class="ri-user-unfollow-line me-1"></i> Baja el {{ legajoBaja }}
                                                </span>
                                            </div>

                                            <div class="row mt-6 col-md-12">
                                                <div class="col-md-6">
                                                    <div class="form-floating form-floating-outline">
                                                        <input
                                                            type="text"
                                                            id="detalle" name="detalle" ref="txtdetalle"
                                                            class="form-control"
                                                            :class="{'is-invalid': form.errors.detalle}"
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
                                                            :class="{'is-invalid': form.errors.nombres}"
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
                                                    maxlength="15" 
                                                    autocomplete=""
                                                    v-bind:disabled="!edicion"
                                                    v-model="form.alta" />
                                                <label for="alta">Fecha de alta</label>
                                                </div>
                                            </div>

                                            <div class="col-md-2 select2-primary">
                                                <div class="form-floating {{ $outline }}">
                                                <input
                                                    type="text"
                                                    id="antiguedad"
                                                    name="antiguedad"
                                                    class="form-control bg-transparent border-0 fw-semibold" 
                                                    placeholder="" 
                                                    readonly
                                                    :value="form.antiguedad" />
                                                <label for="antiguedad">Antigüedad</label>
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
                                            <div class="col-md-2 select2-primary">
                                                <div class="form-floating {{ $outline }}">
                                                <input 
                                                    type="text" 
                                                    id="edad" 
                                                    name="edad" 
                                                    class="form-control bg-transparent border-0 fw-semibold" 
                                                    placeholder="" 
                                                    readonly
                                                    :value="form.edad" />
                                                <label for="edad">Edad</label>
                                                </div>
                                            </div>

                                            <!-- Datos de la baja (solo lectura) — debajo de la fila de alta, solo si el empleado está de baja -->
                                            <div v-if="legajoBaja" class="row mt-4 col-md-12">
                                                <div class="col-md-6 mb-3">
                                                    <label for="sicoss_baja" class="col-form-label">Motivo de la baja</label>
                                                    <select class="form-control" id="sicoss_baja" name="sicoss_baja" v-model="sicossBaja" disabled>
                                                        <option value="">(Seleccione un motivo de baja)</option>
                                                        <option value="25">Abandono del trabajo/ Art.244 LCT</option>
                                                        <option value="53">Baja de oficio por denuncia</option>
                                                        <option value="2">Baja otras causales (renuncia, jubilación, etc.)</option>
                                                        <option value="4">Baja otras causales Decreto N°796/97</option>
                                                        <option value="7">Baja por despido</option>
                                                        <option value="8">Baja por despido (según Decreto N°796/97)</option>
                                                        <option value="1">Baja por fallecimiento</option>
                                                        <option value="40">Cesantía laboral</option>
                                                        <option value="20">Cesión del personal (Art. 229 LCT)</option>
                                                        <option value="32">Concurso del Empleador Art.251 LCT</option>
                                                        <option value="23">Denuncia de contrato de trabajo por el empleador/ Art.242 LCT</option>
                                                        <option value="24">Denuncia de contrato de trabajo por el trabajador/ Art.242 LCT</option>
                                                        <option value="19">Denuncia por transferencia de establecimiento (Art. 226 LCT)</option>
                                                        <option value="26">Despido / ART. 245 - LCT</option>
                                                        <option value="36">Despido con o sin justa causa / ART.64 Inc.c) Ley 22248</option>
                                                        <option value="37">Despido por fuerza mayor-Trabajo Agrario/Art.64 Inc.d) L.22248</option>
                                                        <option value="41">Exoneración</option>
                                                        <option value="52">Extinción por mutuo acuerdo (Art. 241 LCT)</option>
                                                        <option value="29">Fallecimiento del empleador/ Art.249 LCT</option>
                                                        <option value="27">Falta o disminución del trabajo/ Art.247 LCT</option>
                                                        <option value="38">Fin contrato de aprendizaje y pasantias / ART.1 y 2 Ley 25877; ART.2 y 19 Ley 25013</option>
                                                        <option value="47">Fin de pago retiro voluntario (Dec. 263/2018 y otros)</option>
                                                        <option value="28">Fuerza mayor / ART.247 - LCT</option>
                                                        <option value="34">Incapacidad o inhabilidad del trabajador / ART.254 - LCT</option>
                                                        <option value="46">Inicio de pago por retiro voluntario (Dec. 263/2018 y otros)</option>
                                                        <option value="33">Jubilación/ Art.252 LCT / Art.64 Inc.e) L.22248 y otras</option>
                                                        <option value="31">Quiebra del empleador/Art.251 LCT</option>
                                                        <option value="21">Renuncia del trabajador (Art. 240 LCT, Art. 64 inc. a)</option>
                                                        <option value="54">Retiro anticipado o voluntario</option>
                                                        <option value="18">Transferencia del contrato (Art. 225 LCT)</option>
                                                        <option value="99">Vencimiento de contrato a plazo fijo/determinado</option>
                                                        <option value="30">Vencimiento de plazo / ART. 250 - LCT</option>
                                                        <option value="22">Voluntad concurrente (Art. 241 LCT, acuerdo)</option>
                                                        <option value="35">Voluntad concurrente de las partes-Trabajo Agrario/Art.64 Inc.b) L.22248</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-10 mb-2">
                                                    <label for="baja_det" class="col-form-label">Comentarios de la baja</label>
                                                    <textarea class="form-control" name="baja_det" id="baja_det" rows="3" v-model="bajaDet" disabled style="resize: vertical;"></textarea>
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
                                                    <div class="text-danger small mt-1" v-if="form.errors.est_civil">
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
                                                    <div class="text-danger small mt-1" v-if="form.errors.salud">
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
                                                    <div class="text-danger small mt-1" v-if="form.errors.provin">
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
                                                        :class="{'is-invalid': form.errors.locali}"
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
                                                        :class="{'is-invalid': form.errors.domici}"
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
                                                        :class="{'is-invalid': form.errors.nro}"
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
                                                        :class="{'is-invalid': form.errors.piso}"
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
                                                        :class="{'is-invalid': form.errors.dpto}"
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
                                                        :class="{'is-invalid': form.errors.tel1}"
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
                                                        :class="{'is-invalid': form.errors.tel2}"
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
                                                        :class="{'is-invalid': form.errors.tel3}"
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
                                                        :class="{'is-invalid': form.errors.email}"
                                                        placeholder="Correo electrónico"
                                                        autocomplete="off"
                                                        v-bind:disabled="!edicion"
                                                        v-model="form.email"/>

                                                    <label for="email">Correo electrónico *</label>

                                                    <InputError class="mt-2" :message="form.errors.email" />
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="form-floating form-floating-outline">
                                                    <input
                                                        type="text"
                                                        id="web" name="web" ref="txtweb"
                                                        class="form-control"
                                                        :class="{'is-invalid': form.errors.web}"
                                                        placeholder="https://ejemplo.com"
                                                        autocomplete="off"
                                                        v-bind:disabled="!edicion"
                                                        v-model="form.web"/>

                                                    <label for="web">Página Web</label>

                                                    <InputError class="mt-2" :message="form.errors.web" />
                                                </div>
                                            </div>


                                            <div class="col-md-12">
                                                <div class="form-floating form-floating-outline">
                                                    <hr>
                                                </div>
                                            </div>
                                        </div>

                                    </div>

                                    <div class="tab-pane fade show" id="form-tabs-categorias" role="tabpanel">

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
                                                        :class="{'is-invalid': form.errors.tarea}"
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
                                                <FloatMultiselect
                                                    id="obra_sijp"
                                                    label="Obra Social"
                                                    v-model="form.obra_sijp"
                                                    :options="obrasOptions"
                                                    :disabled="!edicion"
                                                    :error="form.errors.obra_sijp"
                                                />
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

                                            <div class="col-md-4">
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

                                            <div class="row"></div>

                                            <!-- <div class="col-md-4">
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
                                            </div> -->

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
                                                            v-for="p in contratos"
                                                            :key="p.codigo"
                                                            :value="String(p.codigo)"
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

                                            <div class="row"></div>

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
                                                    <option
                                                            v-for="j in jornadas"
                                                            :key="j.id"
                                                            :value="j.id"
                                                        >
                                                            {{ j.detalle }}
                                                    </option>
                                                </select>
                                                <label for="jornada_id">Jornada laboral</label>
                                                </div>
                                            </div>

                                            

                                            <div class="col-md-2">
                                                <div class="form-floating form-floating-outline">
                                                    <input
                                                        type="text"
                                                        id="bruto" name="bruto" ref="txtbruto"
                                                        class="form-control"
                                                        :class="{'is-invalid': form.errors.bruto}"
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
                                                        :class="{'is-invalid': form.errors.bruto_azul}"
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

                                    <div class="tab-pane fade show" id="form-tabs-cargas-familia" role="tabpanel">

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
                                            <!-- Conyuge -->
                                            <div class="col-md-3">
                                                <div class="form-check form-switch mt-4">
                                                    <input
                                                        type="checkbox"
                                                        id="sicoss_conyuge"
                                                        name="sicoss_conyuge"
                                                        class="form-check-input"
                                                        v-model="form.sicoss_conyuge"
                                                        v-bind:disabled="!edicion"
                                                    />
                                                    <label class="form-check-label" for="sicoss_conyuge">Cónyuge</label>
                                                </div>
                                            </div>

                                            <div class="row"></div>

                                            <!-- Hijos -->
                                            <div class="col-md-3">
                                                <div class="form-check form-switch mt-4">
                                                    <input
                                                        type="checkbox"
                                                        id="sicoss_hijos"
                                                        name="sicoss_hijos"
                                                        class="form-check-input"
                                                        v-model="form.sicoss_hijos"
                                                        v-bind:disabled="!edicion"
                                                    />
                                                    <label class="form-check-label" for="sicoss_hijos">Hijos</label>
                                                </div>
                                            </div>

                                            <div class="row"></div>
                                            
                                            <!-- Adherentes -->
                                            <div class="col-md-2">
                                                <div class="form-floating form-floating-outline">
                                                    <input
                                                        type="number"
                                                        id="sicoss_adherentes"
                                                        name="sicoss_adherentes"
                                                        class="form-control"
                                                        :class="{'is-invalid': form.errors.sicoss_adherentes}"
                                                        placeholder="0"
                                                        min="0"
                                                        max="99"
                                                        maxlength="2"
                                                        autocomplete="off"
                                                        v-model.number="form.sicoss_adherentes"
                                                        v-bind:disabled="!edicion"
                                                    />
                                                    <label for="sicoss_adherentes">Adherentes</label>
                                                    <InputError class="mt-2" :message="form.errors.sicoss_adherentes" />
                                                </div>
                                            </div>
                                        </div>

                                    </div>

                                    <div class="tab-pane fade show" id="form-tabs-sicoss" role="tabpanel">

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
                                            <!-- Cartel de defaults precargados: arriba de los selectores, ancho completo -->
                                            <div class="col-12" v-if="defaultsSicossAplicados">
                                                <div class="alert alert-info d-flex align-items-center mb-0 py-2 px-3" role="alert">
                                                    <i class="ri-information-line ri-22px me-2 flex-shrink-0"></i>
                                                    <span>Se importaron los parámetros por defecto, ajústelos de ser necesario.</span>
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <FloatMultiselect
                                                    id="sicoss_situa"
                                                    label="Situación de revista"
                                                    v-model="form.sicoss_situa"
                                                    :options="situacionesOptions"
                                                    :disabled="!edicion"
                                                    :error="form.errors.sicoss_situa"
                                                />
                                            </div>

                                            <div class="col-md-6 d-flex align-items-center" v-if="puedeImportarSicoss">
                                                <button
                                                    type="button"
                                                    class="btn btn-outline-primary"
                                                    @click="abrirImportarSicoss"
                                                >
                                                    <i class="ri-download-2-line me-1"></i>
                                                    Importar datos SICOSS desde legajo {{ sicossOrigen.legajo }}
                                                </button>
                                            </div>

                                            <div class="row"></div>
                                            <div class="col-md-6">
                                                <div class="form-floating form-floating-outline">
                                                <select
                                                    id="sicoss_condi"
                                                    name="sicoss_condi"
                                                    class="select2 form-select"
                                                    data-allow-clear="true"
                                                    v-bind:disabled="!edicion"
                                                    v-model="form.sicoss_condi">

                                                    <option disabled value="">(Seleccione una condición)</option>
                                                    <option
                                                            v-for="p in condiciones"
                                                            :key="p.codigo"
                                                            :value="p.codigo"
                                                        >
                                                            {{ p.codigo }} - {{ p.detalle }}
                                                    </option>
                                                </select>
                                                <label for="sicoss_condi">Condición de contratación</label>
                                                </div>
                                            </div>

                                            <div class="row"></div>
                                            <div class="col-md-6">
                                                <FloatMultiselect
                                                    id="sicoss_activ"
                                                    label="01 - Actividad"
                                                    v-model="form.sicoss_activ"
                                                    :options="actividadesOptions"
                                                    :disabled="!edicion"
                                                    :error="form.errors.sicoss_activ"
                                                />
                                            </div>

                                            <div class="row"></div>
                                            <div class="col-md-6">
                                                <FloatMultiselect
                                                    id="sicoss_modal"
                                                    label="08 - Modalidad de Contratacion"
                                                    v-model="form.sicoss_modal"
                                                    :options="contratacionesOptions"
                                                    :disabled="!edicion"
                                                    :error="form.errors.sicoss_modal"
                                                />
                                            </div>

                                            <div class="row"></div>
                                            <div class="col-md-6">
                                                <FloatMultiselect
                                                    id="sicoss_sini"
                                                    label="Código de siniestrado"
                                                    v-model="form.sicoss_sini"
                                                    :options="sinieOptions"
                                                    :disabled="!edicion"
                                                    :error="form.errors.sicoss_sini"
                                                />
                                            </div>

                                            <div class="row"></div>
                                            
                                            <div class="col-md-6">
                                                <FloatMultiselect
                                                    id="sicoss_zona"
                                                    label="Localidad"
                                                    v-model="form.sicoss_zona"
                                                    :options="zonasOptions"
                                                    :disabled="!edicion"
                                                    :error="form.errors.sicoss_zona"
                                                />
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
                            <Link
                                type="button"
                                :href="form.id ? route('legajos.destroy', form.id) : '#'"
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

