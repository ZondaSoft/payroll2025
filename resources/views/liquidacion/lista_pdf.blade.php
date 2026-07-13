<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Liquidación {{ $periodoFmt }} — Vista de lista</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 10px; color: #333; margin: 24px; }
        h1 { font-size: 15px; margin: 0 0 2px 0; }
        .sub { color: #777; margin-bottom: 14px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 4px 6px; }
        th { background: #f0f0f5; text-align: left; }
        td.num, th.num { text-align: right; }
        tfoot td { font-weight: bold; background: #f0f0f5; }
        .neg { color: #dc3545; }
    </style>
</head>
<body>
    <h1>Liquidación — Vista de lista</h1>
    <div class="sub">
        {{ $empresa?->razon ?? $empresa?->detalle ?? '' }}
        &nbsp;·&nbsp; Período {{ $periodoFmt }}
        &nbsp;·&nbsp; Tipo de liquidación: {{ $tipoLiqNombre }}
        &nbsp;·&nbsp; {{ count($lineas) }} legajos
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:60px;">Legajo</th>
                <th>Apellido y Nombre</th>
                <th>Convenio</th>
                <th>Categoría</th>
                <th class="num" style="width:95px;">Neto a Pagar</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($lineas as $l)
                <tr>
                    <td>{{ $l['legajo'] }}</td>
                    <td>{{ $l['nombre'] ?: '—' }}</td>
                    <td>{{ $l['convenio'] ?: '—' }}</td>
                    <td>{{ $l['categoria'] ?: '—' }}</td>
                    <td class="num {{ $l['neto'] < 0 ? 'neg' : '' }}">$ {{ number_format($l['neto'], 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" style="text-align:right;">Total Neto a Pagar</td>
                <td class="num {{ $totalNeto < 0 ? 'neg' : '' }}">$ {{ number_format($totalNeto, 2, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
