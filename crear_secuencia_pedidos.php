<?php
// Script para crear secuencia de pedidos produccion
use Illuminate\Support\Facades\DB;

echo "Verificando secuencia pedidos_produccion_universal...\n";

try {
    $seq = DB::table('numero_secuencias')
        ->where('tipo', 'pedidos_produccion_universal')
        ->first();
    
    if ($seq) {
        echo "✅ Secuencia YA EXISTE\n";
        echo "   Tipo: {$seq->tipo}\n";
        echo "   Siguiente: {$seq->siguiente}\n";
    } else {
        echo "⚠️  Secuencia NO EXISTE, creando...\n";
        
        // Obtener el máximo actual
        $maxActual = DB::table('pedidos_produccion')->max('numero_pedido');
        $siguienteNumero = ($maxActual ? $maxActual + 1 : 1);
        
        echo "   Max actual en BD: $maxActual\n";
        echo "   Siguiente a usar: $siguienteNumero\n";
        
        DB::table('numero_secuencias')->insert([
            'tipo' => 'pedidos_produccion_universal',
            'siguiente' => $siguienteNumero,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        echo "✅ Secuencia CREADA\n";
    }
    
    // Mostrar todas
    echo "\n📋 Todas las secuencias en numero_secuencias:\n";
    $todas = DB::table('numero_secuencias')->get();
    foreach ($todas as $s) {
        echo "   - {$s->tipo}: {$s->siguiente}\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
