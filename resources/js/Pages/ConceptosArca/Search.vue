<script setup>
import SearchableTable from '@/Components/SearchableTable.vue';

defineProps({
    conceptos: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({ search: '' }),
    },
});

const columns = [
    { key: 'codigo_contribuyente', label: 'Código', bold: true },
    { key: 'descripcion_contribuyente', label: 'Descripción contribuyente' },
    { key: 'codigo_afip', label: 'Código ARCA' },
    { key: 'descripcion', label: 'Descripción ARCA' },
];
</script>

<template>
    <SearchableTable
        :items="conceptos"
        :filters="filters"
        :search-route="route('arca.conceptos.search')"
        :detail-route="route('arca.conceptos.show', ':id')"
        :columns="columns"
        title="Búsqueda de Conceptos ARCA"
        subtitle="Encuentra rápidamente el concepto ARCA que necesitas"
        :back-route="route('arca.conceptos.index')"
        back-text="Volver"
        search-placeholder="Buscar por código, descripción o código ARCA..."
        no-results-text="No se encontraron resultados"
    />
</template>
