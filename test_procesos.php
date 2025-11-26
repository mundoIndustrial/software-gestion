<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\PedidoProduccion;

echo "📋 Verificando procesos de una orden...\n\n";

// Obtener una orden cualquiera
$orden = PedidoProduccion::first();

if (!$orden) {
    echo "❌ No hay órdenes en la base de datos\n";
    exit;
}

echo "✅ Orden encontrada: " . $orden->numero_pedido . "\n";
echo "   Cliente: " . $orden->cliente . "\n";
echo "   ID: " . $orden->id . "\n\n";

// Obtener procesos de esa orden
$procesos = DB::table('procesos_prenda')
    ->where('pedidos_produccion_id', $orden->id)
    ->orderBy('fecha_inicio', 'asc')
    ->select('proceso', 'fecha_inicio', 'encargado', 'estado_proceso')
    ->get();

echo "📊 Procesos encontrados: " . count($procesos) . "\n\n";

if ($procesos->isEmpty()) {
    echo "   ⚠️ Esta orden no tiene procesos registrados\n";
} else {
    foreach ($procesos as $proceso) {
        echo "   • " . $proceso->proceso;
        echo " | Fecha: " . $proceso->fecha_inicio;
        echo " | Encargado: " . ($proceso->encargado ?? '-');
        echo " | Estado: " . $proceso->estado_proceso;
        echo "\n";
    }
}

// Agrupar por nombre de proceso único
$procesosUnicos = DB::table('procesos_prenda')
    ->where('pedidos_produccion_id', $orden->id)
    ->orderBy('fecha_inicio', 'asc')
    ->select('proceso', 'fecha_inicio', 'encargado', 'estado_proceso')
    ->get()
    ->groupBy('proceso')
    ->map(function($grupo) {
        return $grupo->first();
    });

echo "\n\n📌 Procesos únicos (por nombre):\n";
foreach ($procesosUnicos as $proceso) {
    echo "   • " . $proceso->proceso;
    echo " | Fecha: " . $proceso->fecha_inicio;
    echo "\n";
}

echo "\n✅ Completado\n";
