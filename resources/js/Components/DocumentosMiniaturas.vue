<script setup>
// Miniaturas de los documentos digitalizados del legajo (sue111s del portal Drakma), estilo
// Material Design 3: tiles con esquinas de 12px, superficie tonal, capa de estado al pasar el
// mouse y elevación. Las imágenes se muestran tal cual; de los PDF se dibuja la primera página
// con pdfjs-dist, que se carga recién cuando hay un PDF para mostrar. Click → abre el archivo.
import { ref, watch, computed } from 'vue';
import axios from 'axios';

const props = defineProps({
    legajoId: { type: [Number, String], default: null },
    // Cambia cuando Inertia trae el legajo de nuevo (p. ej. tras una baja con constancia).
    refrescar: { type: [Object, Number, String], default: null },
    maxVisibles: { type: Number, default: 6 },
});

const docs = ref([]);
const cargando = ref(false);
const error = ref(false);
const expandido = ref(false);
const thumbs = ref({});   // id -> dataURL de la primera página del PDF ('' si falló)

const visibles = computed(() => (expandido.value ? docs.value : docs.value.slice(0, props.maxVisibles)));
const ocultos = computed(() => Math.max(0, docs.value.length - props.maxVisibles));

const fmtFecha = (f) => {
    if (!f) return '';
    const [y, m, d] = String(f).slice(0, 10).split('-');
    return (y && m && d) ? `${d}/${m}/${y}` : String(f);
};
const etiqueta = (d) => d.origen || d.carpeta || d.ext?.toUpperCase() || 'Documento';
const tooltip = (d) => [d.nombre, d.carpeta, fmtFecha(d.fecha)].filter(Boolean).join(' · ');

let pdfjsPromise = null;
const cargarPdfjs = () => {
    pdfjsPromise ??= Promise.all([
        import('pdfjs-dist'),
        import('pdfjs-dist/build/pdf.worker.min.mjs?url'),
    ]).then(([pdfjs, worker]) => {
        pdfjs.GlobalWorkerOptions.workerSrc = worker.default;
        return pdfjs;
    });
    return pdfjsPromise;
};

// Primera página del PDF a un canvas de ~144px de ancho (2x para pantallas de alta densidad).
const miniaturaPdf = async (doc) => {
    try {
        const pdfjs = await cargarPdfjs();
        const pdf = await pdfjs.getDocument({ url: doc.url }).promise;
        const page = await pdf.getPage(1);
        const base = page.getViewport({ scale: 1 });
        const viewport = page.getViewport({ scale: 144 / base.width });
        const canvas = document.createElement('canvas');
        canvas.width = Math.ceil(viewport.width);
        canvas.height = Math.ceil(viewport.height);
        await page.render({ canvasContext: canvas.getContext('2d'), viewport }).promise;
        thumbs.value = { ...thumbs.value, [doc.id]: canvas.toDataURL('image/jpeg', 0.8) };
        pdf.destroy();
    } catch {
        thumbs.value = { ...thumbs.value, [doc.id]: '' };
    }
};

const cargar = async () => {
    if (!props.legajoId) { docs.value = []; return; }
    const pedido = props.legajoId;
    cargando.value = true;
    error.value = false;
    try {
        const { data } = await axios.get(route('legajos.documentos', props.legajoId));
        if (pedido !== props.legajoId) return;   // se navegó a otro legajo mientras cargaba
        docs.value = Array.isArray(data) ? data : [];
        expandido.value = false;
        thumbs.value = {};
        // Secuencial: no lanzar decenas de PDF en paralelo.
        for (const d of docs.value.filter((x) => x.tipo === 'pdf')) {
            if (pedido !== props.legajoId) break;
            await miniaturaPdf(d);
        }
    } catch {
        if (pedido === props.legajoId) { docs.value = []; error.value = true; }
    } finally {
        if (pedido === props.legajoId) cargando.value = false;
    }
};

watch(() => [props.legajoId, props.refrescar], cargar, { immediate: true });
</script>

<template>
    <div class="md3-docs" aria-label="Documentos adjuntos del legajo">
        <div class="md3-docs__header">
            <i class="ri-attachment-2"></i>
            <span>Documentos</span>
            <span v-if="docs.length" class="md3-docs__count">{{ docs.length }}</span>
        </div>

        <div class="md3-docs__strip">
            <!-- Cargando la lista: esqueletos -->
            <template v-if="cargando && !docs.length">
                <div v-for="n in 3" :key="'sk' + n" class="md3-doc md3-doc--skeleton"><div class="md3-doc__thumb"></div></div>
            </template>

            <div v-else-if="error" class="md3-docs__empty"><i class="ri-error-warning-line"></i> No se pudieron cargar</div>
            <div v-else-if="!docs.length" class="md3-docs__empty"><i class="ri-file-forbid-line"></i> Sin documentos adjuntos</div>

            <a
                v-for="d in visibles"
                :key="d.id"
                :href="d.url"
                target="_blank"
                rel="noopener"
                class="md3-doc"
                :title="tooltip(d)"
            >
                <div class="md3-doc__thumb">
                    <img v-if="d.tipo === 'imagen'" :src="d.url" :alt="d.nombre" loading="lazy" />
                    <img v-else-if="d.tipo === 'pdf' && thumbs[d.id]" :src="thumbs[d.id]" :alt="d.nombre" />
                    <div v-else-if="d.tipo === 'pdf' && thumbs[d.id] === undefined" class="md3-doc__shimmer"></div>
                    <i v-else :class="d.tipo === 'pdf' ? 'ri-file-pdf-2-line' : 'ri-file-line'" class="md3-doc__icon"></i>
                    <span class="md3-doc__badge" :class="'md3-doc__badge--' + d.tipo">{{ d.ext?.toUpperCase() }}</span>
                    <span class="md3-doc__state"></span>
                </div>
                <span class="md3-doc__label">{{ etiqueta(d) }}</span>
            </a>

            <button
                v-if="ocultos > 0"
                type="button"
                class="md3-doc md3-doc--more"
                :title="expandido ? 'Mostrar menos' : `Ver los ${ocultos} restantes`"
                @click="expandido = !expandido"
            >
                <div class="md3-doc__thumb">
                    <span v-if="!expandido">+{{ ocultos }}</span>
                    <i v-else class="ri-arrow-left-s-line"></i>
                    <span class="md3-doc__state"></span>
                </div>
                <span class="md3-doc__label">{{ expandido ? 'Menos' : 'Más' }}</span>
            </button>
        </div>
    </div>
</template>

<style scoped>
.md3-docs {
    --md-primary: var(--bs-primary, #666cff);
    --md-surface: var(--bs-card-bg, #fff);
    --md-on-surface: var(--bs-heading-color, #2e263d);
    --md-on-surface-variant: var(--bs-body-color, #6d6777);
    --md-outline: var(--bs-border-color, #e6e5e8);
    --md-container: color-mix(in srgb, var(--md-primary) 8%, var(--md-surface));
    --md-container-high: color-mix(in srgb, var(--md-primary) 14%, var(--md-surface));
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 6px;
    min-width: 0;
    max-width: 100%;
}
.md3-docs__header {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.8125rem;
    font-weight: 500;
    letter-spacing: 0.1px;
    color: var(--md-on-surface-variant);
}
.md3-docs__count {
    min-width: 20px;
    height: 20px;
    padding: 0 6px;
    border-radius: 10px;
    background: var(--md-primary);
    color: #fff;
    font-size: 0.6875rem;
    line-height: 20px;
    text-align: center;
}
.md3-docs__strip {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 10px;
}
.md3-docs__empty {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    height: 32px;
    padding: 0 14px;
    border: 1px solid var(--md-outline);
    border-radius: 8px;
    font-size: 0.8125rem;
    color: var(--md-on-surface-variant);
}

.md3-doc {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    width: 72px;
    padding: 0;
    border: 0;
    background: none;
    text-decoration: none;
    color: inherit;
    cursor: pointer;
}
.md3-doc:focus-visible { outline: none; }
.md3-doc__thumb {
    position: relative;
    width: 72px;
    height: 72px;
    border-radius: 12px;
    overflow: hidden;
    background: var(--md-container);
    border: 1px solid var(--md-outline);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.12);
    transition: box-shadow 0.2s ease, transform 0.2s ease;
}
.md3-doc__thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: top center;
    background: #fff;
}
.md3-doc__icon { font-size: 28px; color: var(--md-primary); }
.md3-doc__badge {
    position: absolute;
    left: 4px;
    bottom: 4px;
    padding: 0 5px;
    border-radius: 4px;
    font-size: 0.5625rem;
    font-weight: 600;
    line-height: 14px;
    letter-spacing: 0.4px;
    color: #fff;
    background: var(--md-primary);
}
.md3-doc__badge--pdf { background: #e5484d; }
/* Capa de estado MD3: 8% al pasar, 12% al enfocar/presionar */
.md3-doc__state {
    position: absolute;
    inset: 0;
    background: var(--md-on-surface);
    opacity: 0;
    transition: opacity 0.15s ease;
    pointer-events: none;
}
.md3-doc:hover .md3-doc__thumb {
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.15), 0 4px 8px 3px rgba(0, 0, 0, 0.1);
    transform: translateY(-1px);
}
.md3-doc:hover .md3-doc__state { opacity: 0.08; }
.md3-doc:focus-visible .md3-doc__state,
.md3-doc:active .md3-doc__state { opacity: 0.12; }
.md3-doc:focus-visible .md3-doc__thumb { outline: 2px solid var(--md-primary); outline-offset: 2px; }
.md3-doc__label {
    max-width: 72px;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
    font-size: 0.6875rem;
    font-weight: 500;
    letter-spacing: 0.5px;
    color: var(--md-on-surface-variant);
}

.md3-doc--more .md3-doc__thumb {
    background: var(--md-container-high);
    border-style: dashed;
    color: var(--md-primary);
    font-weight: 600;
    font-size: 1rem;
}
.md3-doc--more .md3-doc__thumb i { font-size: 24px; }

.md3-doc__shimmer,
.md3-doc--skeleton .md3-doc__thumb {
    position: absolute;
    inset: 0;
    background: linear-gradient(90deg, var(--md-container) 0%, var(--md-container-high) 50%, var(--md-container) 100%);
    background-size: 200% 100%;
    animation: md3-shimmer 1.2s ease-in-out infinite;
}
.md3-doc--skeleton .md3-doc__thumb { position: relative; }
@keyframes md3-shimmer {
    from { background-position: 200% 0; }
    to { background-position: -200% 0; }
}
@media (prefers-reduced-motion: reduce) {
    .md3-doc__thumb, .md3-doc__state { transition: none; }
    .md3-doc__shimmer, .md3-doc--skeleton .md3-doc__thumb { animation: none; }
}
</style>
