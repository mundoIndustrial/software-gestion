<?php

/**
 * ✅ TEST: Verificar que variantes se guardan con genero-talla
 * 
 * Cuando una prenda tiene múltiples géneros:
 * cantidad_talla = {"dama": {"S": 30, "M": 40}, "caballero": {"S": 20, "L": 20}}
 * 
 * Las variantes deben guardarse como:
 * - dama-S (cantidad: 30)
 * - dama-M (cantidad: 40)
 * - caballero-S (cantidad: 20)
 * - caballero-L (cantidad: 20)
 */

require_once __DIR__ . '/bootstrap/app.php';

use Illuminate\Support\Facades\DB;
use App\Models\PrendaPedido;

try {
    echo "\n========================================\n";
    echo "🧪 TEST: Variantes con Género-Talla\n";
    echo "========================================\n";

    // 1️⃣ BUSCAR PRENDAS CON MÚLTIPLES GÉNEROS
    echo "\n1️⃣ Buscando prendas con múltiples géneros...\n";
    
    $prendas = PrendaPedido::all();
    
    $prendasMultiplesGeneros = [];
    foreach ($prendas as $prenda) {
        $cantidadTalla = $prenda->cantidad_talla;
        
        if (is_string($cantidadTalla)) {
            $cantidadTalla = json_decode($cantidadTalla, true);
        }
        
        if (is_array($cantidadTalla) && count($cantidadTalla) > 1) {
            $prendasMultiplesGeneros[] = [
                'id' => $prenda->id,
                'nombre' => $prenda->nombre_prenda,
                'cantidad_talla' => $cantidadTalla,
                'generos' => array_keys($cantidadTalla),
            ];
        }
    }
    
    if (empty($prendasMultiplesGeneros)) {
        echo "⚠️ No hay prendas con múltiples géneros en BD\n";
        echo "\nCreando prenda de prueba...\n";
        
        // Crear prenda de prueba
        $pedidoId = DB::table('pedidos_produccion')->first()?->id;
        if (!$pedidoId) {
            die("❌ No hay pedidos en BD\n");
        }
        
        $prenda = PrendaPedido::create([
            'pedido_produccion_id' => $pedidoId,
            'nombre_prenda' => 'Camiseta Test Géneros',
            'descripcion' => 'Prueba de múltiples géneros',
            'cantidad_talla' => json_encode([
                'dama' => ['S' => 10, 'M' => 15, 'L' => 20],
                'caballero' => ['S' => 8, 'M' => 12, 'L' => 18],
            ]),
            'genero' => 'unisex',
            'de_bodega' => 1,
        ]);
        
        echo "✅ Prenda creada: ID {$prenda->id}\n";
        $prendasMultiplesGeneros[] = [
            'id' => $prenda->id,
            'nombre' => $prenda->nombre_prenda,
            'cantidad_talla' => json_decode($prenda->cantidad_talla, true),
            'generos' => ['dama', 'caballero'],
        ];
    }
    
    echo "✅ Encontradas " . count($prendasMultiplesGeneros) . " prenda(s) con múltiples géneros\n";

    // 2️⃣ VERIFICAR VARIANTES
    echo "\n2️⃣ Verificando variantes guardadas...\n\n";
    
    $todasOk = true;
    
    foreach ($prendasMultiplesGeneros as $prendasInfo) {
        $prendasId = $prendasInfo['id'];
        $prendasNombre = $prendasInfo['nombre'];
        $cantidadTalla = $prendasInfo['cantidad_talla'];
        $generosEsperados = $prendasInfo['generos'];
        
        echo "📋 Prenda: {$prendasNombre} (ID: {$prendasId})\n";
        echo "   Cantidad Talla: " . json_encode($cantidadTalla) . "\n";
        
        // Obtener variantes
        $variantes = DB::table('prenda_pedido_variantes')
            ->where('prenda_pedido_id', $prendasId)
            ->orderBy('talla')
            ->get();
        
        echo "   Variantes en BD: " . $variantes->count() . "\n";
        
        if ($variantes->count() === 0) {
            echo "   ❌ SIN VARIANTES\n";
            $todasOk = false;
            continue;
        }
        
        // Verificar que cada combinación genero-talla existe
        $variatesExpectadas = [];
        $variatesEncontradas = [];
        
        foreach ($generosEsperados as $genero) {
            $tallasPorGenero = $cantidadTalla[$genero] ?? [];
            foreach ($tallasPorGenero as $talla => $cantidad) {
                $variatesExpectadas[] = "{$genero}-{$talla}";
            }
        }
        
        foreach ($variantes as $var) {
            $variatesEncontradas[] = $var->talla;
            echo "     • Talla: {$var->talla}, Cantidad: {$var->cantidad}\n";
        }
        
        // Comparar
        $faltantes = array_diff($variatesExpectadas, $variatesEncontradas);
        $extras = array_diff($variatesEncontradas, $variatesExpectadas);
        
        if (!empty($faltantes)) {
            echo "   ❌ Variantes faltantes: " . implode(', ', $faltantes) . "\n";
            $todasOk = false;
        }
        
        if (!empty($extras)) {
            echo "   ⚠️ Variantes extras: " . implode(', ', $extras) . "\n";
            $todasOk = false;
        }
        
        if (empty($faltantes) && empty($extras)) {
            echo "   ✅ Todas las variantes correctas\n";
        }
        
        echo "\n";
    }

    // 3️⃣ RESULTADO FINAL
    echo "========================================\n";
    if ($todasOk) {
        echo "✅ TEST EXITOSO: Variantes con genero-talla funcionan correctamente\n";
    } else {
        echo "❌ TEST FALLÓ: Hay problemas con las variantes\n";
    }
    echo "========================================\n\n";

} catch (\Exception $e) {
    echo "❌ Error:\n";
    echo "   {$e->getMessage()}\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
}
?>
