<?php
require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== PRUEBA NUEVA LÓGICA (PALABRAS CLAVE GLOBALES) ===\n\n";

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

// Crear lista de palabras clave de TODOS los valores
$allKeywords = [];
foreach ($values as $value) {
    $normalized = preg_replace('/[\r\n]+/', ' ', $value);
    $normalized = preg_replace('/\s+/', ' ', trim($normalized));
    
    $allWords = explode(' ', $normalized);
    foreach ($allWords as $w) {
        $w = trim($w);
        if (strlen($w) >= 3 || preg_match('/[a-záéíóúñü]/i', $w)) {
            $cleaned = preg_replace('/[^\w\s-]/u', '', $w);
            if (!empty($cleaned)) {
                $allKeywords[strtolower($cleaned)] = true;
            }
        }
    }
}

$keywords = array_keys($allKeywords);
echo "Total de palabras clave únicas: " . count($keywords) . "\n";
echo "Palabras: " . implode(', ', array_slice($keywords, 0, 20)) . "...\n\n";

// Buscar registros que contengan AL MENOS UNA palabra clave
echo "🔍 === BUSCANDO REGISTROS CON AL MENOS UNA PALABRA CLAVE ===\n\n";

$query = DB::table('prendas_pedido');
$query->where(function($q) use ($keywords) {
    $first = true;
    foreach ($keywords as $keyword) {
        if ($first) {
            $q->whereRaw(
                "LOWER(REPLACE(REPLACE(REPLACE(REPLACE(descripcion, '\r', ''), '\n', ' '), ',', ' '), '\"', '')) LIKE ?",
                ['%' . $keyword . '%']
            );
            $first = false;
        } else {
            $q->orWhereRaw(
                "LOWER(REPLACE(REPLACE(REPLACE(REPLACE(descripcion, '\r', ''), '\n', ' '), ',', ' '), '\"', '')) LIKE ?",
                ['%' . $keyword . '%']
            );
        }
    }
});

$pedidosEncontrados = $query->distinct()->pluck('pedido_produccion_id')->toArray();

echo "Total de pedidos encontrados: " . count(array_unique($pedidosEncontrados)) . "\n";

$pedidosUnicos = array_unique($pedidosEncontrados);
sort($pedidosUnicos);
echo "Pedidos: " . implode(', ', $pedidosUnicos) . "\n";

// Comparar con los reales
echo "\n=== COMPARACIÓN CON REALIDAD ===\n\n";

$realNapoles = DB::table('prendas_pedido')
    ->whereRaw("LOWER(descripcion) LIKE '%napole%'")
    ->select('pedido_produccion_id')
    ->distinct()
    ->pluck('pedido_produccion_id')
    ->toArray();

sort($realNapoles);
echo "Pedidos REALES con napole: " . implode(', ', $realNapoles) . "\n";
echo "Total real: " . count($realNapoles) . "\n";

$faltan = array_diff($realNapoles, $pedidosUnicos);
$sobran = array_diff($pedidosUnicos, $realNapoles);

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
    echo "\n⚠️  Hay diferencias. Total esperado: " . count($realNapoles) . ", Total obtenido: " . count($pedidosUnicos) . "\n";
}
