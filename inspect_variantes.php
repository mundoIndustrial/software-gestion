<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\VariantePrenda;
use Illuminate\Support\Facades\DB;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "🔍 INSPECCIONANDO VARIANTES EN BD\n";
echo "═══════════════════════════════════════════════════════════════\n";

// Obtener todas las variantes
$variantes = VariantePrenda::all();

echo "\nTotal de variantes: {$variantes->count()}\n";

foreach ($variantes as $v) {
    echo "\n" . str_repeat("─", 65) . "\n";
    echo "Variante ID: {$v->id}\n";
    echo "Prenda: {$v->prendaCotizacion->nombre_producto}\n";
    echo "Cotización: {$v->prendaCotizacion->cotizacion_id}\n";
    echo "\nCampos guardados:\n";
    echo "  - color_id: {$v->color_id}\n";
    echo "  - tela_id: {$v->tela_id}\n";
    echo "  - tipo_manga_id: {$v->tipo_manga_id}\n";
    echo "  - tipo_broche_id: {$v->tipo_broche_id}\n";
    echo "  - tiene_bolsillos: " . ($v->tiene_bolsillos ? 'true' : 'false') . "\n";
    echo "  - tiene_reflectivo: " . ($v->tiene_reflectivo ? 'true' : 'false') . "\n";
    echo "  - descripcion_adicional: " . ($v->descripcion_adicional ? $v->descripcion_adicional : '(vacío)') . "\n";
    echo "  - cantidad_talla: " . ($v->cantidad_talla ? $v->cantidad_talla : '(vacío)') . "\n";
}

echo "\n" . str_repeat("═", 65) . "\n";
echo "✅ INSPECCIÓN COMPLETADA\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Mostrar estructura de tabla
echo "📊 ESTRUCTURA DE TABLA variantes_prenda:\n";
$columns = DB::select("DESCRIBE variantes_prenda");
foreach ($columns as $col) {
    echo "  - {$col->Field} ({$col->Type})\n";
}

echo "\n";
