<?php
/**
 * Test exhaustivo: 20 asesores generando números simultáneamente
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Application\Cotizacion\Services\GenerarNumeroCotizacionService;
use App\Models\User;
use App\Domain\Shared\ValueObjects\UserId;

echo "═══════════════════════════════════════════════════════════════\n";
echo "TEST EXHAUSTIVO: 20 Asesores Generando Números\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

try {
    $service = app(GenerarNumeroCotizacionService::class);

    echo "[1] Creando 20 asesores...\n";
    $asesores = User::factory()->count(20)->create();
    echo "    ✓ {$asesores->count()} asesores creados\n";
    
    echo "\n[2] Cada asesor genera 5 números...\n";
    
    $resultados = [];
    $numerosPerAsessor = 5;
    
    foreach ($asesores as $asesor) {
        $usuarioId = UserId::crear($asesor->id);
        $numeros = [];
        
        for ($i = 0; $i < $numerosPerAsessor; $i++) {
            $numero = $service->generarNumeroCotizacionFormateado($usuarioId);
            $numeros[] = $numero;
        }
        
        $resultados[$asesor->id] = $numeros;
    }
    
    echo "    ✓ Total: " . (count($asesores) * $numerosPerAsessor) . " números generados\n";

    echo "\n[3] Validando resultados...\n";
    
    $ok = true;
    $errores = [];
    
    foreach ($resultados as $asesor_id => $numeros) {
        // Verificar que todos sean únicos
        $unicos = count(array_unique($numeros));
        if ($unicos !== $numerosPerAsessor) {
            $ok = false;
            $errores[] = "Asesor $asesor_id: Duplicados internos ($unicos de $numerosPerAsessor)";
        }
        
        // Verificar que sean consecutivos desde 1
        $esperados = [];
        for ($i = 1; $i <= $numerosPerAsessor; $i++) {
            $esperados[] = sprintf('COT-%05d', $i);
        }
        
        if ($numeros !== $esperados) {
            $ok = false;
            $errores[] = "Asesor $asesor_id: No son consecutivos desde 1\n  Esperados: " . implode(', ', $esperados) . "\n  Obtenidos: " . implode(', ', $numeros);
        }
    }
    
    if ($ok) {
        echo "    ✓ Todos los asesores tienen números únicos y consecutivos\n";
    } else {
        echo "    ✗ Errores encontrados:\n";
        foreach ($errores as $error) {
            echo "      - $error\n";
        }
    }

    echo "\n[4] Estadísticas:\n";
    echo "    - Asesores: " . count($asesores) . "\n";
    echo "    - Números por asesor: $numerosPerAsessor\n";
    echo "    - Total de números: " . (count($asesores) * $numerosPerAsessor) . "\n";
    
    echo "\n" . ($ok ? "✅ TEST EXHAUSTIVO EXITOSO\n" : "❌ TEST EXHAUSTIVO FALLIDO\n");
    exit($ok ? 0 : 1);

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
