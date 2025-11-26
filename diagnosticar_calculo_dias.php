<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PedidoProduccion;
use App\Models\ProcesosPrenda;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "\n=== DIAGNÓSTICO DEL CÁLCULO DE DÍAS - PEDIDO #45395 ===\n\n";

// Obtener el pedido específico
$pedido = PedidoProduccion::where('numero_pedido', 45395)->first();

if(!$pedido) {
    echo "❌ Pedido #45395 no encontrado\n";
    exit;
}

echo "Pedido: #{$pedido->numero_pedido}\n";
echo "Cliente: {$pedido->cliente}\n";
echo "Estado: {$pedido->estado}\n";
echo "Fecha Creación: {$pedido->fecha_de_creacion_de_orden}\n\n";

// Obtener procesos
$procesos = ProcesosPrenda::where('numero_pedido', $pedido->numero_pedido)
    ->whereNotNull('fecha_inicio')
    ->orderBy('fecha_inicio', 'asc')
    ->get();

echo "📊 Procesos encontrados: {$procesos->count()}\n\n";

if($procesos->count() > 0) {
    echo "Detalle de procesos:\n";
    foreach($procesos as $p) {
        echo "  - {$p->proceso}: {$p->fecha_inicio} (Estado: {$p->estado_proceso})\n";
    }
    
    // Simular cálculo
    $fechaInicio = Carbon::parse($procesos->first()->fecha_inicio);
    $hoy = Carbon::now();
    
    echo "\n📅 Cálculo:\n";
    echo "  Fecha Inicio: {$fechaInicio->format('d/m/Y')} (22/11/2025)\n";
    echo "  Fecha Hoy: {$hoy->format('d/m/Y')}\n";
    
    $diasTotales = $fechaInicio->diffInDays($hoy);
    echo "  Días totales (incluyendo weekends): $diasTotales\n";
    
    // Contar fines de semana
    $weekends = 0;
    $current = $fechaInicio->copy();
    while($current < $hoy) {
        if($current->isWeekend()) {
            $weekends++;
        }
        $current->addDay();
    }
    echo "  Fines de semana en rango: $weekends\n";
    
    $diasHabiles = $diasTotales - $weekends;
    echo "  ✅ Días hábiles calculados: $diasHabiles\n";
    
} else {
    echo "⚠️ Sin procesos para este pedido\n";
}

// Verificar en BD directamente
echo "\n\n=== VERIFICACIÓN DIRECTA EN BD ===\n";
$procesosDB = DB::table('procesos_prenda')
    ->where('numero_pedido', 45395)
    ->select('proceso', 'fecha_inicio', 'estado_proceso')
    ->get();

echo "Procesos en BD: {$procesosDB->count()}\n";
foreach($procesosDB as $p) {
    echo "  - {$p->proceso}: {$p->fecha_inicio}\n";
}
?>
