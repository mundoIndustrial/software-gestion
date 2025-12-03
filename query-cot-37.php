<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Cotizacion;
use App\Models\LogoCotizacion;

echo "\n=== COTIZACION 37 ===\n";
$cot = Cotizacion::find(37);
if ($cot) {
    echo "ID: {$cot->id}\n";
    echo "Tipo: {$cot->tipo}\n";
    echo "Estado: {$cot->estado}\n";
    echo "Creada: {$cot->created_at}\n";
    echo "Actualizada: {$cot->updated_at}\n";
} else {
    echo "NO EXISTE COTIZACION 37\n";
}

echo "\n=== LOGO_COTIZACION PARA COT 37 ===\n";
$logo = LogoCotizacion::where('cotizacion_id', 37)->first();
if ($logo) {
    echo "ID: {$logo->id}\n";
    echo "Cotización ID: {$logo->cotizacion_id}\n";
    echo "Observaciones:\n";
    echo json_encode($logo->observaciones_generales, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    echo "Imagenes (JSON):\n";
    echo json_encode($logo->imagenes, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
} else {
    echo "NO EXISTE LOGO_COTIZACION PARA COT 37\n";
}

echo "\n=== ULTIMAS 5 LOGO_COTIZACIONES ===\n";
$logos = LogoCotizacion::latest('id')->limit(5)->get();
foreach ($logos as $l) {
    $obs = is_array($l->observaciones_generales) ? count($l->observaciones_generales) : 0;
    echo "ID: {$l->id} | Cot: {$l->cotizacion_id} | Imgs: " . count($l->imagenes ?? []) . " | Obs: {$obs}\n";
}

echo "\n=== VERIFICAR CARPETA ALMACENAMIENTO ===\n";
$rutaFisica = storage_path('app/public/cotizaciones/37/logo');
echo "Ruta física: {$rutaFisica}\n";
echo "Existe: " . (is_dir($rutaFisica) ? "SÍ" : "NO") . "\n";

if (is_dir($rutaFisica)) {
    $archivos = glob($rutaFisica . '/*');
    echo "Archivos: " . count($archivos) . "\n";
    foreach ($archivos as $arch) {
        echo "  - " . basename($arch) . "\n";
    }
}

echo "\n=== TODAS LAS CARPETAS EN COTIZACIONES ===\n";
$basePath = storage_path('app/public/cotizaciones');
if (is_dir($basePath)) {
    $folders = glob($basePath . '/*', GLOB_ONLYDIR);
    echo "Total carpetas: " . count($folders) . "\n";
    foreach (array_slice($folders, -5) as $f) {
        echo "  - " . basename($f) . "/\n";
        $tipos = glob($f . '/*', GLOB_ONLYDIR);
        foreach ($tipos as $t) {
            echo "    - " . basename($t) . "/\n";
        }
    }
}
?>
