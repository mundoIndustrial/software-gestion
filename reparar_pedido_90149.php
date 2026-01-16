<?php
/**
 * REPARACIÓN DEL PEDIDO #90149
 * Script para completar datos faltantes (tallas, colores, telas, imágenes)
 * Ejecutar: php artisan tinker < reparar_pedido_90149.php
 */

use Illuminate\Support\Facades\DB;

echo "\n" . str_repeat("=", 110) . "\n";
echo "🔧 REPARACIÓN PEDIDO #90149 - Completar datos faltantes\n";
echo str_repeat("=", 110) . "\n\n";

// 1. Obtener el pedido
$pedido = DB::table('pedidos_produccion')->where('numero_pedido', 90149)->first();

if (!$pedido) {
    echo "❌ Pedido #90149 no encontrado\n";
    exit;
}

echo "📋 Pedido encontrado: ID={$pedido->id}, Cliente={$pedido->cliente}\n\n";

// 2. Obtener todas las variantes del pedido
$prendas = DB::table('prendas_pedido')->where('pedido_id', $pedido->id)->get();

foreach ($prendas as $prenda) {
    echo "👕 PRENDA: {$prenda->nombre_prenda}\n";
    echo str_repeat("-", 110) . "\n";
    
    $variantes = DB::table('prenda_pedido_variantes')
        ->where('prenda_pedido_id', $prenda->id)
        ->get();
    
    foreach ($variantes as $var) {
        echo "  Variante ID {$var->id}:\n";
        
        // Verificar color_id
        if (!$var->color_id) {
            echo "    ❌ SIN color_id\n";
            echo "    Para reparar, ejecuta:\n";
            echo "    UPDATE prenda_pedido_variantes SET color_id = [ID_COLOR] WHERE id = {$var->id};\n";
        } else {
            echo "    ✅ color_id = {$var->color_id}\n";
        }
        
        // Verificar tela_id
        if (!$var->tela_id) {
            echo "    ❌ SIN tela_id\n";
            echo "    Para reparar, ejecuta:\n";
            echo "    UPDATE prenda_pedido_variantes SET tela_id = [ID_TELA] WHERE id = {$var->id};\n";
        } else {
            echo "    ✅ tela_id = {$var->tela_id}\n";
        }
    }
    
    // Verificar procesos
    echo "\n  ⚙️  PROCESOS:\n";
    $procesos = DB::table('pedidos_procesos_prenda_detalles')
        ->where('prenda_pedido_id', $prenda->id)
        ->get();
    
    foreach ($procesos as $proc) {
        echo "    Proceso ID {$proc->id}:\n";
        
        $tallas = json_decode($proc->tallas_dama, true) ?? [];
        if (empty($tallas)) {
            echo "      ❌ tallas_dama VACÍO\n";
            echo "      Para reparar, agregá las tallas manualmente en la BD:\n";
            echo "      UPDATE pedidos_procesos_prenda_detalles SET tallas_dama = '{\"S\": 10, \"M\": 15}' WHERE id = {$proc->id};\n";
        } else {
            echo "      ✅ tallas_dama = " . json_encode($tallas) . "\n";
        }
    }
    
    echo "\n";
}

echo str_repeat("=", 110) . "\n";
echo "💡 PRÓXIMOS PASOS:\n";
echo str_repeat("=", 110) . "\n\n";

echo "Este pedido fue creado ANTES de los fixes. Para completarlo:\n\n";

echo "1. Identificar qué colores/telas/tallas debería tener\n";
echo "2. Agregar manualmente los IDs de colores/telas en prenda_pedido_variantes\n";
echo "3. Agregar manualmente las tallas en pedidos_procesos_prenda_detalles\n\n";

echo "RECOMENDACIÓN: Es mejor crear UN NUEVO PEDIDO con los fixes implementados.\n";
echo "Los fixes garantizan que todos los datos se guarden correctamente.\n";
echo str_repeat("=", 110) . "\n\n";
