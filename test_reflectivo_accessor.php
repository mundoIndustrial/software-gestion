<?php
require 'vendor/autoload.php';

// Cargar Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Models\ReflectivoCotizacion;

// Buscar CUALQUIER reflectivo con fotos
$reflectivos = \App\Models\ReflectivoCotizacion::with('fotos')->get();
echo "Total de reflectivos: " . count($reflectivos) . "\n";

$reflectivosConFotos = $reflectivos->filter(fn($r) => $r->fotos->count() > 0);
echo "Reflectivos con fotos: " . count($reflectivosConFotos) . "\n\n";

if (count($reflectivosConFotos) > 0) {
    $reflectivo = $reflectivosConFotos->first();
    if ($reflectivo && $reflectivo->fotos->count() > 0) {
        echo "✅ Encontrado reflectivo con fotos\n";
        echo "=================================\n\n";
        
        echo "Fotos en la relación: " . count($reflectivo->fotos) . "\n";
        
        // Test 1: Verificar accessor en modelo individual
        $primeraFoto = $reflectivo->fotos->first();
        echo "\n📸 TEST 1: Accessor en modelo individual\n";
        echo "  Ruta original: " . $primeraFoto->ruta_original . "\n";
        echo "  Ruta WebP: " . $primeraFoto->ruta_webp . "\n";
        echo "  Accessor URL: " . $primeraFoto->url . "\n";
        echo "  \$appends: " . implode(', ', $primeraFoto->getAppends()) . "\n";
        
        // Test 2: toArray() de una foto individual
        echo "\n📸 TEST 2: toArray() de una foto individual\n";
        $fotoArray = $primeraFoto->toArray();
        echo "  Keys en array: " . implode(', ', array_keys($fotoArray)) . "\n";
        echo "  ¿Tiene 'url'?: " . (isset($fotoArray['url']) ? 'SÍ ✅' : 'NO ❌') . "\n";
        if (isset($fotoArray['url'])) {
            echo "  Valor de 'url': " . $fotoArray['url'] . "\n";
        }
        
        // Test 3: toArray() de la colección
        echo "\n📸 TEST 3: toArray() de la colección\n";
        $fotosArray = $reflectivo->fotos->toArray();
        echo "  Count: " . count($fotosArray) . "\n";
        if (count($fotosArray) > 0) {
            echo "  Keys en primer elemento: " . implode(', ', array_keys($fotosArray[0])) . "\n";
            echo "  ¿Primer elemento tiene 'url'?: " . (isset($fotosArray[0]['url']) ? 'SÍ ✅' : 'NO ❌') . "\n";
            if (isset($fotosArray[0]['url'])) {
                echo "  Valor de 'url': " . $fotosArray[0]['url'] . "\n";
            }
        }
        
        // Test 4: json_encode
        echo "\n📸 TEST 4: json_encode de la colección\n";
        $fotosJson = json_encode($reflectivo->fotos);
        $fotosDecodificadas = json_decode($fotosJson, true);
        if (count($fotosDecodificadas) > 0) {
            echo "  Keys en primer elemento: " . implode(', ', array_keys($fotosDecodificadas[0])) . "\n";
            echo "  ¿Primer elemento tiene 'url'?: " . (isset($fotosDecodificadas[0]['url']) ? 'SÍ ✅' : 'NO ❌') . "\n";
            if (isset($fotosDecodificadas[0]['url'])) {
                echo "  Valor de 'url': " . $fotosDecodificadas[0]['url'] . "\n";
            }
        }
        
    } else {
        echo "❌ No hay fotos en el reflectivo\n";
    }
} else {
    echo "❌ No se encontró reflectivo con fotos\n";
}
?>
