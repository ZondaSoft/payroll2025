<script setup>
import SearchableTable from '@/Components/SearchableTable.vue';

defineProps({
    condiciones: {
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
        :items="condiciones"
        :filters="filters"
        :search-route="route('sicoss.condiciones.search')"
        :detail-route="route('sicoss.condiciones.show', ':id')"
        :columns="columns"
        title="Búsqueda de Condiciones SICOSS"
        subtitle="Encuentra rápidamente la condición que necesitas"
        :back-route="route('sicoss.condiciones.index')"
        back-text="Volver"
        search-placeholder="Buscar por código o descripción..."
        no-results-text="No se encontraron resultados"
    />
</template>
