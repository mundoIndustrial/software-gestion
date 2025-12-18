<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== VERIFICANDO Y CORRIGIENDO numero_secuencias ===\n\n";

// Ver todos los registros
$secuencias = DB::table('numero_secuencias')->get();

echo "Registros actuales:\n";
foreach ($secuencias as $sec) {
    $tipo = $sec->tipo;
    $siguiente = $sec->siguiente;
    $tipoValor = gettype($siguiente);
    
    echo "  - Tipo: {$tipo}\n";
    echo "    Siguiente: {$siguiente}\n";
    echo "    Tipo de dato: {$tipoValor}\n";
    
    // Verificar si tiene prefijo PEP-
    if (is_string($siguiente) && str_contains($siguiente, 'PEP-')) {
        echo "    ⚠️ TIENE PREFIJO PEP-\n";
        
        // Extraer solo el número
        $numeroLimpio = (int) str_replace('PEP-', '', $siguiente);
        echo "    Número limpio: {$numeroLimpio}\n";
        
        // Actualizar
        DB::table('numero_secuencias')
            ->where('tipo', $tipo)
            ->update(['siguiente' => $numeroLimpio]);
        
        echo "    ✅ CORREGIDO a: {$numeroLimpio}\n";
    } else {
        echo "    ✅ OK (sin prefijo)\n";
    }
    echo "\n";
}

echo "\n=== VERIFICACIÓN FINAL ===\n\n";

$secuenciasFinal = DB::table('numero_secuencias')->get();
foreach ($secuenciasFinal as $sec) {
    echo "  - {$sec->tipo}: {$sec->siguiente} (" . gettype($sec->siguiente) . ")\n";
}

echo "\n✅ Proceso completado\n";
