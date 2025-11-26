<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== ÚLTIMA VARIANTE GUARDADA ===\n\n";

$variante = DB::table('variantes_prenda')
    ->latest('id')
    ->first();

if ($variante) {
    echo "Variante ID: {$variante->id}\n";
    echo "Prenda cotización ID: {$variante->prenda_cotizacion_id}\n";
    echo "Tipo prenda ID: {$variante->tipo_prenda_id}\n";
    echo "Color ID: {$variante->color_id}\n";
    echo "Tela ID: {$variante->tela_id}\n";
    echo "Género ID: {$variante->genero_id}\n";
    echo "Tipo manga ID: {$variante->tipo_manga_id}\n";
    echo "Tipo broche ID: {$variante->tipo_broche_id}\n";
    echo "Tiene bolsillos: " . ($variante->tiene_bolsillos ? 'SÍ' : 'NO') . "\n";
    echo "Tiene reflectivo: " . ($variante->tiene_reflectivo ? 'SÍ' : 'NO') . "\n";
    echo "Descripción adicional: {$variante->descripcion_adicional}\n";
    
    // Obtener relaciones
    $color = DB::table('colores_prenda')->where('id', $variante->color_id)->first();
    $tela = DB::table('telas_prenda')->where('id', $variante->tela_id)->first();
    $genero = DB::table('generos_prenda')->where('id', $variante->genero_id)->first();
    $manga = DB::table('tipos_manga')->where('id', $variante->tipo_manga_id)->first();
    
    echo "\n=== RELACIONES ===\n";
    echo "Color: " . ($color ? $color->nombre : 'NULL') . "\n";
    echo "Tela: " . ($tela ? $tela->nombre : 'NULL') . " (Ref: " . ($tela ? ($tela->referencia ?? 'NULL') : 'NULL') . ")\n";
    echo "Género: " . ($genero ? $genero->nombre : 'NULL') . "\n";
    echo "Manga: " . ($manga ? $manga->nombre : 'NULL') . "\n";
}
