$cotizacion = \App\Models\Cotizacion::find(7);

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "  VERIFICACIÓN DE DATOS REFLECTIVO - COTIZACIÓN #7\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

if (!$cotizacion) {
    echo "❌ Cotización ID 7 no encontrada\n";
    exit;
}

echo "📋 INFORMACIÓN DE LA COTIZACIÓN\n";
echo "─────────────────────────────────────────────────────────────\n";
echo "ID: " . $cotizacion->id . "\n";
echo "Número: " . $cotizacion->numero_cotizacion . "\n";
echo "Cliente: " . $cotizacion->cliente?->nombre . "\n";
echo "Tipo ID: " . $cotizacion->tipo_cotizacion_id . "\n";
echo "Estado: " . $cotizacion->estado . "\n\n";

// Obtener prendas
$prendas = \App\Models\PrendaCot::where('cotizacion_id', 7)->get();
echo "📦 PRENDAS DE LA COTIZACIÓN\n";
echo "─────────────────────────────────────────────────────────────\n";
echo "Total: " . $prendas->count() . "\n\n";

foreach ($prendas as $index => $prenda) {
    echo "🧥 [PRENDA " . ($index + 1) . "]\n";
    echo "ID: " . $prenda->id . " | Nombre: " . $prenda->nombre_producto . "\n";
    
    $prendaReflectivo = \App\Models\PrendaCotReflectivo::where([
        'cotizacion_id' => 7,
        'prenda_cot_id' => $prenda->id
    ])->first();
    
    if (!$prendaReflectivo) {
        echo "⚠️  Sin registro en prenda_cot_reflectivo\n\n";
        continue;
    }
    
    echo "✅ Datos en prenda_cot_reflectivo:\n";
    
    // Telas
    echo "\n🧵 COLOR_TELA_REF:\n";
    if ($prendaReflectivo->color_tela_ref) {
        $colorTelaRef = $prendaReflectivo->color_tela_ref;
        echo json_encode($colorTelaRef, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    } else {
        echo "  (NULL)\n";
    }
    
    // Variaciones
    echo "\n📐 VARIACIONES:\n";
    if ($prendaReflectivo->variaciones) {
        $variaciones = $prendaReflectivo->variaciones;
        echo json_encode($variaciones, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    } else {
        echo "  (NULL)\n";
    }
    
    // Ubicaciones
    echo "\n📍 UBICACIONES:\n";
    if ($prendaReflectivo->ubicaciones) {
        $ubicaciones = $prendaReflectivo->ubicaciones;
        echo json_encode($ubicaciones, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    } else {
        echo "  (NULL)\n";
    }
    
    // Descripción
    echo "\n📝 DESCRIPCIÓN:\n";
    if ($prendaReflectivo->descripcion) {
        echo $prendaReflectivo->descripcion . "\n";
    } else {
        echo "  (NULL)\n";
    }
    
    echo "\n";
}

echo "═══════════════════════════════════════════════════════════════\n";
echo "  ✅ VERIFICACIÓN COMPLETADA\n";
echo "═══════════════════════════════════════════════════════════════\n\n";
