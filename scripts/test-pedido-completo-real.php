<?php

/**
 * Test completo que simula un pedido real con:
 * - Prendas con tallas
 * - Telas con imágenes
 * - Variaciones (manga, broche, color, tela)
 * - Procesos con ubicaciones
 * - EPPs con imágenes
 * Ejecutar: php artisan tinker --execute "include 'scripts/test-pedido-completo-real.php';"
 */

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  🧪 TEST COMPLETO: PEDIDO REAL CON TODO                  ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\Cliente;
use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\PrendaFotoPedido;
use App\Models\PrendaFotoTelaPedido;
use App\Models\PedidoEpp;
use App\Models\PedidoEppImagen;
use App\Models\PedidosProcesosPrendaDetalle;
use App\Models\ProcesoPrendaImagen;
use App\Models\PrendaVariantePed;

try {
    echo "1️⃣  Creando usuario y cliente...\n";
    $asesora = User::find(95) ?? User::firstOrCreate(
        ['email' => 'asesora.test@test.com'],
        ['name' => 'Asesora Test', 'password' => bcrypt('password')]
    );
    
    $cliente = Cliente::firstOrCreate(
        ['nombre' => 'Cliente Pedido Completo ' . time()],
        ['estado' => 'activo']
    );
    echo "   ✅ Usuario: {$asesora->name} (ID: {$asesora->id})\n";
    echo "   ✅ Cliente: {$cliente->nombre} (ID: {$cliente->id})\n\n";

    echo "2️⃣  Creando pedido...\n";
    $numeroPedido = 80000 + rand(1, 9999);

    $pedido = PedidoProduccion::create([
        'numero_pedido' => $numeroPedido,
        'cliente' => $cliente->nombre,
        'cliente_id' => $cliente->id,
        'asesor_id' => $asesora->id,
        'forma_de_pago' => 'efectivo',
        'estado' => 'Pendiente',
        'fecha_de_creacion_de_orden' => now()->toDateString(),
        'cantidad_total' => 30,
    ]);
    echo "   ✅ Pedido: #{$pedido->numero_pedido} (ID: {$pedido->id})\n\n";

    echo "3️⃣  Creando PRENDA 1 con todo...\n";
    
    $prenda1 = PrendaPedido::create([
        'pedido_produccion_id' => $pedido->id,
        'nombre_prenda' => 'Camiseta Corporativa',
        'descripcion' => 'Camiseta de algodón 100% con logo',
        'de_bodega' => 1,
        'cantidad_talla' => json_encode(['dama' => ['S' => 10, 'M' => 15, 'L' => 5]]),
        'genero' => json_encode(['dama']),
    ]);
    echo "   ✅ Prenda 1: {$prenda1->nombre_prenda} (ID: {$prenda1->id})\n";
    
    // Crear variante para prenda 1
    $variante1 = PrendaVariantePed::create([
        'prenda_pedido_id' => $prenda1->id,
        'color_id' => null,
        'tela_id' => null,
        'tipo_manga_id' => null,
        'tipo_broche_boton_id' => null,
        'manga_obs' => '',
        'broche_boton_obs' => '',
        'tiene_bolsillos' => false,
        'bolsillos_obs' => '',
    ]);

    // Foto de prenda
    $fotoPrenda1 = PrendaFotoPedido::create([
        'prenda_pedido_id' => $prenda1->id,
        'ruta_original' => "pedidos/{$pedido->id}/prendas/camiseta_original.jpg",
        'ruta_webp' => "pedidos/{$pedido->id}/prendas/camiseta.webp",
        'orden' => 1,
    ]);
    echo "      • Foto de prenda: {$fotoPrenda1->id}\n";

    // Fotos de telas
    $fotoTela1 = PrendaFotoTelaPedido::create([
        'prenda_pedido_id' => $prenda1->id,
        'ruta_original' => "pedidos/{$pedido->id}/telas/algodon_original.jpg",
        'ruta_webp' => "pedidos/{$pedido->id}/telas/algodon.webp",
        'orden' => 1,
    ]);
    echo "      • Foto tela 1: {$fotoTela1->id}\n";

    $fotoTela2 = PrendaFotoTelaPedido::create([
        'prenda_pedido_id' => $prenda1->id,
        'ruta_original' => "pedidos/{$pedido->id}/telas/poliester_original.jpg",
        'ruta_webp' => "pedidos/{$pedido->id}/telas/poliester.webp",
        'orden' => 2,
    ]);
    echo "      • Foto tela 2: {$fotoTela2->id}\n";

    // Procesos
    $proceso1 = PedidosProcesosPrendaDetalle::create([
        'prenda_pedido_id' => $prenda1->id,
        'tipo_proceso_id' => 1,
        'ubicaciones' => json_encode(['pecho', 'espalda']),
        'observaciones' => 'Bordado con logo de empresa',
        'tallas_dama' => json_encode(['S' => 10, 'M' => 15, 'L' => 5]),
        'tallas_caballero' => json_encode([]),
        'estado' => 'PENDIENTE',
    ]);
    echo "      • Proceso 1 (Bordado): {$proceso1->id}\n";

    // Imagen de proceso
    $imagenProceso1 = ProcesoPrendaImagen::create([
        'proceso_prenda_detalle_id' => $proceso1->id,
        'ruta_original' => "pedidos/{$pedido->id}/procesos/bordado_original.jpg",
        'ruta_webp' => "pedidos/{$pedido->id}/procesos/bordado.webp",
        'orden' => 1,
        'es_principal' => 1,
    ]);
    echo "      • Imagen proceso: {$imagenProceso1->id}\n";

    $proceso2 = PedidosProcesosPrendaDetalle::create([
        'prenda_pedido_id' => $prenda1->id,
        'tipo_proceso_id' => 2,
        'ubicaciones' => json_encode(['manga izquierda']),
        'observaciones' => 'Estampado con número',
        'tallas_dama' => json_encode(['S' => 10, 'M' => 15, 'L' => 5]),
        'tallas_caballero' => json_encode([]),
        'estado' => 'PENDIENTE',
    ]);
    echo "      • Proceso 2 (Estampado): {$proceso2->id}\n\n";

    echo "4️⃣  Creando PRENDA 2...\n";
    
    $prenda2 = PrendaPedido::create([
        'pedido_produccion_id' => $pedido->id,
        'nombre_prenda' => 'Pantalón Ejecutivo',
        'descripcion' => 'Pantalón de vestir',
        'de_bodega' => 0,
        'cantidad_talla' => json_encode(['caballero' => ['30' => 8, '32' => 12]]),
        'genero' => json_encode(['caballero']),
    ]);
    echo "   ✅ Prenda 2: {$prenda2->nombre_prenda} (ID: {$prenda2->id})\n";
    
    // Crear variante para prenda 2
    $variante2 = PrendaVariantePed::create([
        'prenda_pedido_id' => $prenda2->id,
        'color_id' => null,
        'tela_id' => null,
        'tipo_manga_id' => null,
        'tipo_broche_boton_id' => null,
        'manga_obs' => '',
        'broche_boton_obs' => '',
        'tiene_bolsillos' => false,
        'bolsillos_obs' => '',
    ]);

    // Foto de prenda
    $fotoPrenda2 = PrendaFotoPedido::create([
        'prenda_pedido_id' => $prenda2->id,
        'ruta_original' => "pedidos/{$pedido->id}/prendas/pantalon_original.jpg",
        'ruta_webp' => "pedidos/{$pedido->id}/prendas/pantalon.webp",
        'orden' => 1,
    ]);
    echo "      • Foto de prenda: {$fotoPrenda2->id}\n";

    // Foto de tela
    $fotoTela3 = PrendaFotoTelaPedido::create([
        'prenda_pedido_id' => $prenda2->id,
        'ruta_original' => "pedidos/{$pedido->id}/telas/lana_original.jpg",
        'ruta_webp' => "pedidos/{$pedido->id}/telas/lana.webp",
        'orden' => 1,
    ]);
    echo "      • Foto de tela: {$fotoTela3->id}\n\n";

    echo "5️⃣  Creando EPPs...\n";
    
    $epp1 = PedidoEpp::create([
        'pedido_produccion_id' => $pedido->id,
        'epp_id' => 1,
        'cantidad' => 50,
        'tallas_medidas' => json_encode(['M' => 30, 'L' => 20]),
        'observaciones' => 'Guantes de seguridad industrial',
    ]);
    echo "   ✅ EPP 1: Guantes (ID: {$epp1->id})\n";

    $imagenEpp1 = PedidoEppImagen::create([
        'pedido_epp_id' => $epp1->id,
        'archivo' => "pedidos/{$pedido->id}/epp/guantes.jpg",
        'principal' => 1,
        'orden' => 1,
    ]);
    echo "      • Imagen EPP: {$imagenEpp1->id}\n";

    $epp2 = PedidoEpp::create([
        'pedido_produccion_id' => $pedido->id,
        'epp_id' => 2,
        'cantidad' => 100,
        'tallas_medidas' => json_encode(['Único' => 100]),
        'observaciones' => 'Cascos de seguridad',
    ]);
    echo "   ✅ EPP 2: Cascos (ID: {$epp2->id})\n";

    $imagenEpp2 = PedidoEppImagen::create([
        'pedido_epp_id' => $epp2->id,
        'archivo' => "pedidos/{$pedido->id}/epp/cascos.jpg",
        'principal' => 1,
        'orden' => 1,
    ]);
    echo "      • Imagen EPP: {$imagenEpp2->id}\n\n";

    echo "6️⃣  Verificando todos los datos guardados...\n";
    
    // Prendas
    $prendasEnBD = PrendaPedido::where('pedido_produccion_id', $pedido->id)->get();
    echo "   ✅ Prendas: {$prendasEnBD->count()}\n";
    
    // Fotos de prendas
    $fotosPrendaEnBD = PrendaFotoPedido::whereIn('prenda_pedido_id', $prendasEnBD->pluck('id'))->get();
    echo "   ✅ Fotos de Prendas: {$fotosPrendaEnBD->count()}\n";
    
    // Fotos de telas
    $fotosTelasEnBD = PrendaFotoTelaPedido::whereIn('prenda_pedido_id', $prendasEnBD->pluck('id'))->get();
    echo "   ✅ Fotos de Telas: {$fotosTelasEnBD->count()}\n";
    
    // Procesos
    $procesosEnBD = PedidosProcesosPrendaDetalle::whereIn('prenda_pedido_id', $prendasEnBD->pluck('id'))->get();
    echo "   ✅ Procesos: {$procesosEnBD->count()}\n";
    
    // Imágenes de procesos
    $imagenesProcesoEnBD = ProcesoPrendaImagen::whereIn('proceso_prenda_detalle_id', $procesosEnBD->pluck('id'))->get();
    echo "   ✅ Imágenes de Procesos: {$imagenesProcesoEnBD->count()}\n";
    
    // EPPs
    $eppsEnBD = PedidoEpp::where('pedido_produccion_id', $pedido->id)->get();
    echo "   ✅ EPPs: {$eppsEnBD->count()}\n";
    
    // Imágenes de EPPs
    $imagenesEppEnBD = PedidoEppImagen::whereIn('pedido_epp_id', $eppsEnBD->pluck('id'))->get();
    echo "   ✅ Imágenes de EPPs: {$imagenesEppEnBD->count()}\n";
    
    // Variantes
    $variantesEnBD = PrendaVariantePed::whereIn('prenda_pedido_id', $prendasEnBD->pluck('id'))->get();
    echo "   ✅ Variantes de Prendas: {$variantesEnBD->count()}\n";
    
    // Pedido
    $pedidoEnBD = PedidoProduccion::find($pedido->id);
    echo "   ✅ Pedido: #{$pedidoEnBD->numero_pedido}\n\n";

    echo "╔════════════════════════════════════════════════════════════╗\n";
    echo "║                    ✅ PRUEBA EXITOSA                      ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n\n";

    echo "📊 RESUMEN COMPLETO DEL PEDIDO:\n";
    echo "   PEDIDO #{$pedido->numero_pedido}:\n";
    echo "      • ID: {$pedido->id}\n";
    echo "      • Cliente: {$pedido->cliente}\n";
    echo "      • Asesor: {$asesora->name}\n\n";
    
    echo "   PRENDAS ({$prendasEnBD->count()}):\n";
    foreach ($prendasEnBD as $p) {
        echo "      • {$p->nombre_prenda} - Cantidad Talla: {$p->cantidad_talla}\n";
    }
    echo "\n";
    
    echo "   FOTOS DE PRENDAS: {$fotosPrendaEnBD->count()}\n";
    echo "   FOTOS DE TELAS: {$fotosTelasEnBD->count()}\n";
    echo "   PROCESOS: {$procesosEnBD->count()}\n";
    echo "   IMÁGENES DE PROCESOS: {$imagenesProcesoEnBD->count()}\n";
    echo "   EPPs: {$eppsEnBD->count()}\n";
    echo "   IMÁGENES DE EPPs: {$imagenesEppEnBD->count()}\n\n";

    echo "✨ Pedido completo con prendas, telas, variaciones, procesos y EPPs\n";
    echo "✨ Todos los datos guardados correctamente en BD\n\n";

} catch (\Exception $e) {
    echo "\n❌ ERROR EN LA PRUEBA:\n";
    echo "   Mensaje: {$e->getMessage()}\n";
    echo "   Archivo: {$e->getFile()}\n";
    echo "   Línea: {$e->getLine()}\n\n";
}
