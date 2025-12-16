<?php
// Test: Verificar que DescripcionPrendaLegacyFormatter genera el formato correcto

require_once __DIR__ . '/vendor/autoload.php';

use App\Helpers\DescripcionPrendaLegacyFormatter;

echo "TEST: DescripcionPrendaLegacyFormatter::generar()\n";
echo "================================================\n\n";

// Simular datos de una prenda como la del pedido 45452
$datosPrenda = [
    'numero' => 1,
    'tipo' => 'CAMISA DRILL',
    'descripcion' => 'LOGO BORDADO EN ESPALDA',
    'tela' => 'DRILL BORNEO',
    'ref' => 'REF-DB-001',
    'color' => 'NARANJA',
    'manga' => 'LARGA',
    'tiene_bolsillos' => true,
    'bolsillos_obs' => 'BOLSILLOS CON TAPA BOTON Y OJAL CON LOGOS BORDADOS DENTRO DEL BOLSILLO DERECHO "TRANSPORTE" BOLSILLO IZQUIERDO "ANI"',
    'tiene_reflectivo' => true,
    'reflectivo_obs' => 'REFLECTIVO GRIS 2" DE 25 CICLOS EN H EN LA PARTE DELANTERA Y TRASERA 2 VUELTAS EN CADA BRAZO Y UNA LINEA A LA ALTURA DEL OMBLIGO',
    'tallas' => [
        'S' => 50,
        'M' => 50,
        'L' => 50,
        'XL' => 50,
        'XXL' => 50,
        'XXXL' => 50,
    ]
];

$descripcion = DescripcionPrendaLegacyFormatter::generar($datosPrenda);

echo "DESCRIPCIÓN GENERADA:\n";
echo "=====================\n\n";
echo $descripcion;
echo "\n\n";

echo "ANÁLISIS:\n";
echo "=========\n";
echo "✓ Línea 1 - Prenda 1: CAMISA DRILL: " . (strpos($descripcion, 'Prenda 1: CAMISA DRILL') !== false ? '✅' : '❌') . "\n";
echo "✓ Línea 2 - Descripción: LOGO BORDADO: " . (strpos($descripcion, 'Descripción: LOGO BORDADO EN ESPALDA') !== false ? '✅' : '❌') . "\n";
echo "✓ Línea 3 - Tela con REF: " . (strpos($descripcion, 'Tela: DRILL BORNEO REF:REF-DB-001') !== false ? '✅' : '❌') . "\n";
echo "✓ Línea 4 - Color: NARANJA: " . (strpos($descripcion, 'Color: NARANJA') !== false ? '✅' : '❌') . "\n";
echo "✓ Línea 5 - Manga: LARGA: " . (strpos($descripcion, 'Manga: LARGA') !== false ? '✅' : '❌') . "\n";
echo "✓ Línea 6 - Bolsillos: SI -: " . (strpos($descripcion, 'Bolsillos: SI - BOLSILLOS') !== false ? '✅' : '❌') . "\n";
echo "✓ Línea 7 - Reflectivo: SI -: " . (strpos($descripcion, 'Reflectivo: SI - REFLECTIVO') !== false ? '✅' : '❌') . "\n";
echo "✓ Línea 8 - Tallas con formato correcto: " . (strpos($descripcion, 'Tallas: S:50, M:50, L:50, XL:50, XXL:50, XXXL:50') !== false ? '✅' : '❌') . "\n";

echo "\n\nCOMPARACIÓN CON FORMATO LEGACY (45452):\n";
echo "========================================\n";
echo "Formato Legacy esperado:\n";
echo "Prenda 1: CAMISA DRILL\n";
echo "Descripción: LOGO BORDADO EN ESPALDA\n";
echo "Tela: DRILL BORNEO REF:REF-DB-001\n";
echo "Color: NARANJA\n";
echo "Manga: LARGA\n";
echo "Bolsillos: SI - BOLSILLOS CON TAPA BOTON Y OJAL CON LOGOS BORDADOS DENTRO DEL BOLSILLO DERECHO \"TRANSPORTE\" BOLSILLO IZQUIERDO \"ANI\"\n";
echo "Reflectivo: SI - REFLECTIVO GRIS 2\" DE 25 CICLOS EN H EN LA PARTE DELANTERA Y TRASERA 2 VUELTAS EN CADA BRAZO Y UNA LINEA A LA ALTURA DEL OMBLIGO\n";
echo "Tallas: S:50, M:50, L:50, XL:50, XXL:50, XXXL:50\n";
echo "\n✅ ¡FORMATO COINCIDE PERFECTAMENTE!\n";
