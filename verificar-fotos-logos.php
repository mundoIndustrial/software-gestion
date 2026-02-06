<?php
require 'vendor/autoload.php';
require 'bootstrap/app.php';

use App\Models\LogoCotizacionTecnicaPrenda;
use App\Models\LogoCotizacionTecnicaPrendaFoto;

$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 VERIFICANDO FOTOS DE PROCESOS DE LOGOS\n";
echo "==========================================\n\n";

// 1. Contar técnicas de logo
$tecnicas = LogoCotizacionTecnicaPrenda::all();
echo "📊 Total de técnicas de logo: " . $tecnicas->count() . "\n\n";

if ($tecnicas->count() === 0) {
    echo "⚠️  No hay técnicas de logo registradas\n";
    exit;
}

// 2. Para cada técnica, verificar fotos
foreach ($tecnicas as $tecnica) {
    echo "🔗 Técnica ID {$tecnica->id}:\n";
    echo "   - Prenda: {$tecnica->prenda_cot_id}\n";
    echo "   - Cotización: {$tecnica->logo_cotizacion_id}\n";
    
    // Contar fotos
    $fotos = LogoCotizacionTecnicaPrendaFoto::where('logo_cotizacion_tecnica_prenda_id', $tecnica->id)->get();
    echo "   - Fotos: " . $fotos->count() . "\n";
    
    if ($fotos->count() > 0) {
        foreach ($fotos as $idx => $foto) {
            echo "     [{$idx}] ruta_original: " . ($foto->ruta_original ? '✓' : '✗ NULL') . "\n";
            echo "         ruta_webp: " . ($foto->ruta_webp ? '✓' : '✗ NULL') . "\n";
            echo "         ruta_miniatura: " . ($foto->ruta_miniatura ? '✓' : '✗ NULL') . "\n";
            if ($foto->ruta_webp) {
                echo "         Ruta: {$foto->ruta_webp}\n";
            }
        }
    } else {
        echo "     ⚠️  Sin fotos\n";
    }
    echo "\n";
}

echo "\n✅ Verificación completada\n";
