# 📋 MIGRACIÓN - Agregar Número de Cotización a Pedidos

## 🎯 Objetivo
Agregar el campo `numero_cotizacion` a la tabla `pedidos_produccion` para poder referenciar directamente la cotización sin necesidad de usar el ID.

---

## 📊 Cambios Realizados

### 1. **Modelo PedidoProduccion.php**
- ✅ Agregado `numero_cotizacion` al array `$fillable`

### 2. **Migración: 2025_11_22_add_numero_cotizacion_to_pedidos_produccion.php**
- ✅ Agrega columna `numero_cotizacion` (string, nullable)
- ✅ Crea índice para búsquedas rápidas
- ✅ Reversible (puede deshacerse)

### 3. **Controller PedidosProduccionController.php**
- ✅ Actualizado método `crearDesdeCotizacion()` para guardar `numero_cotizacion`

### 4. **Vista plantilla-erp.blade.php**
- ✅ Muestra número de cotización en la plantilla

---

## 🚀 Cómo Ejecutar la Migración

### Opción 1: Ejecutar migración (Recomendado)
```bash
php artisan migrate
```

### Opción 2: Ejecutar migración específica
```bash
php artisan migrate --path=database/migrations/2025_11_22_add_numero_cotizacion_to_pedidos_produccion.php
```

### Opción 3: Deshacer migración (si es necesario)
```bash
php artisan migrate:rollback --step=1
```

---

## 📝 Estructura de la Migración

```php
// Agregar columna
$table->string('numero_cotizacion')->nullable()->after('cotizacion_id');

// Crear índice
$table->index('numero_cotizacion');
```

---

## 💾 Datos Guardados

Cuando se crea un pedido desde una cotización, ahora se guarda:

```php
PedidoProduccion::create([
    'cotizacion_id' => 1,                           // ID de la cotización
    'numero_cotizacion' => 'COT-2025-001',         // Número de la cotización ← NUEVO
    'numero_pedido' => 1,                          // Número del pedido
    'cliente' => 'EMPRESA XYZ',
    'asesora' => 'María García',
    'forma_de_pago' => 'Efectivo',
    'estado' => 'No iniciado',
    'fecha_de_creacion_de_orden' => '2025-11-22'
]);
```

---

## 🔍 Verificar que Funcionó

### En la base de datos
```sql
-- Ver estructura de la tabla
DESCRIBE pedidos_produccion;

-- Ver si existe la columna
SELECT numero_cotizacion FROM pedidos_produccion LIMIT 1;

-- Ver índices
SHOW INDEX FROM pedidos_produccion;
```

### En Laravel Tinker
```bash
php artisan tinker
>>> $pedido = PedidoProduccion::first()
>>> $pedido->numero_cotizacion
=> "COT-2025-001"
```

---

## 🎯 Beneficios

✅ **Referencia directa**: Puedes acceder al número de cotización sin hacer JOIN
✅ **Búsquedas rápidas**: Índice en la columna para búsquedas eficientes
✅ **Trazabilidad**: Fácil ver de qué cotización vino cada pedido
✅ **Reportes**: Puedes generar reportes relacionando cotizaciones y pedidos

---

## 📚 Ejemplo de Uso

### Buscar pedidos por número de cotización
```php
$pedidos = PedidoProduccion::where('numero_cotizacion', 'COT-2025-001')->get();
```

### Mostrar en plantilla
```blade
@if($pedido->numero_cotizacion)
    <p>Cotización: {{ $pedido->numero_cotizacion }}</p>
@endif
```

### En API
```php
return response()->json([
    'numero_pedido' => $pedido->numero_pedido,
    'numero_cotizacion' => $pedido->numero_cotizacion,
    'cliente' => $pedido->cliente
]);
```

---

## ⚠️ Notas Importantes

1. **Nullable**: El campo es nullable, por lo que pedidos antiguos no tendrán valor
2. **Índice**: Se crea un índice para búsquedas rápidas
3. **Reversible**: Puedes deshacer la migración si es necesario
4. **Sin datos históricos**: Los pedidos existentes no tendrán número de cotización

---

## 🔄 Flujo Completo

```
1. Asesor crea cotización
   → Se genera numero_cotizacion (ej: COT-2025-001)

2. Admin aprueba cotización
   → Hace clic en "Aceptar"

3. Sistema crea pedido
   → Guarda numero_cotizacion en pedidos_produccion
   → Guarda cotizacion_id (FK)

4. Asesor ve plantilla
   → Muestra número de cotización
   → Muestra número de pedido
   → Muestra datos de la cotización
```

---

## ✅ Checklist

- [ ] Ejecutar migración: `php artisan migrate`
- [ ] Verificar en BD que la columna existe
- [ ] Crear un pedido desde cotización
- [ ] Verificar que se guarda el número de cotización
- [ ] Ver plantilla ERP y confirmar que muestra el número

---

**Versión:** 1.0
**Fecha:** 22 de Noviembre de 2025
**Estado:** ✅ LISTO PARA EJECUTAR
