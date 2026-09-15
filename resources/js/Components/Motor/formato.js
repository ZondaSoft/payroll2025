export const importe = (n, dec = 2) => {
  if (n === null || n === undefined || n === '') return '—'
  return new Intl.NumberFormat('es-AR', { minimumFractionDigits: dec, maximumFractionDigits: dec }).format(Number(n))
}
export const porcentaje = (fraccion, dec = 2) => {
  if (fraccion === null || fraccion === undefined || fraccion === '') return '—'
  return `${importe(Number(fraccion) * 100, dec)} %`
}
export const fecha = (f) => (f ? String(f).substring(0, 10).split('-').reverse().join('/') : '—')
export const periodo = (p) => {
  if (!p) return '—'
  const s = String(p)
  return s.length === 6 ? `${s.substring(4, 6)}/${s.substring(0, 4)}` : s.split('-').reverse().join('/')
}
export const escapeHtml = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]))
