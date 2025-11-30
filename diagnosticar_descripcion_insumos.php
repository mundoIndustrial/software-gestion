<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Models\PedidoProduccion;
use App\Models\MaterialesOrdenInsumos;

echo "════════════════════════════════════════════════════════════════\n";
echo "DIAGNÓSTICO: Comparación de Descripción en Registros vs Insumos\n";
echo "════════════════════════════════════════════════════════════════\n\n";

// Obtener una orden con prendas
$orden = PedidoProduccion::with('prendas')->whereHas('prendas')->first();

if (!$orden) {
    echo "❌ No se encontró ninguna orden con prendas\n";
    exit(1);
}

echo "📋 Orden Seleccionada: " . $orden->numero_pedido . "\n";
echo "👤 Cliente: " . $orden->cliente . "\n";
echo "─────────────────────────────────────────────────────────────────\n\n";

// ========== PASO 1: Analizar descripcion_prendas en PedidoProduccion ==========
echo "📊 PASO 1: Descripción en PedidoProduccion\n";
echo "═══════════════════════════════════════════════════════════════\n";

$descripcionPrendas = $orden->descripcion_prendas;

if ($descripcionPrendas) {
    echo "✅ descripcion_prendas encontrada\n";
    echo "Longitud: " . strlen($descripcionPrendas) . " caracteres\n";
    echo "Primeros 200 caracteres:\n";
    echo "─────────────────────────────────────────────────────────────────\n";
    echo substr($descripcionPrendas, 0, 200) . "...\n";
    echo "─────────────────────────────────────────────────────────────────\n\n";
    
    // Analizar estructura
    $lineas = explode("\n", $descripcionPrendas);
    echo "📈 Estructura:\n";
    echo "  - Total de líneas: " . count($lineas) . "\n";
    echo "  - Primeras 5 líneas:\n";
    for ($i = 0; $i < min(5, count($lineas)); $i++) {
        echo "    [$i] " . trim($lineas[$i]) . "\n";
    }
    echo "\n";
} else {
    echo "❌ descripcion_prendas está vacía\n\n";
}

// ========== PASO 2: Analizar prendas individuales ==========
echo "📊 PASO 2: Análisis de Prendas Individuales\n";
echo "═══════════════════════════════════════════════════════════════\n";

$prendas = $orden->prendas()->get();
echo "Total de prendas: " . $prendas->count() . "\n\n";

foreach ($prendas as $i => $prenda) {
    echo "🔹 Prenda " . ($i + 1) . ": " . $prenda->nombre_prenda . "\n";
    echo "   ID: " . $prenda->id . "\n";
    echo "   Descripción: " . (substr($prenda->descripcion, 0, 50) ?: 'N/A') . "\n";
    echo "   Descripción Armada: " . (substr($prenda->descripcion_armada, 0, 50) ?: 'N/A') . "\n";
    echo "   Cantidad Talla: " . $prenda->cantidad_talla . "\n";
    
    // Parsear cantidad_talla
    if ($prenda->cantidad_talla) {
        $cantidadTalla = json_decode($prenda->cantidad_talla, true);
        if (is_array($cantidadTalla)) {
            echo "   Tallas encontradas:\n";
            foreach ($cantidadTalla as $talla => $cantidad) {
                echo "     - $talla: $cantidad\n";
            }
        }
    }
    echo "\n";
}

// ========== PASO 3: Materiales en tabla insumos ==========
echo "📊 PASO 3: Materiales en Tabla Insumos\n";
echo "═══════════════════════════════════════════════════════════════\n";

$materiales = MaterialesOrdenInsumos::where('numero_pedido', $orden->numero_pedido)->get();
echo "Total de materiales guardados: " . $materiales->count() . "\n\n";

foreach ($materiales as $i => $material) {
    echo "🔹 Material " . ($i + 1) . ": " . $material->nombre_material . "\n";
    echo "   Recibido: " . ($material->recibido ? 'Sí' : 'No') . "\n";
    echo "   Observaciones: " . (substr($material->observaciones, 0, 50) ?: 'N/A') . "\n";
    echo "   Prenda Pedido ID: " . ($material->prenda_pedido_id ?: 'N/A') . "\n";
    echo "\n";
}

// ========== PASO 4: Comparar con Eager Loading ==========
echo "📊 PASO 4: Datos con Eager Loading (Como se obtiene en Insumos)\n";
echo "═══════════════════════════════════════════════════════════════\n";

$materialesConEagerLoad = MaterialesOrdenInsumos::query()
    ->with('pedido') // Cargar la relación pedido
    ->where('numero_pedido', $orden->numero_pedido)
    ->get();

echo "Total de materiales con Eager Loading: " . $materialesConEagerLoad->count() . "\n\n";

foreach ($materialesConEagerLoad as $i => $material) {
    echo "🔹 Material " . ($i + 1) . ": " . $material->nombre_material . "\n";
    
    // Verificar si la relación pedido está disponible
    if ($material->pedido) {
        echo "   ✅ Relación 'pedido' disponible\n";
        echo "   ✅ descripcion_prendas disponible\n";
        echo "   Longitud: " . strlen($material->pedido->descripcion_prendas) . " caracteres\n";
        echo "   Primeros 100 caracteres:\n";
        echo "   " . substr($material->pedido->descripcion_prendas, 0, 100) . "...\n";
    } else {
        echo "   ❌ Relación 'pedido' NO disponible\n";
    }
    
    if ($material->pedido) {
        echo "   Cliente: " . $material->pedido->cliente . "\n";
        echo "   Estado: " . $material->pedido->estado . "\n";
        echo "   Área: " . $material->pedido->area . "\n";
    }
    echo "\n";
}

// ========== PASO 5: Cómo se arma en Registros ==========
echo "📊 PASO 5: Cómo se Arma la Descripción en Registros\n";
echo "═══════════════════════════════════════════════════════════════\n";

echo "En RegistroOrdenController.php se usa:\n";
echo "  1. \$orden->getDescripcionPrendasAttribute()\n";
echo "  2. Este atributo append está en PedidoProduccion model\n";
echo "  3. Construye la descripción desde las prendas relacionadas\n\n";

// Simular cómo se construye
echo "🔧 Reconstrucción Manual de descripcion_prendas:\n";
echo "─────────────────────────────────────────────────────────────────\n";

$descripcionReconstruida = '';
foreach ($prendas as $i => $prenda) {
    $descripcionReconstruida .= "Prenda " . ($i + 1) . ": " . $prenda->nombre_prenda . "\n";
    
    if ($prenda->descripcion) {
        $descripcionReconstruida .= "Descripción: " . $prenda->descripcion . "\n";
    }
    
    // Parsear cantidad_talla
    if ($prenda->cantidad_talla) {
        $cantidadTalla = json_decode($prenda->cantidad_talla, true);
        if (is_array($cantidadTalla) && !empty($cantidadTalla)) {
            $tallas = [];
            foreach ($cantidadTalla as $talla => $cantidad) {
                $tallas[] = "$talla:$cantidad";
            }
            $descripcionReconstruida .= "Tallas: " . implode(", ", $tallas) . "\n";
        }
    }
    
    $descripcionReconstruida .= "\n";
}

echo $descripcionReconstruida;
echo "\n";

// Comparar
echo "📈 COMPARACIÓN:\n";
echo "─────────────────────────────────────────────────────────────────\n";

if (trim($descripcionPrendas) === trim($descripcionReconstruida)) {
    echo "✅ Las descripciones coinciden perfectamente\n";
} else {
    echo "⚠️ Las descripciones DIFIEREN\n\n";
    
    echo "Descripción Original (BD):\n";
    echo "──────────────────────────\n";
    echo strlen($descripcionPrendas) . " caracteres\n";
    echo "Hash: " . md5($descripcionPrendas) . "\n\n";
    
    echo "Descripción Reconstruida:\n";
    echo "──────────────────────────\n";
    echo strlen($descripcionReconstruida) . " caracteres\n";
    echo "Hash: " . md5($descripcionReconstruida) . "\n\n";
    
    // Mostrar diferencias
    $lineas1 = explode("\n", $descripcionPrendas);
    $lineas2 = explode("\n", $descripcionReconstruida);
    
    echo "Diferencias línea por línea:\n";
    $maxLineas = max(count($lineas1), count($lineas2));
    for ($i = 0; $i < $maxLineas; $i++) {
        $l1 = trim($lineas1[$i] ?? '');
        $l2 = trim($lineas2[$i] ?? '');
        
        if ($l1 !== $l2) {
            echo "Línea " . ($i + 1) . ":\n";
            echo "  Original:    '$l1'\n";
            echo "  Reconstruida: '$l2'\n";
        }
    }
}

echo "\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "✅ DIAGNÓSTICO COMPLETADO\n";
echo "════════════════════════════════════════════════════════════════\n";
