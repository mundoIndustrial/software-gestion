<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== LIMPIAR descripcion_armada ===\n\n";

try {
    // Contar registros con descripcion_armada
    $totalConDescripcion = DB::table('prendas_pedido')
        ->whereNotNull('descripcion_armada')
        ->where('descripcion_armada', '!=', '')
        ->count();
    
    echo "Total de registros con descripcion_armada: $totalConDescripcion\n\n";
    
    if ($totalConDescripcion === 0) {
        echo "✅ No hay registros para limpiar. La columna ya está vacía.\n";
        exit;
    }
    
    // Confirmar antes de eliminar
    echo "⚠️  ADVERTENCIA: Esto eliminará TODA la información en el campo descripcion_armada\n";
    echo "¿Deseas continuar? (s/n): ";
    
    $handle = fopen("php://stdin", "r");
    $respuesta = trim(fgets($handle));
    fclose($handle);
    
    if (strtolower($respuesta) !== 's') {
        echo "\n❌ Operación cancelada.\n";
        exit;
    }
    
    echo "\n🔄 Limpiando descripcion_armada...\n\n";
    
    // Limpiar la columna (establecer a NULL)
    $actualizado = DB::table('prendas_pedido')
        ->update(['descripcion_armada' => NULL]);
    
    echo "✅ Registros actualizados: $actualizado\n\n";
    
    // Verificar que se limpió
    $verificacion = DB::table('prendas_pedido')
        ->whereNotNull('descripcion_armada')
        ->where('descripcion_armada', '!=', '')
        ->count();
    
    if ($verificacion === 0) {
        echo "✅ ¡LISTO! La columna 'descripcion_armada' ha sido limpiada completamente.\n";
        echo "Total de registros limpios: $actualizado\n";
    } else {
        echo "⚠️  Aún hay $verificacion registros con descripcion_armada\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
