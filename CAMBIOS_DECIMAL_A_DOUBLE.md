# Cambios de DECIMAL a DOUBLE en el Sistema de Balanceo

## Fecha: 2025-11-04

## Resumen
Se cambió el tipo de dato de todos los campos numéricos de `DECIMAL` a `DOUBLE` para mejorar la precisión y evitar problemas de redondeo.

## Archivos Modificados

### 1. Migraciones

#### `database/migrations/2025_10_30_202501_create_balanceos_table.php`
- ✅ `horas_por_turno`: `decimal(5,2)` → `double`
- ✅ `tiempo_disponible_horas`: `decimal(8,2)` → `double`
- ✅ `tiempo_disponible_segundos`: `decimal(10,2)` → `double`
- ✅ `sam_total`: `decimal(10,2)` → `double`
- ✅ `tiempo_cuello_botella`: `decimal(10,2)` → `double`
- ✅ `sam_real`: `decimal(10,2)` → `double`

#### `database/migrations/2025_10_30_202502_create_operaciones_balanceo_table.php`
- ✅ `sam`: `decimal(10,2)` → `double`

#### `database/migrations/2025_11_04_094400_change_decimal_to_double_in_balanceos.php` (NUEVA)
- ✅ Migración para modificar las columnas existentes en la base de datos
- ✅ Ejecutada correctamente

### 2. Modelos

#### `app/Models/Balanceo.php`
Cambios en `$casts`:
- ✅ `horas_por_turno`: `decimal:2` → `double`
- ✅ `tiempo_disponible_horas`: `decimal:2` → `double`
- ✅ `tiempo_disponible_segundos`: `decimal:2` → `double`
- ✅ `sam_total`: `decimal:2` → `double`
- ✅ `tiempo_cuello_botella`: `decimal:2` → `double`
- ✅ `sam_real`: `decimal:2` → `double`

#### `app/Models/OperacionBalanceo.php`
Cambios en `$casts`:
- ✅ `sam`: `decimal:2` → `double`

### 3. Vistas

#### `resources/views/balanceo/index.blade.php`
- ✅ `number_format($prenda->balanceoActivo->sam_total, 2)` → `number_format($prenda->balanceoActivo->sam_total, 1)`

#### `resources/views/balanceo/partials/tabla-operaciones.blade.php`
- ✅ `.toFixed(2)` → `.toFixed(1)` en el total de SAM

## Ventajas del Cambio

1. **Mayor Precisión**: `DOUBLE` permite mayor precisión en los cálculos sin limitaciones de decimales fijos
2. **Consistencia**: Todos los valores numéricos ahora usan el mismo tipo de dato
3. **Compatibilidad con Excel**: Los valores coinciden mejor con los cálculos de Excel
4. **Flexibilidad**: No hay restricción de 2 decimales, permitiendo valores más precisos cuando sea necesario

## Formato de Visualización

- **Backend (PHP)**: `number_format($valor, 1)` - 1 decimal
- **Frontend (JavaScript)**: `.toFixed(1)` - 1 decimal
- **SAM individual**: `.toFixed(2)` - 2 decimales para mayor precisión

## Comandos Ejecutados

```bash
php artisan migrate
```

## Notas Importantes

- ✅ La migración se ejecutó correctamente sin errores
- ✅ Los datos existentes se preservaron durante la conversión
- ✅ No se requiere rollback de datos
- ✅ El sistema ahora muestra valores consistentes entre frontend, backend y Excel

## Próximos Pasos

Si necesitas revertir los cambios:
```bash
php artisan migrate:rollback --step=1
```

Esto revertirá solo la última migración (cambio de double a decimal).
