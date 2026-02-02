<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;

// Revisar pedido 12
$pedido = PedidoProduccion::find(12);

echo "=== INFORMACIÓN DEL PEDIDO 12 ===\n";
if (!$pedido) {
    echo "❌ Pedido 12 NO encontrado\n";
    exit;
}

echo "✅ Pedido encontrado\n";
echo "\nDatos del Pedido:\n";
echo "  ID: " . $pedido->id . "\n";
echo "  Número: " . ($pedido->numero_pedido ?? "NULL") . "\n";
echo "  Cliente ID: " . ($pedido->cliente_id ?? "NULL") . "\n";
echo "  Estado: " . $pedido->estado . "\n";
echo "  Descripción: " . substr($pedido->novedades ?? "", 0, 50) . "...\n";
echo "  Creado: " . $pedido->created_at . "\n";
echo "  Actualizado: " . $pedido->updated_at . "\n";

// Revisar prendas
$prendas = PrendaPedido::where('pedido_produccion_id', 12)->get();
echo "\nPrendas (" . $prendas->count() . "):\n";
foreach ($prendas as $prenda) {
    echo "  - ID: {$prenda->id}, Nombre: {$prenda->nombre}\n";
    
    // Revisar procesos
    $procesos = $prenda->procesos()->get();
    echo "    Procesos: " . $procesos->count() . "\n";
    foreach ($procesos as $proc) {
        echo "      - {$proc->tipo_proceso}: {$proc->estado}\n";
    }
}

// Intentar obtener con el use case
echo "\n=== PRUEBA CON USE CASE ===\n";
try {
    $useCase = app(\App\Application\Pedidos\UseCases\ObtenerPedidoUseCase::class);
    $resultado = $useCase->ejecutar(12);
    
    if (method_exists($resultado, 'toArray')) {
        $datos = $resultado->toArray();
    } else {
        $datos = (array) $resultado;
    }
    
    echo "✅ OK - Prendas en respuesta: " . count($datos['prendas'] ?? []) . "\n";
    
    if (!empty($datos['prendas'])) {
        echo "\nPrendas en API:\n";
        foreach ($datos['prendas'] as $prenda) {
            echo "  - {$prenda['nombre']}\n";
        }
    }
    
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Clase: " . get_class($e) . "\n";
}
