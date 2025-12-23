<?php
/**
 * TEST: Crear nueva cotización con logos y verificar el flujo completo
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

use Illuminate\Support\Facades\DB;

// Inicializar
$app->make(\Illuminate\Contracts\Http\Kernel::class)->handle(
    \Illuminate\Http\Request::capture()
);

echo "═══════════════════════════════════════════════════════════════════════\n";
echo "🧪 TEST: CREAR COTIZACIÓN Y VERIFICAR LOGOS\n";
echo "═══════════════════════════════════════════════════════════════════════\n\n";

// 1. Buscar cotizaciones BORRADOR recientes
echo "1️⃣ Buscando cotizaciones BORRADOR...\n\n";

$cotizaciones = DB::table('cotizaciones')
    ->where('es_borrador', true)
    ->where('estado', 'BORRADOR')
    ->orderBy('created_at', 'DESC')
    ->limit(5)
    ->get();

if (count($cotizaciones) === 0) {
    echo "❌ No hay cotizaciones BORRADOR\n\n";
    
    echo "💡 SOLUCIÓN:\n";
    echo "   1. Abre el navegador\n";
    echo "   2. Ve a http://localhost/cotizaciones/crear\n";
    echo "   3. Completa: Cliente MINCIVIL, 1 producto, 1 tela con foto, agregar logo con 3 fotos\n";
    echo "   4. Haz clic en GUARDAR (no enviar)\n";
    echo "   5. Vuelve a ejecutar este script\n";
    exit(1);
}

echo "✅ Cotizaciones BORRADOR encontradas: " . count($cotizaciones) . "\n\n";

// Usar la más reciente
$cotizacion = $cotizaciones[0];

echo "📋 Usando cotización más reciente:\n";
echo "   - ID: {$cotizacion->id}\n";
echo "   - Cliente: {$cotizacion->cliente_id}\n";
echo "   - Estado: {$cotizacion->estado}\n";
echo "   - Es Borrador: {$cotizacion->es_borrador}\n\n";

// 2. Obtener logo
echo "2️⃣ Buscando logo asociado...\n\n";

$logo = DB::table('logo_cotizaciones')
    ->where('cotizacion_id', $cotizacion->id)
    ->first();

if (!$logo) {
    echo "❌ No hay logo en esta cotización\n";
    exit(1);
}

echo "✅ Logo encontrado: ID {$logo->id}\n\n";

// 3. Obtener fotos del logo
echo "3️⃣ Obteniendo fotos del logo...\n\n";

$fotos = DB::table('logo_fotos_cot')
    ->where('logo_cotizacion_id', $logo->id)
    ->orderBy('orden')
    ->get();

if (count($fotos) === 0) {
    echo "❌ No hay fotos en el logo\n";
    exit(1);
}

echo "✅ Fotos encontradas: " . count($fotos) . "\n";
foreach ($fotos as $foto) {
    echo "   - ID {$foto->id}: {$foto->ruta_webp}\n";
}
echo "\n";

// 4. Rutas que se enviarían
echo "4️⃣ Rutas que debería enviar el frontend...\n\n";

$rutasFotos = $fotos->pluck('ruta_webp')->toArray();

foreach ($rutasFotos as $idx => $ruta) {
    echo "   [{$idx}] {$ruta}\n";
}
echo "\n";

// 5. Instrucciones
echo "5️⃣ PRÓXIMO PASO - Test Manual en Navegador:\n\n";

echo "A. Abre la console del navegador (F12)\n";
echo "B. Filtra logs por 'logo_fotos_guardadas' o 'Encontradas imágenes'\n";
echo "C. Haz clic en ENVIAR y observa:\n";
echo "   ✓ 'Encontradas imágenes existentes en galería: " . count($fotos) . "'\n";
echo "   ✓ 'Ruta de foto existente agregada' x " . count($fotos) . "\n";
echo "D. Mira la pestaña Network → encuentra el POST request\n";
echo "E. En Form Data, busca: logo_fotos_guardadas\n";
echo "F. Debería haber " . count($fotos) . " valores\n\n";

echo "═══════════════════════════════════════════════════════════════════════\n";
echo "📍 INFORMACIÓN CRÍTICA PARA DEBUGGING:\n";
echo "═══════════════════════════════════════════════════════════════════════\n\n";

echo "Cotización ID: {$cotizacion->id}\n";
echo "Logo ID: {$logo->id}\n";
echo "Fotos esperadas: " . count($fotos) . "\n";
echo "Rutas esperadas:\n";
foreach ($rutasFotos as $ruta) {
    echo "  - {$ruta}\n";
}

echo "\n✨ Después de hacer clic en ENVIAR, revisa laravel.log y busca:\n";
echo "   'DEBUG - Fotos de logo a conservar (procesadas)'\n";
echo "   Debe mostrar: fotos_guardadas_count: " . count($fotos) . "\n\n";

echo "═══════════════════════════════════════════════════════════════════════\n";
?>
