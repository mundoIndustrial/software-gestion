<?php
/**
 * TEST: Enviar cotización desde borrador y verificar que las fotos de tela se copien
 * 
 * Este test simula:
 * 1. Crear un draft con telas + fotos
 * 2. Enviar como cotización (con fotos_existentes)
 * 3. Verificar que prenda_tela_fotos_cot tenga registros
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

echo "\n=== TEST: FOTOS DE TELA EN ENVÍO ===\n";

// Cotizaciones de prueba
$cotizacionDraft = 54;  // Draft original
$cotizacionEnvio = 55;  // Cotización enviada

// ========== VERIFICACIÓN 1: Draft #54 ==========
echo "\n📋 DRAFT #$cotizacionDraft\n";

$prendasDraft = DB::table('prendas_cot')->where('cotizacion_id', $cotizacionDraft)->get();
echo "✅ Prendas: " . $prendasDraft->count() . "\n";

foreach ($prendasDraft as $prenda) {
    echo "\n  📦 Prenda ID: {$prenda->id} ({$prenda->nombre_producto})\n";
    
    // Telas
    $telas = DB::table('prenda_telas_cot')
        ->where('prenda_cot_id', $prenda->id)
        ->get();
    echo "    🧵 Telas: {$telas->count()}\n";
    
    // Fotos de telas
    foreach ($telas as $tela) {
        $fotos = DB::table('prenda_tela_fotos_cot')
            ->where('prenda_tela_cot_id', $tela->id)
            ->get();
        
        $color = DB::table('colores_prenda')->find($tela->color_id);
        $telaDb = DB::table('telas_prenda')->find($tela->tela_id);
        
        echo "      ├─ {$color->nombre} / {$telaDb->nombre}: {$fotos->count()} fotos\n";
        foreach ($fotos as $foto) {
            echo "        └─ {$foto->ruta_webp}\n";
        }
    }
}

// ========== VERIFICACIÓN 2: Cotización Enviada #55 ==========
echo "\n📋 COTIZACIÓN ENVIADA #$cotizacionEnvio\n";

$prendasEnvio = DB::table('prendas_cot')->where('cotizacion_id', $cotizacionEnvio)->get();
echo "✅ Prendas: " . $prendasEnvio->count() . "\n";

foreach ($prendasEnvio as $prenda) {
    echo "\n  📦 Prenda ID: {$prenda->id} ({$prenda->nombre_producto})\n";
    
    // Telas
    $telas = DB::table('prenda_telas_cot')
        ->where('prenda_cot_id', $prenda->id)
        ->get();
    echo "    🧵 Telas: {$telas->count()}\n";
    
    // Fotos de telas
    foreach ($telas as $tela) {
        $fotos = DB::table('prenda_tela_fotos_cot')
            ->where('prenda_tela_cot_id', $tela->id)
            ->get();
        
        $color = DB::table('colores_prenda')->find($tela->color_id);
        $telaDb = DB::table('telas_prenda')->find($tela->tela_id);
        
        echo "      ├─ {$color->nombre} / {$telaDb->nombre}: {$fotos->count()} fotos\n";
        foreach ($fotos as $foto) {
            echo "        └─ {$foto->ruta_webp}\n";
        }
    }
}

// ========== COMPARACIÓN ==========
echo "\n📊 COMPARACIÓN\n";
$fotosDraft = DB::table('prenda_tela_fotos_cot')
    ->whereIn('prenda_cot_id', $prendasDraft->pluck('id'))
    ->count();

$fotosEnvio = DB::table('prenda_tela_fotos_cot')
    ->whereIn('prenda_cot_id', $prendasEnvio->pluck('id'))
    ->count();

echo "Draft #$cotizacionDraft: $fotosDraft fotos\n";
echo "Envío #$cotizacionEnvio: $fotosEnvio fotos\n";

if ($fotosEnvio >= $fotosDraft) {
    echo "✅ ÉXITO: Las fotos se preservaron o aumentaron\n";
} else {
    echo "❌ FALLO: Las fotos se perdieron ($fotosEnvio < $fotosDraft)\n";
}

// ========== LOGS ==========
echo "\n📝 LOGS RECIENTES\n";
$logs = DB::table('logs')
    ->where('created_at', '>=', DB::raw("DATE_SUB(NOW(), INTERVAL 5 MINUTE)"))
    ->where('channel', 'local')
    ->orderBy('created_at', 'desc')
    ->limit(20)
    ->get();

foreach ($logs as $log) {
    if (strpos($log->message, 'Foto de tela') !== false || 
        strpos($log->message, 'PROCESANDO FOTOS EXISTENTES') !== false) {
        echo "  {$log->created_at}: {$log->message}\n";
    }
}

echo "\n=== FIN TEST ===\n\n";
