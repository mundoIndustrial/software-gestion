<?php
require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== SIMULANDO LÓGICA DEL FILTRO DESCRIPCION_PRENDAS ===\n\n";

// Los 15 valores seleccionados por el usuario
$values = [
    '2 JEANS NAPOLES CABALLERO TALLA 34, PARA PEGAR REFLECTIVO GRIS 2" 50 CICLOS UNA VUELTA ABAJO DE RODILLAS',
    '2 JEANS, 1 JEAN NAPOLES

PARA PEGAR REFLECTIVO GRIS DE 2" 50 CICLOS, UNA VUELTA DEBAJO DE RODILLAS',
    '9 JEANS NAPOLES PARA PEGAR REFLECTIVO GRIS DE 2" 50 CICLOS, UNA VUELTA DEBAJO DE RODILLAS.',
    'JEAN DE CABALLERO NAPOLES PARA PEGAR REFLECTIVO DE REATA NARANJA DE 2" 50 CICLOS, UNA VUELTA EN RODILLAS',
    'JEAN NAPOLES DE CABALLERO CON BOLSILLOS LATERALES Y TRASEROS CLASICOS EL TONO DEBE SER MAS CLARO TIPO AZULADO 

TALLA 38:1

PASAR FOTO DE COMO DEBE SER EL TONO

1.70 ANCHO',
    'JEAN NAPOLES DE CABALLERO, CON BOLSILLOS LATERALES Y TRASEROS CLASICOS, EL TONO DEBE SER AZUL CLARO, TIPO AZULADO

TALLA 38=1
PASA FOTO DE COMO DEBE SER EL TONO
1.70 ANCHO',
    'JEAN NAPOLES DE CABALLERO, CON BOLSILLOS LATERALES Y TRASEROS CLÁSICOS

MODELO BODEGA',
    'JEAN NAPOLES MODA DE CABALLERO BOTÓN METÁLICO
MODELO BODEGA

URGENTE',
    'JEANS NAPOLES DAMA 4CMS MAS ANCHO DE BOTA QUE SEA RECTA ',
    'JEANS NAPOLES DE CABALLERO TALLA 40 PARA PEGAR REFLECTIVO GRIS 2" 50 CICLOS UNA VUELTA MAS ABAJO DE RODILLAS',
    'JEANS NAPOLES PARA COLOCAR UNA LINEA VERTICAL DE REFLECTIVO AZUL MARINO DE CUADROS EN COSTADO IZQUIERDO',
    'JEANS NAPOLES, B.M CABALLERO PARA PEGAR REFLECTIVO GRIS 2"50 CICLOS UNA VUELTA MAS ABAJO DE RODILLAS, FOTO',
    'JEANS NAPOLES, DAMA METALIZO

2 TALLA 10 Y 12 2CM MAS LARGO DE BOTA
2 TALLA 10 5CM MAS LARGO DE BOTA',
    'JEANS NAPOLES, DAMA, BOTON Y CIERRE METALICO 5CM MAS LARGO DE BOTA

MODELO BODEGA',
    'MODA NAPOLES B/M BOTA ANCHA'
];

echo "Valores seleccionados: " . count($values) . "\n\n";

// Construir la condición igual como en el backend
echo "🔍 === SIMULANDO QUERY DEL BACKEND ===\n\n";

$allPedidosEncontrados = [];
$allDescripcionesEncontradas = [];

foreach ($values as $idx => $value) {
    echo "[" . ($idx + 1) . "] Procesando valor...\n";
    
    // Normalizar valor
    $normalized = preg_replace('/[\r\n]+/', ' ', $value);
    $normalized = preg_replace('/\s+/', ' ', trim($normalized));
    
    echo "    Normalizado: " . substr($normalized, 0, 80) . "...\n";
    
    // Dividir en palabras
    $allWords = explode(' ', $normalized);
    
    // Extraer palabras clave (3+ caracteres o con letras)
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
    
    echo "    Palabras clave (" . count($keywordWords) . "): " . implode(', ', array_slice($keywordWords, 0, 6)) . (count($keywordWords) > 6 ? "..." : "") . "\n";
    
    // Buscar en BD
    if (!empty($keywordWords)) {
        // Simular la query: WHERE (keyword1 LIKE ... OR keyword2 LIKE ... OR ...)
        $query = DB::table('prendas_pedido')
            ->where(function($where) use ($keywordWords) {
                $firstWord = true;
                foreach ($keywordWords as $keyword) {
                    if ($firstWord) {
                        $where->whereRaw(
                            "LOWER(REPLACE(REPLACE(REPLACE(REPLACE(descripcion, '\r', ''), '\n', ' '), ',', ' '), '\"', '')) LIKE ?",
                            ['%' . $keyword . '%']
                        );
                        $firstWord = false;
                    } else {
                        $where->orWhereRaw(
                            "LOWER(REPLACE(REPLACE(REPLACE(REPLACE(descripcion, '\r', ''), '\n', ' '), ',', ' '), '\"', '')) LIKE ?",
                            ['%' . $keyword . '%']
                        );
                    }
                }
            })
            ->select('id', 'pedido_produccion_id', 'descripcion')
            ->distinct()
            ->get();
        
        echo "    ✓ Encontrados: " . count($query) . " registros\n";
        
        foreach ($query as $reg) {
            $allPedidosEncontrados[] = $reg->pedido_produccion_id;
            $allDescripcionesEncontradas[$reg->id] = $reg->pedido_produccion_id;
        }
    }
}

echo "\n=== RESULTADOS ===\n";
echo "Total de registros encontrados: " . count($allDescripcionesEncontradas) . "\n";
echo "Total de pedidos únicos: " . count(array_unique($allPedidosEncontrados)) . "\n";

$pedidosUnicos = array_unique($allPedidosEncontrados);
sort($pedidosUnicos);
echo "\nPedidos encontrados: " . implode(', ', $pedidosUnicos) . "\n";

// Comparar con los reales
echo "\n🔎 === COMPARANDO CON REALIDAD ===\n\n";

$realNapoles = DB::table('prendas_pedido')
    ->whereRaw("LOWER(descripcion) LIKE '%napole%'")
    ->select('pedido_produccion_id')
    ->distinct()
    ->pluck('pedido_produccion_id')
    ->toArray();

sort($realNapoles);
echo "Pedidos REALES con napole: " . implode(', ', $realNapoles) . "\n";
echo "Total real: " . count($realNapoles) . "\n";

$pedidosSimulados = array_unique($allPedidosEncontrados);
sort($pedidosSimulados);
echo "\nPedidos encontrados por simulación: " . implode(', ', $pedidosSimulados) . "\n";
echo "Total simulado: " . count($pedidosSimulados) . "\n";

$faltan = array_diff($realNapoles, $pedidosSimulados);
$sobran = array_diff($pedidosSimulados, $realNapoles);

if (!empty($faltan)) {
    echo "\n❌ FALTAN: " . implode(', ', $faltan) . "\n";
} else {
    echo "\n✅ No faltan\n";
}

if (!empty($sobran)) {
    echo "⚠️  SOBRAN: " . implode(', ', $sobran) . "\n";
} else {
    echo "✅ No sobran\n";
}

if (empty($faltan) && empty($sobran)) {
    echo "\n🎉 ¡PERFECTO! La lógica devuelve exactamente " . count($realNapoles) . " pedidos\n";
} else {
    echo "\n⚠️  Hay diferencias. Total esperado: " . count($realNapoles) . ", Total obtenido: " . count($pedidosSimulados) . "\n";
}

// Ver registros de pedido 101 (que tiene 2 descripciones)
echo "\n=== VERIFICACIÓN: PEDIDO 101 ===\n";
$ped101 = DB::table('prendas_pedido')
    ->where('pedido_produccion_id', 101)
    ->select('descripcion')
    ->get();

echo "Registros en pedido 101: " . count($ped101) . "\n";
foreach ($ped101 as $reg) {
    echo "  - " . substr($reg->descripcion, 0, 80) . "...\n";
}

// Ver si están siendo encontrados
$encontrados101 = DB::table('prendas_pedido')
    ->where('pedido_produccion_id', 101)
    ->whereRaw("LOWER(REPLACE(REPLACE(REPLACE(REPLACE(descripcion, '\r', ''), '\n', ' '), ',', ' '), '\"', '')) LIKE '%napoles%'")
    ->count();

echo "¿Encontrados en filtro?: " . ($encontrados101 > 0 ? "SI (" . $encontrados101 . " registros)" : "NO") . "\n";
