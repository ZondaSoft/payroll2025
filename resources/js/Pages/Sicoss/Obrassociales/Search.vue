<script setup>
import SearchableTable from '@/Components/SearchableTable.vue';

defineProps({
    obras: {
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
        :items="obras"
        :filters="filters"
        :search-route="route('sicoss.obras.search')"
        :detail-route="route('sicoss.obras.show', ':id')"
        :columns="columns"
        title="Búsqueda de Obras Sociales"
        subtitle="Encuentra rápidamente la obra social que necesitas"
        :back-route="route('sicoss.obras.index')"
        back-text="Volver"
        search-placeholder="Buscar por código o nombre..."
        no-results-text="No se encontraron resultados"
    />
</template>
