<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== LLENAR descripcion_armada ===\n\n";

try {
    // Obtener todas las prendas_pedido que no tengan descripcion_armada
    $prendas = DB::table('prendas_pedido')
        ->select(
            'id',
            'nombre_prenda',
            'descripcion',
            'descripcion_variaciones',
            'cantidad_talla'
        )
        ->whereNull('descripcion_armada')
        ->orWhere('descripcion_armada', '')
        ->get();
    
    echo "Total de registros a actualizar: " . $prendas->count() . "\n\n";
    
    $actualizado = 0;
    foreach ($prendas as $prenda) {
        // Armar la descripción IGUAL QUE construirDescripcionPrenda() en PedidoService.php
        $lineas = [];
        
        // 1. Nombre de prenda
        $nombrePrenda = strtoupper($prenda->nombre_prenda ?? 'PRENDA');
        $lineas[] = $nombrePrenda;
        
        // 2. Descripción
        if (!empty($prenda->descripcion)) {
            $lineas[] = "Descripción: " . strtoupper($prenda->descripcion);
        }
        
        // 3. Descripción de variaciones (si es JSON)
        if ($prenda->descripcion_variaciones) {
            $desc_var = $prenda->descripcion_variaciones;
            if (is_string($desc_var) && json_decode($desc_var) !== null) {
                $vars = json_decode($desc_var, true);
                
                if (isset($vars['tela'])) {
                    $tela = strtoupper($vars['tela']);
                    if (isset($vars['tela_referencia'])) {
                        $tela .= ' REF:' . strtoupper($vars['tela_referencia']);
                    }
                    $lineas[] = "Tela: " . $tela;
                }
                if (isset($vars['color'])) {
                    $lineas[] = "Color: " . strtoupper($vars['color']);
                }
                if (isset($vars['manga_nombre'])) {
                    $lineas[] = "Manga: " . strtoupper($vars['manga_nombre']);
                }
            }
        }
        
        // 4. Tallas
        if ($prenda->cantidad_talla) {
            $tallaInfo = json_decode($prenda->cantidad_talla, true);
            if (is_array($tallaInfo)) {
                $tallas = [];
                foreach ($tallaInfo as $talla => $cantidad) {
                    if ($cantidad > 0) {
                        $tallas[] = "{$talla}:{$cantidad}";
                    }
                }
                if (!empty($tallas)) {
                    $lineas[] = "Tallas: " . implode(', ', $tallas);
                }
            }
        }
        
        // Armar descripción final con saltos de línea (IGUAL QUE construirDescripcionPrenda)
        $descripcionArmada = implode("\n", $lineas);
        
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
        ->where('descripcion_armada', '!=', '')
        ->limit(5)
        ->get();
    
    foreach ($ejemplos as $ej) {
        echo "ID: {$ej->id}\n";
        echo "  Nombre:    " . substr($ej->nombre_prenda, 0, 50) . "\n";
        echo "  Armada:    " . substr(str_replace("\n", " | ", $ej->descripcion_armada), 0, 100) . "...\n";
        echo "---\n";
    }
    
    echo "\n✅ ¡LISTO! Columna 'descripcion_armada' está lista para usar\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
