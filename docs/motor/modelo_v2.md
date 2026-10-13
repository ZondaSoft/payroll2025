# Alineación con el modelo DBML v2 — Excel funcional v5

Fuente: `Zonda_Motor_Calculo_2026-08-30_FORMULAS_V3_v5.xlsx` · SHA-256 `f80efb5b2437aefda450f97e67f9d328fae29c3e35226ec46ddfc086d62cfe22` · 73 hojas · recibido el 2026-10-13.

Sustituye como fuente de datos al Excel del 30/08 que generó el primer seed (ver [`diccionario.md`](diccionario.md), que documenta el modelo anterior). La hoja `AUDIT_ARCA_20260925` acredita el cierre de la **Etapa 3A** con gate **`LISTO_DESARROLLO`**, conciliada contra el F.931 real de BLEXA 08/2026: 41 empleados, 0 diferencias funcionales.

Las hojas `B2_TRAZABILIDAD` y `B3_TRAZABILIDAD` del propio libro documentan, fila por fila, cada transformación de v3 → v4 → v5; esta nota registra solamente cómo se aplicaron acá.

## 1. Lo que desbloquea

| | Excel 30/08 (seed anterior) | Excel v5 |
|---|---|---|
| Incidencias ARCA | 481 — 64 S / 201 N / **216 PENDIENTE** | 507 — 249 S / 258 N / **0 PENDIENTE**, todas CONFIRMADO |
| Alícuotas de aportes | SIN_CONFIRMAR | CONFIRMADO (Ley 24.241 · Ley 19.032) |
| Alícuotas de contribuciones | SIN_CONFIRMAR | 8/8 CONFIRMADO |
| Topes | 7, ninguno confirmado | 9 con fuente ARCA, 8 confirmados, 6 prorrateables por días |
| Conceptos | 37 | 40 (+VACGOZ, +INAVAC, +AD564) |
| Bases de Zonda | 4 · 148 filas de composición | 5 (+BASE_ACUERDO_5_6_2) · 185 filas |

El gate `estado_validacion` dejó de bloquear por dato faltante: en `PRODUCCION` ya hay normativa habilitante.

## 2. Reglas nuevas

- **RIFL distribuido** (`motor_regimen_distribucion`): el 5 % sustitutivo se reparte 1,31 % SIPA + 3 % INSSJP + 0,57 % AAFF + 0,12 % FNE (RG 5844/2026). Antes era un 5 % sin desglose, que no se podía imputar por subsistema.
- **`SUSTITUCION_SUBSISTEMA`**: tipo de regla nuevo. RENATRE 1,5 % reemplaza el subsistema FNE completo (`reemplaza_subsistema`), no su alícuota.
- **Topes prorrateables** (`proporcional_dias`): el máximo se prorratea por días liquidados cuando la relación no cubre el mes.
- **Mínimo sobre REM_10**: el piso previsional se mide en REM_10, no en REM_1/REM_4/REM_5 como en el libro anterior.
- **`motor_reglas_base_diferencial_arca`**: el diferencial de obra social por jornada parcial —`MAX(0, BASE_OS_JORNADA_COMPLETA − BASE_CONCEPTOS_REM4)` sobre REM_4 y REM_8 (art. 92 ter LCT + ARCA G18)— vive fuera de la matriz S/N de conceptos. Es la misma regla que el módulo LSD ya aplica.
- **Aportes diferenciales** sobre REM_6 con `sujeto_obligado = TRABAJADOR` (docentes, investigadores).

## 3. Movimientos de modelo

Lo que el v2 cambió de lugar, y cómo quedó acá:

| Cambio del v2 | Aplicación |
|---|---|
| `TAB_CONCEPTOS` pierde `codigo_novedad_cantidad`, `requiere_novedad` e `id_formula_base` | las novedades pasan a `motor_concepto_version_novedad` (REQUERIDA/OPCIONAL) y las fórmulas a las columnas `expr_*` de `motor_concepto_versiones`. Las columnas viejas **siguen en la tabla**: las usa el orquestador actual y se retiran con el port del DSL |
| `VALORES_PARAMETROS` pierde `id_categoria` | no existe alcance CATEGORIA en parámetros: ese valor es `VALCAT`. La columna y el nivel siguen presentes hasta el port del DSL; no hay filas que los usen |
| `CATEGORIA_CONCEPTO` pierde `accion` y vigencias | pasa a ser sólo la definición del valor categórico (tipo y unidad); los valores van a `motor_categoria_concepto_vigencia`. La aplicabilidad por categoría se mudó a `motor_concepto_aplicabilidad.id_categoria` |
| `NOVEDADES`: `cantidad` → un `valor` tipado único + `estado` | pendiente: la tabla conserva `cantidad` y `valor` separados |
| Sin regla de perfil ⇒ bloquea (`PERFIL_SIN_REGLA`) | pendiente: hoy el concepto se omite en silencio |

## 4. Tablas nuevas (20)

- **Fórmulas**: `motor_variables`, `motor_valores_variable`, `motor_concepto_version_novedad`, `motor_concepto_version_literal`, `motor_perfil`, `motor_perfil_concepto`, `motor_modulos`, `motor_modulo_dependencia`, `motor_categoria_concepto_vigencia`.
- **Normativa**: `motor_regimen_distribucion`, `motor_reglas_base_diferencial_arca`, `motor_concepto_legacy_mapeo`.
- **Persistencia de resultados** (E-6…E-9, antes fuera de alcance por RT-012): `motor_liquidacion`, `motor_liquidacion_relacion`, `motor_liquidacion_concepto`, `motor_liquidacion_base_arca`, `motor_liquidacion_base_arca_aplicacion`, `motor_liquidacion_base_arca_detalle`, `motor_liquidacion_base_arca_diferencial`, `motor_liquidacion_seg_social`.

Principio de la persistencia: **lo liquidado se congela**. La versión de fórmula, la alícuota y el tope aplicados quedan en la fila; reimprimir un recibo no vuelve a resolverlos. Los importes cero también se guardan: un concepto que dio cero no es lo mismo que uno que no aplicó.

## 5. Lo que NO se sembró, y por qué

`VALORES_VARIABLE` (DIVJOR por convenio), `VALORES_PARAMETROS`, `PERFIL`, `PERFIL_CONCEPTO`, `CATEGORIA_CONCEPTO` y `CONCEPTO_LEGACY_MAPEO` referencian `id_convenio`, `id_categoria` y `empresa_id` **del libro funcional**, no de `sue007s` / `sue006s` / `sue086s`. El propio Excel aclara que los `empresa_id` 1/2 son contextos anonimizados. Sembrarlos apuntaría a registros equivocados, así que se cargan por pantalla cuando se mapeen los convenios reales. Los perfiles del v5, además, son el caso testigo DEMO y no la regla productiva: *"el universo productivo inicial de conceptos sigue abierto"*.

## 6. Decisiones tomadas al aplicar

1. **ACTAFUT sin incidencias**: el v5 omite sus 13 combinaciones a propósito (*"exige acuerdo/vigencia y bloquea si se usa sin matriz"*). Las 13 filas que había se eliminaron: conservarlas haría que el concepto deje de bloquear.
2. **Topes retirados**: los mínimos sobre REM_1/REM_4/REM_5 se eliminaron porque el v5 los reemplazó por el mínimo sobre REM_10. Es un borrado físico sobre una tabla normativa, contra el criterio general de RT-010, porque la tabla no tiene marca de baja ni vigencia utilizable y un tope fantasma produce cálculo incorrecto, no un aviso. Ninguno fue usado en una liquidación y el dato anterior sigue versionado en `database/seeders/motor/datos/`.
3. **Vigencias de ASIST**: el concepto tiene dos versiones (v1 desde 2026-01-01 sin la regla de inasistencias, v2 desde 2026-07-01 con ella y con redondeo DECIMAL propio). El libro **ya trae la v1 cerrada** el 2026-06-30, así que no hubo que inferir nada; el seeder conserva igual el cierre automático como salvaguarda, porque dos versiones PUBLICADA vigentes a la vez bloquearían por la propia regla del v2 (*"debe haber exactamente una versión PUBLICADA vigente"*).
4. **Unidades pendientes**: COMIDA y VIAT_ESP llegan sin unidad (`PENDIENTE_UNIDAD`). La columna pasó a aceptar NULL en lugar de asignarles una: el motor bloquea antes de suponerla.
5. **Parámetros retirados**: `PORC_TITULO` se unificó en `ADTIT` (P-9), `HS_TOLERANCIA_HORARIA` y `PORC_PLUS_PLURALIDAD` pasaron a `VALCAT` (F-1) y los `RECARGO_*` eran propuestas hoy resueltas como literales justificados. Baja lógica, no borrado. Los técnicos `REDONDEO_DECIMALES`, `DIVISOR_DIAS` y `HORAS_MES_REFERENCIA` siguen activos: su equivalente v2 son `VAR(DIVJOR)` y `VAR(HMJORN)` de alcance convenio, y la sustitución es parte del port del DSL.

## 6-bis. Etapa 3A contrastada contra el motor de Oscar (2026-10-13)

El instructivo `instructivo-daniel.html` trae `sim/motor_contributivo.js`, que no estaba en GitHub: es la Etapa 3A **ejecutable**. Contrastado contra la implementación propia, aparecieron cinco diferencias. Tres ya están aplicadas:

1. **REM_10 es una base DERIVADA, no sale de la matriz.** `REM_10 = REM_2 − importe a detraer (Ley 27.430)`, con el piso mínimo previsional como tope. Y con detracción **cero** no es que no se informe: **se informa en cero** (ARCA G18). Implementado en `MotorLiquidacion` (etapa 6-bis) y en `BaseImponibleResultado::derivar()`.
2. **Las contribuciones de seguridad social se liquidan sobre REM_10**, no sobre la base nominal de cada alícuota, cuando REM_10 está calculada. Aplica a las líneas generales y al régimen sustitutivo; los adicionales y las sustituciones de subsistema conservan su propia base.
3. **Los diferenciales de base entran antes de los topes**: lo topeable es bruto + diferencial. El motor los recibe resueltos por el módulo responsable (`ContextoRelacionLaboral::$diferencialesBase`), no los calcula: el diferencial de obra social por jornada parcial es del módulo de OS (art. 92 ter LCT + ARCA G18).

Dos quedan pendientes y están registradas como divergencia deliberada:

4. **Topes sin registro.** El motor de Oscar no topea si no hay registro para (período, base, componente) y sigue adelante; la implementación propia mantiene la **regla B** del Excel funcional — base topeable sin tope parametrizado **bloquea** — porque la spec escrita es explícita: *"el motor no debe suponer ni arrastrar un tope que no esté expresamente parametrizado"*. Con los topes del v5 cargados (REM_1/4/5/10 para 07 y 08/2026) ninguna de las dos posturas cambia el resultado. **A confirmar con Oscar.**
5. **Consistencia entre subsistemas**: si dos aplicaciones componen la misma REM_n con importes distintos, su motor bloquea con `BASE_ARCA_INCONSISTENTE_ENTRE_SUBSISTEMAS`. Todavía no está implementado.

El caso de control **EMP-028** (fila anonimizada del F.931 de agosto 2026, empleado de tiempo parcial) quedó como test de aceptación en `tests/Unit/Motor/Etapa3aEmp028Test.php` y cierra exacto: REM_4 = REM_8 = 1.575.554,29 (582.313,19 + 993.241,10 de diferencial), REM_10 = 578.811,35 (582.313,19 − 3.501,84) y las cuatro contribuciones del encuadramiento B sobre REM_10 — SIPA 62.337,98 · INSSJP 9.203,10 · AAFF 27.204,13 · FNE 5.440,83.

Las columnas de obra social del control quedaron explicadas, y confirman dónde está la frontera entre módulos. El 3 % de aporte y el 6 % de contribución de OS se declaran **descompuestos**, con el Fondo Solidario de Redistribución separado:

| Columna del control | Importe | Composición |
|---|---|---|
| `ap_ss` | 88.613,84 | SIPA 11 % de REM_1 (64.054,45) + INSSJP 3 % de REM_5 (17.469,40) + **FSR 0,45 % de REM_4** (7.089,99) |
| `ap_os` | 40.176,63 | OS **2,55 %** de REM_4 — el 3 % neto del 0,45 % que va al FSR |
| `cont_os` | 80.353,27 | OS **5,1 %** de REM_8 — el 6 % neto del 0,9 % del FSR |

O sea que el FSR del trabajador se informa dentro de los aportes de seguridad social, no con la obra social. El motor no calcula ninguna de las tres: OS y FSR tienen `modulo_responsable = OBRA_SOCIAL` y las resuelve ese módulo, que todavía no existe — el liquidador las deja registradas como no ejecutadas con ese motivo, que es el comportamiento correcto.

## 7. Cómo se regenera

```bash
python3 -I database/seeders/motor/herramientas/generar_datos_v5.py <ruta_del_xlsx>
php artisan migrate                                             # esquema + re-seed normativo
php artisan db:seed --class=MotorVersionesInicialesSeeder       # publica las versiones base
php artisan db:seed --class=MotorFormulasV5Seeder               # carga las fórmulas DSL
```

El generador emite `database/seeders/motor/datos/v5/*.php` con los datos tal como vienen de la hoja, normalizando sólo lo que el xlsx deforma (fechas en serial, booleanos S/N, decimales en notación científica). El mapeo a las columnas `motor_*` lo hace la migración, para que el dato versionado siga siendo fiel a la fuente.

## 8. Lo que falta

Las fórmulas están cargadas como dato pero **todavía no se ejecutan**: hace falta portar el parser/evaluador del DSL (`dsl_v11.js` del repo `okat0566/motor-zonda`, 906 líneas y 110 pruebas) a PHP. Hasta entonces el motor sigue calculando con las estrategias tipadas, cuyas fórmulas difieren de las verificadas — ver §3 de este documento y las diferencias registradas en el análisis del repo.

Cuatro puntos a cerrar con Oscar antes del port:

1. ¿El módulo **Fórmulas** también pasó `LISTO_DESARROLLO`, o sólo la Etapa 3A? La auditoría acredita 3A, y hay versiones en BORRADOR (ADTIT, COMIDA, VIATESP, APJUB, APINSSJYP).
2. El **DBML v2** como archivo: el repo sólo tiene el v1 del 30/08.
3. El **fixture de BLEXA 08/2026** para reproducir la conciliación contra F.931.
4. La **firma del DSL**: este Excel usa `NOV(COD)` y `dsl_v11.js` exige `NOV(COD,CANT)`.
