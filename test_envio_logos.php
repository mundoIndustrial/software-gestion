<?php
/**
 * TEST: Debugging completo del envío de logos
 * Simula exactamente lo que hace el frontend y verifica qué se recibe en el backend
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

// Inicializar la aplicación
$app->make(\Illuminate\Contracts\Http\Kernel::class)->handle(
    $request = \Illuminate\Http\Request::capture()
);

echo "═══════════════════════════════════════════════════════════════════════\n";
echo "🧪 TEST: DEBUGGEANDO ENVÍO DE LOGOS\n";
echo "═══════════════════════════════════════════════════════════════════════\n\n";

// 1. Obtener la cotización existente (ID 16 con logos)
echo "1️⃣ Obteniendo cotización ID 16...\n";
$cotizacion = DB::table('cotizaciones')->where('id', 16)->first();
if (!$cotizacion) {
    echo "❌ Cotización 16 no encontrada\n";
    exit(1);
}

echo "✅ Cotización encontrada:\n";
echo "   - ID: {$cotizacion->id}\n";
echo "   - Cliente: {$cotizacion->cliente_id}\n";
echo "   - Estado: {$cotizacion->estado}\n\n";

// 2. Obtener el logo de la cotización
echo "2️⃣ Obteniendo logo asociado...\n";
$logo = DB::table('logo_cotizaciones')->where('cotizacion_id', 16)->first();
if (!$logo) {
    echo "❌ Logo no encontrado para cotización 16\n";
    exit(1);
}

echo "✅ Logo encontrado:\n";
echo "   - ID: {$logo->id}\n";
echo "   - Descripción: {$logo->descripcion}\n\n";

// 3. Obtener fotos del logo
echo "3️⃣ Obteniendo fotos del logo...\n";
$fotos = DB::table('logo_fotos_cot')->where('logo_cotizacion_id', $logo->id)->get();
echo "✅ Fotos encontradas: " . count($fotos) . "\n";
foreach ($fotos as $idx => $foto) {
    echo "   [{$idx}] ID: {$foto->id}, Ruta: {$foto->ruta_webp}\n";
}
echo "\n";

// 4. Simular lo que envía el frontend
echo "4️⃣ Simulando parámetros que enviaría el frontend...\n\n";

// Rutas de las fotos
$rutasFotos = [];
foreach ($fotos as $foto) {
    $rutasFotos[] = $foto->ruta_webp;
}

echo "📤 Parámetros que se enviarían (logo_fotos_guardadas[]):\n";
foreach ($rutasFotos as $idx => $ruta) {
    echo "   [{$idx}] {$ruta}\n";
}
echo "\n";

// 5. Procesar como lo hace el backend
echo "5️⃣ Procesando como lo hace el backend...\n\n";

// Simular el array como si viniera del formulario
$fotosLogoGuardadas = $rutasFotos;

if (!is_array($fotosLogoGuardadas)) {
    $fotosLogoGuardadas = $fotosLogoGuardadas ? [$fotosLogoGuardadas] : [];
}

echo "✅ Después de validar array: " . count($fotosLogoGuardadas) . " fotos\n";

// Limpiar rutas como lo hace el backend
$fotosLogoGuardadas = array_map(function($ruta) {
    echo "   🔄 Procesando ruta: $ruta\n";
    
    // Si empieza con /storage/, dejarlo como está
    if (strpos($ruta, 'http') === 0) {
        // Es una URL completa
        if (preg_match('#/storage/(.+)$#', $ruta, $matches)) {
            $resultado = '/storage/' . $matches[1];
            echo "      ➜ Era URL, resultado: $resultado\n";
            return $resultado;
        }
    }
    echo "      ➜ Se deja igual: $ruta\n";
    return $ruta;
}, $fotosLogoGuardadas);

echo "\n✅ Después de limpiar rutas: " . count($fotosLogoGuardadas) . " fotos\n";
foreach ($fotosLogoGuardadas as $idx => $ruta) {
    echo "   [{$idx}] {$ruta}\n";
}
echo "\n";

// 6. Verificar qué ruta guardada en BD
echo "6️⃣ Comparando con rutas en BD...\n\n";

$match_count = 0;
foreach ($fotosLogoGuardadas as $rutaEnviada) {
    $existe = DB::table('logo_fotos_cot')
        ->where('logo_cotizacion_id', $logo->id)
        ->where(function($q) use ($rutaEnviada) {
            $q->where('ruta_webp', $rutaEnviada)
              ->orWhere('ruta_original', $rutaEnviada)
              ->orWhere('ruta_webp', 'LIKE', '%' . basename($rutaEnviada));
        })
        ->exists();
    
    if ($existe) {
        echo "✅ Ruta enviada ENCONTRADA EN BD: {$rutaEnviada}\n";
        $match_count++;
    } else {
        echo "❌ Ruta enviada NO ENCONTRADA EN BD: {$rutaEnviada}\n";
    }
}

echo "\n📊 RESUMEN: {$match_count}/" . count($fotosLogoGuardadas) . " rutas encontradas\n\n";

// 7. Simular servicio de eliminación
echo "7️⃣ Simulando EliminarImagenesCotizacionService...\n\n";

$fotosEnBD = DB::table('logo_fotos_cot')
    ->where('logo_cotizacion_id', $logo->id)
    ->get();

echo "Fotos en BD: " . count($fotosEnBD) . "\n";
foreach ($fotosEnBD as $foto) {
    echo "   - ID {$foto->id}: {$foto->ruta_webp}\n";
}

echo "\nFotos a conservar: " . count($fotosLogoGuardadas) . "\n";
foreach ($fotosLogoGuardadas as $ruta) {
    echo "   - {$ruta}\n";
}

// Fotos a eliminar (las que NO están en la lista a conservar)
echo "\nFotos a ELIMINAR (no en lista conservar):\n";
$fotosAEliminar = [];
foreach ($fotosEnBD as $foto) {
    $debeConservarse = false;
    
    foreach ($fotosLogoGuardadas as $rutaConservada) {
        // Comparar flexible: puede ser ruta completa, relativa, o solo basename
        if (strpos($foto->ruta_webp, $rutaConservada) !== false ||
            strpos($rutaConservada, basename($foto->ruta_webp)) !== false ||
            $foto->ruta_webp === $rutaConservada) {
            $debeConservarse = true;
            break;
        }
    }
    
    if (!$debeConservarse) {
        $fotosAEliminar[] = $foto;
        echo "   ❌ ID {$foto->id}: {$foto->ruta_webp}\n";
    } else {
        echo "   ✅ ID {$foto->id}: {$foto->ruta_webp} (CONSERVADA)\n";
    }
}

echo "\n📊 TOTAL A ELIMINAR: " . count($fotosAEliminar) . "\n";
echo "📊 TOTAL A CONSERVAR: " . (count($fotosEnBD) - count($fotosAEliminar)) . "\n\n";

// 8. Problema probable
echo "8️⃣ ANÁLISIS DEL PROBLEMA...\n\n";

// Check si los atributos data-foto-guardada se están agregando correctamente
echo "❓ ¿Las fotos tienen data-foto-guardada='true'?\n";
echo "   → Verifique en cargar-borrador.js línea ~1390\n";
echo "   → El div debe tener: div.setAttribute('data-foto-guardada', 'true')\n\n";

// Check si data-ruta se está agregando
echo "❓ ¿Las fotos tienen data-ruta con la ruta correcta?\n";
echo "   → Verifique en cargar-borrador.js línea ~1404\n";
echo "   → El img debe tener: img.setAttribute('data-ruta', rutaFoto)\n\n";

// Check si guardado.js está encontrando las fotos
echo "❓ ¿guardado.js está encontrando las fotos?\n";
echo "   → Abra console.log en navegador\n";
echo "   → Debería ver: 'Encontradas imágenes existentes en galería: 3'\n\n";

echo "═══════════════════════════════════════════════════════════════════════\n";
echo "✨ TEST COMPLETADO\n";
echo "═══════════════════════════════════════════════════════════════════════\n";
?>
