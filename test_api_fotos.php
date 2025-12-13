<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Models\PedidoProduccion;

$numeroPedido = 45452;

echo "═════════════════════════════════════════════════\n";
echo "TEST: API /api/operario/pedido/{$numeroPedido}\n";
echo "═════════════════════════════════════════════════\n\n";

// Simular lo que hace getPedidoData
$pedido = PedidoProduccion::with(['asesora', 'prendas'])->where('numero_pedido', $numeroPedido)->first();

if (!$pedido) {
    echo "❌ Pedido no encontrado\n";
    exit;
}

echo "✅ Pedido encontrado:\n";
echo "  - ID: {$pedido->id}\n";
echo "  - Número: {$pedido->numero_pedido}\n";
echo "  - Cotización ID: {$pedido->cotizacion_id}\n";
echo "  - Cliente: {$pedido->cliente}\n\n";

// Simular lo que hace obtenerFotosPedido
echo "═════════════════════════════════════════════════\n";
echo "OBTENIENDO FOTOS\n";
echo "═════════════════════════════════════════════════\n\n";

// Paso 1: Obtener prendas cot
$prendasCot = \App\Models\PrendaCot::where('cotizacion_id', $pedido->cotizacion_id)->pluck('id')->toArray();
echo "Prendas encontradas para cotización {$pedido->cotizacion_id}:\n";
echo "  - IDs: " . json_encode($prendasCot) . "\n";
echo "  - Cantidad: " . count($prendasCot) . "\n\n";

if (empty($prendasCot)) {
    echo "⚠️ Sin prendas\n";
    exit;
}

// Paso 2: Obtener fotos
$fotosPrendas = \App\Models\PrendaFotoCot::whereIn('prenda_cot_id', $prendasCot)->orderBy('orden')->get();

echo "Fotos encontradas:\n";
echo "  - Cantidad: {$fotosPrendas->count()}\n";

if ($fotosPrendas->count() > 0) {
    $fotos = [];
    foreach($fotosPrendas as $foto) {
        $ruta = $foto->ruta_webp ?? $foto->ruta_original;
        if ($ruta) {
            $fotos[] = $ruta;
            echo "  - ✅ {$ruta}\n";
        }
    }
    
    echo "\n📦 JSON que se enviaría a la API:\n";
    echo json_encode([
        'numero_pedido' => $pedido->numero_pedido,
        'cliente' => $pedido->cliente,
        'asesora' => $pedido->asesora?->name ?? 'N/A',
        'forma_pago' => $pedido->forma_de_pago ?? 'N/A',
        'descripcion_prendas' => $pedido->descripcion_prendas ?? 'N/A',
        'fecha_creacion' => $pedido->fecha_creacion ?? '',
        'cantidad' => $pedido->cantidad ?? 0,
        'encargado' => 'Operario',
        'fotos' => $fotos,
        'prendas' => $pedido->prendas->map(function($prenda) {
            return [
                'nombre' => $prenda->nombre_prenda ?? '',
                'talla' => $prenda->talla ?? '',
                'cantidad' => $prenda->cantidad ?? 0,
                'descripcion' => $prenda->descripcion ?? ''
            ];
        })
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    
    echo "\n";
} else {
    echo "  ⚠️ Sin fotos\n";
}
