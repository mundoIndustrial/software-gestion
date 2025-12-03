# 🔧 CAMBIOS NECESARIOS - ELIMINAR REFERENCIAS A TABLA_ORIGINAL

**Fecha:** Diciembre 3, 2025  
**Objetivo:** Eliminar todas las referencias a `TablaOriginal` ya que la tabla fue eliminada  
**Nota:** Se mantiene `TablaOriginalBodega` que es una tabla separada

---

## 📍 UBICACIONES ENCONTRADAS

### 1. **RegistroOrdenController.php** (7 referencias)

**Ubicación:** `app/Http/Controllers/RegistroOrdenController.php`

#### Línea 13: Import
```php
// ❌ ELIMINAR
use App\Models\TablaOriginal;
```

#### Líneas 1764-1784: Método `getOrderImages()`
```php
// ❌ CAMBIAR ESTO:
public function getOrderImages($pedido)
{
    // ... código ...
    
    // Si no está en PedidoProduccion, buscar en TablaOriginal (histórico)
    $orden = TablaOriginal::where('pedido', $pedido)->first();
    
    if ($orden && $orden->imagen) {
        // Si es JSON (array de URLs)
        if (is_string($orden->imagen)) {
            $imagenes = json_decode($orden->imagen, true);
        }
    }
}

// ✅ POR ESTO:
public function getOrderImages($pedido)
{
    try {
        // Solo buscar en PedidoProduccion (ya no hay tabla_original)
        $orden = PedidoProduccion::where('numero_pedido', $pedido)->first();
        
        if (!$orden) {
            return response()->json(['imagenes' => []]);
        }
        
        // Obtener imágenes desde prendas_pedido
        $imagenes = [];
        foreach ($orden->prendas as $prenda) {
            if ($prenda->imagen) {
                $imagenes[] = $prenda->imagen;
            }
        }
        
        return response()->json(['imagenes' => $imagenes]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}
```

#### Líneas 1870-1874: Método `getProcesosTablaOriginal()`
```php
// ❌ CAMBIAR ESTO:
public function getProcesosTablaOriginal($numeroPedido)
{
    try {
        // Buscar la orden en tabla_original
        $orden = TablaOriginal::where('pedido', $numeroPedido)->firstOrFail();
        
        // ... resto del código ...
    }
}

// ✅ POR ESTO:
public function getProcesosTablaOriginal($numeroPedido)
{
    try {
        // Buscar la orden en pedidos_produccion
        $orden = PedidoProduccion::where('numero_pedido', $numeroPedido)->firstOrFail();
        
        // ... resto del código ...
    }
}
```

#### Línea 1921: Log
```php
// ❌ CAMBIAR ESTO:
\Log::error('Error en getProcesosTablaOriginal: ' . $e->getMessage());

// ✅ POR ESTO:
\Log::error('Error al obtener procesos de orden: ' . $e->getMessage());
```

---

### 2. **AppServiceProvider.php** (9 referencias)

**Ubicación:** `app/Providers/AppServiceProvider.php`

#### Línea 6: Import
```php
// ❌ CAMBIAR ESTO:
use App\Models\TablaOriginal;

// ✅ POR ESTO:
// Eliminar completamente - no se necesita
```

#### Líneas 28-35: Comentario y Observer
```php
// ❌ CAMBIAR ESTO:
// DESHABILITADOS: Los Observers de TablaOriginal ya no son necesarios
// La sincronización ocurre automáticamente a través de PedidoProduccion
// y sus relaciones con PrendaPedido y ProcesoPrenda.

// Registrar el Observer para TablaOriginal (Pedidos)
// Esto sincroniza automáticamente los cambios en 'descripcion' y 'cliente'
// del padre hacia los registros hijos en 'registros_por_orden'
// TablaOriginal::observe(TablaOriginalObserver::class);

// ✅ POR ESTO:
// Los Observers de TablaOriginal han sido eliminados
// La sincronización ocurre automáticamente a través de PedidoProduccion
// y sus relaciones con PrendaPedido y ProcesoPrenda.
```

---

### 3. **VistasController.php** (6 referencias)

**Ubicación:** `app/Http/Controllers/VistasController.php`

#### Línea 8: Import
```php
// ❌ CAMBIAR ESTO:
use App\Models\TablaOriginal;

// ✅ POR ESTO:
// Eliminar completamente - no se necesita
```

**Nota:** El resto del archivo ya está usando `PrendaPedido` correctamente, no necesita cambios.

---

### 4. **TablaOriginalObserver.php** (7 referencias)

**Ubicación:** `app/Observers/TablaOriginalObserver.php`

#### Acción: ELIMINAR COMPLETAMENTE
```bash
rm app/Observers/TablaOriginalObserver.php
```

**Razón:** Ya no se usa porque `tabla_original` fue eliminada.

---

### 5. **TablaOriginal.php Model** (3 referencias)

**Ubicación:** `app/Models/TablaOriginal.php`

#### Acción: ELIMINAR COMPLETAMENTE
```bash
rm app/Models/TablaOriginal.php
```

**Razón:** Ya no se usa porque `tabla_original` fue eliminada.

---

### 6. **ProductoPedido.php** (1 referencia)

**Ubicación:** `app/Models/ProductoPedido.php`

#### Línea: Import o relación
```php
// ❌ BUSCAR Y ELIMINAR:
use App\Models\TablaOriginal;
// o
$this->belongsTo(TablaOriginal::class, 'pedido', 'pedido');

// ✅ REEMPLAZAR POR:
use App\Models\PedidoProduccion;
// o
$this->belongsTo(PedidoProduccion::class, 'pedido_id', 'id');
```

---

### 7. **Archivos de Backup** (Eliminar)

```bash
# Eliminar archivos de backup
rm app/Http/Controllers/RegistroBodegaController.php.backup
rm app/Http/Controllers/RegistroBodegaController.php.yus8
```

---

### 8. **Console Command** (Opcional)

**Ubicación:** `app/Console/Commands/MigrateTablaOriginalCompleto.php`

#### Acción: ELIMINAR O DESHABILITAR
```bash
# Opción 1: Eliminar
rm app/Console/Commands/MigrateTablaOriginalCompleto.php

# Opción 2: Deshabilitar (si quieres mantenerlo como histórico)
# Cambiar nombre a: MigrateTablaOriginalCompleto.php.disabled
```

---

## 📋 RESUMEN DE CAMBIOS

| Archivo | Acción | Líneas | Prioridad |
|---------|--------|--------|-----------|
| RegistroOrdenController.php | Actualizar | 13, 1764-1784, 1870-1874, 1921 | 🔴 CRÍTICA |
| AppServiceProvider.php | Actualizar | 6, 28-35 | 🟡 IMPORTANTE |
| VistasController.php | Actualizar | 8 | 🟡 IMPORTANTE |
| TablaOriginalObserver.php | Eliminar | - | 🟢 BAJA |
| TablaOriginal.php | Eliminar | - | 🟢 BAJA |
| ProductoPedido.php | Revisar | - | 🟡 IMPORTANTE |
| Backup files | Eliminar | - | 🟢 BAJA |
| MigrateTablaOriginalCompleto.php | Eliminar | - | 🟢 BAJA |

---

## 🚀 PASOS DE EJECUCIÓN

### Paso 1: Actualizar RegistroOrdenController.php
```bash
# 1. Eliminar import
# 2. Actualizar método getOrderImages()
# 3. Actualizar método getProcesosTablaOriginal()
# 4. Actualizar logs
```

### Paso 2: Actualizar AppServiceProvider.php
```bash
# 1. Eliminar import de TablaOriginal
# 2. Actualizar comentarios
# 3. Eliminar línea de observe()
```

### Paso 3: Actualizar VistasController.php
```bash
# 1. Eliminar import de TablaOriginal
```

### Paso 4: Eliminar Archivos Obsoletos
```bash
# 1. Eliminar TablaOriginalObserver.php
# 2. Eliminar TablaOriginal.php
# 3. Eliminar archivos .backup y .yus8
# 4. Eliminar MigrateTablaOriginalCompleto.php (opcional)
```

### Paso 5: Verificar ProductoPedido.php
```bash
# 1. Revisar si tiene referencias a TablaOriginal
# 2. Actualizar si es necesario
```

### Paso 6: Limpiar Autoload
```bash
composer dump-autoload
```

### Paso 7: Ejecutar Tests
```bash
php artisan test
```

### Paso 8: Verificar en Navegador
```bash
# 1. Abrir /orders
# 2. Abrir /vistas
# 3. Abrir /entregas
# 4. Verificar que todo funciona
```

---

## ✅ VERIFICACIÓN

Después de hacer los cambios, ejecutar:

```bash
# 1. Buscar referencias restantes
grep -r "TablaOriginal" app/ --exclude-dir=node_modules

# 2. Buscar referencias a tabla_original
grep -r "tabla_original" app/ --exclude-dir=node_modules

# 3. Ejecutar tests
php artisan test

# 4. Limpiar caché
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

Si no hay resultados, ¡los cambios están completos!

---

## 📝 NOTAS IMPORTANTES

1. **TablaOriginalBodega se mantiene:** No eliminar `TablaOriginalBodega` ni `TablaOriginalBodegaObserver`
2. **Backup de BD:** Hacer backup antes de hacer cambios
3. **Git:** Hacer commit después de cada cambio
4. **Testing:** Ejecutar tests después de cada cambio
5. **Rollback:** Si algo falla, usar `git revert`

