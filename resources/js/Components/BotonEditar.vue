<script setup>
import { Link } from '@inertiajs/vue3';

// Botón Editar/Modificar único para todo el sistema: modelo Materialize
// `btn-label-primary btn-fab` con el ícono de lápiz adelante.
// `demo` es obligatoria: sin ella `.btn.btn-fab:not(.demo)` (core.css) lo convierte
// en un FAB flotante fijo abajo a la derecha.
// Los listeners (@click) y clases extra del padre caen sobre el <Link> raíz.
defineProps({
    href: {
        type: String,
        required: true
    }
});
</script>

<template>
    <Link :href="href" class="btn btn-label-primary btn-fab demo waves-effect">
        <span class="icon-base ri ri-pencil-line icon-16px me-2"></span><slot>Editar</slot>
    </Link>
</template>
