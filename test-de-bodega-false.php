#!/usr/bin/env php
<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Application\Pedidos\DTOs\ObtenerPrendasPedidoDTO;
use App\Application\Pedidos\UseCases\ObtenerPrendasPedidoUseCase;

echo "\n🔍 VERIFICANDO CASOS: de_bodega = true Y de_bodega = false\n";
echo "===========================================================\n\n";

// Ver todas las prendas
echo "📊 Todas las prendas en la BD:\n";
$todasPrendas = PrendaPedido::all();
foreach ($todasPrendas as $p) {
    echo "  - ID {$p->id}: {$p->nombre_prenda} | de_bodega=" . var_export($p->de_bodega, true) . "\n";
}

echo "\n";

// Test 1: Prendas con de_bodega = true
echo "🔹 Prendas con de_bodega = true:\n";
$prendasTrue = PrendaPedido::where('de_bodega', true)->get();
if ($prendasTrue->count() > 0) {
    foreach ($prendasTrue as $p) {
        echo "  ✅ {$p->nombre_prenda} (ID: {$p->id})\n";
    }
} else {
    echo "  ❌ No hay prendas con de_bodega = true\n";
}

echo "\n";

// Test 2: Prendas con de_bodega = false
echo "🔹 Prendas con de_bodega = false:\n";
$prendasFalse = PrendaPedido::where('de_bodega', false)->orWhereNull('de_bodega')->get();
if ($prendasFalse->count() > 0) {
    foreach ($prendasFalse as $p) {
        echo "  ✅ {$p->nombre_prenda} (ID: {$p->id}) | de_bodega=" . var_export($p->de_bodega, true) . "\n";
    }
} else {
    echo "  ❌ No hay prendas con de_bodega = false\n";
}

echo "\n";

// Test 3: Ejecutar UseCase para pedido con prendas falsas
if ($prendasFalse->count() > 0) {
    $prendaFalsa = $prendasFalse->first();
    $pedidoId = $prendaFalsa->pedido_produccion_id;
    
    echo "📋 Ejecutando UseCase para Pedido #{$pedidoId} con prendas de_bodega=false:\n\n";
    
    $dto = ObtenerPrendasPedidoDTO::fromRoute($pedidoId);
    $useCase = app()->make(ObtenerPrendasPedidoUseCase::class);
    $resultado = $useCase->ejecutar($dto);
    
    if (is_array($resultado) && count($resultado) > 0) {
        foreach ($resultado as $prenda) {
            if ($prenda['id'] == $prendaFalsa->id) {
                echo "🎯 Prenda encontrada en respuesta:\n";
                echo "  - nombre: {$prenda['nombre_prenda']}\n";
                echo "  - de_bodega: " . var_export($prenda['de_bodega'] ?? 'NO EXISTE', true) . "\n";
                echo "  - origen: " . var_export($prenda['origen'] ?? 'NO EXISTE', true) . "\n";
                
                if ($prenda['de_bodega'] === false || $prenda['de_bodega'] == 0) {
                    if ($prenda['origen'] === 'confeccion') {
                        echo "\n✅ CORRECTO: de_bodega=false → origen='confeccion'\n";
                    } else {
                        echo "\n⚠️  ERROR: de_bodega=false pero origen='{$prenda['origen']}' (debería ser 'confeccion')\n";
                    }
                }
            }
        }
    }
}

echo "\n";
