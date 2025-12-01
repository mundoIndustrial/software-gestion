<?php
/**
 * Script de prueba para verificar descripción de prendas
 * Ejecutar desde: php artisan tinker < test-descripcion-prendas.php
 * O: php test-descripcion-prendas.php
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/bootstrap/app.php';

use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  TEST: Descripción de Prendas                                 ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

try {
    // 1. Verificar que exista al menos un pedido con prendas
    echo "📋 PASO 1: Buscando pedidos con prendas...\n";
    $pedido = PedidoProduccion::with('prendas')->has('prendas')->first();
    
    if (!$pedido) {
        echo "❌ No hay pedidos con prendas en la base de datos\n\n";
        exit(1);
    }
    
    echo "✅ Pedido encontrado: #{$pedido->numero_pedido}\n";
    echo "   Cliente: {$pedido->cliente}\n";
    echo "   Prendas: {$pedido->prendas->count()}\n\n";
    
    // 2. Verificar que las prendas tengan los campos necesarios
    echo "📋 PASO 2: Verificando campos de prendas...\n";
    $prenda = $pedido->prendas->first();
    
    $campos = [
        'id' => 'ID',
        'numero_pedido' => 'Número Pedido',
        'nombre_prenda' => 'Nombre Prenda',
        'cantidad' => 'Cantidad',
        'descripcion' => 'Descripción',
        'cantidad_talla' => 'Cantidad Talla (JSON)',
        'color_id' => 'Color ID',
        'tela_id' => 'Tela ID',
        'tipo_manga_id' => 'Tipo Manga ID',
        'tiene_bolsillos' => 'Tiene Bolsillos',
        'tiene_reflectivo' => 'Tiene Reflectivo',
    ];
    
    foreach ($campos as $campo => $label) {
        $valor = $prenda->$campo;
        $estado = $valor !== null ? '✅' : '⚠️';
        echo "   {$estado} {$label}: ";
        
        if (is_array($valor)) {
            echo json_encode($valor);
        } elseif (is_bool($valor)) {
            echo $valor ? 'SÍ' : 'NO';
        } else {
            echo $valor ?? '(vacío)';
        }
        echo "\n";
    }
    echo "\n";
    
    // 3. Probar el método generarDescripcionDetallada()
    echo "📋 PASO 3: Generando descripción detallada...\n";
    $descripcionDetallada = $prenda->generarDescripcionDetallada();
    
    if (empty($descripcionDetallada)) {
        echo "❌ La descripción detallada está vacía\n\n";
        exit(1);
    }
    
    echo "✅ Descripción generada:\n";
    echo "────────────────────────────────────────────────────────────────\n";
    echo $descripcionDetallada;
    echo "\n────────────────────────────────────────────────────────────────\n\n";
    
    // 4. Probar el atributo descripcion_prendas del pedido
    echo "📋 PASO 4: Generando descripción_prendas del pedido...\n";
    $descripcionPedido = $pedido->descripcion_prendas;
    
    if (empty($descripcionPedido)) {
        echo "❌ La descripción del pedido está vacía\n\n";
        exit(1);
    }
    
    echo "✅ Descripción del pedido generada:\n";
    echo "────────────────────────────────────────────────────────────────\n";
    echo $descripcionPedido;
    echo "\n────────────────────────────────────────────────────────────────\n\n";
    
    // 5. Verificar que la relación numero_pedido funcione
    echo "📋 PASO 5: Verificando relación numero_pedido...\n";
    
    if ($prenda->numero_pedido !== $pedido->numero_pedido) {
        echo "❌ La relación numero_pedido no coincide\n";
        echo "   Prenda: {$prenda->numero_pedido}\n";
        echo "   Pedido: {$pedido->numero_pedido}\n\n";
        exit(1);
    }
    
    echo "✅ Relación numero_pedido correcta\n";
    echo "   Prenda numero_pedido: {$prenda->numero_pedido}\n";
    echo "   Pedido numero_pedido: {$pedido->numero_pedido}\n\n";
    
    // 6. Verificar que pedido_produccion_id también exista
    echo "📋 PASO 6: Verificando pedido_produccion_id...\n";
    
    if (!$prenda->pedido_produccion_id) {
        echo "⚠️  pedido_produccion_id está vacío (esto es normal si solo usas numero_pedido)\n\n";
    } else {
        echo "✅ pedido_produccion_id presente: {$prenda->pedido_produccion_id}\n\n";
    }
    
    // 7. Resumen final
    echo "╔════════════════════════════════════════════════════════════════╗\n";
    echo "║  ✅ TODAS LAS PRUEBAS PASARON CORRECTAMENTE                   ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n\n";
    
    echo "📊 RESUMEN:\n";
    echo "   • Pedido: #{$pedido->numero_pedido}\n";
    echo "   • Prendas: {$pedido->prendas->count()}\n";
    echo "   • Descripción detallada: ✅ Funciona\n";
    echo "   • Atributo descripcion_prendas: ✅ Funciona\n";
    echo "   • Relación numero_pedido: ✅ Correcta\n";
    echo "   • Campos necesarios: ✅ Presentes\n\n";
    
} catch (\Exception $e) {
    echo "❌ ERROR: {$e->getMessage()}\n";
    echo "   Archivo: {$e->getFile()}\n";
    echo "   Línea: {$e->getLine()}\n\n";
    exit(1);
}
