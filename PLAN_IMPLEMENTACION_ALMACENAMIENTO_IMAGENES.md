# 📋 PLAN DE IMPLEMENTACIÓN - Fixes de Almacenamiento de Imágenes

## 🎯 Objectivo
Migrar del sistema fracturado de almacenamiento genérico a un sistema CENTRALIZADO por pedido.

---

## ✅ CAMBIOS REQUERIDOS

### CAMBIO 1: CrearPedidoService.php (Línea 202)

**Ubicación:** `app/Application/Services/Asesores/CrearPedidoService.php`

**Línea actual (ANTES):**
```php
190-210: if (!empty($producto['telas']) && is_array($producto['telas'])) {
    $telasProcessadas = [];

    foreach ($producto['telas'] as $telaIndex => $tela) {
        $telasProcessadas[$telaIndex] = $tela;
        $fotosProcessadas = [];

        $fotosKey = "productos_friendly.{$productoIndex}.telas.{$telaIndex}.fotos";

        if (!empty($archivos[$fotosKey])) {
            foreach ($archivos[$fotosKey] as $fotoIndex => $archivoFoto) {
                if ($archivoFoto->isValid()) {
                    $rutaGuardada = $archivoFoto->store('prendas/telas', 'public');  //  GENÉRICA
```

**Cambio requerido (DESPUÉS):**

1. Agregar inyección en constructor o method
2. Cambiar `store()` a usar `ImagenPedidoService`

```php
// En __construct:
public function __construct(
    // ... otros servicios ...
    private ImagenPedidoService $imagenPedidoService,
) {}

// En línea 202:
if ($archivoFoto->isValid()) {
    // ✅ Usar servicio centralizado
    $rutaGuardada = $this->imagenPedidoService->guardarImagen(
        $archivoFoto,
        $pedidoId,  // Necesario obtener del payload
        'telas'
    );
```

**Nota:** Necesitarás extraer `$pedidoId` del contexto de la request.

---

### CAMBIO 2: ProcesarFotosTelasService.php (Línea 98)

**Ubicación:** `app/Application/Services/Asesores/ProcesarFotosTelasService.php`

**Línea actual (ANTES):**
```php
95-105: private function guardarFotos(array $archivos): array
{
    $fotosGuardadas = [];

    foreach ($archivos as $archivoFoto) {
        if ($archivoFoto && $archivoFoto->isValid()) {
            try {
                $rutaGuardada = $archivoFoto->store('telas/pedidos', 'public');  //  GENÉRICA
```

**Cambio requerido (DESPUÉS):**

```php
// En método guardarFotos, agregar parámetro:
private function guardarFotos(array $archivos, int $pedidoId): array
{
    $fotosGuardadas = [];

    foreach ($archivos as $archivoFoto) {
        if ($archivoFoto && $archivoFoto->isValid()) {
            try {
                // ✅ Usar servicio centralizado
                $rutaGuardada = $this->imagenPedidoService->guardarImagen(
                    $archivoFoto,
                    $pedidoId,
                    'telas'
                );
```

**Y actualizar llamada a guardarFotos():**
```php
// Línea donde se llama guardarFotos:
//  ANTES: $fotosGuardadas = $this->guardarFotos($archivos);
// ✅ DESPUÉS:
$fotosGuardadas = $this->guardarFotos($archivos, $pedidoId);
```

---

### CAMBIO 3: PedidoWebService.php (Línea 598-615)

**Ubicación:** `app/Domain/Pedidos/Services/PedidoWebService.php`

**Código ACTUAL (ANTES) - DESHABILITADO:**
```php
598-615:
    private function guardarImagenesProceso(PedidosProcesosPrendaDetalle $proceso, array $imagenes): void
    {
        // ... setup ...
        
        Log::debug('[PedidoWebService] guardarImagenesProceso: SKIP processing', [...]);

        return;  //  SE RETORNA AQUÍ SIN GUARDAR
        
        //  TODO EL RESTO ESTÁ COMENTADO
        /*
        foreach ($imagenes as $index => $imagen) {
            ...
        }
        */
    }
```

**Cambio requerido (DESPUÉS):**

REEMPLAZAR COMPLETAMENTE la función:

```php
private function guardarImagenesProceso(PedidosProcesosPrendaDetalle $proceso, array $imagenes): void
{
    if (empty($imagenes)) {
        return;
    }

    try {
        $prenda = $proceso->prenda;
        if (!$prenda) {
            Log::warning('[PedidoWebService] No se pudo obtener prenda para guardar imágenes proceso');
            return;
        }

        $pedidoId = $prenda->pedido_produccion_id;
        $nombreProceso = $proceso->proceso->nombre ?? 'proceso';

        foreach ($imagenes as $index => $imagen) {
            if ($imagen instanceof UploadedFile) {
                // ✅ Usar servicio centralizado
                $ruta = $this->imagenPedidoService->guardarImagen(
                    $imagen,
                    $pedidoId,
                    'procesos',
                    $nombreProceso
                );

                PedidosProcessImagenes::create([
                    'proceso_prenda_detalle_id' => $proceso->id,
                    'ruta_original' => $ruta,
                    'ruta_webp' => str_replace(['.jpg', '.png', '.jpeg'], '.webp', $ruta),
                    'orden' => $index + 1,
                    'es_principal' => $index === 0 ? 1 : 0,
                ]);
                
                Log::debug('[PedidoWebService] Imagen de proceso guardada', [
                    'proceso_id' => $proceso->id,
                    'pedido_id' => $pedidoId,
                    'tipo_proceso' => $nombreProceso,
                    'ruta' => $ruta,
                ]);
            }
        }

        Log::info('[PedidoWebService] Imágenes de procesos guardadas correctamente', [
            'proceso_id' => $proceso->id,
            'pedido_id' => $pedidoId,
            'cantidad' => count($imagenes),
            'tipo_proceso' => $nombreProceso,
        ]);
    } catch (\Exception $e) {
        Log::error('[PedidoWebService] Error guardando imágenes de proceso', [
            'proceso_id' => $proceso->id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
        throw $e;
    }
}
```

**Importante:** 
- Agregar `use Illuminate\Http\UploadedFile;` al inicio
- Inyectar `ImagenPedidoService` en el constructor

---

### CAMBIO 4: EppController.php (Línea 258)

**Ubicación:** `app/Infrastructure/Http/Controllers/Epp/EppController.php`

**Código ACTUAL (ANTES):**
```php
255-265: if ($request->hasFile('imagenes')) {
    foreach ($request->file('imagenes') as $imagen) {
        if ($imagen->isValid()) {
            // Guardar imagen y obtener ruta
            $ruta = $imagen->store('pedidos/epp', 'public');  //  GENÉRICA
            $imagenes[] = $ruta;
        }
    }
}
```

**Cambio requerido (DESPUÉS):**

```php
if ($request->hasFile('imagenes')) {
    // ✅ Usar servicio centralizado
    $imagenes = $this->imagenPedidoService->guardarMultiplesImagenes(
        $request->file('imagenes'),
        $pedidoId,
        'epp'
    );
}
```

---

## 📋 INYECCIONES REQUERIDAS

### Para CrearPedidoService:

```php
// En app/Application/Services/Asesores/CrearPedidoService.php

// Agregar use:
use App\Application\Services\ImagenPedidoService;

// En constructor:
public function __construct(
    // ... otros servicios ...
    private ImagenPedidoService $imagenPedidoService,
) {}
```

### Para ProcesarFotosTelasService:

```php
// En app/Application/Services/Asesores/ProcesarFotosTelasService.php

// Agregar use:
use App\Application\Services\ImagenPedidoService;

// En constructor:
public function __construct(
    // ... otros servicios ...
    private ImagenPedidoService $imagenPedidoService,
) {}
```

### Para PedidoWebService:

```php
// En app/Domain/Pedidos/Services/PedidoWebService.php

// Agregar use:
use App\Application\Services\ImagenPedidoService;
use Illuminate\Http\UploadedFile;

// En constructor:
public function __construct(
    // ... otros servicios ...
    private ImagenPedidoService $imagenPedidoService,
) {}
```

### Para EppController:

```php
// En app/Infrastructure/Http/Controllers/Epp/EppController.php

// Agregar use:
use App\Application\Services\ImagenPedidoService;

// En constructor (__construct):
private ImagenPedidoService $imagenPedidoService;

public function __construct(
    // ... otros servicios ...
    ImagenPedidoService $imagenPedidoService,
) {
    // ... otros assignments ...
    $this->imagenPedidoService = $imagenPedidoService;
}
```

---

## ✅ VALIDACIÓN POST-IMPLEMENTACIÓN

### 1. Verificar que archivos se guardan en rutas correctas:

```bash
# Prendas
ls -la storage/app/public/pedido/*/prendas/ | head -20

# Telas
ls -la storage/app/public/pedido/*/telas/ | head -20

# Procesos
ls -la storage/app/public/pedido/*/procesos/ | head -20

# EPP
ls -la storage/app/public/pedido/*/epp/ | head -20
```

### 2. Verificar que NO quedan archivos en rutas genéricas:

```bash
# Estos directorios DEBEN estar vacíos o no existir
ls storage/app/public/prendas/ 2>/dev/null | wc -l  # Debe ser 0
ls storage/app/public/telas/ 2>/dev/null | wc -l    # Debe ser 0
ls storage/app/public/procesos/ 2>/dev/null | wc -l # Debe ser 0
ls storage/app/public/pedidos/epp 2>/dev/null | wc -l # Debe ser 0
```

### 3. Testing en BD:

```sql
-- Verificar que rutas en BD coinciden con rutas reales
SELECT ruta_original, ruta_webp FROM pedidos_process_imagenes LIMIT 5;
-- Deben ser tipo: pedido/123/procesos/reflectivo/img.webp

SELECT ruta_web FROM pedido_epp_imagenes LIMIT 5;
-- Deben ser tipo: /storage/pedido/123/epp/img.webp
```

### 4. Testing en logs:

```bash
# Ver logs de guardado exitoso
tail -f storage/logs/laravel.log | grep "ImagenPedidoService.*Imagen guardada"
```

---

##  ORDEN DE IMPLEMENTACIÓN

1. ✅ **Crear** `ImagenPedidoService.php` (YA HECHO)
2. ⏳ **Actualizar** `CrearPedidoService.php` 
3. ⏳ **Actualizar** `ProcesarFotosTelasService.php`
4. ⏳ **Actualizar** `PedidoWebService.php` (CRÍTICA)
5. ⏳ **Actualizar** `EppController.php`
6. ⏳ **Testing** Crear pedido con todas las imágenes
7. ⏳ **Validar** Rutas en storage
8. ⏳ **Limpiar** Carpetas genéricas antiguas

---

##  NOTAS IMPORTANTES

- **No modificar** estructura de BD (solo las rutas guardadas)
- **Mantener** compatibilidad con rutas antiguas en migration si es necesario
- **Loguear** todos los cambios de ruta
- **Testing** exhaustivo ANTES de producción
