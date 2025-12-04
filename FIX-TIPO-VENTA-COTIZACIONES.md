# 🔧 FIX: Corrección de tipo_venta en Cotizaciones

**Fecha**: 4 de Diciembre de 2025  
**Status**: ✅ COMPLETADO  
**Impacto**: Crítico - Guardado de tipo de venta en cotizaciones

---

## 📋 Problema Reportado

El usuario reportó que al enviar una cotización de prenda con `tipo_cotizacion: M`, el sistema retornaba error **500** y no se guardaba el tipo de venta en la base de datos.

**Error**: `POST http://servermi:8000/asesores/cotizaciones/prenda 500 (Internal Server Error)`

### Raíz del Problema

**Confusión de campos**: 
- El formulario enviaba `tipo_cotizacion: M` (incorrecto)
- La BD espera `tipo_venta: M` (correcto)
- Existía un campo `tipo_cotizacion` en fillable que no debería estar

**Esquema Correcto de la Tabla `cotizaciones`**:
```sql
-- Campo INCORRECTO (no debe usarse):
tipo_cotizacion_id (FK a tabla tipo_cotizacion - Prenda, Servicio, etc)

-- Campo CORRECTO (M, D, X):
tipo_venta ENUM('M','D','X') -- Mayoreo, Detalle, Otra
```

---

## ✅ Solución Aplicada

### 1. Actualizar `guardado.js`
**Archivo**: `public/js/asesores/cotizaciones/guardado.js`

**Cambio**: 
```javascript
// ANTES:
tipo_cotizacion: tipoCotizacion,

// AHORA:
tipo_venta: tipoCotizacion,
```

**Líneas afectadas**: 
- Línea 88 (payloadEnvio para guardar como borrador)
- Línea 361 (payloadEnvio para enviar cotización)

### 2. Actualizar `CotizacionService.php`
**Archivo**: `app/Services/CotizacionService.php`

**Cambio**: 
```php
// ANTES:
$datos = [
    'tipo_cotizacion' => $datosFormulario['tipo_cotizacion'] ?? null,
    'tipo_venta' => $datosFormulario['tipo_venta'] ?? null,
    ...
];

// AHORA:
$tipoVenta = $datosFormulario['tipo_venta'] ?? null;
$datos = [
    'tipo_cotizacion_id' => $tipoCotizacionId,
    'tipo_venta' => $tipoVenta,  // M, D, X
    ...
];
```

**Cambios**:
- ✅ Remover referencia a `tipo_cotizacion` (campo conflictivo)
- ✅ Buscar solo `tipo_venta` del formulario
- ✅ Logging actualizado

### 3. Actualizar `StoreCotizacionRequest.php`
**Archivo**: `app/Http/Requests/StoreCotizacionRequest.php`

**Cambio**:
```php
// ANTES:
'tipo_cotizacion' => 'required_if:tipo,enviada|nullable|string|in:M,D,X',

// AHORA:
'tipo_venta' => 'required_if:tipo,enviada|nullable|string|in:M,D,X',
```

**Mensajes actualizados**:
```php
'tipo_venta.required_if' => 'El tipo de venta (M/D/X) es requerido para cotizaciones enviadas',
'tipo_venta.in' => 'El tipo de venta debe ser M (Mayoreo), D (Detalle) o X (Otra)',
```

### 4. Actualizar `Cotizacion.php` Model
**Archivo**: `app/Models/Cotizacion.php`

**Cambio**:
```php
// ANTES:
protected $fillable = [
    'tipo_cotizacion',
    'tipo_cotizacion_id',
    'tipo_venta',
    ...
];

// AHORA:
protected $fillable = [
    'tipo_cotizacion_id',
    'tipo_venta',
    ...
];
```

**Razón**: Remover `tipo_cotizacion` del fillable para evitar conflictos

---

## 🎯 Diferencia Conceptual (IMPORTANTE)

### `tipo_cotizacion_id` (FK)
- **Tipo**: Foreign Key a tabla `tipo_cotizacion`
- **Valores**: ID de tipos como "Prenda" (1), "Servicio" (2), etc.
- **Uso**: Identificar QUÉ tipo de producto es la cotización
- **Ejemplo**: 
  ```
  1 = Prenda (ropa, uniformes)
  2 = Servicio (bordado, estampado)
  3 = Accesorios (bolsas, cinturones)
  ```

### `tipo_venta` (ENUM)
- **Tipo**: ENUM('M','D','X')
- **Valores**: 
  - `M` = Mayoreo (compra al por mayor)
  - `D` = Detalle (compra unitaria o pequeña cantidad)
  - `X` = Otra (especial, personalizado)
- **Uso**: Identificar CÓMO se vende/compra el producto
- **Ejemplo**: 
  ```
  Un cliente puede comprar una prenda (tipo_cotizacion_id=1)
  al mayoreo (tipo_venta='M')
  ```

---

## 📊 Esquema Correcto

```sql
CREATE TABLE cotizaciones (
    id BIGINT PRIMARY KEY,
    tipo_cotizacion_id BIGINT,          -- ← FK: ¿QUÉ? (Prenda, Servicio, etc)
    tipo_venta ENUM('M','D','X'),       -- ← ¿CÓMO? (Mayoreo, Detalle, Otra)
    ...
);

FOREIGN KEY (tipo_cotizacion_id) REFERENCES tipo_cotizacion(id);
```

---

## 🧪 Cómo Probar

### 1. Enviar Cotización de Prenda
```
Cliente: MINCIVIL
Tipo de Venta: M (Mayoreo)
Producto: Camisa drill
```

### 2. Verificar BD
```sql
SELECT id, cliente, tipo_venta, tipo_cotizacion_id 
FROM cotizaciones 
WHERE cliente = 'MINCIVIL' 
LIMIT 1;

-- Resultado esperado:
-- id: 123
-- cliente: MINCIVIL
-- tipo_venta: M  ✅ (Ahora debe estar guardado)
-- tipo_cotizacion_id: 1 (o el correspondiente)
```

### 3. En Logs (Laravel)
```
CotizacionService::crear - Datos a guardar
tipo_venta: M
tipo_cotizacion_id: 1
```

---

## ✅ Validación

- ✅ `tipo_venta` se envía correctamente desde formulario
- ✅ `tipo_venta` se valida en StoreCotizacionRequest
- ✅ `tipo_venta` se guarda en la BD
- ✅ `tipo_cotizacion_id` sigue funcionando como FK
- ✅ No hay conflictos entre campos

---

## 📝 Checklist de Cambios

| Archivo | Cambio | Status |
|---------|--------|--------|
| guardado.js | `tipo_cotizacion` → `tipo_venta` | ✅ |
| CotizacionService.php | Remover `tipo_cotizacion` duplicado | ✅ |
| StoreCotizacionRequest.php | Validación correcta | ✅ |
| Cotizacion.php Model | Remover de fillable | ✅ |

---

## 🚀 Próximos Pasos

1. ✅ Probar endpoint de cotizaciones de prenda
2. ✅ Verificar que `tipo_venta: M` se guarde en BD
3. ✅ Verificar que no haya errores 500
4. Crear cotizaciones con `tipo_venta: D` y `X` para validar

---

**Documento Generado**: 4 de Diciembre de 2025  
**Tipo**: Fix / Corrección Crítica  
**Severidad**: Alta  
**Status**: COMPLETADO
