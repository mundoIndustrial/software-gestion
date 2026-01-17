<?php

/**
 * ✅ TEST COMPLETO: Crear pedido con EPP + Prenda Nueva (múltiples géneros)
 * 
 * Simula exactamente lo que el frontend envía:
 * - 1 EPP con talla y cantidad
 * - 1 Prenda Nueva con múltiples géneros (dama + caballero)
 */

require_once __DIR__ . '/bootstrap/app.php';

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Usuario;
use App\Models\Cliente;
use App\Models\PedidoProduccion;

try {
    echo "\n========================================\n";
    echo "🧪 TEST: Crear Pedido con EPP + Prenda Nueva\n";
    echo "========================================\n";

    // 1️⃣ AUTENTICARSE COMO USUARIO
    echo "\n1️⃣ Autenticando como usuario...\n";
    $usuario = Usuario::find(3);
    if (!$usuario) {
        die("❌ Usuario con ID 3 no encontrado\n");
    }
    Auth::login($usuario);
    echo "✅ Autenticado como: {$usuario->nombre}\n";

    // 2️⃣ OBTENER O CREAR CLIENTE
    echo "\n2️⃣ Buscando cliente...\n";
    $cliente = Cliente::where('nombre', 'Cliente Test EPP')->first();
    if (!$cliente) {
        $cliente = Cliente::create([
            'nombre' => 'Cliente Test EPP',
            'email' => 'test-epp@test.com',
            'telefono' => '5555555',
        ]);
        echo "✅ Cliente creado: {$cliente->nombre}\n";
    } else {
        echo "✅ Cliente encontrado: {$cliente->nombre}\n";
    }

    // 3️⃣ PREPARAR DATOS DEL PEDIDO (exactamente como viene del frontend)
    echo "\n3️⃣ Preparando datos del pedido...\n";
    
    $pedidoData = [
        'cliente' => $cliente->nombre,
        'asesora' => $usuario->nombre,
        'forma_de_pago' => 'contado',
        'items' => [
            // ✅ ITEM 0: EPP
            [
                'tipo' => 'epp',
                'epp_id' => 1,
                'nombre' => 'Casco de Seguridad ABS Amarillo',
                'codigo' => 'EPP-CAB-001',
                'categoria' => 'CABEZA',
                'talla' => 'M',
                'cantidad' => 5,
                'observaciones' => 'Test observación EPP',
                'tallas_medidas' => 'M',
                'imagenes' => [],
            ],
            // ✅ ITEM 1: Prenda Nueva con múltiples géneros
            [
                'tipo' => 'prenda_nueva',
                'prenda' => 'Camiseta Test',
                'descripcion' => 'Camiseta de prueba unisex',
                'origen' => 'bodega',
                'cantidad_talla' => [
                    'dama-S' => 10,
                    'dama-M' => 15,
                    'dama-L' => 20,
                    'caballero-S' => 8,
                    'caballero-M' => 12,
                    'caballero-L' => 18,
                ],
                'color' => 'Azul',
                'color_id' => null,
                'tela' => 'Algodón',
                'tela_id' => null,
                'genero' => 'unisex',
                'variaciones' => [
                    'manga' => [
                        'tipo' => 'corta',
                        'obs' => 'Manga corta',
                    ],
                    'bolsillos' => [
                        'tiene' => true,
                        'obs' => 'Bolsillos frontales',
                    ],
                    'broche' => [
                        'tipo' => 'boton',
                        'obs' => 'Botones de 2 agujeros',
                    ],
                    'reflectivo' => [
                        'tiene' => false,
                        'obs' => '',
                    ],
                ],
                'imagenes' => [],
                'procesos' => [],
                'telas' => [],
            ]
        ]
    ];

    echo "✅ Datos preparados:\n";
    echo "   - EPP: {$pedidoData['items'][0]['nombre']} x{$pedidoData['items'][0]['cantidad']}\n";
    echo "   - Prenda: {$pedidoData['items'][1]['prenda']}\n";
    echo "   - Tallas prenda: " . count($pedidoData['items'][1]['cantidad_talla']) . " combinaciones\n";

    // 4️⃣ SIMULAR VALIDACIÓN (como lo hace el frontend)
    echo "\n4️⃣ Validando datos...\n";
    
    $errores = [];
    foreach ($pedidoData['items'] as $index => $item) {
        $itemNum = $index + 1;
        $tipo = $item['tipo'] ?? 'prenda';
        
        if ($tipo === 'epp') {
            if (empty($item['epp_id'])) {
                $errores[] = "Ítem {$itemNum} (EPP): ID del EPP no especificado";
            }
            if (empty($item['cantidad']) || $item['cantidad'] <= 0) {
                $errores[] = "Ítem {$itemNum} (EPP): Cantidad debe ser mayor a 0";
            }
            if (empty($item['talla'])) {
                $errores[] = "Ítem {$itemNum} (EPP): Talla/medida no especificada";
            }
        } else {
            if (empty($item['prenda'])) {
                $errores[] = "Ítem {$itemNum}: Prenda no especificada";
            }
            if (empty($item['cantidad_talla']) || !is_array($item['cantidad_talla']) || count($item['cantidad_talla']) === 0) {
                $errores[] = "Ítem {$itemNum}: Debe especificar cantidades por talla";
            }
        }
    }
    
    if (!empty($errores)) {
        echo "❌ Errores de validación:\n";
        foreach ($errores as $error) {
            echo "   - {$error}\n";
        }
        die("\n");
    }
    
    echo "✅ Validación exitosa\n";

    // 5️⃣ USAR EL CONTROLADOR PARA CREAR EL PEDIDO
    echo "\n5️⃣ Creando pedido en base de datos...\n";
    
    $app = app();
    $container = $app->make('Illuminate\Contracts\Container\Container');
    
    // Llamar al controlador
    $controller = $app->make(\App\Http\Controllers\Asesores\CrearPedidoEditableController::class);
    
    // Crear un request simulado
    $request = new \Illuminate\Http\Request();
    $request->merge($pedidoData);
    $request->setUserResolver(function () use ($usuario) {
        return $usuario;
    });
    
    try {
        $response = $controller->crearPedido($request);
        $responseData = json_decode($response->getContent(), true);
        
        if ($responseData['success'] ?? false) {
            echo "✅ Pedido creado exitosamente\n";
            echo "   Número de pedido: {$responseData['numero_pedido'] ?? 'N/A'}\n";
            echo "   ID: {$responseData['pedido_id'] ?? 'N/A'}\n";
            
            // 6️⃣ VERIFICAR EN BD
            echo "\n6️⃣ Verificando en base de datos...\n";
            
            $pedidoId = $responseData['pedido_id'] ?? null;
            if ($pedidoId) {
                $pedido = PedidoProduccion::find($pedidoId);
                if ($pedido) {
                    echo "✅ Pedido encontrado en BD\n";
                    echo "   Número: {$pedido->numero_pedido}\n";
                    echo "   Cliente: {$pedido->cliente}\n";
                    echo "   Estado: {$pedido->estado}\n";
                    
                    // Verificar EPP
                    echo "\n   📊 Verificando EPP...\n";
                    $epps = $pedido->epps()->get();
                    echo "   Total EPP: " . $epps->count() . "\n";
                    foreach ($epps as $epp) {
                        echo "     - {$epp->nombre} (Talla: {$epp->talla}, Cantidad: {$epp->cantidad})\n";
                    }
                    
                    // Verificar Prendas
                    echo "\n   📊 Verificando Prendas...\n";
                    $prendas = $pedido->prendas()->get();
                    echo "   Total Prendas: " . $prendas->count() . "\n";
                    foreach ($prendas as $prenda) {
                        echo "     - {$prenda->nombre_prenda}\n";
                        
                        // Verificar variantes
                        $variantes = $prenda->variantes()->get();
                        echo "       Variantes: " . $variantes->count() . "\n";
                        foreach ($variantes as $var) {
                            echo "         • Talla: {$var->talla}, Cantidad: {$var->cantidad}\n";
                        }
                        
                        if ($variantes->count() === 0) {
                            echo "         ⚠️ SIN VARIANTES\n";
                        }
                    }
                    
                    echo "\n✅ TEST EXITOSO\n";
                } else {
                    echo "❌ Pedido no encontrado en BD\n";
                }
            } else {
                echo "❌ No se obtuvo ID del pedido\n";
            }
        } else {
            echo "❌ Error al crear pedido:\n";
            echo "   {$responseData['message'] ?? 'Error desconocido'}\n";
            if (isset($responseData['errores'])) {
                foreach ((array)$responseData['errores'] as $error) {
                    echo "   - {$error}\n";
                }
            }
        }
    } catch (\Exception $e) {
        echo "❌ Excepción al crear pedido:\n";
        echo "   {$e->getMessage()}\n";
        echo "\nStack trace:\n";
        echo $e->getTraceAsString() . "\n";
    }

} catch (\Exception $e) {
    echo "❌ Error general:\n";
    echo "   {$e->getMessage()}\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n========================================\n";
?>
