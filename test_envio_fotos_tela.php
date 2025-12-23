<?php
/**
 * TEST AUTOMATIZADO: Flujo completo de envío con fotos de tela
 * 
 * Simula:
 * 1. Crear borrador (#test-draft)
 * 2. Enviar como cotización (#test-enviada) 
 * 3. Verificar que fotos se guardaron
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\Fluent\AssertableJson;

echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║  TEST: ENVÍO DE COTIZACIÓN CON FOTOS DE TELA                  ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";

// Limpiar log anterior
file_put_contents(storage_path('logs/laravel.log'), '');

// ===== PASO 1: Crear imagen de tela para test =====
echo "\n📸 Paso 1: Preparando imágenes de tela...\n";

// Crear imágenes dummy
$testImageDir = storage_path('test_images');
if (!is_dir($testImageDir)) {
    mkdir($testImageDir, 0755, true);
}

// Crear 2 imágenes de prueba (PNG simples 1x1)
$pngData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');

$imgPath1 = $testImageDir . '/tela_test_1.png';
$imgPath2 = $testImageDir . '/tela_test_2.png';

file_put_contents($imgPath1, $pngData);
file_put_contents($imgPath2, $pngData);

echo "✅ Imágenes creadas en $testImageDir\n";

// ===== PASO 2: Crear cotización en BD (simular creación de borrador) =====
echo "\n📝 Paso 2: Creando borrador...\n";

$usuario = DB::table('usuarios')->where('email', 'test@test.com')->first();
if (!$usuario) {
    // Usar usuario existente del sistema
    $usuario = DB::table('usuarios')->whereNotNull('id')->first();
    echo "Usando usuario: {$usuario->email} (ID: {$usuario->id})\n";
}

// Crear cliente
$cliente = DB::table('clientes')->where('nombre', 'TEST CLIENTE AUTO')->first();
if (!$cliente) {
    $clienteId = DB::table('clientes')->insertGetId([
        'nombre' => 'TEST CLIENTE AUTO',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    echo "✅ Cliente creado: ID $clienteId\n";
} else {
    $clienteId = $cliente->id;
    echo "✅ Cliente existente: ID $clienteId\n";
}

// Crear cotización borrador
$cotizacionDraft = DB::table('cotizaciones')->insertGetId([
    'numero_cotizacion' => null,
    'tipo' => 'PL',
    'tipo_cotizacion_id' => 1,
    'usuario_id' => $usuario->id,
    'cliente_id' => $clienteId,
    'tipo_venta' => 'D',
    'estado' => 'BORRADOR',
    'es_borrador' => true,
    'descripcion' => 'Test borrador con fotos de tela',
    'created_at' => now(),
    'updated_at' => now(),
]);

echo "✅ Borrador creado: cotizacion_id = $cotizacionDraft\n";

// Crear prenda
$prendaDraftId = DB::table('prendas_cot')->insertGetId([
    'cotizacion_id' => $cotizacionDraft,
    'nombre_producto' => 'CAMISA TEST FOTOS',
    'descripcion' => 'Prueba de fotos de tela',
    'cantidad' => 1,
    'created_at' => now(),
    'updated_at' => now(),
]);

echo "✅ Prenda creada: prenda_cot_id = $prendaDraftId\n";

// Crear tallas
for ($i = 0; $i < 3; $i++) {
    DB::table('talla_prenda_cots')->insert([
        'prenda_cot_id' => $prendaDraftId,
        'talla' => ['S', 'M', 'L'][$i],
        'cantidad' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

// Crear variante
$varianteId = DB::table('variantes_prenda_cots')->insertGetId([
    'prenda_cot_id' => $prendaDraftId,
    'genero_id' => 2,
    'color' => 'AZUL',
    'tipo_manga_id' => 4,
    'tipo_broche_id' => 2,
    'tiene_bolsillos' => false,
    'telas_multiples' => json_encode([
        [
            'indice' => 0,
            'color' => 'AZUL',
            'tela' => 'ALGODÓN',
            'referencia' => 'ALG-AZUL-01'
        ],
        [
            'indice' => 1,
            'color' => 'NEGRO',
            'tela' => 'POLIÉSTER',
            'referencia' => 'POL-NEGRO-01'
        ]
    ]),
    'created_at' => now(),
    'updated_at' => now(),
]);

echo "✅ Variante creada: variante_id = $varianteId\n";

// Crear colores y telas
$color1 = DB::table('colores_prenda')->insertGetId([
    'nombre' => 'AZUL',
    'activo' => true,
    'created_at' => now(),
    'updated_at' => now(),
]);

$color2 = DB::table('colores_prenda')->insertGetId([
    'nombre' => 'NEGRO',
    'activo' => true,
    'created_at' => now(),
    'updated_at' => now(),
]);

$tela1 = DB::table('telas_prenda')->insertGetId([
    'nombre' => 'ALGODÓN',
    'referencia' => 'ALG-AZUL-01',
    'activo' => true,
    'created_at' => now(),
    'updated_at' => now(),
]);

$tela2 = DB::table('telas_prenda')->insertGetId([
    'nombre' => 'POLIÉSTER',
    'referencia' => 'POL-NEGRO-01',
    'activo' => true,
    'created_at' => now(),
    'updated_at' => now(),
]);

echo "✅ Colores creados: $color1, $color2\n";
echo "✅ Telas creadas: $tela1, $tela2\n";

// Crear prenda_telas_cot
$prendaTela1 = DB::table('prenda_telas_cot')->insertGetId([
    'prenda_cot_id' => $prendaDraftId,
    'variante_prenda_cot_id' => $varianteId,
    'color_id' => $color1,
    'tela_id' => $tela1,
    'created_at' => now(),
    'updated_at' => now(),
]);

$prendaTela2 = DB::table('prenda_telas_cot')->insertGetId([
    'prenda_cot_id' => $prendaDraftId,
    'variante_prenda_cot_id' => $varianteId,
    'color_id' => $color2,
    'tela_id' => $tela2,
    'created_at' => now(),
    'updated_at' => now(),
]);

echo "✅ Prenda_telas_cot creadas: $prendaTela1, $prendaTela2\n";

// Crear fotos de telas (existentes que se reutilizarán)
$foto1 = DB::table('prenda_tela_fotos_cot')->insertGetId([
    'prenda_cot_id' => $prendaDraftId,
    'prenda_tela_cot_id' => $prendaTela1,
    'tela_index' => 0,
    'ruta_original' => 'telas/cotizaciones/test_tela_1.png',
    'ruta_webp' => 'telas/cotizaciones/test_tela_1.webp',
    'orden' => 1,
    'created_at' => now(),
    'updated_at' => now(),
]);

$foto2 = DB::table('prenda_tela_fotos_cot')->insertGetId([
    'prenda_cot_id' => $prendaDraftId,
    'prenda_tela_cot_id' => $prendaTela2,
    'tela_index' => 1,
    'ruta_original' => 'telas/cotizaciones/test_tela_2.png',
    'ruta_webp' => 'telas/cotizaciones/test_tela_2.webp',
    'orden' => 1,
    'created_at' => now(),
    'updated_at' => now(),
]);

echo "✅ Fotos de tela creadas en DRAFT: $foto1, $foto2\n";

// ===== PASO 3: Crear cotización enviada (simular envío) =====
echo "\n📤 Paso 3: Enviando como cotización...\n";

// Generar número de cotización
$numeroSecuencia = DB::table('numero_secuencias')
    ->where('tipo', 'cotizacion')
    ->lockForUpdate()
    ->first();

$nuevoNumero = ($numeroSecuencia->siguiente ?? 0) + 1;

DB::table('numero_secuencias')
    ->where('tipo', 'cotizacion')
    ->update(['siguiente' => $nuevoNumero]);

$numCotizacion = 'COT-' . str_pad($nuevoNumero, 5, '0', STR_PAD_LEFT);

// Crear cotización enviada
$cotizacionEnvio = DB::table('cotizaciones')->insertGetId([
    'numero_cotizacion' => $numCotizacion,
    'tipo' => 'PL',
    'tipo_cotizacion_id' => 1,
    'usuario_id' => $usuario->id,
    'cliente_id' => $clienteId,
    'tipo_venta' => 'D',
    'estado' => 'ENVIADA_CONTADOR',
    'es_borrador' => false,
    'descripcion' => 'Test envío con fotos de tela',
    'created_at' => now(),
    'updated_at' => now(),
]);

echo "✅ Cotización enviada creada: cotizacion_id = $cotizacionEnvio, numero = $numCotizacion\n";

// Crear prenda en cotización enviada
$prendaEnvioId = DB::table('prendas_cot')->insertGetId([
    'cotizacion_id' => $cotizacionEnvio,
    'nombre_producto' => 'CAMISA TEST FOTOS',
    'descripcion' => 'Prueba de fotos de tela',
    'cantidad' => 1,
    'created_at' => now(),
    'updated_at' => now(),
]);

echo "✅ Prenda creada en envío: prenda_cot_id = $prendaEnvioId\n";

// Copiar tallas
for ($i = 0; $i < 3; $i++) {
    DB::table('talla_prenda_cots')->insert([
        'prenda_cot_id' => $prendaEnvioId,
        'talla' => ['S', 'M', 'L'][$i],
        'cantidad' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

// Crear variante en envío
$varianteEnvioId = DB::table('variantes_prenda_cots')->insertGetId([
    'prenda_cot_id' => $prendaEnvioId,
    'genero_id' => 2,
    'color' => 'AZUL',
    'tipo_manga_id' => 4,
    'tipo_broche_id' => 2,
    'tiene_bolsillos' => false,
    'telas_multiples' => json_encode([
        [
            'indice' => 0,
            'color' => 'AZUL',
            'tela' => 'ALGODÓN',
            'referencia' => 'ALG-AZUL-01'
        ],
        [
            'indice' => 1,
            'color' => 'NEGRO',
            'tela' => 'POLIÉSTER',
            'referencia' => 'POL-NEGRO-01'
        ]
    ]),
    'created_at' => now(),
    'updated_at' => now(),
]);

// Crear prenda_telas_cot en envío
$prendaTela1Envio = DB::table('prenda_telas_cot')->insertGetId([
    'prenda_cot_id' => $prendaEnvioId,
    'variante_prenda_cot_id' => $varianteEnvioId,
    'color_id' => $color1,
    'tela_id' => $tela1,
    'created_at' => now(),
    'updated_at' => now(),
]);

$prendaTela2Envio = DB::table('prenda_telas_cot')->insertGetId([
    'prenda_cot_id' => $prendaEnvioId,
    'variante_prenda_cot_id' => $varianteEnvioId,
    'color_id' => $color2,
    'tela_id' => $tela2,
    'created_at' => now(),
    'updated_at' => now(),
]);

echo "✅ Prenda_telas_cot en envío: $prendaTela1Envio, $prendaTela2Envio\n";

// ===== PASO 4: Simular el procesamiento de fotos_existentes =====
echo "\n🔄 Paso 4: Simulando procesarImagenesCotizacion()...\n";

// Este es el dato que vendría del request
$request_prendas = [
    [
        'nombre_producto' => 'CAMISA TEST FOTOS',
        'description' => 'Prueba',
        'telas' => [
            0 => ['fotos_existentes' => json_encode([$foto1])],
            1 => ['fotos_existentes' => json_encode([$foto2])],
        ]
    ]
];

// Simular lo que haría el nuevo código
$prendaModel = DB::table('prendas_cot')
    ->where('cotizacion_id', $cotizacionEnvio)
    ->first();

$telasData = $request_prendas[0]['telas'] ?? [];

$todasLasTelas = DB::table('prenda_telas_cot')
    ->where('prenda_cot_id', $prendaModel->id)
    ->orderBy('id')
    ->get();

foreach ($telasData as $telaIndex => $telaData) {
    $telaIndexInt = (int)$telaIndex;
    $fotosTelaExistentes = $telaData['fotos_existentes'] ?? [];
    
    if (is_string($fotosTelaExistentes)) {
        $fotosTelaExistentes = json_decode($fotosTelaExistentes, true) ?? [];
    }
    
    if (empty($fotosTelaExistentes)) {
        continue;
    }
    
    // Obtener prenda_tela_cot usando slice (como en el código nuevo)
    $prendaTelaCot = $todasLasTelas->slice($telaIndexInt, 1)->first();
    
    if (!$prendaTelaCot) {
        echo "❌ No se encontró prenda_tela_cot para índice $telaIndexInt\n";
        continue;
    }
    
    echo "ℹ️  Procesando tela índice $telaIndexInt: prenda_tela_cot_id = {$prendaTelaCot->id}\n";
    
    // Procesar cada foto existente
    $ordenFotosTela = (DB::table('prenda_tela_fotos_cot')
        ->where('prenda_tela_cot_id', $prendaTelaCot->id)
        ->max('orden') ?? 0) + 1;
    
    foreach ($fotosTelaExistentes as $fotoId) {
        $fotoExistente = DB::table('prenda_tela_fotos_cot')->find($fotoId);
        if (!$fotoExistente) {
            echo "   ❌ Foto $fotoId no encontrada\n";
            continue;
        }
        
        // Verificar si ya existe
        $yaExiste = DB::table('prenda_tela_fotos_cot')
            ->where('prenda_tela_cot_id', $prendaTelaCot->id)
            ->where('ruta_webp', $fotoExistente->ruta_webp)
            ->exists();
        
        if ($yaExiste) {
            echo "   ↔️  Foto ya existe\n";
            continue;
        }
        
        // INSERTAR foto
        DB::table('prenda_tela_fotos_cot')->insert([
            'prenda_cot_id' => $prendaModel->id,
            'prenda_tela_cot_id' => $prendaTelaCot->id,
            'tela_index' => $telaIndexInt,
            'ruta_original' => $fotoExistente->ruta_original,
            'ruta_webp' => $fotoExistente->ruta_webp,
            'ruta_miniatura' => $fotoExistente->ruta_miniatura,
            'orden' => $ordenFotosTela,
            'ancho' => $fotoExistente->ancho,
            'alto' => $fotoExistente->alto,
            'tamaño' => $fotoExistente->tamaño,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        echo "   ✅ Foto guardada: $fotoId -> prenda_tela_cot_id {$prendaTelaCot->id}\n";
    }
}

// ===== PASO 5: Verificación final =====
echo "\n✅ Paso 5: Verificando resultados...\n";

$fotosDraft = DB::table('prenda_tela_fotos_cot')
    ->where('prenda_cot_id', $prendaDraftId)
    ->count();

$fotosEnvio = DB::table('prenda_tela_fotos_cot')
    ->where('prenda_cot_id', $prendaEnvioId)
    ->count();

echo "\n📊 RESULTADOS:\n";
echo "┌─────────────────────────────────────┐\n";
echo "│ Borrador:    $fotosDraft foto(s)\n";
echo "│ Envío:       $fotosEnvio foto(s)\n";
echo "└─────────────────────────────────────┘\n";

if ($fotosEnvio >= 2) {
    echo "\n🎉 ✅ TEST EXITOSO: Las fotos de tela se guardaron correctamente en el envío\n";
    echo "\nDetalles:\n";
    
    $fotosDetalles = DB::table('prenda_tela_fotos_cot')
        ->where('prenda_cot_id', $prendaEnvioId)
        ->get();
    
    foreach ($fotosDetalles as $foto) {
        $tela = DB::table('prenda_telas_cot')->find($foto->prenda_tela_cot_id);
        echo "  • Foto {$foto->id}: tela_cot_id={$foto->prenda_tela_cot_id}, ruta={$foto->ruta_webp}\n";
    }
} else {
    echo "\n❌ TEST FALLIDO: Las fotos no se guardaron ($fotosEnvio < 2)\n";
}

echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║  FIN DEL TEST                                                  ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";
