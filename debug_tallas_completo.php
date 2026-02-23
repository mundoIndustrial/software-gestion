<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Verificar datos de tallas para el pedido 8
echo "=== VERIFICACIÓN DE TALLAS PEDIDO 8 ===" . PHP_EOL;

$pedido = \App\Models\PedidoProduccion::where('numero_pedido', 8)->first();
if ($pedido) {
    $prendas = $pedido->prendas;
    echo 'Pedido: ' . $pedido->numero_pedido . PHP_EOL;
    echo 'Total prendas: ' . $prendas->count() . PHP_EOL;
    
    foreach ($prendas as $index => $prenda) {
        echo PHP_EOL . '--- PRENDA ' . ($index + 1) . ' ---' . PHP_EOL;
        echo 'ID: ' . $prenda->id . PHP_EOL;
        echo 'Nombre: ' . $prenda->nombre_prenda . PHP_EOL;
        
        // Verificar tallas desde relación
        $tallas = $prenda->tallas;
        echo 'Tallas (relación): ' . ($tallas ? $tallas->count() : 0) . PHP_EOL;
        if ($tallas) {
            foreach ($tallas as $talla) {
                echo '  - ' . $talla->talla . ': ' . $talla->cantidad . PHP_EOL;
            }
        }
        
        // Verificar tabla directa
        $tallasDirectas = \DB::table('prenda_pedido_tallas')
            ->where('prenda_pedido_id', $prenda->id)
            ->get();
        echo 'Tallas (directo): ' . $tallasDirectas->count() . PHP_EOL;
        foreach ($tallasDirectas as $talla) {
            echo '  - ' . $talla->talla . ': ' . $talla->cantidad . PHP_EOL;
        }
        
        // Verificar cantidad_talla JSON
        echo 'cantidad_talla JSON: ' . ($prenda->cantidad_talla ?: 'NULL') . PHP_EOL;
        if ($prenda->cantidad_talla) {
            $tallasJson = json_decode($prenda->cantidad_talla, true);
            if ($tallasJson) {
                echo 'Tallas desde JSON:' . PHP_EOL;
                foreach ($tallasJson as $talla => $cantidad) {
                    echo '  - ' . $talla . ': ' . $cantidad . PHP_EOL;
                }
            }
        }
    }
} else {
    echo 'Pedido 8 no encontrado' . PHP_EOL;
}

echo PHP_EOL . "=== VERIFICACIÓN DE DESCRIPCIÓN ===" . PHP_EOL;

// Verificar descripción del pedido
if ($pedido) {
    echo 'descripcion_prendas: ' . ($pedido->descripcion_prendas ?: 'NULL') . PHP_EOL;
    
    // Verificar método buildDescripcionConTallas
    $controller = new \App\Infrastructure\Http\Controllers\RegistroOrdenQueryController(
        app(\App\Services\RegistroOrdenExtendedQueryService::class),
        app(\App\Services\RegistroOrdenSearchExtendedService::class),
        app(\App\Services\RegistroOrdenFilterExtendedService::class),
        app(\App\Services\RegistroOrdenTransformService::class),
        app(\App\Services\RegistroOrdenProcessService::class),
        app(\App\Services\RegistroOrdenStatsService::class),
        app(\App\Services\RegistroOrdenProcessesService::class),
        app(\App\Services\RegistroOrdenEnumService::class)
    );
    
    // Cargar prendas con relaciones
    $prendasConRelaciones = \App\Models\PrendaPedido::where('pedido_produccion_id', $pedido->id)
        ->with(['fotos', 'tallas', 'procesos.tipoProceso', 'procesos.imagenes'])
        ->orderBy('id', 'asc')
        ->get();
    
    $pedido->setRelation('prendas', $prendasConRelaciones);
    
    $descripcion = $controller->buildDescripcionConTallas($pedido);
    echo PHP_EOL . 'Descripción generada:' . PHP_EOL;
    echo $descripcion . PHP_EOL;
}
