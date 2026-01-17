<?php

/**
 * Script para probar la creación de EPP
 * 
 * Uso: php crear_epp_test.php
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

use App\Domain\Shared\CQRS\CommandBus;
use App\Application\Commands\CrearEppCommand;

// Obtener el CommandBus del container
$commandBus = $app->make(CommandBus::class);

try {
    echo "🔵 Iniciando prueba de creación de EPP...\n";
    
    // Datos de prueba
    $nombre = 'Gafas de Seguridad Prueba';
    $categoria = 'OJOS';
    $codigo = 'GAF-SEG-' . time();
    $descripcion = 'Gafas de seguridad para protección ocular. Prueba: ' . date('Y-m-d H:i:s');
    
    echo "📝 Datos a crear:\n";
    echo "  - Nombre: $nombre\n";
    echo "  - Categoría: $categoria\n";
    echo "  - Código: $codigo\n";
    echo "  - Descripción: $descripcion\n\n";
    
    // Crear command
    $command = new CrearEppCommand(
        nombre: $nombre,
        categoria: $categoria,
        codigo: $codigo,
        descripcion: $descripcion
    );
    
    // Ejecutar
    $resultado = $commandBus->execute($command);
    
    echo "✅ EPP creado exitosamente!\n";
    echo "📊 Resultado:\n";
    echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    
} catch (\Exception $e) {
    echo "❌ Error al crear EPP:\n";
    echo "   " . $e->getMessage() . "\n";
    echo "\n📋 Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
}
