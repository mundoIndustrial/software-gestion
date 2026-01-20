<?php

/**
 * Test que simula exactamente lo que el frontend envía
 * Basado en los logs del pedido #45719
 * Ejecutar: php artisan tinker --execute "include 'scripts/test-frontend-real.php';"
 */

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  🧪 TEST: SIMULANDO DATOS REALES DEL FRONTEND            ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

use App\Models\User;
use App\Models\Cliente;
use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\PrendaVariantePed;
use Illuminate\Http\Request;
use App\Infrastructure\Http\Controllers\Asesores\CrearPedidoEditableController;

try {
    echo "1️⃣  Preparando datos como los envía el frontend...\n";
    
    $asesora = User::find(92) ?? User::firstOrCreate(
        ['email' => 'asesor.real@test.com'],
        ['name' => 'Asesor Real', 'password' => bcrypt('password')]
    );
    echo "   ✅ Usuario: {$asesora->name}\n";

    $cliente = Cliente::firstOrCreate(
        ['nombre' => 'Cliente Frontend ' . time()],
        ['estado' => 'activo']
    );
    echo "   ✅ Cliente: {$cliente->nombre}\n\n";

    echo "2️⃣  Creando request con datos del frontend...\n";
    
    // Datos exactamente como los envía el frontend
    $requestData = [
        'cliente' => $cliente->nombre,
        'asesora' => $asesora->name,
        'forma_de_pago' => 'efectivo',
        'items' => [
            [
                'tipo' => 'nuevo',
                'nombre_producto' => 'YRTYrt',  // Exacto del log
                'descripcion' => 'YRTYTR',       // Exacto del log
                'de_bodega' => 1,
                'origen' => 'bodega',
                'color' => '',                   // Vacío como en el log
                'tela' => '',                    // Vacío como en el log
                'genero' => json_encode(['dama']),
                'cantidad_talla' => json_encode(['dama' => ['L' => 2, 'M' => 30]]),  // Exacto del log
                'tallas' => ['L', 'M'],
                'tipo_manga' => '',
                'obs_manga' => '',
                'tipo_broche' => '',
                'obs_broche' => '',
                'tiene_bolsillos' => '0',
                'obs_bolsillos' => '',
                'procesos' => [],
                'telas' => [],
                'fotos' => [],
            ]
        ]
    ];
    
    $request = Request::create(
        '/asesores/pedidos-editable/crear',
        'POST',
        $requestData
    );
    
    $request->setUserResolver(function () use ($asesora) {
        return $asesora;
    });
    
    echo "   ✅ Request creado con datos del frontend\n\n";

    echo "3️⃣  Ejecutando controlador...\n";
    
    $controller = app(CrearPedidoEditableController::class);
    $response = $controller->crearPedido($request);
    
    $contenido = $response->getContent();
    $respuesta = json_decode($contenido, true);
    
    if ($respuesta['success'] ?? false) {
        echo "   ✅ Pedido creado: #{$respuesta['numero_pedido']}\n";
        $pedidoId = $respuesta['pedido_id'];
    } else {
        echo "   ❌ Error: " . ($respuesta['message'] ?? 'Desconocido') . "\n";
        echo "   Errores: " . json_encode($respuesta['errores'] ?? []) . "\n";
        throw new Exception("Solicitud falló");
    }

    echo "\n4️⃣  Verificando datos guardados...\n";
    
    $prendas = PrendaPedido::where('pedido_produccion_id', $pedidoId)->get();
    echo "   ✅ Prendas: {$prendas->count()}\n";
    
    foreach ($prendas as $prenda) {
        echo "\n      📦 Prenda: {$prenda->nombre_prenda}\n";
        echo "         • Descripción: {$prenda->descripcion}\n";
        echo "         • Cantidad Talla: {$prenda->cantidad_talla}\n";
        echo "         • Género: {$prenda->genero}\n";
        
        $variantes = PrendaVariantePed::where('prenda_pedido_id', $prenda->id)->get();
        echo "         • Variantes: {$variantes->count()}\n";
        
        foreach ($variantes as $var) {
            echo "            🔧 Color ID: " . ($var->color_id ?: 'NULL') . "\n";
            echo "            🔧 Tela ID: " . ($var->tela_id ?: 'NULL') . "\n";
            echo "            🔧 Manga Obs: " . ($var->manga_obs ?: 'VACÍO') . "\n";
        }
    }

    echo "\n╔════════════════════════════════════════════════════════════╗\n";
    echo "║                    ✅ PRUEBA COMPLETADA                    ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n\n";

} catch (\Exception $e) {
    echo "\n❌ ERROR:\n";
    echo "   Mensaje: {$e->getMessage()}\n";
    echo "   Archivo: {$e->getFile()}\n";
    echo "   Línea: {$e->getLine()}\n\n";
}
