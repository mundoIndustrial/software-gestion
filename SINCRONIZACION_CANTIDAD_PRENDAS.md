# Sincronización Automática de Cantidad en Prendas Pedido

**Fecha:** 17/12/2025  
**Status:** ✅ COMPLETADO

---

## ¿Qué se implementó?

Se implementó sincronización automática donde el campo `cantidad` en la tabla `prendas_pedido` siempre contiene la suma total de todas las tallas del JSON `cantidad_talla`.

### Ejemplo:

```json
// Entrada en cantidad_talla:
{
  "S": 25,
  "M": 35,
  "L": 40,
  "XL": 20
}

// Resultado en cantidad:
120  (suma: 25+35+40+20)
```

---

## Cambios realizados

### 1. Migración
**Archivo:** `database/migrations/2025_12_17_101525_change_cantidad_type_prendas_pedido.php`

```php
Schema::table('prendas_pedido', function (Blueprint $table) {
    // Cambiar cantidad de varchar(56) a integer
    $table->integer('cantidad')->change();
});
```

**Ejecutada:** ✅ Exitosamente

---

### 2. Model: PrendaPedido
**Archivo:** `app/Models/PrendaPedido.php`

**Cambios en boot method:**

```php
protected static function boot()
{
    parent::boot();

    // ANTES de guardar: calcular cantidad desde cantidad_talla
    static::saving(function ($prenda) {
        if (!empty($prenda->cantidad_talla) && is_array($prenda->cantidad_talla)) {
            // Sumar todas las cantidades de todas las tallas
            $prenda->cantidad = array_sum($prenda->cantidad_talla);
        }
    });

    // ... resto de eventos para recalcular cantidad_total del pedido ...
}
```

---

## Flujo de Guardado

### Cuando se crea una PrendaPedido:

1. **Frontend** envía `cantidad_talla` como JSON: `{"S":25, "M":35, "L":40, "XL":20}`
2. **Controller** `crearDesdeCotizacion()`:
   ```php
   $cantidadTotal = array_sum($cantidadesPorTalla);  // 120
   
   PrendaPedido::create([
       'cantidad_talla' => json_encode($cantidadesPorTalla),
       'cantidad' => $cantidadTotal,  // Se puede omitir, se calcula automáticamente
       // ... otros datos ...
   ]);
   ```
3. **Event** `PrendaPedido::saving()` se dispara:
   - Recalcula `cantidad = array_sum($cantidad_talla)`
   - Garantiza consistencia: `cantidad` siempre = suma de tallas
4. **Base de datos** guarda la prenda

---

## Ventajas

✅ **Sincronización Automática:** Si cambias `cantidad_talla`, `cantidad` se recalcula automáticamente  
✅ **Integridad de Datos:** Nunca habrá desincronización entre tallas y cantidad total  
✅ **Simplicidad:** No necesitas calcular manualmente en el controller  
✅ **Type Safety:** `cantidad` ahora es integer, no varchar  
✅ **Escalabilidad:** Funciona incluso si se agregan/eliminan tallas dinámicamente

---

## Casos de Uso

### Caso 1: Crear con varias tallas
```php
$prenda = PrendaPedido::create([
    'cantidad_talla' => json_encode(['S' => 10, 'M' => 20, 'L' => 15]),
    'cantidad' => 100  // Se ignora, se calcula automáticamente
]);

// Resultado:
// cantidad_talla = {"S":10, "M":20, "L":15}
// cantidad = 45  (10+20+15)
```

### Caso 2: Editar tallas
```php
$prenda->update([
    'cantidad_talla' => json_encode(['S' => 30, 'M' => 40, 'L' => 30, 'XL' => 20])
]);

// Resultado:
// cantidad_talla = {"S":30, "M":40, "L":30, "XL":20}
// cantidad = 120  (30+40+30+20)
// cantidad_total del pedido se recalcula automáticamente
```

### Caso 3: Validación
```php
$prenda = PrendaPedido::find(1);
echo $prenda->cantidad;  // Siempre será igual a sum($prenda->cantidad_talla)
```

---

## Relaciones

```
pedidos_produccion
├── cantidad_total = Σ(prendas.cantidad)
└── prendas_pedido (1..N)
    ├── cantidad = Σ(cantidad_talla)
    └── cantidad_talla = {"S":25, "M":35, ...}
```

### Flujo de Cálculos:
1. Usuario ingresa tallas: S=25, M=35, L=40, XL=20
2. `cantidad_talla` se guarda como JSON
3. `cantidad` = 25+35+40+20 = **120** ✅
4. `cantidad_total` del pedido = suma de todas las prendas

---

## Validaciones Recomendadas

```php
// En PrendaPedido model:
protected static function boot()
{
    parent::boot();

    static::saving(function ($prenda) {
        // Calcular cantidad
        if (!empty($prenda->cantidad_talla)) {
            $prenda->cantidad = array_sum($prenda->cantidad_talla);
        }
        
        // Validar que cantidad sea > 0
        if (empty($prenda->cantidad) || $prenda->cantidad <= 0) {
            throw new \InvalidArgumentException('Cantidad debe ser mayor a 0');
        }
    });
}
```

---

## Verificar en BD

```sql
-- Verificar que cantidad = suma de tallas
SELECT 
    id,
    nombre_prenda,
    cantidad,
    cantidad_talla,
    JSON_EXTRACT_VARCHARJSON_SUM(cantidad_talla) as suma_verificacion,
    CASE 
        WHEN cantidad = JSON_EXTRACT_VARCHARJSON_SUM(cantidad_talla) 
        THEN 'OK'
        ELSE 'ERROR'
    END as sincronizacion
FROM prendas_pedido;
```

---

## Próximos Pasos

- [x] Migración aplicada
- [x] Model actualizado
- [x] Sincronización automática implementada
- [ ] Tests unitarios
- [ ] Validación de cantidad > 0
- [ ] Agregar auditoría de cambios
