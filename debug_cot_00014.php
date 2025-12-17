<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Cotizacion;

echo "===== BUSCANDO COT-00014 =====" . PHP_EOL;
echo PHP_EOL;

// Buscar la cotización específica
$cot = Cotizacion::where('numero_cotizacion', 'COT-00014')->first();
if (!$cot) {
    echo "✗ No encontrada COT-00014" . PHP_EOL;
    exit;
}

echo "📋 Cotización encontrada:" . PHP_EOL;
echo "   ID: " . $cot->id . PHP_EOL;
echo "   Número: " . $cot->numero_cotizacion . PHP_EOL;
echo PHP_EOL;

// Verificar relaciones disponibles
echo "🔍 Verificando relaciones del modelo Cotizacion:" . PHP_EOL;

// 1. prendasCotizaciones
try {
    $count = $cot->prendasCotizaciones()->count();
    echo "   ✓ prendasCotizaciones: $count" . PHP_EOL;
} catch (\Exception $e) {
    echo "   ✗ prendasCotizaciones: " . $e->getMessage() . PHP_EOL;
}

// 2. prendas (alternativa)
try {
    $count = $cot->prendas()->count();
    echo "   ✓ prendas: $count" . PHP_EOL;
} catch (\Exception $e) {
    echo "   ✗ prendas: (no existe)" . PHP_EOL;
}

// 3. Buscar directamente en tablas
echo PHP_EOL . "📊 Buscando en tablas directas:" . PHP_EOL;
$tables = [
    'prendas_cot' => 'cotizacion_id',
    'prendas_cotizaciones' => 'cotizacion_id',
    'tabla_original' => 'numero_cotizacion',
];

foreach ($tables as $table => $field) {
    try {
        if ($field === 'numero_cotizacion') {
            $count = DB::table($table)->where($field, $cot->numero_cotizacion)->count();
        } else {
            $count = DB::table($table)->where($field, $cot->id)->count();
        }
        if ($count > 0) {
            echo "   ✓ $table: $count registros" . PHP_EOL;
            $sample = DB::table($table)->where($field === 'numero_cotizacion' ? $field : 'id', 
                                                $field === 'numero_cotizacion' ? $cot->numero_cotizacion : null)
                                       ->when($field !== 'numero_cotizacion', fn($q) => $q->where($field, $cot->id))
                                       ->first();
            if ($sample) {
                echo "      └─ Ejemplo: " . json_encode($sample, JSON_PRETTY_PRINT) . PHP_EOL;
            }
        }
    } catch (\Exception $e) {
        echo "   ✗ $table: (no existe o error)" . PHP_EOL;
    }
}

// 4. Mostrar las relaciones definidas en el modelo
echo PHP_EOL . "📋 Métodos de Cotizacion:" . PHP_EOL;
$methods = get_class_methods($cot);
foreach ($methods as $method) {
    if (strpos($method, 'prenda') !== false || strpos($method, 'Prenda') !== false) {
        echo "   • " . $method . "()" . PHP_EOL;
    }
}
