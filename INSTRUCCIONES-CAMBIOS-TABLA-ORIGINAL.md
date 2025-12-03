# 📝 INSTRUCCIONES PASO A PASO - ELIMINAR REFERENCIAS A TABLA_ORIGINAL

---

## ✅ CAMBIO 1: RegistroOrdenController.php

### 1.1 Eliminar Import (Línea 13)

**Ubicación:** `app/Http/Controllers/RegistroOrdenController.php` línea 13

**Cambiar de:**
```php
use App\Models\TablaOriginal;
```

**A:**
```php
// Eliminado - tabla_original fue eliminada
```

---

### 1.2 Actualizar Método getOrderImages() (Líneas 1764-1784)

**Ubicación:** `app/Http/Controllers/RegistroOrdenController.php` líneas 1764-1784

**Cambiar de:**
```php
/**
 * GET /registros/{pedido}/images
 * 
 * Busca primero en PedidoProduccion (nuevos pedidos)
 * Si no encuentra, busca en TablaOriginal (histórico)
 */
public function getOrderImages($pedido)
{
    // ... código ...
    
    // Si no está en PedidoProduccion, buscar en TablaOriginal (histórico)
    $orden = TablaOriginal::where('pedido', $pedido)->first();
    
    if ($orden && $orden->imagen) {
        // Si es JSON (array de URLs)
        // ... más código ...
    }
}
```

**A:**
```php
/**
 * GET /registros/{pedido}/images
 * 
 * Busca en PedidoProduccion y sus relaciones
 */
public function getOrderImages($pedido)
{
    try {
        // Solo buscar en PedidoProduccion (tabla_original fue eliminada)
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
        \Log::error('Error al obtener imágenes de orden: ' . $e->getMessage());
        return response()->json(['error' => $e->getMessage()], 500);
    }
}
```

---

### 1.3 Actualizar Método getProcesosTablaOriginal() (Líneas 1870-1874)

**Ubicación:** `app/Http/Controllers/RegistroOrdenController.php` líneas 1870-1874

**Cambiar de:**
```php
/**
 * API: Obtener procesos de una orden desde tabla_original (para bodega tracking)
 * Busca en procesos_prenda usando el número de pedido
 */
public function getProcesosTablaOriginal($numeroPedido)
{
    try {
        // Buscar la orden en tabla_original
        $orden = TablaOriginal::where('pedido', $numeroPedido)->firstOrFail();
        
        // ... resto del código ...
    }
}
```

**A:**
```php
/**
 * API: Obtener procesos de una orden (para bodega tracking)
 * Busca en procesos_prenda usando el número de pedido
 */
public function getProcesosTablaOriginal($numeroPedido)
{
    try {
        // Buscar la orden en pedidos_produccion
        $orden = PedidoProduccion::where('numero_pedido', $numeroPedido)->firstOrFail();
        
        // ... resto del código ...
    }
}
```

---

### 1.4 Actualizar Log (Línea 1921)

**Ubicación:** `app/Http/Controllers/RegistroOrdenController.php` línea 1921

**Cambiar de:**
```php
\Log::error('Error en getProcesosTablaOriginal: ' . $e->getMessage());
```

**A:**
```php
\Log::error('Error al obtener procesos de orden: ' . $e->getMessage());
```

---

## ✅ CAMBIO 2: AppServiceProvider.php

### 2.1 Eliminar Import (Línea 6)

**Ubicación:** `app/Providers/AppServiceProvider.php` línea 6

**Cambiar de:**
```php
use App\Models\TablaOriginal;
use App\Models\TablaOriginalBodega;
use App\Models\ProcesoPrenda;
use App\Observers\TablaOriginalObserver;
use App\Observers\TablaOriginalBodegaObserver;
use App\Observers\ProcesoPrendaObserver;
```

**A:**
```php
use App\Models\TablaOriginalBodega;
use App\Models\ProcesoPrenda;
use App\Observers\TablaOriginalBodegaObserver;
use App\Observers\ProcesoPrendaObserver;
```

---

### 2.2 Actualizar Comentarios (Líneas 28-35)

**Ubicación:** `app/Providers/AppServiceProvider.php` líneas 28-35

**Cambiar de:**
```php
// DESHABILITADOS: Los Observers de TablaOriginal ya no son necesarios
// La sincronización ocurre automáticamente a través de PedidoProduccion
// y sus relaciones con PrendaPedido y ProcesoPrenda.

// Registrar el Observer para TablaOriginal (Pedidos)
// Esto sincroniza automáticamente los cambios en 'descripcion' y 'cliente'
// del padre hacia los registros hijos en 'registros_por_orden'
// TablaOriginal::observe(TablaOriginalObserver::class);

// Registrar el Observer para TablaOriginalBodega (Bodega)
// Esto sincroniza automáticamente los cambios en 'descripcion' y 'cliente'
// del padre hacia los registros hijos en 'registros_por_orden_bodega'
// TablaOriginalBodega::observe(TablaOriginalBodegaObserver::class);
```

**A:**
```php
// Los Observers de TablaOriginal han sido eliminados
// La sincronización ocurre automáticamente a través de PedidoProduccion
// y sus relaciones con PrendaPedido y ProcesoPrenda.

// Registrar el Observer para TablaOriginalBodega (Bodega)
// Esto sincroniza automáticamente los cambios en 'descripcion' y 'cliente'
// del padre hacia los registros hijos en 'registros_por_orden_bodega'
// TablaOriginalBodega::observe(TablaOriginalBodegaObserver::class);
```

---

## ✅ CAMBIO 3: VistasController.php

### 3.1 Eliminar Import (Línea 8)

**Ubicación:** `app/Http/Controllers/VistasController.php` línea 8

**Cambiar de:**
```php
use App\Models\RegistrosPorOrden;
use App\Models\RegistrosPorOrdenBodega;
use App\Models\TablaOriginal;
use App\Models\TablaOriginalBodega;
use App\Models\EntregaPedidoCorte;
use App\Models\EntregaBodegaCorte;
```

**A:**
```php
use App\Models\RegistrosPorOrden;
use App\Models\RegistrosPorOrdenBodega;
use App\Models\TablaOriginalBodega;
use App\Models\EntregaPedidoCorte;
use App\Models\EntregaBodegaCorte;
```

---

## ✅ CAMBIO 4: Eliminar Archivos Obsoletos

### 4.1 Eliminar TablaOriginalObserver.php

```bash
rm app/Observers/TablaOriginalObserver.php
```

**Verificar:** El archivo debe ser eliminado completamente.

---

### 4.2 Eliminar TablaOriginal.php Model

```bash
rm app/Models/TablaOriginal.php
```

**Verificar:** El archivo debe ser eliminado completamente.

---

### 4.3 Eliminar Archivos de Backup

```bash
rm app/Http/Controllers/RegistroBodegaController.php.backup
rm app/Http/Controllers/RegistroBodegaController.php.yus8
```

**Verificar:** Los archivos deben ser eliminados completamente.

---

### 4.4 Eliminar Command (Opcional)

```bash
rm app/Console/Commands/MigrateTablaOriginalCompleto.php
```

**Verificar:** El archivo debe ser eliminado completamente.

---

## ✅ CAMBIO 5: Revisar ProductoPedido.php

### 5.1 Buscar Referencias

```bash
grep -n "TablaOriginal" app/Models/ProductoPedido.php
```

**Si hay resultados:**

**Cambiar de:**
```php
use App\Models\TablaOriginal;

// ... en la clase ...
public function orden()
{
    return $this->belongsTo(TablaOriginal::class, 'pedido', 'pedido');
}
```

**A:**
```php
use App\Models\PedidoProduccion;

// ... en la clase ...
public function orden()
{
    return $this->belongsTo(PedidoProduccion::class, 'pedido_id', 'id');
}
```

---

## ✅ CAMBIO 6: Limpiar Autoload

```bash
composer dump-autoload
```

**Verificar:** No debe haber errores.

---

## ✅ CAMBIO 7: Ejecutar Tests

```bash
php artisan test
```

**Verificar:** Todos los tests deben pasar.

---

## ✅ CAMBIO 8: Limpiar Caché

```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

**Verificar:** No debe haber errores.

---

## ✅ VERIFICACIÓN FINAL

### Paso 1: Buscar Referencias Restantes

```bash
# Buscar referencias a TablaOriginal
grep -r "TablaOriginal" app/ --exclude-dir=node_modules

# Buscar referencias a tabla_original
grep -r "tabla_original" app/ --exclude-dir=node_modules
```

**Resultado esperado:** Sin resultados (excepto en comentarios)

---

### Paso 2: Verificar en Navegador

1. **Abrir `/orders`**
   - Debe mostrar la tabla de órdenes
   - Debe funcionar búsqueda y filtros

2. **Abrir `/vistas`**
   - Debe mostrar las vistas de costura/corte
   - Debe funcionar búsqueda

3. **Abrir `/entregas`**
   - Debe mostrar las entregas
   - Debe funcionar todo correctamente

4. **Abrir DevTools (F12)**
   - No debe haber errores en consola
   - No debe haber errores de red

---

### Paso 3: Hacer Commit

```bash
git add -A
git commit -m "refactor: eliminar referencias a tabla_original

- Eliminar import de TablaOriginal en RegistroOrdenController
- Actualizar método getOrderImages() para usar PedidoProduccion
- Actualizar método getProcesosTablaOriginal() para usar PedidoProduccion
- Eliminar import de TablaOriginal en AppServiceProvider
- Eliminar import de TablaOriginal en VistasController
- Eliminar archivos obsoletos: TablaOriginalObserver, TablaOriginal model
- Eliminar archivos de backup
- Limpiar autoload y caché"
```

---

## 📊 RESUMEN

| Cambio | Archivo | Líneas | Estado |
|--------|---------|--------|--------|
| 1 | RegistroOrdenController.php | 13, 1764-1784, 1870-1874, 1921 | ✅ |
| 2 | AppServiceProvider.php | 6, 28-35 | ✅ |
| 3 | VistasController.php | 8 | ✅ |
| 4.1 | TablaOriginalObserver.php | - | ✅ |
| 4.2 | TablaOriginal.php | - | ✅ |
| 4.3 | Backup files | - | ✅ |
| 4.4 | MigrateTablaOriginalCompleto.php | - | ✅ |
| 5 | ProductoPedido.php | - | ✅ |
| 6 | composer dump-autoload | - | ✅ |
| 7 | php artisan test | - | ✅ |
| 8 | Limpiar caché | - | ✅ |

---

## ⏱️ TIEMPO ESTIMADO

- **Cambios en código:** 15 minutos
- **Eliminar archivos:** 5 minutos
- **Tests y verificación:** 10 minutos
- **Total:** 30 minutos

---

## 🆘 Si Algo Falla

```bash
# Revertir todos los cambios
git revert HEAD

# O restaurar archivos específicos
git checkout app/Http/Controllers/RegistroOrdenController.php
```

