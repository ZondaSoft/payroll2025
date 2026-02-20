<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    agregar: {
        type: Boolean,
        required: true
    },
    edicion: {
        type: Boolean,
        required: true
    },
    formId: {
        type: [String, Number],
        default: null
    },
    titulo: {
        type: String,
        default: 'Concepto de Liquidación'
    },
    rutaCreate: {
        type: String,
        required: true
    },
    rutaEdit: {
        type: String,
        default: null
    },
    rutaFirst: {
        type: String,
        default: null
    },
    rutaPrevious: {
        type: String,
        default: null
    },
    rutaNext: {
        type: String,
        default: null
    },
    rutaLast: {
        type: String,
        default: null
    },
    rutaSearch: {
        type: String,
        default: null
    },
    rutaIndex: {
        type: String,
        required: true
    },
    mostrarBotones: {
        type: Boolean,
        default: true
    },
    onSubmit: {
        type: Function,
        default: null
    }
});

defineEmits(['edit', 'delete', 'grabar', 'cancelar']);

</script>

<template>
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-6 row-gap-4">
        <!-- Sección de agregar -->
        <div v-if="agregar" class="d-flex flex-column justify-content-center">
            <h4 class="mb-1">Agregar {{ titulo }}</h4>
        </div>
        <!-- Sección de edición -->
        <div v-if="!agregar && edicion" class="d-flex flex-column justify-content-center">
            <h4 class="mb-1">Modificar {{ titulo }}</h4>
        </div>
        <!-- Si ni agregar ni edicion están activados -->
        <div v-if="!agregar && !edicion" class="d-flex flex-column justify-content-center">
            <h4 class="mb-1">{{ titulo }}</h4>
        </div>

        <!-- Botones de navegación -->
        <div class="d-flex flex-column justify-content-center" v-if="!agregar && !edicion && formId">
            <div class="btn-group" role="group">
                <Link
                    v-if="rutaFirst"
                    :href="rutaFirst"
                    class="btn btn-outline-secondary waves-effect"
                    data-bs-toggle="tooltip"
                    data-bs-placement="top"
                    data-bs-original-title="ir al primer registro">
                    <i class="ri-arrow-left-double-line"></i>
                </Link>
                <Link
                    v-if="rutaPrevious"
                    :href="rutaPrevious"
                    class="btn btn-outline-secondary waves-effect"
                    data-bs-toggle="tooltip"
                    data-bs-placement="top"
                    data-bs-original-title="ir al registro anterior">
                    <i class="ri-arrow-left-line"></i>
                </Link>
                <Link
                    v-if="rutaNext"
                    :href="rutaNext"
                    class="btn btn-outline-secondary waves-effect"
                    data-bs-toggle="tooltip"
                    data-bs-placement="top"
                    data-bs-original-title="ir al registro siguiente">
                    <i class="ri-arrow-right-line"></i>
                </Link>
                <Link
                    v-if="rutaLast"
                    :href="rutaLast"
                    class="btn btn-outline-secondary waves-effect"
                    data-bs-toggle="tooltip"
                    data-bs-placement="top"
                    data-bs-original-title="ir al ultimo registro">
                    <i class="ri-arrow-right-double-fill"></i>
                </Link>
                <a v-if="rutaSearch" :href="rutaSearch" class="btn btn-outline-secondary waves-effect" 
                    data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Buscar">
                    <i class="ri-search-line"></i>
                </a>
            </div>
        </div>

        <!-- Botones de agregar/Grabar -->
        <div v-if="agregar || edicion" class="d-flex align-content-center flex-wrap gap-4">
            <button type="button" class="btn btn-primary" @click="onSubmit ? onSubmit() : $emit('grabar')">Grabar</button>
            <Link
                :href="rutaIndex"
                class="btn btn-outline-secondary"
                @click="$emit('cancelar')">
                Cancelar
            </Link>
        </div>
        <!-- Botones de CRUD -->
        <div v-else class="d-flex align-content-center flex-wrap gap-4">
            <Link
                :href="rutaCreate"
                class="btn btn-info waves-effect waves-light"
                @click="$emit('edit')">
                Agregar
            </Link>
            <Link
                v-if="formId"
                :href="rutaEdit"
                class="btn btn-outline-secondary"
                @click="$emit('edit')">
                Modificar
            </Link>
            <a type="button" 
                class="btn btn-danger waves-effect waves-light" 
                style="color: white"
                data-bs-toggle="modal" 
                data-bs-target="#modalDelete"
                @click="$emit('delete')">
                Borrar
            </a>
        </div>
    </div>
</template>
