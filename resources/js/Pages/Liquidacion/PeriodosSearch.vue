<script setup>
import SearchableTable from '@/Components/SearchableTable.vue';

defineProps({
    periodos: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({ search: '' }),
    },
});

const columns = [
    { key: 'periodo',  label: 'Período',  bold: true },
    { key: 'tipoliq',  label: 'Tipo' },
    { key: 'fecha',    label: 'Fecha' },
    { key: 'estado',   label: 'Estado' },
];

const TIPOS = {
    1: 'Mensual',
    2: 'SAC 1er semestre',
    3: 'SAC 2do semestre',
    4: 'Vacaciones',
    5: 'Otro',
};

const getTipoNombre = (tipo) => TIPOS[tipo] || tipo;
</script>

<template>
    <SearchableTable
        :items="periodos"
        :filters="filters"
        :search-route="route('liquidacion.periodos.search')"
        :detail-route="route('liquidacion.periodos.show', ':id')"
        :columns="columns"
        title="Búsqueda de Períodos de Liquidación"
        subtitle="Encuentra rápidamente el período que necesitas"
        :back-route="route('liquidacion.periodos.index')"
        back-text="Volver"
        search-placeholder="Buscar por período o comentarios..."
        no-results-text="No se encontraron resultados"
    >
        <template #column-tipoliq="{ item }">
            {{ getTipoNombre(item.tipoliq) }}
        </template>
        <template #column-estado="{ item }">
            <span v-if="item.estado" class="badge bg-label-info">{{ item.estado }}</span>
            <span v-else class="badge bg-label-secondary">-</span>
        </template>
    </SearchableTable>
</template>
