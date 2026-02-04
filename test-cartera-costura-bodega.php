<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\DB;

try {
    echo "=== Prueba: Generar COSTURA-BODEGA al aprobar Pedido ===\n\n";

    // 1. Buscar un pedido pendiente de cartera
    echo "1. Buscando pedido en estado 'pendiente_cartera'...\n";
    
    $pedido = PedidoProduccion::where('estado', 'pendiente_cartera')
        ->first();
    
    if (!$pedido) {
        echo "   ✗ No hay pedidos pendiente_cartera\n";
        exit(1);
    }
    
    echo "   ✓ Pedido encontrado: ID {$pedido->id}\n";
    
    // 2. Simular aprobación de CARTERA
    echo "\n2. Simulando aprobación de CARTERA...\n";
    
    // Verificar estado actual
    echo "   - Estado actual: {$pedido->estado}\n";
    
    // Actualizar estado a PENDIENTE_SUPERVISOR (como lo hace CarteraPedidosController)
    $siguienteNumero = DB::table('consecutivos_recibos_pedidos')
        ->where('tipo_recibo', 'COSTURA')
        ->max('consecutivo_actual') + 1;
    
    $pedido->update([
        'numero_pedido' => $siguienteNumero,
        'estado' => 'PENDIENTE_SUPERVISOR',
        'aprobado_por_usuario_cartera' => auth()->check() ? auth()->user()->id : 1,
        'aprobado_por_cartera_en' => now(),
    ]);
    
    echo "   ✓ Pedido aprobado: nuevo estado PENDIENTE_SUPERVISOR\n";
    
    // 3. Verificar si se creó COSTURA-BODEGA
    echo "\n3. Verificando consecutivo COSTURA-BODEGA...\n";
    
    $costuraBodega = DB::table('consecutivos_recibos_pedidos')
        ->where('pedido_produccion_id', $pedido->id)
        ->where('tipo_recibo', 'COSTURA-BODEGA')
        ->first();
    
    if ($costuraBodega) {
        echo "   ✓ COSTURA-BODEGA creado exitosamente\n";
        echo "     - Pedido ID: {$costuraBodega->pedido_produccion_id}\n";
        echo "     - Tipo: {$costuraBodega->tipo_recibo}\n";
        echo "     - Consecutivo: {$costuraBodega->consecutivo_actual}\n";
        echo "     - Activo: {$costuraBodega->activo}\n";
    } else {
        echo "   ✗ COSTURA-BODEGA NO se creó\n";
        echo "   ! Nota: Se debe crear MANUALMENTE vía CarteraPedidosController::aprobarPedido\n";
    }
    
    // 4. Mostrar todos los consecutivos del pedido
    echo "\n4. Todos los consecutivos del pedido:\n";
    
    $todosConsecutivos = DB::table('consecutivos_recibos_pedidos')
        ->where('pedido_produccion_id', $pedido->id)
        ->get();
    
    foreach ($todosConsecutivos as $cons) {
        echo "   - {$cons->tipo_recibo}: consecutivo={$cons->consecutivo_actual}, activo={$cons->activo}\n";
    }
    
    echo "\n✓ Prueba completada\n";

} catch (\Exception $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
    exit(1);
}
