<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== VERIFICANDO COTIZACION 9 ===\n\n";

$cotizacion = \App\Models\Cotizacion::with(['prendas.variantes', 'prendas.telaFotos'])->find(9);

if (!$cotizacion) {
    echo "❌ Cotización 9 no encontrada\n";
    exit;
}

echo "✅ Cotización encontrada: {$cotizacion->codigo}\n";
echo "Prendas: {$cotizacion->prendas->count()}\n\n";

foreach ($cotizacion->prendas as $prenda) {
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Prenda: {$prenda->nombre_producto}\n";
    
    $variante = $prenda->variantes->first();
    if ($variante) {
        echo "\nVariante ID: {$variante->id}\n";
        echo "Color principal: " . ($variante->color ?? 'N/A') . "\n";
        
        if ($variante->telas_multiples) {
            echo "\n🧵 TELAS MÚLTIPLES:\n";
            $telas = is_string($variante->telas_multiples) 
                ? json_decode($variante->telas_multiples, true) 
                : $variante->telas_multiples;
            
            foreach ($telas as $index => $tela) {
                echo "  Tela {$index}:\n";
                echo "    - Color: " . ($tela['color'] ?? 'N/A') . "\n";
                echo "    - Tela: " . ($tela['tela'] ?? 'N/A') . "\n";
                echo "    - Referencia: " . ($tela['referencia'] ?? 'N/A') . "\n";
            }
        } else {
            echo "⚠️ No hay telas_multiples\n";
        }
    } else {
        echo "⚠️ No hay variante\n";
    }
    
    echo "\n📸 FOTOS DE TELA: {$prenda->telaFotos->count()}\n";
    foreach ($prenda->telaFotos as $foto) {
        echo "  - ID: {$foto->id}, tela_index: " . ($foto->tela_index ?? 'NULL') . ", ruta: {$foto->ruta_webp}\n";
    }
    
    echo "\n";
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
