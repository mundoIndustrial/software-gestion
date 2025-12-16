<?php
/**
 * Script de prueba integral
 * Crea una cotización completa y prueba el flujo de creación de pedido
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use App\Models\Cotizacion;
use App\Models\PrendaCot;
use App\Models\PrendaVarianteCot;
use App\Models\PrendaTelaCot;
use App\Models\PrendaTallaCot;
use App\Models\PrendaFotoCot;
use App\Models\Cliente;
use App\Models\User;
use App\Models\TelaPrenda;
use App\Models\ColorPrenda;
use App\Services\Pedidos\CotizacionDataExtractorService;
use App\DTOs\CrearPedidoProduccionDTO;

echo "\n=== TEST INTEGRAL: Crear Cotización y Pedido ===\n\n";

try {
    // 1. Crear o buscar cliente
    $cliente = Cliente::first() ?? Cliente::create(['nombre' => 'TEST CLIENTE']);
    echo "✅ Cliente: {$cliente->nombre}\n";
    
    // 2. Obtener asesor
    $asesor = User::find(18); // Asesor con ID 18
    if (!$asesor) {
        echo "❌ No encontré usuario con ID 18\n";
        exit(1);
    }
    echo "✅ Asesor: {$asesor->name}\n\n";
    
    // 3. Crear cotización
    $cotizacion = Cotizacion::create([
        'numero_cotizacion' => 'COT-TEST-' . date('YmdHis'),
        'cliente_id' => $cliente->id,
        'asesor_id' => $asesor->id,
        'estado' => 'confirmada',
    ]);
    echo "✅ Cotización creada: #{$cotizacion->id} - {$cotizacion->numero_cotizacion}\n\n";
    
    // 4. Crear prenda
    $prenda = PrendaCot::create([
        'cotizacion_id' => $cotizacion->id,
        'nombre_producto' => 'CAMISA DRILL TEST',
        'descripcion' => 'Camisa de prueba con logo bordado',
        'cantidad' => 1,
    ]);
    echo "✅ Prenda creada: #{$prenda->id}\n";
    
    // 5. Crear variante
    $variante = PrendaVarianteCot::create([
        'prenda_cot_id' => $prenda->id,
        'tipo_prenda' => 'CAMISA',
        'genero_id' => 1, // Masculino o similar
        'tipo_manga' => 'LARGA',
        'tipo_manga_id' => 1,
        'tiene_bolsillos' => true,
        'obs_bolsillos' => 'BOLSILLOS CON TAPA',
        'tipo_broche_id' => 1,
        'obs_broche' => 'BOTONES',
        'tiene_reflectivo' => false,
        'color' => 'NARANJA',
        'descripcion_adicional' => 'Logo bordado en espalda',
    ]);
    echo "✅ Variante creada: #{$variante->id}\n";
    
    // 6. Crear tela y color en sus tablas respectivas
    $color = ColorPrenda::firstOrCreate(
        ['nombre' => 'NARANJA'],
        ['nombre' => 'NARANJA', 'codigo' => 'NAR', 'activo' => true]
    );
    
    $tela = TelaPrenda::firstOrCreate(
        ['nombre' => 'DRILL BORNEO'],
        ['nombre' => 'DRILL BORNEO', 'referencia' => 'REF-DB-001', 'activo' => true]
    );
    
    echo "✅ Color creado: #{$color->id} - {$color->nombre}\n";
    echo "✅ Tela creada: #{$tela->id} - {$tela->nombre}\n";
    
    // 7. Crear relación prenda-tela-color
    $prendaTela = PrendaTelaCot::create([
        'prenda_cot_id' => $prenda->id,
        'variante_prenda_cot_id' => $variante->id,
        'color_id' => $color->id,
        'tela_id' => $tela->id,
    ]);
    echo "✅ Relación Prenda-Tela-Color creada: #{$prendaTela->id}\n";
    
    // 8. Crear tallas
    PrendaTallaCot::create(['prenda_cot_id' => $prenda->id, 'talla' => 'S', 'cantidad' => 50]);
    PrendaTallaCot::create(['prenda_cot_id' => $prenda->id, 'talla' => 'M', 'cantidad' => 50]);
    PrendaTallaCot::create(['prenda_cot_id' => $prenda->id, 'talla' => 'L', 'cantidad' => 50]);
    echo "✅ Tallas creadas: S:50, M:50, L:50\n\n";
    
    // 9. Extraer datos
    $extractor = app(CotizacionDataExtractorService::class);
    $datosExtraidos = $extractor->extraerDatos($cotizacion->fresh());
    
    echo "📦 Datos Extraídos:\n";
    echo "   Total Prendas: " . count($datosExtraidos['prendas']) . "\n";
    echo "   Cliente: {$datosExtraidos['cliente']}\n";
    echo "   Asesor ID: {$datosExtraidos['asesor_id']}\n\n";
    
    if (count($datosExtraidos['prendas']) > 0) {
        $prenda = $datosExtraidos['prendas'][0];
        echo "Primera Prenda:\n";
        echo "   Nombre: {$prenda['nombre_producto']}\n";
        echo "   Tela: {$prenda['tela']}\n";
        echo "   Color: {$prenda['color']}\n";
        echo "   Manga: {$prenda['manga']}\n";
        echo "   Bolsillos: " . ($prenda['tiene_bolsillos'] ? 'Sí' : 'No') . "\n";
        echo "   Cantidades: " . json_encode($prenda['cantidades']) . "\n\n";
    }
    
    // 10. Crear DTO
    $dto = CrearPedidoProduccionDTO::fromRequest([
        'cotizacion_id' => $cotizacion->id,
        'prendas' => $datosExtraidos['prendas'],
        'cliente' => $datosExtraidos['cliente'],
        'cliente_id' => $datosExtraidos['cliente_id'],
    ]);
    
    echo "✅ DTO creado\n";
    echo "   Es válido: " . ($dto->esValido() ? 'Sí' : 'No') . "\n";
    echo "   Prendas válidas: " . count($dto->prendasValidas()) . "\n\n";
    
    if ($dto->esValido()) {
        // 10. Crear pedido via service
        $pedidoCreator = app(\App\Services\Pedidos\PedidoProduccionCreatorService::class);
        $pedido = $pedidoCreator->crear($dto, $asesor->id);
        
        echo "✅ PEDIDO CREADO EXITOSAMENTE\n";
        echo "   ID: {$pedido->id}\n";
        echo "   Numero: {$pedido->numero_pedido}\n";
        echo "   Cliente: {$pedido->cliente}\n";
        echo "   Prendas: {$pedido->prendas()->count()}\n\n";
        
        // 11. Verificar prendas guardadas
        foreach ($pedido->prendas()->get() as $i => $prenda) {
            echo "PRENDA #" . ($i + 1) . ":\n";
            echo "  - Descripción (primeros 150 chars):\n";
            echo "    " . substr($prenda->descripcion, 0, 150) . "\n";
            echo "  - Color ID: {$prenda->color_id}\n";
            echo "  - Tela ID: {$prenda->tela_id}\n";
            echo "  - Tipo Manga ID: {$prenda->tipo_manga_id}\n";
            echo "  - Bolsillos: " . ($prenda->tiene_bolsillos ? 'Sí' : 'No') . "\n";
            echo "  - Reflectivo: " . ($prenda->tiene_reflectivo ? 'Sí' : 'No') . "\n\n";
        }
        
        echo "✅ TEST COMPLETADO EXITOSAMENTE\n";
    } else {
        echo "❌ DTO no válido\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "   Línea: " . $e->getLine() . "\n";
    echo "   Archivo: " . $e->getFile() . "\n\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
