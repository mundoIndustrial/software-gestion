<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Cotizacion;

echo "===== INVESTIGACIÓN DE PRENDAS =====" . PHP_EOL;
echo PHP_EOL;

// Buscar la cotización
$cot = Cotizacion::first();
if (!$cot) {
    echo "✗ No hay cotizaciones" . PHP_EOL;
    exit;
}

echo "📋 Cotización encontrada:" . PHP_EOL;
echo "   ID: " . $cot->id . PHP_EOL;
echo "   Número: " . $cot->numero_cotizacion . PHP_EOL;
echo PHP_EOL;

// Ver qué tablas existen
$tables = DB::select('SHOW TABLES');
echo "📊 Tablas disponibles:" . PHP_EOL;
foreach ($tables as $table) {
    $tableName = $table->{'Tables_in_' . env('DB_DATABASE')};
    if (strpos($tableName, 'prenda') !== false) {
        echo "   • " . $tableName . PHP_EOL;
    }
}
echo PHP_EOL;

// Contar registros en cada tabla de prendas
$tables_to_check = [
    'prendas_cotizaciones',
    'prenda_cotizacion_friendly',
    'tabla_original',
    'registros_prendas'
];

foreach ($tables_to_check as $table) {
    try {
        $count = DB::table($table)->where('cotizacion_id', $cot->id)->count();
        echo "   ✓ $table: $count registros" . PHP_EOL;
        
        if ($count > 0 && $table === 'tabla_original') {
            $sample = DB::table($table)->where('cotizacion_id', $cot->id)->first();
            if ($sample) {
                echo "      └─ Ejemplo: " . json_encode($sample) . PHP_EOL;
            }
        }
    } catch (\Exception $e) {
        echo "   ✗ $table: (tabla no existe)" . PHP_EOL;
    }
}
