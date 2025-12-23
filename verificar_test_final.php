<?php
/**
 * TEST FINAL: Verificar que las fotos se envían correctamente
 * Ejecutar este script DESPUÉS de hacer clic en ENVIAR en el navegador
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$app->make(\Illuminate\Contracts\Http\Kernel::class)->handle(
    \Illuminate\Http\Request::capture()
);

echo "═══════════════════════════════════════════════════════════════════════\n";
echo "✅ TEST FINAL: VERIFICAR ENVÍO DE LOGOS\n";
echo "═══════════════════════════════════════════════════════════════════════\n\n";

// 1. Obtener cotización 16
$cotizacion = DB::table('cotizaciones')->where('id', 16)->first();

echo "1️⃣ Estado de cotización 16:\n";
echo "   - Estado: {$cotizacion->estado}\n";
echo "   - Es Borrador: {$cotizacion->es_borrador}\n";
echo "   - Número: {$cotizacion->numero_cotizacion}\n";
echo "   - Última actualización: {$cotizacion->updated_at}\n\n";

// 2. Obtener logo
$logo = DB::table('logo_cotizaciones')->where('id', 15)->first();

echo "2️⃣ Logo asociado:\n";
echo "   - ID: {$logo->id}\n";
echo "   - Descripción: {$logo->descripcion}\n";
echo "   - Técnicas: {$logo->tecnicas}\n\n";

// 3. Fotos actuales
$fotos = DB::table('logo_fotos_cot')
    ->where('logo_cotizacion_id', 15)
    ->orderBy('orden')
    ->get();

echo "3️⃣ Fotos en BD AHORA:\n";
echo "   Total: " . count($fotos) . "\n";

if (count($fotos) > 0) {
    echo "   ✅ FOTOS CONSERVADAS:\n";
    foreach ($fotos as $foto) {
        echo "      [{$foto->orden}] ID {$foto->id}: {$foto->ruta_webp}\n";
    }
} else {
    echo "   ❌ SIN FOTOS - FUERON ELIMINADAS\n";
}

echo "\n";

// 4. Revisar logs
echo "4️⃣ Último log de DEBUG...\n\n";

// Leer últimas líneas del log
$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    $lines = file($logFile);
    $ultimasLineas = array_slice($lines, -50);
    
    // Buscar líneas relevantes
    $encontrado = false;
    foreach ($ultimasLineas as $linea) {
        if (strpos($linea, 'Fotos de logo a conservar') !== false ||
            strpos($linea, 'fotos_guardadas_count') !== false ||
            strpos($linea, 'fotos_a_conservar_count') !== false ||
            strpos($linea, 'Foto de logo ELIMINADA') !== false) {
            
            echo $linea;
            $encontrado = true;
        }
    }
    
    if (!$encontrado) {
        echo "⚠️ No se encontraron logs recientes de fotos de logo\n";
        echo "   Los últimos logs son:\n";
        foreach (array_slice($ultimasLineas, -5) as $linea) {
            echo "   " . trim($linea) . "\n";
        }
    }
}

echo "\n═══════════════════════════════════════════════════════════════════════\n";
echo "📊 RESUMEN FINAL:\n";
echo "═══════════════════════════════════════════════════════════════════════\n\n";

if (count($fotos) === 3) {
    echo "✅✅✅ ÉXITO - Las 3 fotos fueron PRESERVADAS\n";
    echo "    El fix está funcionando correctamente\n";
} elseif (count($fotos) === 0) {
    echo "❌❌❌ FALLO - Las fotos fueron ELIMINADAS\n";
    echo "    El frontend NO está enviando logo_fotos_guardadas[]\n";
    echo "    Pasos a verificar:\n";
    echo "    1. Abre DevTools (F12)\n";
    echo "    2. Ve a Network → busca el POST a cotizaciones\n";
    echo "    3. En Form Data, busca 'logo_fotos_guardadas'\n";
    echo "    4. Si NO está, el problema es en guardado.js\n";
} else {
    echo "⚠️ PARCIAL - Se conservaron " . count($fotos) . " de 3 fotos\n";
}

echo "\n═══════════════════════════════════════════════════════════════════════\n";
?>
