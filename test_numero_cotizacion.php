<?php

// Script para testear el flujo de numero_cotizacion

require 'bootstrap/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

try {
    \Illuminate\Support\Facades\DB::connection()->getPdo();
    
    echo "📊 Verificando números de cotización existentes:\n";
    
    $cotizaciones = \App\Models\Cotizacion::whereNotNull('numero_cotizacion')
        ->orderBy('numero_cotizacion', 'desc')
        ->select('id', 'numero_cotizacion', 'estado', 'es_borrador')
        ->limit(5)
        ->get();
    
    if ($cotizaciones->isEmpty()) {
        echo "  ❌ No hay cotizaciones enviadas\n";
    } else {
        foreach ($cotizaciones as $cot) {
            echo "  - ID: {$cot->id}, Número: {$cot->numero_cotizacion}, Estado: {$cot->estado}, EsBorrador: " . ($cot->es_borrador ? 'sí' : 'no') . "\n";
        }
    }
    
    echo "\n🔢 Último número de cotización: " . ($cotizaciones->first()?->numero_cotizacion ?? 'ninguno') . "\n";
    
    echo "\n✅ Conexión OK\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
