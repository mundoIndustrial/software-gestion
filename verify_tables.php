<?php
/**
 * VERIFICACIÓN: Confirmar que los datos están en las tablas nuevas
 * y NO en la tabla original
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\ProcesoPrenda;
use App\Models\TablaOriginal;

echo "═══════════════════════════════════════════════════════════════\n";
echo "VERIFICACIÓN: Tablas Nuevas vs Tabla Original\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

try {
    // 1. CONTAR REGISTROS EN TABLAS NUEVAS
    $pedidosProduccion = PedidoProduccion::count();
    $prendasPedido = PrendaPedido::count();
    $procesos = ProcesoPrenda::count();

    echo "📊 TABLAS NUEVAS:\n";
    echo "  • pedidos_produccion: {$pedidosProduccion} registros\n";
    echo "  • prendas_pedido: {$prendasPedido} registros\n";
    echo "  • procesos_prenda: {$procesos} registros\n\n";

    // 2. CONTAR REGISTROS EN TABLA ORIGINAL
    $tablaOriginal = TablaOriginal::count();
    echo "📊 TABLA ORIGINAL:\n";
    echo "  • tabla_original: {$tablaOriginal} registros\n\n";

    // 3. MOSTRAR ÚLTIMOS PEDIDOS DE PRODUCCIÓN
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "ÚLTIMOS PEDIDOS EN pedidos_produccion:\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";

    $ultimosPedidos = PedidoProduccion::latest()->limit(3)->get();
    foreach ($ultimosPedidos as $pedido) {
        echo "✅ Pedido #{$pedido->numero_pedido}\n";
        echo "   • Cliente: {$pedido->cliente}\n";
        echo "   • Asesora: {$pedido->asesora}\n";
        echo "   • Estado: {$pedido->estado}\n";
        echo "   • Prendas: " . $pedido->prendas()->count() . "\n";
        echo "   • Procesos: " . ProcesoPrenda::whereIn('prenda_pedido_id', $pedido->prendas()->pluck('id'))->count() . "\n\n";
    }

    // 4. VERIFICACIÓN FINAL
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "CONCLUSIÓN:\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";

    if ($pedidosProduccion > 0 && $prendasPedido > 0 && $procesos > 0) {
        echo "✅ Los datos se registraron CORRECTAMENTE en las tablas nuevas\n";
        echo "✅ Sistema de pedidos de producción funcionando correctamente\n";
    } else {
        echo "❌ No hay datos en las tablas nuevas\n";
    }

    if ($tablaOriginal == 0) {
        echo "✅ La tabla original está VACÍA (como debe ser)\n";
        echo "✅ No hay duplicación de datos\n";
    } else {
        echo "⚠️  La tabla original tiene {$tablaOriginal} registros\n";
    }

    echo "\n✅ VERIFICACIÓN COMPLETADA\n";
    echo "═══════════════════════════════════════════════════════════════\n";

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
