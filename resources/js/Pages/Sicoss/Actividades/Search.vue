<script setup>
import SearchableTable from '@/Components/SearchableTable.vue';

defineProps({
    actividades: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({ search: '' }),
    },
});

const columns = [
    { key: 'codigo', label: 'Código', bold: true },
    { key: 'detalle', label: 'Descripción' },
];
</script>

<template>
    <SearchableTable
        :items="actividades"
        :filters="filters"
        :search-route="route('sicoss.actividades.search')"
        :detail-route="route('sicoss.actividades.show', ':id')"
        :columns="columns"
        title="Búsqueda de Actividades SICOSS"
        subtitle="Encuentra rápidamente la actividad que necesitas"
        :back-route="route('sicoss.actividades.index')"
        back-text="Volver"
        search-placeholder="Buscar por código o descripción..."
        no-results-text="No se encontraron resultados"
    />
</template>
