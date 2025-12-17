<?php
require 'vendor/autoload.php';

use App\Models\Cotizacion;
use App\Models\PedidoProduccion;
use App\Services\Pedidos\CotizacionDataExtractorService;
use App\Application\Services\PedidoPrendaService;
use Illuminate\Support\Facades\DB;

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== TEST COMPLETO: COTIZACIÓN → PEDIDO ===\n\n";

// Obtener última cotización
$cotizacion = Cotizacion::latest('id')->first();

if (!$cotizacion) {
    echo "❌ No hay cotizaciones\n";
    exit(1);
}

echo "Cotización: #" . $cotizacion->numero_cotizacion . "\n";

// Crear instancia del extractor
$extractor = new CotizacionDataExtractorService();

// Extraer datos
$datosExtraidos = $extractor->extraerDatos($cotizacion);

echo "Prendas extraídas: " . count($datosExtraidos['prendas']) . "\n\n";

// Crear pedido directamente
$numeroMaximo = DB::table('pedidos_produccion')->max('numero_pedido') ?? 0;
$numeroPedido = $numeroMaximo + 1;

// Obtener un asesor real (el de la cotización original)
$asesorId = $cotizacion->asesor_id ?? null;

if (!$asesorId) {
    // Si no hay asesor en cotización, obtener cualquiera
    $asesorId = DB::table('users')->first()?->id ?? null;
}

if (!$asesorId) {
    echo "⚠ No hay asesores en la BD, usando NULL\n";
}

$pedido = PedidoProduccion::create([
    'numero_pedido' => $numeroPedido,
    'cliente_id' => $datosExtraidos['cliente_id'],
    'cotizacion_id' => $cotizacion->id,
    'asesor_id' => $asesorId,
    'estado' => 'Pendiente',
]);

// Guardar prendas
$prendaService = app(PedidoPrendaService::class);
$prendaService->guardarPrendasEnPedido($pedido, $datosExtraidos['prendas']);

echo "✅ Pedido creado: #{$pedido->numero_pedido}\n\n";

// Verificar prendas del pedido
$prendas = DB::table('prendas_pedido')
    ->where('numero_pedido', $pedido->numero_pedido)
    ->get();

echo "1️⃣  PRENDAS DEL PEDIDO:\n";
foreach ($prendas as $idx => $prenda) {
    echo "\n   Prenda $idx: {$prenda->nombre_prenda}\n";
    echo "   - tela_id: " . ($prenda->tela_id ?? "NULL") . "\n";
    echo "   - color_id: " . ($prenda->color_id ?? "NULL") . "\n";
    echo "   - descripcion (primeros 100 chars):\n";
    echo "     " . substr($prenda->descripcion, 0, 100) . "...\n";
}

// Verificar relaciones con telas y colores
echo "\n\n2️⃣  VERIFICACIÓN DE RELACIONES:\n";
foreach ($prendas as $idx => $prenda) {
    echo "\n   Prenda $idx ({$prenda->nombre_prenda}):\n";
    
    if ($prenda->tela_id) {
        $tela = DB::table('telas_prenda')->where('id', $prenda->tela_id)->first();
        echo "   - Tela: {$tela->nombre} (ID: {$tela->id})\n";
        echo "   - Referencia: {$tela->referencia}\n";
    } else {
        echo "   - Tela: SIN RELACIÓN\n";
    }
    
    if ($prenda->color_id) {
        $color = DB::table('colores_prenda')->where('id', $prenda->color_id)->first();
        echo "   - Color: {$color->nombre} (ID: {$color->id})\n";
    } else {
        echo "   - Color: SIN RELACIÓN\n";
    }
}

echo "\n\n3️⃣  RESUMEN:\n";
$conTelaId = 0;
$conColorId = 0;
foreach ($prendas as $prenda) {
    if ($prenda->tela_id) $conTelaId++;
    if ($prenda->color_id) $conColorId++;
}
echo "   ✅ Prendas con tela_id: $conTelaId/" . count($prendas) . "\n";
echo "   ✅ Prendas con color_id: $conColorId/" . count($prendas) . "\n";

if ($conTelaId === count($prendas) && $conColorId === count($prendas)) {
    echo "\n🎉 ¡ÉXITO! Todos los IDs se guardaron correctamente\n";
} else {
    echo "\n⚠ Algunos IDs no se guardaron\n";
}
