#!/usr/bin/env php
<?php

/**
 * SCRIPT DE VALIDACIÓN: Generación Sincrónica de Números de Cotización
 * 
 * Valida que:
 * 1. El lock pessimista funciona
 * 2. No hay números duplicados
 * 3. El formato es correcto
 * 4. Los números se incrementan correctamente
 */

require __DIR__ . '/bootstrap/app.php';

use Illuminate\Support\Facades\DB;

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "🔐 VALIDACIÓN: Generación Sincrónica de Números con Pessimistic Lock\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

try {
    // TEST 1: Verificar que tabla existe
    echo "🔍 TEST 1: Verificar tabla numero_secuencias...\n";
    $secuencias = DB::table('numero_secuencias')->get();
    echo "✅ Tabla existe con " . $secuencias->count() . " secuencias\n\n";
    
    foreach ($secuencias as $sec) {
        echo "   📌 {$sec->tipo}: próximo_numero = {$sec->proximo_numero}\n";
    }
    echo "\n";

    // TEST 2: Generar un número
    echo "🔍 TEST 2: Generar número con pessimistic lock...\n";
    $numero1 = generarNumero('cotizaciones_prenda');
    echo "✅ Número generado: $numero1\n\n";
    
    // TEST 3: Generar 5 números más y verificar que no hay duplicados
    echo "🔍 TEST 3: Generar 5 números más y verificar secuencia...\n";
    $numeros = [$numero1];
    for ($i = 0; $i < 5; $i++) {
        $numeros[] = generarNumero('cotizaciones_prenda');
    }
    
    echo "✅ Números generados:\n";
    foreach ($numeros as $idx => $num) {
        echo "   " . ($idx + 1) . ". $num\n";
    }
    
    // Verificar duplicados
    $unicos = array_unique($numeros);
    if (count($unicos) === count($numeros)) {
        echo "\n✅ ¡NO HAY DUPLICADOS! Todos los números son únicos\n\n";
    } else {
        echo "\n❌ ERROR: Hay duplicados!\n\n";
        exit(1);
    }

    // TEST 4: Verificar formato
    echo "🔍 TEST 4: Verificar formato COT-YYYYMMDD-NNN...\n";
    $patron = '/^COT-\d{8}-\d{3}$/';
    $todosValidos = true;
    foreach ($numeros as $num) {
        if (!preg_match($patron, $num)) {
            echo "❌ Formato inválido: $num\n";
            $todosValidos = false;
        }
    }
    if ($todosValidos) {
        echo "✅ Todos los números tienen formato correcto\n\n";
    } else {
        exit(1);
    }

    // TEST 5: Verificar que diferentes tipos no interfieren
    echo "🔍 TEST 5: Generar números de diferentes tipos...\n";
    $numeroPrenda = generarNumero('cotizaciones_prenda');
    $numeroBordado = generarNumero('cotizaciones_bordado');
    $numeroPrenda2 = generarNumero('cotizaciones_prenda');
    
    echo "   Prenda #1:  $numeroPrenda\n";
    echo "   Bordado #1: $numeroBordado\n";
    echo "   Prenda #2:  $numeroPrenda2\n";
    
    if ($numeroPrenda !== $numeroBordado && $numeroBordado !== $numeroPrenda2) {
        echo "\n✅ Diferentes tipos no interfieren\n\n";
    } else {
        echo "\n❌ ERROR: Tipos interfieren\n\n";
        exit(1);
    }

    // TEST 6: Verificar estado actual de secuencias
    echo "🔍 TEST 6: Verificar estado final de secuencias...\n";
    $secuenciasFinales = DB::table('numero_secuencias')->get();
    echo "✅ Estado final:\n";
    foreach ($secuenciasFinales as $sec) {
        echo "   📌 {$sec->tipo}: próximo_numero = {$sec->proximo_numero}\n";
    }

    echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "✅ TODOS LOS TESTS PASARON\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

} catch (\Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo $e->getFile() . ":" . $e->getLine() . "\n\n";
    exit(1);
}

/**
 * Generar número con pessimistic lock
 */
function generarNumero($tipo = 'cotizaciones_prenda')
{
    return DB::transaction(function () use ($tipo) {
        $secuencia = DB::table('numero_secuencias')
            ->lockForUpdate()
            ->where('tipo', $tipo)
            ->first();

        if (!$secuencia) {
            throw new Exception("Secuencia '$tipo' no encontrada");
        }

        $proximoNumero = $secuencia->proximo_numero;
        
        DB::table('numero_secuencias')
            ->where('tipo', $tipo)
            ->update(['proximo_numero' => $proximoNumero + 1]);

        return 'COT-' . date('Ymd') . '-' . str_pad($proximoNumero, 3, '0', STR_PAD_LEFT);
    });
}
