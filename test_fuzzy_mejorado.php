<?php
require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== ANÁLISIS CON NUEVA LÓGICA FUZZY (AL MENOS UNA PALABRA) ===\n\n";

// 1. Contar TODOS los registros con "napole" en prendas_pedido
$totalNapolesRegistros = DB::table('prendas_pedido')
    ->whereRaw("LOWER(descripcion) LIKE '%napole%'")
    ->count();

echo "1️⃣  REGISTROS CON 'napole' en prendas_pedido: $totalNapolesRegistros\n";

// 2. Contar PEDIDOS ÚNICOS que contienen "napole"
$totalNapolesPedidos = DB::table('prendas_pedido')
    ->whereRaw("LOWER(descripcion) LIKE '%napole%'")
    ->distinct()
    ->count('pedido_produccion_id');

echo "2️⃣  PEDIDOS ÚNICOS con 'napole': $totalNapolesPedidos\n";

// 3. Obtener los valores únicos del dropdown
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

echo "3️⃣  Valores ÚNICOS en dropdown con 'napole': " . count($napolesDropdown) . "\n";

// 4. Simular el filtro fuzzy con palabras clave (NUEVA LÓGICA: AL MENOS UNA)
echo "\n4️⃣  === SIMULANDO FILTRO FUZZY MEJORADO (AL MENOS UNA PALABRA) ===\n";

$matchedPedidoIds = [];
$matchedCount = 0;

foreach ($napolesDropdown as $idx => $value) {
    // Nueva lógica: extraer palabras clave sin remover toda la puntuación
    $normalized = preg_replace('/[\r\n]+/', ' ', $value);
    $normalized = preg_replace('/\s+/', ' ', trim($normalized));
    
    // Dividir en palabras
    $allWords = explode(' ', $normalized);
    
    // Extraer palabras clave (3+ caracteres o con letras)
    $keywordWords = [];
    foreach ($allWords as $w) {
        $w = trim($w);
        // Si tiene 3+ caracteres o contiene letras acentuadas, es palabra clave
        if (strlen($w) >= 3 || preg_match('/[a-záéíóúñü]/i', $w)) {
            // Remover solo puntuación especial que cause problemas
            $cleaned = preg_replace('/[^\w\s-]/u', '', $w);
            if (!empty($cleaned)) {
                $keywordWords[] = strtolower($cleaned);
            }
        }
    }
    
    // Filtrar palabras duplicadas y vacías
    $keywordWords = array_unique(array_filter($keywordWords));
    
    echo "\n   [" . ($idx + 1) . "] Original: " . substr($value, 0, 70) . "...\n";
    echo "       Palabras clave: " . implode(', ', array_slice($keywordWords, 0, 8)) . (count($keywordWords) > 8 ? "..." : "") . "\n";
    
    // Buscar coincidencias con AL MENOS UNA palabra
    if (!empty($keywordWords)) {
        $query = DB::table('prendas_pedido');
        $matches = [];
        
        // Usar orWhere para AL MENOS UNA palabra
        $query->where(function($q) use ($keywordWords) {
            foreach ($keywordWords as $keyword) {
                $q->orWhereRaw(
                    "LOWER(REPLACE(REPLACE(REPLACE(REPLACE(descripcion, '\r', ''), '\n', ' '), ',', ' '), '\"', '')) LIKE ?",
                    ['%' . $keyword . '%']
                );
            }
        });
        
        $matches = $query->distinct()->pluck('pedido_produccion_id')->toArray();
        echo "       ✓ Coincidencias: " . count($matches) . " pedidos\n";
        
        $matchedPedidoIds = array_merge($matchedPedidoIds, $matches);
        $matchedCount += count($matches);
    }
}

$totalMatched = count(array_unique($matchedPedidoIds));
echo "\n   TOTAL PEDIDOS ÚNICOS MATCHEADOS (AL MENOS UNA PALABRA): $totalMatched\n";

// 5. Comparación
echo "\n=== COMPARACIÓN FINAL ===\n";
echo "Registros con 'napole' en BD: $totalNapolesRegistros\n";
echo "Pedidos únicos con 'napole': $totalNapolesPedidos\n";
echo "Valores únicos en dropdown: " . count($napolesDropdown) . "\n";
echo "Pedidos devueltos por filtro MEJORADO: $totalMatched\n";

if ($totalMatched >= $totalNapolesPedidos) {
    echo "\n✅ ¡ÉXITO! Ahora devuelve todos los pedidos\n";
} else {
    echo "\n⚠️  DIFERENCIA: " . ($totalNapolesPedidos - $totalMatched) . " pedidos NO se devuelven\n";
}

// 6. Analizar qué se pierde
echo "\n5️⃣  === VALORES QUE NO MATCHEAN ===\n";

$notMatched = [];
foreach ($napolesDropdown as $dropdownValue) {
    $normalized = preg_replace('/[\r\n]+/', ' ', $dropdownValue);
    $normalized = preg_replace('/\s+/', ' ', trim($normalized));
    
    $allWords = explode(' ', $normalized);
    $keywordWords = [];
    foreach ($allWords as $w) {
        $w = trim($w);
        if (strlen($w) >= 3 || preg_match('/[a-záéíóúñü]/i', $w)) {
            $cleaned = preg_replace('/[^\w\s-]/u', '', $w);
            if (!empty($cleaned)) {
                $keywordWords[] = strtolower($cleaned);
            }
        }
    }
    $keywordWords = array_unique(array_filter($keywordWords));
    
    if (!empty($keywordWords)) {
        $query = DB::table('prendas_pedido');
        $query->where(function($q) use ($keywordWords) {
            foreach ($keywordWords as $keyword) {
                $q->orWhereRaw(
                    "LOWER(REPLACE(REPLACE(REPLACE(REPLACE(descripcion, '\r', ''), '\n', ' '), ',', ' '), '\"', '')) LIKE ?",
                    ['%' . $keyword . '%']
                );
            }
        });
        
        $matches = $query->distinct()->count();
        
        if ($matches == 0) {
            $notMatched[] = [
                'original' => $dropdownValue,
                'normalized' => $normalized,
                'words' => $keywordWords
            ];
        }
    }
}

if (!empty($notMatched)) {
    echo "VALORES QUE NO MATCHEAN (" . count($notMatched) . "):\n";
    foreach ($notMatched as $item) {
        echo "\n  ❌ Original: " . substr($item['original'], 0, 80) . "\n";
        echo "     Palabras clave: " . implode(', ', $item['words']) . "\n";
    }
} else {
    echo "✅ Todos los valores matchean correctamente\n";
}

// 7. Ver todos los registros con napole en la BD
echo "\n6️⃣  === TODOS LOS REGISTROS CON 'napole' EN LA BD ===\n";

$allNapoles = DB::table('prendas_pedido')
    ->whereRaw("LOWER(descripcion) LIKE '%napole%'")
    ->select('id', 'pedido_produccion_id', 'descripcion')
    ->orderBy('pedido_produccion_id')
    ->get();

echo "Total: " . count($allNapoles) . " registros\n\n";
foreach ($allNapoles as $idx => $reg) {
    echo "[" . ($idx + 1) . "] Pedido: " . $reg->pedido_produccion_id . "\n";
    echo "    Descripción: " . substr($reg->descripcion, 0, 100) . (strlen($reg->descripcion) > 100 ? "..." : "") . "\n\n";
}
