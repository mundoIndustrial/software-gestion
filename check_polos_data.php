<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$registros = \App\Models\RegistroPisoPolo::take(5)->get(['id', 'hora', 'modulo', 'cantidad', 'meta', 'tiempo_ciclo', 'porcion_tiempo', 'numero_operarios', 'tiempo_parada_no_programada', 'tiempo_para_programada']);

echo "Primeros 5 registros de Polos:\n";
foreach ($registros as $registro) {
    echo "\nID: {$registro->id}\n";
    echo "Hora: {$registro->hora}\n";
    echo "Módulo: {$registro->modulo}\n";
    echo "Cantidad: {$registro->cantidad}\n";
    echo "Meta guardada: {$registro->meta}\n";
    echo "Tiempo ciclo: {$registro->tiempo_ciclo}\n";
    echo "Porción tiempo: {$registro->porcion_tiempo}\n";
    echo "Número operarios: {$registro->numero_operarios}\n";
    echo "Tiempo parada no programada: {$registro->tiempo_parada_no_programada}\n";
    echo "Tiempo para programada: {$registro->tiempo_para_programada}\n";
    
    // Calcular meta correcta
    $tiempo_disponible = (3600 * $registro->porcion_tiempo * $registro->numero_operarios)
                        - ($registro->tiempo_parada_no_programada ?? 0)
                        - ($registro->tiempo_para_programada ?? 0);
    $tiempo_disponible = max(0, $tiempo_disponible);
    $meta_calculada = $registro->tiempo_ciclo > 0 ? ($tiempo_disponible / $registro->tiempo_ciclo) * 0.9 : 0;
    
    echo "Meta calculada correctamente: " . round($meta_calculada, 2) . "\n";
    echo "---\n";
}
