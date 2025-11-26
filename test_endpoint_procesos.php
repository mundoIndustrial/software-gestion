<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\PedidoProduccion;

echo "🧪 Simulando llamada al endpoint getProcesos...\n\n";

// Obtener una orden con procesos
$ordenConProcesos = DB::table('procesos_prenda')
    ->groupBy('pedidos_produccion_id')
    ->pluck('pedidos_produccion_id')
    ->first();

$orden = PedidoProduccion::find($ordenConProcesos);

if (!$orden) {
    echo "❌ No hay órdenes disponibles\n";
    exit;
}

echo "Orden: " . $orden->numero_pedido . " (ID: " . $orden->id . ")\n";
echo "Cliente: " . $orden->cliente . "\n";
echo "Asesor ID: " . $orden->asesor_id . "\n";
echo "Fecha de creación: " . $orden->fecha_de_creacion_de_orden . "\n";
echo "Fecha estimada: " . $orden->fecha_estimada_de_entrega . "\n\n";

// Obtener procesos
$procesos = DB::table('procesos_prenda')
    ->where('pedidos_produccion_id', $orden->id)
    ->orderBy('fecha_inicio', 'asc')
    ->select('proceso', 'fecha_inicio', 'encargado', 'estado_proceso')
    ->get()
    ->groupBy('proceso')
    ->map(function($grupo) {
        return $grupo->first();
    })
    ->values();

echo "📊 Procesos encontrados: " . count($procesos) . "\n";
foreach ($procesos as $proceso) {
    echo "   • " . $proceso->proceso . " | " . $proceso->fecha_inicio . " | " . $proceso->encargado . "\n";
}

// Simular respuesta JSON
$response = [
    'numero_pedido' => $orden->numero_pedido,
    'cliente' => $orden->cliente,
    'fecha_inicio' => $orden->fecha_de_creacion_de_orden,
    'fecha_estimada_de_entrega' => $orden->fecha_estimada_de_entrega,
    'procesos' => $procesos
];

echo "\n\n📋 Respuesta JSON:\n";
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

echo "\n\n✅ Completado\n";
