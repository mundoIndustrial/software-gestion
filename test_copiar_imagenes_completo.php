<?php
/**
 * Test: Copiar Imágenes de Cotización a Pedido - Flujo Completo
 * 
 * Verifica que cuando se crea un pedido desde una cotización:
 * 1. Se copien las fotos de prendas a prenda_fotos_pedido
 * 2. Se copien las fotos de telas a prenda_fotos_tela_pedido
 * 3. Se copien los logos a prenda_fotos_logo_pedido
 */

require_once 'bootstrap/app.php';

$app = require_once 'bootstrap/app.php';

use App\Models\Cotizacion;
use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\PrendaFotoPed;
use App\Models\PrendaTelaPed;
use App\Models\PrendaTalaFotoPed;
use App\Models\PrendaFotoLogoPed;
use App\Services\Pedidos\CotizacionDataExtractorService;
use App\Services\Pedidos\PedidoProduccionCreatorService;
use App\DTOs\CrearPedidoProduccionDTO;
use Illuminate\Support\Facades\DB;

DB::connection()->getPdo();

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  TEST: COPIAR IMÁGENES DE COTIZACIÓN A PEDIDO - FLUJO COMPLETO ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

try {
    // 1. OBTENER UNA COTIZACIÓN CON IMÁGENES
    echo "1️⃣  Buscando cotización con imágenes...\n";
    
    $cotizacion = Cotizacion::with([
        'prendas.fotos',
        'prendas.telaFotos',
        'prendas.logos'
    ])
    ->whereHas('prendas.fotos')
    ->first();

    if (!$cotizacion) {
        echo "❌ No hay cotizaciones con fotos disponibles\n";
        exit(1);
    }

    echo "✅ Cotización encontrada:\n";
    echo "   ID: {$cotizacion->id}\n";
    echo "   Número: {$cotizacion->numero_cotizacion}\n";
    echo "   Prendas: {$cotizacion->prendas()->count()}\n";
    echo "   Prendas con fotos:\n";
    
    $totalFotosEsperadas = 0;
    $totalFotosTelaEsperadas = 0;
    $totalLogosEsperados = 0;
    
    foreach ($cotizacion->prendas as $prenda) {
        $fotosCount = $prenda->fotos()->count();
        $fotosTelaCount = $prenda->telaFotos()->count();
        $logosCount = $prenda->logos()->count();
        
        echo "      • Prenda ID {$prenda->id}: {$fotosCount} fotos, {$fotosTelaCount} fotos de tela, {$logosCount} logos\n";
        
        $totalFotosEsperadas += $fotosCount;
        $totalFotosTelaEsperadas += $fotosTelaCount;
        $totalLogosEsperados += $logosCount;
    }
    
    echo "   📊 Totales esperados:\n";
    echo "      - Fotos de prendas: {$totalFotosEsperadas}\n";
    echo "      - Fotos de telas: {$totalFotosTelaEsperadas}\n";
    echo "      - Logos: {$totalLogosEsperados}\n\n";

    // 2. CREAR PEDIDO DESDE COTIZACIÓN
    echo "2️⃣  Creando pedido desde cotización...\n";
    
    $extractor = app(CotizacionDataExtractorService::class);
    $creador = app(PedidoProduccionCreatorService::class);
    
    // Extraer datos
    $datosExtraidos = $extractor->extraerDatos($cotizacion);
    
    // Crear DTO
    $dto = CrearPedidoProduccionDTO::fromRequest([
        'cotizacion_id' => $cotizacion->id,
        'prendas' => $datosExtraidos['prendas'],
        'cliente' => $datosExtraidos['cliente'],
        'cliente_id' => $datosExtraidos['cliente_id'],
    ]);
    
    // Crear pedido
    $asesorId = $cotizacion->asesor_id ?? 1;
    $pedido = $creador->crear($dto, $asesorId);
    
    echo "✅ Pedido creado:\n";
    echo "   ID: {$pedido->id}\n";
    echo "   Número: {$pedido->numero_pedido}\n";
    echo "   Cliente: {$pedido->cliente}\n\n";

    // 3. VERIFICAR PRENDAS COPIADAS
    echo "3️⃣  Verificando prendas del pedido...\n";
    
    $prendasPedido = $pedido->prendas()->get();
    echo "✅ Prendas en pedido: {$prendasPedido->count()}\n";
    echo "   Coinciden con cotización: " . ($prendasPedido->count() === $cotizacion->prendas()->count() ? 'SÍ ✓' : 'NO ✗') . "\n\n";

    // 4. VERIFICAR FOTOS DE PRENDAS COPIADAS
    echo "4️⃣  Verificando fotos de prendas...\n";
    
    $totalFotosCopiadas = PrendaFotoPed::whereIn(
        'prenda_ped_id',
        $prendasPedido->pluck('id')
    )->count();
    
    echo "✅ Fotos de prendas copiadas: {$totalFotosCopiadas}\n";
    echo "   Esperadas: {$totalFotosEsperadas}\n";
    echo "   ¡CORRECTO!: " . ($totalFotosCopiadas === $totalFotosEsperadas ? 'SÍ ✓' : 'NO ✗') . "\n";
    
    if ($totalFotosCopiadas > 0) {
        $primeraFoto = PrendaFotoPed::whereIn('prenda_ped_id', $prendasPedido->pluck('id'))->first();
        echo "   Ejemplo de URL copiada:\n";
        echo "   - Original: {$primeraFoto->ruta_original}\n";
        echo "   - WebP: {$primeraFoto->ruta_webp}\n";
    }
    echo "\n";

    // 5. VERIFICAR FOTOS DE TELAS COPIADAS
    echo "5️⃣  Verificando fotos de telas...\n";
    
    $totalFotosTelaCopiadas = PrendaTalaFotoPed::whereIn(
        'prenda_tela_ped_id',
        PrendaTelaPed::whereIn('prenda_ped_id', $prendasPedido->pluck('id'))->pluck('id')
    )->count();
    
    echo "✅ Fotos de telas copiadas: {$totalFotosTelaCopiadas}\n";
    echo "   Esperadas: {$totalFotosTelaEsperadas}\n";
    echo "   ¡CORRECTO!: " . ($totalFotosTelaCopiadas === $totalFotosTelaEsperadas ? 'SÍ ✓' : 'NO ✗') . "\n";
    
    if ($totalFotosTelaCopiadas > 0) {
        $primeraFotoTela = PrendaTalaFotoPed::whereIn(
            'prenda_tela_ped_id',
            PrendaTelaPed::whereIn('prenda_ped_id', $prendasPedido->pluck('id'))->pluck('id')
        )->first();
        echo "   Ejemplo de URL copiada:\n";
        echo "   - WebP: {$primeraFotoTela->ruta_webp}\n";
    }
    echo "\n";

    // 6. VERIFICAR LOGOS COPIADOS
    echo "6️⃣  Verificando logos...\n";
    
    $totalLogosCopiados = PrendaFotoLogoPed::whereIn(
        'prenda_ped_id',
        $prendasPedido->pluck('id')
    )->count();
    
    echo "✅ Logos copiados: {$totalLogosCopiados}\n";
    echo "   Esperados: {$totalLogosEsperados}\n";
    echo "   ¡CORRECTO!: " . ($totalLogosCopiados === $totalLogosEsperados ? 'SÍ ✓' : 'NO ✗') . "\n\n";

    // 7. RESUMEN
    echo "╔════════════════════════════════════════════════════════════════╗\n";
    echo "║                      ✅ RESUMEN COMPLETO                         ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n\n";
    
    $todoOk = ($totalFotosCopiadas === $totalFotosEsperadas) &&
              ($totalFotosTelaCopiadas === $totalFotosTelaEsperadas) &&
              ($totalLogosCopiados === $totalLogosEsperados);
    
    echo "COTIZACIÓN #{$cotizacion->numero_cotizacion}\n";
    echo "  • Prendas: {$cotizacion->prendas()->count()}\n";
    echo "  • Fotos totales: {$totalFotosEsperadas}\n";
    echo "  • Fotos de tela: {$totalFotosTelaEsperadas}\n";
    echo "  • Logos: {$totalLogosEsperados}\n\n";
    
    echo "PEDIDO #{$pedido->numero_pedido}\n";
    echo "  • Prendas: {$prendasPedido->count()}\n";
    echo "  • Fotos copiadas: {$totalFotosCopiadas} " . ($totalFotosCopiadas === $totalFotosEsperadas ? '✓' : '✗') . "\n";
    echo "  • Fotos de tela copiadas: {$totalFotosTelaCopiadas} " . ($totalFotosTelaCopiadas === $totalFotosTelaEsperadas ? '✓' : '✗') . "\n";
    echo "  • Logos copiados: {$totalLogosCopiados} " . ($totalLogosCopiados === $totalLogosEsperados ? '✓' : '✗') . "\n\n";
    
    if ($todoOk) {
        echo "🎉 ¡TODAS LAS IMÁGENES SE COPIARON CORRECTAMENTE!\n\n";
    } else {
        echo "⚠️  ALGUNAS IMÁGENES NO SE COPIARON CORRECTAMENTE\n\n";
    }

} catch (\Exception $e) {
    echo "❌ Error: {$e->getMessage()}\n";
    echo "Trace: {$e->getTraceAsString()}\n";
    exit(1);
}
