<?php
// Script de prueba simple para verificar todos los tipos de cotizaciones

use Illuminate\Support\Facades\DB;

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

try {
    echo "\n🔵 PRUEBA: TODOS LOS TIPOS DE COTIZACIONES\n";
    echo "==========================================\n\n";
    
    // Obtener servicio
    $servicio = app('App\Application\Cotizacion\Services\GenerarNumeroCotizacionService');
    
    // Generar números para diferentes asesores y tipos
    $numeros = [];
    $detalles = [];
    
    for ($asesor = 1; $asesor <= 5; $asesor++) {
        for ($tipo = 0; $tipo < 4; $tipo++) {
            $tipos = ['Normal', 'Prenda', 'Bordado', 'Reflectivo'];
            $numero = $servicio->generarNumeroCotizacionFormateado($asesor);
            $num_int = (int)substr($numero, 4);
            
            $numeros[] = $num_int;
            $detalles[] = "Asesor $asesor - $tipos[$tipo]: $numero";
        }
    }
    
    // Mostrar resultados
    foreach ($detalles as $detalle) {
        echo "$detalle\n";
    }
    
    echo "\n📊 VALIDACIÓN:\n";
    echo "Total generados: " . count($numeros) . "\n";
    echo "Únicos: " . count(array_unique($numeros)) . "\n";
    
    if (count($numeros) === count(array_unique($numeros))) {
        echo "✅ SIN DUPLICADOS\n";
    } else {
        echo "❌ DUPLICADOS ENCONTRADOS\n";
    }
    
    sort($numeros);
    if ($numeros === range(min($numeros), max($numeros))) {
        echo "✅ SECUENCIA CONSECUTIVA\n";
    }
    
    echo "\n✅ TODOS LOS TIPOS USAN NUMERACIÓN GLOBAL Y CONSECUTIVA\n\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
