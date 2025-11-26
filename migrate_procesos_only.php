<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\PrendaPedido;

// Obtener todas las prendas
$prendas = PrendaPedido::all();

$creados = 0;
$errores = 0;

foreach ($prendas as $prenda) {
    try {
        // Procesos por prenda - 13 tipos diferentes
        $procesos = [
            ['tipo' => 'Creación', 'dias_duracion' => 1],
            ['tipo' => 'Corte', 'dias_duracion' => 3],
            ['tipo' => 'Costura', 'dias_duracion' => 5],
            ['tipo' => 'Revisión', 'dias_duracion' => 1],
            ['tipo' => 'Calidad', 'dias_duracion' => 2],
            ['tipo' => 'Empaque', 'dias_duracion' => 1],
            ['tipo' => 'Envío', 'dias_duracion' => 3],
            ['tipo' => 'Despacho', 'dias_duracion' => 1],
            ['tipo' => 'Entrega', 'dias_duracion' => 2],
            ['tipo' => 'Seguimiento', 'dias_duracion' => 1],
            ['tipo' => 'Facturación', 'dias_duracion' => 1],
            ['tipo' => 'Radicación', 'dias_duracion' => 1],
            ['tipo' => 'Archivos', 'dias_duracion' => 1],
        ];

        foreach ($procesos as $proceso) {
            DB::table('procesos_prenda')->insert([
                'pedidos_produccion_id' => $prenda->pedido_produccion_id,  // ✅ CORRECCIÓN: Usar pedidos_produccion_id
                'tipo_proceso' => $proceso['tipo'],
                'dias_duracion' => $proceso['dias_duracion'],
                'estado' => 'Pendiente',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $creados++;
        }
    } catch (\Exception $e) {
        $errores++;
        echo "❌ Error en prenda {$prenda->id}: " . $e->getMessage() . "\n";
    }
}

echo "\n📊 RESULTADOS DE LA MIGRACIÓN:\n";
echo "✅ Procesos creados: " . $creados . "\n";
echo "❌ Errores: " . $errores . "\n";
echo "Total de prendas procesadas: " . count($prendas) . "\n";

// Verificar en BD
$totalEnBD = DB::table('procesos_prenda')->count();
echo "\n📋 Procesos en la BD: " . $totalEnBD . "\n";
