<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Http\Kernel');
$kernel->bootstrap();

use App\Application\Pedidos\UseCases\ObtenerProcesosPorPedidoUseCase;

// Crear una instancia del UseCase
$useCase = app(ObtenerProcesosPorPedidoUseCase::class);

// Ejecutar el UseCase con número de pedido 45808
$resultado = $useCase->ejecutar(45808);

echo "=== RESULTADO DEL USECASE ===\n";
echo "Número de pedido: " . $resultado['numero_pedido'] . "\n";
echo "Cliente: " . $resultado['cliente'] . "\n";
echo "Total procesos: " . count($resultado['procesos']) . "\n";
echo "\n=== PROCESOS ===\n";
echo json_encode($resultado['procesos'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
