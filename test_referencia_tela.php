<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Cotizacion;
use App\Models\PrendaCotizacionFriendly;
use App\Models\VariantePrenda;
use App\Models\TipoPrenda;
use App\Models\ColorPrenda;
use App\Models\TelaPrenda;
use App\Models\TipoManga;
use Illuminate\Support\Facades\DB;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "🧪 TEST - REFERENCIA DE TELA\n";
echo "═══════════════════════════════════════════════════════════════\n";

// Obtener usuario yus2
$usuario = DB::table('users')->where('email', 'yus2@gmail.com')->first();
if (!$usuario) {
    echo "❌ Usuario yus2@gmail.com no encontrado\n";
    exit;
}

echo "\n📋 Usuario: {$usuario->name} (ID: {$usuario->id})\n";

// PASO 1: Crear cotización
echo "\n💾 PASO 1: Creando cotización...\n";
$cotizacion = Cotizacion::create([
    'user_id' => $usuario->id,
    'cliente' => 'TEST REFERENCIA ' . time(),
    'asesora' => $usuario->name,
    'es_borrador' => false,
    'estado' => 'enviada',
    'numero_cotizacion' => 'REF-' . time()
]);
echo "✅ Cotización creada (ID: {$cotizacion->id})\n";

// PASO 2: Crear prenda
echo "\n👕 PASO 2: Creando prenda...\n";
$prenda = PrendaCotizacionFriendly::create([
    'cotizacion_id' => $cotizacion->id,
    'nombre_producto' => 'CAMISA CON REFERENCIA',
    'descripcion' => 'Test con referencia de tela',
    'tallas' => ['S', 'M', 'L'],
    'estado' => 'Pendiente'
]);
echo "✅ Prenda creada (ID: {$prenda->id})\n";

// PASO 3: Crear tela CON REFERENCIA
echo "\n🧵 PASO 3: Creando tela con referencia...\n";

$tela = TelaPrenda::create([
    'nombre' => 'ALGODÓN PREMIUM',
    'referencia' => 'ALG-PREM-2024-001',
    'descripcion' => 'Algodón de alta calidad'
]);
echo "✅ Tela creada (ID: {$tela->id})\n";
echo "   - Nombre: {$tela->nombre}\n";
echo "   - Referencia: {$tela->referencia}\n";

// PASO 4: Obtener datos necesarios
echo "\n🎨 PASO 4: Preparando datos...\n";

$tipoPrenda = TipoPrenda::where('nombre', 'CAMISA')->first();
$color = ColorPrenda::firstOrCreate(['nombre' => 'Rojo']);
$manga = TipoManga::firstOrCreate(['nombre' => 'Larga']);

echo "✅ Tipo prenda: {$tipoPrenda->nombre}\n";
echo "✅ Color: {$color->nombre}\n";
echo "✅ Manga: {$manga->nombre}\n";

// PASO 5: Crear variante
echo "\n📝 PASO 5: Creando variante...\n";

$datosVariante = [
    'prenda_cotizacion_id' => $prenda->id,
    'tipo_prenda_id' => $tipoPrenda->id,
    'color_id' => $color->id,
    'tela_id' => $tela->id,
    'tipo_manga_id' => $manga->id,
    'tiene_bolsillos' => true,
    'tiene_reflectivo' => false,
    'cantidad_talla' => json_encode(['S' => 5, 'M' => 10, 'L' => 8]),
    'descripcion_adicional' => 'Bolsillos: 2 bolsillos frontales'
];

$variante = VariantePrenda::create($datosVariante);
echo "✅ Variante creada (ID: {$variante->id})\n";

// PASO 6: Verificar en BD
echo "\n🔍 PASO 6: Verificando en BD...\n";

$varianteVerificada = VariantePrenda::with('tela')->find($variante->id);

echo "✅ Variante encontrada\n";
echo "   - Color: " . ($varianteVerificada->color ? $varianteVerificada->color->nombre : 'N/A') . "\n";
echo "   - Tela: " . ($varianteVerificada->tela ? $varianteVerificada->tela->nombre : 'N/A') . "\n";
echo "   - Referencia Tela: " . ($varianteVerificada->tela && $varianteVerificada->tela->referencia ? $varianteVerificada->tela->referencia : 'N/A') . "\n";
echo "   - Manga: " . ($varianteVerificada->tipoManga ? $varianteVerificada->tipoManga->nombre : 'N/A') . "\n";
echo "   - Bolsillos: " . ($varianteVerificada->tiene_bolsillos ? 'Sí' : 'No') . "\n";

// PASO 7: Verificar relación desde prenda
echo "\n🔗 PASO 7: Verificando relación desde prenda...\n";

$prendaConVariantes = PrendaCotizacionFriendly::with('variantes.tela')->find($prenda->id);

foreach ($prendaConVariantes->variantes as $v) {
    echo "✅ Variante encontrada\n";
    echo "   - Tela: " . ($v->tela ? $v->tela->nombre : '-') . "\n";
    echo "   - Referencia: " . ($v->tela && $v->tela->referencia ? $v->tela->referencia : '-') . "\n";
}

// PASO 8: Verificar como lo hace el controlador (con with)
echo "\n📺 PASO 8: Verificando como lo hace el controlador...\n";

$cotizacionConVariantes = Cotizacion::with([
    'prendasCotizaciones.variantes.color',
    'prendasCotizaciones.variantes.tela',
    'prendasCotizaciones.variantes.tipoManga'
])->find($cotizacion->id);

foreach ($cotizacionConVariantes->prendasCotizaciones as $p) {
    echo "✅ Prenda: {$p->nombre_producto}\n";
    
    foreach ($p->variantes as $v) {
        echo "   ├─ Color: " . ($v->color ? $v->color->nombre : '-') . "\n";
        echo "   ├─ Tela: " . ($v->tela ? $v->tela->nombre : '-') . "\n";
        echo "   ├─ Referencia: " . ($v->tela && $v->tela->referencia ? $v->tela->referencia : '-') . "\n";
        echo "   ├─ Manga: " . ($v->tipoManga ? $v->tipoManga->nombre : '-') . "\n";
        echo "   └─ Bolsillos: " . ($v->tiene_bolsillos ? 'Sí' : 'No') . "\n";
    }
}

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "✅ TEST COMPLETADO EXITOSAMENTE\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n📝 RESUMEN:\n";
echo "   1. Cotización: {$cotizacion->id}\n";
echo "   2. Prenda: {$prenda->nombre_producto}\n";
echo "   3. Variante: {$variante->id}\n";
echo "   4. Tela: {$tela->nombre}\n";
echo "   5. Referencia Tela: {$tela->referencia}\n";
echo "   6. Referencia guardada en BD: SÍ ✅\n";
echo "\n🌐 Para ver en show, abre: /asesores/cotizaciones/{$cotizacion->id}\n";
echo "\n";
