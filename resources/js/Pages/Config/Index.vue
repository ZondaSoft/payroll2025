<script setup>
import FormHeader from '@/Components/FormHeader.vue';
import Flash from '@/Components/Motor/Flash.vue';
import InputError from '@/Components/InputError.vue';
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

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
});

const submit = () => {
    form.patch(route('config.general.update'), { preserveScroll: true });
};

const ultimaModificacion = computed(() => {
    if (!props.config?.updated_at) return null;
    const fecha = new Date(props.config.updated_at).toLocaleString('es-AR');
    return props.config.usuario?.name ? `${fecha} por ${props.config.usuario.name}` : fecha;
});
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
                                            <div class="form-check form-switch mt-2">
                                                <input
                                                    type="checkbox"
                                                    id="lsd_no_conceptos"
                                                    name="lsd_no_conceptos"
                                                    class="form-check-input"
                                                    v-model="form.lsd_no_conceptos"
                                                    :disabled="!edicion"
                                                />
                                                <label class="form-check-label" for="lsd_no_conceptos">LSD sin conceptos</label>
                                            </div>
                                            <div class="form-text">
                                                Parámetro <code>lsd_no_conceptos</code> para la generación del Libro Sueldo Digital.
                                            </div>
                                            <InputError class="mt-2" :message="form.errors.lsd_no_conceptos" />
                                        </div>
                                    </div>

                                    <p v-if="ultimaModificacion" class="text-muted small mt-6 mb-0">
                                        Última modificación: {{ ultimaModificacion }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</template>
