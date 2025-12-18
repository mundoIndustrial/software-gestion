<?php
/**
 * Script de prueba directo para validar generación de números de cotización
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Application\Cotizacion\Services\GenerarNumeroCotizacionService;
use App\Models\User;
use App\Domain\Shared\ValueObjects\UserId;

echo "═══════════════════════════════════════════════════════════════\n";
echo "TEST: Generación de Números de Cotización Concurrentes\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

try {
    $service = app(GenerarNumeroCotizacionService::class);

    echo "[1] Creando 5 asesores de prueba...\n";
    $asesores = User::factory()->count(5)->create();
    
    echo "[2] Generando números (3 por asesor)...\n\n";
    
    $resultados = [];
    $todoNumeros = [];
    
    foreach ($asesores as $asesor) {
        $usuarioId = UserId::crear($asesor->id);
        $numeros = [];
        
        for ($i = 0; $i < 3; $i++) {
            $numero = $service->generarNumeroCotizacionFormateado($usuarioId);
            $numeros[] = $numero;
            $todoNumeros[] = $numero;
        }
        
        echo "Asesor {$asesor->id}: " . implode(', ', $numeros) . "\n";
        $resultados[$asesor->id] = $numeros;
    }

    echo "\n[3] Validaciones:\n";
    
    $ok = true;
    foreach ($resultados as $asesor_id => $numeros) {
        $unicos = count(array_unique($numeros));
        if ($unicos === 3) {
            echo "  ✓ Asesor $asesor_id: 3 números únicos\n";
        } else {
            echo "  ✗ Asesor $asesor_id: DUPLICADOS\n";
            $ok = false;
        }
    }
    
    echo "\n[4] Verificación de rangos por asesor:\n";
    
    $todoNumeros = [];
    $ok = true;
    
    foreach ($resultados as $asesor_id => $numeros) {
        // Cada asesor debe tener números únicos
        $unicos = count(array_unique($numeros));
        if ($unicos === 3) {
            echo "  ✓ Asesor $asesor_id: 3 números únicos\n";
        } else {
            echo "  ✗ Asesor $asesor_id: Números duplicados\n";
            $ok = false;
        }
        
        // Verificar que son consecutivos desde 1
        if ($numeros[0] === 'COT-00001' && $numeros[1] === 'COT-00002' && $numeros[2] === 'COT-00003') {
            echo "    ✓ Números consecutivos desde 1\n";
        } else {
            echo "    ✗ ERROR: Números no están en orden correcto: " . implode(', ', $numeros) . "\n";
            $ok = false;
        }
        
        $todoNumeros = array_merge($todoNumeros, $numeros);
    }
    
    echo "\n[5] Resumen:\n";
    echo "  - Total de asesores: 5\n";
    echo "  - Números por asesor: 3\n";
    echo "  - Total de números generados: " . count($todoNumeros) . "\n";
    echo "  - Es correcto que haya números repetidos entre asesores diferentes\n";
    echo "  - Cada asesor debe tener sus propios números (1, 2, 3...)\n";
    
    echo "\n" . ($ok ? "✅ PRUEBA EXITOSA\n" : "❌ PRUEBA FALLIDA\n");
    exit($ok ? 0 : 1);

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}


