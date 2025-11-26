<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== VERIFICACIÓN PRE-TEST ===\n\n";

// Verificar tipos_prenda
$tipos = DB::table('tipos_prenda')->count();
echo "✓ Tipos de prenda en BD: {$tipos}\n";

// Verificar tipos_manga
$mangas = DB::table('tipos_manga')->count();
echo "✓ Tipos de manga en BD: {$mangas}\n";

// Última cotización guardada
$cotizacion = DB::table('cotizaciones')->latest('id')->first();
if ($cotizacion) {
    $prendas = DB::table('prendas_cotizaciones')->where('cotizacion_id', $cotizacion->id)->count();
    $variantes = DB::table('variantes_prenda')
        ->whereIn('prenda_cotizacion_id', DB::table('prendas_cotizaciones')->where('cotizacion_id', $cotizacion->id)->pluck('id'))
        ->count();
    
    echo "\nÚltima cotización: ID {$cotizacion->id}\n";
    echo "  - Prendas: {$prendas}\n";
    echo "  - Variantes: {$variantes}\n";
}

echo "\n✅ Sistema listo para test.\n";
echo "\nAhora prueba enviar una cotización nuevamente desde el frontend.\n";
