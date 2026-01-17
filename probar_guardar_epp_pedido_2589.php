<?php

/**
 * Script de prueba: Guardar EPP en el pedido 2589
 * 
 * Uso: php probar_guardar_epp_pedido_2589.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PedidoProduccion;
use App\Models\Epp;
use App\Services\PedidoEppService;

echo "\n╔════════════════════════════════════════════════╗\n";
echo "║  Guardando EPP en Pedido 2589                 ║\n";
echo "╚════════════════════════════════════════════════╝\n\n";

try {
    // Obtener el pedido 2589
    $pedido = PedidoProduccion::find(2589);
    if (!$pedido) {
        throw new Exception('❌ Pedido 2589 no encontrado');
    }
    echo "✅ Pedido encontrado: #{$pedido->numero_pedido}\n\n";

    // Obtener un EPP
    $epp = Epp::first();
    if (!$epp) {
        throw new Exception('❌ No hay EPP disponibles');
    }
    echo "✅ EPP encontrado: {$epp->nombre}\n\n";

    // Preparar datos
    $eppsData = [
        [
            'epp_id' => $epp->id,
            'cantidad' => 50,
            'tallas_medidas' => [
                'talla' => 'M',
                'color' => 'Azul',
                'medida' => '60cm'
            ],
            'observaciones' => 'EPP agregado en pedido 2589',
            'imagenes' => [
                [
                    'archivo' => '/storage/pedidos/2589/epp/imagen1.jpg',
                    'principal' => true,
                    'orden' => 0
                ]
            ]
        ]
    ];

    echo "📦 Datos a guardar:\n";
    echo "   - Pedido: 2589\n";
    echo "   - EPP: {$epp->nombre}\n";
    echo "   - Cantidad: 50\n";
    echo "   - Talla: M\n\n";

    // Guardar
    $service = new PedidoEppService();
    echo "💾 Guardando EPP...\n";
    $resultado = $service->guardarEppsDelPedido($pedido, $eppsData);

    if (empty($resultado)) {
        throw new Exception('❌ No se guardó el EPP');
    }

    $pedidoEpp = $resultado[0];
    
    echo "\n✅ EPP guardado exitosamente!\n";
    echo "   - ID: {$pedidoEpp->id}\n";
    echo "   - Cantidad: {$pedidoEpp->cantidad}\n";
    echo "   - Talla: {$pedidoEpp->tallas_medidas['talla']}\n\n";

    // Verificar en BD
    $verificacion = \App\Models\PedidoEpp::find($pedidoEpp->id);
    if (!$verificacion) {
        throw new Exception('❌ EPP no se guardó en BD');
    }

    echo "╔════════════════════════════════════════════════╗\n";
    echo "║           ✅ EPP GUARDADO CORRECTAMENTE       ║\n";
    echo "║                                                ║\n";
    echo "║  Pedido: 2589                                 ║\n";
    echo "║  EPP ID: {$pedidoEpp->id}                                  ║\n";
    echo "║  Cantidad: 50                                 ║\n";
    echo "╚════════════════════════════════════════════════╝\n\n";

} catch (Exception $e) {
    echo "\n❌ ERROR: {$e->getMessage()}\n\n";
    exit(1);
}
