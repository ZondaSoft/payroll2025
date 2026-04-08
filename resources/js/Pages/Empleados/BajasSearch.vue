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
    { key: 'baja',    label: 'Baja' },
];
</script>

<template>
    <SearchableTable
        :items="legajos"
        :filters="filters"
        :search-route="route('bajas.search')"
        :detail-route="route('bajas.show', ':id')"
        :columns="columns"
        title="Búsqueda de Empleados de Baja"
        subtitle="Búsqueda rápida entre los empleados dados de baja"
        :back-route="route('bajas.index')"
        back-text="Volver"
        search-placeholder="Buscar por apellido, nombre, CUIL o legajo..."
        no-results-text="No se encontraron empleados de baja"
    >
        <template #column-baja="{ item }">
            {{ item.baja ? new Date(item.baja).toLocaleDateString('es-AR') : '—' }}
        </template>
    </SearchableTable>
</template>
