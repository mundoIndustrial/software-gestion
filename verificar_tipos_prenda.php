<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\TipoPrenda;
use Illuminate\Support\Facades\DB;

echo "🔍 Verificando tipos de prenda...\n";

$tipos = TipoPrenda::all();

if ($tipos->isEmpty()) {
    echo "❌ No hay tipos de prenda en la BD\n";
    echo "\n📝 Creando tipos de prenda...\n";
    
    $tiposACrear = [
        [
            'nombre' => 'JEAN',
            'codigo' => 'JEAN',
            'palabras_clave' => ['JEAN', 'DENIM'],
            'descripcion' => 'Prendas tipo jean',
            'activo' => true
        ],
        [
            'nombre' => 'CAMISA',
            'codigo' => 'CAMISA',
            'palabras_clave' => ['CAMISA', 'SHIRT'],
            'descripcion' => 'Prendas tipo camisa',
            'activo' => true
        ],
        [
            'nombre' => 'CAMISETA',
            'codigo' => 'CAMISETA',
            'palabras_clave' => ['CAMISETA', 'T-SHIRT', 'TSHIRT'],
            'descripcion' => 'Prendas tipo camiseta',
            'activo' => true
        ],
        [
            'nombre' => 'PANTALÓN',
            'codigo' => 'PANTALON',
            'palabras_clave' => ['PANTALÓN', 'PANTALON', 'PANTS'],
            'descripcion' => 'Prendas tipo pantalón',
            'activo' => true
        ],
        [
            'nombre' => 'POLO',
            'codigo' => 'POLO',
            'palabras_clave' => ['POLO'],
            'descripcion' => 'Prendas tipo polo',
            'activo' => true
        ]
    ];
    
    foreach ($tiposACrear as $tipo) {
        TipoPrenda::create($tipo);
        echo "✅ Creado: {$tipo['nombre']}\n";
    }
    
    echo "\n✅ Tipos de prenda creados exitosamente\n";
} else {
    echo "✅ Tipos de prenda encontrados:\n";
    foreach ($tipos as $tipo) {
        echo "   - {$tipo->nombre} (palabras clave: " . implode(', ', $tipo->palabras_clave ?? []) . ")\n";
    }
}

echo "\n🧪 Probando reconocimiento de 'CAMISA DRILL'...\n";
$tipoPrenda = TipoPrenda::reconocerPorNombre('CAMISA DRILL');

if ($tipoPrenda) {
    echo "✅ Tipo reconocido: {$tipoPrenda->nombre}\n";
} else {
    echo "❌ No se pudo reconocer el tipo de prenda\n";
}
