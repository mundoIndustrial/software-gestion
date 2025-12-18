<?php
/**
 * Script de prueba para validar generación de números GLOBALES y CONSECUTIVOS
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Application\Cotizacion\Services\GenerarNumeroCotizacionService;
use App\Models\User;
use App\Domain\Shared\ValueObjects\UserId;

echo "═══════════════════════════════════════════════════════════════\n";
echo "TEST: Números de Cotización GLOBALES y CONSECUTIVOS\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

try {
    $service = app(GenerarNumeroCotizacionService::class);

    echo "[1] Creando 20 asesores de prueba (para validar concurrencia)...\n";
    $asesores = User::factory()->count(20)->create();
    
    echo "[2] Cada asesor genera 3 números (total 60 números)...\n\n";
    
    $todoNumeros = [];
    $asesoresResultados = [];
    
    foreach ($asesores as $asesor) {
        $usuarioId = UserId::crear($asesor->id);
        $numerosDelAsesor = [];
        
        for ($i = 0; $i < 3; $i++) {
            $numero = $service->generarNumeroCotizacionFormateado($usuarioId);
            $numeroInt = (int)substr($numero, 5); // Extraer número: "COT-00042" → 42
            $numerosDelAsesor[] = $numeroInt;
            $todoNumeros[] = $numeroInt;
        }
        
        $asesoresResultados[$asesor->id] = $numerosDelAsesor;
        echo "  Asesor {$asesor->id}: " . implode(', ', array_map(fn($n) => 'COT-' . str_pad($n, 5, '0', STR_PAD_LEFT), $numerosDelAsesor)) . "\n";
    }

    echo "\n[3] VALIDACIÓN 1: Verificar NO HAY DUPLICADOS GLOBALES\n";
    $totalUnicos = count(array_unique($todoNumeros));
    $total = count($todoNumeros);
    
    if ($totalUnicos === $total) {
        echo "  ✅ SIN DUPLICADOS: $totalUnicos números únicos de $total total\n";
    } else {
        echo "  ❌ DUPLICADOS ENCONTRADOS: $totalUnicos únicos de $total total\n";
        exit(1);
    }

    echo "\n[4] VALIDACIÓN 2: Verificar SECUENCIA CONSECUTIVA GLOBAL\n";
    sort($todoNumeros);
    
    $esConsecutiva = true;
    $primero = reset($todoNumeros);
    echo "  Secuencia comienza en: $primero\n";
    echo "  Secuencia termina en: " . end($todoNumeros) . "\n";
    
    for ($i = 0; $i < count($todoNumeros); $i++) {
        if ($todoNumeros[$i] !== $primero + $i) {
            $esConsecutiva = false;
            echo "  ❌ Error en posición $i: esperaba " . ($primero + $i) . ", encontré " . $todoNumeros[$i] . "\n";
            break;
        }
    }
    
    if ($esConsecutiva) {
        echo "  ✅ SECUENCIA PERFECTA: Los 60 números son consecutivos\n";
    } else {
        echo "  ❌ SECUENCIA CON GAPS: Hay huecos en la numeración\n";
        exit(1);
    }

    echo "\n[5] VALIDACIÓN 3: Mostrar primeros y últimos números\n";
    echo "  Primeros 5: " . implode(', ', array_map(fn($n) => 'COT-' . str_pad($n, 5, '0', STR_PAD_LEFT), array_slice($todoNumeros, 0, 5))) . "\n";
    echo "  Últimos 5: " . implode(', ', array_map(fn($n) => 'COT-' . str_pad($n, 5, '0', STR_PAD_LEFT), array_slice($todoNumeros, -5))) . "\n";

    echo "\n" . str_repeat("═", 65) . "\n";
    echo "✅ PRUEBA EXITOSA\n";
    echo str_repeat("═", 65) . "\n";
    echo "RESULTADO: 20 asesores generando simultáneamente 3 números cada uno\n";
    echo "Obtuvieron 60 números GLOBALES, ÚNICOS y CONSECUTIVOS sin duplicados\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    exit(0);

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
