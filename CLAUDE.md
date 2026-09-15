# CLAUDE.md - Payroll2025

Sistema integral de liquidación de sueldos para nómina argentina.

## Stack

- **Backend**: Laravel 11 + Eloquent ORM
- **Frontend**: Vue 3 + Inertia.js + Tailwind CSS + Bootstrap 5
- **Base de datos**: MySQL/MariaDB
- **Build**: Vite
- **Otros**: Select2, Remixicon

## Comandos frecuentes

```bash
# Desarrollo
npm run dev
php artisan serve

# Build
npm run build

# Migraciones
php artisan migrate
php artisan migrate:status

# Caché
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

## Estructura del proyecto

```
app/
├── Http/Controllers/       # 20 controladores
├── Models/                 # 44 modelos (prefijo Sue*)
├── Exports/                # Exportaciones Excel
├── Imports/                # Importaciones (SICOSS, ARCA, BASEDAT)
└── Observers/              # Auditoría

resources/js/
├── Pages/                  # Componentes Vue por módulo
│   ├── Liquidacion/
│   ├── Empleados/
│   ├── Lsd/
│   ├── Sicoss/
│   └── Auth/
├── Components/             # Componentes reutilizables
└── Layouts/

database/migrations/        # 34+ migraciones
routes/web.php              # Todas las rutas (autenticadas)
```

## Modelos clave

| Modelo | Tabla | Descripción |
|--------|-------|-------------|
| Sue001 | sue001s | Empleados/Legajos (hub central) |
| Sue102 | sue102s | Conceptos de liquidación |
| Sue090 | sue090s | Detalle de liquidación por empleado/período |
| Sue100 | sue100s | Períodos de liquidación |
| Sue089 | sue089s | Rangos de tipos de conceptos |
| Sue086 | sue086s | Empresas/Grupos empresarios |
| LsdEmision | lsd_emisiones | Emisiones del Libro Sueldo Digital |
| LsdItem | lsd_items | Items del LSD |
| Conceptosarca | conceptosarcas | Conceptos ARCA/AFIP con tasas |

## Tipos de conceptos (Sue102.tipo)

| Código | Descripción |
|--------|-------------|
| H | Haberes |
| D | Descuentos |
| AS | Asignaciones |
| NR | No Remunerativo |
| GC | Ganancias Crédito |
| DG | Descuento Ganancias |
| R | Retenciones |
| AP | Aportes |
| AU | Aportes Único |

## Módulos del sistema

1. **Legajos** — CRUD empleados (Sue001) con datos personales, laborales, familiares, SICOSS
2. **Conceptos de Liquidación** — CRUD Sue102, validación de rangos (Sue089), mapeo ARCA
3. **Liquidación Individual** — Visualización por empleado/período, clasificación por tipo
4. **LSD** — Generación TXT formato SICOSS, gestión de emisiones con estados
5. **SICOSS** — ABM actividades, condiciones, modalidades, situaciones, obras sociales, siniestros, localidades
6. **Importaciones** — SICOSS, ARCA/AFIP y BASEDAT

## Convenciones de código

### Backend (Laravel)
- Todos los CRUDs implementan navegación: `first()`, `last()`, `previous($id)`, `next($id)`
- Validaciones con `Request::validate()` y mensajes en **español**
- Eager loading con `with()` para todas las relaciones
- Rutas con nombre descriptivo en snake_case

### Frontend (Vue + Inertia)
- Páginas en `resources/js/Pages/{Modulo}/`
- Componentes reutilizables en `resources/js/Components/`
- Select2 para búsquedas de registros relacionados
- Bootstrap 5 para modales de confirmación
- Paginación de 20 registros por defecto

## Rutas principales

```
GET|POST   /liquidacion/conceptos/*       → ConceptosLiquidacionController
GET        /liquidacion/individual         → LiquidacionIndividualController
GET|POST   /legajos/*                     → LegajosController
GET|POST   /bajas/*                       → BajasController
GET|POST   /lsd/*                         → LsdController
GET|POST   /sicoss/*                      → Sicoss*Controller (8 submódulos)
GET|POST   /arca/importar*                → ArcaImportarController
GET|POST   /sicoss/importar*              → SicossImportarController
GET|POST   /basedat/importar*             → LiquidacionImportarController
```

## Flujos de negocio principales

### Liquidación Individual
`Sue100` (período) → `Sue001` (empleado) → `Sue090` (conceptos) → clasificar por tipo via `Sue102`

### Generación LSD
`Sue090` + `Sue001` + `Sue102` → TXT formato SICOSS → `LsdEmision` + `LsdItem[]`

### Importación ARCA
Archivo Excel → `Conceptosarca` → mapeo a `Sue102.concepto_arca`

## Notas importantes

- Los nombres de tablas siguen el patrón `sue{nnn}s` (ejemplo: `sue001s`, `sue102s`)
- Las liquidaciones se almacenan en `sue090s` relacionadas por `codigo` del empleado (no por FK directa)
- El LSD soporta estados: `borrador`, `generado`, `enviado`, `confirmado`, `rechazado`
- Las importaciones generan registros de OK/ERR para auditoría
- Toda la aplicación requiere autenticación (middleware `auth`)

## Motor de Cálculo (Zonda Sueldos) — `app/Motor/`

Módulo que calcula la liquidación desde cero (básicos, antigüedad, novedades, bases Zonda, bases ARCA, topes, aportes, contribuciones, regímenes). Guía de operación: `docs/motor/operacion.md`. Diccionario de tablas: `docs/motor/diccionario.md`.

- **Frontera hexagonal / MVC**: solo `app/Motor/` es hexagonal. `Dominio/` es PHP puro (prohibido `Illuminate\`, `App\Models\`, `Carbon\`, helpers `config()/app()/now()`…; lo verifica `tests/Unit/Motor/ArquitecturaTest.php`); `Aplicacion/` define puertos y casos de uso; `Infraestructura/` implementa Eloquent, adaptadores legacy y EnMemoria. `MotorServiceProvider` es el único lugar que lee `config()` y hace los bindings. Los controladores (`app/Http/Controllers/Motor/`) y páginas (`resources/js/Pages/Motor/`) siguen el MVC del resto del repo.
- **Tablas** con prefijo `motor_` (41), migraciones en `database/migrations/motor/` (cargadas por el provider). Aritmética con `Brick\Math\BigDecimal` (escala 6, HALF_UP); nunca float. FK físicas solo entre `motor_*`; hacia `sue*` la referencia es lógica y los adaptadores nunca hacen JOIN con legacy.
- **Rutas**: bloque `Route::prefix('motor')->name('motor.')` en `routes/web.php`; las rutas literales van **antes** de cada `Route::resource`. Catálogos normativos genéricos vía `Catalogo/CatalogoMotorController` + `DefinicionCatalogo`.
- **Fail-closed**: todo dato normativo tiene `estado_validacion`; en `PRODUCCION` solo `CONFIRMADO` habilita el cálculo. El liquidador acumula `Bloqueo[]` en vez de lanzar.
- **Tests**: `php artisan test --filter=Motor` es la **única** invocación de tests permitida (los Feature de Breeze usan `RefreshDatabase` contra la base compartida). Los Feature del motor exigen `pdo_sqlite`.
