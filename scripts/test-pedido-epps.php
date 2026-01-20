<?php

/**
 * Script de prueba que crea un pedido completo con prendas y EPPs
 * Ejecutar: php artisan tinker --execute "include 'scripts/test-pedido-epps.php';"
 */

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║   🧪 PRUEBA: PEDIDO CON PRENDAS Y EPPs GUARDADOS          ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Cliente;
use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\PrendaFotoPedido;
use App\Models\PrendaFotoTelaPedido;
use App\Models\PedidoEpp;
use App\Models\PedidoEppImagen;

try {
    echo "1️⃣  Creando usuario y cliente...\n";
    $asesora = User::firstOrCreate(
        ['email' => 'asesora.test@test.com'],
        ['name' => 'Asesora Test', 'password' => bcrypt('password')]
    );
    
    $cliente = Cliente::firstOrCreate(
        ['nombre' => 'Cliente Test ' . time()],
        ['estado' => 'activo']
    );
    echo "   ✅ Usuario: {$asesora->name} (ID: {$asesora->id})\n";
    echo "   ✅ Cliente: {$cliente->nombre} (ID: {$cliente->id})\n\n";

    echo "2️⃣  Creando pedido...\n";
    $numeroPedido = 60000 + rand(1, 9999);

    $pedido = PedidoProduccion::create([
        'numero_pedido' => $numeroPedido,
        'cliente' => $cliente->nombre,
        'cliente_id' => $cliente->id,
        'asesor_id' => $asesora->id,
        'forma_de_pago' => 'efectivo',
        'estado' => 'Pendiente',
        'fecha_de_creacion_de_orden' => now()->toDateString(),
        'cantidad_total' => 0,
    ]);
    echo "   ✅ Pedido: #{$pedido->numero_pedido} (ID: {$pedido->id})\n\n";

    echo "3️⃣  Creando prendas...\n";
    
    // Prenda 1
    $prenda1 = PrendaPedido::create([
        'pedido_produccion_id' => $pedido->id,
        'nombre_prenda' => 'Camiseta Básica',
        'descripcion' => 'Camiseta de algodón 100%',
        'de_bodega' => 1,
        'cantidad_talla' => json_encode(['dama-S' => 10, 'dama-M' => 15, 'dama-L' => 5]),
        'genero' => 'dama',
    ]);
    echo "   ✅ Prenda 1: {$prenda1->nombre_prenda} (ID: {$prenda1->id})\n";

    // Foto de prenda 1
    $fotoPrenda1 = PrendaFotoPedido::create([
        'prenda_pedido_id' => $prenda1->id,
        'ruta_original' => 'storage/pedidos/' . $pedido->id . '/prendas/camiseta_original.jpg',
        'ruta_webp' => 'storage/pedidos/' . $pedido->id . '/prendas/camiseta.webp',
        'orden' => 1,
    ]);
    echo "      • Foto de prenda creada (ID: {$fotoPrenda1->id})\n";

    // Foto de tela para prenda 1
    $fotoTela1 = PrendaFotoTelaPedido::create([
        'prenda_pedido_id' => $prenda1->id,
        'ruta_original' => 'storage/pedidos/' . $pedido->id . '/telas/algodon_original.jpg',
        'ruta_webp' => 'storage/pedidos/' . $pedido->id . '/telas/algodon.webp',
        'orden' => 1,
    ]);
    echo "      • Foto de tela creada (ID: {$fotoTela1->id})\n\n";

    echo "4️⃣  Creando EPPs...\n";
    
    // EPP 1
    $epp1 = PedidoEpp::create([
        'pedido_produccion_id' => $pedido->id,
        'epp_id' => 1,
        'cantidad' => 50,
        'tallas_medidas' => json_encode(['M' => 30, 'L' => 20]),
        'observaciones' => 'Guantes de seguridad industrial',
    ]);
    echo "   ✅ EPP 1 creado (ID: {$epp1->id})\n";

    // Imagen de EPP 1
    $imagenEpp1 = PedidoEppImagen::create([
        'pedido_epp_id' => $epp1->id,
        'archivo' => 'storage/pedidos/' . $pedido->id . '/epp/guantes_original.jpg',
        'principal' => 1,
        'orden' => 1,
    ]);
    echo "      • Imagen de EPP creada (ID: {$imagenEpp1->id})\n";

    // EPP 2
    $epp2 = PedidoEpp::create([
        'pedido_produccion_id' => $pedido->id,
        'epp_id' => 2,
        'cantidad' => 100,
        'tallas_medidas' => json_encode(['Único' => 100]),
        'observaciones' => 'Cascos de seguridad',
    ]);
    echo "   ✅ EPP 2 creado (ID: {$epp2->id})\n";

    // Imagen de EPP 2
    $imagenEpp2 = PedidoEppImagen::create([
        'pedido_epp_id' => $epp2->id,
        'archivo' => 'storage/pedidos/' . $pedido->id . '/epp/cascos_original.jpg',
        'principal' => 1,
        'orden' => 1,
    ]);
    echo "      • Imagen de EPP creada (ID: {$imagenEpp2->id})\n\n";

    echo "5️⃣  Verificando datos guardados en BD...\n";
    
    // Verificar prendas
    $prendasEnBD = PrendaPedido::where('pedido_produccion_id', $pedido->id)->get();
    echo "   ✅ Tabla prendas_pedido: {$prendasEnBD->count()} registros\n";
    
    // Verificar fotos de prenda
    $fotosPrendaEnBD = PrendaFotoPedido::whereIn('prenda_pedido_id', $prendasEnBD->pluck('id'))->get();
    echo "   ✅ Tabla prenda_fotos_pedido: {$fotosPrendaEnBD->count()} registros\n";
    
    // Verificar fotos de tela
    $fotosTelasEnBD = PrendaFotoTelaPedido::whereIn('prenda_pedido_id', $prendasEnBD->pluck('id'))->get();
    echo "   ✅ Tabla prenda_fotos_tela_pedido: {$fotosTelasEnBD->count()} registros\n";
    
    // Verificar EPPs
    $eppsEnBD = PedidoEpp::where('pedido_produccion_id', $pedido->id)->get();
    echo "   ✅ Tabla pedido_epp: {$eppsEnBD->count()} registros\n";
    
    // Verificar imágenes de EPPs
    $imagenesEppEnBD = PedidoEppImagen::whereIn('pedido_epp_id', $eppsEnBD->pluck('id'))->get();
    echo "   ✅ Tabla pedido_epp_imagenes: {$imagenesEppEnBD->count()} registros\n";

    // Verificar pedido
    $pedidoEnBD = PedidoProduccion::find($pedido->id);
    echo "   ✅ Tabla pedidos_produccion: Pedido #{$pedidoEnBD->numero_pedido}\n\n";

    echo "╔════════════════════════════════════════════════════════════╗\n";
    echo "║                    ✅ PRUEBA EXITOSA                      ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n\n";

    echo "📊 RESUMEN COMPLETO DE DATOS GUARDADOS:\n";
    echo "   PEDIDO (pedidos_produccion):\n";
    echo "      • ID: {$pedido->id}\n";
    echo "      • Número: {$pedido->numero_pedido}\n";
    echo "      • Cliente: {$pedido->cliente}\n";
    echo "      • Asesor: {$asesora->name}\n\n";
    
    echo "   PRENDAS (prendas_pedido):\n";
    echo "      • Total: {$prendasEnBD->count()}\n";
    foreach ($prendasEnBD as $p) {
        echo "        - {$p->nombre_prenda} (ID: {$p->id})\n";
    }
    echo "\n";
    
    echo "   FOTOS DE PRENDAS (prenda_fotos_pedido):\n";
    echo "      • Total: {$fotosPrendaEnBD->count()}\n\n";
    
    echo "   FOTOS DE TELAS (prenda_fotos_tela_pedido):\n";
    echo "      • Total: {$fotosTelasEnBD->count()}\n\n";
    
    echo "   EPPs (pedido_epp):\n";
    echo "      • Total: {$eppsEnBD->count()}\n";
    foreach ($eppsEnBD as $e) {
        echo "        - EPP ID: {$e->epp_id}, Cantidad: {$e->cantidad}\n";
    }
    echo "\n";
    
    echo "   IMÁGENES DE EPPs (pedido_epp_imagenes):\n";
    echo "      • Total: {$imagenesEppEnBD->count()}\n\n";

    echo "✨ Todos los datos se guardaron correctamente en las tablas\n";
    echo "✨ Prendas con fotos guardadas\n";
    echo "✨ EPPs con imágenes guardadas\n\n";

} catch (\Exception $e) {
    echo "\n❌ ERROR EN LA PRUEBA:\n";
    echo "   Mensaje: {$e->getMessage()}\n";
    echo "   Archivo: {$e->getFile()}\n";
    echo "   Línea: {$e->getLine()}\n\n";
}
