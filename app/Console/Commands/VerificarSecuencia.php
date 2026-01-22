<?php
// Script para verificar secuencia universal
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// echo "Verificando tabla numero_secuencias...\n";
Log::debug("Verificando tabla numero_secuencias");

try {
    $tabla = DB::table('numero_secuencias')->first();
    if (!$tabla) {
        // echo "  Tabla está vacía\n";
        Log::debug("Tabla numero_secuencias está vacía");
    }
    
    $todos = DB::table('numero_secuencias')->get();
    // echo " Contenido actual:\n";
    Log::debug("Contenido actual de numero_secuencias");
    foreach ($todos as $row) {
        // echo "   Tipo: {$row->tipo}, Siguiente: {$row->siguiente}\n";
        Log::debug("Secuencia encontrada", ['tipo' => $row->tipo, 'siguiente' => $row->siguiente]);
    }
    
    // Verificar que existe universal
    $universal = DB::table('numero_secuencias')
        ->where('tipo', 'cotizaciones_universal')
        ->first();
    
    if (!$universal) {
        // echo "\n  Secuencia universal NO EXISTE, creando...\n";
        Log::info("Secuencia universal NO EXISTE, creando...");
        DB::table('numero_secuencias')->insert([
            'tipo' => 'cotizaciones_universal',
            'siguiente' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        // echo " Secuencia universal CREADA\n";
        Log::info("Secuencia universal CREADA");
    } else {
        // echo " Secuencia universal ya existe (siguiente: {$universal->siguiente})\n";
        Log::debug("Secuencia universal ya existe", ['siguiente' => $universal->siguiente]);
    }
    
} catch (\Exception $e) {
    // echo " Error: " . $e->getMessage() . "\n";
    Log::error("Error en VerificarSecuencia: " . $e->getMessage());
}
