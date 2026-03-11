<script setup>
import SearchableTable from '@/Components/SearchableTable.vue';

defineProps({
    localidades: {
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
    { key: 'numero', label: 'Número' },
    { key: 'detalle', label: 'Localidad' },
];
</script>

<template>
    <SearchableTable
        :items="localidades"
        :filters="filters"
        :search-route="route('sicoss.localidades.search')"
        :detail-route="route('sicoss.localidades.show', ':id')"
        :columns="columns"
        title="Búsqueda de Localidades SICOSS"
        subtitle="Encuentra rápidamente la localidad que necesitas"
        :back-route="route('sicoss.localidades.index')"
        back-text="Volver"
        search-placeholder="Buscar por código, número o localidad..."
        no-results-text="No se encontraron resultados"
    />
</template>
