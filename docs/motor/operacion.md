# Motor de cálculo (Zonda Sueldos) — guía de operación

Módulo `app/Motor/` (arquitectura hexagonal) + controladores en `app/Http/Controllers/Motor/` + páginas en `resources/js/Pages/Motor/`. Menú lateral **Motor de cálculo** (`resources/views/layouts/legajos.blade.php`).

Especificación de origen: `Zonda_Guia_Practica_Tablas_y_Relaciones.md`, `Zonda_Especificacion_Tecnica_Tablas_2026-08-30.xlsx`, `Zonda_Motor_Calculo_2026-08-30.xlsx`, `mockup_conceptos_zonda_7_9_3.html`. Diccionario de las 41 tablas `motor_*`: [`diccionario.md`](diccionario.md).

## 1. Puesta en marcha (una sola vez por entorno)

La base `swnov` es **compartida con el portal Drakma**. Solo se aplican migraciones pendientes; jamás `migrate:rollback/fresh/refresh/reset` ni `db:wipe`.

```bash
# 0. Comprobar que no haya config cacheada y cuál es la conexión real
ls bootstrap/cache/config.php 2>/dev/null && php artisan config:clear
php artisan migrate:status | grep -i pending          # deben ser solo las 7 de database/migrations/motor/

# 1. Esquema + datos normativos del Excel (todo SIN_CONFIRMAR / SIN_DATO)
php artisan migrate                                   # crea 41 tablas motor_* y siembra la normativa

# 2. Catálogos ARCA desde las tablas SICOSS ya cargadas (insertOrIgnore: no pisa lo del Excel)
php artisan db:seed --class=MotorCatalogosSicossSeeder   # o: php artisan motor:importar-catalogos-sicoss

# 3. Primera versión publicada de los 37 conceptos (idempotente por hash)
php artisan db:seed --class=MotorVersionesInicialesSeeder

# 4. Relaciones laborales canónicas desde sue001s (idempotente; por empresa o todas)
php artisan motor:sincronizar-relaciones 05 --periodo=2026-07

# 5. Encuadramiento patronal A/B de cada empresa → pantalla "Encuadramiento empresas"
#    (sin él, el motor bloquea todas las contribuciones: ENCUADRAMIENTO_AUSENTE)

# 6. Mapeo de novedades legacy → pantalla "Mapeo de novedades" (sue031s.cod_nov × columna de sue028s → tipo del motor)

# 7. Normalizar las novedades del período
php artisan motor:normalizar-novedades 2026-07
```

Después de 5–7, el simulador (`/motor/simulador`) puede liquidar en entorno **PRUEBA** (acepta `SIN_CONFIRMAR` con aviso). Para **PRODUCCION** cada alícuota, tope, régimen, básico e incidencia que intervenga debe estar `CONFIRMADO`.

## 2. Ciclo mensual

| Paso | Dónde | Qué hace |
|---|---|---|
| Sincronizar relaciones | Relaciones laborales → *Sincronizar desde legajos* (o `motor:sincronizar-relaciones`) | Altas nuevas, cambios de categoría (historiza), bajas, cambios ARCA. Cambio de empresa → `INCONSISTENTE_ORIGEN` (no se mueve solo). |
| Normalizar novedades | Relaciones laborales → *Normalizar novedades* (o `motor:normalizar-novedades AAAA-MM`) | Traduce `sue028s` a `motor_novedades`. Lo sin mapeo queda `SIN_MAPEO` en `motor_if_novedad_origen` y bloquea en PRODUCCION. |
| Cargar básicos / topes del período | Escalas y básicos · Normativa ARCA → Topes | Sin básico vigente: `BASICO_SIN_IMPORTE`. Sin tope del período para REM_1/4/5: `TOPE_SIN_PARAMETRIZACION` (no se arrastra el anterior). |
| Simular | Simulador | Devuelve haberes, bases Zonda, bases ARCA 1‑10, aportes, contribuciones, avisos, **bloqueos** y traza. No persiste. |
| Revisar integridad | Integridad | Conceptos mínimos, circularidades, incidencias PENDIENTE, registros SIN_DATO/SIN_CONFIRMAR, códigos sin mapear, legajos sin relación, conceptos sin versión. |

## 3. Conceptos: borrador → validar → publicar

- La edición siempre modifica el **borrador**; el motor liquida con la **versión publicada vigente** al último día del período (snapshot JSON + sha256 en `motor_concepto_versiones`).
- *Validar* corre las 20 validaciones del mockup (R2, R3, R5, R7, R8, R9, R10, R13…). *Publicar* pide vigencia desde/hasta, tipo de cambio y motivo; si el hash no cambió no crea versión; si la vigencia cae entre dos versiones las recorta; una versión `usada_en_liquidacion` nunca se modifica (RT-006).
- Cambiar incidencias ARCA o aplicabilidad de un concepto publicado exige publicar nueva versión (la matriz avisa).
- Borrar = baja lógica (`activo = 0`), nunca borrado físico (RT-010).

## 4. Reglas que producen bloqueos (fail-closed)

Precedencia de parámetros y aplicabilidad: TRABAJADOR (5) > EMPRESA (4) > CATEGORÍA (3) > CONVENIO (2) > GENERAL (1); empate = `PARAMETRO_EMPATE` / `CONFLICTO_PERFILES`. Topes A‑H: sin registro, importe vacío o duplicado → bloqueo; prohibido arrastre temporal y entre componentes. Regímenes: exactamente un BASE; SUSTITUTIVO reemplaza, ADICIONAL suma, DESTINO redirige (un chofer con RIFL paga 5 % + OS 6 %, nunca 25 %). Alícuota de aporte con `codigo_condicion` NULL = comodín; la específica gana.

## 5. Tests

```bash
php artisan test --filter=Motor      # ÚNICA forma permitida de correr tests en este proyecto
```

`tests/TestCase.php` aborta si la conexión no es sqlite `:memory:`. Los tests Feature del motor levantan su propio esquema (`tests/Support/Motor/UsaEsquemaMotor.php`) y **requieren `pdo_sqlite`** (`sudo apt install php8.3-sqlite3`); sin la extensión se marcan *skipped*. Los tests unitarios corren con repositorios en memoria y el fixture `tests/Fixtures/Motor/CasoJulio2026.php`.

## 6. Entorno

`config/motor.php` → `MOTOR_ENTORNO` (`PRUEBA` por defecto). Los parámetros técnicos (`REDONDEO_DECIMALES`, `DIVISOR_DIAS`, `HORAS_MES_REFERENCIA`) viven en `motor_parametros` con alcance GENERAL y estado `CONFIRMADO`, no en config.

## 7. Desviaciones respecto de la especificación

Ver §11 del plan de implementación: prefijo `motor_`; booleanos en vez de DECIMAL para S/N; `condicion_arca = TODAS` → NULL; FK hacia legacy lógicas (no físicas); `TIPOS_NOVEDAD`/`NOVEDADES` creadas en el motor + `motor_novedad_mapeo`; `EMPRESA_ENCUADRAMIENTO` creada; ~20 columnas de extensión del mockup en `motor_conceptos` + `motor_concepto_versiones` + `motor_concepto_aplicabilidad`; fórmulas como estrategias tipadas (sin DSL); RENATRE en lugar de RENATEA.
