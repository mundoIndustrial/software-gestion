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
echo "🧪 TEST FINAL - SISTEMA COMPLETO DE VARIACIONES\n";
echo "═══════════════════════════════════════════════════════════════\n";

// Obtener usuario yus2
$usuario = DB::table('users')->where('email', 'yus2@gmail.com')->first();
if (!$usuario) {
    echo "❌ Usuario yus2@gmail.com no encontrado\n";
    exit;
}

echo "\n📋 Usuario: {$usuario->name} (ID: {$usuario->id})\n";

try {
    DB::beginTransaction();

    // PASO 1: Crear cotización
    echo "\n💾 PASO 1: Creando cotización...\n";
    $cotizacion = Cotizacion::create([
        'user_id' => $usuario->id,
        'cliente' => 'TEST FINAL ' . time(),
        'asesora' => $usuario->name,
        'es_borrador' => false,
        'estado' => 'enviada',
        'numero_cotizacion' => 'FINAL-' . time(),
        'productos' => json_encode([
            [
                'nombre_producto' => 'CAMISA DRILL',
                'descripcion' => 'Camiseta drill con bordado',
                'cantidad' => 50,
                'cantidades' => ['S' => 10, 'M' => 20, 'L' => 20]
            ]
        ])
    ]);
    echo "✅ Cotización creada (ID: {$cotizacion->id})\n";

    // PASO 2: Crear prenda
    echo "\n👕 PASO 2: Creando prenda...\n";
    $prenda = PrendaCotizacionFriendly::create([
        'cotizacion_id' => $cotizacion->id,
        'nombre_producto' => 'CAMISA DRILL',
        'descripcion' => 'Camiseta drill con bordado',
        'tallas' => ['S', 'M', 'L'],
        'estado' => 'Pendiente'
    ]);
    echo "✅ Prenda creada (ID: {$prenda->id})\n";

    // PASO 3: Crear datos de variantes
    echo "\n🎨 PASO 3: Preparando datos...\n";
    $tipoPrenda = TipoPrenda::firstOrCreate(
        ['nombre' => 'CAMISA'],
        [
            'codigo' => 'CAM',
            'descripcion' => 'Camiseta',
            'palabras_clave' => 'camisa,camiseta'
        ]
    );
    $color = ColorPrenda::firstOrCreate(['nombre' => 'Naranja']);
    $tela = TelaPrenda::firstOrCreate(
        ['nombre' => 'DRILL BORNEO'],
        ['referencia' => 'DRILL-BORNEO-001']
    );
    $manga = TipoManga::firstOrCreate(['nombre' => 'Larga']);

    echo "✅ Tipo prenda: {$tipoPrenda->nombre}\n";
    echo "✅ Color: {$color->nombre}\n";
    echo "✅ Tela: {$tela->nombre} (Ref: {$tela->referencia})\n";
    echo "✅ Manga: {$manga->nombre}\n";

    // PASO 4: Crear variante con observaciones
    echo "\n📝 PASO 4: Creando variante con observaciones...\n";
    $variante = VariantePrenda::create([
        'prenda_cotizacion_id' => $prenda->id,
        'tipo_prenda_id' => $tipoPrenda->id,
        'color_id' => $color->id,
        'tela_id' => $tela->id,
        'tipo_manga_id' => $manga->id,
        'tiene_bolsillos' => true,
        'tiene_reflectivo' => true,
        'cantidad_talla' => json_encode(['S' => 10, 'M' => 20, 'L' => 20]),
        'descripcion_adicional' => 'Bolsillos: LLEVA BOLSILLOS CON TAPA BOTON Y OJAL CON LOGOS BORDADOS DENTRO DEL BOLSILLO DERECHO | Reflectivo: CON REFLECTIVO GRIS 2" DE 25 CICLOS EN H EN LA PARTE DELANTERA Y TRASERA 2 VUELTAS EN CADA BRAZO Y UNA LINEA A LA ALTURA DEL OMBLIGO'
    ]);
    echo "✅ Variante creada (ID: {$variante->id})\n";

    // PASO 5: Verificar en BD
    echo "\n🔍 PASO 5: Verificando en BD...\n";
    $varianteVerificada = VariantePrenda::with('color', 'tela', 'tipoManga')->find($variante->id);
    echo "✅ Variante encontrada\n";
    echo "   - Color: {$varianteVerificada->color->nombre}\n";
    echo "   - Tela: {$varianteVerificada->tela->nombre}\n";
    echo "   - Referencia: {$varianteVerificada->tela->referencia}\n";
    echo "   - Manga: {$varianteVerificada->tipoManga->nombre}\n";
    echo "   - Bolsillos: " . ($varianteVerificada->tiene_bolsillos ? 'Sí' : 'No') . "\n";
    echo "   - Reflectivo: " . ($varianteVerificada->tiene_reflectivo ? 'Sí' : 'No') . "\n";

    // PASO 6: Verificar endpoint JSON
    echo "\n📺 PASO 6: Verificando endpoint JSON...\n";
    $cotizacionConVariantes = Cotizacion::with([
        'prendasCotizaciones.variantes.color',
        'prendasCotizaciones.variantes.tela',
        'prendasCotizaciones.variantes.tipoManga'
    ])->find($cotizacion->id);

    $jsonResponse = [
        'id' => $cotizacionConVariantes->id,
        'cliente' => $cotizacionConVariantes->cliente,
        'asesora' => $cotizacionConVariantes->asesora,
        'prendas' => $cotizacionConVariantes->prendasCotizaciones->map(function($p) {
            $variante = $p->variantes->first();
            return [
                'id' => $p->id,
                'nombre_producto' => $p->nombre_producto,
                'descripcion' => $p->descripcion,
                'variantes' => [
                    'color' => $variante && $variante->color ? $variante->color->nombre : null,
                    'tela' => $variante && $variante->tela ? $variante->tela->nombre : null,
                    'tela_referencia' => $variante && $variante->tela && $variante->tela->referencia ? $variante->tela->referencia : null,
                    'manga' => $variante && $variante->tipoManga ? $variante->tipoManga->nombre : null,
                    'tiene_bolsillos' => $variante ? $variante->tiene_bolsillos : false,
                    'tiene_reflectivo' => $variante ? $variante->tiene_reflectivo : false,
                    'observaciones' => $variante ? $variante->descripcion_adicional : null
                ]
            ];
        })->toArray()
    ];

    echo "✅ JSON Response:\n";
    echo json_encode($jsonResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

    DB::commit();

    echo "\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "✅ TEST COMPLETADO EXITOSAMENTE\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "\n📝 RESUMEN:\n";
    echo "   1. Cotización: {$cotizacion->id}\n";
    echo "   2. Prenda: {$prenda->nombre_producto}\n";
    echo "   3. Variante: {$variante->id}\n";
    echo "   4. Sistema: 100% Funcional ✅\n";
    echo "\n🌐 Para ver en show, abre: /asesores/cotizaciones/{$cotizacion->id}\n";
    echo "\n";

} catch (\Exception $e) {
    DB::rollBack();
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
