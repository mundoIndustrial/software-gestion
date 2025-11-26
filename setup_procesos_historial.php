<?php
// Script para setup de procesos_historial y ajustes en procesos_prenda

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

try {
    // Eliminar tabla si existe
    if (Schema::hasTable('procesos_historial')) {
        Schema::dropIfExists('procesos_historial');
        echo "ℹ️  Tabla procesos_historial eliminada\n";
    }

    // Crear tabla de historial
    Schema::create('procesos_historial', function ($table) {
        $table->id();
        $table->integer('numero_pedido')->index();
        $table->string('proceso')->nullable();
        $table->date('fecha_inicio')->nullable();
        $table->string('encargado')->nullable();
        $table->string('estado_proceso')->default('En Progreso');
        $table->timestamps();
        $table->index(['numero_pedido', 'created_at']);
    });
    echo "✅ Tabla procesos_historial creada correctamente\n";

    // Migrar datos existentes al historial
    $procesos = DB::table('procesos_prenda')->get();
    $count = 0;
    foreach ($procesos as $proceso) {
        DB::table('procesos_historial')->insert([
            'numero_pedido' => $proceso->numero_pedido,
            'proceso' => $proceso->proceso,
            'fecha_inicio' => $proceso->fecha_inicio,
            'encargado' => $proceso->encargado,
            'estado_proceso' => $proceso->estado_proceso ?? 'En Progreso',
            'created_at' => $proceso->created_at,
            'updated_at' => $proceso->updated_at,
        ]);
        $count++;
    }
    echo "✅ $count registros migrados al historial\n";

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
