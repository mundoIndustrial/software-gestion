#  FIX: Rutas API de Catálogos Reubicadas

## 🔴 Problema Inicial

```
GET http://desktop-8un1ehm:8000/api/tallas-disponibles 404 (Not Found)
```

## 🔍 Análisis

Las rutas estaban **dentro del grupo de asesores con prefijo `/asesores`**, entonces la URL real era:
```
/asesores/api/tallas-disponibles  ← Esto SÍ existía
/api/tallas-disponibles           ← Esto NO existía (404)
```

Pero el JavaScript llamaba a `/api/tallas-disponibles` sin el prefijo `/asesores/`.

##  Solución Implementada

Moví las 4 rutas de catálogos a un **grupo API separado** con `prefix('api')`:

```php
Route::middleware(['auth', 'role:asesor,admin'])
     ->prefix('api')  // ← Sin 'asesores', solo 'api'
     ->name('api.')
     ->group(function () {
        Route::get('/tallas-disponibles', ...);
        Route::get('/prenda-pedido/{prendaId}/tallas', ...);
        Route::get('/prenda-pedido/{prendaId}/variantes', ...);
        Route::get('/prenda-pedido/{prendaId}/colores-telas', ...);
    });
```

**Resultado**: Ahora la URL es:
```
 GET /api/tallas-disponibles           (FUNCIONA)
 GET /api/prenda-pedido/123/tallas     (FUNCIONA)
 GET /api/prenda-pedido/123/variantes  (FUNCIONA)
 GET /api/prenda-pedido/123/colores-telas (FUNCIONA)
```

## 📍 Ubicación en Código

**Archivo**: `routes/web.php`  
**Líneas**: 587-595  
**Grupo**: Independiente, después del grupo "asesores"  
**Middleware**: `auth` + rol `asesor,admin` (mantiene seguridad)

## 🔐 Seguridad

-  Mantiene autenticación (`auth`)
-  Mantiene validación de rol (`role:asesor,admin`)
-  Solo asesores y admins pueden acceder

## 📝 Cambios

```diff
- Rutas DENTRO del grupo /asesores prefix
+ Rutas FUERA del grupo /asesores, en su propio grupo /api prefix
```

## 🧪 Verificación

Ejecutar en navegador:
```javascript
// DevTools Console
fetch('/api/tallas-disponibles')
  .then(r => r.json())
  .then(d => console.log(' Tallas cargadas:', d))
  .catch(e => console.error('❌ Error:', e))
```

**Resultado esperado**:
```json
{
  "success": true,
  "data": {
    "DAMA": ["XS", "S", "M", ...],
    "CABALLERO": ["28", "30", "32", ...]
  }
}
```

## 📊 Commit Realizado

```
FIX: Mover rutas API de catálogos fuera del grupo asesores para que sean accesibles
```

---

**Status**:  Rutas ahora son accesibles desde JavaScript sin el prefijo `/asesores/`

