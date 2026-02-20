# CRUD de Conceptos de Liquidación

## 📋 Descripción

Se ha creado un CRUD completo para gestionar **Conceptos de Liquidación** en el sistema de nómina 2025. Este módulo permite crear, leer, actualizar y eliminar conceptos utilizados en el cálculo de liquidaciones de sueldos.

## 📁 Estructura de Archivos Creados

### 1. **Controlador**
- **Archivo**: `app/Http/Controllers/ConceptosLiquidacionController.php`
- **Métodos implementados**:
  - `index()` - Mostrar listado de conceptos
  - `create()` - Formulario para crear nuevo concepto
  - `store()` - Guardar nuevo concepto
  - `show()` - Ver detalle de un concepto
  - `edit()` - Formulario para editar concepto
  - `update()` - Guardar cambios del concepto
  - `destroy()` - Eliminar concepto
  - `first()` - Ir al primer concepto
  - `last()` - Ir al último concepto
  - `previous()` - Ir al concepto anterior
  - `next()` - Ir al siguiente concepto
  - `search()` - Búsqueda de conceptos

### 2. **Modelo**
- **Archivo**: `app/Models/Sue102.php`
- **Propiedades**:
  - `codigo` (integer) - Código único del concepto
  - `detalle` (string) - Descripción del concepto
  - `tipo` (integer) - Tipo de concepto (1-9)
  - `formula` (text) - Fórmula de cálculo
  - `porcentaje` (decimal) - Porcentaje si aplica
  - `importe_fijo` (decimal) - Importe fijo si aplica
  - `imponible` (boolean) - Afecta aportes
  - `afecta_sac` (boolean) - Afecta SAC
  - `afecta_vacaciones` (boolean) - Afecta vacaciones
  - `imprime_recibo` (boolean) - Se imprime en recibo
  - `orden_impresion` (integer) - Orden en recibo
  - `activo` (boolean) - Estado del concepto
  - `cuenta_contable` (string) - Cuenta contable asociada
  - `observaciones` (text) - Observaciones adicionales
  - `sicoss_afecta` (boolean) - Afecta a Sicoss
  - `gcias_afecta` (boolean) - Afecta a Ganancias

**Tipos de Conceptos**:
- 1: HABER
- 2: DESCUENTO
- 3: ASIGNACIONES
- 4: NO_REMUNERATIVO
- 5: GANANCIAS
- 6: DEVOLUCIÓN DE GANANCIA
- 7: REDONDEO
- 8: APORTES
- 9: AUXILIARES

### 3. **Rutas**
- **Archivo**: `routes/web.php`
- **Prefijo**: `liquidacion/conceptos`
- **Rutas registradas**:
  - `GET /liquidacion/conceptos` - Index
  - `POST /liquidacion/conceptos` - Store
  - `GET /liquidacion/conceptos/create` - Create form
  - `GET /liquidacion/conceptos/{concepto}` - Show
  - `PUT|PATCH /liquidacion/conceptos/{concepto}` - Update
  - `DELETE /liquidacion/conceptos/{concepto}` - Destroy
  - `GET /liquidacion/conceptos/{concepto}/edit` - Edit form
  - `GET /liquidacion/conceptos/first` - Primer registro
  - `GET /liquidacion/conceptos/last` - Último registro
  - `GET /liquidacion/conceptos/{concepto}/previous` - Anterior
  - `GET /liquidacion/conceptos/{concepto}/next` - Siguiente
  - `GET /liquidacion/conceptos/search` - Búsqueda

### 4. **Componentes Vue 3 / Inertia**
- **Archivo 1**: `resources/js/Pages/Liquidacion/Conceptos.vue`
  - Componente principal del formulario
  - Soporta modo agregar, editar y ver
  - Tabs para información general y configuración
  - Navegación entre registros
  
- **Archivo 2**: `resources/js/Pages/Liquidacion/Search.vue`
  - Componente de búsqueda y listado
  - Tabla con resultados paginados
  - Acciones rápidas (ver, editar, eliminar)

### 5. **Vistas Blade** (Respaldo)
- **Archivos creados**:
  - `resources/views/liquidacion/Conceptos.blade.php` - Formulario CRUD
  - `resources/views/liquidacion/Conceptos/Search.blade.php` - Búsqueda

## 🚀 Cómo Usar

### Acceder al CRUD
```
http://localhost:8000/liquidacion/conceptos
```

### Crear un nuevo concepto
1. Ir a `/liquidacion/conceptos`
2. Hacer clic en "Agregar Concepto"
3. Rellenar el formulario con:
   - Código (único, requerido)
   - Descripción (requerida)
   - Tipo (requerido)
   - Valores y configuración opcional
4. Hacer clic en "Grabar"

### Editar un concepto
1. Ver el concepto
2. Hacer clic en "Modificar"
3. Cambiar los datos
4. Hacer clic en "Grabar"

### Eliminar un concepto
1. Ver el concepto
2. Hacer clic en "Borrar"
3. Confirmar eliminación

### Navegar entre registros
- **Primer registro**: Botón `|<`
- **Anterior**: Botón `<`
- **Siguiente**: Botón `>`
- **Último registro**: Botón `>|`
- **Búsqueda**: Botón `🔍`

## 📊 Validaciones

### Al crear un nuevo concepto:
- `codigo`: Requerido, numérico, único
- `detalle`: Requerido, máximo 250 caracteres
- `tipo`: Requerido, valor entre 1 y 9

### Al actualizar:
- `detalle`: Requerido, máximo 250 caracteres
- `tipo`: Requerido, valor entre 1 y 9
- Todas las afectaciones y valores son opcionales

## 🔗 Integración con el Sistema

El CRUD está completamente integrado con:
- ✅ Autenticación Laravel (requiere estar autenticado)
- ✅ Sistema de permisos/roles (usa RolePermission)
- ✅ Modelo Sue102 existente
- ✅ Inertia.js y Vue 3
- ✅ Bootstrap y Remixicon para UI

## 📝 Próximos Pasos Sugeridos

1. **Agregar validaciones adicionales** según las necesidades del negocio
2. **Crear relaciones** con otros modelos (si es necesario)
3. **Agregar logs** para auditar cambios
4. **Crear policies** para controlar acceso por rol
5. **Agregar mas campos** si la estructura de Sue102 lo requiere

## ✅ Checklist de Implementación

- ✅ Controlador creado con todos los métodos CRUD
- ✅ Modelo Sue102 actualizado con fillable y casts
- ✅ Rutas registradas en web.php
- ✅ Componentes Vue 3 creados (Conceptos.vue y Search.vue)
- ✅ Validaciones en controlador
- ✅ Navegación entre registros implementada
- ✅ Búsqueda implementada
- ✅ Respaldos en Blade templates
- ✅ Cache de rutas actualizado

---

**Fecha de creación**: 13 de febrero de 2026
**Versión**: 1.0
