<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use Illuminate\Support\Facades\DB;

echo "=== ANÁLISIS DEL PEDIDO #37 ===\n\n";

// Buscar el pedido
$pedido = PedidoProduccion::where('numero_pedido', '37')->first();

if (!$pedido) {
    echo "❌ Pedido #37 no encontrado\n";
    exit(1);
}

echo "✅ Pedido encontrado\n";
echo "ID: {$pedido->id}\n";
echo "Número: {$pedido->numero_pedido}\n";
echo "Cliente: {$pedido->cliente}\n";
echo "Estado: {$pedido->estado}\n";
echo "Fecha creación: {$pedido->created_at}\n\n";

// Analizar prendas
echo "=== PRENDAS DEL PEDIDO ===\n";
$prendas = PrendaPedido::where('pedido_produccion_id', $pedido->id)
    ->whereNull('deleted_at')
    ->get();

echo "Total prendas: {$prendas->count()}\n\n";

foreach ($prendas as $prenda) {
    echo "Prenda ID: {$prenda->id}\n";
    echo "  Nombre: {$prenda->nombre_prenda}\n";
    echo "  De Bodega: " . ($prenda->de_bodega ? '✅ SÍ (true)' : '❌ NO (false)') . "\n";
    echo "  Deleted: " . ($prenda->deleted_at ? 'SÍ' : 'NO') . "\n";
    echo "\n";
}

$prendasDeBodega = $prendas->where('de_bodega', 1)->count();
$prendasNoDeBodega = $prendas->where('de_bodega', 0)->count();

echo "Resumen prendas:\n";
echo "  - De bodega (de_bodega = 1): {$prendasDeBodega}\n";
echo "  - No de bodega (de_bodega = 0): {$prendasNoDeBodega}\n\n";

// Analizar bodega_detalles_talla
echo "=== REGISTROS EN BODEGA_DETALLES_TALLA ===\n";
$bodegaDetalles = DB::table('bodega_detalles_talla')
    ->where('pedido_produccion_id', $pedido->id)
    ->whereNull('deleted_at')
    ->get();

echo "Total registros: {$bodegaDetalles->count()}\n\n";

$areasCostura = $bodegaDetalles->where('area', 'Costura');
$areasEpp = $bodegaDetalles->where('area', 'EPP');
$areasOtro = $bodegaDetalles->where('area', 'Otro');

echo "Por área:\n";
echo "  - Costura: {$areasCostura->count()}\n";
echo "  - EPP: {$areasEpp->count()}\n";
echo "  - Otro: {$areasOtro->count()}\n\n";

// Detalle de Costura
if ($areasCostura->count() > 0) {
    echo "Detalle Costura:\n";
    $costuraPendiente = $areasCostura->where('estado_bodega', 'Pendiente');
    $costuraEntregado = $areasCostura->where('estado_bodega', 'Entregado');
    $costuraAnulado = $areasCostura->where('estado_bodega', 'Anulado');
    
    echo "  - Pendiente: {$costuraPendiente->count()}\n";
    echo "  - Entregado: {$costuraEntregado->count()}\n";
    echo "  - Anulado: {$costuraAnulado->count()}\n";
    
    if ($costuraPendiente->count() > 0) {
        echo "\n  Registros pendientes de Costura:\n";
        foreach ($costuraPendiente as $detalle) {
            echo "    - ID: {$detalle->id} | Prenda: {$detalle->prenda_nombre} | Talla: {$detalle->talla} | Cantidad: {$detalle->cantidad}\n";
        }
    }
    echo "\n";
}

// Detalle de EPP
if ($areasEpp->count() > 0) {
    echo "Detalle EPP:\n";
    $eppPendiente = $areasEpp->where('estado_bodega', 'Pendiente');
    $eppEntregado = $areasEpp->where('estado_bodega', 'Entregado');
    $eppAnulado = $areasEpp->where('estado_bodega', 'Anulado');
    
    echo "  - Pendiente: {$eppPendiente->count()}\n";
    echo "  - Entregado: {$eppEntregado->count()}\n";
    echo "  - Anulado: {$eppAnulado->count()}\n";
    
    if ($eppPendiente->count() > 0) {
        echo "\n  Registros pendientes de EPP:\n";
        foreach ($eppPendiente as $detalle) {
            $epp = DB::table('epp')->where('id', $detalle->pedido_epp_id)->first();
            $eppNombre = $epp ? $epp->nombre : 'N/A';
            echo "    - ID: {$detalle->id} | EPP: {$eppNombre} | Cantidad: {$detalle->cantidad}\n";
        }
    }
    echo "\n";
}

// Análisis de por qué aparece
echo "=== ANÁLISIS: ¿POR QUÉ APARECE EN PENDIENTES? ===\n\n";

$debeMostrarseEnCostura = false;
$debeMostrarseEnEpp = false;

// Para costura: debe tener prendas de_bodega=true Y registros pendientes en bodega_detalles_talla con area=Costura
if ($prendasDeBodega > 0 && $areasCostura->where('estado_bodega', 'Pendiente')->count() > 0) {
    $debeMostrarseEnCostura = true;
    echo "✅ DEBE aparecer en pendientes de COSTURA:\n";
    echo "   - Tiene {$prendasDeBodega} prenda(s) con de_bodega = true\n";
    echo "   - Tiene " . $areasCostura->where('estado_bodega', 'Pendiente')->count() . " registro(s) pendiente(s) en bodega_detalles_talla con area=Costura\n\n";
}

if ($prendasNoDeBodega > 0 && $prendasDeBodega == 0 && $areasCostura->where('estado_bodega', 'Pendiente')->count() > 0) {
    echo "❌ NO DEBE aparecer en pendientes de COSTURA:\n";
    echo "   - Todas las prendas tienen de_bodega = false ({$prendasNoDeBodega} prendas)\n";
    echo "   - Los registros en bodega_detalles_talla son solo para seguimiento de producción\n";
    echo "   - No hay despacho de bodega necesario\n\n";
}

// Para EPP: debe tener registros pendientes en bodega_detalles_talla con area=EPP
if ($areasEpp->where('estado_bodega', 'Pendiente')->count() > 0) {
    $debeMostrarseEnEpp = true;
    echo "✅ DEBE aparecer en pendientes de EPP:\n";
    echo "   - Tiene " . $areasEpp->where('estado_bodega', 'Pendiente')->count() . " registro(s) pendiente(s) en bodega_detalles_talla con area=EPP\n\n";
}

if (!$debeMostrarseEnCostura && !$debeMostrarseEnEpp) {
    echo "❌ ESTE PEDIDO NO DEBE APARECER EN /despacho/pendientes\n";
    echo "   Razón: No cumple las condiciones para estar pendiente de despacho\n\n";
} else {
    echo "✅ ESTE PEDIDO SÍ DEBE APARECER EN /despacho/pendientes\n";
    if ($debeMostrarseEnCostura) {
        echo "   - Como pendiente de Costura\n";
    }
    if ($debeMostrarseEnEpp) {
        echo "   - Como pendiente de EPP\n";
    }
    echo "\n";
}

// Verificar procesamiento de pedidos
echo "=== VERIFICACIÓN EN PROCESOS DE PRENDA ===\n";
$procesosDetalles = DB::table('pedidos_procesos_prenda_detalles')
    ->whereIn('prenda_pedido_id', $prendas->pluck('id'))
    ->get();

echo "Total registros en pedidos_procesos_prenda_detalles: {$procesosDetalles->count()}\n";

if ($procesosDetalles->count() > 0) {
    echo "Detalle:\n";
    foreach ($prendas as $prenda) {
        $procesosPrenda = $procesosDetalles->where('prenda_pedido_id', $prenda->id);
        if ($procesosPrenda->count() > 0) {
            echo "  - Prenda ID {$prenda->id} ({$prenda->nombre_prenda}): {$procesosPrenda->count()} proceso(s)\n";
        }
    }
}

echo "\n=== FIN DEL ANÁLISIS ===\n";
