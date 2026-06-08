<script setup>
import SearchableTable from '@/Components/SearchableTable.vue';

defineProps({
    registros: { type: Object, required: true },
    filters: { type: Object, default: () => ({ search: '' }) },
});

const columns = [
    { key: 'codigo', label: 'Código', bold: true },
    { key: 'detalle', label: 'Detalle' },
    { key: 'cod_conve', label: 'Convenio' },
];
</script>

<template>
    <SearchableTable
        :items="registros"
        :filters="filters"
        :search-route="route('categorias.search')"
        :detail-route="route('categorias.show', ':id')"
        :columns="columns"
        title="Búsqueda de Categorías"
        subtitle="Encuentra rápidamente la categoría que necesitas"
        :back-route="route('categorias.index')"
        back-text="Volver"
        search-placeholder="Buscar por código o detalle..."
        no-results-text="No se encontraron resultados"
    />
</template>
