<?php
/**
 * Debug completo de datos de factura
 */

require __DIR__ . '/vendor/autoload.php';

// Cargar Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$pedidoId = $argv[1] ?? 47;

echo "\n========== DEBUG FACTURA COMPLETA ==========\n";
echo "Pedido ID: $pedidoId\n\n";

try {
    // Usar el servicio de factura
    $service = resolve(\App\Domain\Pedidos\Services\ReciboPedidoService::class);
    $datos = $service->obtenerDatosRecibos($pedidoId, false);
    
    echo "✅ ReciboPedidoService retornó datos\n\n";
    
    // Revisar prendas
    if (isset($datos['prendas']) && !empty($datos['prendas'])) {
        echo "📦 PRENDAS: " . count($datos['prendas']) . "\n";
        
        foreach ($datos['prendas'] as $pIdx => $prenda) {
            $nombrePrenda = isset($prenda['nombre']) ? $prenda['nombre'] : (isset($prenda['id']) ? "ID: {$prenda['id']}" : "Prenda $pIdx");
            echo "\n  Prenda $pIdx: $nombrePrenda\n";
            
            if (isset($prenda['procesos']) && !empty($prenda['procesos'])) {
                echo "  ✅ " . count($prenda['procesos']) . " procesos\n";
                
                foreach ($prenda['procesos'] as $prcIdx => $proceso) {
                    $tipo = isset($proceso['tipo']) ? $proceso['tipo'] : 'N/A';
                    $modo = isset($proceso['modo_tallas']) ? $proceso['modo_tallas'] : 'NULL';
                    echo "\n    Proceso $prcIdx: $tipo\n";
                    echo "    - modo_tallas: $modo\n";
                    echo "    - tallas_detalles existe: " . (isset($proceso['tallas_detalles']) ? 'SÍ' : 'NO') . "\n";
                    
                    if (isset($proceso['tallas_detalles'])) {
                        echo "    - tallas_detalles keys: " . implode(', ', array_keys($proceso['tallas_detalles'])) . "\n";
                        echo "    - tallas_detalles datos:\n";
                        echo "      " . json_encode($proceso['tallas_detalles'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
                    }
                }
            } else {
                echo "  ❌ Sin procesos\n";
            }
        }
    }
    
    // Hacer un echo de parte de la estructura como JSON para verificar
    echo "\n\n✅ Validación JSON:\n";
    $firstProceso = isset($datos['prendas'][0]['procesos'][0]) ? $datos['prendas'][0]['procesos'][0] : [];
    $json = json_encode([
        'prendas_count' => count($datos['prendas'] ?? []),
        'primer_prenda_procesos_count' => count($datos['prendas'][0]['procesos'] ?? []),
        'primer_proceso_modo_tallas' => isset($firstProceso['modo_tallas']) ? $firstProceso['modo_tallas'] : 'NULL',
        'primer_proceso_tallas_detalles' => isset($firstProceso['tallas_detalles']) ? 'EXISTE' : 'NO_EXISTE',
    ]);
    echo $json . "\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n========== FIN DEBUG ==========\n\n";
