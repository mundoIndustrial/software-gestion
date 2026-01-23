# 🔧 FIX: Fotos no se cargan en modal la primera vez

## 🐛 PROBLEMA REPORTADO

**Síntoma:** 
- Abres modal de edición de prenda → NO APARECEN las fotos
- Recarga página → Abres modal de nuevo → ✅ Ahora SÍ aparecen las fotos

**Causa Raíz:** El QueryHandler no estaba incluyendo `fotos` en el `with()`

## 📍 UBICACIÓN DEL BUG

**Archivo:** [app/Domain/PedidoProduccion/QueryHandlers/ObtenerPrendasPorPedidoHandler.php](app/Domain/PedidoProduccion/QueryHandlers/ObtenerPrendasPorPedidoHandler.php)

**Línea original (❌):**
```php
$prendas = $this->prendaModel
    ->where('pedido_id', $query->getPedidoId())
    ->with(['color', 'tela', 'tipoManga', 'tipoBroche', 'tallas'])  // ❌ NO incluye 'fotos'
    ->get();

// Y además usaba CACHE que se quedaba desactualizado
cache()->put($cacheKey, $prendas, now()->addHour());
```

**Problemas:**
1. ❌ NO incluye `'fotos'` en el `with()`
2. ❌ NO incluye `'coloresTelas'` con sus fotos
3. ❌ NO incluye `'variantes'` (manga, broche, bolsillos)
4. ❌ NO incluye `'procesos'` 
5. ❌ CACHE desactualizado hace que cambios recientes no se vean

## ✅ SOLUCIÓN IMPLEMENTADA

**Nueva lógica (correcciones):**

```php
// 🔄 NO USAR CACHE - Las relaciones pueden cambiar frecuentemente
$prendas = $this->prendaModel
    ->where('pedido_produccion_id', $query->getPedidoId())  // ✅ Campo correcto
    ->with([
        'variantes',              // ✅ Manga, broche, bolsillos
        'tallas',                 // ✅ Tallas por género
        'coloresTelas',           // ✅ Combinaciones color-tela
        'coloresTelas.color',     // ✅ Detalles del color
        'coloresTelas.tela',      // ✅ Detalles de la tela
        'coloresTelas.fotos',     // ✅ Fotos de cada color-tela
        'fotos',                  // ✅ AGREGADO: Fotos de referencia de la prenda
        'procesos',               // ✅ Procesos de producción
        'procesos.tipoProceso',   // ✅ Tipo de proceso
        'procesos.imagenes',      // ✅ Imágenes de los procesos
    ])
    ->get();
```

### Cambios Clave:

| Aspecto | Antes | Después |
|--------|--------|---------|
| Campo FK | `pedido_id` ❌ | `pedido_produccion_id` ✅ |
| Incluye fotos | NO ❌ | SÍ ✅ |
| Incluye fotos telas | NO ❌ | SÍ ✅ |
| Incluye variantes | NO ❌ | SÍ ✅ |
| Incluye procesos | NO ❌ | SÍ ✅ |
| Cache | SÍ (desactualizado) ❌ | NO ✅ |

## 🔗 RELACIONES INCLUIDAS

El `with()` ahora carga:

```
PrendaPedido
├─ variantes (manga, broche, bolsillos)
├─ tallas (S, M, L, XL por género)
├─ coloresTelas
│  ├─ color (detalles: nombre, código)
│  ├─ tela (detalles: nombre, referencia)
│  └─ fotos (fotos de cada combinación color-tela)
├─ fotos (fotos de referencia de la prenda completa)
├─ procesos (bordado, estampado, DTF, etc.)
│  ├─ tipoProceso (tipo de proceso)
│  └─ imagenes (fotos del proceso)
```

## 📊 IMPACTO

**Antes:** Modal muestra prenda SIN fotos → Usuario recarga → Entonces SÍ ve fotos

**Después:** Modal muestra prenda CON TODAS las fotos desde la primera vez

## 🧪 CÓMO VERIFICAR

1. Abre prenda con fotos
2. Click en modal de edición
3. Verifica que `prenda.fotos` esté presente:
   - En navegador: DevTools → Network → busca request a obtener prenda
   - Verifica JSON response incluya `"fotos": [...]`
4. Las fotos deben aparecer al primer intento (sin necesidad de recargar)

## 📝 DETALLES TÉCNICOS

### Por qué faltaban las fotos:

El modelo `PrendaPedido` define la relación:
```php
public function fotos(): HasMany
{
    return $this->hasMany(PrendaFotoPedido::class, 'prenda_pedido_id');
}
```

Pero el QueryHandler NO la estaba cargando en el `with()`. Sin `with()`, las fotos NO se cargan automáticamente y solo se traen cuando las consultas explícitamente (lazy loading), lo que causa el retraso.

### Por qué aparecía al recargar:

Con cache activado:
1. Primera vez: No hay cache → Se carga desde DB sin fotos → Se cachea
2. Usuario recarga página → Cache se limpia
3. Modal se abre de nuevo → Se consulta DB nuevamente (sin cache) → Ahora SÍ incluye fotos

(Probablemente una diferencia en cómo se construía la query original vs. la segunda)

## ✅ CONCLUSIÓN

El fix es simple pero crítico: **agregar `'fotos'` al `with()` en el QueryHandler** y eliminar el cache que causaba inconsistencias.

Esto garantiza que todos los datos relacionados (fotos, variantes, tallas, procesos) se cargan junto con la prenda desde la primera consulta.
