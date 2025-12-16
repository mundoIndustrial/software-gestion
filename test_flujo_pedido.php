<?php
/**
 * Test simple para verificar el flujo de creación de pedido
 * desde una cotización existente
 */

require_once __DIR__ . '/vendor/autoload.php';

// Inicializar Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use App\Models\Cotizacion;
use App\Services\Pedidos\CotizacionDataExtractorService;

echo "\n=== TEST: Extracción de Datos de Cotización ===\n\n";

try {
    // Obtener una cotización que tenga prendas
    $cotizacion = Cotizacion::whereHas('prendasCotizaciones')->first();
    
    if (!$cotizacion) {
        // Si no hay con prendas, buscar cualquiera
        $cotizacion = Cotizacion::first();
        if (!$cotizacion) {
            echo "❌ No hay cotizaciones en la base de datos\n";
            exit(1);
        }
        echo "⚠️  Cotización sin prendas (la más antigua): #{$cotizacion->id}\n";
    }
    
    echo "✅ Cotización encontrada: #{$cotizacion->id} - {$cotizacion->numero_cotizacion}\n";
    echo "   Cliente: {$cotizacion->cliente?->nombre}\n";
    echo "   Asesor: {$cotizacion->asesor_id}\n\n";
    
    // Instanciar el extractor
    $extractor = app(CotizacionDataExtractorService::class);
    
    // Extraer datos
    $datosExtraidos = $extractor->extraerDatos($cotizacion);
    
    echo "📦 Datos Extraídos:\n";
    echo "   Cotización ID: {$datosExtraidos['cotizacion_id']}\n";
    echo "   Número: {$datosExtraidos['numero_cotizacion']}\n";
    echo "   Cliente ID: {$datosExtraidos['cliente_id']}\n";
    echo "   Cliente: {$datosExtraidos['cliente']}\n";
    echo "   Asesor ID: {$datosExtraidos['asesor_id']}\n";
    echo "   Total Prendas: " . count($datosExtraidos['prendas']) . "\n\n";
    
    // Analizar cada prenda
    foreach ($datosExtraidos['prendas'] as $prenda) {
        echo "👕 Prenda #{$prenda['index']}:\n";
        echo "   Nombre: {$prenda['nombre_producto']}\n";
        echo "   Descripción: " . (strlen($prenda['descripcion'] ?? '') > 50 
            ? substr($prenda['descripcion'] ?? '', 0, 50) . "..." 
            : ($prenda['descripcion'] ?? 'Sin descripción')) . "\n";
        echo "   Tela: {$prenda['tela']} ({$prenda['tela_referencia']})\n";
        echo "   Color: {$prenda['color']}\n";
        echo "   Género: {$prenda['genero']}\n";
        echo "   Manga: {$prenda['manga']}\n";
        echo "   Bolsillos: " . ($prenda['tiene_bolsillos'] ? 'Sí' : 'No') . "\n";
        echo "   Reflectivo: " . ($prenda['tiene_reflectivo'] ? 'Sí' : 'No') . "\n";
        echo "   Cantidades: " . json_encode($prenda['cantidades']) . "\n";
        echo "   Total Fotos: " . count($prenda['fotos']) . "\n\n";
    }
    
    echo "✅ Extracción completada exitosamente\n\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}
