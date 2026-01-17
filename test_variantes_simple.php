<?php
/**
 * ✅ TEST SIMPLE: Validar que variantes se guardan con genero-talla
 */

require_once __DIR__ . '/bootstrap/app.php';

use App\Models\PrendaPedido;

try {
    echo "\n========================================\n";
    echo "🧪 TEST: Validar Variantes con Género-Talla\n";
    echo "========================================\n";

    // Buscar una prenda reciente
    echo "\n1️⃣ Buscando prenda reciente...\n";
    $prenda = PrendaPedido::latest('id')->first();
    
    if (!$prenda) {
        die("❌ No hay prendas en la BD\n");
    }

    echo "✅ Prenda encontrada: {$prenda->nombre_prenda}\n";
    echo "   ID: {$prenda->id}\n";
    echo "   Pedido: {$prenda->pedido_produccion_id}\n";

    // Mostrar cantidad_talla en prendas_pedido
    echo "\n2️⃣ Verificando campo cantidad_talla en prendas_pedido...\n";
    $cantidadTalla = $prenda->cantidad_talla;
    
    if ($cantidadTalla) {
        echo "   Estructura JSON:\n";
        if (is_string($cantidadTalla)) {
            $decoded = json_decode($cantidadTalla, true);
            echo "   " . json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        } else {
            echo "   " . json_encode($cantidadTalla, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        }
    } else {
        echo "   ⚠️ cantidad_talla está vacío\n";
    }

    // Mostrar variantes guardadas en prenda_pedido_variantes
    echo "\n3️⃣ Verificando variantes en prenda_pedido_variantes...\n";
    $variantes = $prenda->variantes()->get();
    
    echo "   Total variantes: " . $variantes->count() . "\n";
    
    if ($variantes->count() > 0) {
        echo "\n   📊 Detalle de variantes:\n";
        foreach ($variantes as $var) {
            echo "      • Talla: '{$var->talla}', Cantidad: {$var->cantidad}\n";
        }

        // Validar que hay variantes con genero-talla
        echo "\n4️⃣ Validando formato genero-talla...\n";
        $conGenero = 0;
        $sinGenero = 0;
        
        foreach ($variantes as $var) {
            if (strpos($var->talla, '-') !== false) {
                $conGenero++;
            } else {
                $sinGenero++;
            }
        }

        echo "   Con formato genero-talla: {$conGenero}\n";
        echo "   Sin formato genero-talla: {$sinGenero}\n";

        if ($conGenero > 0 && $sinGenero === 0) {
            echo "\n✅ TEST EXITOSO: Todas las variantes tienen formato genero-talla\n";
        } elseif ($sinGenero > 0) {
            echo "\n⚠️ ADVERTENCIA: Hay variantes sin formato genero-talla (prendas antiguas)\n";
        }

        // Verificar si hay múltiples géneros
        echo "\n5️⃣ Detectando géneros en variantes...\n";
        $generos = [];
        foreach ($variantes as $var) {
            if (strpos($var->talla, '-') !== false) {
                [$genero, $talla] = explode('-', $var->talla, 2);
                $generos[$genero] = ($generos[$genero] ?? 0) + 1;
            }
        }

        if (!empty($generos)) {
            echo "   Géneros encontrados:\n";
            foreach ($generos as $genero => $count) {
                echo "      • {$genero}: {$count} tallas\n";
            }
        } else {
            echo "   No se encontraron géneros en formato genero-talla\n";
        }

    } else {
        echo "   ⚠️ La prenda no tiene variantes\n";
    }

    echo "\n========================================\n";

} catch (\Exception $e) {
    echo "❌ Error: {$e->getMessage()}\n";
    echo "\nStack trace:\n{$e->getTraceAsString()}\n";
}
?>
