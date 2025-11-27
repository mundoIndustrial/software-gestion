<?php
require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== ANÁLISIS DE FILTRO DESCRIPCION_PRENDAS ===\n\n";

// 1. Contar todos los registros con "napole"
$totalNapoles = DB::table('prendas_pedido')
    ->whereRaw("LOWER(descripcion) LIKE '%napole%'")
    ->count();

echo "1️⃣  Total prendas_pedido con LIKE '%napole%': $totalNapoles\n";

// 2. Obtener los valores únicos del dropdown
$uniqueValues = DB::table('prendas_pedido')
    ->whereNotNull('descripcion')
    ->where('descripcion', '!=', '')
    ->distinct()
    ->pluck('descripcion')
    ->filter(function($value) {
        return $value !== null && $value !== '';
    })
    ->values()
    ->toArray();

$napolesDropdown = array_filter($uniqueValues, function($v) {
    return stripos($v, 'napole') !== false;
});

echo "2️⃣  Valores únicos en dropdown con 'napole': " . count($napolesDropdown) . "\n";

// 3. Simular el filtro fuzzy con palabras clave
echo "\n3️⃣  === SIMULANDO FILTRO FUZZY ===\n";

$matchedPedidoIds = [];

foreach ($napolesDropdown as $value) {
    // Normalizar igual que el backend
    $normalized = preg_replace('/[^\w\s]/u', '', $value);
    $normalized = preg_replace('/\s+/', ' ', trim($normalized));
    
    echo "\n   Valor: " . substr($value, 0, 60) . "...\n";
    echo "   Normalizado: " . substr($normalized, 0, 60) . "...\n";
    
    // Dividir en palabras
    $words = array_filter(explode(' ', $normalized));
    echo "   Palabras clave: " . implode(', ', $words) . "\n";
    
    // Buscar coincidencias
    $query = DB::table('prendas_pedido');
    foreach ($words as $word) {
        $query->where('descripcion', 'LIKE', '%' . $word . '%');
    }
    
    $matches = $query->distinct()->pluck('pedido_produccion_id')->toArray();
    echo "   Coincidencias: " . count($matches) . "\n";
    
    $matchedPedidoIds = array_merge($matchedPedidoIds, $matches);
}

$totalMatched = count(array_unique($matchedPedidoIds));
echo "\n4️⃣  Total de pedidos matcheados por filtro fuzzy: $totalMatched\n";

// 4. Comparación
echo "\n=== COMPARACIÓN ===\n";
echo "Con LIKE '%napole%' en prendas_pedido: $totalNapoles\n";
echo "Con filtro fuzzy (todas las palabras): $totalMatched\n";
echo "Diferencia: " . ($totalNapoles - $totalMatched) . "\n";

// 5. Ver qué se pierde
echo "\n5️⃣  === ANALIZANDO PÉRDIDAS ===\n";

// Obtener todos los registros con napole
$allNapoles = DB::table('prendas_pedido')
    ->whereRaw("LOWER(descripcion) LIKE '%napole%'")
    ->pluck('descripcion', 'pedido_produccion_id')
    ->toArray();

echo "Total único pedido_ids con napole: " . count(array_unique(array_values($allNapoles))) . "\n";

// Ver cuáles no están en el dropdown
$notInDropdown = [];
foreach ($allNapoles as $pedido_id => $desc) {
    $found = false;
    foreach ($napolesDropdown as $dropdownValue) {
        if ($desc === $dropdownValue) {
            $found = true;
            break;
        }
    }
    if (!$found) {
        $notInDropdown[] = $desc;
    }
}

if (!empty($notInDropdown)) {
    echo "\nValores CON 'napole' que NO están en dropdown:\n";
    foreach (array_unique($notInDropdown) as $val) {
        echo "  - " . substr($val, 0, 80) . "\n";
    }
} else {
    echo "\n✅ Todos los valores con 'napole' están en dropdown\n";
}

// 6. Analizar por qué no matchean
echo "\n6️⃣  === ANÁLISIS DE NO-COINCIDENCIAS ===\n";

$notMatched = [];
foreach ($napolesDropdown as $dropdownValue) {
    $normalized = preg_replace('/[^\w\s]/u', '', $dropdownValue);
    $normalized = preg_replace('/\s+/', ' ', trim($normalized));
    $words = array_filter(explode(' ', $normalized));
    
    $query = DB::table('prendas_pedido');
    foreach ($words as $word) {
        $query->where('descripcion', 'LIKE', '%' . $word . '%');
    }
    
    $matches = $query->distinct()->count();
    
    if ($matches == 0) {
        $notMatched[] = [
            'original' => $dropdownValue,
            'normalized' => $normalized,
            'words' => $words
        ];
    }
}

if (!empty($notMatched)) {
    echo "Valores que NO matchean con filtro fuzzy:\n";
    foreach ($notMatched as $item) {
        echo "\n  Original: " . substr($item['original'], 0, 80) . "\n";
        echo "  Normalizado: " . substr($item['normalized'], 0, 80) . "\n";
        echo "  Palabras: " . implode(', ', $item['words']) . "\n";
    }
} else {
    echo "✅ Todos los valores matchean\n";
}
