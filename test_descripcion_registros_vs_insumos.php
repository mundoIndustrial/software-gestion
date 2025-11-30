<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Models\PedidoProduccion;

echo "════════════════════════════════════════════════════════════════\n";
echo "TEST: Comparar cómo se obtiene descripcion_prendas\n";
echo "REGISTROS vs INSUMOS\n";
echo "════════════════════════════════════════════════════════════════\n\n";

// ========== COMO LO HACE REGISTROS ==========
echo "📊 MÉTODO 1: COMO LO HACE REGISTROS\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "Código:\n";
echo "  \$query = PedidoProduccion::query()\n";
echo "    ->with(['asesora:id,name', 'prendas' => function(\$q) { \n";
echo "      \$q->select('id', 'pedido_produccion_id', 'nombre_prenda', 'cantidad', 'descripcion');\n";
echo "    }]);\n";
echo "  \$ordenes = \$query->paginate();\n\n";

$queryRegistros = PedidoProduccion::query()
    ->with([
        'asesora:id,name',
        'prendas' => function($q) {
            $q->select('id', 'pedido_produccion_id', 'nombre_prenda', 'cantidad', 'descripcion');
        }
    ])
    ->limit(1)
    ->get();

if ($queryRegistros->count() > 0) {
    $ordenRegistros = $queryRegistros->first();
    echo "✅ Orden encontrada: " . $ordenRegistros->numero_pedido . "\n";
    echo "Prendas cargadas: " . $ordenRegistros->relationLoaded('prendas') . "\n";
    echo "Total prendas: " . $ordenRegistros->prendas->count() . "\n\n";
    
    echo "descripcion_prendas:\n";
    echo "─────────────────────────────────────────────────────────────────\n";
    $descripcionRegistros = $ordenRegistros->descripcion_prendas;
    echo $descripcionRegistros;
    echo "\n─────────────────────────────────────────────────────────────────\n";
    echo "Longitud: " . strlen($descripcionRegistros) . " caracteres\n";
    echo "Hash: " . md5($descripcionRegistros) . "\n\n\n";
} else {
    echo "❌ No se encontró orden\n\n";
}

// ========== COMO LO HACE INSUMOS ==========
echo "📊 MÉTODO 2: COMO LO HACE INSUMOS\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "Código:\n";
echo "  \$query = PedidoProduccion::where(...filters...)\n";
echo "    ->with('prendas')\n";
echo "    ->orderBy('numero_pedido', 'asc')\n";
echo "    ->paginate(10);\n";
echo "  \$ordenes->getCollection()->transform(function(\$orden) {\n";
echo "    // solo cargar materiales\n";
echo "    return \$orden;\n";
echo "  });\n\n";

// Usar la MISMA orden que en registros para comparar
$numeroOrdenRegistros = $queryRegistros->first()->numero_pedido;
echo "📌 Buscando la MISMA orden que en registros: $numeroOrdenRegistros\n\n";

$queryInsumos = PedidoProduccion::where('numero_pedido', $numeroOrdenRegistros)
    ->with('prendas')
    ->orderBy('numero_pedido', 'asc')
    ->limit(1)
    ->get();

if ($queryInsumos->count() > 0) {
    $ordenInsumos = $queryInsumos->first();
    echo "✅ Orden encontrada: " . $ordenInsumos->numero_pedido . "\n";
    echo "Prendas cargadas: " . $ordenInsumos->relationLoaded('prendas') . "\n";
    echo "Total prendas: " . $ordenInsumos->prendas->count() . "\n\n";
    
    echo "descripcion_prendas:\n";
    echo "─────────────────────────────────────────────────────────────────\n";
    $descripcionInsumos = $ordenInsumos->descripcion_prendas;
    echo $descripcionInsumos;
    echo "\n─────────────────────────────────────────────────────────────────\n";
    echo "Longitud: " . strlen($descripcionInsumos) . " caracteres\n";
    echo "Hash: " . md5($descripcionInsumos) . "\n\n";
} else {
    echo "❌ No se encontró orden\n\n";
}

// ========== COMPARACIÓN ==========
echo "📈 COMPARACIÓN\n";
echo "═══════════════════════════════════════════════════════════════\n";

if ($queryRegistros->count() > 0 && $queryInsumos->count() > 0) {
    if ($descripcionRegistros === $descripcionInsumos) {
        echo "✅ LAS DESCRIPCIONES SON IDÉNTICAS\n\n";
    } else {
        echo "⚠️ LAS DESCRIPCIONES DIFIEREN\n\n";
        echo "Registros longitud: " . strlen($descripcionRegistros) . "\n";
        echo "Insumos longitud: " . strlen($descripcionInsumos) . "\n\n";
        
        // Mostrar primeras diferencias
        $lineasRegistros = explode("\n", $descripcionRegistros);
        $lineasInsumos = explode("\n", $descripcionInsumos);
        
        echo "Primeras 5 líneas:\n";
        echo "─────────────────────────────────────────────────────────────────\n";
        echo "REGISTROS:\n";
        for ($i = 0; $i < min(5, count($lineasRegistros)); $i++) {
            echo "  [$i] " . $lineasRegistros[$i] . "\n";
        }
        echo "\nINSUMOS:\n";
        for ($i = 0; $i < min(5, count($lineasInsumos)); $i++) {
            echo "  [$i] " . $lineasInsumos[$i] . "\n";
        }
    }
}

echo "\n════════════════════════════════════════════════════════════════\n";
echo "✅ TEST COMPLETADO\n";
echo "════════════════════════════════════════════════════════════════\n";
