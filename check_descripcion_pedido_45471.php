<?php
/**
 * Script para verificar la descripción del pedido 45471
 * Ejecutar desde: php check_descripcion_pedido_45471.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PedidoProduccion;

echo "\n========================================\n";
echo "VERIFICANDO DESCRIPCIÓN DEL PEDIDO 45471\n";
echo "========================================\n\n";

try {
    // Buscar el pedido
    $pedido = PedidoProduccion::where('numero_pedido', '45471')
        ->with([
            'prendas' => function ($q) {
                $q->with(['color', 'tela', 'tipoManga']);
            }
        ])
        ->first();

    if (!$pedido) {
        echo "❌ Pedido 45471 no encontrado\n";
        exit(1);
    }

    // Información del pedido
    echo "✅ PEDIDO ENCONTRADO\n";
    echo "─────────────────────────────────────────\n";
    echo "Número: {$pedido->numero_pedido}\n";
    echo "Cliente: {$pedido->cliente}\n";
    echo "Total Prendas: {$pedido->prendas->count()}\n";
    echo "Cantidad Total: {$pedido->cantidad_total}\n";
    echo "Estado: {$pedido->estado}\n";
    echo "Forma de Pago: {$pedido->forma_de_pago}\n";
    echo "─────────────────────────────────────────\n\n";

    // Información de cada prenda
    foreach ($pedido->prendas as $index => $prenda) {
        echo "📌 PRENDA " . ($index + 1) . "\n";
        echo "   Nombre: {$prenda->nombre_prenda}\n";
        
        if ($prenda->relationLoaded('color') && $prenda->color) {
            echo "   Color: {$prenda->color->nombre}\n";
        } elseif ($prenda->color_id) {
            echo "   Color ID: {$prenda->color_id} (sin cargar)\n";
        }
        
        if ($prenda->relationLoaded('tela') && $prenda->tela) {
            echo "   Tela: {$prenda->tela->nombre} (REF: {$prenda->tela->referencia})\n";
        } elseif ($prenda->tela_id) {
            echo "   Tela ID: {$prenda->tela_id} (sin cargar)\n";
        }
        
        if ($prenda->relationLoaded('tipoManga') && $prenda->tipoManga) {
            echo "   Manga: {$prenda->tipoManga->nombre}\n";
        } elseif ($prenda->tipo_manga_id) {
            echo "   Manga ID: {$prenda->tipo_manga_id} (sin cargar)\n";
        }
        
        echo "   Cantidad: {$prenda->cantidad}\n";
        echo "   Tallas: " . json_encode($prenda->cantidad_talla) . "\n";
        
        if ($prenda->descripcion) {
            $desc = substr($prenda->descripcion, 0, 80);
            echo "   Descripción: {$desc}...\n";
        }
        
        if ($prenda->descripcion_variaciones) {
            $var = substr($prenda->descripcion_variaciones, 0, 80);
            echo "   Variaciones: {$var}...\n";
        }
        echo "\n";
    }

    // Obtener y mostrar descripción generada
    $descripcion = $pedido->descripcion_prendas;
    
    echo "========================================\n";
    echo "✅ DESCRIPCIÓN GENERADA:\n";
    echo "========================================\n";
    echo $descripcion;
    echo "\n========================================\n\n";

} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
    exit(1);
}

echo "✅ Test completado exitosamente\n";
