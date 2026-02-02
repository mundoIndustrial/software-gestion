$prenda = \App\Models\PrendaCotReflectivo::where('cotizacion_id', 7)->where('prenda_cot_id', 7)->first();

echo "═══════════════════════════════════════════════════════════\n";
echo "TIPOS DE DATOS DEL MODELO PrendaCotReflectivo\n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "color_tela_ref:\n";
echo "  Tipo: " . gettype($prenda->color_tela_ref) . "\n";
echo "  Value: " . json_encode($prenda->color_tela_ref, JSON_PRETTY_PRINT) . "\n\n";

echo "variaciones:\n";
echo "  Tipo: " . gettype($prenda->variaciones) . "\n";
echo "  Value: " . json_encode($prenda->variaciones, JSON_PRETTY_PRINT) . "\n\n";

echo "ubicaciones:\n";
echo "  Tipo: " . gettype($prenda->ubicaciones) . "\n";
echo "  Value: " . json_encode($prenda->ubicaciones, JSON_PRETTY_PRINT) . "\n\n";

echo "═══════════════════════════════════════════════════════════\n";
