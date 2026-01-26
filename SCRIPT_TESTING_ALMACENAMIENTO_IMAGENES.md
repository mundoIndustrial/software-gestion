# 🧪 SCRIPT TESTING - Validar Sistema de Almacenamiento

## Test 1: Verificar Carpetas por Pedido

```bash
#!/bin/bash

PEDIDO_ID=1

echo "🔍 Buscando archivos en pedido $PEDIDO_ID..."

echo -e "\n📁 Prendas:"
find storage/app/public/pedido/$PEDIDO_ID/prendas -type f 2>/dev/null | wc -l

echo -e "\n📁 Telas:"
find storage/app/public/pedido/$PEDIDO_ID/telas -type f 2>/dev/null | wc -l

echo -e "\n📁 Procesos:"
find storage/app/public/pedido/$PEDIDO_ID/procesos -type f 2>/dev/null | wc -l

echo -e "\n📁 EPP:"
find storage/app/public/pedido/$PEDIDO_ID/epp -type f 2>/dev/null | wc -l

echo -e "\n✅ Total por pedido:"
find storage/app/public/pedido/$PEDIDO_ID -type f 2>/dev/null | wc -l
```

---

## Test 2: Verificar NO existen Carpetas Genéricas

```bash
#!/bin/bash

echo "🔴 Verificando que NO existen carpetas genéricas..."

CARPETAS_GENÉRICAS=(
    "storage/app/public/prendas"
    "storage/app/public/telas"
    "storage/app/public/procesos"
    "storage/app/public/pedidos/epp"
    "storage/app/public/prendas/telas"
    "storage/app/public/telas/pedidos"
)

for carpeta in "${CARPETAS_GENÉRICAS[@]}"; do
    if [ -d "$carpeta" ]; then
        archivos=$(find "$carpeta" -type f 2>/dev/null | wc -l)
        echo " $carpeta: $archivos archivos"
        if [ $archivos -gt 0 ]; then
            echo "   📋 Contenido:"
            find "$carpeta" -type f | head -5
        fi
    else
        echo "✅ $carpeta: NO existe (correcto)"
    fi
done
```

---

## Test 3: Verificar BD vs Almacenamiento

```sql
-- Verificar que rutas en BD existen en storage

-- PROCESOS:
SELECT 
    pppi.id,
    pppi.proceso_prenda_detalle_id,
    pppi.ruta_original,
    pppi.ruta_webp
FROM pedidos_process_imagenes pppi
LIMIT 5;

-- Verificar formato esperado: pedido/123/procesos/reflectivo/img.webp

-- EPP:
SELECT 
    pei.id,
    pei.pedido_epp_id,
    pei.ruta_web,
    pei.ruta_original
FROM pedido_epp_imagenes pei
LIMIT 5;

-- Verificar formato esperado: /storage/pedido/123/epp/img.webp
```

---

## Test 4: Testing de Logs

```bash
#!/bin/bash

echo "📋 Últimas operaciones de ImagenPedidoService..."

tail -f storage/logs/laravel.log | grep -E "ImagenPedidoService|Imagen (guardada|eliminada)" | head -20
```

---

## Test 5: PHP Testing

```php
<?php

namespace Tests\Feature;

use App\Application\Services\ImagenPedidoService;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ImagenPedidoServiceTest extends TestCase
{
    private ImagenPedidoService $service;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ImagenPedidoService::class);
    }
    
    /** @test */
    public function puede_guardar_imagen_prendas()
    {
        $file = UploadedFile::fake()->image('prenda.jpg');
        $pedidoId = 1;
        
        $ruta = $this->service->guardarImagen($file, $pedidoId, 'prendas');
        
        $this->assertStringContainsString('pedido/1/prendas', $ruta);
        Storage::disk('public')->assertExists($ruta);
    }
    
    /** @test */
    public function puede_guardar_imagen_procesos_con_subtipo()
    {
        $file = UploadedFile::fake()->image('reflectivo.jpg');
        $pedidoId = 1;
        
        $ruta = $this->service->guardarImagen(
            $file, 
            $pedidoId, 
            'procesos', 
            'reflectivo'
        );
        
        $this->assertStringContainsString('pedido/1/procesos/reflectivo', $ruta);
        Storage::disk('public')->assertExists($ruta);
    }
    
    /** @test */
    public function puede_guardar_multiples_imagenes()
    {
        $files = [
            UploadedFile::fake()->image('epp1.jpg'),
            UploadedFile::fake()->image('epp2.jpg'),
        ];
        $pedidoId = 1;
        
        $rutas = $this->service->guardarMultiplesImagenes(
            $files,
            $pedidoId,
            'epp'
        );
        
        $this->assertCount(2, $rutas);
        foreach ($rutas as $ruta) {
            Storage::disk('public')->assertExists($ruta);
        }
    }
    
    /** @test */
    public function rechaza_archivo_inválido()
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $file = 'no es un archivo';
        $pedidoId = 1;
        
        $this->service->guardarImagen($file, $pedidoId, 'prendas');
    }
    
    /** @test */
    public function rechaza_tipo_inválido()
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $file = UploadedFile::fake()->image('test.jpg');
        $pedidoId = 1;
        
        $this->service->guardarImagen($file, $pedidoId, 'tipo_invalido');
    }
}
```

---

## Test 6: Manual Testing Workflow

### Paso 1: Crear Pedido con Prendas

```bash
# Ir a: http://localhost:8000/asesores/pedidos/crear

# Llena formulario:
# - Cliente: Test
# - Prenda: Polo
# - Tela: Algodón
# - Sube foto de tela

# Verifica en terminal:
tail -f storage/logs/laravel.log | grep "ImagenPedidoService"

# Debería ver algo como:
# [2026-01-26 10:00:00] local.INFO: [ImagenPedidoService] Imagen guardada 
# {"pedido_id":123,"tipo":"telas","ruta":"pedido/123/telas/..."}
```

### Paso 2: Verificar Carpeta

```bash
# Abre file explorer o terminal:
ls -la storage/app/public/pedido/123/telas/

# Debe ver archivos como:
# - imagen.jpg (original)
# - imagen.webp (convertida)
```

### Paso 3: Crear Proceso

```bash
# En la misma orden, agrega un proceso:
# - Tipo: Reflectivo
# - Ubicaciones: pecho
# - Sube 2 imágenes

# Verifica:
ls -la storage/app/public/pedido/123/procesos/reflectivo/

# Debe ver 2 archivos webp
```

### Paso 4: Agregar EPP

```bash
# En la misma orden, agrega EPP:
# - EPP: Casco
# - Cantidad: 5
# - Sube 1 imagen

# Verifica:
ls -la storage/app/public/pedido/123/epp/

# Debe ver archivo webp
```

### Paso 5: Verificar BD

```bash
# Terminal:
php artisan tinker

# Buscar proceso:
$proceso = \App\Models\PedidosProcesosPrendaDetalle::first();
$proceso->imagenes()->get();

# Debe tener ruta: pedido/123/procesos/reflectivo/...

# Buscar EPP:
$epp = \App\Models\PedidoEpp::first();
$epp->imagenes()->get();

# Debe tener ruta: pedido/123/epp/...
```

---

## Test 7: Limpiar Carpetas Antiguas

```bash
#!/bin/bash

echo "🧹 Limpiando carpetas genéricas antiguas..."

# RESPALDAR PRIMERO
echo "📦 Respaldando..."
tar -czf backup_old_images.tar.gz storage/app/public/prendas storage/app/public/telas storage/app/public/procesos 2>/dev/null

# ELIMINAR
echo "🗑️ Eliminando..."
rm -rf storage/app/public/prendas
rm -rf storage/app/public/telas
rm -rf storage/app/public/procesos
rm -rf storage/app/public/pedidos/epp

echo "✅ Limpieza completada"
```

---

## Test 8: Permiso de Lectura

```bash
#!/bin/bash

echo "🔐 Verificando permisos..."

# Los archivos deben ser legibles por web
ls -la storage/app/public/pedido/*/prendas/ 2>/dev/null | head -10

# Deben tener permisos 644 (rw-r--r--)
# Si no, ejecutar:
chmod -R 644 storage/app/public/pedido/

# Directorios deben tener 755 (rwxr-xr-x)
chmod -R 755 storage/app/public/pedido/
```

---

## Test 9: Verificar URLs Públicas

```bash
# En navegador, verifica que las URLs funcionen:

# Prendas:
http://localhost:8000/storage/pedido/123/prendas/imagen.webp

# Procesos:
http://localhost:8000/storage/pedido/123/procesos/reflectivo/imagen.webp

# EPP:
http://localhost:8000/storage/pedido/123/epp/imagen.webp

# Todas deben mostrar la imagen sin 404
```

---

## Test 10: Simular Fallo de Imagen

```php
<?php

// Test en tinker:
$service = app(\App\Application\Services\ImagenPedidoService::class);

// Esto debe fallar:
try {
    $service->guardarImagen('no es archivo', 1, 'prendas');
} catch (\InvalidArgumentException $e) {
    echo "✅ Excepción capturada: " . $e->getMessage();
}

// Ver en logs:
// [ERROR] Archivo debe ser instancia de UploadedFile
```

---

## Resumen de Validación

```bash
#!/bin/bash

echo "📊 RESUMEN DE VALIDACIÓN"
echo "=========================="
echo ""
echo "✅ Archivos por pedido:"
echo "   Prendas: $(find storage/app/public/pedido/*/prendas -type f 2>/dev/null | wc -l)"
echo "   Telas:   $(find storage/app/public/pedido/*/telas -type f 2>/dev/null | wc -l)"
echo "   Procesos:$(find storage/app/public/pedido/*/procesos -type f 2>/dev/null | wc -l)"
echo "   EPP:     $(find storage/app/public/pedido/*/epp -type f 2>/dev/null | wc -l)"
echo ""
echo " Archivos en carpetas genéricas:"
echo "   prendas/        : $(find storage/app/public/prendas -type f 2>/dev/null | wc -l)"
echo "   telas/          : $(find storage/app/public/telas -type f 2>/dev/null | wc -l)"
echo "   procesos/       : $(find storage/app/public/procesos -type f 2>/dev/null | wc -l)"
echo "   pedidos/epp/    : $(find storage/app/public/pedidos/epp -type f 2>/dev/null | wc -l)"
echo ""
echo "📈 Total en sistema correcto:"
echo "   $(find storage/app/public/pedido -type f 2>/dev/null | wc -l) archivos en pedido/{id}/"
echo ""
```

---

## ✅ Criterios de Éxito

- [ ] TODAS las imágenes en `pedido/{id}/{tipo}/`
- [ ] CERO archivos en carpetas genéricas
- [ ] Procesos con imágenes visibles
- [ ] EPP con imágenes guardadas
- [ ] URLs públicas funcionales
- [ ] Logs sin errores
- [ ] Testing exitoso
