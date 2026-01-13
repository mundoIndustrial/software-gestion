<?php

// Script para verificar qué prendas tiene una cotización específica
// Uso: php verificar-prendas-cotizacion.php 297

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Cotizacion;
use App\Models\PrendaCot;

$cotizacionId = $argv[1] ?? 297;

echo "\n=== VERIFICANDO COTIZACIÓN ID: $cotizacionId ===\n\n";

// 1. Obtener cotización
$cotizacion = Cotizacion::find($cotizacionId);
if (!$cotizacion) {
    echo "❌ Cotización no encontrada\n";
    exit(1);
}

echo "✅ Cotización encontrada: " . $cotizacion->numero_cotizacion . "\n";
echo "   Tipo: " . ($cotizacion->tipoCotizacion?->codigo ?? 'N/A') . "\n";
echo "   Cliente: " . ($cotizacion->cliente?->nombre ?? 'N/A') . "\n\n";

// 2. Verificar tabla prendas_cot
echo "--- TABLA prendas_cot ---\n";
$prendasCot = DB::table('prendas_cot')
    ->where('cotizacion_id', $cotizacionId)
    ->get();

echo "Total en prendas_cot: " . $prendasCot->count() . "\n";
if ($prendasCot->count() > 0) {
    foreach ($prendasCot as $prenda) {
        echo "  - ID: {$prenda->id}, Nombre: {$prenda->nombre_producto}\n";
    }
} else {
    echo "  (vacío)\n";
}
echo "\n";

// 3. Verificar tabla prendas_cotizaciones
echo "--- TABLA prendas_cotizaciones ---\n";
$prendasCotizaciones = DB::table('prendas_cotizaciones')
    ->where('cotizacion_id', $cotizacionId)
    ->get();

echo "Total en prendas_cotizaciones: " . $prendasCotizaciones->count() . "\n";
if ($prendasCotizaciones->count() > 0) {
    foreach ($prendasCotizaciones as $prenda) {
        echo "  - ID: {$prenda->id}, Nombre: {$prenda->nombre_prenda}\n";
    }
} else {
    echo "  (vacío)\n";
}
echo "\n";

// 4. Verificar relación Eloquent prendas()
echo "--- RELACIÓN Eloquent prendas() ---\n";
$prendasEloquent = $cotizacion->prendas;
echo "Total via relación prendas(): " . $prendasEloquent->count() . "\n";
if ($prendasEloquent->count() > 0) {
    foreach ($prendasEloquent as $prenda) {
        echo "  - ID: {$prenda->id}, Nombre: {$prenda->nombre_producto}\n";
    }
} else {
    echo "  (vacío)\n";
}
echo "\n";

// 5. Verificar relación Eloquent prendasCotizaciones()
echo "--- RELACIÓN Eloquent prendasCotizaciones() ---\n";
$prendasCotizacionesEloquent = $cotizacion->prendasCotizaciones;
echo "Total via relación prendasCotizaciones(): " . $prendasCotizacionesEloquent->count() . "\n";
if ($prendasCotizacionesEloquent->count() > 0) {
    foreach ($prendasCotizacionesEloquent as $prenda) {
        echo "  - ID: {$prenda->id}, Nombre: {$prenda->nombre_prenda}\n";
    }
} else {
    echo "  (vacío)\n";
}
echo "\n";

// 6. Verificar logo
echo "--- LOGO ---\n";
$logo = $cotizacion->logoCotizacion;
if ($logo) {
    echo "✅ Tiene logo (ID: {$logo->id})\n";
    echo "   Prendas técnicas: " . $logo->prendas->count() . "\n";
    if ($logo->prendas->count() > 0) {
        foreach ($logo->prendas as $prenda) {
            echo "     - {$prenda->nombre_prenda}\n";
        }
    }
} else {
    echo "❌ No tiene logo\n";
}
echo "\n";

echo "=== FIN ===\n\n";
