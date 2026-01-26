# SISTEMA DE UPLOADS CENTRALIZADO - CÓDIGO CORREGIDO

## 🎯 OBJETIVO PRINCIPAL

**NINGÚN** archivo debe guardarse en carpetas globales como:
- ❌ `/prendas/`
- ❌ `/telas/`
- ❌ `/procesos/`
- ❌ `/epps/`
- ❌ `/logos/`
- ❌ `/reflectivos/`

**TODO** debe ir a:
- ✅ `temp/{uuid}/{tipo}/webp/` (temporal)
- ✅ `pedidos/{pedido_id}/{tipo}/` (final)

---

## 📋 FLUJO COMPLETO

### 1. UPLOAD INICIAL (Frontend → Backend)

```
Usuario carga imagen en frontend
    ↓
POST /asesores/pedidos-editable/subir-imagenes-prenda
    ↓
CrearPedidoEditableController::guardarImagen()
    ↓
ImageUploadService::processAndSaveImage($archivo, 'prendas', $uuid)
    ↓
GUARDA EN: temp/{uuid}/prendas/webp/imagen.webp
           temp/{uuid}/prendas/original/imagen.jpg
           temp/{uuid}/prendas/thumbnails/imagen.webp
    ↓
RETORNA: ["temp/{uuid}/prendas/webp/imagen.webp"]
```

### 2. CREACIÓN DEL PEDIDO (Cuando usuario confirma)

```
Usuario crea pedido en frontend
    ↓
POST /asesores/pedidos-editable/crear
    ↓
PedidoWebService::crearPedidoCompleto($data)
    ↓
PrendaImagenService::crearPrendaConImagen()
    ↓
ImagenRelocalizadorService::relocalizarImagenes(
    pedidoId: 2754,
    rutasTemp: ["temp/abc-123/prendas/webp/imagen.webp"],
    tipoEspecifico: 'prendas'
)
    ↓
LEE: temp/abc-123/prendas/webp/imagen.webp
EXTRAE TIPO: 'prendas'
CREA: pedidos/2754/prendas/
COPIA: pedidos/2754/prendas/imagen.webp
ELIMINA: temp/abc-123/prendas/webp/imagen.webp
LIMPIA: temp/abc-123/ (si está vacío)
    ↓
GUARDA EN BD:
    - ruta_original: 'pedidos/2754/prendas/imagen.jpg'
    - ruta_webp: 'pedidos/2754/prendas/imagen.webp'
```

---

## 🔧 SERVICIOS CORREGIDOS

### ImageUploadService.php

**Ubicación**: `app/Application/Services/ImageUploadService.php`

**CAMBIO CRÍTICO (Línea 39)**:
```php
// ❌ ANTES (creaba carpetas globales)
$basePath = "{$folder}/temp/{$tempUuid}";
// Resultado: prendas/temp/abc-123/ ❌

// ✅ AHORA (centralizado)
$basePath = "temp/{$tempUuid}/{$folder}";
// Resultado: temp/abc-123/prendas/ ✅
```

**Métodos principales**:
- `processAndSaveImage($file, $folder, $tempUuid, $customFilename = null)` → Guarda en `temp/{uuid}/{tipo}/`
- `uploadPrendaImage($file, $tempUuid)` → Wrapper para prendas
- `uploadTelaImage($file, $tempUuid)` → Wrapper para telas

**Estructura creada**:
```
temp/
└── {uuid}/
    └── {tipo}/
        ├── webp/           → imagen.webp (85% calidad)
        ├── original/       → imagen.jpg (100% calidad)
        └── thumbnails/     → imagen.webp (300x300)
```

---

### ImagenRelocalizadorService.php

**Ubicación**: `app/Domain/Pedidos/Services/ImagenRelocalizadorService.php`

**MÉTODO ACTUALIZADO: `extraerTipo()`** (Soporta 3 formatos)

```php
private function extraerTipo(string $ruta): string
{
    $partes = explode('/', $ruta);
    $tiposValidos = ['prendas', 'telas', 'procesos', 'logos', 'reflectivos', 'epp'];
    
    // 1. FORMATO NUEVO CENTRALIZADO: temp/uuid/prendas/webp/file.webp
    if (isset($partes[0]) && $partes[0] === 'temp' && isset($partes[2])) {
        $tipoCandidato = strtolower($partes[2]);
        if (in_array($tipoCandidato, $tiposValidos)) {
            return $tipoCandidato;
        }
    }
    
    // 2. FORMATO VIEJO TEMP: prendas/temp/uuid/file.webp
    if (isset($partes[0])) {
        $tipoCandidato = strtolower($partes[0]);
        if (in_array($tipoCandidato, $tiposValidos)) {
            return $tipoCandidato;
        }
    }
    
    // 3. FORMATO VIEJO DIRECTO: prendas/2026/01/file.jfif
    foreach ($partes as $parte) {
        $tipoCandidato = strtolower($parte);
        if (in_array($tipoCandidato, $tiposValidos)) {
            return $tipoCandidato;
        }
    }
    
    return 'prendas'; // Fallback
}
```

**MÉTODO ACTUALIZADO: `limpiarCarpetaTempSiVacia()`** (Limpieza recursiva)

```php
private function limpiarCarpetaTempSiVacia(string $carpeta): void
{
    try {
        if (!Storage::disk('public')->exists($carpeta)) {
            return;
        }

        // Verificar si la carpeta está vacía
        $archivos = Storage::disk('public')->files($carpeta);
        $subdirectorios = Storage::disk('public')->directories($carpeta);
        
        if (empty($archivos) && empty($subdirectorios)) {
            Storage::disk('public')->deleteDirectory($carpeta);
            
            // Verificar y limpiar carpeta padre si es parte de temp/{uuid}/
            $partes = explode('/', $carpeta);
            if (count($partes) >= 3 && $partes[0] === 'temp') {
                $carpetaPadre = implode('/', array_slice($partes, 0, -1));
                if ($carpetaPadre !== 'temp') {
                    $this->limpiarCarpetaTempSiVacia($carpetaPadre);
                }
            }
        }
    } catch (\Exception $e) {
        Log::warning('[ImagenRelocalizadorService] Error limpiando carpeta temp', [
            'carpeta' => $carpeta,
            'error' => $e->getMessage(),
        ]);
    }
}
```

**MÉTODO ACTUALIZADO: `limpiarCarpetaTempPorUuid()`**

```php
public function limpiarCarpetaTempPorUuid(string $uuid): void
{
    try {
        $carpeta = "temp/{$uuid}";
        
        if (Storage::disk('public')->exists($carpeta)) {
            Storage::disk('public')->deleteDirectory($carpeta);
            Log::info('[ImagenRelocalizadorService] Carpeta temp limpiada por UUID', [
                'uuid' => $uuid,
                'carpeta' => $carpeta,
            ]);
        }
    } catch (\Exception $e) {
        Log::warning('[ImagenRelocalizadorService] Error limpiando carpeta temp por UUID', [
            'uuid' => $uuid,
            'error' => $e->getMessage(),
        ]);
    }
}
```

---

### PedidoWebService.php

**Ubicación**: `app/Domain/Pedidos/Services/PedidoWebService.php`

**MÉTODO ACTUALIZADO: `guardarImagenesTela()`**

```php
private function guardarImagenesTela(
    PrendaPedidoColorTela $colorTela,
    array $imagenes,
    int $pedidoId // ← NUEVO PARÁMETRO
): void {
    if (empty($imagenes)) {
        return;
    }

    $rutasOriginales = is_string($imagenes[0]) ? $imagenes : [];
    $resultado = $this->telaImagenService->guardarImagenesTela($colorTela, $imagenes);

    if (!empty($rutasOriginales)) {
        $this->imagenRelocalizador->relocalizarImagenes(
            $pedidoId,
            $rutasOriginales,
            'telas' // ← TIPO EXPLÍCITO
        );
    }
}
```

**MÉTODO ACTUALIZADO: `crearTelasDesdeFormulario()`**

```php
private function crearTelasDesdeFormulario(
    PrendaPedido $prendaPedido,
    array $telasFormulario
): void {
    foreach ($telasFormulario as $telaData) {
        // ... código de creación de color_tela ...
        
        if (!empty($imagenesTela)) {
            $this->guardarImagenesTela(
                $colorTela,
                $imagenesTela,
                $prendaPedido->pedido_id // ← NUEVO: se pasa el pedido_id
            );
        }
    }
}
```

**MÉTODO ACTUALIZADO: `guardarImagenesProceso()`**

```php
private function guardarImagenesProceso(
    PedidosProcesosPrendaDetalle $proceso,
    array $imagenes
): void {
    if (empty($imagenes)) {
        return;
    }

    $rutasOriginales = is_string($imagenes[0]) ? $imagenes : [];
    $resultado = $this->procesoImagenService->guardarImagenesProceso($proceso, $imagenes);

    if (!empty($rutasOriginales)) {
        $this->imagenRelocalizador->relocalizarImagenes(
            $proceso->proceso->prenda->pedido_id,
            $rutasOriginales,
            'procesos' // ← TIPO EXPLÍCITO
        );
    }
}
```

---

### CrearPedidoEditableController.php

**Ubicación**: `app/Infrastructure/Http/Controllers/Asesores/CrearPedidoEditableController.php`

**MÉTODO ACTUALIZADO: `guardarImagen()`**

```php
private function guardarImagen(Request $request, string $tipo = 'prenda'): JsonResponse
{
    try {
        $archivo = $request->file('imagen');
        $tempUuid = $request->input('temp_uuid', Str::uuid()->toString());
        $customFilename = $request->input('filename', null);

        // Usar ImageUploadService para procesamiento y guardado
        $rutas = $this->imageUploadService->processAndSaveImage(
            $archivo,
            $tipo === 'tela' ? 'telas' : 'prendas',
            $tempUuid,
            $customFilename
        );

        return response()->json([
            'success' => true,
            'rutas' => $rutas,
            'temp_uuid' => $tempUuid,
            'message' => 'Imagen guardada exitosamente en formato WebP',
        ]);

    } catch (\Exception $e) {
        Log::error("[CrearPedidoEditableController] Error guardando imagen", [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Error al guardar la imagen: ' . $e->getMessage(),
        ], 500);
    }
}
```

---

## ✅ SERVICIOS YA CORRECTOS (No necesitan cambios)

### PrendaImagenService.php

**Ubicación**: `app/Domain/Pedidos/Services/PrendaImagenService.php`

✅ **YA guarda directamente en**: `storage_path("app/public/pedidos/{$pedidoId}/prendas")`

**Métodos principales**:
- `crearPrendaConImagen()` → Guarda prenda + imagen
- `guardarImagenesPrenda()` → Guarda solo imágenes

### TelaImagenService.php

**Ubicación**: `app/Domain/Pedidos/Services/TelaImagenService.php`

✅ **YA guarda directamente en**: `storage_path("app/public/pedidos/{$pedidoId}/telas")`

**Métodos principales**:
- `guardarImagenesTela()` → Guarda imágenes de telas

### ProcesoImagenService.php

**Ubicación**: `app/Domain/Pedidos/Services/ProcesoImagenService.php`

✅ **YA guarda directamente en**: `storage_path("app/public/pedidos/{$pedidoId}/procesos/{$tipoProcesoNombre}")`

**Métodos principales**:
- `guardarImagenesProceso()` → Guarda imágenes de procesos

---

## 🔍 OTROS LUGARES QUE USAN `->store()` (NO MODIFICAR)

Estos servicios NO están relacionados con pedidos y siguen sus propios flujos:

### Cotizaciones
- `ImagenCotizacionService.php` → `cotizaciones/{id}/prendas|telas`
- `CotizacionPrendaController.php` → `cotizaciones/{id}/prendas|telas`
- `CotizacionController.php` → `cotizaciones/reflectivo`

### Supervisor/Admin
- `SupervisorPedidosController.php` → `pedidos/{numero}/{tipo}` (usa número, no ID)
- `ContadorController.php` → `avatars/` (usuarios)

### EPP
- `EppController.php` → `epp/imagenes/` (catálogo EPP)

### Tableros Producción
- `TablerosController.php` → Usa servicios internos

### NOTA: `PedidosProduccionController.php`
- **Línea 722**: `$path = $imagen->store('prendas', 'public');`
- ⚠️ **POTENCIAL PROBLEMA**: Este controller guarda en carpeta global `/prendas/`
- **REVISAR**: Si se usa para pedidos, debería usar el flujo correcto

---

## 🧪 TESTING

### Test Manual

1. **Subir imágenes sin crear pedido**:
```bash
POST /asesores/pedidos-editable/subir-imagenes-prenda
Body: { imagen: file, temp_uuid: "abc-123" }

Verificar:
✅ Se crea: storage/app/public/temp/abc-123/prendas/webp/imagen.webp
❌ NO se crea: storage/app/public/prendas/
```

2. **Crear pedido completo**:
```bash
POST /asesores/pedidos-editable/crear
Body: { 
    prendas: [{ imagenes: ["temp/abc-123/prendas/webp/imagen.webp"] }]
}

Verificar:
✅ Se crea: storage/app/public/pedidos/2754/prendas/imagen.webp
✅ Se elimina: storage/app/public/temp/abc-123/
❌ NO se crea: storage/app/public/prendas/
```

3. **Verificar base de datos**:
```sql
SELECT ruta_original, ruta_webp 
FROM prenda_fotos_pedido 
WHERE pedido_id = 2754;

Resultado esperado:
ruta_original: "pedidos/2754/prendas/imagen.jpg"
ruta_webp: "pedidos/2754/prendas/imagen.webp"
```

### Test Automatizado

```php
php artisan test --filter ImagenRelocalizadorTest
```

---

## 📁 ESTRUCTURA DE CARPETAS FINAL

```
storage/app/public/
│
├── temp/                          ← Temporal (se limpia automáticamente)
│   ├── abc-123/
│   │   ├── prendas/
│   │   │   ├── webp/
│   │   │   ├── original/
│   │   │   └── thumbnails/
│   │   ├── telas/
│   │   └── procesos/
│   └── def-456/
│
├── pedidos/                       ← Final (permanente)
│   ├── 2754/
│   │   ├── prendas/
│   │   │   ├── imagen1.webp
│   │   │   └── imagen2.webp
│   │   ├── telas/
│   │   └── procesos/
│   │       ├── ESTAMPADO/
│   │       └── BORDADO/
│   └── 2755/
│
├── cotizaciones/                  ← Cotizaciones (separado)
├── avatars/                       ← Usuarios (separado)
└── epp/                           ← Catálogo EPP (separado)
```

---

## 🚨 LO QUE NUNCA DEBE EXISTIR

```
storage/app/public/
├── ❌ prendas/              ← NUNCA debe crearse
├── ❌ telas/                ← NUNCA debe crearse
├── ❌ procesos/             ← NUNCA debe crearse
├── ❌ epps/                 ← NUNCA debe crearse
├── ❌ logos/                ← NUNCA debe crearse
└── ❌ reflectivos/          ← NUNCA debe crearse
```

Si estas carpetas existen, eliminarlas manualmente:
```bash
rm -rf storage/app/public/prendas
rm -rf storage/app/public/telas
rm -rf storage/app/public/procesos
```

---

## 📊 COMPATIBILIDAD CON FORMATOS ANTIGUOS

El sistema soporta 3 formatos de rutas:

| Formato | Ejemplo | Estado |
|---------|---------|--------|
| **Nuevo Centralizado** | `temp/abc-123/prendas/webp/img.webp` | ✅ ACTUAL |
| **Viejo Temp** | `prendas/temp/abc-123/img.webp` | ⚠️ Compatibilidad |
| **Viejo Directo** | `prendas/2026/01/img.jfif` | ⚠️ Compatibilidad |

El método `extraerTipo()` detecta automáticamente el formato.

---

## 🔄 FLUJO DE CLEANUP

### Limpieza automática después de relocalizaciónen:

1. **Relocalizador termina** → Llama a `limpiarCarpetaTempSiVacia()`
2. **Verifica carpeta actual** → `temp/abc-123/prendas/webp/`
3. **¿Está vacía?** → SÍ → Elimina
4. **Sube un nivel** → `temp/abc-123/prendas/`
5. **¿Está vacía?** → SÍ → Elimina
6. **Sube un nivel** → `temp/abc-123/`
7. **¿Está vacía?** → SÍ → Elimina
8. **Termina** (no elimina `temp/` raíz)

### Limpieza manual por UUID:

```php
$this->imagenRelocalizador->limpiarCarpetaTempPorUuid('abc-123');
// Elimina directamente: temp/abc-123/
```

---

## 📝 LOGS PARA DEBUGGING

Todos los logs usan el prefijo `[ImagenRelocalizadorService]`:

```log
[ImagenRelocalizadorService] Relocalizando imágenes
    pedido_id: 2754
    cantidad_rutas: 3
    tipo_especifico: prendas

[ImagenRelocalizadorService] Imagen relocalizada exitosamente
    pedido_id: 2754
    ruta_temp: temp/abc-123/prendas/webp/imagen.webp
    ruta_final: pedidos/2754/prendas/imagen.webp
    tipo_detectado: prendas

[ImagenRelocalizadorService] Carpeta temp limpiada
    carpeta: temp/abc-123/prendas/webp

[ImagenRelocalizadorService] Carpeta temp limpiada por UUID
    uuid: abc-123
    carpeta: temp/abc-123
```

---

## ✅ CHECKLIST IMPLEMENTACIÓN

- [x] `ImageUploadService` ahora guarda en `temp/{uuid}/{tipo}/`
- [x] `ImagenRelocalizadorService::extraerTipo()` soporta 3 formatos
- [x] `ImagenRelocalizadorService::limpiarCarpetaTempSiVacia()` con limpieza recursiva
- [x] `ImagenRelocalizadorService::limpiarCarpetaTempPorUuid()` centralizado
- [x] `PedidoWebService::guardarImagenesTela()` recibe `$pedidoId`
- [x] `PedidoWebService::guardarImagenesProceso()` llama relocalizador con tipo
- [x] `PedidoWebService::crearTelasDesdeFormulario()` pasa `pedido_id`
- [x] `CrearPedidoEditableController::guardarImagen()` usa `ImageUploadService`
- [x] Verificado: `PrendaImagenService`, `TelaImagenService`, `ProcesoImagenService` ya correctos
- [ ] **PENDIENTE**: Testing end-to-end
- [ ] **PENDIENTE**: Eliminar carpetas globales si existen
- [ ] **PENDIENTE**: Revisar `PedidosProduccionController.php` línea 722

---

## 🎯 RESULTADO FINAL

### Antes (❌ Incorrecto):
```
storage/app/public/
├── prendas/
│   ├── 2026/01/imagen1.jfif
│   └── temp/abc-123/imagen2.webp
├── telas/
│   └── 2026/01/tela1.jpg
└── pedidos/
    └── 2754/                      ← Algunos archivos aquí
```

### Ahora (✅ Correcto):
```
storage/app/public/
├── temp/
│   └── abc-123/                   ← Solo mientras no se crea pedido
│       ├── prendas/
│       └── telas/
└── pedidos/
    └── 2754/                      ← TODO aquí
        ├── prendas/
        │   ├── imagen1.webp
        │   └── imagen2.webp
        ├── telas/
        │   └── tela1.webp
        └── procesos/
            └── ESTAMPADO/
                └── proceso1.webp
```

---

## 📚 REFERENCIAS

- **Conversación completa**: Ver historial de cambios
- **Archivos modificados**:
  - `ImageUploadService.php` (línea 39)
  - `ImagenRelocalizadorService.php` (métodos: extraerTipo, limpiarCarpetaTempSiVacia, limpiarCarpetaTempPorUuid)
  - `PedidoWebService.php` (métodos: guardarImagenesTela, guardarImagenesProceso, crearTelasDesdeFormulario)
  - `CrearPedidoEditableController.php` (método: guardarImagen)

---

**Fecha última actualización**: 2025-01-17  
**Estado**: ✅ Código corregido - Pendiente testing
