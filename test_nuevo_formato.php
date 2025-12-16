<?php
/**
 * Test del nuevo formato de descripción
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use App\Helpers\DescripcionPrendaLegacyFormatter;

// Datos de prueba basados en 45452 Prenda 1
$prenda = [
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
    'tallas' => ['L' => 50, 'M' => 50, 'S' => 50, 'XL' => 50, 'XXL' => 50, 'XXXL' => 50]
];

$descripcion = DescripcionPrendaLegacyFormatter::generar($prenda);

echo "✅ DESCRIPCIÓN GENERADA:\n";
echo "========================\n\n";
echo $descripcion;
echo "\n\n";
echo "========================\n";
echo "Longitud: " . strlen($descripcion) . " caracteres\n";
