<?php
/**
 * TEST: Verificar que las imágenes de prendas de cotización se guardan correctamente
 * 
 * FLUJO:
 * 1. Crear pedido desde cotización
 * 2. Verificar que se guardaron imágenes de prendas en prenda_fotos_pedido
 * 3. Verificar que tengan la ruta CORRECTA (NO camino /proceso/)
 */

// Cargar Laravel
require_once __DIR__ . '/bootstrap/app.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

echo "========================================\n";
echo "🔍 TEST: Imágenes de Prendas desde Cotización\n";
echo "========================================\n\n";

// Query: Obtener los pedidos más recientes y verificar sus imágenes
$pedidosRecientes = DB::table('pedidos_producciones')
    ->orderBy('created_at', 'desc')
    ->take(3)
    ->get(['id', 'numero_pedido', 'created_at']);

echo "📋 Últimos 3 pedidos creados:\n";
foreach ($pedidosRecientes as $pedido) {
    echo "  - Pedido ID: {$pedido->id}, Número: {$pedido->numero_pedido}\n";
    
    // Verificar prendas en este pedido
    $prendas = DB::table('prendas_pedidos')
        ->where('pedido_produccion_id', $pedido->id)
        ->get(['id', 'nombre_prenda']);
    
    echo "    Prendas: {$prendas->count()}\n";
    
    foreach ($prendas as $prenda) {
        // Verificar imágenes de PRENDA (NO procesos)
        $fotoPrenda = DB::table('prenda_fotos_pedido')
            ->where('prenda_pedido_id', $prenda->id)
            ->first(['id', 'ruta_webp', 'orden']);
        
        if ($fotoPrenda) {
            $esProcesoPath = strpos($fotoPrenda->ruta_webp, '/proceso/') !== false;
            $estado = $esProcesoPath ? '❌ INCORRECTO (RUTA DE PROCESO)' : '✅ CORRECTO (RUTA DE PRENDA)';
            echo "    • {$prenda->nombre_prenda} (ID: {$prenda->id})\n";
            echo "      └─ Imagen: {$fotoPrenda->ruta_webp}\n";
            echo "      └─ Estado: {$estado}\n\n";
        } else {
            echo "    • {$prenda->nombre_prenda} (ID: {$prenda->id})\n";
            echo "      └─ ⚠️  SIN IMÁGENES EN BD\n\n";
        }
    }
}

echo "\n========================================\n";
echo "✅ TEST COMPLETADO\n";
echo "========================================\n";
