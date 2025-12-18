<?php
// Script para verificar secuencia universal
use Illuminate\Support\Facades\DB;

echo "Verificando tabla numero_secuencias...\n";

try {
    $tabla = DB::table('numero_secuencias')->first();
    if (!$tabla) {
        echo "⚠️  Tabla está vacía\n";
    }
    
    $todos = DB::table('numero_secuencias')->get();
    echo "✅ Contenido actual:\n";
    foreach ($todos as $row) {
        echo "   Tipo: {$row->tipo}, Siguiente: {$row->siguiente}\n";
    }
    
    // Verificar que existe universal
    $universal = DB::table('numero_secuencias')
        ->where('tipo', 'cotizaciones_universal')
        ->first();
    
    if (!$universal) {
        echo "\n⚠️  Secuencia universal NO EXISTE, creando...\n";
        DB::table('numero_secuencias')->insert([
            'tipo' => 'cotizaciones_universal',
            'siguiente' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        echo "✅ Secuencia universal CREADA\n";
    } else {
        echo "✅ Secuencia universal ya existe (siguiente: {$universal->siguiente})\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
