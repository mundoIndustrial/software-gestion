<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n";
echo "═══════════════════════════════════════════════════════════════════════════════\n";
echo "                    VERIFICACIÓN DE IMÁGENES GUARDADAS                        \n";
echo "═══════════════════════════════════════════════════════════════════════════════\n\n";

// Pedido ID - cambiar si es necesario
$pedidoId = 5;

echo "Pedido ID: " . $pedidoId . "\n\n";

// ═════════════════════════════════════════════════════════════════════════════════
// 1. IMÁGENES DE PRENDAS (prenda_fotos_pedido)
// ═════════════════════════════════════════════════════════════════════════════════

echo "┌─────────────────────────────────────────────────────────────────────────────┐\n";
echo "│  1. IMÁGENES DE PRENDAS (prenda_fotos_pedido)                              │\n";
echo "└─────────────────────────────────────────────────────────────────────────────┘\n";

try {
    $prendaFotos = DB::table('prenda_fotos_pedido')
        ->join('prendas_pedido', 'prenda_fotos_pedido.prenda_pedido_id', '=', 'prendas_pedido.id')
        ->where('prendas_pedido.pedido_id', $pedidoId)
        ->select(
            'prenda_fotos_pedido.id',
            'prenda_fotos_pedido.prenda_pedido_id',
            'prenda_fotos_pedido.ruta_original',
            'prenda_fotos_pedido.ruta_webp',
            'prenda_fotos_pedido.orden',
            'prenda_fotos_pedido.created_at',
            'prendas_pedido.nombre as nombre_prenda'
        )
        ->orderBy('prenda_fotos_pedido.id')
        ->get();

    if ($prendaFotos->isEmpty()) {
        echo "❌ No hay imágenes de prendas registradas\n\n";
    } else {
        echo "✅ Imágenes de prendas encontradas: " . $prendaFotos->count() . "\n\n";
        foreach ($prendaFotos as $foto) {
            echo "   ID: {$foto->id}\n";
            echo "   Prenda: {$foto->nombre_prenda}\n";
            echo "   Prenda ID: {$foto->prenda_pedido_id}\n";
            echo "   Ruta Original: {$foto->ruta_original}\n";
            echo "   Ruta WebP: {$foto->ruta_webp}\n";
            echo "   Orden: {$foto->orden}\n";
            echo "   Creada: {$foto->created_at}\n";
            echo "   ────────────────────────────────────────────────────────────\n";
        }
    }
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n\n";
}

// ═════════════════════════════════════════════════════════════════════════════════
// 2. IMÁGENES DE TELAS (prenda_fotos_tela_pedido)
// ═════════════════════════════════════════════════════════════════════════════════

echo "\n┌─────────────────────────────────────────────────────────────────────────────┐\n";
echo "│  2. IMÁGENES DE TELAS (prenda_fotos_tela_pedido)                           │\n";
echo "└─────────────────────────────────────────────────────────────────────────────┘\n";

try {
    $telaFotos = DB::table('prenda_fotos_tela_pedido')
        ->join('prenda_pedido_colores_telas', 'prenda_fotos_tela_pedido.prenda_pedido_colores_telas_id', '=', 'prenda_pedido_colores_telas.id')
        ->join('prendas_pedido', 'prenda_pedido_colores_telas.prenda_pedido_id', '=', 'prendas_pedido.id')
        ->where('prendas_pedido.pedido_id', $pedidoId)
        ->select(
            'prenda_fotos_tela_pedido.id',
            'prenda_fotos_tela_pedido.prenda_pedido_colores_telas_id',
            'prenda_fotos_tela_pedido.ruta_original',
            'prenda_fotos_tela_pedido.ruta_webp',
            'prenda_fotos_tela_pedido.orden',
            'prenda_fotos_tela_pedido.created_at',
            'prendas_pedido.nombre as nombre_prenda',
            DB::raw("CONCAT(telas.nombre, ' - ', colores_prenda.nombre) as tela_color")
        )
        ->leftJoin('telas', 'prenda_pedido_colores_telas.tela_id', '=', 'telas.id')
        ->leftJoin('colores_prenda', 'prenda_pedido_colores_telas.color_id', '=', 'colores_prenda.id')
        ->orderBy('prenda_fotos_tela_pedido.id')
        ->get();

    if ($telaFotos->isEmpty()) {
        echo "❌ No hay imágenes de telas registradas\n\n";
    } else {
        echo "✅ Imágenes de telas encontradas: " . $telaFotos->count() . "\n\n";
        foreach ($telaFotos as $foto) {
            echo "   ID: {$foto->id}\n";
            echo "   Prenda: {$foto->nombre_prenda}\n";
            echo "   Tela-Color: {$foto->tela_color}\n";
            echo "   Tela Pedido ID: {$foto->prenda_pedido_colores_telas_id}\n";
            echo "   Ruta Original: {$foto->ruta_original}\n";
            echo "   Ruta WebP: {$foto->ruta_webp}\n";
            echo "   Orden: {$foto->orden}\n";
            echo "   Creada: {$foto->created_at}\n";
            echo "   ────────────────────────────────────────────────────────────\n";
        }
    }
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n\n";
}

// ═════════════════════════════════════════════════════════════════════════════════
// 3. IMÁGENES DE PROCESOS (pedidos_procesos_imagenes)
// ═════════════════════════════════════════════════════════════════════════════════

echo "\n┌─────────────────────────────────────────────────────────────────────────────┐\n";
echo "│  3. IMÁGENES DE PROCESOS (pedidos_procesos_imagenes)                       │\n";
echo "└─────────────────────────────────────────────────────────────────────────────┘\n";

try {
    $procesoImagenes = DB::table('pedidos_procesos_imagenes')
        ->join('pedidos_procesos_prenda_detalles', 'pedidos_procesos_imagenes.proceso_prenda_detalle_id', '=', 'pedidos_procesos_prenda_detalles.id')
        ->join('prendas_pedido', 'pedidos_procesos_prenda_detalles.prenda_pedido_id', '=', 'prendas_pedido.id')
        ->where('prendas_pedido.pedido_id', $pedidoId)
        ->select(
            'pedidos_procesos_imagenes.id',
            'pedidos_procesos_imagenes.proceso_prenda_detalle_id',
            'pedidos_procesos_imagenes.ruta_original',
            'pedidos_procesos_imagenes.ruta_webp',
            'pedidos_procesos_imagenes.orden',
            'pedidos_procesos_imagenes.es_principal',
            'pedidos_procesos_imagenes.created_at',
            'prendas_pedido.nombre as nombre_prenda',
            'pedidos_procesos_prenda_detalles.tipo as tipo_proceso'
        )
        ->orderBy('pedidos_procesos_imagenes.id')
        ->get();

    if ($procesoImagenes->isEmpty()) {
        echo "❌ No hay imágenes de procesos registradas\n\n";
    } else {
        echo "✅ Imágenes de procesos encontradas: " . $procesoImagenes->count() . "\n\n";
        foreach ($procesoImagenes as $foto) {
            $principal = $foto->es_principal ? '✓ PRINCIPAL' : 'Secundaria';
            echo "   ID: {$foto->id}\n";
            echo "   Prenda: {$foto->nombre_prenda}\n";
            echo "   Tipo Proceso: {$foto->tipo_proceso}\n";
            echo "   Proceso Detalle ID: {$foto->proceso_prenda_detalle_id}\n";
            echo "   Ruta Original: {$foto->ruta_original}\n";
            echo "   Ruta WebP: {$foto->ruta_webp}\n";
            echo "   Orden: {$foto->orden}\n";
            echo "   Es Principal: {$principal}\n";
            echo "   Creada: {$foto->created_at}\n";
            echo "   ────────────────────────────────────────────────────────────\n";
        }
    }
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n\n";
}

// ═════════════════════════════════════════════════════════════════════════════════
// RESUMEN FINAL
// ═════════════════════════════════════════════════════════════════════════════════

echo "\n┌─────────────────────────────────────────────────────────────────────────────┐\n";
echo "│  RESUMEN                                                                    │\n";
echo "└─────────────────────────────────────────────────────────────────────────────┘\n\n";

try {
    $totalPrendas = DB::table('prenda_fotos_pedido')
        ->join('prendas_pedido', 'prenda_fotos_pedido.prenda_pedido_id', '=', 'prendas_pedido.id')
        ->where('prendas_pedido.pedido_id', $pedidoId)
        ->count();

    $totalTelas = DB::table('prenda_fotos_tela_pedido')
        ->join('prenda_pedido_colores_telas', 'prenda_fotos_tela_pedido.prenda_pedido_colores_telas_id', '=', 'prenda_pedido_colores_telas.id')
        ->join('prendas_pedido', 'prenda_pedido_colores_telas.prenda_pedido_id', '=', 'prendas_pedido.id')
        ->where('prendas_pedido.pedido_id', $pedidoId)
        ->count();

    $totalProcesos = DB::table('pedidos_procesos_imagenes')
        ->join('pedidos_procesos_prenda_detalles', 'pedidos_procesos_imagenes.proceso_prenda_detalle_id', '=', 'pedidos_procesos_prenda_detalles.id')
        ->join('prendas_pedido', 'pedidos_procesos_prenda_detalles.prenda_pedido_id', '=', 'prendas_pedido.id')
        ->where('prendas_pedido.pedido_id', $pedidoId)
        ->count();

    $totalGeneral = $totalPrendas + $totalTelas + $totalProcesos;

    echo "📊 TOTALES DEL PEDIDO #" . $pedidoId . "\n";
    echo "───────────────────────────────────────────────────────────\n";
    echo "   Imágenes de Prendas:  " . $totalPrendas . "\n";
    echo "   Imágenes de Telas:    " . $totalTelas . "\n";
    echo "   Imágenes de Procesos: " . $totalProcesos . "\n";
    echo "   ───────────────────────────────────\n";
    echo "   TOTAL GENERAL:        " . $totalGeneral . "\n\n";

    if ($totalGeneral === 3) {
        echo "✅ ¡ÉXITO! Todas las imágenes fueron guardadas correctamente en las tablas.\n\n";
    } else if ($totalGeneral === 0) {
        echo "❌ No se registraron imágenes en las tablas.\n\n";
    } else {
        echo "⚠️  Se registraron " . $totalGeneral . " imágenes (se esperaban 3).\n\n";
    }

} catch (\Exception $e) {
    echo "❌ Error al calcular totales: " . $e->getMessage() . "\n\n";
}

echo "═══════════════════════════════════════════════════════════════════════════════\n\n";
?>
