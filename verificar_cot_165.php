<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Verificar cotización 165
$cot = DB::table('cotizaciones')->where('id', 165)->first();

if ($cot) {
    echo "Cotización 165:\n";
    echo "ID: {$cot->id}\n";
    echo "Número: {$cot->numero_cotizacion}\n";
    echo "Tipo: {$cot->tipo_cotizacion_id}\n";
    echo "Borrador: " . ($cot->es_borrador ? 'SI' : 'NO') . "\n";
    echo "Fecha envío: " . ($cot->fecha_envio ?? 'NULL') . "\n";
    echo "Fecha inicio: " . ($cot->fecha_inicio ?? 'NULL') . "\n";
    echo "Created: {$cot->created_at}\n";
    echo "Updated: {$cot->updated_at}\n";
} else {
    echo "Cotización 165 no existe\n";
}
