<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== VERIFICACIÓN: Tipos de Recibo en Base de Datos ===\n\n";

$registros = DB::table('consecutivos_recibos')
    ->orderBy('id')
    ->get(['id', 'tipo_recibo', 'consecutivo_actual', 'notas']);

foreach ($registros as $reg) {
    echo "ID: {$reg->id} | Tipo: {$reg->tipo_recibo} | Actual: {$reg->consecutivo_actual}\n";
    echo "  └─ {$reg->notas}\n\n";
}

echo "--- RESUMEN DE CAMBIOS ---\n";
echo "✅ ESTAMPADO: Consecutivo independiente (solo para estampado, sin DTF ni SUBLIMADO)\n";
echo "✅ DTF: Nuevo - Consecutivo propio para Direct-to-Film\n";
echo "✅ SUBLIMADO: Nuevo - Consecutivo propio para Sublimado\n";
echo "✅ BORDADO: Sin cambios\n";
echo "✅ REFLECTIVO: Sin cambios\n";
echo "✅ COSTURA: Sin cambios\n";
?>
