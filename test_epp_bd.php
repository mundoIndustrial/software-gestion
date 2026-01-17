#!/usr/bin/env php
<?php
/**
 * Test de búsqueda de EPP - Debug directo
 */

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';

$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Epp;

echo "🔍 Buscando EPP en la BD...\n\n";

// Buscar todos los EPP
$epps = Epp::with('imagenes', 'categoria')->where('activo', true)->limit(5)->get();

echo "✅ Total EPP encontrados: " . $epps->count() . "\n\n";

foreach ($epps as $epp) {
    echo "📌 EPP ID: {$epp->id}\n";
    echo "   Código: {$epp->codigo}\n";
    echo "   Nombre: {$epp->nombre}\n";
    $categoriaCode = isset($epp->categoria) ? $epp->categoria->codigo : 'N/A';
    echo "   Categoría: {$categoriaCode}\n";
    echo "   Imágenes: {$epp->imagenes->count()}\n";
    
    if ($epp->imagenes->count() > 0) {
        foreach ($epp->imagenes as $img) {
            $principal = $img->principal ? 'sí' : 'no';
            echo "     - {$img->archivo} (principal: {$principal})\n";
        }
    }
    echo "\n";
}

echo "✅ Test completado\n";
