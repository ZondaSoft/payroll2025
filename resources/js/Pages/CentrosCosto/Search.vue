<script setup>
import SearchableTable from '@/Components/SearchableTable.vue';

defineProps({
    registros: { type: Object, required: true },
    filters: { type: Object, default: () => ({ search: '' }) },
});

const columns = [
    { key: 'codigo', label: 'Código', bold: true },
    { key: 'detalle', label: 'Detalle' },
    { key: 'responsa', label: 'Responsable' },
    { key: 'localidad', label: 'Localidad' },
];
</script>

<template>
    <SearchableTable
        :items="registros"
        :filters="filters"
        :search-route="route('centros.costo.search')"
        :detail-route="route('centros.costo.show', ':id')"
        :columns="columns"
        title="Búsqueda de Centros de Costo"
        subtitle="Encuentra rápidamente el centro de costo que necesitas"
        :back-route="route('centros.costo.index')"
        back-text="Volver"
        search-placeholder="Buscar por código, detalle o responsable..."
        no-results-text="No se encontraron resultados"
    />
</template>
