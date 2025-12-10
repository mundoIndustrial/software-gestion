<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "📊 TABLAS CREADAS EN 2025_12_10\n";
echo str_repeat("=", 60) . "\n\n";

$tablas = [
    'prenda_telas' => 'Relación entre prendas y telas',
    'genero_prendas' => 'Géneros de prendas',
    'tipo_prendas' => 'Tipos de prendas',
];

foreach ($tablas as $tabla => $descripcion) {
    if (Schema::hasTable($tabla)) {
        $columns = Schema::getColumns($tabla);
        echo "✅ $tabla\n";
        echo "   Descripción: $descripcion\n";
        echo "   Columnas: " . count($columns) . "\n";
        foreach ($columns as $col) {
            echo "      - {$col['name']} ({$col['type']})\n";
        }
        echo "\n";
    } else {
        echo "❌ $tabla - NO EXISTE\n\n";
    }
}

echo str_repeat("=", 60) . "\n";
echo "\n📁 SERVICIOS EN app/Application/Services/\n";
echo str_repeat("=", 60) . "\n\n";

$servicios = [
    'PrendaServiceNew' => 'Gestión completa de prendas',
    'PrendaTelasService' => 'Gestión de telas en prendas',
    'PrendaVariantesService' => 'Gestión de variantes de prendas',
    'TipoPrendaDetectorService' => 'Detección de tipo de prenda',
    'ColorGeneroMangaBrocheService' => 'Gestión de colores, géneros, mangas y broches',
];

$dir = __DIR__ . '/app/Application/Services/';
foreach ($servicios as $servicio => $descripcion) {
    $archivo = $dir . $servicio . '.php';
    if (file_exists($archivo)) {
        echo "✅ $servicio\n";
        echo "   Descripción: $descripcion\n";
        echo "   Archivo: $archivo\n\n";
    } else {
        echo "❌ $servicio - NO ENCONTRADO\n\n";
    }
}

echo str_repeat("=", 60) . "\n";
echo "\n🔗 RELACIONES IDENTIFICADAS\n";
echo str_repeat("=", 60) . "\n\n";

echo "1. PrendaTelasService <-> prenda_telas\n";
echo "   - Gestiona la relación entre prendas y telas\n";
echo "   - Usa tabla: prenda_telas\n\n";

echo "2. PrendaVariantesService <-> variantes_prenda\n";
echo "   - Gestiona variantes de prendas\n";
echo "   - Usa tabla: variantes_prenda\n\n";

echo "3. ColorGeneroMangaBrocheService <-> genero_prendas\n";
echo "   - Gestiona géneros de prendas\n";
echo "   - Usa tabla: genero_prendas\n\n";

echo "4. TipoPrendaDetectorService <-> tipo_prendas\n";
echo "   - Detecta tipo de prenda\n";
echo "   - Usa tabla: tipo_prendas\n\n";

echo "5. PrendaServiceNew (Orquestador)\n";
echo "   - Usa todos los servicios anteriores\n";
echo "   - Coordina la creación de prendas\n\n";
