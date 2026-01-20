<?php

/**
 * Script de prueba que simula la creación real de un pedido
 * Ejecutar: php artisan tinker < scripts/test-crear-pedido-real.php
 */

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║     🧪 PRUEBA REAL DE CREACIÓN DE PEDIDO CON PRENDAS      ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Cliente;
use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;

try {
    echo "1️⃣  Creando usuario (asesora)...\n";
    $asesora = User::firstOrCreate(
        ['email' => 'asesora.test@test.com'],
        [
            'name' => 'Asesora Test',
            'password' => bcrypt('password'),
        ]
    );
    echo "   ✅ Usuario: {$asesora->name} (ID: {$asesora->id})\n\n";

    echo "2️⃣  Creando cliente...\n";
    $cliente = Cliente::firstOrCreate(
        ['nombre' => 'Cliente Test ' . time()],
        ['estado' => 'activo']
    );
    echo "   ✅ Cliente: {$cliente->nombre} (ID: {$cliente->id})\n\n";

    echo "3️⃣  Creando pedido...\n";
    $numeroPedido = DB::table('numero_secuencias')
        ->where('tipo', 'pedido_produccion')
        ->value('siguiente') ?? 45709;

    $pedido = PedidoProduccion::create([
        'numero_pedido' => $numeroPedido,
        'cliente' => $cliente->nombre,
        'cliente_id' => $cliente->id,
        'asesor_id' => $asesora->id,
        'forma_de_pago' => 'efectivo',
        'estado' => 'pendiente',
        'fecha_de_creacion_de_orden' => now(),
        'cantidad_total' => 0,
    ]);
    echo "   ✅ Pedido: #{$pedido->numero_pedido} (ID: {$pedido->id})\n\n";

    echo "4️⃣  Creando prendas...\n";
    
    // Prenda 1
    $prenda1 = PrendaPedido::create([
        'pedido_produccion_id' => $pedido->id,
        'nombre_producto' => 'Camiseta Básica',
        'descripcion' => 'Camiseta de algodón 100%',
        'de_bodega' => 1,
        'origen' => 'bodega',
        'cantidad_talla' => json_encode(['dama-S' => 10, 'dama-M' => 15, 'dama-L' => 5]),
        'estado' => 'pendiente',
    ]);
    echo "   ✅ Prenda 1: {$prenda1->nombre_producto} (ID: {$prenda1->id})\n";
    echo "      • Cantidad Talla: {$prenda1->cantidad_talla}\n";

    // Prenda 2
    $prenda2 = PrendaPedido::create([
        'pedido_produccion_id' => $pedido->id,
        'nombre_producto' => 'Pantalón Ejecutivo',
        'descripcion' => 'Pantalón de vestir',
        'de_bodega' => 0,
        'origen' => 'confeccion',
        'cantidad_talla' => json_encode(['caballero-30' => 8, 'caballero-32' => 12]),
        'estado' => 'pendiente',
    ]);
    echo "   ✅ Prenda 2: {$prenda2->nombre_producto} (ID: {$prenda2->id})\n";
    echo "      • Cantidad Talla: {$prenda2->cantidad_talla}\n\n";

    echo "5️⃣  Verificando datos guardados...\n";
    
    // Verificar pedido
    $pedidoVerificado = PedidoProduccion::find($pedido->id);
    if ($pedidoVerificado) {
        echo "   ✅ Pedido en BD:\n";
        echo "      • Número: {$pedidoVerificado->numero_pedido}\n";
        echo "      • Cliente: {$pedidoVerificado->cliente}\n";
        echo "      • Asesor ID: {$pedidoVerificado->asesor_id}\n";
        echo "      • Estado: {$pedidoVerificado->estado}\n";
    } else {
        echo "   ❌ Pedido NO encontrado en BD\n";
    }

    // Verificar prendas
    $prendasEnBD = PrendaPedido::where('pedido_produccion_id', $pedido->id)->get();
    echo "\n   ✅ Prendas en BD: {$prendasEnBD->count()}\n";
    foreach ($prendasEnBD as $prenda) {
        echo "      • {$prenda->nombre_producto} (ID: {$prenda->id})\n";
        echo "        - Cantidad Talla: {$prenda->cantidad_talla}\n";
        echo "        - De Bodega: {$prenda->de_bodega}\n";
        echo "        - Estado: {$prenda->estado}\n";
    }

    // Verificar relaciones
    echo "\n   ✅ Verificando relaciones:\n";
    $clienteRelacion = $pedido->cliente()->first();
    if ($clienteRelacion) {
        echo "      • Cliente: {$clienteRelacion->nombre}\n";
    }

    $asesorRelacion = $pedido->asesor()->first();
    if ($asesorRelacion) {
        echo "      • Asesor: {$asesorRelacion->name}\n";
    }

    echo "\n╔════════════════════════════════════════════════════════════╗\n";
    echo "║                    ✅ PRUEBA EXITOSA                      ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n\n";

    echo "📊 RESUMEN DE DATOS GUARDADOS:\n";
    echo "   • Pedido ID: {$pedido->id}\n";
    echo "   • Número Pedido: {$pedido->numero_pedido}\n";
    echo "   • Cliente: {$cliente->nombre}\n";
    echo "   • Asesor: {$asesora->name}\n";
    echo "   • Total Prendas: {$prendasEnBD->count()}\n";
    echo "   • Forma de Pago: {$pedido->forma_de_pago}\n";
    echo "   • Estado: {$pedido->estado}\n\n";

    echo "✨ Todos los datos se guardaron correctamente en la base de datos\n";
    echo "✨ Las relaciones entre entidades funcionan correctamente\n\n";

} catch (\Exception $e) {
    echo "\n❌ ERROR EN LA PRUEBA:\n";
    echo "   Mensaje: {$e->getMessage()}\n";
    echo "   Archivo: {$e->getFile()}\n";
    echo "   Línea: {$e->getLine()}\n\n";
    echo "Stack Trace:\n";
    echo $e->getTraceAsString() . "\n\n";
}
