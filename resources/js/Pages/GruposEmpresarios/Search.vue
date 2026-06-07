<script setup>
import SearchableTable from '@/Components/SearchableTable.vue';

defineProps({
    grupos: {
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
    { key: 'detalle', label: 'Razón social' },
    { key: 'fantasia', label: 'Nombre fantasía' },
    { key: 'cuit', label: 'CUIT' },
];
</script>

<template>
    <SearchableTable
        :items="grupos"
        :filters="filters"
        :search-route="route('grupos.empresarios.search')"
        :detail-route="route('grupos.empresarios.show', ':id')"
        :columns="columns"
        title="Búsqueda de Grupos Empresarios"
        subtitle="Encuentra rápidamente el grupo empresario que necesitas"
        :back-route="route('grupos.empresarios.index')"
        back-text="Volver"
        search-placeholder="Buscar por código, razón social, fantasía o CUIT..."
        no-results-text="No se encontraron resultados"
    />
</template>
