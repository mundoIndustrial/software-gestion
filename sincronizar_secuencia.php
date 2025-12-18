<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Cotizacion;

echo "Sincronizando secuencia global con últimas cotizaciones...\n\n";

// Obtener la última cotización
$ultimaCotizacion = Cotizacion::whereNotNull('numero_cotizacion')
    ->orderBy('id', 'desc')
    ->first();

if ($ultimaCotizacion) {
    // Extraer el número
    preg_match('/(\d+)/', $ultimaCotizacion->numero_cotizacion, $matches);
    $ultimoNumero = (int)$matches[1];
    $proximoNumero = $ultimoNumero + 1;
    
    echo "✓ Última cotización en BD: {$ultimaCotizacion->numero_cotizacion}\n";
    echo "✓ Próximo número a usar: $proximoNumero\n\n";
    
    // Actualizar la secuencia global
    DB::table('cotizacion_secuencias')
        ->where('tipo', 'global')
        ->update(['siguiente_numero' => $proximoNumero]);
    
    echo "✅ Secuencia actualizada correctamente\n\n";
    
    // Verificar
    $secuencia = DB::table('cotizacion_secuencias')
        ->where('tipo', 'global')
        ->first();
    
    echo "Estado actual de cotizacion_secuencias:\n";
    echo "  Tipo: {$secuencia->tipo}\n";
    echo "  Siguiente número: {$secuencia->siguiente_numero}\n";
} else {
    echo "❌ No hay cotizaciones en la base de datos\n";
    exit(1);
}
