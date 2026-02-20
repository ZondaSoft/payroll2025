<script setup>
import { Link, router } from '@inertiajs/vue3';
import { ref, watch, onMounted } from 'vue';

const props = defineProps({
    items: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({ search: '' }),
    },
    searchRoute: {
        type: String,
        required: true,
    },
    detailRoute: {
        type: String,
        required: true,
    },
    columns: {
        type: Array,
        required: true,
    },
    title: {
        type: String,
        default: 'Búsqueda',
    },
    subtitle: {
        type: String,
        default: 'Encuentra rápidamente los elementos que necesitas',
    },
    backRoute: {
        type: String,
        default: null,
    },
    backText: {
        type: String,
        default: 'Volver',
    },
    searchPlaceholder: {
        type: String,
        default: 'Buscar...',
    },
    noResultsText: {
        type: String,
        default: 'No se encontraron resultados',
    },
});

const searchInput = ref(props.filters.search || '');
const searchEl = ref(null);

const focusSearch = () => {
    requestAnimationFrame(() => searchEl.value?.focus());
};

onMounted(() => {
    focusSearch();
});

const clearSearch = () => {
    searchInput.value = '';
    router.get(props.searchRoute, { search: '' }, {
        preserveScroll: true,
        preserveState: true,
        onFinish: focusSearch,
    });
};

const handleSubmit = () => {
    router.get(props.searchRoute, { search: searchInput.value }, {
        preserveScroll: true,
        preserveState: true,
        onFinish: focusSearch,
    });
};

// Búsqueda incremental a partir del 2do carácter
watch(searchInput, (newValue) => {
    clearTimeout(window.__tSearch);
    if (newValue.length >= 1 || newValue.length === 0) {
        window.__tSearch = setTimeout(() => {
            router.get(props.searchRoute, { search: newValue }, {
                preserveScroll: true,
                preserveState: true,
                onFinish: focusSearch,
            });
        }, 300);
    }
});

const handleEnter = () => {
    if (props.items.data.length === 1) {
        const idField = Object.keys(props.items.data[0])[0];
        router.visit(props.detailRoute.replace(':id', props.items.data[0][idField]));
    }
};

const getColumnValue = (item, columnKey) => {
    return item[columnKey];
};

const getDetailUrl = (item) => {
    const idField = Object.keys(item)[0];
    return props.detailRoute.replace(':id', item[idField]);
};
</script>

<template>
    <div class="container-xxl flex-grow-1 container-p-y">
        <!-- Encabezado -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-6 row-gap-4">
            <div class="d-flex flex-column justify-content-center">
                <h4 class="mb-1">{{ title }}</h4>
                <p class="mb-0">{{ subtitle }}</p>
            </div>
        </div>

        <!-- Card de búsqueda -->
        <div class="card mb-6">
            <div class="card-body">
                <form @submit.prevent="handleSubmit" class="row g-3 align-items-center">
                    <div class="col-auto" v-if="backRoute">
                        <Link :href="backRoute" 
                            class="btn btn-icon btn-text-primary rounded-pill" 
                            data-bs-toggle="tooltip" 
                            data-bs-placement="top" 
                            :data-bs-original-title="backText">
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
                                :placeholder="searchPlaceholder" 
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
                            <th v-for="column in columns" :key="column.key">
                                {{ column.label }}
                            </th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                        <tr v-for="item in items.data" :key="item.id">
                            <td v-for="column in columns" :key="column.key">
                                <slot :name="`column-${column.key}`" :item="item">
                                    <strong v-if="column.bold">{{ getColumnValue(item, column.key) }}</strong>
                                    <span v-else>{{ getColumnValue(item, column.key) }}</span>
                                </slot>
                            </td>
                            <td>
                                <Link :href="getDetailUrl(item)" 
                                    class="btn btn-sm btn-icon btn-text-primary rounded-pill" 
                                    data-bs-toggle="tooltip" 
                                    data-bs-placement="top" 
                                    data-bs-original-title="Ver detalle">
                                    <i class="ri-search-line"></i>
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="items.data.length === 0">
                            <td :colspan="columns.length + 1" class="text-center py-5">
                                <p class="text-muted mb-0">{{ noResultsText }}</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <div v-if="items.links && items.links.length > 3" class="card-footer">
                <div class="d-flex justify-content-center">
                    <nav aria-label="Page navigation">
                        <ul class="pagination">
                            <li v-for="(link, index) in items.links" :key="index" 
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
