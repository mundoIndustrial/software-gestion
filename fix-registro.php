<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\RegistroPisoCorte;

echo "=== ACTUALIZANDO REGISTRO ===\n\n";

$registro = RegistroPisoCorte::where('id', 5)->first();

if ($registro) {
    echo "Registro encontrado: Orden {$registro->orden_produccion}\n";
    echo "Tiempo ciclo actual: {$registro->tiempo_ciclo}\n";
    
    // Actualizar tiempo_ciclo a 97
    $registro->tiempo_ciclo = 97;
    
    // Recalcular meta
    $tiempo_disponible = $registro->tiempo_disponible;
    $meta = $tiempo_disponible / 97;
    $registro->meta = $meta;
    
    // Recalcular eficiencia
    $eficiencia = $meta > 0 ? ($registro->cantidad / $meta) : 0;
    $registro->eficiencia = $eficiencia;
    
    $registro->save();
    
    echo "\n✅ Registro actualizado:\n";
    echo "Tiempo ciclo nuevo: {$registro->tiempo_ciclo}\n";
    echo "Meta nueva: {$registro->meta}\n";
    echo "Eficiencia nueva: {$registro->eficiencia}\n";
} else {
    echo "No se encontró el registro\n";
}
