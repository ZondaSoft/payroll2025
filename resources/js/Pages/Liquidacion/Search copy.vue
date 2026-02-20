<script setup>
import { Link, router } from '@inertiajs/vue3';
import { ref, watch, onMounted } from 'vue';

const props = defineProps({
    conceptos: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({ search: '' }),
    },
});

const searchInput = ref(props.filters.search || '');
const searchForm = ref(null);
const searchEl = ref(null);

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

const focusSearch = () => {
  // pequeño tick por si el DOM se re-renderizó
  requestAnimationFrame(() => searchEl.value?.focus());
};

onMounted(() => {
  focusSearch();
});

const clearSearch = () => {
  searchInput.value = '';
  router.get('/liquidacion/conceptos/search', { search: '' }, {
    preserveScroll: true,
    preserveState: true,
    only: ['conceptos'],
    onFinish: focusSearch,
  });
};

const handleSubmit = () => {
  router.get('/liquidacion/conceptos/search', { search: searchInput.value }, {
    preserveScroll: true,
    preserveState: true,
    onFinish: focusSearch,
  });
};

// Búsqueda incremental a partir del 2do carácter
watch(searchInput, (newValue) => {
  if (newValue.length >= 1) {
    clearTimeout(window.__tConceptosSearch);
    window.__tConceptosSearch = setTimeout(() => {
      router.get('/liquidacion/conceptos/search', { search: newValue }, {
        preserveScroll: true,
        preserveState: true,
        only: ['conceptos'],
        onFinish: focusSearch,
      });
    }, 300);
  }
});

const handleEnter = () => {
    if (props.conceptos.data.length === 1) {
        router.visit(`/liquidacion/conceptos/${props.conceptos.data[0].id}`);
    }
};

</script>

<template>
    <div class="container-xxl flex-grow-1 container-p-y">
        <!-- Encabezado -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-6 row-gap-4">
            <div class="d-flex flex-column justify-content-center">
                <h4 class="mb-1">Búsqueda de Conceptos de Liquidación</h4>
                <p class="mb-0">Encuentra rápidamente los conceptos que necesitas</p>
            </div>
        </div>

        <!-- Card de búsqueda -->
        <div class="card mb-6">
            <div class="card-body">
                <form ref="searchForm" method="GET" action="/liquidacion/conceptos/search" class="row g-3 align-items-center">
                    <div class="col-auto">
                        <Link href="/liquidacion/conceptos" 
                            class="btn btn-icon btn-text-primary rounded-pill" 
                            data-bs-toggle="tooltip" 
                            data-bs-placement="top" 
                            data-bs-original-title="Volver">
                            <i class="ri-arrow-left-circle-line" style="font-size: 2.5rem;"></i>
                        </Link>
                    </div>
                    <div class="col-md-8">
                        <div class="input-group input-group-merge">
                            <span class="input-group-text"><i class="ri-search-line"></i></span>
                            <input 
                                ref="searchEl"
                                type="text" 
                                class="form-control" 
                                placeholder="Buscar por descripción o código..." 
                                name="search"
                                v-model="searchInput"
                                @keydown.enter.prevent="handleEnter">
                            <button 
                                v-if="searchInput" 
                                type="button" 
                                class="btn btn-outline-secondary"
                                @click="clearSearch"
                                data-bs-toggle="tooltip" 
                                data-bs-placement="top" 
                                data-bs-original-title="Limpiar búsqueda">
                                <i class="ri-close-line"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <span class="tf-icons ri-search-line"></span>&nbsp; Buscar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabla de resultados -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Resultados de Búsqueda</h5>
            </div>
            <div class="table-responsive text-nowrap">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Código</th>
                            <th>Descripción</th>
                            <th>Tipo</th>
                            <th>Estado</th>
                            <th>Seleccionar</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                        <tr v-for="concepto in conceptos.data" :key="concepto.id">
                            <td>
                                <strong>{{ concepto.codigo }}</strong>
                            </td>
                            <td>{{ concepto.detalle }}</td>
                            <td>
                                {{ getTipoNombre(concepto.tipo) }}
                            </td>
                            <td>
                                <span v-if="concepto.activo" class="badge bg-label-success">Activo</span>
                                <span v-else class="badge bg-label-danger">Inactivo</span>
                            </td>
                            <td>
                                <Link :href="`/liquidacion/conceptos/${concepto.id}`" 
                                    class="btn btn-sm btn-icon btn-text-primary rounded-pill" 
                                    data-bs-toggle="tooltip" 
                                    data-bs-placement="top" 
                                    data-bs-original-title="Ver concepto">
                                    <i class="ri-search-line"></i>
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="conceptos.data.length === 0">
                            <td colspan="5" class="text-center py-5">
                                <p class="text-muted mb-0">No se encontraron resultados</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <div v-if="conceptos.links && conceptos.links.length > 3" class="card-footer">
                <div class="d-flex justify-content-center">
                    <nav aria-label="Page navigation">
                        <ul class="pagination">
                            <li v-for="(link, index) in conceptos.links" :key="index" 
                                :class="['page-item', { active: link.active, disabled: !link.url }]">
                                <Link v-if="link.url" :href="link.url" class="page-link">
                                    <span v-html="link.label"></span>
                                </Link>
                                <span v-else class="page-link" v-html="link.label"></span>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</template>
