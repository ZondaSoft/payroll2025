<script setup>
import FormHeader from '@/Components/FormHeader.vue';
import Flash from '@/Components/Motor/Flash.vue';
import InputError from '@/Components/InputError.vue';
import { useForm } from '@inertiajs/vue3';

// Parámetros generales del sistema: registro único de sue_config, sin alta/baja/navegación.
const props = defineProps({
    config: {
        type: Object,
        required: true,
    },
    edicion: {
        type: Boolean,
        required: true,
    },
});

const form = useForm({
    lsd_no_conceptos: Boolean(props.config?.lsd_no_conceptos),
    lsd_ocultar_activos: Boolean(props.config?.lsd_ocultar_activos),
});

const submit = () => {
    form.patch(route('config.general.update'), { preserveScroll: true });
};
</script>

<template>
    <Flash />
    <form @submit.prevent="submit">
        <div class="container-xxl flex-grow-1 container-p-y">
            <div class="app-ecommerce">
                <!-- HEAD Y BOTONES: misma cabecera que los ABM, pero sin Agregar, Borrar, navegación ni búsqueda -->
                <FormHeader
                    :agregar="false"
                    :edicion="edicion"
                    :form-id="config.id"
                    titulo="Parámetros generales"
                    texto-modificar="Editar"
                    :mostrar-crear="false"
                    :mostrar-borrar="false"
                    :ruta-edit="route('config.general.edit')"
                    :ruta-index="route('config.general.index')"
                    :on-submit="submit"
                />
                <!-- END HEAD Y BOTONES -->

                <div class="row">
                    <div class="col">
                        <div class="card mb-6">
                            <div class="card-header overflow-hidden">
                                <ul class="nav nav-tabs" role="tablist">
                                    <li class="nav-item">
                                        <button
                                            class="nav-link active"
                                            data-bs-toggle="tab"
                                            data-bs-target="#form-tabs-lsd"
                                            role="tab"
                                            aria-selected="true">
                                            <span class="ri-file-text-line ri-20px d-sm-none"></span
                                            ><span class="d-none d-sm-block">Libro Sueldo Digital</span>
                                        </button>
                                    </li>
                                </ul>
                            </div>

                            <div class="tab-content">
                                <div class="tab-pane fade active show" id="form-tabs-lsd" role="tabpanel">
                                    <div class="row g-6">
                                        <div class="col-md-6">
                                            <!-- La leyenda va primero y el switch a su derecha -->
                                            <div class="d-flex align-items-center gap-3 mt-2">
                                                <label class="form-check-label mb-0" for="lsd_no_conceptos">
                                                    En generación del LSD agregar conceptos sin preguntar ?
                                                </label>
                                                <div class="form-check form-switch mb-0">
                                                    <input
                                                        type="checkbox"
                                                        id="lsd_no_conceptos"
                                                        name="lsd_no_conceptos"
                                                        class="form-check-input"
                                                        v-model="form.lsd_no_conceptos"
                                                        :disabled="!edicion"
                                                    />
                                                </div>
                                            </div>
                                            <div class="form-text">
                                                Cuando se genera el archivo para el libro de sueldo digital, si detecta nuevos conceptos, agregarlos automáticamente o consultar antes ?
                                            </div>
                                            <InputError class="mt-2" :message="form.errors.lsd_no_conceptos" />
                                        </div>
                                        <div class="col-md-6">
                                            <!-- La leyenda va primero y el switch a su derecha -->
                                            <div class="d-flex align-items-center gap-3 mt-2">
                                                <label class="form-check-label mb-0" for="lsd_ocultar_activos">
                                                    Ocultar ventana de empleados activos sin liquidación y no incluirlos en el txt ?
                                                </label>
                                                <div class="form-check form-switch mb-0">
                                                    <input
                                                        type="checkbox"
                                                        id="lsd_ocultar_activos"
                                                        name="lsd_ocultar_activos"
                                                        class="form-check-input"
                                                        v-model="form.lsd_ocultar_activos"
                                                        :disabled="!edicion"
                                                    />
                                                </div>
                                            </div>
                                            <div class="form-text">
                                                Activado, la generación del libro de sueldo digital no muestra el diálogo y deja a esos empleados <b>afuera del archivo</b>; al terminar se avisa cuáles quedaron sin informar. Desactivado, se muestra el diálogo para elegir cuáles se incluyen con las bases en 0.
                                            </div>
                                            <InputError class="mt-2" :message="form.errors.lsd_ocultar_activos" />
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
</template>

<style scoped>
/* Subrayado de la pestaña activa por CSS: la raya del theme es un <span class="tab-slider">
   que posiciona el JS del template, y no sobrevive a los re-render de Inertia. Mismo fix
   que en Motor/Conceptos/Form.vue y Liquidacion/Conceptos.vue. */
.nav-tabs .nav-link.active {
    color: var(--bs-primary, #696cff);
    border-bottom: 3px solid var(--bs-primary, #696cff) !important;
}
</style>
