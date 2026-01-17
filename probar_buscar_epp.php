#!/usr/bin/env php
<?php
/**
 * Script para probar búsqueda de EPP
 * Uso: php probar_buscar_epp.php "casco"
 */

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/bootstrap/app.php';

$termino = $argv[1] ?? 'casco';

echo "🔍 Buscando EPP con término: '{$termino}'\n";
echo "═══════════════════════════════════════════════════════════\n";

try {
    $query = new \App\Domain\Epp\Queries\BuscarEppQuery($termino);
    $queryBus = app(\App\Domain\Shared\CQRS\QueryBus::class);
    
    $epps = $queryBus->execute($query);
    
    echo "✅ Búsqueda exitosa\n";
    echo "📊 Total encontrado: " . count($epps) . "\n";
    echo "\n";
    
    foreach ($epps as $index => $epp) {
        echo "[$index] {$epp['nombre']}\n";
        echo "    • Código: {$epp['codigo']}\n";
        echo "    • Categoría: {$epp['categoria']}\n";
        echo "    • Descripción: {$epp['descripcion']}\n";
        echo "    • Imágenes: " . count($epp['imagenes'] ?? []) . "\n";
        echo "\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "\nTRACE:\n";
    echo $e->getTraceAsString();
    exit(1);
}

echo "✅ Test completado\n";
