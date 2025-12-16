<?php
/**
 * Test para crear un pedido desde una cotización
 * Simula el flujo: asesores/cotizaciones/{id}/crear-pedido-produccion
 */

require_once 'bootstrap/app.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = \Illuminate\Http\Request::capture()
);

use App\Models\Cotizacion;
use App\Models\PedidoProduccion;
use App\DTOs\CrearPedidoProduccionDTO;
use App\Services\Pedidos\PedidoProduccionCreatorService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

// Conectar a la BD
DB::connection()->getPdo();

echo "=== TEST CREAR PEDIDO DESDE COTIZACIÓN ===\n\n";

try {
    // 1. Obtener una cotización con prendas
    $cot = Cotizacion::with('prendas')->whereHas('prendas')->first();
    
    if (!$cot) {
        echo "❌ No hay cotizaciones con prendas disponibles\n";
        exit(1);
    }
    
    echo "✓ Cotización encontrada: ID=" . $cot->id . ", Prendas=" . $cot->prendas->count() . "\n";
    
    // 2. Crear DTO desde cotización
    $dto = CrearPedidoProduccionDTO::fromCotizacion($cot);
    
    echo "✓ DTO creado con " . count($dto->prendasValidas()) . " prendas\n";
    
    // 3. Crear servicio
    $service = app(PedidoProduccionCreatorService::class);
    
    echo "✓ Servicio instanciado\n";
    
    // 4. Crear pedido
    $asesorId = 1; // Admin
    $pedido = $service->crear($dto, $asesorId);
    
    echo "✓ Pedido creado exitosamente!\n";
    echo "  - ID: " . $pedido->id . "\n";
    echo "  - Número: " . $pedido->numero_pedido . "\n";
    echo "  - Estado: " . $pedido->estado . "\n";
    
    // 5. Verificar prendas guardadas
    $prendasCount = DB::table('prendas_pedido')
        ->where('numero_pedido', $pedido->numero_pedido)
        ->count();
    
    echo "  - Prendas guardadas: " . $prendasCount . "\n";
    
    // 6. Verificar fotos
    $fotosCount = DB::table('prenda_fotos_pedido')
        ->whereIn('prenda_pedido_id', 
            DB::table('prendas_pedido')
                ->where('numero_pedido', $pedido->numero_pedido)
                ->pluck('id')
        )
        ->count();
    
    echo "  - Fotos de prendas: " . $fotosCount . "\n";
    
    // 7. Verificar logos
    $logosCount = DB::table('prenda_fotos_logo_pedido')
        ->whereIn('prenda_pedido_id', 
            DB::table('prendas_pedido')
                ->where('numero_pedido', $pedido->numero_pedido)
                ->pluck('id')
        )
        ->count();
    
    echo "  - Logos de prendas: " . $logosCount . "\n";
    
    // 8. Verificar telas
    $telasCount = DB::table('prenda_fotos_tela_pedido')
        ->whereIn('prenda_pedido_id', 
            DB::table('prendas_pedido')
                ->where('numero_pedido', $pedido->numero_pedido)
                ->pluck('id')
        )
        ->count();
    
    echo "  - Fotos de telas: " . $telasCount . "\n";
    
    echo "\n✅ TEST EXITOSO\n";
    
} catch (\InvalidArgumentException $e) {
    echo "❌ Error de validación: " . $e->getMessage() . "\n";
    exit(1);
} catch (\Throwable $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "   " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "   " . $e->getTraceAsString() . "\n";
    exit(1);
}
