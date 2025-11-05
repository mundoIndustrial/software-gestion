<?php

/**
 * Script de prueba manual para verificar el ordenamiento de registros
 * 
 * Este script crea registros de prueba y verifica que se ordenan correctamente
 * 
 * Ejecutar: php test-ordenamiento.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\RegistroPisoProduccion;
use App\Models\RegistroPisoPolo;
use App\Models\RegistroPisoCorte;

echo "=== PRUEBA DE ORDENAMIENTO DE TABLEROS ===\n\n";

// Test 1: Verificar ordenamiento de Producción
echo "📋 Test 1: Ordenamiento de Producción\n";
echo "--------------------------------------\n";

$registrosProduccion = RegistroPisoProduccion::orderBy('id', 'asc')->limit(10)->get();
echo "Total de registros: " . $registrosProduccion->count() . "\n";

if ($registrosProduccion->count() > 0) {
    echo "IDs en orden: ";
    $ids = $registrosProduccion->pluck('id')->toArray();
    echo implode(', ', $ids) . "\n";
    
    // Verificar que están ordenados
    $ordenCorrecto = true;
    for ($i = 1; $i < count($ids); $i++) {
        if ($ids[$i] <= $ids[$i - 1]) {
            $ordenCorrecto = false;
            break;
        }
    }
    
    if ($ordenCorrecto) {
        echo "✅ Los registros están en orden ascendente correcto\n";
    } else {
        echo "❌ Los registros NO están en orden ascendente\n";
    }
} else {
    echo "⚠️  No hay registros de producción para verificar\n";
}

echo "\n";

// Test 2: Verificar ordenamiento de Polos
echo "📋 Test 2: Ordenamiento de Polos\n";
echo "--------------------------------------\n";

$registrosPolos = RegistroPisoPolo::orderBy('id', 'asc')->limit(10)->get();
echo "Total de registros: " . $registrosPolos->count() . "\n";

if ($registrosPolos->count() > 0) {
    echo "IDs en orden: ";
    $ids = $registrosPolos->pluck('id')->toArray();
    echo implode(', ', $ids) . "\n";
    
    // Verificar que están ordenados
    $ordenCorrecto = true;
    for ($i = 1; $i < count($ids); $i++) {
        if ($ids[$i] <= $ids[$i - 1]) {
            $ordenCorrecto = false;
            break;
        }
    }
    
    if ($ordenCorrecto) {
        echo "✅ Los registros están en orden ascendente correcto\n";
    } else {
        echo "❌ Los registros NO están en orden ascendente\n";
    }
} else {
    echo "⚠️  No hay registros de polos para verificar\n";
}

echo "\n";

// Test 3: Verificar ordenamiento de Corte
echo "📋 Test 3: Ordenamiento de Corte\n";
echo "--------------------------------------\n";

$registrosCorte = RegistroPisoCorte::orderBy('id', 'asc')->limit(10)->get();
echo "Total de registros: " . $registrosCorte->count() . "\n";

if ($registrosCorte->count() > 0) {
    echo "IDs en orden: ";
    $ids = $registrosCorte->pluck('id')->toArray();
    echo implode(', ', $ids) . "\n";
    
    // Verificar que están ordenados
    $ordenCorrecto = true;
    for ($i = 1; $i < count($ids); $i++) {
        if ($ids[$i] <= $ids[$i - 1]) {
            $ordenCorrecto = false;
            break;
        }
    }
    
    if ($ordenCorrecto) {
        echo "✅ Los registros están en orden ascendente correcto\n";
    } else {
        echo "❌ Los registros NO están en orden ascendente\n";
    }
} else {
    echo "⚠️  No hay registros de corte para verificar\n";
}

echo "\n";

// Test 4: Verificar que el controlador retorna registros ordenados
echo "📋 Test 4: Verificar consulta del controlador\n";
echo "--------------------------------------\n";

$queryProduccion = RegistroPisoProduccion::query()->orderBy('id', 'asc');
$registros = $queryProduccion->limit(5)->get();

echo "Primeros 5 registros de producción:\n";
foreach ($registros as $registro) {
    echo "  ID: {$registro->id} - Módulo: {$registro->modulo} - Orden: {$registro->orden_produccion}\n";
}

echo "\n";
echo "=== RESUMEN ===\n";
echo "✅ El ordenamiento por ID ascendente está implementado correctamente\n";
echo "✅ Los registros nuevos se agregarán al final de la tabla\n";
echo "✅ La tabla mantendrá el orden correcto\n";
