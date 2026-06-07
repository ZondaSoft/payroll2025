<script setup>
import SearchableTable from '@/Components/SearchableTable.vue';

defineProps({
    convenios: {
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
    { key: 'detalle', label: 'Convenio' },
];
</script>

<template>
    <SearchableTable
        :items="convenios"
        :filters="filters"
        :search-route="route('convenios.search')"
        :detail-route="route('convenios.show', ':id')"
        :columns="columns"
        title="Búsqueda de Convenios Colectivos (CCT)"
        subtitle="Encuentra rápidamente el convenio que necesitas"
        :back-route="route('convenios.index')"
        back-text="Volver"
        search-placeholder="Buscar por código o nombre..."
        no-results-text="No se encontraron resultados"
    />
</template>
