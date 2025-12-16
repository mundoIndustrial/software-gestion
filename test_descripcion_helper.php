<?php
// Test: Verificar que DescripcionPrendaHelper genera el formato correcto

require_once __DIR__ . '/vendor/autoload.php';

use App\Helpers\DescripcionPrendaHelper;

echo "TEST: DescripcionPrendaHelper::generarDescripcion()\n";
echo "====================================================\n\n";

// Simular datos de una prenda como se enviarían desde el frontend
$datosPrenda = [
    'numero' => 1,
    'tipo' => 'CAMISA DRILL',
    'color' => 'NARANJA',
    'tela' => 'DRILL BORNEO',
    'ref' => 'REF-DB-001',
    'manga' => 'LARGA',
    'obs_manga' => '',
    'logo' => 'LOGO BORDADO EN ESPALDA',
    'bolsillos' => [
        'BOLSILLOS CON TAPA BOTON Y OJAL CON LOGOS BORDADOS DENTRO DEL BOLSILLO DERECHO "TRANSPORTE" BOLSILLO IZQUIERDO "ANI"'
    ],
    'broche' => '',
    'reflectivos' => [
        'REFLECTIVO GRIS 2" DE 25 CICLOS EN H EN LA PARTE DELANTERA Y TRASERA 2 VUELTAS EN CADA BRAZO Y UNA LINEA A LA ALTURA DEL OMBLIGO'
    ],
    'otros' => [],
    'tallas' => [
        'S' => 50,
        'M' => 50,
        'L' => 50,
        'XL' => 50,
        'XXL' => 50,
        'XXXL' => 50,
    ]
];

$descripcion = DescripcionPrendaHelper::generarDescripcion($datosPrenda);

echo "DESCRIPCIÓN GENERADA:\n";
echo "=====================\n\n";
echo $descripcion;
echo "\n\n";

echo "ANÁLISIS:\n";
echo "=========\n";
echo "✓ Línea 1 - Prenda y tipo: " . (strpos($descripcion, 'PRENDA 1: CAMISA DRILL') !== false ? '✅' : '❌') . "\n";
echo "✓ Línea 2 - Color, Tela, Manga: " . (strpos($descripcion, 'Color: NARANJA') !== false ? '✅' : '❌') . "\n";
echo "✓ Tiene Descripción: " . (strpos($descripcion, 'DESCRIPCIÓN:') !== false ? '✅' : '❌') . "\n";
echo "✓ Tiene Logo: " . (strpos($descripcion, 'Logo: LOGO BORDADO EN ESPALDA') !== false ? '✅' : '❌') . "\n";
echo "✓ Tiene Bolsillos: " . (strpos($descripcion, 'Bolsillos:') !== false ? '✅' : '❌') . "\n";
echo "✓ Tiene Reflectivo: " . (strpos($descripcion, 'Reflectivo:') !== false ? '✅' : '❌') . "\n";
echo "✓ Tiene Tallas: " . (strpos($descripcion, 'TALLAS:') !== false ? '✅' : '❌') . "\n";
echo "✓ Tallas correctas: " . (strpos($descripcion, 'S: 50') !== false ? '✅' : '❌') . "\n";
