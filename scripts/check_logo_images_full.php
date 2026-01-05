<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$logoPedido = DB::table('logo_pedidos')
    ->where('numero_pedido', '#LOGO-00106')
    ->first(['id', 'numero_pedido', 'pedido_id', 'logo_cotizacion_id', 'cliente']);

echo "=== LogoPedido ===\n";
echo json_encode($logoPedido, JSON_PRETTY_PRINT);
echo "\n\n";

if ($logoPedido) {
    // Verificar si hay pedido_produccion asociado
    if ($logoPedido->pedido_id) {
        $numeroPedido = DB::table('pedidos_produccion')
            ->where('id', $logoPedido->pedido_id)
            ->value('numero_pedido');
        
        echo "=== Buscando prendas del pedido_produccion (numero_pedido: {$numeroPedido}) ===\n";
        $prendas = DB::table('prendas_pedido')
            ->where('numero_pedido', $numeroPedido)
            ->get(['id', 'numero_pedido', 'nombre_prenda']);
        
        echo "Prendas encontradas: " . $prendas->count() . "\n";
        
        foreach ($prendas as $prenda) {
            // Buscar fotos de logo en prenda_fotos_logo_pedido
            $fotosLogo = DB::table('prenda_fotos_logo_pedido')
                ->where('prenda_pedido_id', $prenda->id)
                ->get(['id', 'prenda_pedido_id', 'ruta_original', 'ruta_webp', 'ubicacion']);
            
            if ($fotosLogo->count() > 0) {
                echo "\n  Prenda ID {$prenda->id} ({$prenda->nombre_prenda}): {$fotosLogo->count()} fotos de logo\n";
                foreach ($fotosLogo as $foto) {
                    echo "    - Ubicación: {$foto->ubicacion}, Ruta: {$foto->ruta_webp}\n";
                }
            }
        }
    }
    
    // Verificar si hay logo_cotizacion asociada
    if ($logoPedido->logo_cotizacion_id) {
        echo "\n=== Buscando fotos en logo_cotizacion (ID: {$logoPedido->logo_cotizacion_id}) ===\n";
        $fotosLogoCot = DB::table('logo_cotizacion_fotos')
            ->where('logo_cotizacion_id', $logoPedido->logo_cotizacion_id)
            ->get(['id', 'logo_cotizacion_id', 'url', 'ruta_webp', 'ubicacion', 'orden']);
        
        echo "Fotos encontradas: " . $fotosLogoCot->count() . "\n";
        foreach ($fotosLogoCot as $foto) {
            echo "  - Orden {$foto->orden}: {$foto->ubicacion} -> {$foto->ruta_webp}\n";
        }
    }
}
