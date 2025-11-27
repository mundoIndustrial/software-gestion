<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== LLENAR descripcion_armada ===\n\n";

try {
    // Obtener todas las prendas_pedido
    $prendas = DB::table('prendas_pedido')
        ->select(
            'id',
            'nombre_prenda',
            'descripcion',
            'descripcion_variaciones',
            'cantidad_talla'
        )
        ->get();
    
    echo "Total de registros: " . $prendas->count() . "\n\n";
    
    $actualizado = 0;
    foreach ($prendas as $prenda) {
        // Armar la descripción
        $partes = [];
        
        // Agregar nombre de prenda (tipo de prenda)
        if ($prenda->nombre_prenda) {
            $partes[] = trim($prenda->nombre_prenda);
        }
        
        // Agregar descripción
        if ($prenda->descripcion) {
            $partes[] = trim($prenda->descripcion);
        }
        
        // Agregar descripción de variaciones
        if ($prenda->descripcion_variaciones) {
            $partes[] = trim($prenda->descripcion_variaciones);
        }
        
        // Agregar cantidad/talla
        if ($prenda->cantidad_talla) {
            $tallaInfo = json_decode($prenda->cantidad_talla, true);
            if (is_array($tallaInfo)) {
                $tallas = implode(', ', array_keys($tallaInfo));
                $partes[] = "TALLAS: " . $tallas;
            }
        }
        
        // Armar descripción final
        $descripcionArmada = implode(' | ', array_filter($partes));
        
        // Actualizar
        DB::table('prendas_pedido')
            ->where('id', $prenda->id)
            ->update(['descripcion_armada' => $descripcionArmada]);
        
        $actualizado++;
        
        if ($actualizado % 100 === 0) {
            echo "  ✓ Actualizados: $actualizado\n";
        }
    }
    
    echo "\n✅ Totales actualizados: $actualizado\n\n";
    
    // Verificar algunos ejemplos
    echo "=== EJEMPLOS DE DESCRIPCIONES ARMADAS ===\n\n";
    $ejemplos = DB::table('prendas_pedido')
        ->whereNotNull('descripcion_armada')
        ->limit(5)
        ->get();
    
    foreach ($ejemplos as $ej) {
        echo "ID: {$ej->id}\n";
        echo "  Original:  " . substr($ej->descripcion, 0, 70) . "...\n";
        echo "  Armada:    " . substr($ej->descripcion_armada, 0, 100) . "...\n";
        echo "---\n";
    }
    
    echo "\n✅ ¡LISTO! Columna 'descripcion_armada' está lista para usar\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
