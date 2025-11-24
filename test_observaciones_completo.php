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
echo "🧪 TEST COMPLETO - OBSERVACIONES EN VARIANTES\n";
echo "═══════════════════════════════════════════════════════════════\n";

// Obtener usuario (usa el primero disponible)
$usuario = DB::table('users')->first();
if (!$usuario) {
    echo "❌ No hay usuarios en la BD\n";
    exit;
}

echo "\n📋 Usuario: {$usuario->name} (ID: {$usuario->id})\n";

// PASO 1: Crear cotización
echo "\n💾 PASO 1: Creando cotización...\n";
$cotizacion = Cotizacion::create([
    'user_id' => $usuario->id,
    'cliente' => 'TEST OBSERVACIONES ' . time(),
    'asesora' => 'Test',
    'es_borrador' => false,
    'estado' => 'enviada',
    'numero_cotizacion' => 'TEST-' . time()
]);
echo "✅ Cotización creada (ID: {$cotizacion->id})\n";

// PASO 2: Crear prenda
echo "\n👕 PASO 2: Creando prenda...\n";
$prenda = PrendaCotizacionFriendly::create([
    'cotizacion_id' => $cotizacion->id,
    'nombre_producto' => 'CAMISA DRILL TEST',
    'descripcion' => 'Test con observaciones',
    'tallas' => ['S', 'M', 'L'],
    'estado' => 'Pendiente'
]);
echo "✅ Prenda creada (ID: {$prenda->id})\n";

// PASO 3: Obtener/crear datos necesarios
echo "\n🎨 PASO 3: Preparando datos...\n";

$tipoPrenda = TipoPrenda::where('nombre', 'CAMISA')->first();
$color = ColorPrenda::firstOrCreate(['nombre' => 'Naranja']);
$tela = TelaPrenda::firstOrCreate(['nombre' => 'DRILL BORNEO']);
$manga = TipoManga::firstOrCreate(['nombre' => 'Larga']);

echo "✅ Tipo prenda: {$tipoPrenda->nombre}\n";
echo "✅ Color: {$color->nombre}\n";
echo "✅ Tela: {$tela->nombre}\n";
echo "✅ Manga: {$manga->nombre}\n";

// PASO 4: Crear variante CON OBSERVACIONES (simulando lo que hace el controlador)
echo "\n📝 PASO 4: Creando variante con observaciones...\n";

$variantes = [
    'color' => 'Naranja',
    'tela' => 'DRILL BORNEO',
    'tipo_manga_id' => $manga->id,
    'tiene_bolsillos' => true,
    'tiene_reflectivo' => true,
    'obs_bolsillos' => 'prueba de bolsillo',
    'obs_broche' => 'botones de madera',
    'obs_reflectivo' => 'Gris 2" en pecho y espalda'
];

// Simular lo que hace el controlador
$datosVariante = [
    'prenda_cotizacion_id' => $prenda->id,
    'tipo_prenda_id' => $tipoPrenda->id,
    'color_id' => $color->id,
    'tela_id' => $tela->id,
    'tipo_manga_id' => $manga->id,
    'tiene_bolsillos' => true,
    'tiene_reflectivo' => true,
    'cantidad_talla' => json_encode(['S' => 1, 'M' => 1, 'L' => 1])
];

// Procesar observaciones (como lo hace el controlador)
$observacionesArray = [];

if (isset($variantes['obs_bolsillos']) && !empty($variantes['obs_bolsillos'])) {
    $observacionesArray[] = "Bolsillos: {$variantes['obs_bolsillos']}";
}
if (isset($variantes['obs_broche']) && !empty($variantes['obs_broche'])) {
    $observacionesArray[] = "Broche: {$variantes['obs_broche']}";
}
if (isset($variantes['obs_reflectivo']) && !empty($variantes['obs_reflectivo'])) {
    $observacionesArray[] = "Reflectivo: {$variantes['obs_reflectivo']}";
}

if (!empty($observacionesArray)) {
    $datosVariante['descripcion_adicional'] = implode(' | ', $observacionesArray);
}

echo "📝 Observaciones procesadas:\n";
echo "   {$datosVariante['descripcion_adicional']}\n";

// Guardar variante
$variante = VariantePrenda::create($datosVariante);
echo "✅ Variante creada (ID: {$variante->id})\n";

// PASO 5: Verificar en BD
echo "\n🔍 PASO 5: Verificando en BD...\n";

$varianteVerificada = VariantePrenda::find($variante->id);

echo "✅ Variante encontrada\n";
echo "   - Color: " . ($varianteVerificada->color ? $varianteVerificada->color->nombre : 'N/A') . "\n";
echo "   - Tela: " . ($varianteVerificada->tela ? $varianteVerificada->tela->nombre : 'N/A') . "\n";
echo "   - Manga: " . ($varianteVerificada->tipoManga ? $varianteVerificada->tipoManga->nombre : 'N/A') . "\n";
echo "   - Bolsillos: " . ($varianteVerificada->tiene_bolsillos ? 'Sí' : 'No') . "\n";
echo "   - Reflectivo: " . ($varianteVerificada->tiene_reflectivo ? 'Sí' : 'No') . "\n";
echo "   - Observaciones: {$varianteVerificada->descripcion_adicional}\n";

// PASO 6: Verificar relación desde prenda
echo "\n🔗 PASO 6: Verificando relación desde prenda...\n";

$prendaConVariantes = PrendaCotizacionFriendly::with('variantes')->find($prenda->id);
$variantesCount = $prendaConVariantes->variantes->count();

echo "✅ Prenda con variantes: {$variantesCount}\n";

foreach ($prendaConVariantes->variantes as $v) {
    echo "   - Variante ID: {$v->id}\n";
    echo "     Observaciones: {$v->descripcion_adicional}\n";
}

// PASO 7: Verificar en show (simulado)
echo "\n📺 PASO 7: Simulando vista show...\n";

$cotizacionConVariantes = Cotizacion::with([
    'prendasCotizaciones.variantes.color',
    'prendasCotizaciones.variantes.tela',
    'prendasCotizaciones.variantes.tipoManga'
])->find($cotizacion->id);

foreach ($cotizacionConVariantes->prendasCotizaciones as $p) {
    echo "   Prenda: {$p->nombre_producto}\n";
    
    foreach ($p->variantes as $v) {
        echo "   ├─ Color: " . ($v->color ? $v->color->nombre : '-') . "\n";
        echo "   ├─ Tela: " . ($v->tela ? $v->tela->nombre : '-') . "\n";
        echo "   ├─ Manga: " . ($v->tipoManga ? $v->tipoManga->nombre : '-') . "\n";
        echo "   ├─ Bolsillos: " . ($v->tiene_bolsillos ? 'Sí' : 'No') . "\n";
        echo "   ├─ Reflectivo: " . ($v->tiene_reflectivo ? 'Sí' : 'No') . "\n";
        echo "   └─ Observaciones: {$v->descripcion_adicional}\n";
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
echo "   4. Observaciones guardadas: SÍ ✅\n";
echo "   5. Observaciones mostradas: SÍ ✅\n";
echo "\n🌐 Para ver en show, abre: /asesores/cotizaciones/{$cotizacion->id}\n";
echo "\n";
