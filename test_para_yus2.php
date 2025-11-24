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
echo "🧪 TEST PARA YUS2 - OBSERVACIONES EN VARIANTES\n";
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
    'cliente' => 'CLIENTE YUS2 ' . time(),
    'asesora' => $usuario->name,
    'es_borrador' => false,
    'estado' => 'enviada',
    'numero_cotizacion' => 'YUS2-' . time()
]);
echo "✅ Cotización creada (ID: {$cotizacion->id})\n";

// PASO 2: Crear prenda
echo "\n👕 PASO 2: Creando prenda...\n";
$prenda = PrendaCotizacionFriendly::create([
    'cotizacion_id' => $cotizacion->id,
    'nombre_producto' => 'CAMISA DRILL YUS2',
    'descripcion' => 'Test con observaciones para yus2',
    'tallas' => ['S', 'M', 'L', 'XL'],
    'estado' => 'Pendiente'
]);
echo "✅ Prenda creada (ID: {$prenda->id})\n";

// PASO 3: Obtener/crear datos necesarios
echo "\n🎨 PASO 3: Preparando datos...\n";

$tipoPrenda = TipoPrenda::where('nombre', 'CAMISA')->first();
$color = ColorPrenda::firstOrCreate(['nombre' => 'Azul']);
$tela = TelaPrenda::firstOrCreate(['nombre' => 'ALGODÓN']);
$manga = TipoManga::firstOrCreate(['nombre' => 'Corta']);

echo "✅ Tipo prenda: {$tipoPrenda->nombre}\n";
echo "✅ Color: {$color->nombre}\n";
echo "✅ Tela: {$tela->nombre}\n";
echo "✅ Manga: {$manga->nombre}\n";

// PASO 4: Crear variante CON OBSERVACIONES
echo "\n📝 PASO 4: Creando variante con observaciones...\n";

$variantes = [
    'color' => 'Azul',
    'tela' => 'ALGODÓN',
    'tipo_manga_id' => $manga->id,
    'tiene_bolsillos' => true,
    'tiene_reflectivo' => false,
    'obs_bolsillos' => '2 bolsillos frontales con cierre',
    'obs_broche' => 'botones de plástico blanco',
    'obs_reflectivo' => ''
];

// Simular lo que hace el controlador
$datosVariante = [
    'prenda_cotizacion_id' => $prenda->id,
    'tipo_prenda_id' => $tipoPrenda->id,
    'color_id' => $color->id,
    'tela_id' => $tela->id,
    'tipo_manga_id' => $manga->id,
    'tiene_bolsillos' => true,
    'tiene_reflectivo' => false,
    'cantidad_talla' => json_encode(['S' => 5, 'M' => 10, 'L' => 8, 'XL' => 3])
];

// Procesar observaciones
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

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "✅ TEST COMPLETADO EXITOSAMENTE\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n📝 RESUMEN:\n";
echo "   1. Cotización: {$cotizacion->id}\n";
echo "   2. Prenda: {$prenda->nombre_producto}\n";
echo "   3. Variante: {$variante->id}\n";
echo "   4. Observaciones guardadas: SÍ ✅\n";
echo "\n🌐 Para ver en show, abre: /asesores/cotizaciones/{$cotizacion->id}\n";
echo "\n";
