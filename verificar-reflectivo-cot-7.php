<?php

/**
 * Script para verificar datos de prenda_cot_reflectivo para cotización ID 7
 * Muestra la estructura completa de telas, variaciones, ubicaciones y descripción
 */

require 'vendor/autoload.php';
require 'bootstrap/app.php';

use App\Models\Cotizacion;
use App\Models\PrendaCotReflectivo;
use App\Models\PrendaCot;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "  VERIFICACIÓN DE DATOS REFLECTIVO - COTIZACIÓN #7\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// 1. Verificar cotización
echo "📋 [1] INFORMACIÓN DE LA COTIZACIÓN\n";
echo "───────────────────────────────────────────────────────────────\n";
$cotizacion = Cotizacion::find(7);

if (!$cotizacion) {
    echo "❌ Cotización ID 7 no encontrada\n";
    exit(1);
}

echo "ID: " . $cotizacion->id . "\n";
echo "Número: " . $cotizacion->numero_cotizacion . "\n";
echo "Cliente: " . $cotizacion->cliente?->nombre . "\n";
echo "Tipo: ID=" . $cotizacion->tipo_cotizacion_id . " | Nombre=" . $cotizacion->tipoCotizacion?->nombre . "\n";
echo "Estado: " . $cotizacion->estado . "\n";
echo "\n";

// 2. Obtener prendas de la cotización
echo "📦 [2] PRENDAS DE LA COTIZACIÓN\n";
echo "───────────────────────────────────────────────────────────────\n";
$prendas = PrendaCot::where('cotizacion_id', 7)->get();
echo "Total de prendas: " . $prendas->count() . "\n\n";

if ($prendas->isEmpty()) {
    echo "⚠️  No hay prendas en esta cotización\n";
    exit(1);
}

// 3. Para cada prenda, mostrar datos de prenda_cot_reflectivo
foreach ($prendas as $index => $prenda) {
    echo "🧥 [Prenda " . ($index + 1) . "]\n";
    echo "───────────────────────────────────────────────────────────────\n";
    echo "ID: " . $prenda->id . "\n";
    echo "Nombre: " . $prenda->nombre_producto . "\n";
    echo "Descripción: " . ($prenda->descripcion ?? 'N/A') . "\n";
    echo "\n";

    // Buscar datos en prenda_cot_reflectivo
    $prendaReflectivo = PrendaCotReflectivo::where([
        'cotizacion_id' => 7,
        'prenda_cot_id' => $prenda->id
    ])->first();

    if (!$prendaReflectivo) {
        echo "⚠️  No hay registro en prenda_cot_reflectivo\n";
        echo "\n";
        continue;
    }

    echo "✅ Registro en prenda_cot_reflectivo encontrado\n";
    echo "\n";

    // 3.1 Telas, Colores y Referencias
    echo "   🧵 TELAS / COLORES / REFERENCIAS:\n";
    echo "   ┌─────────────────────────────────────────────────────────\n";
    
    if ($prendaReflectivo->color_tela_ref) {
        $colorTelaRef = $prendaReflectivo->color_tela_ref;
        if (is_array($colorTelaRef)) {
            echo "   Tipo: Array (" . count($colorTelaRef) . " elementos)\n";
            foreach ($colorTelaRef as $idx => $item) {
                echo "   \n";
                echo "   [$idx] Tela: " . ($item['tela'] ?? 'N/A') . "\n";
                echo "       Color: " . ($item['color'] ?? 'N/A') . "\n";
                echo "       Referencia: " . ($item['referencia'] ?? 'N/A') . "\n";
            }
        } else {
            echo "   Tipo: String\n";
            echo "   Contenido: " . $colorTelaRef . "\n";
        }
    } else {
        echo "   ⚠️  Sin datos (NULL)\n";
    }
    echo "   └─────────────────────────────────────────────────────────\n";
    echo "\n";

    // 3.2 Variaciones
    echo "   📐 VARIACIONES:\n";
    echo "   ┌─────────────────────────────────────────────────────────\n";
    
    if ($prendaReflectivo->variaciones) {
        $variaciones = $prendaReflectivo->variaciones;
        if (is_array($variaciones)) {
            echo "   Tipo: Array (" . count($variaciones) . " elementos)\n";
            foreach ($variaciones as $idx => $variacion) {
                echo "   \n";
                echo "   [$idx] " . json_encode($variacion, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
            }
        } else {
            echo "   Tipo: String\n";
            echo "   Contenido: " . $variaciones . "\n";
        }
    } else {
        echo "   ⚠️  Sin datos (NULL)\n";
    }
    echo "   └─────────────────────────────────────────────────────────\n";
    echo "\n";

    // 3.3 Ubicaciones
    echo "   📍 UBICACIONES:\n";
    echo "   ┌─────────────────────────────────────────────────────────\n";
    
    if ($prendaReflectivo->ubicaciones) {
        $ubicaciones = $prendaReflectivo->ubicaciones;
        if (is_array($ubicaciones)) {
            echo "   Tipo: Array (" . count($ubicaciones) . " elementos)\n";
            foreach ($ubicaciones as $idx => $ubicacion) {
                echo "   \n";
                echo "   [$idx] " . json_encode($ubicacion, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
            }
        } else {
            echo "   Tipo: String\n";
            echo "   Contenido: " . $ubicaciones . "\n";
        }
    } else {
        echo "   ⚠️  Sin datos (NULL)\n";
    }
    echo "   └─────────────────────────────────────────────────────────\n";
    echo "\n";

    // 3.4 Descripción
    echo "   📝 DESCRIPCIÓN:\n";
    echo "   ┌─────────────────────────────────────────────────────────\n";
    
    if ($prendaReflectivo->descripcion) {
        echo "   " . $prendaReflectivo->descripcion . "\n";
    } else {
        echo "   ⚠️  Sin descripción (NULL)\n";
    }
    echo "   └─────────────────────────────────────────────────────────\n";
    echo "\n";

    // Mostrar registro completo en JSON
    echo "   📄 REGISTRO COMPLETO (JSON):\n";
    echo "   ┌─────────────────────────────────────────────────────────\n";
    echo "   " . json_encode($prendaReflectivo->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    echo "   └─────────────────────────────────────────────────────────\n";
    echo "\n";
    echo "\n";
}

echo "═══════════════════════════════════════════════════════════════\n";
echo "  ✅ VERIFICACIÓN COMPLETADA\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

?>
