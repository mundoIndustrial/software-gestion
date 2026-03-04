<?php
/**
 * Script para debuggear por_talla en procesos
 * Uso: php debug-por-talla.php <proceso_id>
 */

require __DIR__ . '/vendor/autoload.php';

// Cargar Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$procesId = $argv[1] ?? 42;

echo "\n========== DEBUG POR_TALLA ==========\n";
echo "Proceso ID: $procesId\n\n";

// 1. Obtener datos del proceso
$proceso = \DB::table('pedidos_procesos_prenda_detalles')
    ->where('id', $procesId)
    ->first();

if (!$proceso) {
    echo "❌ Proceso NO encontrado\n";
    exit(1);
}

echo "✅ Proceso encontrado:\n";
echo "  - ID: {$proceso->id}\n";
echo "  - Tipo: {$proceso->tipo_proceso_id}\n";
echo "  - modo_tallas: " . ($proceso->modo_tallas ?? 'NULL') . "\n";
echo "  - estado: {$proceso->estado}\n";

// 2. Verificar si hay tallas_detalles
$tallesDetalles = \DB::table('pedidos_procesos_prenda_tallas')
    ->where('proceso_prenda_detalle_id', $procesId)
    ->get(['genero', 'talla', 'ubicaciones', 'observaciones']);

echo "\n📊 Registros en pedidos_procesos_prenda_tallas:\n";
if ($tallesDetalles->count() > 0) {
    echo "✅ " . $tallesDetalles->count() . " registros encontrados:\n";
    foreach ($tallesDetalles as $talla) {
        echo "\n  - Género: {$talla->genero}, Talla: {$talla->talla}\n";
        echo "    • Ubicaciones: " . ($talla->ubicaciones ?? 'NULL') . "\n";
        echo "    • Observaciones: " . ($talla->observaciones ?? 'NULL') . "\n";
    }
} else {
    echo "❌ NO hay registros de tallas_detalles para este proceso\n";
}

// 3. Verificar en ReciboPedidoService
echo "\n\n🔍 Verificando ReciboPedidoService...\n";
try {
    $service = resolve(\App\Domain\Pedidos\Services\ReciboPedidoService::class);
    $reflection = new \ReflectionMethod($service, 'obtenerTallesDetallesProceso');
    $reflection->setAccessible(true);
    
    // Simular llamada al método
    $resultado = $reflection->invoke($service, $proceso);
    echo "✅ Método obtenerTallesDetallesProceso ejecutado:\n";
    echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n========== FIN DEBUG ==========\n\n";
