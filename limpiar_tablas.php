<?php
require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n=== LIMPIEZA COMPLETA DE TABLAS ===\n\n";

try {
    // Desactivar checks de integridad referencial
    \DB::statement('SET FOREIGN_KEY_CHECKS=0');
    
    echo "🧹 Limpiando tablas...\n";
    
    // Limpiar en orden correcto (dependencias primero)
    \DB::table('procesos_prenda')->truncate();
    echo "  ✓ procesos_prenda truncated\n";
    
    \DB::table('prendas_pedido')->truncate();
    echo "  ✓ prendas_pedido truncated\n";
    
    \DB::table('pedidos_produccion')->truncate();
    echo "  ✓ pedidos_produccion truncated\n";
    
    // Reactivar checks
    \DB::statement('SET FOREIGN_KEY_CHECKS=1');
    
    echo "\n✅ Limpieza completada exitosamente\n\n";
    
} catch (\Exception $e) {
    echo "\n❌ Error en limpieza: " . $e->getMessage() . "\n\n";
}
