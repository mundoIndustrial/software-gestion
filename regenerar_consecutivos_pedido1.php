<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$pedidoId = 1;

echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║  REGENERACIÓN DE CONSECUTIVOS - PEDIDO ID {$pedidoId}                  ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// 1. Limpiar consecutivos anteriores
echo "🗑️  LIMPIANDO CONSECUTIVOS ANTERIORES...\n";
DB::table('consecutivos_recibos_pedidos')
    ->where('pedido_produccion_id', $pedidoId)
    ->delete();

echo "  ✓ Consecutivos eliminados\n\n";

// 2. Obtener el pedido
$pedido = DB::table('pedidos_produccion')->where('id', $pedidoId)->first();

if (!$pedido) {
    echo "❌ ERROR: No se encontró el pedido con ID {$pedidoId}\n";
    exit(1);
}

// 3. Usar el servicio para regenerar
$service = app(\App\Services\ConsecutivosRecibosService::class);

echo "📋 REGENERANDO CONSECUTIVOS...\n";
echo "  ID Pedido: {$pedido->id}\n";
echo "  Número Pedido: {$pedido->numero_pedido}\n";
echo "  Estado Actual: {$pedido->estado}\n\n";

// Cambiar estado temporalmente para disparar la lógica
$estadoAnterior = 'PENDIENTE_SUPERVISOR';
$estadoNuevo = 'PENDIENTE_INSUMOS';

$resultado = $service->generarConsecutivosSiAplica($pedido, $estadoAnterior, $estadoNuevo);

if ($resultado) {
    echo "✓ Consecutivos regenerados exitosamente\n\n";
} else {
    echo "⚠️  No se generaron consecutivos (puede que no aplique la lógica)\n\n";
}

// 4. Mostrar los consecutivos generados
$consecutivos = DB::table('consecutivos_recibos_pedidos')
    ->where('pedido_produccion_id', $pedidoId)
    ->orderBy('tipo_recibo')
    ->orderBy('prenda_id')
    ->get();

echo "📊 CONSECUTIVOS GENERADOS:\n";
echo str_repeat("─", 70) . "\n";

if ($consecutivos->isEmpty()) {
    echo "  ❌ No se encontraron consecutivos\n";
} else {
    echo "  Total: " . $consecutivos->count() . "\n\n";
    
    $porTipo = [];
    foreach ($consecutivos as $cons) {
        if (!isset($porTipo[$cons->tipo_recibo])) {
            $porTipo[$cons->tipo_recibo] = 0;
        }
        $porTipo[$cons->tipo_recibo]++;
        
        $prenda = $cons->prenda_id ? "Prenda #{$cons->prenda_id}" : "General";
        echo "  [{$cons->tipo_recibo}] {$prenda} = Consecutivo #{$cons->consecutivo_actual}\n";
    }
    
    echo "\n  Resumen por tipo:\n";
    foreach ($porTipo as $tipo => $cantidad) {
        echo "    - {$tipo}: {$cantidad}\n";
    }
}

echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║  FIN DEL PROCESO                                              ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";
