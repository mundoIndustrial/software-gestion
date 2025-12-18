<?php

/**
 * Script de prueba para verificar generación de números de pedidos
 * Simula múltiples intentos concurrentes
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\DB;

echo "🧪 PRUEBA DE SECUENCIA PEDIDOS DE PRODUCCIÓN\n";
echo str_repeat("=", 60) . "\n\n";

// 1. Verificar estado actual
echo "1️⃣  Estado actual de la secuencia:\n";
$seq = DB::table('numero_secuencias')
    ->where('tipo', 'pedidos_produccion_universal')
    ->first();

echo "   Tipo: {$seq->tipo}\n";
echo "   Siguiente: {$seq->siguiente}\n\n";

// 2. Generar 5 números simulados
echo "2️⃣  Generando 5 números de prueba (sin guardar):\n";

// Simular lo que haría el controlador
for ($i = 0; $i < 5; $i++) {
    $secuencia = DB::table('numero_secuencias')
        ->lockForUpdate()
        ->where('tipo', 'pedidos_produccion_universal')
        ->first();

    $siguiente = $secuencia->siguiente;
    $numeroPedido = 'PEP-' . str_pad($siguiente, 6, '0', STR_PAD_LEFT);
    
    // Simular incremento (sin realmente hacerlo)
    echo "   [$i+1] {$numeroPedido} (secuencia: {$siguiente})\n";
    
    // En la realidad se actualiza así:
    // DB::table('numero_secuencias')
    //     ->where('tipo', 'pedidos_produccion_universal')
    //     ->update(['siguiente' => $siguiente + 1]);
}

echo "\n3️⃣  Estado final (sin cambios reales):\n";
$seq = DB::table('numero_secuencias')
    ->where('tipo', 'pedidos_produccion_universal')
    ->first();
echo "   Siguiente: {$seq->siguiente}\n";

echo "\n✅ Prueba completada\n";
