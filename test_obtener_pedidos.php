<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Application\Pedidos\UseCases\ObtenerPedidoUseCase;
use App\Domain\Pedidos\Repositories\PedidoRepository;

// Instanciar el use case
$repository = app(PedidoRepository::class);
$useCase = new ObtenerPedidoUseCase($repository);

// Probar cada pedido
$pedidoIds = [1, 3, 4, 6, 8, 9, 10, 12];

echo "Probando ObtenerPedidoUseCase para cada pedido:\n";
echo str_repeat("=", 80) . "\n";

foreach ($pedidoIds as $id) {
    try {
        echo "\n🔍 Pedido ID: $id\n";
        $resultado = $useCase->ejecutar($id);
        
        // Convertir a array
        if (method_exists($resultado, 'toArray')) {
            $datos = $resultado->toArray();
        } else {
            $datos = (array) $resultado;
        }
        
        echo "✅ OK - Prendas: " . count($datos['prendas'] ?? []) . "\n";
        
        if (empty($datos['prendas'])) {
            echo "   ⚠️ SIN PRENDAS\n";
        }
        
    } catch (\Exception $e) {
        echo "❌ ERROR: " . $e->getMessage() . "\n";
        echo "   Clase: " . get_class($e) . "\n";
    }
}

echo "\n" . str_repeat("=", 80) . "\n";
