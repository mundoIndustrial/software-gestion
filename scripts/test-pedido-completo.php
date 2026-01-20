<?php

/**
 * Script de prueba completo que crea un pedido con:
 * - Prendas con variantes, fotos y procesos
 * - EPPs con imágenes
 * Ejecutar: php artisan tinker --execute "include 'scripts/test-pedido-completo.php';"
 */

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║   🧪 PRUEBA COMPLETA: PEDIDO CON PRENDAS Y EPPs          ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Cliente;
use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\PrendaVariantePed;
use App\Models\PrendaFotoPedido;
use App\Models\PrendaFotoTelaPedido;
use App\Models\PedidoEpp;
use App\Models\PedidoEppImagen;
use App\Models\PedidosProcesosPrendaDetalle;
use App\Models\PedidosProcessosImagen;

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
    
    // Generar número de pedido único
    $numeroPedido = 50000 + rand(1, 9999);

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

    echo "3️⃣  Creando prendas con variantes...\n";
    
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

    // Variante de Prenda 1
    $variante1 = PrendaVariantePed::create([
        'prenda_pedido_id' => $prenda1->id,
        'color_id' => 1,
        'tela_id' => 1,
        'tipo_manga_id' => 1,
        'tipo_broche_boton_id' => 1,
        'manga_obs' => 'Manga corta',
        'broche_boton_obs' => 'Botones de madera',
        'tiene_bolsillos' => 1,
        'bolsillos_obs' => 'Bolsillos laterales',
    ]);
    echo "      • Variante creada (ID: {$variante1->id})\n";

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
    echo "      • Foto de tela creada (ID: {$fotoTela1->id})\n";

    // Proceso para prenda 1
    $proceso1 = PedidosProcesosPrendaDetalle::create([
        'prenda_pedido_id' => $prenda1->id,
        'tipo_proceso_id' => 1,
        'ubicaciones' => json_encode(['pecho', 'espalda']),
        'observaciones' => 'Bordado en pecho',
        'tallas_dama' => json_encode(['S' => 10, 'M' => 15, 'L' => 5]),
        'tallas_caballero' => json_encode([]),
        'estado' => 'PENDIENTE',
    ]);
    echo "      • Proceso creado (ID: {$proceso1->id})\n";

    // Imagen de proceso
    $imagenProceso1 = PedidosProcessosImagen::create([
        'proceso_prenda_detalle_id' => $proceso1->id,
        'ruta_original' => 'storage/pedidos/' . $pedido->id . '/procesos/bordado_original.jpg',
        'ruta_webp' => 'storage/pedidos/' . $pedido->id . '/procesos/bordado.webp',
        'orden' => 1,
        'es_principal' => 1,
    ]);
    echo "      • Imagen de proceso creada (ID: {$imagenProceso1->id})\n\n";

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

    echo "5️⃣  Verificando datos guardados...\n";
    
    // Verificar prendas
    $prendasEnBD = PrendaPedido::where('pedido_produccion_id', $pedido->id)->get();
    echo "   ✅ Prendas: {$prendasEnBD->count()}\n";
    
    // Verificar variantes
    $variantesEnBD = PrendaPedidoVariante::whereIn('prenda_pedido_id', $prendasEnBD->pluck('id'))->get();
    echo "   ✅ Variantes: {$variantesEnBD->count()}\n";
    
    // Verificar fotos de prenda
    $fotosPrendaEnBD = PrendaFotoPedido::whereIn('prenda_pedido_id', $prendasEnBD->pluck('id'))->get();
    echo "   ✅ Fotos de Prenda: {$fotosPrendaEnBD->count()}\n";
    
    // Verificar fotos de tela
    $fotosTelasEnBD = PrendaFotoTelaPedido::whereIn('prenda_pedido_id', $prendasEnBD->pluck('id'))->get();
    echo "   ✅ Fotos de Tela: {$fotosTelasEnBD->count()}\n";
    
    // Verificar procesos
    $procesosEnBD = PedidosProcesosPrendaDetalle::whereIn('prenda_pedido_id', $prendasEnBD->pluck('id'))->get();
    echo "   ✅ Procesos: {$procesosEnBD->count()}\n";
    
    // Verificar imágenes de procesos
    $imagenesProcesoEnBD = PedidosProcessosImagen::whereIn('proceso_prenda_detalle_id', $procesosEnBD->pluck('id'))->get();
    echo "   ✅ Imágenes de Procesos: {$imagenesProcesoEnBD->count()}\n";
    
    // Verificar EPPs
    $eppsEnBD = PedidoEpp::where('pedido_produccion_id', $pedido->id)->get();
    echo "   ✅ EPPs: {$eppsEnBD->count()}\n";
    
    // Verificar imágenes de EPPs
    $imagenesEppEnBD = PedidoEppImagen::whereIn('pedido_epp_id', $eppsEnBD->pluck('id'))->get();
    echo "   ✅ Imágenes de EPPs: {$imagenesEppEnBD->count()}\n\n";

    echo "╔════════════════════════════════════════════════════════════╗\n";
    echo "║                    ✅ PRUEBA EXITOSA                      ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n\n";

    echo "📊 RESUMEN COMPLETO:\n";
    echo "   PEDIDO:\n";
    echo "      • ID: {$pedido->id}\n";
    echo "      • Número: {$pedido->numero_pedido}\n";
    echo "      • Cliente: {$pedido->cliente}\n";
    echo "      • Asesor: {$asesora->name}\n\n";
    
    echo "   PRENDAS:\n";
    echo "      • Total: {$prendasEnBD->count()}\n";
    echo "      • Variantes: {$variantesEnBD->count()}\n";
    echo "      • Fotos de Prenda: {$fotosPrendaEnBD->count()}\n";
    echo "      • Fotos de Tela: {$fotosTelasEnBD->count()}\n";
    echo "      • Procesos: {$procesosEnBD->count()}\n";
    echo "      • Imágenes de Procesos: {$imagenesProcesoEnBD->count()}\n\n";
    
    echo "   EPPs:\n";
    echo "      • Total: {$eppsEnBD->count()}\n";
    echo "      • Imágenes de EPPs: {$imagenesEppEnBD->count()}\n\n";

    echo "✨ Todos los datos se guardaron correctamente en todas las tablas\n";
    echo "✨ Prendas, variantes, fotos, procesos e imágenes guardadas\n";
    echo "✨ EPPs e imágenes de EPPs guardadas\n\n";

} catch (\Exception $e) {
    echo "\n❌ ERROR EN LA PRUEBA:\n";
    echo "   Mensaje: {$e->getMessage()}\n";
    echo "   Archivo: {$e->getFile()}\n";
    echo "   Línea: {$e->getLine()}\n\n";
    echo "Stack Trace:\n";
    echo $e->getTraceAsString() . "\n\n";
}
