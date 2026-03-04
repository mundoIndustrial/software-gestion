<?php

// Laravel bootstrap
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Application\Pedidos\DTOs\ObtenerFacturaDTO;
use App\Application\Pedidos\UseCases\ObtenerFacturaUseCase;
use Illuminate\Support\Facades\Log;

// Configurar usuario autenticado (fake)
auth()->login(\App\Models\User::first());

// Crear DTO
$dto = new ObtenerFacturaDTO(
    pedidoId: 36,
);

// Instanciar UseCase
$useCase = app(ObtenerFacturaUseCase::class);

// Ejecutar
$datos = $useCase->ejecutar($dto);

// Diagnosticar procesos
echo "\n✅ DIAGNÓSTICO FACTURA - COLORES EN PROCESOS\n";
echo "═══════════════════════════════════════════════════════════\n";

echo "\nTotal prendas: " . count($datos['prendas']) . "\n";

foreach ($datos['prendas'] as $prenIdx => $prenda) {
    echo "\n─────────────────────────────────────────────────────────\n";
    echo "📦 PRENDA: {$prenda['nombre']}\n";
    echo "─────────────────────────────────────────────────────────\n";
    
    $procesos = $prenda['procesos'] ?? [];
    echo "   Procesos: " . count($procesos) . "\n";
    
    foreach ($procesos as $procIdx => $proceso) {
        echo "\n   🔧 PROCESO: {$proceso['nombre']}\n";
        
        $tallas = $proceso['tallas'] ?? [];
        echo "      Géneros con tallas: " . count($tallas) . "\n";
        
        foreach ($tallas as $genero => $tallaData) {
            echo "\n         📊 GÉNERO: {$genero}\n";
            
            if (empty($tallaData)) {
                echo "            (sin tallas)\n";
                continue;
            }
            
            foreach ($tallaData as $nomTalla => $valor) {
                echo "            Talla {$nomTalla}:\n";
                
                if (is_array($valor)) {
                    // Es array de colores
                    echo "               💾 CON COLORES (array):\n";
                    foreach ($valor as $colorItem) {
                        $color = $colorItem['color'] ?? 'N/A';
                        $tela = $colorItem['tela'] ?? 'N/A';
                        $cant = $colorItem['cantidad'] ?? 0;
                        echo "                  • {$color} ({$tela}): {$cant}\n";
                    }
                } else {
                    // Es valor simple (número)
                    echo "               📄 SIMPLE: {$valor}\n";
                }
            }
        }
    }
}

echo "\n═══════════════════════════════════════════════════════════\n";
echo "✅ Diagnóstico completado\n\n";
