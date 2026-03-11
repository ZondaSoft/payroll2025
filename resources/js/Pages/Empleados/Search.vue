<script setup>
import SearchableTable from '@/Components/SearchableTable.vue';

defineProps({
    legajos: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({ search: '' }),
    },
});

const columns = [
    { key: 'codigo',  label: 'Legajo',  bold: true },
    { key: 'detalle', label: 'Apellido y Nombre' },
    { key: 'nombres', label: 'Nombres' },
    { key: 'cuil',    label: 'CUIL' },
    { key: 'alta',    label: 'Alta' },
];
</script>

<template>
    <SearchableTable
        :items="legajos"
        :filters="filters"
        :search-route="route('legajos.search')"
        :detail-route="route('legajos.show', ':id')"
        :columns="columns"
        title="Búsqueda de Empleados"
        subtitle="Búsqueda rápida entre los empleados activos"
        :back-route="route('legajos.index')"
        back-text="Volver"
        search-placeholder="Buscar por apellido, nombre, CUIL o legajo..."
        no-results-text="No se encontraron empleados activos"
    >
        <template #column-alta="{ item }">
            {{ item.alta ? new Date(item.alta).toLocaleDateString('es-AR') : '—' }}
        </template>
    </SearchableTable>
</template>
