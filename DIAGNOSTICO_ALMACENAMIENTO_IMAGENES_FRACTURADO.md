# 🔴 DIAGNÓSTICO CRÍTICO: Sistema de Almacenamiento de Imágenes Fracturado

**Fecha:** 26 de Enero, 2026  
**Severidad:** 🔴 CRÍTICA  
**Estado:** Imágenes NO se guardan en rutas correctas

---

## 📊 RESUMEN EJECUTIVO

El sistema de almacenamiento de imágenes está **FRACTURADO** en 3 niveles independientes SIN COHERENCIA:

| Tipo | Ruta Esperada | Ruta Real (Problema) | Archivo |
|------|---------------|----------------------|---------|
| **Prendas** | `pedido/{id}/prendas/` | `prendas/telas/` | CrearPedidoService.php:202 |
| **Telas** | `pedido/{id}/telas/` | `telas/pedidos/` | ProcesarFotosTelasService.php:98 |
| **Procesos** | `pedido/{id}/procesos/{tipo}/` | Función comentada (∅) | PedidoWebService.php:598 |
| **EPP** | `pedido/{id}/epp/` | `pedidos/epp/` | EppController.php:258 |

---

## 🔴 PROBLEMA 1: GUARDAR EN RUTAS GENÉRICAS (NO POR PEDIDO)

### 1.1 Prendas - Fotos de Telas
**Archivo:** [app/Application/Services/Asesores/CrearPedidoService.php](app/Application/Services/Asesores/CrearPedidoService.php#L202)

**Línea 202:**
```php
$rutaGuardada = $archivoFoto->store('prendas/telas', 'public');
//  GUARDA EN: storage/app/public/prendas/telas/
// ✅ DEBERÍA: storage/app/public/pedido/{pedidoId}/telas/
```

**Problema:**
- ✖️ Todos los pedidos comparten la misma carpeta `prendas/telas/`
- ✖️ Sin referencia a `pedido_id`
- ✖️ Imposible reorganizar después
- ✖️ Riesgo de conflicto de nombres

---

### 1.2 Telas - Fotos de Telas
**Archivo:** [app/Application/Services/Asesores/ProcesarFotosTelasService.php](app/Application/Services/Asesores/ProcesarFotosTelasService.php#L98)

**Línea 98:**
```php
$rutaGuardada = $archivoFoto->store('telas/pedidos', 'public');
//  GUARDA EN: storage/app/public/telas/pedidos/
// ✅ DEBERÍA: storage/app/public/pedido/{pedidoId}/telas/
```

**Problema:**
- ✖️ Carpeta invertida: debería ser `/pedido/{id}/telas/` no `/telas/pedidos/`
- ✖️ Sin `pedido_id` embebido en ruta

---

### 1.3 Procesos - Imágenes
**Archivo:** [app/Domain/Pedidos/Services/PedidoWebService.php](app/Domain/Pedidos/Services/PedidoWebService.php#L598)

**Línea 598-615:**
```php
private function guardarImagenesProceso(PedidosProcesosPrendaDetalle $proceso, array $imagenes): void
{
    // ...
    Log::debug('[PedidoWebService] guardarImagenesProceso: SKIP processing', [
        'proceso_id' => $proceso->id,
        'pedido_id' => $pedidoId,
        'imagenes_count' => count($imagenes),
    ]);

    return;  //  SE RETORNA ANTES DE GUARDAR NADA
    
    //  CÓDIGO COMENTADO (NUNCA SE EJECUTA)
    /*  
    foreach ($imagenes as $index => $imagen) {
        // ...
    }
    */
}
```

**Problema:**
- ✖️ **Función INTENCIONALMENTE DESHABILITADA**
- ✖️ Todo el código comentado
- ✖️ `return;` antes de procesar
- ✖️ Las imágenes de procesos NO se guardan en disco
- ✖️ Solo quedan referencias en BD sin archivos reales

---

### 1.4 EPP - Imágenes
**Archivo:** [app/Infrastructure/Http/Controllers/Epp/EppController.php](app/Infrastructure/Http/Controllers/Epp/EppController.php#L258)

**Línea 258:**
```php
$ruta = $imagen->store('pedidos/epp', 'public');
//  GUARDA EN: storage/app/public/pedidos/epp/
// ✅ DEBERÍA: storage/app/public/pedido/{pedidoId}/epp/
```

**Problema:**
- ✖️ Ruta genérica sin `pedido_id`
- ✖️ Todos los EPP de todos los pedidos en la misma carpeta
- ✖️ Imposible encontrar/eliminar por pedido

---

## 🔴 PROBLEMA 2: MÚLTIPLES SERVICIOS SIN COORDINACIÓN

| Servicio | Responsabilidad | Ruta | Estado |
|----------|-----------------|------|--------|
| `CrearPedidoService` | Prendas + telas | `prendas/telas/` |  Genérica |
| `ProcesarFotosTelasService` | Fotos telas adicionales | `telas/pedidos/` |  Genérica |
| `PedidoWebService` | Procesos | DESHABILITADA |  ∅ |
| `PedidoEppService` | EPP | `pedidos/epp/` |  Genérica |
| `ImageUploadService` | Temp processing | `temp/{uuid}/` | ✅ Correcta |

**Falta:** Un servicio CENTRALIZADO que:
- ✅ Reciba `pedido_id`
- ✅ Guarde en `pedido/{id}/tipo/`
- ✅ Valide instancias UploadedFile
- ✅ Coordine con todos los controllers

---

## 🔴 PROBLEMA 3: UploadedFile NO SE VALIDA

### En CrearPedidoService.php:195-203

```php
if (!empty($producto['telas']) && is_array($producto['telas'])) {
    $telasProcessadas = [];

    foreach ($producto['telas'] as $telaIndex => $tela) {
        $telasProcessadas[$telaIndex] = $tela;
        $fotosProcessadas = [];

        $fotosKey = "productos_friendly.{$productoIndex}.telas.{$telaIndex}.fotos";

        if (!empty($archivos[$fotosKey])) {
            foreach ($archivos[$fotosKey] as $fotoIndex => $archivoFoto) {
                if ($archivoFoto->isValid()) {  // ✅ Valida isValid()
                    $rutaGuardada = $archivoFoto->store('prendas/telas', 'public');
                    //  NO verifica instanceof UploadedFile
                    //  NO verifica error types
                    //  NO maneja excepciones de store()
```

**Falta:**
```php
// ✅ CORRECTO:
if (!$archivoFoto instanceof \Illuminate\Http\UploadedFile) {
    throw new \InvalidArgumentException('Archivo inválido');
}

if (!$archivoFoto->isValid()) {
    Log::warning('Archivo inválido', ['error' => $archivoFoto->getError()]);
    continue;
}

try {
    $ruta = $archivoFoto->store('pedido/' . $pedidoId . '/telas', 'public');
} catch (\Exception $e) {
    Log::error('Error guardando tela', ['error' => $e->getMessage()]);
    throw $e;
}
```

---

## 🔴 PROBLEMA 4: PROCESOS SIN MECANISMO DE ALMACENAMIENTO

### Flujo Actual (ROTO):

1. ✅ Frontend recoge imágenes de procesos
2. ✅ Controller procesa y valida
3.  **PedidoWebService.guardarImagenesProceso() → RETORNA SIN HACER NADA**
4.  Las imágenes nunca se guardan en disco
5.  BD tiene referencias a rutas que no existen

### Código en PedidoWebService.php:615

```php
private function guardarImagenesProceso(...): void
{
    // ... setup ...
    
    return;  //  ¡AQUÍ SALE SIN GUARDAR!
    
    // TODO: Código NUNCA ejecutado
    /*
    foreach ($imagenes as $index => $imagen) {
        if ($imagen instanceof UploadedFile) {
            $resultado = $this->imageUploadService->guardarImagenDirecta(...);
            // ...
        }
    }
    */
}
```

---

## 🎯 SOLUCIONES REQUERIDAS

### ✅ SOLUCIÓN 1: Crear Servicio Centralizado

**Archivo:** `app/Application/Services/ImagenPedidoService.php`

```php
<?php
namespace App\Application\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ImagenPedidoService
{
    const DISK = 'public';
    const BASE_PATH = 'pedido';
    
    /**
     * Guardar imagen en carpeta específica del pedido
     * 
     * @param UploadedFile $file
     * @param int $pedidoId
     * @param string $tipo (prendas|telas|procesos|epp)
     * @param string|null $subtipo (para procesos: reflectivo, bordado, etc)
     * @return string Ruta guardada relativa a storage/app/public
     */
    public function guardarImagen(
        UploadedFile $file,
        int $pedidoId,
        string $tipo,
        ?string $subtipo = null
    ): string {
        // Validar UploadedFile
        if (!$file instanceof UploadedFile) {
            throw new \InvalidArgumentException('Archivo debe ser UploadedFile');
        }
        
        if (!$file->isValid()) {
            throw new \RuntimeException('Archivo inválido: ' . $file->getErrorMessage());
        }
        
        // Construir ruta
        $rutaBase = sprintf('%s/%d/%s', self::BASE_PATH, $pedidoId, $tipo);
        if ($subtipo) {
            $rutaBase .= '/' . $subtipo;
        }
        
        // Crear directorio si no existe
        if (!Storage::disk(self::DISK)->exists($rutaBase)) {
            Storage::disk(self::DISK)->makeDirectory($rutaBase);
        }
        
        // Guardar archivo
        try {
            $ruta = $file->store($rutaBase, self::DISK);
            
            Log::info('[ImagenPedidoService] Imagen guardada', [
                'pedido_id' => $pedidoId,
                'tipo' => $tipo,
                'subtipo' => $subtipo,
                'ruta' => $ruta,
            ]);
            
            return $ruta;
        } catch (\Exception $e) {
            Log::error('[ImagenPedidoService] Error guardando', [
                'pedido_id' => $pedidoId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
```

---

### ✅ SOLUCIÓN 2: Corregir CrearPedidoService

**Archivo:** `app/Application/Services/Asesores/CrearPedidoService.php`

**Cambio línea 202:**

```php
//  ANTES:
$rutaGuardada = $archivoFoto->store('prendas/telas', 'public');

// ✅ DESPUÉS:
$rutaGuardada = $this->imagenService->guardarImagen(
    $archivoFoto,
    $pedidoId,  // Necesario obtener del request
    'telas'
);
```

---

### ✅ SOLUCIÓN 3: Corregir ProcesarFotosTelasService

**Línea 98:**

```php
//  ANTES:
$rutaGuardada = $archivoFoto->store('telas/pedidos', 'public');

// ✅ DESPUÉS:
$rutaGuardada = $this->imagenService->guardarImagen(
    $archivoFoto,
    $pedidoId,
    'telas'
);
```

---

### ✅ SOLUCIÓN 4: Habilitar PedidoWebService.guardarImagenesProceso()

**Línea 598-660:**

```php
//  ANTES:
private function guardarImagenesProceso(...) {
    // ...
    return;  // DESHABILITADA
    // ... código comentado ...
}

// ✅ DESPUÉS:
private function guardarImagenesProceso(PedidosProcesosPrendaDetalle $proceso, array $imagenes): void
{
    if (empty($imagenes)) {
        return;
    }

    try {
        $prenda = $proceso->prenda;
        if (!$prenda) {
            Log::warning('[PedidoWebService] No se pudo obtener prenda para guardar imágenes');
            return;
        }

        $pedidoId = $prenda->pedido_produccion_id;
        $nombreProceso = $proceso->proceso->nombre ?? 'proceso';

        foreach ($imagenes as $index => $imagen) {
            if ($imagen instanceof UploadedFile) {
                $ruta = $this->imagenService->guardarImagen(
                    $imagen,
                    $pedidoId,
                    'procesos',
                    $nombreProceso
                );

                PedidosProcessImagenes::create([
                    'proceso_prenda_detalle_id' => $proceso->id,
                    'ruta_original' => $ruta,
                    'ruta_webp' => str_replace(['.jpg', '.png'], '.webp', $ruta),
                    'orden' => $index + 1,
                    'es_principal' => $index === 0 ? 1 : 0,
                ]);
            }
        }

        Log::info('[PedidoWebService] Imágenes de procesos guardadas', [
            'proceso_id' => $proceso->id,
            'pedido_id' => $pedidoId,
            'cantidad' => count($imagenes),
        ]);
    } catch (\Exception $e) {
        Log::error('[PedidoWebService] Error guardando imágenes proceso', [
            'error' => $e->getMessage(),
        ]);
        throw $e;
    }
}
```

---

### ✅ SOLUCIÓN 5: Corregir EppController

**Línea 258:**

```php
//  ANTES:
$ruta = $imagen->store('pedidos/epp', 'public');

// ✅ DESPUÉS:
$ruta = $this->imagenService->guardarImagen(
    $imagen,
    $pedidoId,
    'epp'
);
```

---

## 📋 ESTRUCTURA FINAL ESPERADA

```
storage/app/public/
├── pedido/
│   ├── 1/
│   │   ├── prendas/           ← Fotos de prendas
│   │   │   ├── prenda_1.webp
│   │   │   └── prenda_2.webp
│   │   ├── telas/            ← Fotos de telas
│   │   │   ├── tela_1.webp
│   │   │   └── tela_2.webp
│   │   ├── procesos/         ← Imágenes de procesos
│   │   │   ├── reflectivo/
│   │   │   │   ├── reflectivo_1.webp
│   │   │   │   └── reflectivo_2.webp
│   │   │   └── bordado/
│   │   │       ├── bordado_1.webp
│   │   │       └── bordado_2.webp
│   │   └── epp/              ← Imágenes de EPP
│   │       ├── epp_1.webp
│   │       └── epp_2.webp
│   │
│   ├── 2/
│   │   ├── prendas/
│   │   ├── telas/
│   │   ├── procesos/
│   │   └── epp/
│   └── ...
│
├── temp/                     ← Procesamiento temporal
│   └── {uuid}/
└── epp/                      ← Catálogo EPP (no por pedido)
    └── {codigo}/
```

---

## ✅ CHECKLIST DE IMPLEMENTACIÓN

- [ ] Crear `ImagenPedidoService.php`
- [ ] Inyectar en CrearPedidoService
- [ ] Inyectar en ProcesarFotosTelasService
- [ ] Inyectar en PedidoWebService
- [ ] Inyectar en EppController
- [ ] Remover `return;` de guardarImagenesProceso()
- [ ] Descomentar código de procesos
- [ ] Testing: Crear pedido con prendas
- [ ] Testing: Verificar rutas `pedido/{id}/prendas/`
- [ ] Testing: Crear con procesos
- [ ] Testing: Verificar rutas `pedido/{id}/procesos/{tipo}/`
- [ ] Testing: Agregar EPP
- [ ] Testing: Verificar rutas `pedido/{id}/epp/`
- [ ] Verificar logs sin errores

---

## 🔍 VALIDACIÓN RÁPIDA

Después de implementar, ejecutar:

```bash
# Buscar archivos en carpetas genéricas
ls storage/app/public/prendas/ 2>/dev/null | wc -l
ls storage/app/public/telas/ 2>/dev/null | wc -l
ls storage/app/public/procesos/ 2>/dev/null | wc -l

# Deben estar en pedido/{id}/
ls storage/app/public/pedido/*/prendas/ 2>/dev/null | wc -l
ls storage/app/public/pedido/*/telas/ 2>/dev/null | wc -l
ls storage/app/public/pedido/*/procesos/ 2>/dev/null | wc -l
```

---

## 📝 RESUMEN FINAL

| Aspecto | Status | Acción |
|--------|--------|--------|
| **Prendas guardadas genéricamente** | 🔴 CRÍTICA | Migrar a `pedido/{id}/prendas/` |
| **Telas guardadas genéricamente** | 🔴 CRÍTICA | Migrar a `pedido/{id}/telas/` |
| **Procesos NO se guardan** | 🔴 CRÍTICA | Habilitar + migrar a `pedido/{id}/procesos/` |
| **EPP guardadas genéricamente** | 🔴 CRÍTICA | Migrar a `pedido/{id}/epp/` |
| **Sin validación UploadedFile** | 🔴 CRÍTICA | Agregar en servicio centralizado |
| **Múltiples servicios sin coordinación** | 🔴 CRÍTICA | Crear ImagenPedidoService |

**Resultado esperado:** ✅ Todas las imágenes organizadas por pedido con estructura clara y mantenible.
