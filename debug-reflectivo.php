<?php
require 'bootstrap/app.php';

$app = new Illuminate\Foundation\Application(
    $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__)
);

$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Debug recibos REFLECTIVO
$recibosReflectivo = \App\Models\ConsecutivoReciboPedido::where('tipo_recibo', 'REFLECTIVO')
    ->where('activo', 1)
    ->with(['prenda', 'prenda.pedidoProduccion'])
    ->get();

echo "=== RECIBOS REFLECTIVO ENCONTRADOS ===\n";
echo "Total: " . $recibosReflectivo->count() . "\n\n";

foreach ($recibosReflectivo as $recibo) {
    echo "Recibo ID: {$recibo->id}\n";
    echo "Prenda ID: {$recibo->prenda_id}\n";
    echo "Pedido ID: {$recibo->pedido_produccion_id}\n";
    echo "Consecutivo: {$recibo->consecutivo_actual}\n";
    echo "Tipo: {$recibo->tipo_recibo}\n";
    
    if ($recibo->prenda) {
        echo "  Prenda: {$recibo->prenda->nombre_prenda}\n";
        
        if ($recibo->prenda->pedidoProduccion) {
            $pedido = $recibo->prenda->pedidoProduccion;
            echo "    Pedido #: {$pedido->numero_pedido}\n";
            echo "    Estado: {$pedido->estado}\n";
            echo "    Área: {$pedido->area}\n";
        }
    }
    
    // Verificar si está aprobado
    $detalleAprobado = \App\Models\PedidosProcesosPrendaDetalle::where('prenda_pedido_id', $recibo->prenda_id)
        ->where('estado', 'APROBADO')
        ->first();
    
    echo "  ✓ Aprobado: " . ($detalleAprobado ? "SÍ" : "NO") . "\n";
    echo "\n";
}

// Verificar total de detalles APROBADOS
$detallesAprobados = \App\Models\PedidosProcesosPrendaDetalle::where('estado', 'APROBADO')->count();
echo "\n=== ESTADÍSTICAS ===\n";
echo "Detalles APROBADOS en pedidos_procesos_prenda_detalles: {$detallesAprobados}\n";
