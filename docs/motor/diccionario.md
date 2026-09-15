# Diccionario técnico del Motor (fuente: Zonda_Especificacion_Tecnica_Tablas_2026-08-30.xlsx)

Generado por `database/seeders/motor/herramientas/generar_datos.py`. Fuente única para migraciones, modelos y entidades.
Tabla física = `motor_` + nombre en minúsculas. Las desviaciones aplicadas se listan en el plan (§11).

## JORNADAS  →  `motor_jornadas`

**LEGACY_REFERENCIA** · REUTILIZAR_MEDIANTE_ADAPTADOR · REQUIERE_MAPEO_GESTION

Define jornadas completas de referencia reutilizables por distintos convenios.

| campo | tipo | null | pk | uq | ai | fk |
|---|---|---|---|---|---|---|
| `id_jornada` | BIGINT UNSIGNED | NO | SI | SI | SI |  |
| `codigo` | VARCHAR(50) | NO | NO | SI | NO |  |
| `descripcion` | VARCHAR(100) | NO | NO | NO | NO |  |
| `horas_dia` | DECIMAL(12,4) | NO | NO | NO | NO |  |
| `horas_semanales` | DECIMAL(12,4) | NO | NO | NO | NO |  |
| `horas_mensuales` | DECIMAL(12,4) | NO | NO | NO | NO |  |
| `activo` | BOOLEAN | NO | NO | NO | NO |  |

## CONVENIOS  →  `motor_convenios`

**LEGACY_REFERENCIA** · REUTILIZAR_MEDIANTE_ADAPTADOR · REQUIERE_MAPEO_GESTION

Contiene la identidad estable de cada convenio colectivo utilizado por Zonda.

| campo | tipo | null | pk | uq | ai | fk |
|---|---|---|---|---|---|---|
| `id_convenio` | BIGINT UNSIGNED | SI | SI | SI | SI |  |
| `codigo` | VARCHAR(50) | NO | NO | SI | NO |  |
| `numero_cct` | VARCHAR(100) | NO | NO | NO | NO |  |
| `descripcion` | VARCHAR(100) | NO | NO | NO | NO |  |
| `id_jornada` | BIGINT UNSIGNED | NO | NO | NO | NO | JORNADAS.id_jornada |
| `activo` | BOOLEAN | NO | NO | NO | NO |  |

## CATEGORIAS  →  `motor_categorias`

**LEGACY_REFERENCIA** · REUTILIZAR_MEDIANTE_ADAPTADOR · REQUIERE_MAPEO_GESTION

Contiene la identificación estable de las categorías laborales de cada convenio.

| campo | tipo | null | pk | uq | ai | fk |
|---|---|---|---|---|---|---|
| `id_categoria` | BIGINT UNSIGNED | SI | SI | SI | SI |  |
| `codigo` | VARCHAR(50) | NO | NO | SI | NO |  |
| `id_convenio` | BIGINT UNSIGNED | SI | NO | NO | NO | CONVENIOS.id_convenio |
| `descripcion` | VARCHAR(100) | NO | NO | NO | NO |  |
| `activo` | BOOLEAN | NO | NO | NO | NO |  |

## BASICOS  →  `motor_basicos`

**MOTOR** · CREAR_MOTOR · LISTO_ESPECIFICACION

Contiene los sueldos básicos de cada categoría con su correspondiente vigencia.

| campo | tipo | null | pk | uq | ai | fk |
|---|---|---|---|---|---|---|
| `id_basico` | BIGINT UNSIGNED | NO | SI | SI | SI |  |
| `id_categoria` | BIGINT UNSIGNED | SI | NO | NO | NO | CATEGORIAS.id_categoria |
| `vigencia_desde` | DATE | NO | NO | NO | NO |  |
| `vigencia_hasta` | DATE | SI | NO | NO | NO |  |
| `importe_basico` | DECIMAL(18,6) | NO | NO | NO | NO |  |
| `moneda` | VARCHAR(100) | NO | NO | NO | NO |  |
| `fuente_normativa` | TEXT | SI | NO | NO | NO |  |

## LEGAJOS  →  `motor_legajos`

**LEGACY_REFERENCIA** · REUTILIZAR_MEDIANTE_ADAPTADOR · REQUIERE_MAPEO_GESTION

| campo | tipo | null | pk | uq | ai | fk |
|---|---|---|---|---|---|---|
| `id_legajo` | BIGINT UNSIGNED | NO | SI | SI | SI |  |
| `legajo` | VARCHAR(50) | NO | NO | NO | NO |  |
| `apellido` | VARCHAR(100) | NO | NO | NO | NO |  |
| `nombre` | VARCHAR(100) | NO | NO | NO | NO |  |
| `cuil` | VARCHAR(20) | SI | NO | NO | NO |  |
| `regimen` | VARCHAR(50) | NO | NO | NO | NO |  |
| `salud` | VARCHAR(50) | NO | NO | NO | NO |  |
| `estudios` | VARCHAR(50) | NO | NO | NO | NO |  |
| `adherido_sindicato` | BOOLEAN | NO | NO | NO | NO |  |
| `manejo_fondos` | BOOLEAN | NO | NO | NO | NO |  |
| `horas_semanales_pactadas` | DECIMAL(12,4) | NO | NO | NO | NO |  |

## TIPOS_NOVEDAD  →  `motor_tipos_novedad`

**LEGACY_REFERENCIA** · REUTILIZAR_MEDIANTE_ADAPTADOR · REQUIERE_MAPEO_GESTION

Define los códigos de novedades que pueden ser utilizados por el motor de cálculo.

| campo | tipo | null | pk | uq | ai | fk |
|---|---|---|---|---|---|---|
| `id_tipo_novedad` | BIGINT UNSIGNED | NO | SI | SI | SI |  |
| `codigo` | VARCHAR(50) | NO | NO | SI | NO |  |
| `descripcion` | VARCHAR(100) | NO | NO | NO | NO |  |
| `unidad` | VARCHAR(50) | NO | NO | NO | NO |  |
| `activo` | BOOLEAN | NO | NO | NO | NO |  |

## NOVEDADES  →  `motor_novedades`

**LEGACY_REFERENCIA** · REUTILIZAR_MEDIANTE_ADAPTADOR · REQUIERE_MAPEO_GESTION

Contiene las cantidades variables informadas para cada empleado y período de liquidación.

| campo | tipo | null | pk | uq | ai | fk |
|---|---|---|---|---|---|---|
| `id_novedad` | BIGINT UNSIGNED | NO | SI | SI | SI |  |
| `periodo` | CHAR(7) | NO | NO | NO | NO |  |
| `relacion_laboral_id` | BIGINT UNSIGNED | NO | NO | NO | NO | RELACION_LABORAL.relacion_laboral_id |
| `codigo_novedad` | VARCHAR(50) | NO | NO | NO | NO | TIPOS_NOVEDAD.codigo |
| `cantidad` | DECIMAL(12,4) | NO | NO | NO | NO |  |

## TAB_CONCEPTOS  →  `motor_tab_conceptos`

**MOTOR** · CREAR_MOTOR · LISTO_ESPECIFICACION

Maestro de conceptos del motor. Cada fila es un concepto único y estable del sistema.

| campo | tipo | null | pk | uq | ai | fk |
|---|---|---|---|---|---|---|
| `id_concepto` | BIGINT UNSIGNED | NO | SI | SI | SI |  |
| `codigo_concepto` | VARCHAR(50) | NO | NO | SI | NO |  |
| `nombre_concepto` | VARCHAR(255) | NO | NO | NO | NO |  |
| `alias_recibo` | VARCHAR(100) | SI | NO | NO | NO |  |
| `convenio_documental` | VARCHAR(100) | NO | NO | NO | NO |  |
| `tipo_concepto` | VARCHAR(100) | NO | NO | NO | NO |  |
| `naturaleza` | VARCHAR(50) | NO | NO | NO | NO |  |
| `signo` | VARCHAR(50) | NO | NO | NO | NO |  |
| `unidad_principal` | VARCHAR(100) | NO | NO | NO | NO |  |
| `modo_calculo` | VARCHAR(100) | NO | NO | NO | NO |  |
| `modulo_responsable` | VARCHAR(100) | NO | NO | NO | NO |  |
| `base_modulo` | VARCHAR(100) | SI | NO | NO | NO | BASES_ARCA.codigo_base |
| `codigo_parametro` | VARCHAR(50) | SI | NO | NO | NO | PARAMETROS.codigo |
| `codigo_novedad_cantidad` | VARCHAR(50) | SI | NO | NO | NO | TIPOS_NOVEDAD.codigo |
| `id_formula_base` | BIGINT UNSIGNED | SI | NO | NO | NO |  |
| `requiere_novedad` | BOOLEAN | NO | NO | NO | NO |  |
| `editable_manual` | BOOLEAN | NO | NO | NO | NO |  |
| `repetible` | BOOLEAN | NO | NO | NO | NO |  |
| `permite_importe_cero` | DECIMAL(18,6) | NO | NO | NO | NO |  |
| `imprime_recibo` | BOOLEAN | NO | NO | NO | NO |  |
| `es_concepto_sistema` | BOOLEAN | NO | NO | NO | NO |  |
| `tipo_repeticion` | VARCHAR(100) | NO | NO | NO | NO |  |
| `orden_visual_sugerido` | INT | NO | NO | NO | NO |  |
| `vigencia_desde` | DATE | NO | NO | NO | NO |  |
| `vigencia_hasta` | DATE | SI | NO | NO | NO |  |
| `activo` | BOOLEAN | NO | NO | NO | NO |  |
| `observaciones_funcionales` | TEXT | NO | NO | NO | NO |  |

## CATEGORIA_CONCEPTO  →  `motor_categoria_concepto`

**MOTOR** · CREAR_MOTOR · LISTO_ESPECIFICACION

Declara qué conceptos aplican a una categoría determinada cuando esa aplicabilidad NO es común a todo el convenio.

| campo | tipo | null | pk | uq | ai | fk |
|---|---|---|---|---|---|---|
| `id_categoria_concepto` | BIGINT UNSIGNED | NO | SI | SI | SI |  |
| `id_categoria` | BIGINT UNSIGNED | SI | NO | NO | NO | CATEGORIAS.id_categoria |
| `codigo_concepto` | VARCHAR(50) | NO | NO | SI | NO | TAB_CONCEPTOS.codigo_concepto |
| `accion` | VARCHAR(100) | NO | NO | NO | NO |  |
| `vigencia_desde` | DATE | NO | NO | NO | NO |  |
| `vigencia_hasta` | DATE | SI | NO | NO | NO |  |
| `estado_validacion` | VARCHAR(100) | NO | NO | NO | NO |  |
| `fuente` | VARCHAR(255) | NO | NO | NO | NO |  |
| `observaciones` | TEXT | SI | NO | NO | NO |  |

## BASES_ZONDA  →  `motor_bases_zonda`

**MOTOR** · CREAR_MOTOR · LISTO_ESPECIFICACION

Catálogo de las bases de cálculo propias de Zonda: agrupaciones reutilizables de conceptos que una fórmula o un módulo del motor puede consumir.

| campo | tipo | null | pk | uq | ai | fk |
|---|---|---|---|---|---|---|
| `id_base_zonda` | BIGINT UNSIGNED | NO | SI | SI | SI |  |
| `codigo_base` | VARCHAR(50) | SI | NO | SI | NO |  |
| `nombre` | VARCHAR(100) | NO | NO | NO | NO |  |
| `descripcion` | TEXT | NO | NO | NO | NO |  |
| `activo` | BOOLEAN | NO | NO | NO | NO |  |
| `estado_validacion` | VARCHAR(100) | NO | NO | NO | NO |  |
| `observaciones` | TEXT | SI | NO | NO | NO |  |

## BASE_ZONDA_INCIDENCIA  →  `motor_base_zonda_incidencia`

**MOTOR** · CREAR_MOTOR · LISTO_ESPECIFICACION

Relación N:M entre BASES_ZONDA y TAB_CONCEPTOS. Registra, para cada par base–concepto, la decisión explícita sobre si el concepto integra esa base, con su vigencia.

| campo | tipo | null | pk | uq | ai | fk |
|---|---|---|---|---|---|---|
| `id_incidencia` | BIGINT UNSIGNED | NO | SI | SI | SI |  |
| `id_base_zonda` | BIGINT UNSIGNED | NO | NO | NO | NO | BASES_ZONDA.id_base_zonda |
| `codigo_concepto` | VARCHAR(50) | NO | NO | NO | NO | TAB_CONCEPTOS.codigo_concepto |
| `incidencia` | VARCHAR(100) | NO | NO | NO | NO |  |
| `vigencia_desde` | DATE | NO | NO | NO | NO |  |
| `vigencia_hasta` | DATE | SI | NO | NO | NO |  |
| `estado_validacion` | VARCHAR(100) | NO | NO | NO | NO |  |
| `observaciones` | TEXT | SI | NO | NO | NO |  |

## MODELOS_ANTIGUEDAD  →  `motor_modelos_antiguedad`

**MOTOR** · CREAR_MOTOR · LISTO_ESPECIFICACION

Contiene modelos de escalas de antigüedad reutilizables por diferentes convenios.

| campo | tipo | null | pk | uq | ai | fk |
|---|---|---|---|---|---|---|
| `id_modelo_antiguedad` | BIGINT UNSIGNED | NO | SI | SI | SI |  |
| `codigo` | VARCHAR(50) | NO | NO | SI | NO |  |
| `descripcion` | VARCHAR(255) | NO | NO | NO | NO |  |
| `metodo_calculo` | VARCHAR(100) | NO | NO | NO | NO |  |
| `porcentaje_por_anio` | DECIMAL(18,6) | SI | NO | NO | NO |  |
| `porcentaje_maximo` | DECIMAL(18,6) | SI | NO | NO | NO |  |
| `version` | INT | NO | NO | NO | NO |  |
| `estado` | VARCHAR(50) | SI | NO | NO | NO |  |
| `estado_validacion` | VARCHAR(100) | NO | NO | NO | NO |  |
| `activo` | BOOLEAN | NO | NO | NO | NO |  |
| `observaciones` | TEXT | SI | NO | NO | NO |  |

## ANTIGUEDAD_TRAMOS  →  `motor_antiguedad_tramos`

**MOTOR** · CREAR_MOTOR · LISTO_ESPECIFICACION

Contiene los años y porcentajes correspondientes a cada modelo de antigüedad.

| campo | tipo | null | pk | uq | ai | fk |
|---|---|---|---|---|---|---|
| `id_tramo_antiguedad` | BIGINT UNSIGNED | NO | SI | SI | SI |  |
| `id_modelo_antiguedad` | BIGINT UNSIGNED | NO | NO | NO | NO | MODELOS_ANTIGUEDAD.id_modelo_antiguedad |
| `anios_desde` | DECIMAL(12,4) | NO | NO | NO | NO |  |
| `anios_hasta` | DECIMAL(12,4) | SI | NO | NO | NO |  |
| `porcentaje` | DECIMAL(18,6) | NO | NO | NO | NO |  |
| `orden` | INT | NO | NO | NO | NO |  |

## CONVENIO_MODELO_ANTIGUEDAD  →  `motor_convenio_modelo_antiguedad`

**MOTOR** · CREAR_MOTOR · LISTO_ESPECIFICACION

Asigna a cada convenio el modelo de antigüedad que debe utilizar.

| campo | tipo | null | pk | uq | ai | fk |
|---|---|---|---|---|---|---|
| `id_asignacion_antiguedad` | BIGINT UNSIGNED | NO | SI | SI | SI |  |
| `id_convenio` | BIGINT UNSIGNED | SI | NO | NO | NO | CONVENIOS.id_convenio |
| `id_modelo_antiguedad` | BIGINT UNSIGNED | NO | NO | NO | NO | MODELOS_ANTIGUEDAD.id_modelo_antiguedad |
| `vigencia_desde` | DATE | NO | NO | NO | NO |  |
| `vigencia_hasta` | DATE | SI | NO | NO | NO |  |
| `estado_validacion` | VARCHAR(100) | NO | NO | NO | NO |  |

## MODELOS_VACACIONES  →  `motor_modelos_vacaciones`

**MOTOR** · CREAR_MOTOR · LISTO_ESPECIFICACION

Contiene modelos de escalas vacacionales reutilizables por distintos convenios.

| campo | tipo | null | pk | uq | ai | fk |
|---|---|---|---|---|---|---|
| `id_modelo_vacaciones` | BIGINT UNSIGNED | NO | SI | SI | SI |  |
| `codigo` | VARCHAR(50) | NO | NO | SI | NO |  |
| `descripcion` | VARCHAR(255) | NO | NO | NO | NO |  |
| `version` | INT | NO | NO | NO | NO |  |
| `estado` | VARCHAR(50) | NO | NO | NO | NO |  |
| `fuente` | VARCHAR(100) | NO | NO | NO | NO |  |
| `activo` | BOOLEAN | NO | NO | NO | NO |  |

## VACACIONES_TRAMOS  →  `motor_vacaciones_tramos`

**MOTOR** · CREAR_MOTOR · LISTO_ESPECIFICACION

Contiene los límites de antigüedad y los días correspondientes a cada modelo vacacional.

| campo | tipo | null | pk | uq | ai | fk |
|---|---|---|---|---|---|---|
| `id_tramo_vacaciones` | BIGINT UNSIGNED | NO | SI | SI | SI |  |
| `id_modelo_vacaciones` | BIGINT UNSIGNED | NO | NO | NO | NO | MODELOS_VACACIONES.id_modelo_vacaciones |
| `anios_desde` | DECIMAL(12,4) | NO | NO | NO | NO |  |
| `incluye_desde` | BOOLEAN | NO | NO | NO | NO |  |
| `anios_hasta` | DECIMAL(12,4) | SI | NO | NO | NO |  |
| `incluye_hasta` | BOOLEAN | SI | NO | NO | NO |  |
| `dias` | INT | NO | NO | NO | NO |  |
| `orden` | INT | NO | NO | NO | NO |  |

## CONVENIO_MODELO_VACACIONES  →  `motor_convenio_modelo_vacaciones`

**MOTOR** · CREAR_MOTOR · LISTO_ESPECIFICACION

Asigna a cada convenio el modelo vacacional aplicable.

| campo | tipo | null | pk | uq | ai | fk |
|---|---|---|---|---|---|---|
| `id_asignacion_vacaciones` | BIGINT UNSIGNED | NO | SI | SI | SI |  |
| `id_convenio` | BIGINT UNSIGNED | SI | NO | NO | NO | CONVENIOS.id_convenio |
| `id_modelo_vacaciones` | BIGINT UNSIGNED | NO | NO | NO | NO | MODELOS_VACACIONES.id_modelo_vacaciones |
| `vigencia_desde` | DATE | NO | NO | NO | NO |  |
| `vigencia_hasta` | DATE | SI | NO | NO | NO |  |
| `fuente` | VARCHAR(255) | NO | NO | NO | NO |  |

## PARAMETROS  →  `motor_parametros`

**MOTOR** · CREAR_MOTOR · LISTO_ESPECIFICACION

Un parámetro es un dato de configuración que una fórmula utiliza como entrada.

| campo | tipo | null | pk | uq | ai | fk |
|---|---|---|---|---|---|---|
| `id_parametro` | BIGINT UNSIGNED | NO | SI | SI | SI |  |
| `codigo` | VARCHAR(50) | NO | NO | SI | NO |  |
| `descripcion` | VARCHAR(255) | NO | NO | NO | NO |  |
| `tipo_parametro` | VARCHAR(100) | NO | NO | NO | NO |  |
| `unidad` | VARCHAR(50) | NO | NO | NO | NO |  |
| `activo` | BOOLEAN | NO | NO | NO | NO |  |

## VALORES_PARAMETROS  →  `motor_valores_parametros`

**MOTOR** · CREAR_MOTOR · LISTO_ESPECIFICACION

Contiene los valores de cada parámetro según su alcance y vigencia.

| campo | tipo | null | pk | uq | ai | fk |
|---|---|---|---|---|---|---|
| `id_valor_parametro` | BIGINT UNSIGNED | NO | SI | SI | SI |  |
| `id_parametro` | BIGINT UNSIGNED | NO | NO | NO | NO | PARAMETROS.id_parametro |
| `alcance` | VARCHAR(50) | NO | NO | NO | NO |  |
| `id_convenio` | BIGINT UNSIGNED | SI | NO | NO | NO | CONVENIOS.id_convenio |
| `id_categoria` | BIGINT UNSIGNED | SI | NO | NO | NO | CATEGORIAS.id_categoria |
| `valor` | DECIMAL(18,6) | NO | NO | NO | NO |  |
| `vigencia_desde` | DATE | NO | NO | NO | NO |  |
| `vigencia_hasta` | DATE | SI | NO | NO | NO |  |
| `estado_validacion` | VARCHAR(100) | NO | NO | NO | NO |  |
| `observaciones` | TEXT | SI | NO | NO | NO |  |

## PARAMETROS_ALTAS_PROPUESTAS  →  `motor_parametros_altas_propuestas`

**AUXILIAR** · NO_CREAR · BORRADOR_AUXILIAR

Hoja de trabajo, NO es una tabla del modelo. Contiene las altas que hacen falta en PARAMETROS y en VALORES_PARAMETROS.

| campo | tipo | null | pk | uq | ai | fk |
|---|---|---|---|---|---|---|
| `destino` | VARCHAR(100) | NO | NO | NO | NO |  |
| `codigo` | VARCHAR(50) | NO | NO | SI | NO | PARAMETROS.codigo |
| `alcance` | VARCHAR(50) | SI | NO | NO | NO |  |
| `id_convenio` | BIGINT UNSIGNED | SI | NO | NO | NO | CONVENIOS.id_convenio |
| `id_categoria` | BIGINT UNSIGNED | SI | NO | NO | NO | CATEGORIAS.id_categoria |
| `valor` | DECIMAL(18,6) | SI | NO | NO | NO |  |
| `vigencia_desde` | DATE | SI | NO | NO | NO |  |
| `justificacion` | TEXT | NO | NO | NO | NO |  |

## BASES_ARCA  →  `motor_bases_arca`

**NORMATIVA** · CREAR_MOTOR · LISTO_ESPECIFICACION

Cataloga las diez bases imponibles que el Libro de Sueldos Digital y el F.931 exigen informar por trabajador y período.

| campo | tipo | null | pk | uq | ai | fk |
|---|---|---|---|---|---|---|
| `id_base` | BIGINT UNSIGNED | NO | SI | SI | SI |  |
| `codigo_base` | VARCHAR(50) | SI | NO | SI | NO |  |
| `descripcion` | VARCHAR(255) | NO | NO | NO | NO |  |
| `sujeto_obligado` | VARCHAR(100) | NO | NO | NO | NO |  |
| `admite_tope` | DECIMAL(18,6) | NO | NO | NO | NO |  |
| `activo` | BOOLEAN | NO | NO | NO | NO |  |

## SUBSISTEMAS  →  `motor_subsistemas`

**NORMATIVA** · CREAR_MOTOR · LISTO_ESPECIFICACION

Define cada destino de la recaudación de la seguridad social. Es el catálogo al que apuntan las alícuotas de aportes y contribuciones.

| campo | tipo | null | pk | uq | ai | fk |
|---|---|---|---|---|---|---|
| `id_subsistema` | BIGINT UNSIGNED | NO | SI | SI | SI |  |
| `codigo` | VARCHAR(50) | NO | NO | SI | NO |  |
| `descripcion` | VARCHAR(255) | NO | NO | NO | NO |  |
| `modulo_responsable` | VARCHAR(100) | NO | NO | NO | NO |  |
| `base_aportes` | VARCHAR(100) | SI | NO | NO | NO | BASES_ARCA.codigo_base |
| `base_contribuciones` | VARCHAR(100) | NO | NO | NO | NO | BASES_ARCA.codigo_base |
| `activo` | BOOLEAN | NO | NO | NO | NO |  |

## SUBSISTEMA_APLICACION  →  `motor_subsistema_aplicacion`

**NORMATIVA** · CREAR_MOTOR · LISTO_ESPECIFICACION

SUBSISTEMA_APLICACION es un catálogo normativo de Zonda. Cada fila representa una combinación oficial válida entre un subsistema y un tipo de aplicación (APORTE o CONTRIBUCION). La existencia de la fila expresa habilitación. La columna codigo_base indica qué REM_n utiliza cuando existe una base imponible ordinaria. Puede ser NULL para aplicaciones oficiales que no posean una REM_n propia. columna_lsd identifica la posición oficial utilizada en el archivo de conceptos del LSD.

| campo | tipo | null | pk | uq | ai | fk |
|---|---|---|---|---|---|---|
| `id_subsistema_aplicacion` | BIGINT UNSIGNED | NO | SI | SI | SI |  |
| `id_subsistema` | BIGINT UNSIGNED | NO | NO | NO | NO | SUBSISTEMAS.id_subsistema |
| `tipo_aplicacion` | VARCHAR(100) | NO | NO | NO | NO |  |
| `codigo_base` | VARCHAR(50) | SI | NO | SI | NO | BASES_ARCA.codigo_base |
| `columna_lsd` | INT | NO | NO | NO | NO |  |
| `activo` | BOOLEAN | NO | NO | NO | NO |  |
| `estado_validacion` | VARCHAR(100) | NO | NO | NO | NO |  |
| `observaciones` | TEXT | SI | NO | NO | NO |  |

## CONCEPTO_INCIDENCIA_ARCA  →  `motor_concepto_incidencia_arca`

**NORMATIVA** · CREAR_MOTOR · LISTO_ESPECIFICACION

CONCEPTO_INCIDENCIA_ARCA es la fuente primaria de parametrización normativa para determinar la incidencia de cada concepto en cada aplicación ARCA válida.

| campo | tipo | null | pk | uq | ai | fk |
|---|---|---|---|---|---|---|
| `id_incidencia_arca` | BIGINT UNSIGNED | NO | SI | SI | SI |  |
| `codigo_concepto` | VARCHAR(50) | NO | NO | NO | NO | TAB_CONCEPTOS.codigo_concepto |
| `id_subsistema_aplicacion` | BIGINT UNSIGNED | NO | NO | NO | NO | SUBSISTEMA_APLICACION.id_subsistema_aplicacion |
| `incidencia` | VARCHAR(100) | NO | NO | NO | NO |  |
| `vigencia_desde` | DATE | NO | NO | NO | NO |  |
| `vigencia_hasta` | DATE | SI | NO | NO | NO |  |
| `estado_validacion` | VARCHAR(100) | NO | NO | NO | NO |  |
| `fuente_normativa` | TEXT | SI | NO | NO | NO |  |
| `observaciones` | TEXT | SI | NO | NO | NO |  |

## ENCUADRAMIENTOS_PATRONALES  →  `motor_encuadramientos_patronales`

**NORMATIVA** · CREAR_MOTOR · LISTO_ESPECIFICACION

Define el dominio cerrado de encuadramientos patronales previstos por la Ley 27.541: A y B.

| campo | tipo | null | pk | uq | ai | fk |
|---|---|---|---|---|---|---|
| `encuadramiento` | VARCHAR(50) | NO | SI | SI | NO |  |
| `descripcion` | VARCHAR(255) | NO | NO | NO | NO |  |
| `fuente_normativa` | TEXT | SI | NO | NO | NO |  |
| `activo` | BOOLEAN | NO | NO | NO | NO |  |

## ALICUOTAS_CONTRIBUCIONES  →  `motor_alicuotas_contribuciones`

**NORMATIVA** · CREAR_MOTOR · LISTO_ESPECIFICACION

Contiene las alícuotas de contribuciones patronales de la seguridad social, desglosadas por subsistema y con vigencia propia.

| campo | tipo | null | pk | uq | ai | fk |
|---|---|---|---|---|---|---|
| `id_alicuota` | BIGINT UNSIGNED | NO | SI | SI | SI |  |
| `encuadramiento` | VARCHAR(50) | NO | NO | NO | NO | ENCUADRAMIENTOS_PATRONALES.encuadramiento |
| `id_subsistema` | BIGINT UNSIGNED | NO | NO | NO | NO | SUBSISTEMAS.id_subsistema |
| `porcentaje` | DECIMAL(18,6) | NO | NO | NO | NO |  |
| `base_aplicable` | VARCHAR(100) | NO | NO | NO | NO | BASES_ARCA.codigo_base |
| `vigencia_desde` | DATE | NO | NO | NO | NO |  |
| `vigencia_hasta` | DATE | SI | NO | NO | NO |  |
| `estado_validacion` | VARCHAR(100) | NO | NO | NO | NO |  |
| `fuente_normativa` | TEXT | SI | NO | NO | NO |  |

## ALICUOTAS_APORTES  →  `motor_alicuotas_aportes`

**NORMATIVA** · CREAR_MOTOR · LISTO_ESPECIFICACION

Contiene las alícuotas de aportes personales del trabajador, con su subsistema, su base imponible y su vigencia.

| campo | tipo | null | pk | uq | ai | fk |
|---|---|---|---|---|---|---|
| `id_alicuota_aporte` | BIGINT UNSIGNED | NO | SI | SI | SI |  |
| `id_subsistema` | BIGINT UNSIGNED | NO | NO | NO | NO | SUBSISTEMAS.id_subsistema |
| `condicion_arca` | VARCHAR(100) | NO | NO | NO | NO | CONDICIONES_ARCA.codigo_condicion |
| `porcentaje` | DECIMAL(18,6) | NO | NO | NO | NO |  |
| `base_aplicable` | VARCHAR(100) | NO | NO | NO | NO | BASES_ARCA.codigo_base |
| `aplica_tope` | DECIMAL(18,6) | NO | NO | NO | NO |  |
| `vigencia_desde` | DATE | NO | NO | NO | NO |  |
| `vigencia_hasta` | DATE | SI | NO | NO | NO |  |
| `estado_validacion` | VARCHAR(100) | NO | NO | NO | NO |  |
| `fuente_normativa` | TEXT | SI | NO | NO | NO |  |

## TOPES_MINIMOS  →  `motor_topes_minimos`

**NORMATIVA** · CREAR_MOTOR · LISTO_ESPECIFICACION

Contiene la base mínima y la base máxima previsional de cada período, por base imponible.

| campo | tipo | null | pk | uq | ai | fk |
|---|---|---|---|---|---|---|
| `id_tope` | BIGINT UNSIGNED | NO | SI | SI | SI |  |
| `periodo` | CHAR(7) | NO | NO | NO | NO |  |
| `codigo_base` | VARCHAR(50) | SI | NO | SI | NO | BASES_ARCA.codigo_base |
| `tipo_tope` | DECIMAL(18,6) | NO | NO | NO | NO |  |
| `componente` | VARCHAR(100) | NO | NO | NO | NO | COMPONENTES_TOPE.codigo_componente |
| `importe` | DECIMAL(18,6) | SI | NO | NO | NO |  |
| `estado_validacion` | VARCHAR(100) | NO | NO | NO | NO |  |
| `fuente_normativa` | TEXT | SI | NO | NO | NO |  |

## COMPONENTES_TOPE  →  `motor_componentes_tope`

**NORMATIVA** · CREAR_MOTOR · LISTO_ESPECIFICACION

Catálogo cerrado de los componentes sobre los que se consume el cupo de un tope dentro de un mismo período y base.

| campo | tipo | null | pk | uq | ai | fk |
|---|---|---|---|---|---|---|
| `codigo_componente` | VARCHAR(50) | NO | SI | SI | NO |  |
| `descripcion` | VARCHAR(100) | NO | NO | NO | NO |  |
| `activo` | BOOLEAN | NO | NO | NO | NO |  |

## REGIMENES_CONTRIBUTIVOS  →  `motor_regimenes_contributivos`

**NORMATIVA** · CREAR_MOTOR · LISTO_ESPECIFICACION

Catálogo de todas las desviaciones posibles respecto del régimen general: adicionales sectoriales, beneficios promocionales y sustituciones de destino.

| campo | tipo | null | pk | uq | ai | fk |
|---|---|---|---|---|---|---|
| `id_regimen` | BIGINT UNSIGNED | NO | SI | SI | SI |  |
| `codigo` | VARCHAR(50) | NO | NO | SI | NO |  |
| `descripcion` | VARCHAR(255) | NO | NO | NO | NO |  |
| `tipo_regla` | TEXT | NO | NO | NO | NO |  |
| `sujeto_obligado` | VARCHAR(100) | NO | NO | NO | NO |  |
| `porcentaje` | DECIMAL(18,6) | SI | NO | NO | NO |  |
| `destino` | VARCHAR(100) | NO | NO | NO | NO |  |
| `base_aplicable` | VARCHAR(100) | NO | NO | NO | NO | BASES_ARCA.codigo_base |
| `duracion_meses` | INT | SI | NO | NO | NO |  |
| `orden_aplicacion` | INT | NO | NO | NO | NO |  |
| `vigencia_desde` | DATE | NO | NO | NO | NO |  |
| `vigencia_hasta` | DATE | SI | NO | NO | NO |  |
| `estado_validacion` | VARCHAR(100) | NO | NO | NO | NO |  |
| `fuente_normativa` | TEXT | SI | NO | NO | NO |  |

## CONDICIONES_ARCA  →  `motor_condiciones_arca`

**NORMATIVA** · CREAR_MOTOR · LISTO_ESPECIFICACION

Catálogo de la condición previsional del trabajador según ARCA. Es un selector de reglas de cálculo, no un dato descriptivo.

| campo | tipo | null | pk | uq | ai | fk |
|---|---|---|---|---|---|---|
| `codigo_condicion` | VARCHAR(50) | NO | SI | SI | NO |  |
| `descripcion` | VARCHAR(255) | NO | NO | NO | NO |  |
| `consecuencia_general` | VARCHAR(255) | NO | NO | NO | NO |  |
| `activo` | BOOLEAN | NO | NO | NO | NO |  |

## MODALIDADES_ARCA  →  `motor_modalidades_arca`

**NORMATIVA** · CREAR_MOTOR · LISTO_ESPECIFICACION

MODALIDADES_ARCA representa el nomenclador de modalidades utilizado para el encuadramiento/declaración ante ARCA. La existencia de una modalidad determinada no implica por sí sola la aplicación automática de un régimen contributivo. Los regímenes efectivamente aplicables se registran en RELACION_LABORAL_REGIMEN.

| campo | tipo | null | pk | uq | ai | fk |
|---|---|---|---|---|---|---|
| `codigo_modalidad` | VARCHAR(50) | NO | SI | SI | NO |  |
| `descripcion` | VARCHAR(100) | NO | NO | NO | NO |  |
| `activo` | BOOLEAN | NO | NO | NO | NO |  |

## SITUACIONES_REVISTA_ARCA  →  `motor_situaciones_revista_arca`

**NORMATIVA** · CREAR_MOTOR · LISTO_ESPECIFICACION

Catálogo de las situaciones de revista que ARCA admite informar dentro de un período.

| campo | tipo | null | pk | uq | ai | fk |
|---|---|---|---|---|---|---|
| `codigo_situacion` | VARCHAR(50) | NO | SI | SI | NO |  |
| `descripcion` | VARCHAR(100) | NO | NO | NO | NO |  |
| `activo` | BOOLEAN | NO | NO | NO | NO |  |

## ACTIVIDADES_ARCA  →  `motor_actividades_arca`

**NORMATIVA** · CREAR_MOTOR · LISTO_ESPECIFICACION

Catálogo de los códigos de actividad de ARCA que puede tomar el encuadre SICOSS de una relación laboral.

| campo | tipo | null | pk | uq | ai | fk |
|---|---|---|---|---|---|---|
| `codigo_actividad_arca` | VARCHAR(50) | NO | SI | SI | NO |  |
| `descripcion` | VARCHAR(100) | NO | NO | NO | NO |  |
| `vigencia_desde` | DATE | NO | NO | NO | NO |  |
| `vigencia_hasta` | DATE | SI | NO | NO | NO |  |
| `estado` | VARCHAR(50) | NO | NO | NO | NO |  |

## LOCALIDADES_ARCA  →  `motor_localidades_arca`

**NORMATIVA** · CREAR_MOTOR · LISTO_ESPECIFICACION

Catálogo de los códigos de localidad de ARCA que puede tomar el encuadre SICOSS de una relación laboral.

| campo | tipo | null | pk | uq | ai | fk |
|---|---|---|---|---|---|---|
| `codigo_localidad_arca` | VARCHAR(50) | NO | SI | SI | NO |  |
| `descripcion` | VARCHAR(100) | NO | NO | NO | NO |  |
| `provincia` | VARCHAR(100) | NO | NO | NO | NO |  |
| `vigencia_desde` | DATE | NO | NO | NO | NO |  |
| `vigencia_hasta` | DATE | SI | NO | NO | NO |  |
| `estado` | VARCHAR(50) | NO | NO | NO | NO |  |

## EMPRESAS  →  `motor_empresas`

**LEGACY_REFERENCIA** · REUTILIZAR_MEDIANTE_ADAPTADOR · REQUIERE_MAPEO_GESTION

Contiene únicamente los datos permanentes del empleador. No guarda el encuadramiento A/B ni ningún porcentaje.

| campo | tipo | null | pk | uq | ai | fk |
|---|---|---|---|---|---|---|
| `empresa_id` | BIGINT UNSIGNED | NO | SI | SI | SI |  |
| `cuit` | VARCHAR(20) | NO | NO | NO | NO |  |
| `razon_social` | VARCHAR(100) | NO | NO | NO | NO |  |
| `actividad_principal_clae` | VARCHAR(100) | NO | NO | NO | NO |  |
| `sector_actividad` | VARCHAR(100) | NO | NO | NO | NO |  |
| `fecha_inicio_actividad` | DATE | NO | NO | NO | NO |  |
| `estado` | VARCHAR(50) | NO | NO | NO | NO |  |

## EMPRESA_ENCUADRAMIENTO  →  `motor_empresa_encuadramiento`

**REVISAR** · PENDIENTE_RELEVAMIENTO · PENDIENTE

Registra qué encuadramiento patronal tuvo la empresa y desde cuándo. Es una línea de tiempo, no un campo.

| campo | tipo | null | pk | uq | ai | fk |
|---|---|---|---|---|---|---|
| `encuadramiento_id` | BIGINT UNSIGNED | NO | SI | SI | SI |  |
| `empresa_id` | BIGINT UNSIGNED | NO | NO | NO | NO | EMPRESAS.empresa_id |
| `encuadramiento` | VARCHAR(50) | NO | NO | NO | NO | ENCUADRAMIENTOS_PATRONALES.encuadramiento |
| `detalle` | VARCHAR(100) | NO | NO | NO | NO |  |
| `vigencia_desde` | DATE | NO | NO | NO | NO |  |
| `vigencia_hasta` | DATE | SI | NO | NO | NO |  |
| `certificado_mipyme` | VARCHAR(100) | NO | NO | NO | NO |  |
| `vencimiento_certificado` | DATE | SI | NO | NO | NO |  |
| `origen` | VARCHAR(100) | NO | NO | NO | NO |  |
| `documento_respaldo` | VARCHAR(100) | NO | NO | NO | NO |  |

## RELACION_LABORAL  →  `motor_relacion_laboral`

**CANONICO** · CREAR_MOTOR · LISTO_ESPECIFICACION

RELACION_LABORAL representa una contratación concreta del trabajador. Los datos personales o descriptivos del empleado no se duplican en esta tabla y se obtienen desde LEGAJOS mediante id_legajo.

| campo | tipo | null | pk | uq | ai | fk |
|---|---|---|---|---|---|---|
| `relacion_laboral_id` | BIGINT UNSIGNED | NO | SI | SI | SI |  |
| `id_legajo` | BIGINT UNSIGNED | NO | NO | NO | NO | LEGAJOS.id_legajo |
| `empresa_id` | BIGINT UNSIGNED | NO | NO | NO | NO | EMPRESAS.empresa_id |
| `legajo` | VARCHAR(50) | NO | NO | NO | NO |  |
| `fecha_ingreso` | DATE | NO | NO | NO | NO |  |
| `fecha_egreso` | DATE | SI | NO | NO | NO |  |
| `estado` | VARCHAR(50) | NO | NO | NO | NO |  |

## RELACION_LABORAL_CATEGORIA  →  `motor_relacion_laboral_categoria`

**CANONICO** · CREAR_MOTOR · LISTO_ESPECIFICACION

Guarda la categoría de cada relación laboral con su vigencia, porque la categoría puede cambiar durante la contratación.

| campo | tipo | null | pk | uq | ai | fk |
|---|---|---|---|---|---|---|
| `relacion_categoria_id` | BIGINT UNSIGNED | NO | SI | SI | SI |  |
| `relacion_laboral_id` | BIGINT UNSIGNED | NO | NO | NO | NO | RELACION_LABORAL.relacion_laboral_id |
| `id_categoria` | BIGINT UNSIGNED | SI | NO | NO | NO | CATEGORIAS.id_categoria |
| `vigencia_desde` | DATE | NO | NO | NO | NO |  |
| `vigencia_hasta` | DATE | SI | NO | NO | NO |  |

## RELACION_LABORAL_ARCA  →  `motor_relacion_laboral_arca`

**CANONICO** · CREAR_MOTOR · LISTO_ESPECIFICACION

Guarda el encuadre SICOSS de cada relación laboral: condición, actividad, modalidad de contratación y localidad, con vigencia propia.

| campo | tipo | null | pk | uq | ai | fk |
|---|---|---|---|---|---|---|
| `encuadre_sicoss_id` | BIGINT UNSIGNED | NO | SI | SI | SI |  |
| `relacion_laboral_id` | BIGINT UNSIGNED | NO | NO | NO | NO | RELACION_LABORAL.relacion_laboral_id |
| `codigo_condicion` | VARCHAR(50) | NO | NO | SI | NO | CONDICIONES_ARCA.codigo_condicion |
| `actividad_interna` | VARCHAR(100) | NO | NO | NO | NO |  |
| `codigo_actividad_arca` | VARCHAR(50) | NO | NO | SI | NO | ACTIVIDADES_ARCA.codigo_actividad_arca |
| `codigo_modalidad` | VARCHAR(50) | NO | NO | SI | NO | MODALIDADES_ARCA.codigo_modalidad |
| `codigo_localidad_arca` | VARCHAR(50) | NO | NO | SI | NO | LOCALIDADES_ARCA.codigo_localidad_arca |
| `vigencia_desde` | DATE | NO | NO | NO | NO |  |
| `vigencia_hasta` | DATE | SI | NO | NO | NO |  |
| `estado_validacion` | VARCHAR(100) | NO | NO | NO | NO |  |

## RELACION_LABORAL_REGIMEN  →  `motor_relacion_laboral_regimen`

**CANONICO** · CREAR_MOTOR · LISTO_ESPECIFICACION

Asigna a cada relación laboral los regímenes contributivos que le corresponden, con vigencia propia para cada uno.

| campo | tipo | null | pk | uq | ai | fk |
|---|---|---|---|---|---|---|
| `relacion_regimen_id` | BIGINT UNSIGNED | NO | SI | SI | SI |  |
| `relacion_laboral_id` | BIGINT UNSIGNED | NO | NO | NO | NO | RELACION_LABORAL.relacion_laboral_id |
| `id_regimen` | BIGINT UNSIGNED | NO | NO | NO | NO | REGIMENES_CONTRIBUTIVOS.id_regimen |
| `vigencia_desde` | DATE | NO | NO | NO | NO |  |
| `vigencia_hasta` | DATE | SI | NO | NO | NO |  |
| `estado_validacion` | VARCHAR(100) | NO | NO | NO | NO |  |
| `documento_respaldo` | VARCHAR(100) | SI | NO | NO | NO |  |

## SITUACION_REVISTA_PERIODO  →  `motor_situacion_revista_periodo`

**CANONICO** · CREAR_MOTOR · LISTO_ESPECIFICACION

Registra qué le pasó a cada trabajador dentro del período. Es la única tabla mensual del bloque contributivo.

| campo | tipo | null | pk | uq | ai | fk |
|---|---|---|---|---|---|---|
| `situacion_periodo_id` | BIGINT UNSIGNED | NO | SI | SI | SI |  |
| `periodo` | CHAR(7) | NO | NO | NO | NO |  |
| `relacion_laboral_id` | BIGINT UNSIGNED | NO | NO | NO | NO | RELACION_LABORAL.relacion_laboral_id |
| `orden` | INT | NO | NO | NO | NO |  |
| `codigo_situacion` | VARCHAR(50) | NO | NO | SI | NO | SITUACIONES_REVISTA_ARCA.codigo_situacion |
| `dia_desde` | INT | NO | NO | NO | NO |  |
| `dia_hasta` | INT | NO | NO | NO | NO |  |
| `origen_novedad` | VARCHAR(100) | NO | NO | NO | NO |  |

## IF_RELACION_LABORAL_ORIGEN  →  `motor_if_relacion_laboral_origen`

**INTERFAZ** · CREAR_INTERFAZ · LISTO_ESPECIFICACION

Tabla técnica de desacople y trazabilidad entre Zonda Gestión y el modelo canónico del Motor.

| campo | tipo | null | pk | uq | ai | fk |
|---|---|---|---|---|---|---|
| `id_interfaz_relacion` | BIGINT UNSIGNED | NO | SI | SI | SI |  |
| `relacion_laboral_id` | BIGINT UNSIGNED | NO | NO | NO | NO | RELACION_LABORAL.relacion_laboral_id |
| `sistema_origen` | VARCHAR(50) | NO | NO | NO | NO |  |
| `tabla_origen` | VARCHAR(100) | NO | NO | NO | NO |  |
| `clave_origen` | VARCHAR(191) | NO | NO | NO | NO |  |
| `codigo_legajo_origen` | VARCHAR(100) | SI | NO | NO | NO |  |
| `activo` | BOOLEAN | NO | NO | NO | NO |  |
| `created_at` | DATETIME(6) | NO | NO | NO | NO |  |
| `updated_at` | DATETIME(6) | NO | NO | NO | NO |  |

## IF_NOVEDAD_ORIGEN  →  `motor_if_novedad_origen`

**INTERFAZ** · CREAR_INTERFAZ · LISTO_ESPECIFICACION

Tabla técnica de desacople y trazabilidad entre Zonda Gestión y el modelo canónico del Motor.

| campo | tipo | null | pk | uq | ai | fk |
|---|---|---|---|---|---|---|
| `id_interfaz_novedad` | BIGINT UNSIGNED | NO | SI | SI | SI |  |
| `id_novedad` | BIGINT UNSIGNED | NO | NO | NO | NO | NOVEDADES.id_novedad |
| `sistema_origen` | VARCHAR(50) | NO | NO | NO | NO |  |
| `tabla_origen` | VARCHAR(100) | NO | NO | NO | NO |  |
| `clave_origen` | VARCHAR(191) | NO | NO | NO | NO |  |
| `fecha_importacion` | DATETIME(6) | NO | NO | NO | NO |  |
| `hash_origen` | CHAR(64) | SI | NO | NO | NO |  |

## RELACIONES_TECNICAS

| origen | fk | destino | pk/uk | card. | estado | on_delete | on_update |
|---|---|---|---|---|---|---|---|
| CONVENIOS | id_jornada | JORNADAS | id_jornada | N:1 | VALIDADA | RESTRICT | RESTRICT |
| CATEGORIAS | id_convenio | CONVENIOS | id_convenio | N:1 | VALIDADA | RESTRICT | RESTRICT |
| BASICOS | id_categoria | CATEGORIAS | id_categoria | N:1 | VALIDADA | RESTRICT | RESTRICT |
| BASE_ZONDA_INCIDENCIA | id_base_zonda | BASES_ZONDA | id_base_zonda | N:1 | VALIDADA | RESTRICT | RESTRICT |
| BASE_ZONDA_INCIDENCIA | codigo_concepto | TAB_CONCEPTOS | codigo_concepto | N:1 | VALIDADA | RESTRICT | RESTRICT |
| NOVEDADES | codigo_novedad | TIPOS_NOVEDAD | codigo | N:1 | VALIDADA | RESTRICT | RESTRICT |
| ANTIGUEDAD_TRAMOS | id_modelo_antiguedad | MODELOS_ANTIGUEDAD | id_modelo_antiguedad | N:1 | VALIDADA | RESTRICT | RESTRICT |
| CONVENIO_MODELO_ANTIGUEDAD | id_convenio | CONVENIOS | id_convenio | N:1 | VALIDADA | RESTRICT | RESTRICT |
| CONVENIO_MODELO_ANTIGUEDAD | id_modelo_antiguedad | MODELOS_ANTIGUEDAD | id_modelo_antiguedad | N:1 | VALIDADA | RESTRICT | RESTRICT |
| VACACIONES_TRAMOS | id_modelo_vacaciones | MODELOS_VACACIONES | id_modelo_vacaciones | N:1 | VALIDADA | RESTRICT | RESTRICT |
| CONVENIO_MODELO_VACACIONES | id_convenio | CONVENIOS | id_convenio | N:1 | VALIDADA | RESTRICT | RESTRICT |
| CONVENIO_MODELO_VACACIONES | id_modelo_vacaciones | MODELOS_VACACIONES | id_modelo_vacaciones | N:1 | VALIDADA | RESTRICT | RESTRICT |
| VALORES_PARAMETROS | id_parametro | PARAMETROS | id_parametro | N:1 | VALIDADA | RESTRICT | RESTRICT |
| VALORES_PARAMETROS | id_convenio | CONVENIOS | id_convenio | N:1 | VALIDADA | RESTRICT | RESTRICT |
| VALORES_PARAMETROS | id_categoria | CATEGORIAS | id_categoria | N:1 | VALIDADA | RESTRICT | RESTRICT |
| SUBSISTEMAS | base_aportes | BASES_ARCA | codigo_base | N:1 | VALIDADA | RESTRICT | RESTRICT |
| SUBSISTEMAS | base_contribuciones | BASES_ARCA | codigo_base | N:1 | VALIDADA | RESTRICT | RESTRICT |
| ALICUOTAS_CONTRIBUCIONES | encuadramiento | ENCUADRAMIENTOS_PATRONALES | encuadramiento | N:1 | VALIDADA | RESTRICT | RESTRICT |
| ALICUOTAS_CONTRIBUCIONES | id_subsistema | SUBSISTEMAS | id_subsistema | N:1 | VALIDADA | RESTRICT | RESTRICT |
| SUBSISTEMA_APLICACION | id_subsistema | SUBSISTEMAS | id_subsistema | N:1 | VALIDADA | RESTRICT | RESTRICT |
| ALICUOTAS_CONTRIBUCIONES | base_aplicable | BASES_ARCA | codigo_base | N:1 | VALIDADA | RESTRICT | RESTRICT |
| ALICUOTAS_APORTES | id_subsistema | SUBSISTEMAS | id_subsistema | N:1 | VALIDADA | RESTRICT | RESTRICT |
| SUBSISTEMA_APLICACION | codigo_base | BASES_ARCA | codigo_base | N:1 | VALIDADA | RESTRICT | RESTRICT |
| ALICUOTAS_APORTES | condicion_arca | CONDICIONES_ARCA | codigo_condicion | N:1 | VÁLIDA C/COMODÍN | RESTRICT | RESTRICT |
| ALICUOTAS_APORTES | base_aplicable | BASES_ARCA | codigo_base | N:1 | VALIDADA | RESTRICT | RESTRICT |
| TOPES_MINIMOS | codigo_base | BASES_ARCA | codigo_base | N:1 | VALIDADA | RESTRICT | RESTRICT |
| REGIMENES_CONTRIBUTIVOS | base_aplicable | BASES_ARCA | codigo_base | N:1 | VALIDADA | RESTRICT | RESTRICT |
| CONCEPTO_INCIDENCIA_ARCA | codigo_concepto | TAB_CONCEPTOS | codigo_concepto | N:1 | VALIDADA | RESTRICT | RESTRICT |
| EMPRESA_ENCUADRAMIENTO | empresa_id | EMPRESAS | empresa_id | N:1 | VALIDADA | RESTRICT | RESTRICT |
| EMPRESA_ENCUADRAMIENTO | encuadramiento | ENCUADRAMIENTOS_PATRONALES | encuadramiento | N:1 | VALIDADA | RESTRICT | RESTRICT |
| RELACION_LABORAL | empresa_id | EMPRESAS | empresa_id | N:1 | VALIDADA | RESTRICT | RESTRICT |
| CONCEPTO_INCIDENCIA_ARCA | id_subsistema_aplicacion | SUBSISTEMA_APLICACION | id_subsistema_aplicacion | N:1 | VALIDADA | RESTRICT | RESTRICT |
| RELACION_LABORAL_ARCA | relacion_laboral_id | RELACION_LABORAL | relacion_laboral_id | 1:1 | VALIDADA | RESTRICT | RESTRICT |
| RELACION_LABORAL_ARCA | codigo_condicion | CONDICIONES_ARCA | codigo_condicion | N:1 | VALIDADA | RESTRICT | RESTRICT |
| RELACION_LABORAL_ARCA | codigo_modalidad | MODALIDADES_ARCA | codigo_modalidad | N:1 | VALIDADA | RESTRICT | RESTRICT |
| RELACION_LABORAL_REGIMEN | relacion_laboral_id | RELACION_LABORAL | relacion_laboral_id | N:1 | VALIDADA | RESTRICT | RESTRICT |
| RELACION_LABORAL_REGIMEN | id_regimen | REGIMENES_CONTRIBUTIVOS | id_regimen | N:1 | VALIDADA | RESTRICT | RESTRICT |
| SITUACION_REVISTA_PERIODO | relacion_laboral_id | RELACION_LABORAL | relacion_laboral_id | N:1 | VALIDADA | RESTRICT | RESTRICT |
| SITUACION_REVISTA_PERIODO | codigo_situacion | SITUACIONES_REVISTA_ARCA | codigo_situacion | N:1 | VALIDADA | RESTRICT | RESTRICT |
| TAB_CONCEPTOS | codigo_parametro | PARAMETROS | codigo | N:1 | PENDIENTE ALTA | RESTRICT | RESTRICT |
| TAB_CONCEPTOS | codigo_novedad_cantidad | TIPOS_NOVEDAD | codigo | N:1 | VALIDADA | RESTRICT | RESTRICT |
| CATEGORIA_CONCEPTO | id_categoria | CATEGORIAS | id_categoria | N:1 | VALIDADA | RESTRICT | RESTRICT |
| CATEGORIA_CONCEPTO | codigo_concepto | TAB_CONCEPTOS | codigo_concepto | N:1 | VALIDADA | RESTRICT | RESTRICT |
| PARAMETROS_ALTAS_PROPUESTAS | codigo | PARAMETROS | codigo | N:1 | PENDIENTE ALTA | RESTRICT | RESTRICT |
| PARAMETROS_ALTAS_PROPUESTAS | id_convenio | CONVENIOS | id_convenio | N:1 | VALIDADA | RESTRICT | RESTRICT |
| PARAMETROS_ALTAS_PROPUESTAS | id_categoria | CATEGORIAS | id_categoria | N:1 | VALIDADA | RESTRICT | RESTRICT |
| RELACION_LABORAL | id_legajo | LEGAJOS | id_legajo | N:1 | VALIDADA | RESTRICT | RESTRICT |
| RELACION_LABORAL_CATEGORIA | relacion_laboral_id | RELACION_LABORAL | relacion_laboral_id | N:1 | VALIDADA | RESTRICT | RESTRICT |
| RELACION_LABORAL_CATEGORIA | id_categoria | CATEGORIAS | id_categoria | N:1 | VALIDADA | RESTRICT | RESTRICT |
| NOVEDADES | relacion_laboral_id | RELACION_LABORAL | relacion_laboral_id | N:1 | VALIDADA | RESTRICT | RESTRICT |
| RELACION_LABORAL_ARCA | codigo_actividad_arca | ACTIVIDADES_ARCA | codigo_actividad_arca | N:1 | PENDIENTE | RESTRICT | RESTRICT |
| RELACION_LABORAL_ARCA | codigo_localidad_arca | LOCALIDADES_ARCA | codigo_localidad_arca | N:1 | PENDIENTE | RESTRICT | RESTRICT |
| TAB_CONCEPTOS | base_modulo | BASES_ARCA | codigo_base | N:1 | VALIDADA | RESTRICT | RESTRICT |
| TOPES_MINIMOS | componente | COMPONENTES_TOPE | codigo_componente | N:1 | VALIDADA | RESTRICT | RESTRICT |
| IF_RELACION_LABORAL_ORIGEN | relacion_laboral_id | RELACION_LABORAL | relacion_laboral_id | N:1 | NUEVA_INTERFAZ | RESTRICT | RESTRICT |
| IF_NOVEDAD_ORIGEN | id_novedad | NOVEDADES | id_novedad | 1:1/N:1 | NUEVA_INTERFAZ | RESTRICT | RESTRICT |
