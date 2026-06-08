<script setup>
import SearchableTable from '@/Components/SearchableTable.vue';

defineProps({
    registros: { type: Object, required: true },
    filters: { type: Object, default: () => ({ search: '' }) },
});

const columns = [
    { key: 'codigo', label: 'Código', bold: true },
    { key: 'detalle', label: 'Detalle' },
    { key: 'duracion', label: 'Duración' },
];
</script>

<template>
    <SearchableTable
        :items="registros"
        :filters="filters"
        :search-route="route('tipos.contrato.search')"
        :detail-route="route('tipos.contrato.show', ':id')"
        :columns="columns"
        title="Búsqueda de Tipos de Contrato"
        subtitle="Encuentra rápidamente el tipo de contrato que necesitas"
        :back-route="route('tipos.contrato.index')"
        back-text="Volver"
        search-placeholder="Buscar por código o detalle..."
        no-results-text="No se encontraron resultados"
    />
</template>
