"""
Genera database/seeders/motor/datos/v5/*.php desde el Excel funcional v5
(Zonda_Motor_Calculo_2026-08-30_FORMULAS_V3_v5.xlsx — modelo DBML v2).

Uso:  python3 -I generar_datos_v5.py <ruta_del_xlsx>

Emite los datos TAL CUAL vienen de la hoja (nombres de columna de la
especificación), normalizando sólo lo que el formato xlsx deforma:
fechas en serial de Excel, booleanos S/N y decimales en notación científica.
El mapeo a las columnas de las tablas motor_* lo hace la migración de seed,
para que el dato versionado siga siendo fiel a la fuente.
"""
import json, os, re, sys
from datetime import date, timedelta
from decimal import Decimal

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from xlsx import X

DESTINO = os.path.join(os.path.dirname(os.path.abspath(__file__)), '..', 'datos', 'v5')
EPOCH = date(1899, 12, 30)  # serial 1 = 1900-01-01

# Hojas a volcar y columnas que son fechas (serial de Excel) o booleanos S/N.
HOJAS = {
    'TAB_CONCEPTOS':                 {'fechas': ['vigencia_desde', 'vigencia_hasta'], 'bool': []},
    'BASES_ZONDA':                   {'fechas': [], 'bool': ['activo']},
    'BASE_ZONDA_INCIDENCIA':         {'fechas': ['vigencia_desde', 'vigencia_hasta'], 'bool': []},
    'CONCEPTO_INCIDENCIA_ARCA':      {'fechas': ['vigencia_desde', 'vigencia_hasta'], 'bool': []},
    'BASES_ARCA':                    {'fechas': [], 'bool': []},
    'SUBSISTEMAS':                   {'fechas': [], 'bool': []},
    'SUBSISTEMA_APLICACION':         {'fechas': [], 'bool': ['activo']},
    'ALICUOTAS_APORTES':             {'fechas': ['vigencia_desde', 'vigencia_hasta'], 'bool': []},
    'ALICUOTAS_CONTRIBUCIONES':      {'fechas': ['vigencia_desde', 'vigencia_hasta'], 'bool': []},
    'TOPES_MINIMOS':                 {'fechas': ['periodo', 'vigencia_desde', 'vigencia_hasta'], 'bool': []},
    'COMPONENTES_TOPE':              {'fechas': [], 'bool': []},
    'REGIMENES_CONTRIBUTIVOS':       {'fechas': ['vigencia_desde', 'vigencia_hasta'], 'bool': []},
    'REGIMEN_DISTRIBUCION':          {'fechas': ['vigencia_desde', 'vigencia_hasta'], 'bool': []},
    'REGLAS_BASE_DIFERENCIAL_ARCA':  {'fechas': ['vigencia_desde', 'vigencia_hasta'], 'bool': []},
    'ENCUADRAMIENTOS_PATRONALES':    {'fechas': [], 'bool': ['activo']},
    'CONDICIONES_ARCA':              {'fechas': [], 'bool': []},
    'MODALIDADES_ARCA':              {'fechas': [], 'bool': []},
    'SITUACIONES_REVISTA_ARCA':      {'fechas': [], 'bool': []},
    'PARAMETROS':                    {'fechas': [], 'bool': ['activo']},
    'VALORES_PARAMETROS':            {'fechas': ['vigencia_desde', 'vigencia_hasta'], 'bool': []},
    'VARIABLES':                     {'fechas': [], 'bool': ['activo']},
    'VALORES_VARIABLE':              {'fechas': ['vigencia_desde', 'vigencia_hasta'], 'bool': []},
    'TIPOS_NOVEDAD':                 {'fechas': [], 'bool': ['activo']},
    'MODELOS_ANTIGUEDAD':            {'fechas': [], 'bool': ['activo']},
    'ANTIGUEDAD_TRAMOS':             {'fechas': [], 'bool': []},
    'MODELOS_VACACIONES':            {'fechas': [], 'bool': ['activo']},
    'VACACIONES_TRAMOS':             {'fechas': [], 'bool': ['incluye_desde', 'incluye_hasta']},
    'CONCEPTO_VERSION':              {'fechas': ['vigencia_desde', 'vigencia_hasta'], 'bool': []},
    'CONCEPTO_VERSION_NOVEDAD':      {'fechas': [], 'bool': []},
    'CONCEPTO_VERSION_LITERAL':      {'fechas': [], 'bool': []},
    'PERFIL':                        {'fechas': ['vigencia_desde', 'vigencia_hasta'], 'bool': ['activo']},
    'PERFIL_CONCEPTO':               {'fechas': ['vigencia_desde', 'vigencia_hasta'], 'bool': ['aplica']},
    'MODULOS':                       {'fechas': [], 'bool': ['requiere_historico', 'activo']},
    'MODULO_DEPENDENCIA':            {'fechas': [], 'bool': []},
    'CATEGORIA_CONCEPTO':            {'fechas': [], 'bool': ['activo']},
    'CATEGORIA_CONCEPTO_VIGENCIA':   {'fechas': ['vigencia_desde', 'vigencia_hasta'], 'bool': []},
    'CONCEPTO_LEGACY_MAPEO':         {'fechas': ['vigencia_desde', 'vigencia_hasta'], 'bool': ['tiene_canti']},
}

CLAVES = ('id_', 'codigo', 'periodo', 'legajo')


def detectar_header(grid):
    for i, r in enumerate(grid):
        c0 = str(r[0]).strip()
        if not c0:
            continue
        if (c0.startswith(CLAVES) or c0 in ('nombre', 'variante', 'encuadramiento')) and sum(1 for x in r if str(x).strip()) >= 2:
            return i
    return None


def fecha(v):
    """Serial de Excel → 'YYYY-MM-DD'. Deja pasar lo que ya es fecha o texto."""
    s = str(v).strip()
    if not s:
        return None
    if re.fullmatch(r'\d{4}-\d{2}(-\d{2})?', s):
        return s if len(s) == 10 else s + '-01'
    if re.fullmatch(r'\d{1,6}(\.0+)?', s):
        return (EPOCH + timedelta(days=int(float(s)))).isoformat()
    return s


def numero(v):
    """Normaliza decimales (incluida la notación científica del xlsx) a texto sin ruido."""
    s = str(v).strip()
    if not s:
        return None
    try:
        d = Decimal(s).normalize()
        # 1.3100000000000001E-2 → 0.0131
        return format(d.quantize(Decimal('0.000000001')).normalize(), 'f')
    except Exception:
        return s


NUMERICAS = re.compile(r'^(porcentaje|importe|valor|factor|tarifa|alicuota|monto|dias|decimales|anios|meses|orden|cantidad)')


def limpiar(hoja, filas, conf):
    out = []
    for f in filas:
        d = {}
        for k, v in f.items():
            v = str(v).strip()
            if k in conf['fechas']:
                d[k] = fecha(v)
            elif k in conf['bool']:
                d[k] = None if v == '' else (1 if v.upper() in ('S', 'SI', 'TRUE', '1') else 0)
            elif v == '':
                d[k] = None
            elif NUMERICAS.match(k) and re.fullmatch(r'-?[\d.,eE+-]+', v):
                d[k] = numero(v)
            else:
                d[k] = v
        out.append(d)
    return out


def php(valor, ind=0):
    p = '    ' * ind
    if valor is None:
        return 'null'
    if isinstance(valor, bool):
        return 'true' if valor else 'false'
    if isinstance(valor, int):
        return str(valor)
    if isinstance(valor, list):
        if not valor:
            return '[]'
        cuerpo = ',\n'.join(p + '    ' + php(v, ind + 1) for v in valor)
        return '[\n' + cuerpo + ',\n' + p + ']'
    if isinstance(valor, dict):
        partes = ', '.join(f"'{k}' => {php(v, ind + 1)}" for k, v in valor.items())
        return '[' + partes + ']'
    s = str(valor).replace('\\', '\\\\').replace("'", "\\'")
    return f"'{s}'"


def main():
    if len(sys.argv) < 2:
        sys.exit('uso: generar_datos_v5.py <ruta_del_xlsx>')
    w = X(sys.argv[1])
    os.makedirs(DESTINO, exist_ok=True)
    resumen = {}
    for hoja, conf in HOJAS.items():
        if hoja not in w.names():
            print(f'  ! {hoja}: no está en el libro')
            continue
        cols, grid = w.table(hoja)
        hi = detectar_header(grid)
        if hi is None:
            print(f'  ! {hoja}: no se detectó encabezado')
            continue
        hdr = [str(c).strip() for c in grid[hi]]
        filas = []
        for r in grid[hi + 1:]:
            if not any(str(x).strip() for x in r):
                continue
            d = {hdr[i]: r[i] for i in range(min(len(hdr), len(r))) if hdr[i]}
            if any(str(v).strip() for v in d.values()):
                filas.append(d)
        datos = limpiar(hoja, filas, conf)
        destino = os.path.join(DESTINO, hoja.lower() + '.php')
        with open(destino, 'w') as fh:
            fh.write('<?php\n\n')
            fh.write(f'// Generado por generar_datos_v5.py desde el Excel funcional v5 (DBML v2). No editar a mano.\n')
            fh.write(f'// Hoja {hoja} — {len(datos)} filas.\n\n')
            fh.write('return ' + php(datos) + ";\n")
        resumen[hoja] = len(datos)
        print(f'  {hoja:32} {len(datos):4} filas → datos/v5/{hoja.lower()}.php')
    json.dump(resumen, open(os.path.join(DESTINO, 'conteos.json'), 'w'), indent=1)
    print(f'\n{len(resumen)} hojas generadas')


if __name__ == '__main__':
    main()
