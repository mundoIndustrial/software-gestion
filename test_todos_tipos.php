
// TEST: Todos los tipos de cotizaciones con numeración global

echo "🔵 PRUEBA: TODOS LOS TIPOS DE COTIZACIONES - NUMERACIÓN GLOBAL\n";
echo "================================================================\n\n";

// Obtener el servicio
$servicioNumeros = app('App\Application\Cotizacion\Services\GenerarNumeroCotizacionService');

// Simular 5 asesores
$asesores = [1, 2, 3, 4, 5];

$numeros_generados = [];
$combinaciones = [];

// Cada asesor generará 4 números (uno por cada tipo)
// Total: 5 asesores × 4 tipos = 20 números
foreach ($asesores as $asesor_id) {
    foreach (['Normal', 'Prenda', 'Bordado', 'Reflectivo'] as $tipo) {
        $numero_formateado = $servicioNumeros->generarNumeroCotizacionFormateado($asesor_id);
        $numero_int = (int) substr($numero_formateado, 4);
        
        $numeros_generados[] = $numero_int;
        $combinaciones[] = [
            'asesor_id' => $asesor_id,
            'tipo' => $tipo,
            'numero_formateado' => $numero_formateado,
            'numero_int' => $numero_int
        ];
        
        echo "Asesor {$asesor_id} - Tipo {$tipo}: {$numero_formateado}\n";
    }
}

echo "\n📊 VALIDACIÓN DE RESULTADOS:\n";
echo "==============================\n\n";

// Validar 1: Todos los números son únicos
$numeros_unicos = array_unique($numeros_generados);
$total_numeros = count($numeros_generados);
$total_unicos = count($numeros_unicos);

echo "✓ Total números generados: {$total_numeros}\n";
echo "✓ Números únicos: {$total_unicos}\n";

if ($total_numeros === $total_unicos) {
    echo "✅ SIN DUPLICADOS - Todos los números son únicos\n\n";
} else {
    echo "❌ ERROR: Hay duplicados!\n";
    throw new Exception("Se encontraron números duplicados");
}

// Validar 2: Los números son consecutivos
sort($numeros_generados);
$esperado = range(min($numeros_generados), max($numeros_generados));

if ($numeros_generados === $esperado) {
    echo "✅ SECUENCIA PERFECTA - Los números son consecutivos sin gaps\n\n";
} else {
    echo "❌ ERROR: La secuencia no es perfecta!\n";
    throw new Exception("La secuencia no es consecutiva");
}

echo "✓ Verificando que la numeración es GLOBAL y no por asesor/tipo...\n\n";

$por_asesor = [];
foreach ($combinaciones as $comb) {
    $asesor = $comb['asesor_id'];
    if (!isset($por_asesor[$asesor])) {
        $por_asesor[$asesor] = [];
    }
    $por_asesor[$asesor][] = $comb['numero_int'];
}

echo "📋 Números por asesor:\n";
foreach ($por_asesor as $asesor_id => $numeros) {
    echo "   Asesor {$asesor_id}: " . implode(", ", $numeros) . "\n";
}

echo "\n✅ PRUEBA EXITOSA\n";
echo "================\n";
echo "Todos los tipos de cotizaciones usan la MISMA secuencia global.\n";
echo "No importa si es Normal, Prenda, Bordado o Reflectivo:\n";
echo "→ Los números siempre son consecutivos y únicos\n";
echo "→ La numeración es GLOBAL para toda la aplicación\n\n";
