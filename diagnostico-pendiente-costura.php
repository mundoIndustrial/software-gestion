<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== DIAGNÓSTICO: PENDIENTE COSTURA ===\n\n";

// 1. Contar registros con area='Costura' y estado_bodega='Pendiente'
$totalCosturaPendiente = DB::table('bodega_detalles_talla')
    ->where('area', 'Costura')
    ->where('estado_bodega', 'Pendiente')
    ->whereNull('deleted_at')
    ->count();

echo "Total registros con area='Costura' y estado_bodega='Pendiente': {$totalCosturaPendiente}\n\n";

// 2. Listar pedidos únicos
$pedidosUnicos = DB::table('bodega_detalles_talla')
    ->select('numero_pedido')
    ->where('area', 'Costura')
    ->where('estado_bodega', 'Pendiente')
    ->whereNull('deleted_at')
    ->distinct()
    ->pluck('numero_pedido')
    ->toArray();

echo "Pedidos únicos: " . count($pedidosUnicos) . "\n";
echo "Números: " . implode(', ', $pedidosUnicos) . "\n\n";

// 3. Mostrar detalles de cada pedido
echo "=== DETALLES DE CADA PEDIDO ===\n\n";

foreach ($pedidosUnicos as $numeroPedido) {
    echo "--------------------------------------\n";
    echo "PEDIDO: {$numeroPedido}\n";
    echo "--------------------------------------\n";
    
    $registros = DB::table('bodega_detalles_talla')
        ->where('numero_pedido', $numeroPedido)
        ->where('area', 'Costura')
        ->where('estado_bodega', 'Pendiente')
        ->whereNull('deleted_at')
        ->get();
    
    echo "Total registros: " . $registros->count() . "\n";
    
    foreach ($registros as $registro) {
        echo "\n  ID: {$registro->id}\n";
        echo "  Prenda: {$registro->prenda_nombre}\n";
        echo "  Talla: {$registro->talla}\n";
        echo "  Cantidad: {$registro->cantidad}\n";
        echo "  Pendientes: {$registro->pendientes}\n";
        echo "  Área: {$registro->area}\n";
        echo "  Estado: {$registro->estado_bodega}\n";
        echo "  Prenda ID: {$registro->prenda_id}\n";
        echo "  Empresa: {$registro->empresa}\n";
        echo "  Asesor: {$registro->asesor}\n";
        
        // Verificar si la prenda existe y su de_bodega
        if ($registro->prenda_id) {
            $prenda = DB::table('prendas_pedido')
                ->where('id', $registro->prenda_id)
                ->whereNull('deleted_at')
                ->first();
            
            if ($prenda) {
                echo "  Prenda de_bodega: " . ($prenda->de_bodega ? 'true' : 'false') . "\n";
                
                // Verificar si tiene procesos
                $procesos = DB::table('pedidos_procesos_prenda_detalles')
                    ->where('prenda_pedido_id', $registro->prenda_id)
                    ->whereNull('deleted_at')
                    ->count();
                
                echo "  Tiene procesos: " . ($procesos > 0 ? "Sí ({$procesos})" : "No") . "\n";
            } else {
                echo "  Prenda: NO ENCONTRADA\n";
            }
        }
        
        echo "  ---\n";
    }
    
    echo "\n";
}

// 4. Resumen de áreas y estados
echo "\n=== RESUMEN DE TODAS LAS ÁREAS/ESTADOS ===\n\n";

$resumen = DB::table('bodega_detalles_talla')
    ->select('area', 'estado_bodega', DB::raw('COUNT(*) as total'))
    ->whereNull('deleted_at')
    ->groupBy('area', 'estado_bodega')
    ->orderBy('area')
    ->orderBy('estado_bodega')
    ->get();

foreach ($resumen as $row) {
    echo "Área: " . ($row->area ?? 'NULL') . " | Estado: " . ($row->estado_bodega ?? 'NULL') . " | Total: {$row->total}\n";
}

echo "\n=== FIN DEL DIAGNÓSTICO ===\n";
