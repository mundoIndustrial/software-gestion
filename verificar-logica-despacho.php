<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== VERIFICACIÓN LÓGICA DE DESPACHO PENDIENTES ===\n\n";
echo "Reglas:\n";
echo "1. Prendas con de_bodega=false → NO deben aparecer\n";
echo "2. Prendas con de_bodega=true PERO con procesos → NO deben aparecer\n";
echo "3. Prendas con de_bodega=true SIN procesos Y con registros pendientes → SÍ deben aparecer\n";
echo "4. EPP pendientes → SÍ deben aparecer\n\n";
echo str_repeat('=', 100) . "\n\n";

// Obtener todos los pedidos con registros pendientes en bodega
$pedidosConPendientes = DB::table('bodega_detalles_talla as bdt')
    ->join('pedidos_produccion as pp', 'pp.id', '=', 'bdt.pedido_produccion_id')
    ->where('bdt.estado_bodega', 'Pendiente')
    ->whereNull('bdt.deleted_at')
    ->whereNull('pp.deleted_at')
    ->select('pp.id', 'pp.numero_pedido', 'pp.cliente', 'bdt.area')
    ->distinct()
    ->orderBy('pp.numero_pedido')
    ->get();

echo "Total pedidos con registros pendientes en bodega_detalles_talla: " . $pedidosConPendientes->count() . "\n\n";

foreach ($pedidosConPendientes as $pedido) {
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "PEDIDO #{$pedido->numero_pedido} (ID: {$pedido->id}) - {$pedido->cliente}\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    // Analizar registros pendientes
    $registrosCosturaPendientes = DB::table('bodega_detalles_talla')
        ->where('pedido_produccion_id', $pedido->id)
        ->where('area', 'Costura')
        ->where('estado_bodega', 'Pendiente')
        ->whereNull('deleted_at')
        ->get();
    
    $registrosEppPendientes = DB::table('bodega_detalles_talla')
        ->where('pedido_produccion_id', $pedido->id)
        ->where('area', 'EPP')
        ->where('estado_bodega', 'Pendiente')
        ->whereNull('deleted_at')
        ->get();
    
    $debeAparecerCostura = false;
    $debeAparecerEpp = false;
    $razon = [];
    
    // Analizar COSTURA
    if ($registrosCosturaPendientes->count() > 0) {
        echo "📦 COSTURA: {$registrosCosturaPendientes->count()} registro(s) pendiente(s)\n";
        
        foreach ($registrosCosturaPendientes as $reg) {
            // Verificar prenda vinculada
            if ($reg->prenda_id) {
                $prenda = DB::table('prendas_pedido')
                    ->where('id', $reg->prenda_id)
                    ->first(['id', 'nombre_prenda', 'de_bodega']);
                
                if ($prenda) {
                    // Verificar si tiene procesos
                    $tieneProcesos = DB::table('pedidos_procesos_prenda_detalles')
                        ->where('prenda_pedido_id', $prenda->id)
                        ->whereNull('deleted_at')
                        ->exists();
                    
                    $icon = '  ';
                    if ($prenda->de_bodega && !$tieneProcesos) {
                        $icon = '✅';
                        $debeAparecerCostura = true;
                    } else if (!$prenda->de_bodega) {
                        $icon = '❌';
                        $razon[] = "Prenda {$prenda->id} tiene de_bodega=false";
                    } else if ($tieneProcesos) {
                        $icon = '⚠️ ';
                        $razon[] = "Prenda {$prenda->id} tiene procesos pendientes";
                    }
                    
                    echo "  {$icon} Reg #{$reg->id}: {$prenda->nombre_prenda} (prenda_id={$prenda->id})\n";
                    echo "       - de_bodega: " . ($prenda->de_bodega ? 'true' : 'false') . "\n";
                    echo "       - tiene_procesos: " . ($tieneProcesos ? 'SÍ' : 'NO') . "\n";
                } else {
                    echo "  ⚠️  Reg #{$reg->id}: prenda_id={$reg->prenda_id} NO ENCONTRADA\n";
                }
            } else {
                echo "  ⚠️  Reg #{$reg->id}: Sin prenda_id vinculada\n";
            }
        }
        echo "\n";
    }
    
    // Analizar EPP
    if ($registrosEppPendientes->count() > 0) {
        echo "🛡️  EPP: {$registrosEppPendientes->count()} registro(s) pendiente(s)\n";
        
        foreach ($registrosEppPendientes as $reg) {
            $debeAparecerEpp = true;
            echo "  ✅ Reg #{$reg->id}: pedido_epp_id={$reg->pedido_epp_id}\n";
        }
        echo "\n";
    }
    
    // CONCLUSIÓN
    echo "═════════════════════════════════════════════════════════════════════════════════════════════\n";
    if ($debeAparecerCostura || $debeAparecerEpp) {
        echo "✅ DEBE APARECER en /despacho/pendientes\n";
        if ($debeAparecerCostura) {
            echo "   → Como pendiente de Costura (tiene prendas de_bodega=true sin procesos)\n";
        }
        if ($debeAparecerEpp) {
            echo "   → Como pendiente de EPP\n";
        }
    } else {
        echo "❌ NO DEBE APARECER en /despacho/pendientes\n";
        if (count($razon) > 0) {
            echo "   Razones:\n";
            foreach ($razon as $r) {
                echo "   → {$r}\n";
            }
        }
    }
    echo "═════════════════════════════════════════════════════════════════════════════════════════════\n\n";
}

echo "\n" . str_repeat('=', 100) . "\n";
echo "=== FIN DE LA VERIFICACIÓN ===\n";
