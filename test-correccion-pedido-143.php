<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== PRUEBA DE CORRECCIÓN - Pedido 143 ===" . PHP_EOL;

// Probar la nueva lógica
$calculator = new \App\Application\Bodega\Calculators\PedidoEstadoCalculator();
$resultado = $calculator->calcular('143');

echo PHP_EOL . "RESULTADO con nueva lógica:" . PHP_EOL;
echo "Total items reales: {$resultado['total_items']}" . PHP_EOL;
echo "Items pendientes: {$resultado['items_pendientes']}" . PHP_EOL;
echo "Items entregados: {$resultado['items_entregados']}" . PHP_EOL;
echo "¿Tiene pendientes?: " . ($resultado['tiene_pendientes'] ? 'TRUE' : 'FALSE') . PHP_EOL;
echo "¿Todos pendientes?: " . ($resultado['todos_pendientes'] ? 'TRUE' : 'FALSE') . PHP_EOL;
echo "¿Todos entregados?: " . ($resultado['todos_entregados'] ? 'TRUE' : 'FALSE') . PHP_EOL;

echo PHP_EOL . "=== ANÁLISIS ===" . PHP_EOL;
if ($resultado['todos_pendientes']) {
    echo "❌ AÚN muestra AMARILLO - Todos los items están pendientes" . PHP_EOL;
} else {
    echo "✅ CORREGIDO - No todos los items están pendientes, no mostrará amarillo" . PHP_EOL;
}

echo PHP_EOL . "Explicación:" . PHP_EOL;
echo "- Total items reales: {$resultado['total_items']} (items que deberían existir)" . PHP_EOL;
echo "- Items pendientes: {$resultado['items_pendientes']} (items en estado pendiente en bodega)" . PHP_EOL;
echo "- Si total_items > items_pendientes, significa que faltan items por procesar" . PHP_EOL;
echo "- Por lo tanto, no todos están pendientes y no debe mostrar amarillo" . PHP_EOL;
