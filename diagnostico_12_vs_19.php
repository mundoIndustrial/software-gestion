<?php
require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== DIAGNÓSTICO: ¿POR QUÉ SOLO 12 EN LUGAR DE 19? ===\n\n";

// Simular los 15 valores seleccionados
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

// Probar la lógica actual (con OR entre valores)
echo "1️⃣  === PROBANDO LÓGICA ACTUAL (OR ENTRE VALORES) ===\n\n";

$query = DB::table('prendas_pedido')
    ->whereIn('id', function($subquery) use ($values) {
        $subquery->select('pedido_produccion_id')
            ->from('prendas_pedido')
            ->where(function($q) use ($values) {
                foreach ($values as $idx => $value) {
                    // Extraer palabras clave (3+ caracteres o con letras/acentos)
                    $normalized = preg_replace('/[\r\n]+/', ' ', $value);
                    $normalized = preg_replace('/\s+/', ' ', trim($normalized));
                    
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
                    
                    echo "[$idx] Palabras clave: " . implode(', ', array_slice($keywordWords, 0, 5)) . "...\n";
                    
                    if (!empty($keywordWords)) {
                        // PROBLEMA: El orWhere aquí crea la lógica equivocada
                        $q->orWhere(function($where) use ($keywordWords) {
                            foreach ($keywordWords as $keyword) {
                                $where->whereRaw(
                                    "LOWER(REPLACE(REPLACE(REPLACE(REPLACE(descripcion, '\r', ''), '\n', ' '), ',', ' '), '\"', '')) LIKE ?",
                                    ['%' . $keyword . '%']
                                );
                            }
                        });
                    }
                }
            })
            ->distinct();
    })
    ->get();

echo "\n✓ Resultados con lógica actual: " . count($query) . " registros\n";

// Probar lógica mejorada (agrupar todos los valores, luego OR)
echo "\n2️⃣  === PROBANDO LÓGICA MEJORADA (AGRUPAR VALORES) ===\n\n";

// Primero, extraer TODAS las palabras clave de TODOS los valores
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

$uniqueKeywords = array_keys($allKeywords);
echo "Palabras clave únicas de todos los valores: " . count($uniqueKeywords) . "\n";
echo "Palabras: " . implode(', ', array_slice($uniqueKeywords, 0, 15)) . "...\n\n";

// Buscar registros que contengan AL MENOS UNA de estas palabras clave
$query2 = DB::table('prendas_pedido')
    ->where(function($q) use ($uniqueKeywords) {
        foreach ($uniqueKeywords as $keyword) {
            $q->orWhereRaw(
                "LOWER(REPLACE(REPLACE(REPLACE(REPLACE(descripcion, '\r', ''), '\n', ' '), ',', ' '), '\"', '')) LIKE ?",
                ['%' . $keyword . '%']
            );
        }
    })
    ->whereIn('pedido_produccion_id', function($sub) use ($values) {
        $sub->select('pedido_produccion_id')
            ->from('prendas_pedido')
            ->whereIn('descripcion', $values);
    })
    ->distinct()
    ->get();

echo "✓ Resultados con lógica mejorada: " . count($query2) . " registros\n";

// Probar si el problema es la lógica de OR
echo "\n3️⃣  === BUSCANDO DIRECTAMENTE POR VALORES EXACTOS ===\n\n";

$query3 = DB::table('prendas_pedido')
    ->whereIn('descripcion', $values)
    ->get();

echo "✓ Búsqueda directa por valores: " . count($query3) . " registros\n";
echo "Pedidos encontrados:\n";
foreach ($query3 as $reg) {
    echo "  - Pedido " . $reg->pedido_produccion_id . "\n";
}

// Verificar qué falta
echo "\n4️⃣  === VERIFICANDO QUÉ FALTA ===\n\n";

$allNapolesInDB = DB::table('prendas_pedido')
    ->whereRaw("LOWER(descripcion) LIKE '%napole%'")
    ->select('pedido_produccion_id', 'descripcion')
    ->distinct('pedido_produccion_id')
    ->get();

$pedidosDirectos = array_unique(array_map(function($r) { return $r->pedido_produccion_id; }, $query3->toArray()));
$pedidosTotales = array_unique(array_map(function($r) { return $r->pedido_produccion_id; }, $allNapolesInDB->toArray()));

echo "Pedidos con napole en BD: " . implode(', ', sort($pedidosTotales) ?: $pedidosTotales) . "\n";
echo "Pedidos encontrados: " . implode(', ', sort($pedidosDirectos) ?: $pedidosDirectos) . "\n";

$faltan = array_diff($pedidosTotales, $pedidosDirectos);
if (!empty($faltan)) {
    echo "\n❌ FALTAN estos pedidos: " . implode(', ', $faltan) . "\n";
}
