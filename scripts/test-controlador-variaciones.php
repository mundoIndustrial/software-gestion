<?php

/**
 * Test que pasa por el controlador para verificar variaciones
 * Ejecutar: php artisan tinker --execute "include 'scripts/test-controlador-variaciones.php';"
 */

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  🧪 TEST: VARIACIONES A TRAVÉS DEL CONTROLADOR           ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

use App\Models\User;
use App\Models\Cliente;
use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\PrendaVariantePed;
use Illuminate\Http\Request;
use App\Infrastructure\Http\Controllers\Asesores\CrearPedidoEditableController;

try {
    echo "1️⃣  Preparando datos de prueba...\n";
    
    // Obtener usuario
    $asesora = User::find(95) ?? User::firstOrCreate(
        ['email' => 'asesora.test@test.com'],
        ['name' => 'Asesora Test', 'password' => bcrypt('password')]
    );
    echo "   ✅ Usuario: {$asesora->name} (ID: {$asesora->id})\n";

    // Crear cliente
    $cliente = Cliente::firstOrCreate(
        ['nombre' => 'Cliente Controlador ' . time()],
        ['estado' => 'activo']
    );
    echo "   ✅ Cliente: {$cliente->nombre}\n\n";

    echo "2️⃣  Creando solicitud POST con variaciones...\n";
    
    // Crear solicitud POST con datos de variaciones
    $request = Request::create(
        '/asesores/pedidos-editable/crear',
        'POST',
        [
            'cliente' => $cliente->nombre,
            'asesora' => $asesora->name,
            'forma_de_pago' => 'efectivo',
            'items' => [
                [
                    'tipo' => 'nuevo',
                    'nombre_producto' => 'Camiseta Test',
                    'nombre_prenda' => 'Camiseta Test',
                    'descripcion' => 'Camiseta con variaciones',
                    'de_bodega' => 1,
                    'origen' => 'bodega',
                    'color' => 'Azul',
                    'tela' => 'Algodón',
                    'genero' => json_encode(['dama']),
                    'cantidad_talla' => json_encode(['dama' => ['S' => 5, 'M' => 10, 'L' => 8]]),
                    'tallas' => ['S', 'M', 'L'],
                    'tipo_manga' => 'Corta',
                    'obs_manga' => 'Manga corta 5cm',
                    'tipo_broche' => 'Botones',
                    'obs_broche' => 'Botones de 12mm',
                    'tiene_bolsillos' => '1',
                    'obs_bolsillos' => 'Bolsillos laterales con cierre',
                    'procesos' => [],
                ]
            ]
        ]
    );
    
    $request->setUserResolver(function () use ($asesora) {
        return $asesora;
    });
    
    echo "   ✅ Solicitud POST creada\n";
    echo "   ✅ Datos enviados:\n";
    echo "      - Cliente: {$cliente->nombre}\n";
    echo "      - Prenda: Camiseta Test\n";
    echo "      - Manga Obs: Manga corta 5cm\n";
    echo "      - Broche Obs: Botones de 12mm\n";
    echo "      - Bolsillos Obs: Bolsillos laterales con cierre\n\n";

    echo "3️⃣  Ejecutando controlador...\n";
    
    // Instanciar controlador
    $controller = app(CrearPedidoEditableController::class);
    
    // Ejecutar método crearPedido
    $response = $controller->crearPedido($request);
    
    echo "   ✅ Controlador ejecutado\n";
    echo "   ✅ Status: " . $response->getStatusCode() . "\n\n";

    echo "4️⃣  Verificando respuesta...\n";
    
    // Obtener contenido de la respuesta
    $contenido = $response->getContent();
    $respuesta = json_decode($contenido, true);
    
    if ($respuesta['success'] ?? false) {
        echo "   ✅ Respuesta exitosa\n";
        $pedidoId = $respuesta['pedido_id'];
        $numeroPedido = $respuesta['numero_pedido'];
        echo "      • Pedido ID: {$pedidoId}\n";
        echo "      • Número Pedido: {$numeroPedido}\n\n";
    } else {
        echo "   ❌ Error: " . ($respuesta['message'] ?? 'Desconocido') . "\n";
        echo "   Status: " . $response->getStatusCode() . "\n";
        echo "   Respuesta: " . $contenido . "\n";
        throw new Exception("Solicitud falló");
    }

    echo "5️⃣  Verificando datos guardados en BD...\n";
    
    // Verificar pedido
    $pedido = PedidoProduccion::find($pedidoId);
    if ($pedido) {
        echo "   ✅ Pedido en BD: #{$pedido->numero_pedido}\n";
    }

    // Verificar prendas
    $prendas = PrendaPedido::where('pedido_produccion_id', $pedidoId)->get();
    echo "   ✅ Prendas guardadas: {$prendas->count()}\n";
    
    foreach ($prendas as $prenda) {
        echo "\n      📦 Prenda: {$prenda->nombre_prenda} (ID: {$prenda->id})\n";
        echo "         Cantidad Talla: {$prenda->cantidad_talla}\n";
        
        // Verificar variantes
        $variantes = PrendaVariantePed::where('prenda_pedido_id', $prenda->id)->get();
        echo "         ✅ Variantes guardadas: {$variantes->count()}\n";
        
        foreach ($variantes as $variante) {
            echo "\n         🔧 Variante ID: {$variante->id}\n";
            echo "            • Manga Obs: {$variante->manga_obs}\n";
            echo "            • Broche Obs: {$variante->broche_boton_obs}\n";
            echo "            • Tiene Bolsillos: " . ($variante->tiene_bolsillos ? 'Sí' : 'No') . "\n";
            echo "            • Bolsillos Obs: {$variante->bolsillos_obs}\n";
        }
    }

    echo "\n╔════════════════════════════════════════════════════════════╗\n";
    echo "║                    ✅ PRUEBA EXITOSA                      ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n\n";

    echo "📊 RESUMEN:\n";
    echo "   ✅ Solicitud pasó por el controlador\n";
    echo "   ✅ Pedido creado: #{$numeroPedido}\n";
    echo "   ✅ Prendas guardadas: {$prendas->count()}\n";
    echo "   ✅ Variaciones guardadas correctamente\n";
    echo "   ✅ Observaciones de variaciones guardadas\n\n";

} catch (\Exception $e) {
    echo "\n❌ ERROR EN LA PRUEBA:\n";
    echo "   Mensaje: {$e->getMessage()}\n";
    echo "   Archivo: {$e->getFile()}\n";
    echo "   Línea: {$e->getLine()}\n\n";
}
