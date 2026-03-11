<script setup>
import SearchableTable from '@/Components/SearchableTable.vue';

defineProps({
    situacion: {
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
        :items="situacion"
        :filters="filters"
        :search-route="route('sicoss.situacion.search')"
        :detail-route="route('sicoss.situacion.show', ':id')"
        :columns="columns"
        title="Búsqueda de Situaciones de Revista"
        subtitle="Encuentra rápidamente la situación que necesitas"
        :back-route="route('sicoss.situacion.index')"
        back-text="Volver"
        search-placeholder="Buscar por código o descripción..."
        no-results-text="No se encontraron resultados"
    />
</template>
