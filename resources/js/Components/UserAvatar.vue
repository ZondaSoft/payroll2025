<script setup>
// Avatar redondo reutilizable. Muestra la foto (src) y, si no está disponible
// —src vacío o la imagen falla al cargar (404, etc.)— cae a un avatar con las
// iniciales del nombre sobre un color estable. El fallback no depende de ningún
// archivo, así que funciona en cualquier entorno (incluida la subcarpeta de prod).
import { ref, computed, watch } from 'vue'

const props = defineProps({
  src: { type: String, default: '' },
  name: { type: String, default: '' },
  size: { type: [Number, String], default: 55 },
  alt: { type: String, default: 'Avatar' },
})

// ¿Falló la carga de la imagen? → mostramos iniciales.
const error = ref(false)
// Al cambiar de src (otro empleado) reintentamos la imagen.
watch(() => props.src, () => { error.value = false })

const mostrarImagen = computed(() => !!props.src && !error.value)

const iniciales = computed(() => {
  const limpio = (props.name || '').trim()
  if (!limpio) return '?'
  const partes = limpio.split(/\s+/).filter(Boolean)
  const primera = partes[0]?.[0] ?? ''
  const segunda = partes.length > 1 ? partes[partes.length - 1][0] : (partes[0]?.[1] ?? '')
  return (primera + segunda).toUpperCase() || '?'
})

// Color de fondo estable derivado del nombre (misma persona → mismo color).
const PALETA = ['#7e3ff2', '#0d6efd', '#20c997', '#fd7e14', '#d63384', '#6610f2', '#198754', '#dc3545', '#0dcaf0', '#6f42c1']
const bgColor = computed(() => {
  const s = props.name || '?'
  let h = 0
  for (let i = 0; i < s.length; i++) h = (h * 31 + s.charCodeAt(i)) >>> 0
  return PALETA[h % PALETA.length]
})

const sizePx = computed(() => `${parseInt(props.size, 10) || 55}px`)
const fontPx = computed(() => `${Math.round((parseInt(props.size, 10) || 55) * 0.4)}px`)
</script>

<template>
  <img
    v-if="mostrarImagen"
    :src="src"
    :alt="alt"
    class="rounded-circle border"
    :style="{ width: sizePx, height: sizePx, objectFit: 'cover' }"
    @error="error = true"
  >
  <div
    v-else
    class="rounded-circle border d-inline-flex align-items-center justify-content-center text-white fw-semibold"
    :style="{ width: sizePx, height: sizePx, backgroundColor: bgColor, fontSize: fontPx, lineHeight: 1, userSelect: 'none' }"
    :title="name || alt"
    :aria-label="name || alt"
  >
    {{ iniciales }}
  </div>
</template>
