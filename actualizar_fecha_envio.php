<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Actualizar cotizaciones de logo sin fecha_envio
$updated = DB::table('cotizaciones')
    ->whereNull('fecha_envio')
    ->where('es_borrador', false)
    ->where('tipo_cotizacion_id', 2)
    ->update(['fecha_envio' => DB::raw('created_at')]);

echo "Cotizaciones actualizadas: $updated\n";

// Mostrar cotizaciones de logo
$cotizaciones = DB::table('cotizaciones')
    ->where('tipo_cotizacion_id', 2)
    ->where('es_borrador', false)
    ->select('id', 'numero_cotizacion', 'fecha_envio', 'created_at')
    ->get();

echo "\nCotizaciones de Logo:\n";
foreach ($cotizaciones as $cot) {
    echo "ID: {$cot->id} | Número: {$cot->numero_cotizacion} | Fecha envío: {$cot->fecha_envio} | Creada: {$cot->created_at}\n";
}
