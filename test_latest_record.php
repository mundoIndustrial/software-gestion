<?php

require 'vendor/autoload.php';

// Set up Laravel app
$app = require_once 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Http\Kernel');
$response = $kernel->handle($request = \Illuminate\Http\Request::capture());

// Now we can use models
use App\Models\RegistroPisoCorte;

// Get the latest record
$latestRecord = RegistroPisoCorte::orderBy('id', 'desc')->first();

if ($latestRecord) {
    echo "=== ÚLTIMO REGISTRO ===\n";
    echo "ID: " . $latestRecord->id . "\n";
    echo "Fecha: " . $latestRecord->fecha . "\n";
    echo "Orden: " . $latestRecord->orden_produccion . "\n";
    echo "Cantidad: " . $latestRecord->cantidad . "\n";
    echo "Tiempo Ciclo: " . $latestRecord->tiempo_ciclo . "\n";
    echo "Tiempo Disponible: " . $latestRecord->tiempo_disponible . "\n";
    echo "Meta: " . $latestRecord->meta . "\n";
    echo "Eficiencia: " . $latestRecord->eficiencia . "\n";
    echo "Created At: " . $latestRecord->created_at . "\n";
    echo "\n";
    
    // Get the last 5 records to compare
    echo "=== ÚLTIMOS 5 REGISTROS ===\n";
    $records = RegistroPisoCorte::orderBy('id', 'desc')->limit(5)->get();
    foreach ($records as $record) {
        echo "ID: {$record->id} | TD: {$record->tiempo_disponible} | Meta: {$record->meta} | Eff: {$record->eficiencia}\n";
    }
} else {
    echo "No hay registros\n";
}
?>
