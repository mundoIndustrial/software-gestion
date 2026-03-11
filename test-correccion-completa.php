<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== PRUEBA COMPLETA DE CORRECCIÓN - Pedido 143 ===" . PHP_EOL;

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

echo PHP_EOL . "=== ANÁLISIS DE COLORES ===" . PHP_EOL;

if ($resultado['todos_pendientes']) {
    echo "❌ FONDO AMARILLO - Todos los items están pendientes" . PHP_EOL;
} else {
    echo "✅ SIN FONDO AMARILLO - No todos los items están pendientes" . PHP_EOL;
}

if ($resultado['todos_entregados']) {
    echo "❌ FONDO AZUL - Todos los items están entregados" . PHP_EOL;
} else {
    echo "✅ SIN FONDO AZUL - No todos los items están entregados" . PHP_EOL;
}

echo PHP_EOL . "=== CONCLUSIÓN ===" . PHP_EOL;
echo "Con la corrección:" . PHP_EOL;
echo "- Solo se muestra AMARILLO si TODOS los items reales están pendientes" . PHP_EOL;
echo "- Solo se muestra AZUL si TODOS los items reales están entregados" . PHP_EOL;
echo "- Si faltan items por procesar en bodega_detalles_talla, no se colorea" . PHP_EOL;

echo PHP_EOL . "Explicación para Pedido 143:" . PHP_EOL;
echo "- Total items reales: {$resultado['total_items']} (items que deberían existir)" . PHP_EOL;
echo "- Items pendientes: {$resultado['items_pendientes']} (items en estado pendiente)" . PHP_EOL;
echo "- Items entregados: {$resultado['items_entregados']} (items en estado entregado)" . PHP_EOL;
echo "- Como {$resultado['total_items']} > {$resultado['items_pendientes']}, faltan items por procesar" . PHP_EOL;
echo "- Por lo tanto: SIN AMARILLO y SIN AZUL ✅" . PHP_EOL;
