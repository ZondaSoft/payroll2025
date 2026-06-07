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
    { key: 'empresa', label: 'Empresa' },
    { key: 'alta',    label: 'Alta' },
    { key: 'baja',    label: 'BAJA' },
];

// Formatea "YYYY-MM-DD..." como "DD/MM/AAAA" sin parsear con Date (evita corrimiento por timezone).
const fmtFecha = (d) => {
    if (!d) return '—';
    const [y, m, day] = String(d).slice(0, 10).split('-');
    return (y && m && day) ? `${day}/${m}/${y}` : String(d);
};
</script>

<template>
    <SearchableTable
        :items="legajos"
        :filters="filters"
        :search-route="route('legajos.search')"
        :detail-route="route('legajos.show', ':id')"
        :columns="columns"
        title="Búsqueda de Empleados"
        subtitle="Búsqueda rápida de empleados (incluye activos y de baja)"
        :back-route="route('legajos.index')"
        back-text="Volver"
        search-placeholder="Buscar por apellido, nombre, CUIL o legajo..."
        no-results-text="No se encontraron empleados"
    >
        <template #column-alta="{ item }">
            {{ fmtFecha(item.alta) }}
        </template>
        <template #column-baja="{ item }">
            <span v-if="item.baja" class="badge bg-danger">{{ fmtFecha(item.baja) }}</span>
            <span v-else class="text-muted">—</span>
        </template>
    </SearchableTable>
</template>
