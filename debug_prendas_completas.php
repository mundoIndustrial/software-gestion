<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$userId = DB::table('pedidos_produccion')
    ->orderByDesc('id')
    ->value('asesor_id');

echo "=== DEBUG NOTIFICACIONES DESPACHO V2 (Por Prenda Completa) ===\n";
echo "Usando asesor ID: $userId\n\n";

// Ejecutar el query de notificaciones CON NUEVA LÓGICA V2
echo "=== QUERY DE NOTIFICACIONES (LÓGICA V2 - Prendas Completas) ===\n\n";

$sql = <<<'SQL'
SELECT 
    pp.id,
    pp.numero_pedido,
    pp.cliente,
    MAX(bdt.updated_at) as updated_at,
    (SELECT COUNT(DISTINCT pr.id) 
     FROM prendas_pedido pr 
     WHERE pr.pedido_produccion_id = pp.id AND pr.deleted_at IS NULL) as total_prendas,
    (SELECT COUNT(DISTINCT pe.id) 
     FROM pedido_epp pe 
     WHERE pe.pedido_produccion_id = pp.id AND pe.deleted_at IS NULL) as total_epps,
    (SELECT COUNT(DISTINCT bdt_inner.prenda_id)
     FROM bodega_detalles_talla bdt_inner
     WHERE bdt_inner.pedido_produccion_id = pp.id
       AND bdt_inner.deleted_at IS NULL
       AND bdt_inner.prenda_id IS NOT NULL
       AND NOT EXISTS (
           SELECT 1 FROM bodega_detalles_talla bdt_check
           WHERE bdt_check.pedido_produccion_id = pp.id
             AND bdt_check.prenda_id = bdt_inner.prenda_id
             AND bdt_check.deleted_at IS NULL
             AND bdt_check.estado_bodega NOT IN ('Anulado', 'Homologar')
             AND bdt_check.estado_bodega <> 'Entregado'
       )) as prendas_completas,
    (SELECT COUNT(DISTINCT bdt_epp.pedido_epp_id)
     FROM bodega_detalles_talla bdt_epp
     WHERE bdt_epp.pedido_produccion_id = pp.id
       AND bdt_epp.deleted_at IS NULL
       AND bdt_epp.pedido_epp_id IS NOT NULL
       AND bdt_epp.estado_bodega = 'Entregado') as epps_completos
FROM pedidos_produccion pp
LEFT JOIN bodega_detalles_talla as bdt ON bdt.pedido_produccion_id = pp.id
WHERE pp.asesor_id = ?
GROUP BY pp.id, pp.numero_pedido, pp.cliente
HAVING (
    (SELECT COUNT(DISTINCT pr.id) 
     FROM prendas_pedido pr 
     WHERE pr.pedido_produccion_id = pp.id AND pr.deleted_at IS NULL) +
    (SELECT COUNT(DISTINCT pe.id) 
     FROM pedido_epp pe 
     WHERE pe.pedido_produccion_id = pp.id AND pe.deleted_at IS NULL)
) > 0
AND
(SELECT COUNT(DISTINCT pr.id) 
 FROM prendas_pedido pr 
 WHERE pr.pedido_produccion_id = pp.id AND pr.deleted_at IS NULL) = 
(SELECT COUNT(DISTINCT bdt_inner.prenda_id)
 FROM bodega_detalles_talla bdt_inner
 WHERE bdt_inner.pedido_produccion_id = pp.id
   AND bdt_inner.deleted_at IS NULL
   AND bdt_inner.prenda_id IS NOT NULL
   AND NOT EXISTS (
       SELECT 1 FROM bodega_detalles_talla bdt_check
       WHERE bdt_check.pedido_produccion_id = pp.id
         AND bdt_check.prenda_id = bdt_inner.prenda_id
         AND bdt_check.deleted_at IS NULL
         AND bdt_check.estado_bodega NOT IN ('Anulado', 'Homologar')
         AND bdt_check.estado_bodega <> 'Entregado'
   ))
AND
(SELECT COUNT(DISTINCT pe.id) 
 FROM pedido_epp pe 
 WHERE pe.pedido_produccion_id = pp.id AND pe.deleted_at IS NULL) = 
(SELECT COUNT(DISTINCT bdt_epp.pedido_epp_id)
 FROM bodega_detalles_talla bdt_epp
 WHERE bdt_epp.pedido_produccion_id = pp.id
   AND bdt_epp.deleted_at IS NULL
   AND bdt_epp.pedido_epp_id IS NOT NULL
   AND bdt_epp.estado_bodega = 'Entregado')
ORDER BY MAX(bdt.updated_at) DESC
SQL;

$pedidosCompletosDespacho = DB::select($sql, [$userId]);

echo "Pedidos completos en despacho encontrados: " . count($pedidosCompletosDespacho) . "\n\n";

if (empty($pedidosCompletosDespacho)) {
    echo "No hay pedidos que cumplan las condiciones.\n";
} else {
    foreach ($pedidosCompletosDespacho as $pedido) {
        $totalItems = ($pedido->total_prendas ?? 0) + ($pedido->total_epps ?? 0);
        echo "✓ Pedido #{$pedido->numero_pedido} ({$pedido->cliente})\n";
        echo "  Total prendas: {$pedido->total_prendas} | Total epps: {$pedido->total_epps}\n";
        echo "  Prendas completas: {$pedido->prendas_completas} | EPPs completos: {$pedido->epps_completos}\n";
        echo "  Total items: {$totalItems}\n\n";
    }
}

echo "\n=== DEBUG ESPECÍFICO: PEDIDO #156 ===\n\n";

// Análisis detallado del pedido 156
$prendas156 = DB::table('prendas_pedido')
    ->where('pedido_produccion_id', 156)
    ->whereNull('deleted_at')
    ->get();

echo "Prendas del pedido 156:\n";
foreach ($prendas156 as $prenda) {
    echo "  Prenda ID {$prenda->id}: {$prenda->nombre_prenda}\n";
    
    // Verificar si está completa
    $tallasRegistradas = DB::table('bodega_detalles_talla')
        ->where('pedido_produccion_id', 156)
        ->where('prenda_id', $prenda->id)
        ->whereNull('deleted_at')
        ->where(function($q) {
            $q->whereNotIn('estado_bodega', ['Anulado', 'Homologar'])
              ->orWhere('estado_bodega', 'Entregado');
        })
        ->select(['talla', 'estado_bodega'])
        ->get();
    
    $tallasNoEntregado = DB::table('bodega_detalles_talla')
        ->where('pedido_produccion_id', 156)
        ->where('prenda_id', $prenda->id)
        ->whereNull('deleted_at')
        ->whereNotIn('estado_bodega', ['Anulado', 'Homologar'])
        ->where('estado_bodega', '<>', 'Entregado')
        ->select(['talla', 'estado_bodega'])
        ->get();
    
    if ($tallasRegistradas->count() > 0) {
        echo "    Tallas registradas:\n";
        foreach ($tallasRegistradas as $talla) {
            echo "      - Talla {$talla->talla}: {$talla->estado_bodega}\n";
        }
    } else {
        echo "    Sin registros en bodega\n";
    }
    
    if ($tallasNoEntregado->count() > 0) {
        echo "    ⚠ Tallas PENDIENTES:\n";
        foreach ($tallasNoEntregado as $talla) {
            echo "      - Talla {$talla->talla}: {$talla->estado_bodega}\n";
        }
    }
}

echo "\nEPPs del pedido 156:\n";
$epps156 = DB::table('pedido_epp')
    ->where('pedido_produccion_id', 156)
    ->whereNull('deleted_at')
    ->count();
    
echo "  Total EPPs: $epps156\n";

if ($prendas156->count() === 0 && $epps156 === 0) {
    echo "  (Pedido sin prendas ni EPPs)\n";
}

echo "\n¿Debería salir notificación? " . ((empty($pedidosCompletosDespacho) || !in_array(156, array_column($pedidosCompletosDespacho, 'id'))) ? "NO ✓" : "SÍ") . "\n";
