<?php
/**
 * PRUEBA: Tabla de Asesores con Pedidos de Producción
 */

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Cotizacion;
use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\ProcesoPrenda;

echo "═══════════════════════════════════════════════════════════════\n";
echo "PRUEBA: Tabla de Asesores con Pedidos de Producción\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

try {
    // 1. OBTENER ASESOR
    $asesor = User::where('email', 'yus2@test.com')->first();
    if (!$asesor) {
        $asesor = User::create([
            'name' => 'yus2',
            'email' => 'yus2@test.com',
            'password' => bcrypt('password'),
        ]);
    }
    echo "✅ Asesor: {$asesor->name}\n";

    // 2. CREAR COTIZACIÓN
    $cotizacion = Cotizacion::create([
        'user_id' => $asesor->id,
        'numero_cotizacion' => 'COT-' . date('YmdHis'),
        'cliente' => 'MINCIVIL',
        'asesora' => $asesor->name,
        'forma_de_pago' => 'CRÉDITO',
        'estado' => 'Aprobada',
        'productos' => json_encode([['nombre_producto' => 'CAMISA DRILL', 'cantidad' => 50]]),
    ]);
    echo "✅ Cotización: {$cotizacion->numero_cotizacion}\n";

    // 3. CREAR PEDIDO
    $pedido = PedidoProduccion::create([
        'cotizacion_id' => $cotizacion->id,
        'numero_cotizacion' => $cotizacion->numero_cotizacion,
        'numero_pedido' => rand(45000, 50000),
        'cliente' => 'MINCIVIL',
        'asesora' => $asesor->name,
        'forma_de_pago' => 'CRÉDITO',
        'estado' => 'En Ejecución',
        'novedades' => 'Prueba de tabla de asesores',
        'fecha_de_creacion_de_orden' => now(),
        'dia_de_entrega' => 15,
        'fecha_estimada_de_entrega' => now()->addDays(15),
    ]);
    echo "✅ Pedido: #{$pedido->numero_pedido}\n";

    // 4. CREAR PRENDAS
    $prenda = PrendaPedido::create([
        'pedido_produccion_id' => $pedido->id,
        'nombre_prenda' => 'CAMISA DRILL',
        'cantidad' => '50',
        'descripcion' => 'Camiseta drill con bordado',
    ]);
    echo "✅ Prenda: {$prenda->nombre_prenda}\n";

    // 5. CREAR PROCESOS
    $procesos_datos = [
        ['proceso' => 'Creación Orden', 'encargado' => 'CINDY'],
        ['proceso' => 'Corte', 'encargado' => 'RAMIRO'],
        ['proceso' => 'Costura', 'encargado' => 'RAMIRO'],
        ['proceso' => 'Entrega', 'encargado' => 'JONATHAN'],
    ];

    foreach ($procesos_datos as $p) {
        ProcesoPrenda::create([
            'prenda_pedido_id' => $prenda->id,
            'proceso' => $p['proceso'],
            'fecha_inicio' => now(),
            'encargado' => $p['encargado'],
            'estado_proceso' => 'Completado',
        ]);
    }
    echo "✅ Procesos creados\n\n";

    // 6. MOSTRAR DATOS
    $pedido = $pedido->load(['prendas' => function ($q) {
        $q->with('procesos');
    }]);

    echo "═══════════════════════════════════════════════════════════════\n";
    echo "DATOS DEL PEDIDO\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";

    echo "📋 INFORMACIÓN:\n";
    echo "  • Pedido: #{$pedido->numero_pedido}\n";
    echo "  • Cliente: {$pedido->cliente}\n";
    echo "  • Asesora: {$pedido->asesora}\n";
    echo "  • Forma de Pago: {$pedido->forma_de_pago}\n";
    echo "  • Estado: {$pedido->estado}\n";
    echo "  • Fecha Creación: {$pedido->fecha_de_creacion_de_orden->format('d/m/Y')}\n";
    echo "  • Fecha Estimada: {$pedido->fecha_estimada_de_entrega->format('d/m/Y')}\n";
    echo "  • Día de Entrega: {$pedido->dia_de_entrega} días\n";
    echo "  • Área Actual: {$pedido->getAreaActual()}\n";
    echo "  • Novedades: {$pedido->novedades}\n\n";

    echo "👗 PRENDAS:\n";
    foreach ($pedido->prendas as $p) {
        echo "  • {$p->nombre_prenda} (Cantidad: {$p->cantidad})\n";
        echo "    Procesos:\n";
        foreach ($p->procesos as $proc) {
            echo "      - {$proc->proceso} ({$proc->encargado})\n";
        }
    }

    echo "\n✅ PRUEBA COMPLETADA EXITOSAMENTE\n";
    echo "═══════════════════════════════════════════════════════════════\n";

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
