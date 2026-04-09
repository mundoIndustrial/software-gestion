<?php
/**
 * Script para verificar que la corrección en RecibosCosturaReadRepository funciona
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Infrastructure\Insumos\ReadModels\RecibosCosturaReadRepository;

echo "\n════════════════════════════════════════════════════════════════\n";
echo "  VERIFICACIÓN: Query después de la corrección\n";
echo "════════════════════════════════════════════════════════════════\n\n";

$repository = new RecibosCosturaReadRepository();

// Obtener query base corregida
$query = $repository->buildBaseQuery();

echo "SQL Query (DESPUÉS DE CORRECCIÓN):\n";
echo $query->toSql() . "\n\n";

echo "Bindings: " . json_encode($query->getBindings()) . "\n\n";

// Ejecutar query
$recibos = $query->get();

echo "✓ Total de recibos encontrados: " . $recibos->count() . " recibos\n\n";

if ($recibos->count() > 0) {
    echo "Recibos encontrados:\n";
    foreach ($recibos as $idx => $recibo) {
        echo "  " . ($idx + 1) . ". Recibo: {$recibo->consecutivo_actual} " .
             "| Estado: {$recibo->recibo_estado} " .
             "| Área: {$recibo->recibo_area} " .
             "| Pedido: {$recibo->numero_pedido} ({$recibo->cliente})\n";
    }
}

echo "\n✓ Verificación completada\n\n";
