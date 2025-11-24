<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\TipoPrenda;
use App\Models\Cotizacion;
use App\Models\PrendaCotizacionFriendly;
use App\Models\VariantePrenda;
use App\Models\ColorPrenda;
use App\Models\TelaPrenda;
use App\Models\TipoManga;
use Illuminate\Support\Facades\DB;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "🧪 TEST COMPLETO DEL SISTEMA DE VARIACIONES\n";
echo "═══════════════════════════════════════════════════════════════\n";

// PASO 1: Verificar tipos de prenda
echo "\n📋 PASO 1: Verificando tipos de prenda...\n";
$tiposCamisa = TipoPrenda::where('nombre', 'CAMISA')->first();
if ($tiposCamisa) {
    echo "✅ Tipo CAMISA encontrado (ID: {$tiposCamisa->id})\n";
} else {
    echo "❌ Tipo CAMISA no encontrado\n";
    exit;
}

// PASO 2: Reconocer prenda
echo "\n🔍 PASO 2: Reconociendo 'CAMISA DRILL'...\n";
$tipoPrenda = TipoPrenda::reconocerPorNombre('CAMISA DRILL');
if ($tipoPrenda) {
    echo "✅ Reconocido como: {$tipoPrenda->nombre}\n";
} else {
    echo "❌ No se pudo reconocer\n";
    exit;
}

// PASO 3: Crear cotización simulada
echo "\n💾 PASO 3: Creando cotización simulada...\n";

// Obtener un usuario válido
$usuario = DB::table('users')->first();
if (!$usuario) {
    echo "❌ No hay usuarios en la BD\n";
    exit;
}

$cotizacion = Cotizacion::create([
    'user_id' => $usuario->id,
    'cliente' => 'CLIENTE TEST ' . time(),
    'asesora' => 'Asesor Test',
    'es_borrador' => false,
    'estado' => 'enviada',
    'numero_cotizacion' => 'COT-TEST-' . time(),
    'productos' => json_encode([
        [
            'nombre_producto' => 'CAMISA DRILL',
            'descripcion' => 'Test',
            'tallas' => ['S', 'M', 'L']
        ]
    ])
]);
echo "✅ Cotización creada (ID: {$cotizacion->id})\n";

// PASO 4: Crear prenda
echo "\n👕 PASO 4: Creando prenda...\n";
$prenda = PrendaCotizacionFriendly::create([
    'cotizacion_id' => $cotizacion->id,
    'nombre_producto' => 'CAMISA DRILL',
    'descripcion' => 'Test',
    'tallas' => ['S', 'M', 'L'],
    'estado' => 'Pendiente'
]);
echo "✅ Prenda creada (ID: {$prenda->id})\n";

// PASO 5: Crear/buscar variantes
echo "\n🎨 PASO 5: Procesando variantes...\n";

// Color
$color = ColorPrenda::firstOrCreate(
    ['nombre' => 'Naranja'],
    ['nombre' => 'Naranja']
);
echo "✅ Color: {$color->nombre} (ID: {$color->id})\n";

// Tela
$tela = TelaPrenda::firstOrCreate(
    ['nombre' => 'DRILL BORNEO'],
    ['nombre' => 'DRILL BORNEO']
);
echo "✅ Tela: {$tela->nombre} (ID: {$tela->id})\n";

// Manga
$manga = TipoManga::where('nombre', 'Larga')->first();
if (!$manga) {
    echo "⚠️ Manga 'Larga' no encontrada, creando...\n";
    $manga = TipoManga::create(['nombre' => 'Larga']);
}
echo "✅ Manga: {$manga->nombre} (ID: {$manga->id})\n";

// PASO 6: Guardar variante
echo "\n💾 PASO 6: Guardando variante en BD...\n";
$variante = VariantePrenda::create([
    'prenda_cotizacion_id' => $prenda->id,
    'tipo_prenda_id' => $tipoPrenda->id,
    'color_id' => $color->id,
    'tela_id' => $tela->id,
    'tipo_manga_id' => $manga->id,
    'tiene_reflectivo' => true,
    'cantidad_talla' => json_encode(['S' => 1, 'M' => 1, 'L' => 1])
]);
echo "✅ Variante guardada (ID: {$variante->id})\n";

// PASO 7: Verificar en BD
echo "\n🔍 PASO 7: Verificando en BD...\n";
$variantes = VariantePrenda::where('prenda_cotizacion_id', $prenda->id)->get();
echo "✅ Variantes encontradas: {$variantes->count()}\n";

foreach ($variantes as $v) {
    echo "\n   Variante ID: {$v->id}\n";
    echo "   - Tipo Prenda: {$v->tipoPrenda->nombre}\n";
    echo "   - Color: " . ($v->color ? $v->color->nombre : 'N/A') . "\n";
    echo "   - Tela: " . ($v->tela ? $v->tela->nombre : 'N/A') . "\n";
    echo "   - Manga: " . ($v->tipoManga ? $v->tipoManga->nombre : 'N/A') . "\n";
    echo "   - Reflectivo: " . ($v->tiene_reflectivo ? 'Sí' : 'No') . "\n";
}

// PASO 8: Verificar relación desde prenda
echo "\n🔗 PASO 8: Verificando relación desde prenda...\n";
$prendaConVariantes = PrendaCotizacionFriendly::with('variantes')->find($prenda->id);
echo "✅ Prenda con variantes: {$prendaConVariantes->variantes->count()} variantes\n";

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "✅ TEST COMPLETADO EXITOSAMENTE\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";
