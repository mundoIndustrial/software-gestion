<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Cotizacion;
use App\Models\PrendaCotizacionFriendly;
use App\Models\VariantePrenda;
use Illuminate\Support\Facades\DB;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "🧪 TEST FINAL - OBSERVACIONES EN SHOW\n";
echo "═══════════════════════════════════════════════════════════════\n";

// Obtener la última cotización creada
$cotizacion = Cotizacion::latest()->first();

if (!$cotizacion) {
    echo "❌ No hay cotizaciones en la BD\n";
    exit;
}

echo "\n📋 Cotización encontrada (ID: {$cotizacion->id})\n";

// Obtener prendas
$prendas = $cotizacion->prendasCotizaciones;
echo "📦 Prendas encontradas: {$prendas->count()}\n";

if ($prendas->isEmpty()) {
    echo "❌ No hay prendas en esta cotización\n";
    exit;
}

// Verificar variantes
echo "\n🔍 Verificando variantes...\n";
foreach ($prendas as $index => $prenda) {
    $num = $index + 1;
    echo "\n   Prenda {$num}: {$prenda->nombre_producto}\n";
    
    $variantes = $prenda->variantes;
    echo "   Variantes encontradas: {$variantes->count()}\n";
    
    if ($variantes->isEmpty()) {
        echo "   ⚠️ Sin variantes\n";
        continue;
    }
    
    foreach ($variantes as $variante) {
        echo "\n   Variante ID: {$variante->id}\n";
        $color = $variante->color ? $variante->color->nombre : 'N/A';
        $tela = $variante->tela ? $variante->tela->nombre : 'N/A';
        $manga = $variante->tipoManga ? $variante->tipoManga->nombre : 'N/A';
        $bolsillos = $variante->tiene_bolsillos ? 'Sí' : 'No';
        $broche = $variante->tipoBroche ? $variante->tipoBroche->nombre : 'N/A';
        $reflectivo = $variante->tiene_reflectivo ? 'Sí' : 'No';
        
        echo "      - Color: {$color}\n";
        echo "      - Tela: {$tela}\n";
        echo "      - Manga: {$manga}\n";
        echo "      - Bolsillos: {$bolsillos}\n";
        echo "      - Broche: {$broche}\n";
        echo "      - Reflectivo: {$reflectivo}\n";
        
        // OBSERVACIONES
        if ($variante->descripcion_adicional) {
            echo "      - Observaciones:\n";
            echo "         {$variante->descripcion_adicional}\n";
        } else {
            echo "      - Observaciones: (vacías)\n";
        }
    }
}

// Verificar en BD directamente
echo "\n\n🔍 Verificando en BD directamente...\n";
$variantes = VariantePrenda::whereHas('prendaCotizacion', function($q) use ($cotizacion) {
    $q->where('cotizacion_id', $cotizacion->id);
})->get();

echo "✅ Variantes en BD: {$variantes->count()}\n";

foreach ($variantes as $v) {
    echo "\n   Variante ID: {$v->id}\n";
    echo "   - descripcion_adicional: " . ($v->descripcion_adicional ? $v->descripcion_adicional : '(vacía)') . "\n";
}

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "✅ TEST COMPLETADO\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";
echo "📝 RESUMEN:\n";
echo "   1. Cotización ID: {$cotizacion->id}\n";
echo "   2. Prendas: {$prendas->count()}\n";
echo "   3. Variantes: {$variantes->count()}\n";
echo "   4. Observaciones guardadas: " . ($variantes->where('descripcion_adicional', '!=', null)->count()) . "\n";
echo "\n";
echo "🌐 Para ver en show, abre: /asesores/cotizaciones/{$cotizacion->id}\n";
echo "\n";
