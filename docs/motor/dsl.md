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
| `VersionFormula` | la parte ejecutable de una versión: las cuatro expresiones, su unidad, su redondeo y sus novedades declaradas |
| `ValidadorVersion` | la puerta de publicación: valida la versión completa en modo `PUBLICACION` o `EDICION` |
| `GrafoCalculo` | deriva el orden de cálculo de las dependencias y detecta ciclos |
| `EvaluadorConcepto` | la secuencia del §6 para un concepto: aplicabilidad → novedades → módulo → condición → cantidad → valor → importe → cierre |
| `BaseConstruida` | una base de Zonda armada con los conceptos ya calculados |
| `EstadoCorrida` | lo que se va calculando: conceptos resueltos, bases armadas y el orden que resolvió el grafo |
| `ResolvedorConEstado` | sirve `CONC` y `BASE` desde la corrida en curso y delega el resto |
| `CorridaFormulas` | el orquestador: elige versión por variante, recorre el grafo, arma bases y evalúa conceptos |
| `DecisionPerfil` | lo que el perfil decidió sobre un concepto: si aplica, con qué variante, o por qué bloquea |
| `ResolvedorDominio` | traduce cada referencia del DSL al dato del trabajador (fuera de `Formulas`: usa los resolutores del dominio) |

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

`tests/Unit/Motor/Formulas/` — 122 tests (parser 32, analizador 21, evaluador 13, validador de versión 20,
grafo 8, evaluador de concepto 16, corrida 12) más 12 de `ResolutorPerfiles`. Los importes esperados son
los de los recibos de 07/2026 que Oscar cerró al centavo, y el port los reproduce desde la misma
expresión DSL:

| Fórmula | Expresión | Resultado |
|---|---|---|
| BASICO 7019 | `VAR(BASCAT) * VAR(HSPACT) / VAR(HSJORN)` | 1.335.827,18 |
| BASICO 221 | `VAR(BASCAT)` | 1.060.010,29 |
| ANTIG | `BASE(BASE_ANTIGUEDAD) * MOD(ANTIGUEDAD.PORCENTAJE)` | 213.732,3488 |
| ASIST | `SI(NOV(INASIS) > PARAM(INASIS_MAX) O …, 0, CONC(BASICO) * PARAM(PORC_ASISTENCIA))` | 106.866,17 |
| HE50 | `CANT * VALUNI * 1.5` sobre `BASE(BASE_HORA_NORMAL) / VAR(HMJORN)` | 118.020,3310 |
| FERTRAB | `CANT * VALUNI` sobre `BASE(BASE_HORA_NORMAL) / VAR(DIVJOR)` | 107.464,7613 |
| KMREC | `CANT * VALUNI` con `PARAM(TARIFA_KM)` | 208.105,7031 |

## De la expresión a la liquidación

El orden de cálculo **ya no es el de las etapas del liquidador**: lo deriva `GrafoCalculo` de las
dependencias que declara cada fórmula. No existe ninguna columna `orden_calculo`, y agregar un concepto
que consuma una base nueva reordena la corrida sin tocar código.

```
versiones vigentes ─┐
perfiles del        ├─→ ResolutorPerfiles ─→ decisión por concepto (aplica + variante)
trabajador          │
                    └─→ GrafoCalculo ─→ orden (C:… y B:…)
                                          │
        ResolvedorDominio ──┐             ▼
        (PARAM/NOV/VAR/     ├──→ CorridaFormulas ─→ EstadoCorrida
         VALCAT/MOD)        │      · B:… → BaseConstruida::armar()
        ResolvedorConEstado ┘      · C:… → EvaluadorConcepto::evaluar()
        (CONC/BASE)
```

**De dónde sale cada referencia** (`ResolvedorDominio`):

| Referencia | Fuente | Lo que *no* es |
|---|---|---|
| `PARAM(COD)` | `ResolutorParametros`, precedencia por ámbito | el alcance CATEGORIA lo retiró el v2: ese valor es `VALCAT` |
| `NOV(COD)` | `motor_novedades` del período | una novedad ausente no es cero salvo que la versión la declare `OPCIONAL` |
| `VAR(BASCAT)` | `motor_basicos` por categoría y vigencia | no sale de `sue001s.bruto` |
| `VAR(HSPACT)` | jornada pactada de la relación | no se infiere de la categoría ni del convenio; en larga distancia no existe |
| `VAR(HMJORN)`, `VAR(DIVJOR)`, `VAR(HSJORN)`, `VAR(HDJORN)` | `motor_valores_variable` por convenio | no se derivan uno del otro (P‑10‑b): el 371/03 tiene 25 y 200, el 40/89 tiene 24 y 192 |
| `VAR(DIASLIQ)`, `VAR(ANTANIO)` | contexto del período y antigüedad de la relación | — |
| `VALCAT(COD)` | `motor_categoria_concepto` + su vigencia | — |
| `CONC`, `BASE` | estado de la corrida, en el orden del grafo | — |
| `MOD(COD)` | `RegistroModulos`; hoy `ANTIGUEDAD.PORCENTAJE` | lo que no está registrado no se puede invocar |

**Los perfiles deciden dos cosas a la vez** (`ResolutorPerfiles`): si el concepto corresponde y con qué
variante se liquida. La precedencia es TRABAJADOR(1) → EMPRESA(2) → CATEGORIA(3) → CONVENIO(4) →
GENERAL(5), y un nivel que no declara el concepto no vota. Tres diferencias con el
`ResolutorAplicabilidad` que reemplaza:

- la regla ganadora aporta **la variante**, que es lo que permite que `BASICO` elija entre
  `JORNADA_PACTADA` y `LARGA_DISTANCIA`;
- dos reglas contradictorias del mismo nivel dan `PERFIL_AMBIGUO` y bloquean **ese concepto**, no la
  liquidación entera;
- que ningún perfil se pronuncie da `PERFIL_SIN_REGLA` en vez de omitir el concepto en silencio.

**Una base no se arma «con lo que haya»**: un integrante `NO_APLICA` se excluye con su motivo, pero uno
bloqueado bloquea la base, y con ella todo lo que la consuma. Es lo que hace que una configuración
incompleta se note en vez de producir un recibo verosímil y equivocado.

## Verificación de punta a punta

`tests/Feature/Motor/CalcularHaberes7019Test.php` liquida el legajo 7019 desde las tablas del motor:
fórmulas publicadas en `motor_concepto_versiones`, perfiles en `motor_perfil`, divisores en
`motor_valores_variable` y básico en `motor_basicos`. Los importes son los del recibo de 07/2026:

| Concepto | Importe |
|---|---|
| BASICO (JORNADA_PACTADA) | 1.335.827,18 |
| ANTIG (8 años × 2 %) | 213.732,3488 |
| ASIST (redondeo DECIMAL) | 106.866,17 |
| HE50 (9,5 h) | 118.020,3310 |
| HE100 (12 h) | 198.771,0839 |
| **BASE_HORA_NORMAL** | **1.656.425,6988** |
| Subtotal remunerativo | 1.973.217,11 |

`BASE_HORA_NORMAL` es el valor que fija el criterio: sale de sumar el **ASIST ya redondeado** a dos
decimales y de excluir MANFON, que este legajo no cobra (regla de nivel TRABAJADOR). Con el ASIST sin
redondear daría 1.656.425,7032 y las horas extras cerrarían mal.

## Lo que falta

1. **Los módulos del tramo 3**: promedios de variables de seis meses (`MOD_HISTORICO_INSUFICIENTE` sin
   historia suficiente), contingencias con su divisor, ILT y SAC sobre ILT.
2. **Las pantallas**: el editor de fórmulas con validación dimensional en vivo, el ABM de perfiles y
   variantes, y el ABM de variables — hoy `DIVJOR` y `HMJORN` sólo se cargan por seeder, y eso es lo
   que bloquea poner un convenio nuevo en marcha.
3. **`INASIS_MAX` y `LLEGTAR_MAX`**: la versión 2 de ASIST los referencia y el libro v5 no los declara.
   Se crearon **sin valor** (migración `000008`): hasta que alguien confirme el tope del convenio, el
   motor bloquea ASIST con `PARAMETRO_SIN_VALOR` en vez de decidir por el CCT.
