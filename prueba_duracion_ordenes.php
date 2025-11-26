<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\PedidoProduccion;
use Carbon\Carbon;

echo "\n=== PRUEBA: CÁLCULO DE DURACIÓN PARA ÓRDENES ENTREGADAS ===\n\n";

// Obtener órdenes entregadas
$ordenesEntregadas = PedidoProduccion::where('estado', 'Entregado')
    ->limit(5)
    ->get();

echo "📋 Órdenes Entregadas (primeras 5):\n\n";

foreach($ordenesEntregadas as $orden) {
    echo "Pedido #{$orden->numero_pedido} - {$orden->cliente}\n";
    echo "  Creación: {$orden->fecha_de_creacion_de_orden}\n";
    
    // Obtener procesos
    $procesos = DB::table('procesos_prenda')
        ->where('numero_pedido', $orden->numero_pedido)
        ->whereNotNull('fecha_inicio')
        ->orderBy('fecha_inicio', 'asc')
        ->get();
    
    if($procesos->count() > 0) {
        echo "  Procesos: {$procesos->count()}\n";
        foreach($procesos as $p) {
            echo "    - {$p->proceso}: {$p->fecha_inicio}\n";
        }
        
        // Calcular duración
        $fechaInicio = Carbon::parse($procesos->first()->fecha_inicio);
        $fechaFin = Carbon::parse($procesos->last()->fecha_inicio);
        $dias = $fechaInicio->diffInDays($fechaFin);
        
        echo "  📊 Duración (primero a último proceso): $dias días\n";
    } else {
        echo "  ⚠️ Sin procesos registrados\n";
    }
    echo "\n";
}

echo "\n✅ Prueba completada: El sistema ahora puede calcular duración desde procesos_prenda\n";
?>
