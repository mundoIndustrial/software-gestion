<?php

require __DIR__ . '/../bootstrap/app.php';

use Illuminate\Support\Facades\DB;

class TestPedidoSimple
{
    public function ejecutar()
    {
        echo "\n";
        echo "╔════════════════════════════════════════════════════════════╗\n";
        echo "║        🧪 PRUEBA SIMPLE DE CREACIÓN DE PEDIDO             ║\n";
        echo "╚════════════════════════════════════════════════════════════╝\n\n";

        try {
            // 1. Crear cliente
            echo "1️⃣  Creando cliente...\n";
            $cliente = DB::table('clientes')->insertGetId([
                'nombre' => 'Cliente Test ' . time(),
                'estado' => 'activo',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            echo "   ✅ Cliente creado (ID: {$cliente})\n\n";

            // 2. Crear pedido
            echo "2️⃣  Creando pedido...\n";
            $numeroPedido = DB::table('numero_secuencias')
                ->where('tipo', 'pedido_produccion')
                ->value('siguiente') ?? 45709;

            $pedidoId = DB::table('pedidos_produccion')->insertGetId([
                'numero_pedido' => $numeroPedido,
                'cliente' => 'Cliente Test',
                'cliente_id' => $cliente,
                'asesor_id' => 1,
                'forma_de_pago' => 'efectivo',
                'estado' => 'pendiente',
                'fecha_de_creacion_de_orden' => now(),
                'cantidad_total' => 50,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            echo "   ✅ Pedido creado: #{$numeroPedido} (ID: {$pedidoId})\n\n";

            // 3. Crear prenda
            echo "3️⃣  Creando prenda...\n";
            $prendaId = DB::table('prendas_pedido')->insertGetId([
                'pedido_produccion_id' => $pedidoId,
                'nombre_producto' => 'Camiseta Test',
                'descripcion' => 'Camiseta de prueba',
                'de_bodega' => 1,
                'origen' => 'bodega',
                'cantidad_talla' => json_encode(['dama-S' => 10, 'dama-M' => 15]),
                'estado' => 'pendiente',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            echo "   ✅ Prenda creada (ID: {$prendaId})\n\n";

            // 4. Verificar datos
            echo "4️⃣  Verificando datos guardados...\n";
            $pedido = DB::table('pedidos_produccion')->find($pedidoId);
            $prenda = DB::table('prendas_pedido')->find($prendaId);
            
            echo "   ✅ Pedido en BD:\n";
            echo "      • Número: {$pedido->numero_pedido}\n";
            echo "      • Cliente: {$pedido->cliente}\n";
            echo "      • Estado: {$pedido->estado}\n";
            
            echo "\n   ✅ Prenda en BD:\n";
            echo "      • Nombre: {$prenda->nombre_producto}\n";
            echo "      • Cantidad talla: {$prenda->cantidad_talla}\n";
            echo "      • Estado: {$prenda->estado}\n\n";

            // 5. Resumen
            echo "╔════════════════════════════════════════════════════════════╗\n";
            echo "║                    ✅ PRUEBA EXITOSA                      ║\n";
            echo "╚════════════════════════════════════════════════════════════╝\n\n";

            echo "📊 DATOS GUARDADOS:\n";
            echo "   • Pedido ID: {$pedidoId}\n";
            echo "   • Número Pedido: {$numeroPedido}\n";
            echo "   • Prenda ID: {$prendaId}\n";
            echo "   • Cliente ID: {$cliente}\n\n";

            echo "✨ Los datos se guardaron correctamente en la base de datos\n\n";

        } catch (\Exception $e) {
            echo "\n❌ ERROR EN LA PRUEBA:\n";
            echo "   Mensaje: {$e->getMessage()}\n";
            echo "   Archivo: {$e->getFile()}\n";
            echo "   Línea: {$e->getLine()}\n\n";
        }
    }
}

$test = new TestPedidoSimple();
$test->ejecutar();
