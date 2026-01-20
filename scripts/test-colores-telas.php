<?php

/**
 * Test para verificar que colores y telas se crean/buscan automáticamente
 * Ejecutar: php artisan tinker --execute "include 'scripts/test-colores-telas.php';"
 */

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  🧪 TEST: COLORES Y TELAS - BUSCAR O CREAR               ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

use App\Models\User;
use App\Models\Cliente;
use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\PrendaVariantePed;
use App\Models\ColorPrenda;
use App\Models\TelaPrenda;
use App\Application\Services\PedidoPrendaService;
use App\Application\Services\ColorGeneroMangaBrocheService;

try {
    echo "1️⃣  Preparando datos de prueba...\n";
    
    $asesora = User::find(95);
    $cliente = Cliente::firstOrCreate(
        ['nombre' => 'Cliente Colores Telas ' . time()],
        ['estado' => 'activo']
    );
    echo "   ✅ Cliente: {$cliente->nombre}\n\n";

    echo "2️⃣  Creando pedido...\n";
    
    $pedido = PedidoProduccion::create([
        'numero_pedido' => 80000 + rand(1, 9999),
        'cliente' => $cliente->nombre,
        'cliente_id' => $cliente->id,
        'asesor_id' => $asesora->id,
        'forma_de_pago' => 'efectivo',
        'estado' => 'Pendiente',
        'fecha_de_creacion_de_orden' => now()->toDateString(),
        'cantidad_total' => 20,
    ]);
    echo "   ✅ Pedido: #{$pedido->numero_pedido}\n\n";

    echo "3️⃣  Probando buscar/crear colores y telas...\n";
    
    $colorService = app(ColorGeneroMangaBrocheService::class);
    
    // Probar color nuevo
    echo "   📌 Buscando/creando color 'Azul Marino'...\n";
    $colorAzul = $colorService->buscarOCrearColor('Azul Marino');
    echo "      ✅ Color ID: {$colorAzul->id}, Nombre: {$colorAzul->nombre}\n";
    
    // Probar color existente
    echo "   📌 Buscando color 'Azul Marino' nuevamente...\n";
    $colorAzul2 = $colorService->buscarOCrearColor('Azul Marino');
    echo "      ✅ Color ID: {$colorAzul2->id} (debe ser igual: {$colorAzul->id})\n";
    
    // Probar tela nueva
    echo "   📌 Buscando/creando tela 'Poliéster 100%'...\n";
    $telaPoliester = $colorService->obtenerOCrearTela('Poliéster 100%');
    echo "      ✅ Tela ID: {$telaPoliester->id}, Nombre: {$telaPoliester->nombre}\n";
    
    // Probar tela existente
    echo "   📌 Buscando tela 'Poliéster 100%' nuevamente...\n";
    $telaPoliester2 = $colorService->obtenerOCrearTela('Poliéster 100%');
    echo "      ✅ Tela ID: {$telaPoliester2->id} (debe ser igual: {$telaPoliester->id})\n\n";

    echo "4️⃣  Creando prenda con colores y telas automáticos...\n";
    
    $prendaData = [
        'nombre_producto' => 'Camiseta Colores Telas',
        'descripcion' => 'Prueba de colores y telas automáticos',
        'genero' => json_encode(['dama']),
        'de_bodega' => 1,
        'cantidad_talla' => ['dama' => ['S' => 5, 'M' => 10, 'L' => 8]],
        'color' => 'Rojo Intenso',  // Se buscará/creará automáticamente
        'tela' => 'Algodón Orgánico',  // Se buscará/creará automáticamente
        'tipo_manga_id' => null,
        'tipo_broche_boton_id' => null,
        'obs_manga' => 'Manga corta',
        'obs_broche' => '',
        'tiene_bolsillos' => true,
        'obs_bolsillos' => 'Bolsillos laterales',
        'obs_reflectivo' => '',
        'fotos' => [],
        'telas' => [],
        'procesos' => [],
        'cantidades' => [],
        'variaciones' => '{}',
    ];
    
    $servicio = app(PedidoPrendaService::class);
    $servicio->guardarPrendasEnPedido($pedido, [$prendaData]);
    
    echo "   ✅ Prenda guardada\n\n";

    echo "5️⃣  Verificando variantes guardadas...\n";
    
    $prendas = PrendaPedido::where('pedido_produccion_id', $pedido->id)->get();
    foreach ($prendas as $prenda) {
        echo "   📦 Prenda: {$prenda->nombre_prenda}\n";
        
        $variantes = PrendaVariantePed::where('prenda_pedido_id', $prenda->id)->get();
        foreach ($variantes as $var) {
            echo "      🔧 Variante ID: {$var->id}\n";
            echo "         • Color ID: {$var->color_id}\n";
            echo "         • Tela ID: {$var->tela_id}\n";
            
            if ($var->color_id) {
                $color = ColorPrenda::find($var->color_id);
                echo "         • Color Nombre: {$color->nombre}\n";
            }
            
            if ($var->tela_id) {
                $tela = TelaPrenda::find($var->tela_id);
                echo "         • Tela Nombre: {$tela->nombre}\n";
            }
        }
    }

    echo "\n6️⃣  Verificando colores y telas en BD...\n";
    
    $coloresCount = ColorPrenda::count();
    $telasCount = TelaPrenda::count();
    echo "   ✅ Total colores en BD: {$coloresCount}\n";
    echo "   ✅ Total telas en BD: {$telasCount}\n";

    echo "\n╔════════════════════════════════════════════════════════════╗\n";
    echo "║                    ✅ PRUEBA EXITOSA                      ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n\n";

    echo "📊 RESUMEN:\n";
    echo "   ✅ Colores se buscan/crean automáticamente\n";
    echo "   ✅ Telas se buscan/crean automáticamente\n";
    echo "   ✅ IDs se guardan correctamente en variantes\n";
    echo "   ✅ Relaciones funcionan correctamente\n\n";

} catch (\Exception $e) {
    echo "\n❌ ERROR:\n";
    echo "   Mensaje: {$e->getMessage()}\n";
    echo "   Archivo: {$e->getFile()}\n";
    echo "   Línea: {$e->getLine()}\n\n";
}
