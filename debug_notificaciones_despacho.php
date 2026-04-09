<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== DEBUG NOTIFICACIONES DESPACHO ===\n\n";

// Buscar un asesor con pedidos
$userId = DB::table('pedidos_produccion')
    ->orderByDesc('id')
    ->value('asesor_id');

if (!$userId) {
    echo "No hay pedidos en la base de datos.\n";
    exit(1);
}

echo "Usando asesor ID: $userId\n\n";

// Mostrar todos los pedidos del asesor
$pedidosAsesor = DB::table('pedidos_produccion')
    ->where('asesor_id', $userId)
    ->select(['id', 'numero_pedido', 'cliente', 'estado'])
    ->orderByDesc('updated_at')
    ->take(5)
    ->get();

echo "Pedidos del asesor ID $userId:\n";
foreach ($pedidosAsesor as $pedido) {
    echo "  - Pedido #{$pedido->numero_pedido} (ID: {$pedido->id}) - {$pedido->cliente} - Estado: {$pedido->estado}\n";
}
echo "\n";

// Para cada pedido, mostrar los items en bodega_detalles_talla
foreach ($pedidosAsesor as $pedido) {
    $items = DB::table('bodega_detalles_talla')
        ->where('pedido_produccion_id', $pedido->id)
        ->whereNull('deleted_at')
        ->select([
            'id',
            'area',
            'prenda_nombre',
            'estado_bodega',
            'costura_estado',
            'epp_estado',
            'talla',
            'cantidad'
        ])
        ->get();

    echo "Items del Pedido #{$pedido->numero_pedido}:\n";
    if ($items->isEmpty()) {
        echo "  (Sin items)\n";
    } else {
        foreach ($items as $item) {
            $estadoActual = match($item->area) {
                'Costura' => $item->costura_estado,
                'EPP' => $item->epp_estado,
                default => $item->estado_bodega
            };
            echo "  - {$item->prenda_nombre} ({$item->talla}) | Area: {$item->area} | Estado: {$estadoActual}\n";
            echo "    (bodega: {$item->estado_bodega} | costura: {$item->costura_estado} | epp: {$item->epp_estado})\n";
        }
    }
    echo "\n";
}

// Ejecutar el query de notificaciones CON NUEVA LÓGICA
echo "=== QUERY DE NOTIFICACIONES (NUEVA LÓGICA) ===\n\n";

$pedidosCompletosDespacho = DB::table('pedidos_produccion as pp')
    ->where('pp.asesor_id', $userId)
    ->select([
        'pp.id',
        'pp.numero_pedido',
        'pp.cliente',
        DB::raw("(
            SELECT COUNT(*)
            FROM prendas_pedido
            WHERE pedido_produccion_id = pp.id AND deleted_at IS NULL
        ) as total_prendas"),
        DB::raw("(
            SELECT COUNT(*)
            FROM pedido_epp
            WHERE pedido_produccion_id = pp.id AND deleted_at IS NULL
        ) as total_epps"),
        DB::raw("(
            SELECT COUNT(*)
            FROM bodega_detalles_talla
            WHERE pedido_produccion_id = pp.id 
              AND deleted_at IS NULL
              AND estado_bodega NOT IN ('Anulado', 'Homologar')
              AND estado_bodega = 'Entregado'
        ) as items_entregados"),
        DB::raw("(SELECT MAX(updated_at) FROM bodega_detalles_talla WHERE pedido_produccion_id = pp.id) as updated_at"),
    ])
    ->groupBy('pp.id', 'pp.numero_pedido', 'pp.cliente')
    // Total de items del pedido = prendas + epps
    ->havingRaw("(
        (SELECT COUNT(*) FROM prendas_pedido WHERE pedido_produccion_id = pp.id AND deleted_at IS NULL) +
        (SELECT COUNT(*) FROM pedido_epp WHERE pedido_produccion_id = pp.id AND deleted_at IS NULL)
    ) > 0")
    // Items entregados debe ser igual al total de items
    ->havingRaw("(
        SELECT COUNT(*) FROM bodega_detalles_talla
        WHERE pedido_produccion_id = pp.id 
          AND deleted_at IS NULL
          AND estado_bodega NOT IN ('Anulado', 'Homologar')
          AND estado_bodega = 'Entregado'
    ) = (
        (SELECT COUNT(*) FROM prendas_pedido WHERE pedido_produccion_id = pp.id AND deleted_at IS NULL) +
        (SELECT COUNT(*) FROM pedido_epp WHERE pedido_produccion_id = pp.id AND deleted_at IS NULL)
    )")
    ->orderByRaw('pp.updated_at DESC')
    ->get();

echo "Pedidos completos en despacho encontrados: " . $pedidosCompletosDespacho->count() . "\n\n";

if ($pedidosCompletosDespacho->isEmpty()) {
    echo "No hay pedidos que cumplan las condiciones.\n\n";
    
    // Debug: mostrar qué pasa con un pedido específico
    echo "=== DEBUG: Análisis de un pedido específico ===\n\n";
    
    $primerPedido = $pedidosAsesor->first();
    if ($primerPedido) {
        echo "Analizando Pedido #{$primerPedido->numero_pedido} (ID: {$primerPedido->id})\n\n";
        
        $itemsDebug = DB::table('bodega_detalles_talla')
            ->where('pedido_produccion_id', $primerPedido->id)
            ->whereNull('deleted_at')
            ->get();
        
        echo "Total items: " . $itemsDebug->count() . "\n\n";
        
        $noAnuladosCount = 0;
        $pendientesCount = 0;
        
        foreach ($itemsDebug as $item) {
            $estadoSegunArea = $item->estado_bodega; // Usar solo estado_bodega
            
            if (!in_array($estadoSegunArea, ['Anulado', 'Homologar'])) {
                $noAnuladosCount++;
                echo "✓ {$item->prenda_nombre} ({$item->area}): {$estadoSegunArea}\n";
                
                if ($estadoSegunArea !== 'Entregado') {
                    $pendientesCount++;
                    echo "  → PENDIENTE: no está Entregado\n";
                }
            } else {
                echo "✗ {$item->prenda_nombre} ({$item->area}): {$estadoSegunArea} (ignorado)\n";
            }
        }
        
        echo "\nResumen:\n";
        echo "  Items no-anulados: $noAnuladosCount\n";
        echo "  Items pendientes: $pendientesCount\n";
        echo "  ¿Debería salir notificación? " . ($noAnuladosCount > 0 && $pendientesCount === 0 ? "SÍ" : "NO") . "\n";
    }
} else {
    foreach ($pedidosCompletosDespacho as $pedido) {
        $totalItems = ($pedido->total_prendas ?? 0) + ($pedido->total_epps ?? 0);
        echo "✓ Pedido #{$pedido->numero_pedido} ({$pedido->cliente})\n";
        echo "  Prendas: {$pedido->total_prendas} | EPPs: {$pedido->total_epps} | Total: {$totalItems}\n";
        echo "  Items Entregado: {$pedido->items_entregados}\n";
        echo "  ✓ CUMPLE: Todos los {$totalItems} items están Entregado\n\n";
    }
}

echo "\n=== DEBUG ESPECÍFICO: PEDIDO #156 ===\n\n";

$pedido156 = DB::table('pedidos_produccion as pp')
    ->where('pp.id', 156)
    ->select([
        'pp.numero_pedido',
        'pp.cliente',
        DB::raw("(SELECT COUNT(*) FROM prendas_pedido WHERE pedido_produccion_id = pp.id AND deleted_at IS NULL) as total_prendas"),
        DB::raw("(SELECT COUNT(*) FROM pedido_epp WHERE pedido_produccion_id = pp.id AND deleted_at IS NULL) as total_epps"),
        DB::raw("(SELECT COUNT(*) FROM bodega_detalles_talla WHERE pedido_produccion_id = pp.id AND deleted_at IS NULL AND estado_bodega NOT IN ('Anulado', 'Homologar') AND estado_bodega = 'Entregado') as items_entregados"),
    ])
    ->first();

if ($pedido156) {
    $totalItems = ($pedido156->total_prendas ?? 0) + ($pedido156->total_epps ?? 0);
    echo "Pedido #156:\n";
    echo "  Prendas: {$pedido156->total_prendas}\n";
    echo "  EPPs: {$pedido156->total_epps}\n";
    echo "  Total items: {$totalItems}\n";
    echo "  Items Entregado (en bodega): {$pedido156->items_entregados}\n";
    echo "  ¿Debe salir notificación? " . ($pedido156->items_entregados === $totalItems && $totalItems > 0 ? "SÍ" : "NO") . "\n";
    if ($pedido156->items_entregados !== $totalItems) {
        echo "  Razón: Faltan " . ($totalItems - $pedido156->items_entregados) . " items\n";
    }
} else {
    echo "Pedido #156 no encontrado.\n";
}
