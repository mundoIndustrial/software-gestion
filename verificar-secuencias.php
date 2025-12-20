<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "═══════════════════════════════════════════════════════════════\n";
echo "           TABLA numero_secuencias\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$secuencias = DB::table('numero_secuencias')->orderBy('tipo')->get();
foreach ($secuencias as $sec) {
    printf("  %-30s → %d\n", $sec->tipo, $sec->siguiente_numero);
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "           ÚLTIMAS 10 COTIZACIONES CREADAS\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$cotizaciones = DB::table('cotizaciones')
    ->orderBy('id', 'desc')
    ->limit(10)
    ->get(['id', 'numero_cotizacion', 'tipo', 'estado', 'created_at']);

foreach ($cotizaciones as $cot) {
    printf("  ID: %-5s | %-15s | Tipo: %-3s | Estado: %-20s | %s\n", 
        $cot->id, 
        $cot->numero_cotizacion, 
        $cot->tipo,
        $cot->estado,
        $cot->created_at
    );
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "           ANÁLISIS\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$ultimaCotizacion = DB::table('cotizaciones')->orderBy('id', 'desc')->first();
if ($ultimaCotizacion) {
    // Extraer el número del formato COT-XXXXX
    preg_match('/COT-(\d+)/', $ultimaCotizacion->numero_cotizacion, $matches);
    $numeroReal = isset($matches[1]) ? intval($matches[1]) : 0;
    
    echo "  Última cotización creada:\n";
    echo "    → ID: {$ultimaCotizacion->id}\n";
    echo "    → Número: {$ultimaCotizacion->numero_cotizacion}\n";
    echo "    → Consecutivo extraído: {$numeroReal}\n";
    echo "\n";
    
    $secuenciaUniversal = DB::table('numero_secuencias')->where('tipo', 'cotizaciones_universal')->first();
    if ($secuenciaUniversal) {
        echo "  Secuencia 'cotizaciones_universal':\n";
        echo "    → Siguiente número: {$secuenciaUniversal->siguiente_numero}\n";
        echo "\n";
        
        $diferencia = $secuenciaUniversal->siguiente_numero - $numeroReal;
        if ($diferencia == 1) {
            echo "  ✅ ESTADO: SINCRONIZADO\n";
            echo "     La secuencia está correcta (siguiente = último + 1)\n";
        } else {
            echo "  ⚠️  ESTADO: DESINCRONIZADO\n";
            echo "     Diferencia: {$diferencia}\n";
            echo "     Siguiente número esperado: " . ($numeroReal + 1) . "\n";
        }
    }
}

echo "\n═══════════════════════════════════════════════════════════════\n";
