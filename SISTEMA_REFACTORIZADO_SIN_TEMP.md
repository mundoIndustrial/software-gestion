# 🔄 SISTEMA REFACTORIZADO - SIN CARPETAS TEMPORALES

## ✅ CAMBIOS IMPLEMENTADOS

### 🎯 Objetivo Logrado

❌ **ELIMINADO**: Sistema de carpetas temporales `temp/{uuid}/`  
❌ **ELIMINADO**: `ImagenRelocalizadorService` (ya no se usa)  
❌ **ELIMINADO**: Flujo de relocalización  
✅ **NUEVO**: Guardado directo en `pedidos/{pedido_id}/{tipo}/`

---

## 📋 NUEVO FLUJO

### Antes (❌ Complejo)
```
1. Frontend → Upload imagen → temp/{uuid}/prendas/
2. Frontend → Crear pedido
3. Backend → Relocali imágenes → pedidos/{id}/prendas/
4. Backend → Limpiar temp/{uuid}/
```

### Ahora (✅ Simplificado)
```
1. Frontend → Crear pedido vacío → obtiene pedido_id: 2754
2. Frontend → Upload imagen con pedido_id
3. Backend → Guarda directo → pedidos/2754/prendas/imagen.webp
```

---

## 🔧 ARCHIVOS MODIFICADOS

### 1. ImageUploadService.php

**Nuevo método principal**: `guardarImagenDirecta()`

```php
/**
 * Guardar imagen directamente en pedidos/{pedido_id}/{tipo}/
 * 
 * @param UploadedFile $file
 * @param int $pedidoId (REQUERIDO - no puede ser null)
 * @param string $tipo 'prendas', 'telas', 'procesos', etc.
 * @param string|null $subcarpeta Opcional: 'ESTAMPADO', 'BORDADO'
 * @param string|null $customFilename Opcional: nombre personalizado
 * @return array ['original', 'webp', 'thumbnail']
 */
public function guardarImagenDirecta(
    UploadedFile $file,
    int $pedidoId,
    string $tipo,
    ?string $subcarpeta = null,
    ?string $customFilename = null
): array
```

**Ejemplo de uso**:
```php
$imageUploadService = app(ImageUploadService::class);

// Guardar en: pedidos/2754/prendas/
$resultado = $imageUploadService->guardarImagenDirecta(
    $request->file('imagen'),
    2754,                    // pedido_id
    'prendas',              // tipo
    null,                   // sin subcarpeta
    'prenda_01'             // nombre personalizado
);

// Guardar en: pedidos/2754/procesos/ESTAMPADO/
$resultado = $imageUploadService->guardarImagenDirecta(
    $request->file('imagen'),
    2754,                    // pedido_id
    'procesos',             // tipo
    'ESTAMPADO',            // subcarpeta
    null                    // nombre autogenerado
);

// Retorna:
[
    'original' => 'pedidos/2754/prendas/prenda_01.jpg',
    'webp' => 'pedidos/2754/prendas/prenda_01.webp',
    'thumbnail' => 'pedidos/2754/prendas/prenda_01_thumb.webp'
]
```

**Método deprecado** (mantener por compatibilidad):
- `processAndSaveImage()` → Loguea warning para migrar a `guardarImagenDirecta()`

---

### 2. CrearPedidoEditableController.php

**Nuevo endpoint**: `POST /asesores/pedidos-editable/subir-imagen`

```php
/**
 * Subir imagen directamente a pedidos/{pedido_id}/{tipo}/
 * 
 * Body (form-data):
 * - imagen: file (required)
 * - pedido_id: int (required)
 * - tipo: string (required) - 'prendas', 'telas', 'procesos'
 * - subcarpeta: string (optional) - ej: 'ESTAMPADO'
 * - filename: string (optional) - nombre personalizado
 */
public function subirImagen(Request $request): JsonResponse
```

**Ejemplo desde frontend (JavaScript)**:
```javascript
// 1. Crear pedido primero
const responsePedido = await fetch('/asesores/pedidos-editable/crear', {
    method: 'POST',
    body: JSON.stringify({ 
        cliente_id: 123,
        // ... otros datos
    })
});
const { pedido_id } = await responsePedido.json();

// 2. Subir imagen CON pedido_id
const formData = new FormData();
formData.append('imagen', fileInput.files[0]);
formData.append('pedido_id', pedido_id);
formData.append('tipo', 'prendas');
formData.append('subcarpeta', 'ESTAMPADO'); // opcional

const responseImagen = await fetch('/asesores/pedidos-editable/subir-imagen', {
    method: 'POST',
    body: formData
});

// Respuesta:
{
    "success": true,
    "message": "Imagen guardada exitosamente",
    "data": {
        "ruta_original": "pedidos/2754/prendas/imagen.jpg",
        "ruta_webp": "pedidos/2754/prendas/imagen.webp",
        "thumbnail": "pedidos/2754/prendas/imagen_thumb.webp",
        "url_webp": "/storage/pedidos/2754/prendas/imagen.webp",
        "url_original": "/storage/pedidos/2754/prendas/imagen.jpg"
    }
}
```

**Endpoint deprecado** (mantener por compatibilidad):
- `subirImagenesPrenda()` → Loguea para migrar a `subirImagen()`

---

### 3. PedidoWebService.php

**Método refactorizado**: `guardarImagenesProceso()`

```php
/**
 * Guardar imágenes de proceso directamente
 * 
 * ✅ Si recibe UploadedFile → guarda con ImageUploadService
 * ✅ Si recibe string (ruta ya guardada) → guarda solo en BD
 * ❌ Ya NO usa ImagenRelocalizadorService
 */
private function guardarImagenesProceso(
    PedidosProcesosPrendaDetalle $proceso,
    array $imagenes
): void
```

**Cambios**:
- ❌ Eliminada dependencia de `ImagenRelocalizadorService`
- ✅ Inyecta `ImageUploadService` en constructor
- ✅ Usa `guardarImagenDirecta()` si recibe `UploadedFile`
- ✅ Guarda directo en `pedidos/{id}/procesos/{nombre_proceso}/`

**Constructor actualizado**:
```php
public function __construct(
    PrendaImagenService $prendaImagenService = null,
    TelaImagenService $telaImagenService = null,
    ProcesoImagenService $procesoImagenService = null,
    ImageUploadService $imageUploadService = null  // ← NUEVO
) {
    $this->prendaImagenService = $prendaImagenService ?? app(PrendaImagenService::class);
    $this->telaImagenService = $telaImagenService ?? app(TelaImagenService::class);
    $this->procesoImagenService = $procesoImagenService ?? app(ProcesoImagenService::class);
    $this->imageUploadService = $imageUploadService ?? app(ImageUploadService::class);
}
```

---

## 📁 ESTRUCTURA FINAL

### Carpetas que YA NO EXISTEN
```
❌ temp/
❌ prendas/ (global)
❌ telas/ (global)
❌ procesos/ (global)
```

### Carpetas que SÍ EXISTEN
```
storage/app/public/
└── pedidos/
    ├── 2754/
    │   ├── prendas/
    │   │   ├── imagen1.jpg
    │   │   ├── imagen1.webp
    │   │   ├── imagen1_thumb.webp
    │   │   ├── imagen2.jpg
    │   │   ├── imagen2.webp
    │   │   └── imagen2_thumb.webp
    │   ├── telas/
    │   │   ├── tela1.jpg
    │   │   ├── tela1.webp
    │   │   └── tela1_thumb.webp
    │   └── procesos/
    │       ├── ESTAMPADO/
    │       │   ├── proceso1.jpg
    │       │   ├── proceso1.webp
    │       │   └── proceso1_thumb.webp
    │       └── BORDADO/
    │           ├── proceso2.jpg
    │           ├── proceso2.webp
    │           └── proceso2_thumb.webp
    └── 2755/
        └── ...
```

---

## 🔄 MIGRACIÓN DESDE SISTEMA ANTIGUO

### Si tu código usa `processAndSaveImage()`:

**ANTES**:
```php
$resultado = $imageUploadService->processAndSaveImage(
    $file,
    'prenda_01',
    'prendas',
    $tempUuid
);
// Retorna: temp/{uuid}/prendas/webp/prenda_01.webp
```

**DESPUÉS**:
```php
$resultado = $imageUploadService->guardarImagenDirecta(
    $file,
    $pedidoId,     // ← REQUERIDO
    'prendas',
    null,
    'prenda_01'
);
// Retorna: pedidos/2754/prendas/prenda_01.webp
```

### Si tu código usa `ImagenRelocalizadorService`:

**ANTES**:
```php
// 1. Subir a temp/
$rutas = [...]; // temp/{uuid}/prendas/webp/imagen.webp

// 2. Relocalizar cuando se crea pedido
$relocalizador->relocalizarImagenes($pedidoId, $rutas, 'prendas');
```

**DESPUÉS**:
```php
// ❌ NO hacer esto
// ✅ Subir directamente con pedido_id desde el inicio

$resultado = $imageUploadService->guardarImagenDirecta(
    $file,
    $pedidoId,
    'prendas'
);
// Ya está en: pedidos/2754/prendas/imagen.webp
```

---

## 🧪 TESTING

### Test del nuevo endpoint

```bash
# 1. Crear pedido vacío (implementar endpoint si no existe)
POST /asesores/pedidos-editable/crear
{
    "cliente_id": 123,
    "observaciones": "Test pedido"
}

# Respuesta: { "pedido_id": 2754 }

# 2. Subir imagen con pedido_id
POST /asesores/pedidos-editable/subir-imagen
Content-Type: multipart/form-data

imagen: [archivo]
pedido_id: 2754
tipo: prendas

# 3. Verificar estructura
ls storage/app/public/pedidos/2754/prendas/
# Debe existir:
# - imagen.jpg
# - imagen.webp
# - imagen_thumb.webp

# 4. Verificar que NO existen carpetas globales
ls storage/app/public/prendas/   # ← NO debe existir
ls storage/app/public/temp/       # ← NO debe existir
```

### Test de procesos con subcarpeta

```bash
POST /asesores/pedidos-editable/subir-imagen

imagen: [archivo]
pedido_id: 2754
tipo: procesos
subcarpeta: ESTAMPADO

# Verificar:
ls storage/app/public/pedidos/2754/procesos/ESTAMPADO/
# Debe contener la imagen
```

---

## ⚠️ PUNTOS IMPORTANTES

### 1. Crear Pedido PRIMERO
El frontend DEBE crear el pedido (aunque sea vacío) antes de subir imágenes.

**Opción A**: Crear pedido completo con todos los datos  
**Opción B**: Crear pedido "borrador" y luego completar

### 2. No más UUID temporales
El parámetro `temp_uuid` ya no se usa. Todo se identifica por `pedido_id`.

### 3. Subcarpetas para procesos
Los procesos requieren subcarpeta con el nombre del proceso:
- `ESTAMPADO`
- `BORDADO`
- `DTF`
- `SUBLIMADO`

```php
$imageUploadService->guardarImagenDirecta(
    $file,
    $pedidoId,
    'procesos',
    'ESTAMPADO'  // ← Requerido para procesos
);
```

### 4. Validaciones
El endpoint valida:
- Imagen válida (jpeg, png, webp, max 10MB)
- `pedido_id` existe en BD (`exists:pedidos,id`)
- `tipo` es válido (`prendas`, `telas`, `procesos`, etc.)

---

## 📝 RUTA DE ARCHIVOS (routes/asesores.php)

Agregar nueva ruta:

```php
use App\Infrastructure\Http\Controllers\Asesores\CrearPedidoEditableController;

Route::prefix('pedidos-editable')->name('pedidos-editable.')->group(function () {
    // ... rutas existentes ...
    
    // NUEVO ENDPOINT
    Route::post('/subir-imagen', [CrearPedidoEditableController::class, 'subirImagen'])
        ->name('subir-imagen');
});
```

---

## ✅ CHECKLIST DE IMPLEMENTACIÓN

- [x] `ImageUploadService::guardarImagenDirecta()` creado
- [x] `CrearPedidoEditableController::subirImagen()` creado
- [x] `PedidoWebService::guardarImagenesProceso()` refactorizado
- [x] `PedidoWebService` inyecta `ImageUploadService`
- [x] Imports agregados (Storage, Log)
- [ ] **PENDIENTE**: Agregar ruta en `routes/asesores.php`
- [ ] **PENDIENTE**: Actualizar frontend para usar nuevo flujo
- [ ] **PENDIENTE**: Testing end-to-end
- [ ] **PENDIENTE**: Deprecar/eliminar `ImagenRelocalizadorService`

---

## 🚀 PRÓXIMOS PASOS

### 1. Frontend (JavaScript)
Actualizar flujo:
```javascript
// 1. Crear pedido primero
const pedido = await crearPedido(datos);

// 2. Subir imágenes con pedido_id
await subirImagen(file, pedido.id, 'prendas');
```

### 2. Rutas
Agregar en `routes/asesores.php`:
```php
Route::post('/pedidos-editable/subir-imagen', 
    [CrearPedidoEditableController::class, 'subirImagen']);
```

### 3. Testing
- Crear pedido de prueba
- Subir imagen con nuevo endpoint
- Verificar estructura de carpetas
- Verificar BD tiene rutas correctas

### 4. Cleanup (opcional)
- Eliminar `ImagenRelocalizadorService.php` (deprecado)
- Eliminar métodos `processAndSaveImage()` (deprecado)
- Eliminar lógica de temp UUID en frontend

---

## 📚 DOCUMENTACIÓN RELACIONADA

- [AUDITORIA_UPLOADS_COMPLETA.md](AUDITORIA_UPLOADS_COMPLETA.md) - Estado anterior
- [SISTEMA_UPLOADS_CENTRALIZADO_CORREGIDO.md](SISTEMA_UPLOADS_CENTRALIZADO_CORREGIDO.md) - Sistema con temp
- **Este archivo** - Sistema refactorizado SIN temp

---

**Fecha**: 2025-01-25  
**Estado**: ✅ CÓDIGO LISTO - Pendiente testing  
**Sistema**: Sin carpetas temporales  
**Relocalización**: ❌ Eliminada  
**Guardado directo**: ✅ Implementado
