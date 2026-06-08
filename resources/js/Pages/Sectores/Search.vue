<script setup>
import SearchableTable from '@/Components/SearchableTable.vue';

defineProps({
    registros: { type: Object, required: true },
    filters: { type: Object, default: () => ({ search: '' }) },
});

const columns = [
    { key: 'codigo', label: 'Código', bold: true },
    { key: 'detalle', label: 'Detalle' },
    { key: 'vacac_tipo_dias', label: 'Tipo días vac.' },
];
</script>

<template>
    <SearchableTable
        :items="registros"
        :filters="filters"
        :search-route="route('sectores.search')"
        :detail-route="route('sectores.show', ':id')"
        :columns="columns"
        title="Búsqueda de Sectores"
        subtitle="Encuentra rápidamente el sector que necesitas"
        :back-route="route('sectores.index')"
        back-text="Volver"
        search-placeholder="Buscar por código o detalle..."
        no-results-text="No se encontraron resultados"
    />
</template>
