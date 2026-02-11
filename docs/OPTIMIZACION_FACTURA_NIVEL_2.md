# Optimizaciones Adicionales para Factura - Nivel 2

##  Próximas Mejoras por Impacto

| # | Optimización | Impacto | Dificultad | Tiempo | Estado |
|---|--|--|--|--|--|
| 1 | Eliminar arrays vacíos en JSON | ⚡⚡⚡ | 🟢 Fácil | 20 min | 📋 Pendiente |
| 2 | Caching con Redis | ⚡⚡⚡ | 🟡 Media | 45 min | 📋 Pendiente |
| 3 | Lazy loading de imágenes | ⚡⚡ | 🟡 Media | 1h | 📋 Pendiente |
| 4 | Pagination de prendas | ⚡⚡⚡ | 🟠 Complejo | 2h | 📋 Pendiente |
| 5 | Usar select() para columnas | ⚡⚡ | 🟢 Fácil | 30 min | 📋 Pendiente |
| 6 | Endpoint de metadata | ⚡ | 🟢 Fácil | 20 min | 📋 Pendiente |
| 7 | Comprensión gzip | ⚡⚡ | 🟢 Fácil | 15 min | 📋 Pendiente |

---

## 1️⃣ Eliminar Arrays Vacíos en JSON ⚡⚡⚡

### Problema Actual:
```json
{
  "telas_array": [
    {
      "fotos": [],           ← Ocupa espacio
      "fotos_tela": [],      ← Innecesario
      "imagenes": []         ← Duplicado
    }
  ]
}
```

### Solución Optimizada:
```json
{
  "telas_array": [
    {
      // Solo incluir si tiene contenido
    }
  ]
}
```

### Implementación (en PedidoProduccionRepository.php):
```php
// ANTES: 
$telaItem = [
    'fotos' => $fotosColorTela,
    'fotos_tela' => $fotosColorTela,
    'imagenes' => $fotosColorTela,
];

// DESPUÉS:
$telaItem = [];
if (!empty($fotosColorTela)) {
    $telaItem['fotos'] = $fotosColorTela;
    // Omitir duplicados fotos_tela e imagenes
}
```

### Beneficio:
- **Tamaño JSON:** -30% a -50% en muchos casos
- **Tiempo Parsing:** -10-15%
- **Tiempo Transfer:** -30-50%

---

## 2️⃣ Caching con Redis ⚡⚡⚡

### Patrón:
```php
// En PedidosController.php - obtenerDatosFacturaJSON()
public function obtenerDatosFacturaJSON($id)
{
    $cacheKey = "factura:pedido:{$id}:v1";
    
    // Intentar obtener del cache
    $datos = Cache::remember($cacheKey, now()->addMinutes(30), function() use ($id) {
        $reciboPrenda = ReciboPrenda::find($id);
        $pedido = PedidoProduccion::where('numero_pedido', $reciboPrenda->numero_pedido)->first();
        return $this->pedidoRepository->obtenerDatosFactura($pedido->id);
    });
    
    return response()->json($datos);
}
```

### Beneficio:
- **Segunda solicitud:** 50-100ms (vs 1000-2000ms)
- **Carga de servidor:** -50% en horas pico

### Invalidación (en actualizaciones):
```php
// Cuando se actualiza un pedido
event(new PedidoActualizado($pedido)); // Dispara evento

// En evento:
Cache::forget("factura:pedido:{$pedido->id}:v1");
```

---

## 3️⃣ Lazy Loading de Imágenes ⚡⚡

### Problema:
Todas las imágenes se cargan en la respuesta inicial aunque el frontend las carga lentamente.

### Solución:
```php
// Nuevo parámetro opcional: ?include_images=true
public function obtenerDatosFacturaJSON($id, Request $request)
{
    $incluirImagenes = $request->boolean('include_images', false);
    
    $datos = $this->pedidoRepository->obtenerDatosFactura($pedido->id);
    
    if (!$incluirImagenes) {
        // Remover todas las imágenes
        foreach ($datos['prendas'] as &$prenda) {
            $prenda['imagenes'] = [];
            $prenda['imagenes_tela'] = [];
            // Mantener solo count de imágenes
            $prenda['imagenes_count'] = count($prenda['imagenes'] ?? []);
        }
        foreach ($datos['epps'] as &$epp) {
            $epp['imagenes'] = [];
            $epp['imagenes_count'] = count($epp['imagenes'] ?? []);
        }
    }
    
    return response()->json($datos);
}
```

### Frontend:
```javascript
// Primera carga: sin imágenes
const response = await fetch(`/gestion-bodega/pedidos/4/factura-datos?include_images=false`);

// Luego al hacer scroll: cargar imágenes
const imagenesResponse = await fetch(`/gestion-bodega/pedidos/4/imagenes`);
```

### Beneficio:
- **Tamaño inicial:** -60% a -80%
- **Tiempo respuesta:** -50-70%

---

## 4️⃣ Pagination de Prendas ⚡⚡⚡

Si un pedido tiene 30+ prendas, devolver solo 5-10 por página:

```php
public function obtenerDatosFacturaJSON($id, Request $request)
{
    $perPage = (int)$request->get('per_page', 10);
    $page = (int)$request->get('page', 1);
    
    $datos = $this->pedidoRepository->obtenerDatosFactura($pedido->id);
    
    // Paginar prendas
    $prendas = collect($datos['prendas']);
    $paginadas = $prendas->slice(($page-1)*$perPage, $perPage)->values();
    
    return response()->json([
        ...$datos,
        'prendas' => $paginadas,
        'prendas_total' => $prendas->count(),
        'prendas_pagina' => $page,
        'prendas_por_pagina' => $perPage,
    ]);
}
```

### Beneficio:
- **Primera página:** -70% tamaño
- **Tiempo respuesta:** -60-80%

---

## 5️⃣ Usar select() para Columnas Específicas ⚡⚡

Remover columnas innecesarias desde la BD:

```php
// Modificar en obtenerPorId()
public function obtenerPorId(int $id): ?PedidoProduccion
{
    return PedidoProduccion::with([
        'prendas' => function($q) {
            $q->select('id', 'pedido_produccion_id', 'nombre_prenda', 'descripcion', 'de_bodega'); // Solo estos
        },
        'prendas.coloresTelas' => function($q) {
            $q->select('id', 'prenda_pedido_id', 'tela_id', 'color_id', 'referencia'); // Sin fotos aquí
        },
    ])->find($id);
}
```

### Beneficio:
- **Tamaño datos BD:** -20-30%
- **Tiempo transferencia:** -10-20%

---

## 6️⃣ Endpoint de Metadata ⚡

Para casos donde solo necesitan información básica sin detalles:

```php
Route::get('/pedidos/{id}/factura-metadata', [PedidosController::class, 'metadataFactura'])
    ->name('factura-metadata');

public function metadataFactura($id)
{
    $recibo = ReciboPrenda::find($id);
    $pedido = PedidoProduccion::where('numero_pedido', $recibo->numero_pedido)->first();
    
    return response()->json([
        'numero_pedido' => $pedido->numero_pedido,
        'cliente' => $pedido->cliente,
        'prendas_count' => $pedido->prendas->count(),
        'epps_count' => $pedido->epps->count(),
        'total_items' => ...,
        // Sin detalles de prendas/EPPs
    ]);
}
```

### Beneficio:
- **Respuesta instantánea:** <50ms
- **Para validaciones rápidas**

---

## 7️⃣ Comprensión gzip (Automático) ⚡⚡

Laravel ya soporta gzip, pero verificar que está activado:

```bash
# En .htaccess o nginx.conf:
# Apache:
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE application/json text/html text/xml
</IfModule>

# Nginx:
gzip on;
gzip_types application/json;
gzip_min_length 1000;
```

### Beneficio:
- **Tamaño transferencia:** -60% a -80%
- **Automático para navegadores**

---

## 📈 Resumen de Optimizaciones

### Implementadas (Fase 1) 
```
- Eliminadas queries N+1
- Reducido logging excesivo
- Relaciones precargadas
```

### Recomendadas a Continuación (Fase 2) 📋

**Fáciles (30 min):**
1. Eliminar arrays vacíos en JSON
2. Comprensión gzip
3. Endpoint de metadata

**Medianas (1h):**
4. Caching con Redis
5. Lazy loading de imágenes

**Complejas (2h):**
6. Pagination de prendas
7. Optimize select() queries

---

##  Plan de Implementación Recomendado

### Día 1 (Hoy):
```
 Fase 1: Ya completada (N+1 fixes + logging)
- Ganancia esperada: 7-14x más rápido
```

### Día 2-3 (Si necesarias más mejoras):
```
⏳ Fase 2a: Fáciles (30 min)
1. Eliminar arrays vacíos
2. Gzip (verificar)
- Ganancia estimada: +20-30% más rápido
```

### Día 4-5 (Si sigue siendo lento):
```
⏳ Fase 2b: Medianas (1-2h)
1. Redis Caching
2. Lazy loading
- Ganancia estimada: +30-50% más rápido
```

### Semana 2 (Optimización final):
```
⏳ Fase 2c: Avanzadas (2h+)
1. Pagination
2. Select() specifics
- Ganancia estimada: +50-80% + escalabilidad
```

---

##  Cómo Medir la Mejora

### Antes de cambios:
```bash
time curl -w "\nTiempo: %{time_total}s\nTamaño: %{size_download} bytes" \
  http://localhost:8000/gestion-bodega/pedidos/4/factura-datos
```

### Después:
- Comparar tiempos
- Comparar tamaños de respuesta
- Usar Chrome DevTools → Network

### Benchmark Script:
```php
php artisan tinker
include('test-factura-performance.php')

// Ver duración en logs:
// [FACTURA] obtenerDatosFactura completado {"duracion_ms": 1250}
```

---

##  Consideraciones Importantes

### Caching:
- Invalidar cuando se actualiza pedido
- TTL de 30 min es seguro
- redis debe estar disponible

### Lazy Loading:
- Frontend debe soportar segunda request
- Mostrar skeleton/spinner mientras carga

### Pagination:
- Frontend debe paginar también
- Mantener estado de página

### Select():
- Verificar que frontend no necesita otros campos
- Testear bien antes de mergear

---

## 📝 Próximos Pasos

1. **Medir estado actual:**
   ```bash
   curl -i http://localhost:8000/gestion-bodega/pedidos/4/factura-datos
   ```
   Anotarb: Tiempo + Tamaño (Content-Length)

2. **Decidir cuál implementar:**
   - Si el tiempo es lo crítico → Redis Caching
   - Si es tamaño → Arrays vacíos + Gzip
   - Si hay muchos pedidos grandes → Pagination

3. **Implementar una por una:**
   - Hacer cambio
   - Testear
   - Medir beneficio
   - Documentar

4. **Crear alertas:**
   - Si response > 2MB → advertencia
   - Si tiempo > 1s → warning en logs

---

Avísame cuál prefieres implementar primero, y te ayudo con los detalles.
