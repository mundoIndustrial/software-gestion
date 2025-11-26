<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== ÚLTIMA COTIZACIÓN Y VARIANTES ===\n\n";

$cotizacion = DB::table('cotizaciones')->latest('id')->first();
if ($cotizacion) {
    echo "Cotización ID: {$cotizacion->id}\n";
    echo "Cliente: {$cotizacion->cliente}\n";
    echo "Tipo: {$cotizacion->tipo}\n";
    
    $prendas = DB::table('prendas_cotizaciones')->where('cotizacion_id', $cotizacion->id)->get();
    echo "\nPrendas: " . count($prendas) . "\n";
    
    foreach ($prendas as $prenda) {
        echo "\n  Prenda ID: {$prenda->id}\n";
        echo "  Nombre: {$prenda->nombre_producto}\n";
        
        $variantes = DB::table('variantes_prenda')->where('prenda_cotizacion_id', $prenda->id)->get();
        echo "  Variantes: " . count($variantes) . "\n";
        
        foreach ($variantes as $var) {
            echo "\n    Variante ID: {$var->id}\n";
            
            // Obtener relaciones
            $color = DB::table('colores_prenda')->where('id', $var->color_id)->first();
            $tela = DB::table('telas_prenda')->where('id', $var->tela_id)->first();
            $genero = DB::table('generos_prenda')->where('id', $var->genero_id)->first();
            $manga = DB::table('tipos_manga')->where('id', $var->tipo_manga_id)->first();
            
            echo "      Color: " . ($color ? $color->nombre : 'NULL') . "\n";
            echo "      Tela: " . ($tela ? $tela->nombre : 'NULL');
            if ($tela && $tela->referencia) {
                echo " (Ref: {$tela->referencia})";
            }
            echo "\n";
            echo "      Género: " . ($genero ? $genero->nombre : 'NULL') . "\n";
            echo "      Manga: " . ($manga ? $manga->nombre : 'NULL') . "\n";
            echo "      Bolsillos: " . ($var->tiene_bolsillos ? 'SÍ' : 'NO') . "\n";
            echo "      Reflectivo: " . ($var->tiene_reflectivo ? 'SÍ' : 'NO') . "\n";
            echo "      Descripción: " . substr($var->descripcion_adicional ?? '', 0, 100) . "...\n";
        }
    }
}
