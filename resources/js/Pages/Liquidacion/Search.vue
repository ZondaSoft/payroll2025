<script setup>
import SearchableTable from '@/Components/SearchableTable.vue';

defineProps({
    conceptos: {
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
    { key: 'tipo', label: 'Tipo' },
    { key: 'activo', label: 'Estado' },
];
</script>

<template>
    <SearchableTable
        :items="conceptos"
        :filters="filters"
        :search-route="route('liquidacion.conceptos.search')"
        :detail-route="route('liquidacion.conceptos.show', ':id')"
        :columns="columns"
        title="Búsqueda de Conceptos de Liquidación"
        subtitle="Encuentra rápidamente los conceptos que necesitas"
        :back-route="route('liquidacion.conceptos.index')"
        back-text="Volver"
        search-placeholder="Buscar por descripción o código..."
        no-results-text="No se encontraron resultados"
    >
        <template #column-tipo="{ item }">
            {{ getTipoNombre(item.tipo) }}
        </template>
        <template #column-activo="{ item }">
            <span v-if="item.activo" class="badge bg-label-success">Activo</span>
            <span v-else class="badge bg-label-danger">Inactivo</span>
        </template>
    </SearchableTable>
</template>

<script>
const TIPOS = {
    1: 'HABER',
    2: 'DESCUENTO',
    3: 'ASIGNACIONES',
    4: 'NO_REMUNERATIVO',
    5: 'GANANCIAS',
    6: 'DEVOLUCIÓN DE GANANCIA',
    7: 'REDONDEO',
    8: 'APORTES',
    9: 'AUXILIARES',
};

const getTipoNombre = (tipo) => {
    return TIPOS[tipo] || 'Desconocido';
};
</script>
