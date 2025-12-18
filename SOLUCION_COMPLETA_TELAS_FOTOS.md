# SOLUCIÓN COMPLETA: Captura y Guardado de Múltiples Telas con Fotos

## Problema Final Identificado
Las imágenes de múltiples telas **no se estaban guardando en la base de datos**, aunque se capturaban correctamente en el formulario.

## Causa Raíz
1. Los archivos de fotos de telas se enviaban desde el frontend
2. Pero **no se procesaban** (no se guardaban en storage)
3. Se pasaban como objetos `File` al servicio en lugar de rutas procesadas
4. El servicio esperaba rutas (`ruta_original`, `ruta_webp`, etc.) no archivos

## Solución Completa

### 1. Frontend - Captura Correcta ✅ (Ya implementado)
- Múltiples filas de telas con índices: `[0]`, `[1]`, `[2]`
- Fotos asociadas a cada tela: `window.telasSeleccionadas[productoId][telaIndex][]`
- FormModule envía estructura correcta: `productos_friendly[0][telas][0][fotos][0]`

### 2. Backend - Nuevo Procesamiento de Fotos ✅

#### A. AsesoresController - Método `procesarFotosTelas()`

**Nuevo flujo en `store()`**:
```php
// ANTES: Los archivos iban directamente al servicio
$pedidoPrendaService->guardarPrendasEnPedido($pedidoBorrador, $validated[$productosKey]);

// DESPUÉS: Se procesan primero
$productosConTelasProcessadas = $this->procesarFotosTelas($request, $validated[$productosKey]);
$pedidoPrendaService->guardarPrendasEnPedido($pedidoBorrador, $productosConTelasProcessadas);
```

**¿Qué hace?**:
1. Itera sobre cada producto y sus telas
2. Obtiene archivos de fotos usando `$request->allFiles()` o `$request->hasFile()`
3. Valida que cada archivo sea válido
4. **Guarda cada foto en storage**: `'telas/pedidos'` directory en `public` disk
5. Obtiene la URL procesada: `Storage::url($rutaGuardada)`
6. Retorna los productos con las fotos convertidas a rutas

#### B. Estructura de Datos Transformada

**ANTES (lo que recibe del formulario)**:
```php
productos_friendly[0][telas][0] = [
    'tela_id' => 1,
    'color_id' => 2,
    'referencia' => 'REF001',
    'fotos' => [
        File (objeto),
        File (objeto),
    ]
]
```

**DESPUÉS (lo que pasa al servicio)**:
```php
productos_friendly[0][telas][0] = [
    'tela_id' => 1,
    'color_id' => 2,
    'referencia' => 'REF001',
    'fotos' => [
        [
            'ruta_original' => '/storage/telas/pedidos/abc123.jpg',
            'ruta_webp' => null,
            'ruta_miniatura' => null,
            'tamaño' => 245632,
        ],
        [
            'ruta_original' => '/storage/telas/pedidos/def456.jpg',
            'ruta_webp' => null,
            'ruta_miniatura' => null,
            'tamaño' => 312451,
        ],
    ]
]
```

### 3. Logging Detallado ✅

Se agregó logging en varios puntos para rastrear el proceso:

```
📁 Archivos recibidos en request: {total_archivos, archivos_keys}
✅ Procesando fotos de tela: {producto_index, tela_index, cantidad_fotos}
✅ Foto de tela guardada en storage: {ruta_guardada, nombre_archivo, tamaño}
✅ Fotos procesadas guardadas en tela: {tela_index, cantidad_fotos}
✅ Procesamiento de fotos de telas completado
```

Esto permite debugging si algo falla.

## Flujo Completo de Guardado

```
Usuario llena formulario con múltiples telas
  ↓
Usuario sube fotos para cada tela
  ↓
Frontend FormModule agrupa fotos por tela:
   productos_friendly[0][telas][0][fotos][0] = File
   productos_friendly[0][telas][0][fotos][1] = File
   productos_friendly[0][telas][1][fotos][0] = File
  ↓
Usuario hace submit
  ↓
AsesoresController::store() recibe request
  ↓
Validación (sin validar archivos)
  ↓
procesarFotosTelas() PROCESA cada foto:
   ├─ Itera productos → telas → fotos
   ├─ Valida archivo
   ├─ Guarda en: /storage/app/public/telas/pedidos/
   ├─ Obtiene URL: /storage/telas/pedidos/abc.jpg
   └─ Agrupa en estructura de rutas
  ↓
Retorna productos con fotos → RUTAS (no Files)
  ↓
PedidoPrendaService::guardarPrendasEnPedido()
  ├─ guardarPrenda() para cada prenda
  └─ guardarFotosTelas() para cada tela
       └─ Itera $tela['fotos'] (ahora son rutas)
            └─ INSERT en prenda_fotos_tela_pedido
  ↓
Base de datos recibe registros:
   INSERT INTO prenda_fotos_tela_pedido (
       prenda_pedido_id, 
       tela_id, 
       color_id, 
       ruta_original,  ← /storage/telas/pedidos/abc.jpg
       tamaño
   )
  ↓
✅ Fotos guardadas exitosamente
```

## Archivos Finales Modificados

### 1. [app/Http/Controllers/AsesoresController.php](app/Http/Controllers/AsesoresController.php)
- ✅ `store()`: Llama a `procesarFotosTelas()`
- ✅ `procesarFotosTelas()`: Nuevo método que procesa archivos

### 2. [app/Application/Services/PedidoPrendaService.php](app/Application/Services/PedidoPrendaService.php)
- ✅ `guardarFotosTelas()`: Sin cambios necesarios (ya funciona con rutas)
- ✅ Recibe datos correctamente procesados del controlador

### 3. Frontend (Ya completado en paso anterior)
- ✅ [resources/views/components/template-producto.blade.php](resources/views/components/template-producto.blade.php)
- ✅ [public/js/asesores/cotizaciones/productos.js](public/js/asesores/cotizaciones/productos.js)
- ✅ [public/js/asesores/cotizaciones/modules/FormModule.js](public/js/asesores/cotizaciones/modules/FormModule.js)

## Base de Datos

Tabla destino: `prenda_fotos_tela_pedido`

```sql
Columns:
- id (PK)
- prenda_pedido_id (FK)
- tela_id (FK, nullable)
- color_id (FK, nullable)
- ruta_original ← SE GUARDA AQUÍ
- ruta_webp (nullable)
- ruta_miniatura (nullable)
- orden
- ancho, alto, tamaño
- observaciones
- timestamps + soft deletes
```

## Testing

1. ✅ Agregar 1 prenda con 1 tela + fotos → Debe guardar
2. ✅ Agregar 1 prenda con 2-3 telas cada una con fotos → Debe guardar todas
3. ✅ Verificar en BD que `prenda_fotos_tela_pedido` tiene los registros correctos
4. ✅ Verificar que los archivos existen en `/storage/app/public/telas/pedidos/`
5. ✅ Verificar que `ruta_original` en BD contiene `/storage/telas/pedidos/...`

## Logs a Verificar

Para debugging, revisar:
```
storage/logs/laravel.log

Buscar por:
- "📁 Archivos recibidos"
- "✅ Procesando fotos de tela"
- "✅ Foto de tela guardada en storage"
- "❌ Error guardando foto" (si hay errores)
```

## Resumen de Solución

| Aspecto | Antes | Después |
|---------|-------|---------|
| **Captura de telas** | Una sola | Múltiples indexadas ✅ |
| **Relación foto-tela** | Sin relación | Asociada por índice ✅ |
| **Procesamiento fotos** | Directo (no procesa) | En controlador ✅ |
| **Almacenamiento** | ❌ No guardaba | ✅ En `/storage/telas/pedidos/` |
| **Base de datos** | Sin registros | ✅ En `prenda_fotos_tela_pedido` |
| **URLs guardadas** | ❌ Ninguna | ✅ `/storage/telas/pedidos/...` |

**Resultado Final**: Múltiples telas con múltiples fotos cada una se capturan, procesan y guardan correctamente ✨
