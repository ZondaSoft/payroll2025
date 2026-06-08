<script setup>
import SearchableTable from '@/Components/SearchableTable.vue';

defineProps({
    registros: { type: Object, required: true },
    filters: { type: Object, default: () => ({ search: '' }) },
});

const columns = [
    { key: 'codigo', label: 'Código', bold: true },
    { key: 'detalle', label: 'Detalle' },
];
</script>

<template>
    <SearchableTable
        :items="registros"
        :filters="filters"
        :search-route="route('cuadrillas.search')"
        :detail-route="route('cuadrillas.show', ':id')"
        :columns="columns"
        title="Búsqueda de Cuadrillas"
        subtitle="Encuentra rápidamente la cuadrilla que necesitas"
        :back-route="route('cuadrillas.index')"
        back-text="Volver"
        search-placeholder="Buscar por código o detalle..."
        no-results-text="No se encontraron resultados"
    />
</template>
