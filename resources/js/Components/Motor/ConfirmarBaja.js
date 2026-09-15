import Swal from 'sweetalert2'

// Diálogo de confirmación MD3: Cancelar a la izquierda, primario a la derecha, botones ovalados.
export async function confirmar({ titulo, html, confirmar = 'Sí, continuar', peligro = false }) {
  const r = await Swal.fire({
    icon: peligro ? 'warning' : 'question',
    title: titulo,
    html,
    showCancelButton: true,
    reverseButtons: true,
    buttonsStyling: false,
    confirmButtonText: confirmar,
    cancelButtonText: 'Cancelar',
    customClass: { confirmButton: `btn rounded-pill ${peligro ? 'btn-danger' : 'btn-primary'} ms-2`, cancelButton: 'btn rounded-pill btn-outline-secondary' },
  })
  return r.isConfirmed
}

export function exito(titulo) {
  return Swal.fire({ icon: 'success', title: titulo, timer: 1500, showConfirmButton: false })
}

export function errorValidacion(errores) {
  return Swal.fire({ icon: 'error', title: 'Revisá los datos', html: `<div class="text-start small">${Object.values(errores).map((e) => `• ${e}`).join('<br>')}</div>`, confirmButtonText: 'Cerrar', buttonsStyling: false, customClass: { confirmButton: 'btn rounded-pill btn-primary' } })
}
