<script setup>
import SearchableTable from '@/Components/SearchableTable.vue';

defineProps({
    siniestros: {
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
        :items="siniestros"
        :filters="filters"
        :search-route="route('sicoss.siniestros.search')"
        :detail-route="route('sicoss.siniestros.show', ':id')"
        :columns="columns"
        title="Búsqueda de Siniestros SICOSS"
        subtitle="Encuentra rápidamente el siniestro que necesitas"
        :back-route="route('sicoss.siniestros.index')"
        back-text="Volver"
        search-placeholder="Buscar por código o descripción..."
        no-results-text="No se encontraron resultados"
    />
</template>
