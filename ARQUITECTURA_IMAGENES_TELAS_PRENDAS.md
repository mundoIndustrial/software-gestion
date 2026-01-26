# 🎨 Arquitectura de Imágenes de Telas en Prendas

## 📋 Resumen Ejecutivo

El sistema permite guardar **múltiples imágenes de telas** para cada prenda en un pedido. Cada tela puede tener:
- Identificadores: `color_id` y `tela_id`
- Múltiples imágenes de referencia (webp optimizadas)
- Información de orden y almacenamiento

## 🏗️ Estructura de Tablas

### 1. **prendas_pedido**
```
id → PK
pedido_produccion_id → FK
nombre_prenda
descripcion
cantidad
...
```

### 2. **prenda_pedido_colores_telas** (RELACIÓN Color-Tela)
```
id → PK
prenda_pedido_id → FK a prendas_pedido
color_id → FK a colores_prenda (del catálogo)
tela_id → FK a telas_prenda (del catálogo)
created_at, updated_at
```

**IMPORTANTE:** Esta tabla SOLO almacena combinaciones de `color_id` + `tela_id`. 
- Los nombres reales vienen de los catálogos (`colores_prenda` y `telas_prenda`)
- Se crean mediante `ColorTelaService::obtenerOCrearColorTela()`

### 3. **prenda_fotos_tela_pedido** (IMÁGENES)
```
id → PK
prenda_pedido_colores_telas_id → FK a prenda_pedido_colores_telas
ruta_original → varchar(255)  [Deprecated]
ruta_webp → varchar(255)      [ACTIVO]
orden → int
created_at, updated_at, deleted_at
```

## 🔄 Flujo de Guardado de Imágenes de Telas

### Paso 1: Datos Llegan desde FormData del Frontend
```javascript
// FormData estructura:
formData.append('prendas[0][telas][0][imagenes][0]', archivoFile)
formData.append('prendas[0][telas][0][imagenes][1]', archivoFile)
formData.append('prendas[0][telas][1][imagenes][0]', archivoFile)
// ...
```

### Paso 2: Backend Procesa en `CrearPedidoEditableController::procesarYAsignarImagenes()`

**Punto clave:** `prendas.{itemIdx}.telas.{telaIdx}.imagenes.{imgIdx}`

```php
// Obtener pedido con prendas
$pedido = PedidoProduccion::with('prendas.coloresTelas')->findOrFail($pedidoId);

// Por cada prenda
foreach ($items as $itemIdx => $item) {
    $prenda = $pedido->prendas[$itemIdx];
    
    // Por cada tela
    if (isset($item['telas']) && is_array($item['telas'])) {
        foreach ($item['telas'] as $telaIdx => $tela) {
            
            // ✅ CRUCIAL: Obtener o crear la relación color-tela
            $telaRelacion = $prenda->coloresTelas->get($telaIdx);
            
            if (!$telaRelacion && isset($tela['color_id'], $tela['tela_id'])) {
                // Usar ColorTelaService para obtener/crear
                $colorTelaId = $this->colorTelaService->obtenerOCrearColorTela(
                    $prenda->id,
                    $tela['color_id'],
                    $tela['tela_id']
                );
                $telaRelacion = PrendaPedidoColorTela::find($colorTelaId);
            }
            
            // Por cada imagen de la tela
            $imgIdx = 0;
            while ($request->hasFile("prendas.{$itemIdx}.telas.{$telaIdx}.imagenes.{$imgIdx}")) {
                
                // Guardar archivo
                $archivo = $request->file("prendas.{$itemIdx}.telas.{$telaIdx}.imagenes.{$imgIdx}");
                $resultado = $this->imageUploadService->guardarImagenDirecta(
                    $archivo, 
                    $pedidoId, 
                    'telas'  // ← Tipo específico
                );
                
                // Registrar en BD
                PrendaFotoTelaPedido::create([
                    'prenda_pedido_colores_telas_id' => $telaRelacion->id,
                    'ruta_webp' => $resultado['webp'],
                    'orden' => $imgIdx + 1,
                ]);
                
                $imgIdx++;
            }
        }
    }
}
```

### Paso 3: Almacenamiento en Disco

**Ruta:** `storage/app/public/pedidos/{pedidoId}/telas/`

```
storage/
└── app/
    └── public/
        └── pedidos/
            └── 12345/              # ID del pedido
                ├── prendas/
                │   ├── image_001_0.webp
                │   ├── image_001_1.webp
                │   └── ...
                ├── telas/
                │   ├── color_tela_5_0.webp
                │   ├── color_tela_5_1.webp
                │   ├── color_tela_6_0.webp
                │   └── ...
                ├── procesos/
                │   ├── BORDADO/
                │   │   └── ...
                │   └── ESTAMPADO/
                │       └── ...
                └── epps/
                    ├── 1/
                    │   └── ...
                    └── 2/
                        └── ...
```

## ✅ Verificación: ¿Se Guardaron Correctamente?

### Query SQL
```sql
-- Verificar telas para una prenda
SELECT 
    pct.id,
    pct.prenda_pedido_id,
    cp.nombre as color_nombre,
    tp.nombre as tela_nombre,
    COUNT(pft.id) as cantidad_fotos
FROM prenda_pedido_colores_telas pct
LEFT JOIN colores_prenda cp ON pct.color_id = cp.id
LEFT JOIN telas_prenda tp ON pct.tela_id = tp.id
LEFT JOIN prenda_fotos_tela_pedido pft ON pct.id = pft.prenda_pedido_colores_telas_id
WHERE pct.prenda_pedido_id = ?
GROUP BY pct.id;
```

### Logs
```
[CrearPedidoEditableController] 🧵 Procesando telas
prenda_id: 123
cantidad_telas: 3

[CrearPedidoEditableController] Telas existentes en BD
cantidad: 3
ids: [45, 46, 47]

[CrearPedidoEditableController] ✅ Imágenes de tela procesadas
tela_id: 45
cantidad_imagenes: 2
```

## 🐛 Problemas Comunes y Soluciones

### Problema 1: "Tela no encontrada en índice"
**Causa:** La prenda no tiene la relación color-tela en el índice esperado.
**Solución:** 
- Verificar que `color_id` y `tela_id` sean válidos
- Usar `ColorTelaService::obtenerOCrearColorTela()` para crear la relación

```php
$colorTelaId = $this->colorTelaService->obtenerOCrearColorTela(
    $prenda->id,
    $tela['color_id'],  // ← Debe existir en colores_prenda
    $tela['tela_id']    // ← Debe existir en telas_prenda
);
```

### Problema 2: "Imágenes de tela no se ven en la BD"
**Causa:** Las imágenes se guardaron en disco pero no se registraron en `prenda_fotos_tela_pedido`.
**Verificar:**
- El `pedido_id` es correcto
- La carpeta `pedidos/{pedidoId}/telas/` existe
- Los logs muestran `📸 Imagen tela guardada`

```sql
SELECT * FROM prenda_fotos_tela_pedido 
WHERE prenda_pedido_colores_telas_id = ?;
```

### Problema 3: FormData no llega correctamente
**Verificar en el frontend:**
```javascript
// Debe ser: prendas[itemIdx][telas][telaIdx][imagenes][imgIdx]
const key = `prendas[${itemIdx}][telas][${telaIdx}][imagenes][${imgIdx}]`;
formData.append(key, file);  // ← Correcto
```

## 📊 Estadísticas por Pedido

```php
// Contar telas por pedido
$prendas = PedidoProduccion::with('prendas.coloresTelas.fotos')
    ->findOrFail($pedidoId)
    ->prendas;

foreach ($prendas as $prenda) {
    foreach ($prenda->coloresTelas as $colorTela) {
        echo "Tela {$colorTela->id}: {$colorTela->fotos->count()} imágenes\n";
    }
}
```

## 🔗 Servicios Relacionados

### ColorTelaService
- `obtenerOCrearColorTela(int $prendaId, ?int $colorId, ?int $telaId): ?int`
- Obtiene la combinación color-tela existente o la crea
- Retorna el ID de la relación

### ImageUploadService
- `guardarImagenDirecta(UploadedFile $archivo, int $pedidoId, string $tipo, ...): array`
- `$tipo = 'telas'` para imágenes de telas
- Retorna: `['webp' => '...', 'original' => '...', 'thumbnail' => '...']`

## 📝 Notas Importantes

1. **Las imágenes SIEMPRE son WebP:** El sistema convierte a WebP automáticamente en `ImageUploadService`

2. **Orden importa:** Se almacena en `prenda_fotos_tela_pedido.orden` (1-based)

3. **Soft Delete:** Las fotos tienen `deleted_at`, se pueden "eliminar lógicamente"

4. **Por Tela, no por Prenda:** Cada combinación color-tela tiene sus propias imágenes, no por prenda

5. **IDs Requeridos:** 
   - `color_id` debe existir en `colores_prenda`
   - `tela_id` debe existir en `telas_prenda`

