# Implementación de cantidad_total en pedidos_produccion

**Fecha:** 17/12/2025  
**Status:** ✅ COMPLETADO

---

## ¿Qué se implementó?

Se agregó un campo `cantidad_total` a la tabla `pedidos_produccion` que guarda la suma total de todas las prendas de un pedido.

### Ejemplo:
```
Pedido PED-2025-001
├── Prenda 1: Camisa (50 unidades)
│   ├── S: 10
│   ├── M: 15
│   ├── L: 15
│   └── XL: 10
│
├── Prenda 2: Pantalón (30 unidades)
│   ├── 32: 10
│   ├── 34: 10
│   └── 36: 10
│
└── cantidad_total = 80 (suma de todas)
```

---

## Cambios realizados

### 1. Migración
**Archivo:** `database/migrations/2025_12_17_101039_add_cantidad_total_to_pedido_produccion_table.php`

```php
Schema::table('pedidos_produccion', function (Blueprint $table) {
    $table->integer('cantidad_total')->default(0)
        ->comment('Suma total de cantidades de todas las prendas');
});
```

**Ejecutada:** ✅ Exitosamente

---

### 2. Model: PedidoProduccion
**Archivo:** `app/Models/PedidoProduccion.php`

**Cambios:**
- ✅ Agregado `cantidad_total` a `$fillable`
- ✅ Ya tenía acceso el `getCantidadTotalAttribute()` que suma prendas

```php
protected $fillable = [
    // ... otros campos ...
    'cantidad_total',
];
```

---

### 3. Model: PrendaPedido
**Archivo:** `app/Models/PrendaPedido.php`

**Cambios:**
- ✅ Agregado `protected static function boot()` con tres eventos:
  - `static::created()`: Recalcula cantidad_total cuando se crea una prenda
  - `static::updated()`: Recalcula cantidad_total cuando se actualiza una prenda  
  - `static::deleted()`: Recalcula cantidad_total cuando se elimina una prenda

```php
protected static function boot()
{
    parent::boot();

    static::created(function ($prenda) {
        if ($prenda->numero_pedido) {
            $cantidadTotal = static::where('numero_pedido', $prenda->numero_pedido)
                ->sum('cantidad');
            
            PedidoProduccion::where('numero_pedido', $prenda->numero_pedido)
                ->update(['cantidad_total' => $cantidadTotal]);
        }
    });

    // ... otros eventos ...
}
```

---

### 4. Controller: PedidosProduccionController
**Archivo:** `app/Http/Controllers/Asesores/PedidosProduccionController.php`

**Cambios:**
- ✅ Agregado cálculo manual de `cantidad_total` antes de hacer commit:

```php
// Calcular cantidad_total: suma de todas las cantidades de todas las prendas
$cantidadTotalPedido = PrendaPedido::where('numero_pedido', $pedido->numero_pedido)
    ->sum('cantidad');

$pedido->update([
    'cantidad_total' => $cantidadTotalPedido
]);
```

---

## Flujo de Guardado

### Cuando se crea un Pedido:

1. **Frontend** envía JSON con prendas y sus cantidades
2. **Controller** `crearDesdeCotizacion()`:
   - Crea `PedidoProduccion`
   - Itera y crea cada `PrendaPedido`
   - Calcula `cantidad_total` = suma de `prenda.cantidad`
   - Actualiza `pedidos_produccion.cantidad_total`
3. **Base de datos** guarda el pedido con `cantidad_total`

### Cuando se modifica una Prenda (después):

1. Se actualiza `prendas_pedido.cantidad`
2. **Event** `PrendaPedido::updated()` se dispara
3. Recalcula automáticamente `pedidos_produccion.cantidad_total`
4. Se guarda en BD sin necesidad de intervención manual

---

## Ventajas

✅ **Acceso rápido:** No requiere calcular cada vez que se necesita  
✅ **Sincronización automática:** Los eventos mantienen el valor actualizado  
✅ **Integridad:** Si se cambia cantidad de prenda, cantidad_total se actualiza  
✅ **Auditoría:** Historial de totales registrado en la BD  
✅ **Reportes:** Fácil filtrar/agrupar por cantidad_total

---

## Valores en BD

```sql
SELECT 
    id,
    numero_pedido,
    cliente,
    cantidad_total,
    (SELECT SUM(cantidad) FROM prendas_pedido 
     WHERE numero_pedido = pedidos_produccion.numero_pedido) as verificacion
FROM pedidos_produccion;

-- Resultado esperado: cantidad_total = verificacion
```

---

## Próximos pasos

- [ ] Agregar validación mínima de cantidad_total (> 0)
- [ ] Crear vista que muestre cantidad_total prominentemente
- [ ] Filtros en listado: "Pedidos con >100 unidades", etc.
- [ ] Reporte de capacidad producción vs cantidad_total
