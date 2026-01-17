#!/usr/bin/env php
<?php
/**
 * Test directo de BuscarEppQuery
 */

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';

$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Domain\Epp\Queries\BuscarEppQuery;
use App\Domain\Shared\CQRS\QueryBus;

echo "🔍 Testando BuscarEppQuery...\n\n";

try {
    $queryBus = app(QueryBus::class);
    
    // Test 1: Buscar por término vacío
    echo "Test 1: Buscar todos (término vacío)\n";
    $query = new BuscarEppQuery('');
    $resultado = $queryBus->execute($query);
    echo "✅ Resultado: " . count($resultado) . " EPP encontrados\n\n";
    
    // Test 2: Buscar por código
    echo "Test 2: Buscar por código 'EPP-CAB'\n";
    $query = new BuscarEppQuery('EPP-CAB');
    $resultado = $queryBus->execute($query);
    echo "✅ Resultado: " . count($resultado) . " EPP encontrados\n";
    if (count($resultado) > 0) {
        foreach ($resultado as $epp) {
            echo "   - {$epp['codigo']}: {$epp['nombre']}\n";
        }
    }
    echo "\n";
    
    // Test 3: Buscar por nombre
    echo "Test 3: Buscar por nombre 'Casco'\n";
    $query = new BuscarEppQuery('Casco');
    $resultado = $queryBus->execute($query);
    echo "✅ Resultado: " . count($resultado) . " EPP encontrados\n";
    if (count($resultado) > 0) {
        foreach ($resultado as $epp) {
            echo "   - {$epp['codigo']}: {$epp['nombre']}\n";
        }
    }
    echo "\n";
    
    echo "✅ Todos los tests pasaron\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
