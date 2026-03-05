<?php

/**
 * TEST: Verificar que modo_tallas se devuelve correctamente del servidor
 * Ejecutar: php test-modo-tallas-verify.php [PEDIDO_ID]
 */

require_once 'bootstrap/app.php';

// Obtener ID del pedido desde argumentos
$pedidoId = isset($argv[1]) ? (int)$argv[1] : null;

if (!$pedidoId) {
    echo "❌ Uso: php test-modo-tallas-verify.php <PEDIDO_ID>\n";
    echo "Ejemplo: php test-modo-tallas-verify.php 123\n";
    exit(1);
}

try {
    $app = require_once 'bootstrap/app.php';
    $app->make(\Illuminate\Contracts\Http\Kernel::class);

    // Usar el Use Case DDD
    $obtenerPedidoUseCase = app(\App\Application\Pedidos\UseCases\ObtenerProduccionPedidoUseCase::class);
    $dto = new \App\Application\Pedidos\UseCases\ObtenerProduccionPedidoDTO($pedidoId);
    
    echo "📍 Obteniendo pedido $pedidoId...\n\n";
    
    $resultado = $obtenerPedidoUseCase->ejecutar($dto);
    
    // Verificar estructura
    echo "✅ Pedido obtenido\n";
    echo "─────────────────────────────────────────────\n";
    
    if (isset($resultado['pedido']['prendas'])) {
        $prendas = $resultado['pedido']['prendas'];
        echo "Prendas encontradas: " . count($prendas) . "\n\n";
        
        foreach ($prendas as $idx => $prenda) {
            echo "📦 Prenda $idx: " . ($prenda['nombre_prenda'] ?? 'Sin nombre') . "\n";
            
            if (!isset($prenda['procesos']) || !is_array($prenda['procesos']) || count($prenda['procesos']) === 0) {
                echo "   ❌ Sin procesos\n\n";
                continue;
            }
            
            echo "   Procesos: " . count($prenda['procesos']) . "\n";
            
            foreach ($prenda['procesos'] as $proc) {
                $tipoNombre = $proc['tipoProceso']['nombre'] ?? 'N/A';
                $modoTallas = $proc['modo_tallas'] ?? 'NO EXISTE';
                
                echo "   ├─ $tipoNombre\n";
                echo "   │  ├─ modo_tallas: " . ($modoTallas === 'NO EXISTE' ? "❌ $modoTallas" : "✅ $modoTallas") . "\n";
                echo "   │  ├─ id: " . ($proc['id'] ?? 'N/A') . "\n";
                echo "   │  └─ campos: " . implode(', ', array_slice(array_keys($proc), 0, 8)) . "\n";
            }
            
            echo "\n";
        }
    } else {
        echo "❌ No se encontraron prendas\n";
    }
    
    echo "─────────────────────────────────────────────\n";
    echo "✅ Verificación completada\n";

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
