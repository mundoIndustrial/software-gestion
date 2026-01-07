<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Verificar datos para cotización 225
echo "=== COTIZACIÓN 225 ===\n";
$prendas225 = DB::table('logo_cotizacion_tecnica_prendas')
    ->where('logo_cotizacion_id', 225)
    ->get();

echo "Prendas en cotización 225: " . count($prendas225) . "\n";
foreach ($prendas225 as $p) {
    echo "  - ID: {$p->id}, Nombre: {$p->nombre_prenda}, TipoLogo: {$p->tipo_logo_id}, Grupo: {$p->grupo_combinado}\n";
    
    $fotos = DB::table('logo_cotizacion_tecnica_prendas_fotos')
        ->where('logo_cotizacion_tecnica_prenda_id', $p->id)
        ->get();
    echo "    Fotos: " . count($fotos) . "\n";
}

// Verificar si existe la cotización
$cot = DB::table('logo_cotizaciones')->where('id', 225)->first();
if ($cot) {
    echo "\nCotización encontrada: ID={$cot->id}\n";
} else {
    echo "\nCotización 225 NO EXISTE\n";
}

