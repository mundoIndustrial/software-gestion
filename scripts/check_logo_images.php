<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Buscar el logo_pedido
$logoPedido = DB::table('logo_pedidos')
    ->where('numero_pedido', '#LOGO-00106')
    ->first(['id', 'numero_pedido', 'cliente']);

echo "LogoPedido encontrado:\n";
echo json_encode($logoPedido, JSON_PRETTY_PRINT);
echo "\n\n";

if ($logoPedido) {
    // Buscar imágenes asociadas
    $imagenes = DB::table('logo_pedido_imagenes')
        ->where('logo_pedido_id', $logoPedido->id)
        ->get(['id', 'logo_pedido_id', 'url', 'ruta_webp', 'ruta_original', 'orden']);
    
    echo "Imágenes encontradas en logo_pedido_imagenes:\n";
    echo json_encode($imagenes->toArray(), JSON_PRETTY_PRINT);
    echo "\nTotal: " . $imagenes->count() . "\n";
}
