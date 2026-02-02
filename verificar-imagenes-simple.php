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

// Pedido ID
$pedidoId = 5;

echo "Pedido ID: " . $pedidoId . "\n\n";

// ═════════════════════════════════════════════════════════════════════════════════
// VERIFICAR QUE EL PEDIDO EXISTE
// ═════════════════════════════════════════════════════════════════════════════════

try {
    $pedido = DB::table('pedidos_produccion')
        ->where('id', $pedidoId)
        ->first();

    if (!$pedido) {
        echo "❌ Pedido ID " . $pedidoId . " no existe\n\n";
        exit;
    }

    echo "Pedido encontrado:\n";
    echo "   ID: " . $pedido->id . "\n";
    echo "   Número: " . $pedido->numero_pedido . "\n";
    echo "   Cliente: " . $pedido->cliente . "\n";
    echo "   Estado: " . $pedido->estado . "\n\n";

} catch (\Exception $e) {
    echo "❌ Error al buscar pedido: " . $e->getMessage() . "\n\n";
    exit;
}

// ═════════════════════════════════════════════════════════════════════════════════
// 1. IMÁGENES DE PRENDAS
// ═════════════════════════════════════════════════════════════════════════════════

echo "┌─────────────────────────────────────────────────────────────────────────────┐\n";
echo "│  1. IMÁGENES DE PRENDAS (prenda_fotos_pedido)                              │\n";
echo "└─────────────────────────────────────────────────────────────────────────────┘\n";

try {
    $prendaFotos = DB::table('prenda_fotos_pedido')
        ->join('prendas_pedido', 'prenda_fotos_pedido.prenda_pedido_id', '=', 'prendas_pedido.id')
        ->where('prendas_pedido.pedido_produccion_id', $pedidoId)
        ->select(
            'prenda_fotos_pedido.id',
            'prenda_fotos_pedido.ruta_webp',
            'prendas_pedido.nombre_prenda'
        )
        ->orderBy('prenda_fotos_pedido.id')
        ->get();

    if ($prendaFotos->isEmpty()) {
        echo "❌ No hay imágenes de prendas\n\n";
    } else {
        echo "✅ Imágenes de prendas: " . $prendaFotos->count() . "\n\n";
        foreach ($prendaFotos as $foto) {
            echo "   Prenda: " . $foto->nombre_prenda . "\n";
            echo "   Ruta: " . $foto->ruta_webp . "\n";
            echo "   ────────────────────────────────────────────────────────────\n";
        }
    }
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n\n";
}

// ═════════════════════════════════════════════════════════════════════════════════
// 2. IMÁGENES DE TELAS
// ═════════════════════════════════════════════════════════════════════════════════

echo "\n┌─────────────────────────────────────────────────────────────────────────────┐\n";
echo "│  2. IMÁGENES DE TELAS (prenda_fotos_tela_pedido)                           │\n";
echo "└─────────────────────────────────────────────────────────────────────────────┘\n";

try {
    $telaFotos = DB::table('prenda_fotos_tela_pedido')
        ->join('prenda_pedido_colores_telas', 'prenda_fotos_tela_pedido.prenda_pedido_colores_telas_id', '=', 'prenda_pedido_colores_telas.id')
        ->join('prendas_pedido', 'prenda_pedido_colores_telas.prenda_pedido_id', '=', 'prendas_pedido.id')
        ->where('prendas_pedido.pedido_produccion_id', $pedidoId)
        ->select(
            'prenda_fotos_tela_pedido.id',
            'prenda_fotos_tela_pedido.ruta_webp',
            'prendas_pedido.nombre_prenda'
        )
        ->orderBy('prenda_fotos_tela_pedido.id')
        ->get();

    if ($telaFotos->isEmpty()) {
        echo "❌ No hay imágenes de telas\n\n";
    } else {
        echo "✅ Imágenes de telas: " . $telaFotos->count() . "\n\n";
        foreach ($telaFotos as $foto) {
            echo "   Prenda: " . $foto->nombre_prenda . "\n";
            echo "   Ruta: " . $foto->ruta_webp . "\n";
            echo "   ────────────────────────────────────────────────────────────\n";
        }
    }
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n\n";
}

// ═════════════════════════════════════════════════════════════════════════════════
// 3. IMÁGENES DE PROCESOS
// ═════════════════════════════════════════════════════════════════════════════════

echo "\n┌─────────────────────────────────────────────────────────────────────────────┐\n";
echo "│  3. IMÁGENES DE PROCESOS (pedidos_procesos_imagenes)                       │\n";
echo "└─────────────────────────────────────────────────────────────────────────────┘\n";

try {
    $procesoImagenes = DB::table('pedidos_procesos_imagenes')
        ->join('pedidos_procesos_prenda_detalles', 'pedidos_procesos_imagenes.proceso_prenda_detalle_id', '=', 'pedidos_procesos_prenda_detalles.id')
        ->join('prendas_pedido', 'pedidos_procesos_prenda_detalles.prenda_pedido_id', '=', 'prendas_pedido.id')
        ->where('prendas_pedido.pedido_produccion_id', $pedidoId)
        ->select(
            'pedidos_procesos_imagenes.id',
            'pedidos_procesos_imagenes.ruta_webp',
            'pedidos_procesos_imagenes.es_principal',
            'prendas_pedido.nombre_prenda',
            'pedidos_procesos_prenda_detalles.tipo as tipo_proceso'
        )
        ->orderBy('pedidos_procesos_imagenes.id')
        ->get();

    if ($procesoImagenes->isEmpty()) {
        echo "❌ No hay imágenes de procesos\n\n";
    } else {
        echo "✅ Imágenes de procesos: " . $procesoImagenes->count() . "\n\n";
        foreach ($procesoImagenes as $foto) {
            $principal = $foto->es_principal ? '✓ PRINCIPAL' : 'Secundaria';
            echo "   Prenda: " . $foto->nombre_prenda . "\n";
            echo "   Proceso: " . strtoupper($foto->tipo_proceso) . "\n";
            echo "   Tipo: " . $principal . "\n";
            echo "   Ruta: " . $foto->ruta_webp . "\n";
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
        ->where('prendas_pedido.pedido_produccion_id', $pedidoId)
        ->count();

    $totalTelas = DB::table('prenda_fotos_tela_pedido')
        ->join('prenda_pedido_colores_telas', 'prenda_fotos_tela_pedido.prenda_pedido_colores_telas_id', '=', 'prenda_pedido_colores_telas.id')
        ->join('prendas_pedido', 'prenda_pedido_colores_telas.prenda_pedido_id', '=', 'prendas_pedido.id')
        ->where('prendas_pedido.pedido_produccion_id', $pedidoId)
        ->count();

    $totalProcesos = DB::table('pedidos_procesos_imagenes')
        ->join('pedidos_procesos_prenda_detalles', 'pedidos_procesos_imagenes.proceso_prenda_detalle_id', '=', 'pedidos_procesos_prenda_detalles.id')
        ->join('prendas_pedido', 'pedidos_procesos_prenda_detalles.prenda_pedido_id', '=', 'prendas_pedido.id')
        ->where('prendas_pedido.pedido_produccion_id', $pedidoId)
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
