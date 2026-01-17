#!/usr/bin/env php
<?php
/**
 * Script de Prueba: Verificar Guardado de Imágenes EPP
 * 
 * Uso: php verificar_imagenes_epp.php {numero_pedido}
 * 
 * Ejemplo:
 *   php verificar_imagenes_epp.php 90148
 */

// Cargar Laravel
require __DIR__ . '/bootstrap/app.php';

use Illuminate\Support\Facades\DB;
use App\Models\PedidoProduccion;
use App\Models\PedidoEpp;
use App\Models\PedidoEppImagen;

// Obtener número de pedido de argumentos
$numeroPedido = $argv[1] ?? null;

if (!$numeroPedido) {
    echo "❌ Debe proporcionar el número de pedido\n";
    echo "Uso: php verificar_imagenes_epp.php {numero_pedido}\n";
    exit(1);
}

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  VERIFICACIÓN: Imágenes EPP del Pedido #" . str_pad($numeroPedido, 5) . "                   ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

try {
    // 1. Buscar el pedido
    $pedido = PedidoProduccion::where('numero_pedido', $numeroPedido)->first();
    
    if (!$pedido) {
        echo "❌ Pedido #$numeroPedido NO encontrado\n";
        exit(1);
    }
    
    echo "✅ Pedido encontrado: #$numeroPedido (ID: {$pedido->id})\n";
    echo "   Fecha: " . $pedido->created_at->format('Y-m-d H:i:s') . "\n";
    echo "   Estado: " . ($pedido->estado ?? 'sin_estado') . "\n\n";
    
    // 2. Obtener EPPs del pedido
    $pedidosEpp = PedidoEpp::where('pedido_produccion_id', $pedido->id)
        ->with(['epp', 'imagenes'])
        ->get();
    
    if ($pedidosEpp->isEmpty()) {
        echo "⚠️  El pedido NO tiene EPP registrados\n";
        exit(0);
    }
    
    echo "📦 EPP Encontrados: " . $pedidosEpp->count() . "\n";
    echo "─────────────────────────────────────────────────────────────\n\n";
    
    // 3. Iterar EPPs y verificar imágenes
    foreach ($pedidosEpp as $idx => $pedidoEpp) {
        $numeroEpp = $idx + 1;
        
        echo "EPP #$numeroEpp:\n";
        echo "  ├─ ID: {$pedidoEpp->id}\n";
        echo "  ├─ Nombre: " . ($pedidoEpp->epp?->nombre ?? 'N/A') . "\n";
        echo "  ├─ Código: " . ($pedidoEpp->epp?->codigo ?? 'N/A') . "\n";
        echo "  ├─ Cantidad: {$pedidoEpp->cantidad}\n";
        echo "  ├─ Talla: " . ($pedidoEpp->tallas_medidas ?? 'N/A') . "\n";
        echo "  ├─ Observaciones: " . ($pedidoEpp->observaciones ?? 'Sin observaciones') . "\n";
        
        // Obtener imágenes
        $imagenes = $pedidoEpp->imagenes()
            ->orderBy('orden', 'asc')
            ->get();
        
        if ($imagenes->isEmpty()) {
            echo "  └─ 📷 Imágenes: NINGUNA\n";
        } else {
            echo "  └─ 📷 Imágenes: {$imagenes->count()}\n";
            
            foreach ($imagenes as $imgIdx => $imagen) {
                $esPrincipal = $imagen->principal ? '🌟' : '  ';
                $esUltima = $imgIdx === $imagenes->count() - 1;
                $prefix = $esUltima ? '      └─' : '      ├─';
                
                echo "$prefix $esPrincipal Imagen " . ($imgIdx + 1) . "\n";
                echo "         │  ID: {$imagen->id}\n";
                echo "         │  Archivo: {$imagen->archivo}\n";
                echo "         │  Principal: " . ($imagen->principal ? 'Sí' : 'No') . "\n";
                echo "         │  Orden: {$imagen->orden}\n";
                echo "         │  Guardada: " . $imagen->created_at->format('Y-m-d H:i:s') . "\n";
                
                // Verificar si el archivo existe
                $rutaCompleta = storage_path('app/' . $imagen->archivo);
                $existe = file_exists($rutaCompleta);
                $tamaño = $existe ? filesize($rutaCompleta) : null;
                
                if ($existe) {
                    $tamañoFormato = $tamaño > 1024 * 1024 
                        ? round($tamaño / (1024 * 1024), 2) . ' MB'
                        : round($tamaño / 1024, 2) . ' KB';
                    echo "         └─ ✅ Archivo existe ({$tamañoFormato})\n";
                } else {
                    echo "         └─ ❌ ARCHIVO NO EXISTE\n";
                }
            }
        }
        
        echo "\n";
    }
    
    // 4. Estadísticas finales
    $totalImagenes = PedidoEppImagen::whereIn(
        'pedido_epp_id',
        $pedidosEpp->pluck('id')
    )->count();
    
    echo "╔════════════════════════════════════════════════════════════════╗\n";
    echo "║  RESUMEN                                                       ║\n";
    echo "╠════════════════════════════════════════════════════════════════╣\n";
    echo "║  Total EPP: " . str_pad($pedidosEpp->count(), 3) . "                                   ║\n";
    echo "║  Total Imágenes: " . str_pad($totalImagenes, 3) . "                                ║\n";
    
    // Verificar imágenes sin archivo
    $sinArchivo = PedidoEppImagen::whereIn(
        'pedido_epp_id',
        $pedidosEpp->pluck('id')
    )
    ->where('archivo', null)
    ->count();
    
    if ($sinArchivo > 0) {
        echo "║  ⚠️  Imágenes sin ruta: " . str_pad($sinArchivo, 3) . "                         ║\n";
    }
    
    echo "╚════════════════════════════════════════════════════════════════╝\n\n";
    
    // 5. Query SQL para referencia
    echo "📋 QUERY SQL para consultar imágenes de este pedido:\n";
    echo "─────────────────────────────────────────────────────────────\n";
    echo "SELECT \n";
    echo "    pe.id as pedido_epp_id,\n";
    echo "    pe.cantidad,\n";
    echo "    e.nombre as epp_nombre,\n";
    echo "    pei.archivo,\n";
    echo "    pei.principal,\n";
    echo "    pei.orden\n";
    echo "FROM pedido_epp pe\n";
    echo "LEFT JOIN epp e ON pe.epp_id = e.id\n";
    echo "LEFT JOIN pedido_epp_imagenes pei ON pe.id = pei.pedido_epp_id\n";
    echo "WHERE pe.pedido_produccion_id = {$pedido->id}\n";
    echo "ORDER BY pe.id, pei.orden;\n";
    echo "\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "   Línea: " . $e->getLine() . "\n";
    echo "   Archivo: " . $e->getFile() . "\n";
    exit(1);
}

echo "✅ Verificación completada\n\n";
