#!/usr/bin/env php
<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PedidoProduccion;
use App\Application\Pedidos\DTOs\ObtenerPrendasPedidoDTO;
use App\Application\Pedidos\UseCases\ObtenerPrendasPedidoUseCase;

echo "\n🔍 VERIFICANDO QUE EL USECASE RETORNA de_bodega Y origen\n";
echo "==========================================================\n\n";

// Obtener un pedido con prendas
$pedido = PedidoProduccion::with('prendas')->first();

if ($pedido && $pedido->prendas->count() > 0) {
    echo "✅ Pedido encontrado: #{$pedido->numero_pedido} (ID: {$pedido->id})\n";
    echo "   Prendas: {$pedido->prendas->count()}\n\n";
    
    // Usar el UseCase
    $dto = ObtenerPrendasPedidoDTO::fromRoute($pedido->id);
    $useCase = app()->make(ObtenerPrendasPedidoUseCase::class);
    $resultado = $useCase->ejecutar($dto);
    
    echo "📋 Respuesta del UseCase:\n";
    echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
    
    // Verificar si de_bodega y origen están presentes
    if (is_array($resultado) && count($resultado) > 0) {
        $prenda = $resultado[0];
        echo "🔍 Primera prenda en respuesta:\n";
        echo "  - nombre_prenda: " . ($prenda['nombre_prenda'] ?? 'N/A') . "\n";
        echo "  - de_bodega: " . var_export($prenda['de_bodega'] ?? 'NO EXISTE', true) . "\n";
        echo "  - origen: " . var_export($prenda['origen'] ?? 'NO EXISTE', true) . "\n";
        echo "  - Claves disponibles: " . implode(', ', array_keys($prenda)) . "\n";
        
        if (!isset($prenda['de_bodega'])) {
            echo "\n⚠️  PROBLEMA: de_bodega no está en la respuesta!\n";
        } else {
            echo "\n✅ de_bodega está presente: " . var_export($prenda['de_bodega'], true) . "\n";
        }
        
        if (!isset($prenda['origen'])) {
            echo "⚠️  PROBLEMA: origen no está en la respuesta!\n";
        } else {
            echo "✅ origen está presente: " . var_export($prenda['origen'], true) . "\n";
        }
    }
} else {
    echo "❌ No hay pedidos con prendas en la base de datos\n";
}

echo "\n";
