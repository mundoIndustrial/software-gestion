<?php
/**
 * Debug para simular qué retorna el endpoint operario
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

use App\Models\PedidoProduccion;
use App\Infrastructure\Services\Pedidos\ObtenerPrendasRecibosService;

try {
    $pedidoId = 176;
    
    echo "=== DEBUG ENDPOINT OPERARIO ===\n\n";
    
    // 1. Cargar pedido como lo haría el endpoint
    $pedido = PedidoProduccion::with([
        'prendas.tallas',
        'prendas.variantes',
    ])->find($pedidoId);
    
    if (!$pedido) {
        echo "❌ Pedido no encontrado\n";
        exit(1);
    }
    
    echo "✅ Pedido cargado: " . $pedido->numero_pedido . "\n\n";
    
    // 2. Simular lo que hace GetPedidoDataOperarioUseCase
    $obtenerPrendasService = new ObtenerPrendasRecibosService();
    
    $prendas = [];
    foreach ($pedido->prendas as $prenda) {
        echo "--- Procesando Prenda: " . $prenda->nombre_prenda . " (ID: " . $prenda->id . ") ---\n";
        
        // Esto es lo que hace ObtenerPrendasRecibosService
        $prendaData = [
            'id' => $prenda->id,
            'nombre_prenda' => $prenda->nombre_prenda,
            'variantes' => [],
            'cantidad' => 0,
        ];
        
        // Ver si tiene tallas
        if ($prenda->tallas && $prenda->tallas->count() > 0) {
            echo "  ✅ Tiene " . $prenda->tallas->count() . " tallas\n";
            
            // Contar cantidad total
            $cantidadTotal = 0;
            foreach ($prenda->tallas as $talla) {
                $cantidadTotal += $talla->cantidad;
                echo "    - " . $talla->genero . ": " . $talla->talla . " x " . $talla->cantidad . "\n";
            }
            $prendaData['cantidad'] = $cantidadTotal;
            echo "  Cantidad total: $cantidadTotal\n";
        }
        
        // Ver si tiene variantes
        if ($prenda->variantes && $prenda->variantes->count() > 0) {
            echo "  ⚠️ Tiene " . $prenda->variantes->count() . " variantes en BD\n";
            
            foreach ($prenda->variantes as $var) {
                echo "    - Talla: " . ($var->talla ?? 'NULL') . ", Genero: " . ($var->genero ?? 'NULL') . ", Ctd: " . ($var->cantidad ?? 'NULL') . "\n";
                
                // Si talla es NULL, probablemente se esté usando cantidad de tallas sumada
                if (!$var->talla && !$var->cantidad && $prendaData['cantidad'] > 0) {
                    echo "      ⚠️ POSIBLE PROBLEMA: variante NULL con cantidad de tallas\n";
                }
            }
        }
        
        echo "\n";
        $prendas[] = $prendaData;
    }
    
    // 3. Ver qué estructura está siendo retornada
    echo "\n=== ESTRUCTURA RETORNADA AL ENDPOINT ===\n";
    echo json_encode(['prendas' => $prendas], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
