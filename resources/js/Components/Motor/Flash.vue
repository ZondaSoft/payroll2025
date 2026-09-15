<script setup>
import { usePage } from '@inertiajs/vue3'
import { watch } from 'vue'
import Swal from 'sweetalert2'

// Muestra los mensajes flash de sesión (success/error/warning) como toast; el resto del feedback lo hacen los onSuccess.
const page = usePage()
const mostrar = (flash) => {
  if (!flash) return
  const tipo = flash.success ? 'success' : flash.error ? 'error' : flash.warning ? 'warning' : null
  if (!tipo) return
  Swal.fire({ icon: tipo, title: flash.success || flash.error || flash.warning, toast: true, position: 'top-end', timer: 2500, showConfirmButton: false })
}
watch(() => page.props.flash, mostrar, { immediate: true, deep: true })
</script>

<template><span hidden></span></template>
