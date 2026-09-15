<script setup>
defineProps({ estado: { type: String, default: null }, small: { type: Boolean, default: false } })
const clases = { SIN_DATO: 'bg-label-danger', SIN_CONFIRMAR: 'bg-label-warning', CONFIRMADO: 'bg-label-success' }
const etiquetas = { SIN_DATO: 'Sin dato', SIN_CONFIRMAR: 'Sin confirmar', CONFIRMADO: 'Confirmado' }
</script>

<template>
  <span v-if="estado" class="badge" :class="[clases[estado] || 'bg-label-secondary', small ? 'small' : '']" :title="'Estado de validación: ' + (etiquetas[estado] || estado)">
    {{ etiquetas[estado] || estado }}
  </span>
</template>
