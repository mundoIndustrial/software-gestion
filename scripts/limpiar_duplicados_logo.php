<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n";
echo "=================================================\n";
echo "LIMPIEZA DE LOGO_PEDIDOS DUPLICADOS\n";
echo "=================================================\n\n";

// Buscar duplicados por cotizacion_id (misma cotización, múltiples logo_pedidos)
$duplicados = DB::table('logo_pedidos')
    ->select('cotizacion_id', DB::raw('COUNT(*) as cantidad'))
    ->whereNotNull('cotizacion_id')
    ->groupBy('cotizacion_id')
    ->having('cantidad', '>', 1)
    ->get();

if ($duplicados->isEmpty()) {
    echo "✅ No se encontraron duplicados\n\n";
    exit(0);
}

echo "⚠️  Se encontraron " . $duplicados->count() . " cotizaciones con múltiples logo_pedidos\n\n";

foreach ($duplicados as $dup) {
    $cotizacionId = $dup->cotizacion_id;
    $cantidad = $dup->cantidad;
    
    // Obtener todos los logo_pedidos de esta cotización
    $logoPedidos = DB::table('logo_pedidos')
        ->where('cotizacion_id', $cotizacionId)
        ->orderBy('id', 'asc')
        ->get();
    
    echo "📋 Cotización ID: $cotizacionId (Cantidad: $cantidad)\n";
    echo str_repeat('-', 80) . "\n";
    
    $pedidoParaMantener = null;
    $pedidosParaEliminar = [];
    
    foreach ($logoPedidos as $lp) {
        $tieneNumeroPedidoCost = !is_null($lp->numero_pedido_cost);
        $tienePedidoId = !is_null($lp->pedido_id);
        $tieneNumeroCompleto = !empty($lp->numero_pedido) && str_starts_with($lp->numero_pedido, '#LOGO-');
        
        echo "  ID: {$lp->id} | Numero: {$lp->numero_pedido} | ";
        echo "Pedido ID: " . ($lp->pedido_id ?? 'NULL') . " | ";
        echo "Numero Pedido Cost: " . ($lp->numero_pedido_cost ?? 'NULL') . "\n";
        
        // Criterio: Mantener el que tiene numero_pedido_cost Y pedido_id válido
        if ($tieneNumeroPedidoCost && $tienePedidoId) {
            if (!$pedidoParaMantener) {
                $pedidoParaMantener = $lp;
                echo "    ✅ MANTENER (tiene numero_pedido_cost Y pedido_id)\n";
            } else {
                $pedidosParaEliminar[] = $lp;
                echo "    ❌ ELIMINAR (duplicado)\n";
            }
        } else {
            $pedidosParaEliminar[] = $lp;
            echo "    ❌ ELIMINAR (sin numero_pedido_cost o sin pedido_id)\n";
        }
    }
    
    // Si no hay ninguno con numero_pedido_cost, mantener el primero
    if (!$pedidoParaMantener && !empty($logoPedidos)) {
        $pedidoParaMantener = $logoPedidos[0];
        $pedidosParaEliminar = array_slice($logoPedidos->toArray(), 1);
        echo "  ⚠️  Ninguno tiene numero_pedido_cost, manteniendo el primero (ID: {$pedidoParaMantener->id})\n";
    }
    
    echo "\n";
    
    if (!empty($pedidosParaEliminar)) {
        echo "  🗑️  IDs a eliminar: " . implode(', ', array_column($pedidosParaEliminar, 'id')) . "\n";
        
        foreach ($pedidosParaEliminar as $pedidoEliminar) {
            // Verificar si tiene procesos asociados
            $cantidadProcesos = DB::table('procesos_pedidos_logo')
                ->where('logo_pedido_id', $pedidoEliminar->id)
                ->count();
            
            if ($cantidadProcesos > 0) {
                echo "    ⚠️  ADVERTENCIA: El logo_pedido ID {$pedidoEliminar->id} tiene {$cantidadProcesos} procesos asociados\n";
                echo "    🔄 Reasignando procesos al pedido ID {$pedidoParaMantener->id}...\n";
                
                DB::table('procesos_pedidos_logo')
                    ->where('logo_pedido_id', $pedidoEliminar->id)
                    ->update(['logo_pedido_id' => $pedidoParaMantener->id]);
            }
            
            // Eliminar el registro duplicado
            DB::table('logo_pedidos')->where('id', $pedidoEliminar->id)->delete();
            echo "    ✅ Eliminado logo_pedido ID {$pedidoEliminar->id}\n";
        }
    }
    
    echo "\n";
}

echo "=================================================\n";
echo "✅ LIMPIEZA COMPLETADA\n";
echo "=================================================\n\n";
