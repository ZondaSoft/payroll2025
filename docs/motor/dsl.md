# DSL v1.1 — port a PHP

`app/Motor/Dominio/Formulas/` es el port del lenguaje de fórmulas de Zonda. La fuente es **`src/dsl_v11.js` tal como viene en el instructivo** (SHA‑256 `d074418d9320381bd4925422350ca9f62e7fe6a9e703e873731b0003e5c13ce1`), no la copia de `okat0566/motor-zonda` en GitHub, que quedó atrás: **F‑2/P‑6 eliminó `NOV(COD, CANT)`** y ahora `NOV()` acepta exactamente un código, porque la novedad tiene un único valor tipado y su obligatoriedad vive en la versión (`CONCEPTO_VERSION_NOVEDAD`).

## Por qué un DSL y no código PHP

Las fórmulas son **datos versionados**: viven en las columnas `expr_*` de `motor_concepto_versiones`, con su vigencia y su variante. Una paritaria o un cambio de convenio se publica como versión nueva, sin tocar el código ni desplegar. El motor conserva las estrategias tipadas para el método `MODULO` (aportes, contribuciones, antigüedad), que no se expresan en el lenguaje.

## Piezas

| Clase | Qué hace |
|---|---|
| `Dsl` | catálogos del lenguaje: prefijos, funciones, reservadas, alias de migración, tokens fuera de la v1.1 |
| `Tokenizador` | lexer. Conserva el **texto** de cada número: el importe se evalúa desde ahí, nunca desde un float |
| `Parser` | AST con la precedencia del contrato: `O` < `Y` < comparación < `+ -` < `* /` < unario |
| `Nodo` | nodos del AST, con la misma forma que el parser de referencia para poder comparar árboles |
| `Dimension` | álgebra dimensional: `ARS`, `HORAS`, `DIAS`, `KM`, `UNIDADES`, escalares y la unidad *sin declarar* |
| `Analizador` | referencias, alias, dependencias y unidades. Es lo que impide que una fórmula "ande" por casualidad |
| `Evaluador` | ejecuta el AST con los valores del resolvedor, en `BigDecimal` |
| `ResolvedorDsl` | puerto: qué le pregunta el evaluador al mundo (`PARAM`, `NOV`, `VAR`, `VALCAT`, `CONC`, `BASE`, `MOD`) |
| `BloqueoDsl` | el evaluador no pudo producir un número, con el código del contrato |
| `TrazaFormula` | de dónde salió cada valor: la respuesta a "por qué este importe" |

## Reglas que el port preserva

- **Suma y resta exigen unidades compatibles**; la multiplicación y la división las componen. Sumar días de inasistencia con llegadas tarde es un error, no una suma de números.
- **Una magnitud nunca se compara contra un escalar**: `NOV(INASIS) > 0` es incompatible; el tope se declara en la misma unidad (`PARAM(INASIS_MAX)` en DIAS).
- **El literal `0` es dimensionalmente neutro sólo para unificar las ramas de `SI()`**. Fuera de ahí sigue siendo un escalar y `CONC(BASICO) + 0` es incompatible.
- **Una unidad que el catálogo no declara no es un error**: no se puede afirmar incompatibilidad, así que se informa como "sin declarar" y al publicar se convierte en `CFG_UNIDAD_SIN_DECLARAR`. Nunca se inventa una unidad.
- **R13**: un concepto que consume una base no puede integrarla (`DSL_CONSUME_E_INTEGRA`).
- **`VALCAT` sólo lee el valor del propio concepto** (`DSL_VALCAT_AJENO`).
- **Un dato que falta bloquea y no se convierte en cero**: `VAR_SIN_VALOR`, `PARAM_SIN_VALOR`, `VALCAT_SIN_VALOR`, `CONC_BLOQUEADO`, `BASE_BLOQUEADA`, `MOD_SIN_RESULTADO`, `DIVISION_CERO`. La única ausencia que vale cero es una novedad declarada `OPCIONAL`.
- Sólo `PARAM`, `NOV`, `VAR` y `VALCAT` **no** generan arista en el grafo; `CONC`, `BASE` y `MOD` sí.

## Diferencia deliberada con el original

El evaluador de referencia usa coma flotante; este port usa **`BigDecimal` con escala 6 y `HALF_UP`**, y toda división lleva escala explícita. Por eso los importes salen idénticos a los del recibo sin arrastrar el ruido de punto flotante que el propio proyecto documenta (la coherencia entre `8 h / 192` y `1 día / 24` daba una diferencia de 1,7 × 10⁻⁸ en JS; acá es exacta).

## Verificación

`tests/Unit/Motor/Formulas/` — 66 tests. Los importes esperados son los de los recibos de 07/2026 que Oscar cerró al centavo, y el port los reproduce desde la misma expresión DSL:

| Fórmula | Expresión | Resultado |
|---|---|---|
| BASICO 7019 | `VAR(BASCAT) * VAR(HSPACT) / VAR(HSJORN)` | 1.335.827,18 |
| BASICO 221 | `VAR(BASCAT)` | 1.060.010,29 |
| ANTIG | `BASE(BASE_ANTIGUEDAD) * MOD(ANTIGUEDAD.PORCENTAJE)` | 213.732,3488 |
| ASIST | `SI(NOV(INASIS) > PARAM(INASIS_MAX) O …, 0, CONC(BASICO) * PARAM(PORC_ASISTENCIA))` | 106.866,17 |
| HE50 | `CANT * VALUNI * 1.5` sobre `BASE(BASE_HORA_NORMAL) / VAR(HMJORN)` | 118.020,3310 |
| FERTRAB | `CANT * VALUNI` sobre `BASE(BASE_HORA_NORMAL) / VAR(DIVJOR)` | 107.464,7613 |
| KMREC | `CANT * VALUNI` con `PARAM(TARIFA_KM)` | 208.105,7031 |

## Lo que falta

El port cubre el lenguaje: leer, validar y ejecutar una expresión. Para que el motor liquide con estas fórmulas en lugar de las estrategias tipadas faltan tres piezas, que son el frente siguiente:

1. **`validarVersion()`** — las validaciones de publicación: novedad usada sin declarar y declarada sin usar, literales sin justificar, unidades sin declarar, coherencia de método y componentes.
2. **El grafo topológico** — hoy el orden de cálculo lo fija el liquidador por etapas; el contrato lo deriva de las dependencias (`CONC`, `BASE`, `MOD`) y rechaza ciclos.
3. **La integración**: una estrategia que lea las `expr_*` de la versión vigente y evalúe con un resolvedor sobre el dominio real, en lugar de las estrategias tipadas.
