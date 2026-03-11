<script setup>
import SearchableTable from '@/Components/SearchableTable.vue';

defineProps({
    modalidades: {
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
        :items="modalidades"
        :filters="filters"
        :search-route="route('sicoss.modalidades.search')"
        :detail-route="route('sicoss.modalidades.show', ':id')"
        :columns="columns"
        title="Búsqueda de Modalidades SICOSS"
        subtitle="Encuentra rápidamente la modalidad que necesitas"
        :back-route="route('sicoss.modalidades.index')"
        back-text="Volver"
        search-placeholder="Buscar por código o descripción..."
        no-results-text="No se encontraron resultados"
    />
</template>
