import Swal from 'sweetalert2'

const esc = (s) => String(s ?? '')
  .replace(/&/g, '&amp;')
  .replace(/</g, '&lt;')
  .replace(/>/g, '&gt;')
  .replace(/"/g, '&quot;')
  .replace(/'/g, '&#39;')

/**
 * Muestra un modal de aviso (icono warning) con los conceptos cuyo `tipo`
 * se corrigió automáticamente de formato numérico a letra, según los
 * Rangos de conceptos (Configuración > Rangos de conceptos).
 *
 * @param {Array<{codigo, detalle, anterior, nuevo}>} ajustes
 */
export function avisarAjustesTipos(ajustes) {
  if (!Array.isArray(ajustes) || ajustes.length === 0) return

  const filas = ajustes.map(a => `
    <tr>
      <td style="padding:4px 8px;border:1px solid #dee2e6;font-family:monospace;">${esc(a.codigo)}</td>
      <td style="padding:4px 8px;border:1px solid #dee2e6;">${esc(a.detalle || '—')}</td>
      <td style="padding:4px 8px;border:1px solid #dee2e6;text-align:center;color:#6c757d;">${esc(a.anterior)}</td>
      <td style="padding:4px 8px;border:1px solid #dee2e6;text-align:center;">→</td>
      <td style="padding:4px 8px;border:1px solid #dee2e6;text-align:center;font-weight:600;">${esc(a.nuevo)}</td>
    </tr>
  `).join('')

  Swal.fire({
    icon: 'warning',
    title: 'Tipos de concepto ajustados',
    html: `
      <div style="text-align:left;font-size:14px;">
        <p style="margin-bottom:12px;">
          Se ajustaron automáticamente <strong>${ajustes.length}</strong> concepto(s) que tenían el tipo en
          formato numérico, según los Rangos de conceptos (Configuración &gt; Rangos de conceptos):
        </p>
        <div style="max-height:300px;overflow:auto;">
          <table style="width:100%;border-collapse:collapse;font-size:12px;">
            <thead style="position:sticky;top:0;background:#f8f9fa;">
              <tr>
                <th style="padding:6px 8px;border:1px solid #dee2e6;text-align:left;">Código</th>
                <th style="padding:6px 8px;border:1px solid #dee2e6;text-align:left;">Descripción</th>
                <th style="padding:6px 8px;border:1px solid #dee2e6;text-align:center;">Antes</th>
                <th style="padding:6px 8px;border:1px solid #dee2e6;"></th>
                <th style="padding:6px 8px;border:1px solid #dee2e6;text-align:center;">Ahora</th>
              </tr>
            </thead>
            <tbody>${filas}</tbody>
          </table>
        </div>
      </div>
    `,
    width: 760,
    confirmButtonText: 'Entendido',
  })
}
