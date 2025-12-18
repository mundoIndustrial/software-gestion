<?php
/**
 * Test específico: Validar que la secuencia es perfecta
 * Si última = COT-00023, entonces:
 * - Asesor 1 → COT-00024
 * - Asesor 2 → COT-00025
 * - Asesor 3 → COT-00026
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Application\Cotizacion\Services\GenerarNumeroCotizacionService;
use App\Models\User;
use App\Models\Cotizacion;
use App\Domain\Shared\ValueObjects\UserId;

echo "═══════════════════════════════════════════════════════════════\n";
echo "TEST ESPECÍFICO: Secuencia Global Consecutiva\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

try {
    $service = app(GenerarNumeroCotizacionService::class);

    // Obtener la última cotización para saber dónde estamos
    $ultimaCotizacion = Cotizacion::whereNotNull('numero_cotizacion')
        ->orderBy('id', 'desc')
        ->first();

    echo "[1] Estado actual de la BD:\n";
    if ($ultimaCotizacion) {
        echo "  Última cotización guardada: {$ultimaCotizacion->numero_cotizacion} (ID: {$ultimaCotizacion->id})\n";
        
        // Extraer el número
        preg_match('/\d+/', $ultimaCotizacion->numero_cotizacion, $matches);
        $ultimoNumero = (int)$matches[0];
        $proximoEsperado = $ultimoNumero + 1;
        echo "  Próximo número esperado: COT-" . str_pad($proximoEsperado, 5, '0', STR_PAD_LEFT) . "\n";
    } else {
        echo "  No hay cotizaciones previas. Comenzaremos desde COT-00001\n";
        $proximoEsperado = 1;
    }

    echo "\n[2] Creando 3 asesores para simular acceso simultáneo...\n";
    $asesores = User::factory()->count(3)->create();
    
    echo "[3] Generando números simultáneamente:\n\n";
    
    $numerosObtenidos = [];
    
    foreach ($asesores as $index => $asesor) {
        $usuarioId = UserId::crear($asesor->id);
        $numero = $service->generarNumeroCotizacionFormateado($usuarioId);
        $numeroInt = (int)substr($numero, 5);
        $numerosObtenidos[] = $numeroInt;
        
        echo "  Asesor {$asesor->id}: {$numero}\n";
    }

    echo "\n[4] VALIDACIÓN:\n";
    
    $ok = true;
    
    // Verificar que el primer número sea el esperado
    if ($numerosObtenidos[0] === $proximoEsperado) {
        echo "  ✅ Primer asesor obtuvo el número correcto: COT-" . str_pad($proximoEsperado, 5, '0', STR_PAD_LEFT) . "\n";
    } else {
        echo "  ❌ ERROR: Primer asesor obtuvo " . str_pad($numerosObtenidos[0], 5, '0', STR_PAD_LEFT) . " pero esperaba " . str_pad($proximoEsperado, 5, '0', STR_PAD_LEFT) . "\n";
        $ok = false;
    }
    
    // Verificar que sean consecutivos
    for ($i = 1; $i < count($numerosObtenidos); $i++) {
        $esperado = $proximoEsperado + $i;
        if ($numerosObtenidos[$i] === $esperado) {
            echo "  ✅ Asesor " . ($i + 1) . " obtuvo el número correcto: COT-" . str_pad($esperado, 5, '0', STR_PAD_LEFT) . "\n";
        } else {
            echo "  ❌ ERROR: Asesor " . ($i + 1) . " obtuvo " . str_pad($numerosObtenidos[$i], 5, '0', STR_PAD_LEFT) . " pero esperaba " . str_pad($esperado, 5, '0', STR_PAD_LEFT) . "\n";
            $ok = false;
        }
    }

    echo "\n[5] Resumen:\n";
    echo "  Secuencia obtenida:\n";
    foreach ($numerosObtenidos as $index => $num) {
        echo "    Asesor " . ($index + 1) . ": COT-" . str_pad($num, 5, '0', STR_PAD_LEFT) . "\n";
    }

    echo "\n" . str_repeat("═", 65) . "\n";
    if ($ok) {
        echo "✅ PRUEBA EXITOSA\n";
        echo "La secuencia es correcta y consecutiva\n";
    } else {
        echo "❌ PRUEBA FALLIDA\n";
        echo "Hay problemas en la secuencia\n";
    }
    echo str_repeat("═", 65) . "\n";
    
    exit($ok ? 0 : 1);

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
