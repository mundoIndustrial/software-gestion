# 🧪 Test del Módulo de Insumos

## Descripción

Este script verifica que la migración del módulo de insumos a `pedidos_produccion` funcione correctamente.

## Cómo Ejecutar

### Opción 1: Ejecutar el script batch (Windows)

```bash
test_insumos.bat
```

### Opción 2: Ejecutar directamente con PHP

```bash
php test_insumos.php
```

### Opción 3: Ejecutar desde Artisan

```bash
php artisan tinker
>>> include 'test_insumos.php'
```

## Qué Verifica el Test

El script ejecuta 10 tests:

### ✓ TEST 1: Estructura de tabla materiales_orden_insumos
- Verifica que la columna `pedido_produccion_id` existe
- Verifica que la columna `tabla_original_pedido` fue eliminada

### ✓ TEST 2: Contar registros en pedidos_produccion
- Muestra el total de pedidos en la BD

### ✓ TEST 3: Contar registros en prendas_pedido
- Muestra el total de prendas en la BD

### ✓ TEST 4: Contar registros en materiales_orden_insumos
- Muestra el total de materiales en la BD

### ✓ TEST 5: Verificar relación MaterialesOrdenInsumos -> PedidoProduccion
- Verifica que la relación funciona correctamente
- Intenta cargar un pedido desde un material

### ✓ TEST 6: Verificar que materiales tienen pedido_produccion_id
- Verifica que todos los materiales tienen el campo `pedido_produccion_id` poblado
- Muestra cuántos materiales tienen el campo

### ✓ TEST 7: Verificar que NO hay materiales con tabla_original_pedido
- Verifica que la migración fue exitosa
- Confirma que no hay datos antiguos

### ✓ TEST 8: Verificar descripción_prendas en un pedido
- Toma un pedido y arma la descripción desde sus prendas
- Muestra cómo se vería la descripción en la tabla

### ✓ TEST 9: Verificar que el filtro de numero_pedido funciona
- Verifica que se puede filtrar por `numero_pedido`

### ✓ TEST 10: Verificar que el filtro de cliente funciona
- Verifica que se puede filtrar por `cliente`

## Resultado Esperado

Si todo funciona correctamente, verás:

```
═══════════════════════════════════════════════════════════════
  TEST - MÓDULO DE INSUMOS (pedidos_produccion)
═══════════════════════════════════════════════════════════════

✓ TEST 1: Verificar estructura de tabla materiales_orden_insumos
  ✅ Columna 'pedido_produccion_id' existe
  ✅ Columna 'tabla_original_pedido' fue eliminada

✓ TEST 2: Contar registros en pedidos_produccion
  ✅ Total de pedidos: 2258

✓ TEST 3: Contar registros en prendas_pedido
  ✅ Total de prendas: 5432

✓ TEST 4: Contar registros en materiales_orden_insumos
  ✅ Total de materiales: 1234

✓ TEST 5: Verificar relación MaterialesOrdenInsumos -> PedidoProduccion
  ✅ Relación funciona: Material ID 1 -> Pedido ID 123

✓ TEST 6: Verificar que materiales tienen pedido_produccion_id
  ✅ Materiales con pedido_produccion_id: 1234 de 1234
  ✅ Todos los materiales tienen pedido_produccion_id

✓ TEST 7: Verificar que NO hay materiales con tabla_original_pedido
  ✅ No hay materiales con tabla_original_pedido (migración exitosa)

✓ TEST 8: Verificar descripción_prendas en un pedido
  Pedido ID: 1
  Número Pedido: 1
  Prendas: 2
  ✅ Descripción armada: CAMISA DRILL (Cant: 50) | PANTALON DRILL (Cant: 30)

✓ TEST 9: Verificar que el filtro de numero_pedido funciona
  ✅ Filtro por numero_pedido funciona: 1

✓ TEST 10: Verificar que el filtro de cliente funciona
  ✅ Filtro por cliente funciona: EMPRESA XYZ

═══════════════════════════════════════════════════════════════
  TESTS COMPLETADOS
═══════════════════════════════════════════════════════════════
```

## Qué Hacer si Hay Errores

### Error: Columna 'pedido_produccion_id' NO existe
- Ejecutar la migración: `php artisan migrate`

### Error: Columna 'tabla_original_pedido' aún existe
- Verificar que la migración se ejecutó correctamente

### Error: Relación no devuelve pedido
- Verificar que el modelo `MaterialesOrdenInsumos` tiene la relación correcta

### Error: Algunos materiales no tienen pedido_produccion_id
- Ejecutar la migración nuevamente
- Verificar que los datos se migraron correctamente

## Archivos Relacionados

- `test_insumos.php` - Script de test
- `test_insumos.bat` - Script batch para ejecutar el test
- `app/Http/Controllers/Insumos/InsumosController.php` - Controlador actualizado
- `app/Models/MaterialesOrdenInsumos.php` - Modelo actualizado
- `database/migrations/2025_11_29_000001_migrate_materiales_to_pedidos_produccion.php` - Migración

## Notas

- El test es de solo lectura, no modifica la BD
- Puedes ejecutarlo múltiples veces sin problemas
- Si hay errores, revisa los logs en `storage/logs/`
