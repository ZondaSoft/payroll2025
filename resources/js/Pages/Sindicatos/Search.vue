<script setup>
import SearchableTable from '@/Components/SearchableTable.vue';

defineProps({
    registros: { type: Object, required: true },
    filters: { type: Object, default: () => ({ search: '' }) },
});

const columns = [
    { key: 'codigo', label: 'Código', bold: true },
    { key: 'detalle', label: 'Detalle' },
    { key: 'localidad', label: 'Localidad' },
];
</script>

<template>
    <SearchableTable
        :items="registros"
        :filters="filters"
        :search-route="route('sindicatos.search')"
        :detail-route="route('sindicatos.show', ':id')"
        :columns="columns"
        title="Búsqueda de Sindicatos"
        subtitle="Encuentra rápidamente el sindicato que necesitas"
        :back-route="route('sindicatos.index')"
        back-text="Volver"
        search-placeholder="Buscar por código, detalle o localidad..."
        no-results-text="No se encontraron resultados"
    />
</template>
