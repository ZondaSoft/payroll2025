#!/usr/bin/env python3
"""
Vuelca las hojas de los Excel del especialista (Zonda) a arrays PHP en
database/seeders/motor/datos/ y el diccionario de campos a docs/motor/diccionario.md.

Uso: python3 database/seeders/motor/herramientas/generar_datos.py [carpeta_excel]
No hay openpyxl en el equipo: usa el parser propio xlsx.py (zipfile + ElementTree).
"""
import sys, os, re, json, datetime
sys.path.insert(0, os.path.dirname(__file__))
from xlsx import X

CARPETA = sys.argv[1] if len(sys.argv) > 1 else '/home/daniel/Documentos/Novedades/PayRoll2025'
FUNCIONAL = os.path.join(CARPETA, 'Zonda_Motor_Calculo_2026-08-30.xlsx')
TECNICO = os.path.join(CARPETA, 'Zonda_Especificacion_Tecnica_Tablas_2026-08-30.xlsx')
RAIZ = os.path.abspath(os.path.join(os.path.dirname(__file__), '..', '..', '..', '..'))
DATOS = os.path.join(RAIZ, 'database', 'seeders', 'motor', 'datos')
DOCS = os.path.join(RAIZ, 'docs', 'motor')

def serial_a_fecha(v):
    return (datetime.date(1899, 12, 30) + datetime.timedelta(days=int(float(v)))).isoformat()

COLS_FECHA = ('vigencia_desde', 'vigencia_hasta', 'fecha_ingreso', 'fecha_egreso', 'fecha_inicio_actividad', 'vencimiento_certificado')
COLS_PERIODO = ('periodo', 'periodo_inicio')
COLS_BOOL_SN = ('admite_tope', 'aplica_tope')
COLS_INT = ('id_base', 'id_subsistema', 'id_subsistema_aplicacion', 'columna_lsd', 'id_parametro', 'id_valor_parametro',
            'id_convenio', 'id_categoria', 'id_jornada', 'id_modelo_antiguedad', 'id_tramo_antiguedad', 'id_modelo_vacaciones',
            'id_tramo_vacaciones', 'id_asignacion_antiguedad', 'id_asignacion_vacaciones', 'id_basico', 'id_legajo',
            'id_tipo_novedad', 'id_novedad', 'id_concepto', 'id_categoria_concepto', 'id_base_zonda', 'id_incidencia',
            'id_incidencia_arca', 'orden', 'orden_aplicacion', 'duracion_meses', 'version', 'dias', 'orden_visual_sugerido',
            'activo', 'incluye_desde', 'incluye_hasta', 'requiere_novedad', 'editable_manual', 'repetible', 'imprime_recibo',
            'es_concepto_sistema', 'permite_importe_cero', 'horas_dia', 'horas_semanales', 'horas_mensuales', 'adherido_sindicato',
            'manejo_fondos', 'dia_desde', 'dia_hasta', 'legajo')

def normalizar(col, v):
    v = v.strip()
    if v == '':
        return None
    if col in COLS_FECHA:
        return serial_a_fecha(v) if re.fullmatch(r'\d+(\.0+)?', v) else v
    if col in COLS_PERIODO:
        if re.fullmatch(r'\d+(\.0+)?', v):
            return serial_a_fecha(v)[:7]
        return v[:7]
    if col in COLS_BOOL_SN:
        return 1 if v.upper() in ('S', 'SI', 'SÍ', '1', 'TRUE') else 0
    if col in COLS_INT:
        if v.lower() in ('true', 'false'):
            return 1 if v.lower() == 'true' else 0
        if re.fullmatch(r'-?\d+(\.0+)?', v):
            return int(float(v))
        return v
    return v

def hoja(x, nombre, clave):
    c, g = x.table(nombre)
    idx = [i for i, r in enumerate(g) if r and r[0].strip() == clave]
    if not idx:
        raise SystemExit(f'{nombre}: no se encontró el encabezado "{clave}"')
    s = idx[0]
    hdr = [h.strip() for h in g[s]]
    filas = []
    for r in g[s + 1:]:
        if not r or not r[0].strip():
            continue
        fila = {}
        for j, h in enumerate(hdr):
            if not h:
                continue
            fila[h] = normalizar(h, r[j] if j < len(r) else '')
        filas.append(fila)
    return hdr, filas

def php(v):
    if v is None:
        return 'null'
    if isinstance(v, bool):
        return 'true' if v else 'false'
    if isinstance(v, int):
        return str(v)
    return "'" + str(v).replace('\\', '\\\\').replace("'", "\\'") + "'"

def escribir_php(nombre_archivo, nombre_hoja, filas, comentario=''):
    ruta = os.path.join(DATOS, nombre_archivo)
    with open(ruta, 'w', encoding='utf-8') as f:
        f.write('<?php\n\n')
        f.write(f'// Generado por database/seeders/motor/herramientas/generar_datos.py desde la hoja {nombre_hoja}\n')
        f.write('// de Zonda_Motor_Calculo_2026-08-30.xlsx. No editar a mano: regenerar.\n')
        if comentario:
            f.write('// ' + comentario + '\n')
        f.write('\nreturn [\n')
        for fila in filas:
            f.write('    [' + ', '.join(f"'{k}' => {php(v)}" for k, v in fila.items()) + '],\n')
        f.write('];\n')
    return len(filas)

x = X(FUNCIONAL)
HOJAS = [
    # (hoja, clave de encabezado, archivo destino, comentario)
    ('BASES_ARCA', 'id_base', 'bases_arca.php', ''),
    ('SUBSISTEMAS', 'id_subsistema', 'subsistemas.php', ''),
    ('SUBSISTEMA_APLICACION', 'id_subsistema_aplicacion', 'subsistema_aplicacion.php', ''),
    ('ENCUADRAMIENTOS_PATRONALES', 'encuadramiento', 'encuadramientos_patronales.php', ''),
    ('COMPONENTES_TOPE', 'codigo_componente', 'componentes_tope.php', ''),
    ('CONDICIONES_ARCA', 'codigo_condicion', 'condiciones_arca.php', ''),
    ('MODALIDADES_ARCA', 'codigo_modalidad', 'modalidades_arca.php', ''),
    ('SITUACIONES_REVISTA_ARCA', 'codigo_situacion', 'situaciones_revista_arca.php', ''),
    ('ALICUOTAS_CONTRIBUCIONES', 'id_alicuota', 'alicuotas_contribuciones.php', 'porcentaje como fracción (0.1077 = 10,77 %)'),
    ('ALICUOTAS_APORTES', 'id_alicuota_aporte', 'alicuotas_aportes.php', "condicion_arca 'TODAS' = comodín (se persiste como NULL)"),
    ('TOPES_MINIMOS', 'id_tope', 'topes_minimos.php', 'periodo en YYYY-MM; importe NULL = SIN_DATO'),
    ('REGIMENES_CONTRIBUTIVOS', 'id_regimen', 'regimenes_contributivos.php', ''),
    ('PARAMETROS', 'id_parametro', 'parametros.php', ''),
    ('VALORES_PARAMETROS', 'id_valor_parametro', 'valores_parametros.php', 'SOLO FIXTURE: referencian ids de convenio/categoría del caso de prueba, no de sue006s/sue007s'),
    ('MODELOS_ANTIGUEDAD', 'id_modelo_antiguedad', 'modelos_antiguedad.php', ''),
    ('ANTIGUEDAD_TRAMOS', 'id_tramo_antiguedad', 'antiguedad_tramos.php', ''),
    ('CONVENIO_MODELO_ANTIGUEDAD', 'id_asignacion_antiguedad', 'convenio_modelo_antiguedad.php', 'SOLO FIXTURE'),
    ('MODELOS_VACACIONES', 'id_modelo_vacaciones', 'modelos_vacaciones.php', ''),
    ('VACACIONES_TRAMOS', 'id_tramo_vacaciones', 'vacaciones_tramos.php', ''),
    ('CONVENIO_MODELO_VACACIONES', 'id_asignacion_vacaciones', 'convenio_modelo_vacaciones.php', 'SOLO FIXTURE'),
    ('BASICOS', 'id_basico', 'basicos.php', 'SOLO FIXTURE'),
    ('TIPOS_NOVEDAD', 'id_tipo_novedad', 'tipos_novedad.php', ''),
    ('TAB_CONCEPTOS', 'id_concepto', 'conceptos.php', ''),
    ('CATEGORIA_CONCEPTO', 'id_categoria_concepto', 'categoria_concepto.php', 'SOLO FIXTURE'),
    ('BASES_ZONDA', 'id_base_zonda', 'bases_zonda.php', ''),
    ('BASE_ZONDA_INCIDENCIA', 'id_incidencia', 'base_zonda_incidencia.php', ''),
    ('CONCEPTO_INCIDENCIA_ARCA', 'id_incidencia_arca', 'concepto_incidencia_arca.php', ''),
    ('CONVENIOS', 'id_convenio', 'fixture_convenios.php', 'SOLO FIXTURE (LEGACY_REFERENCIA)'),
    ('JORNADAS', 'id_jornada', 'fixture_jornadas.php', 'SOLO FIXTURE (LEGACY_REFERENCIA)'),
    ('CATEGORIAS', 'id_categoria', 'fixture_categorias.php', 'SOLO FIXTURE (LEGACY_REFERENCIA)'),
    ('LEGAJOS', 'id_legajo', 'fixture_legajos.php', 'SOLO FIXTURE (LEGACY_REFERENCIA)'),
    ('EMPRESAS', 'empresa_id', 'fixture_empresas.php', 'SOLO FIXTURE (LEGACY_REFERENCIA)'),
    ('NOVEDADES', 'id_novedad', 'fixture_novedades.php', 'SOLO FIXTURE'),
    ('RELACION_LABORAL', 'relacion_laboral_id', 'fixture_relacion_laboral.php', 'SOLO FIXTURE'),
    ('RELACION_LABORAL_CATEGORIA', 'relacion_categoria_id', 'fixture_relacion_laboral_categoria.php', 'SOLO FIXTURE'),
    ('RELACION_LABORAL_ARCA', 'encuadre_sicoss_id', 'fixture_relacion_laboral_arca.php', 'SOLO FIXTURE'),
    ('RELACION_LABORAL_REGIMEN', 'relacion_regimen_id', 'fixture_relacion_laboral_regimen.php', 'SOLO FIXTURE'),
    ('SITUACION_REVISTA_PERIODO', 'situacion_periodo_id', 'fixture_situacion_revista_periodo.php', 'SOLO FIXTURE'),
]
resumen = []
for nombre, clave, archivo, com in HOJAS:
    hdr, filas = hoja(x, nombre, clave)
    n = escribir_php(archivo, nombre, filas, com)
    resumen.append((archivo, n))
    print(f'{archivo:48s} {n:4d} filas')

# ---- diccionario técnico → markdown ----
t = X(TECNICO)
c, g = t.table('DICCIONARIO_CAMPOS')
rows = [r for r in g[3:] if r and r[0].strip()]
por = {}
for r in rows:
    por.setdefault(r[0].strip(), []).append(r)
c2, g2 = t.table('DICCIONARIO_TABLAS')
meta = {}
for r in g2[3:]:
    if r and r[0].strip():
        meta[r[0].strip()] = {'arq': r[1].strip(), 'accion': r[2].strip(), 'estado': r[3].strip(), 'finalidad': r[6].strip() if len(r) > 6 else ''}
c3, g3 = t.table('RELACIONES_TECNICAS')
rel = [r for r in g3[3:] if r and r[0].strip()]
with open(os.path.join(DOCS, 'diccionario.md'), 'w', encoding='utf-8') as f:
    f.write('# Diccionario técnico del Motor (fuente: Zonda_Especificacion_Tecnica_Tablas_2026-08-30.xlsx)\n\n')
    f.write('Generado por `database/seeders/motor/herramientas/generar_datos.py`. Fuente única para migraciones, modelos y entidades.\n')
    f.write('Tabla física = `motor_` + nombre en minúsculas. Las desviaciones aplicadas se listan en el plan (§11).\n\n')
    for tabla, campos in por.items():
        m = meta.get(tabla, {})
        f.write(f'## {tabla}  →  `motor_{tabla.lower()}`\n\n')
        f.write(f"**{m.get('arq','')}** · {m.get('accion','')} · {m.get('estado','')}\n\n")
        if m.get('finalidad'):
            f.write(m['finalidad'] + '\n\n')
        f.write('| campo | tipo | null | pk | uq | ai | fk |\n|---|---|---|---|---|---|---|\n')
        for r in campos:
            campo, tipo, nul, pk, uq, ai, fkt, fkc = [v.strip() for v in r[1:9]]
            fk = f'{fkt}.{fkc}' if fkt else ''
            f.write(f'| `{campo}` | {tipo} | {nul} | {pk} | {uq} | {ai} | {fk} |\n')
        f.write('\n')
    f.write('## RELACIONES_TECNICAS\n\n| origen | fk | destino | pk/uk | card. | estado | on_delete | on_update |\n|---|---|---|---|---|---|---|---|\n')
    for r in rel:
        v = [s.strip() for s in r]
        f.write(f'| {v[1]} | {v[2]} | {v[3]} | {v[4]} | {v[5]} | {v[6]} | {v[8] if len(v)>8 else ""} | {v[9] if len(v)>9 else ""} |\n')
print('diccionario.md escrito:', os.path.join(DOCS, 'diccionario.md'))
