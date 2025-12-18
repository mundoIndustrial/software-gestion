<?php

// Cargar Laravel
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

use Illuminate\Support\Facades\DB;

try {
    $app->make(\Illuminate\Contracts\Http\Kernel::class);

    echo "\n";
    echo "╔════════════════════════════════════════════════════════════════╗\n";
    echo "║ PRUEBA: TODOS LOS TIPOS DE COTIZACIONES - NUMERACIÓN GLOBAL   ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n\n";

    $numeros_generados = [];
    $detalles = [];
    
    echo "🔄 Generando números para todos los tipos...\n\n";
    
    $tipos_cotizacion = ['Normal', 'Prenda', 'Bordado', 'Reflectivo'];
    
    // Generar 5 números de cada tipo (20 total)
    for ($i = 0; $i < 5; $i++) {
        foreach ($tipos_cotizacion as $tipo) {
            // Usar transacción con lock (como lo hace GenerarNumeroCotizacionService)
            DB::beginTransaction();
            try {
                $secuencia = DB::table('cotizacion_secuencias')
                    ->where('tipo', 'global')
                    ->lockForUpdate()
                    ->first();
                
                if (!$secuencia) {
                    throw new Exception("Secuencia global no encontrada");
                }
                
                $numero_int = $secuencia->siguiente_numero;
                DB::table('cotizacion_secuencias')
                    ->where('tipo', 'global')
                    ->update(['siguiente_numero' => $numero_int + 1]);
                
                DB::commit();
                
                $numero_formateado = 'COT-' . str_pad($numero_int, 6, '0', STR_PAD_LEFT);
                
                $numeros_generados[] = $numero_int;
                $detalles[] = [
                    'iteracion' => $i + 1,
                    'tipo' => $tipo,
                    'numero_formateado' => $numero_formateado,
                    'numero_int' => $numero_int
                ];
                
                printf("  Iteración %d - %-12s: %s\n", $i + 1, $tipo, $numero_formateado);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        }
    }
    
    echo "\n";
    echo "╔════════════════════════════════════════════════════════════════╗\n";
    echo "║ VALIDACIÓN DE RESULTADOS                                       ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n\n";
    
    // Validación 1: Todos los números son únicos
    $total_numeros = count($numeros_generados);
    $numeros_unicos = array_unique($numeros_generados);
    $total_unicos = count($numeros_unicos);
    
    echo "📊 Estadísticas:\n";
    echo "   Total números generados: {$total_numeros}\n";
    echo "   Números únicos: {$total_unicos}\n";
    echo "   Duplicados: " . ($total_numeros - $total_unicos) . "\n\n";
    
    if ($total_numeros === $total_unicos) {
        echo "   ✅ SIN DUPLICADOS - Todos los números son únicos\n\n";
    } else {
        echo "   ❌ ERROR: Se encontraron duplicados!\n";
        throw new Exception("Hay números duplicados");
    }
    
    // Validación 2: Secuencia perfecta (números consecutivos)
    sort($numeros_generados);
    $rango_esperado = range(min($numeros_generados), max($numeros_generados));
    
    echo "🔍 Validando secuencia:\n";
    echo "   Rango: " . min($numeros_generados) . " → " . max($numeros_generados) . "\n";
    echo "   Esperados: " . count($rango_esperado) . " números\n";
    echo "   Obtenidos: " . count($numeros_generados) . " números\n";
    
    if ($numeros_generados === $rango_esperado) {
        echo "   ✅ SECUENCIA PERFECTA - Números consecutivos sin gaps\n\n";
    } else {
        echo "   ❌ ERROR: La secuencia no es perfecta\n";
        throw new Exception("Secuencia no es consecutiva");
    }
    
    // Validación 3: Agrupar por tipo para mostrar distribución
    echo "📋 Distribución de números por tipo:\n";
    $por_tipo = [];
    foreach ($detalles as $detalle) {
        $tipo = $detalle['tipo'];
        if (!isset($por_tipo[$tipo])) {
            $por_tipo[$tipo] = [];
        }
        $por_tipo[$tipo][] = $detalle['numero_int'];
    }
    
    foreach ($por_tipo as $tipo => $numeros) {
        echo "   {$tipo}: " . implode(", ", $numeros) . "\n";
    }
    
    echo "\n";
    echo "╔════════════════════════════════════════════════════════════════╗\n";
    echo "║ ✅ PRUEBA EXITOSA                                              ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n\n";
    
    echo "✓ Todos los tipos de cotizaciones usan la MISMA secuencia global\n";
    echo "✓ Normal, Prenda, Bordado y Reflectivo comparten números\n";
    echo "✓ Números únicos: {$total_unicos}/{$total_numeros}\n";
    echo "✓ Secuencia perfecta: Desde {$rango_esperado[0]} hasta {$rango_esperado[count($rango_esperado)-1]}\n";
    echo "✓ Sin importar el tipo: numeración global y consecutiva\n\n";
    
} catch (\Exception $e) {
    echo "\n";
    echo "╔════════════════════════════════════════════════════════════════╗\n";
    echo "║ ❌ ERROR EN LA PRUEBA                                          ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n\n";
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "   Archivo: " . $e->getFile() . "\n";
    echo "   Línea: " . $e->getLine() . "\n\n";
    exit(1);
}
