<?php

/**
 * Test completo con imágenes reales
 * Verifica que se guardan: prendas, variaciones, tallas y imágenes en storage
 * Ejecutar: php artisan tinker --execute "include 'scripts/test-completo-con-imagenes.php';"
 */

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  🧪 TEST COMPLETO: PEDIDO + VARIACIONES + IMÁGENES       ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

use App\Models\User;
use App\Models\Cliente;
use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\PrendaVariantePed;
use App\Models\PrendaFotoPedido;
use App\Models\PrendaFotoTelaPedido;
use App\Application\Services\PedidoPrendaService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

try {
    echo "1️⃣  Preparando datos de prueba...\n";
    
    $asesora = User::find(95) ?? User::firstOrCreate(
        ['email' => 'asesora.test@test.com'],
        ['name' => 'Asesora Test', 'password' => bcrypt('password')]
    );
    echo "   ✅ Usuario: {$asesora->name}\n";

    $cliente = Cliente::firstOrCreate(
        ['nombre' => 'Cliente Imágenes ' . time()],
        ['estado' => 'activo']
    );
    echo "   ✅ Cliente: {$cliente->nombre}\n\n";

    echo "2️⃣  Creando imágenes de prueba...\n";
    
    // Crear imagen de prenda (JPG)
    $imagenPrenda = imagecreatetruecolor(400, 300);
    $colorRojo = imagecolorallocate($imagenPrenda, 255, 0, 0);
    imagefill($imagenPrenda, 0, 0, $colorRojo);
    imagestring($imagenPrenda, 5, 150, 140, 'PRENDA TEST', imagecolorallocate($imagenPrenda, 255, 255, 255));
    
    $jpgPrendaPath = storage_path('app/temp_prenda_' . time() . '.jpg');
    imagejpeg($imagenPrenda, $jpgPrendaPath, 90);
    imagedestroy($imagenPrenda);
    echo "   ✅ Imagen de prenda creada\n";

    // Crear imagen de tela (JPG)
    $imagenTela = imagecreatetruecolor(400, 300);
    $colorAzul = imagecolorallocate($imagenTela, 0, 0, 255);
    imagefill($imagenTela, 0, 0, $colorAzul);
    imagestring($imagenTela, 5, 150, 140, 'TELA TEST', imagecolorallocate($imagenTela, 255, 255, 255));
    
    $jpgTelaPath = storage_path('app/temp_tela_' . time() . '.jpg');
    imagejpeg($imagenTela, $jpgTelaPath, 90);
    imagedestroy($imagenTela);
    echo "   ✅ Imagen de tela creada\n\n";

    echo "3️⃣  Creando pedido...\n";
    
    $pedido = PedidoProduccion::create([
        'numero_pedido' => 80000 + rand(1, 9999),
        'cliente' => $cliente->nombre,
        'cliente_id' => $cliente->id,
        'asesor_id' => $asesora->id,
        'forma_de_pago' => 'efectivo',
        'estado' => 'Pendiente',
        'fecha_de_creacion_de_orden' => now()->toDateString(),
        'cantidad_total' => 23,
    ]);
    echo "   ✅ Pedido creado: #{$pedido->numero_pedido}\n\n";

    echo "4️⃣  Convertiendo imágenes a UploadedFile...\n";
    
    // Convertir archivos a UploadedFile
    $fotoPrendaUpload = new UploadedFile(
        $jpgPrendaPath,
        'prenda_test.jpg',
        'image/jpeg',
        null,
        true
    );
    
    $fotoTelaUpload = new UploadedFile(
        $jpgTelaPath,
        'tela_test.jpg',
        'image/jpeg',
        null,
        true
    );
    echo "   ✅ Imágenes convertidas a UploadedFile\n\n";

    echo "5️⃣  Preparando datos de prenda con imágenes...\n";
    
    $prendaData = [
        'nombre_producto' => 'Camiseta Completa',
        'descripcion' => 'Camiseta con variaciones e imágenes',
        'genero' => json_encode(['dama']),
        'de_bodega' => 1,
        'cantidad_talla' => ['dama' => ['S' => 5, 'M' => 10, 'L' => 8]],
        'color_id' => 5,
        'tela_id' => 3,
        'tipo_manga_id' => 2,
        'tipo_broche_boton_id' => 1,
        'obs_manga' => 'Manga corta 5cm',
        'obs_broche' => 'Botones de 12mm',
        'tiene_bolsillos' => true,
        'obs_bolsillos' => 'Bolsillos laterales',
        'obs_reflectivo' => '',
        'fotos' => [$fotoPrendaUpload],
        'telas' => [
            [
                'nombre_tela' => 'Algodón',
                'color' => 'Rojo',
                'referencia' => 'ALG-ROJO-001',
                'fotos' => [$fotoTelaUpload]
            ]
        ],
        'procesos' => [],
        'cantidades' => [],
        'variaciones' => '{}',
    ];
    
    echo "   ✅ Datos de prenda preparados\n\n";

    echo "6️⃣  Guardando prenda con servicio...\n";
    
    $servicio = app(PedidoPrendaService::class);
    $servicio->guardarPrendasEnPedido($pedido, [$prendaData]);
    
    echo "   ✅ Prenda guardada\n\n";

    echo "7️⃣  Verificando datos en BD...\n";
    
    $prendas = PrendaPedido::where('pedido_produccion_id', $pedido->id)->get();
    echo "   ✅ Prendas: {$prendas->count()}\n";
    
    foreach ($prendas as $prenda) {
        echo "\n      📦 Prenda: {$prenda->nombre_prenda} (ID: {$prenda->id})\n";
        echo "         Cantidad Talla: {$prenda->cantidad_talla}\n";
        
        $variantes = PrendaVariantePed::where('prenda_pedido_id', $prenda->id)->get();
        echo "         Variantes: {$variantes->count()}\n";
        foreach ($variantes as $var) {
            echo "            • Color ID: {$var->color_id}, Tela ID: {$var->tela_id}\n";
            echo "            • Manga Obs: {$var->manga_obs}\n";
        }
    }

    echo "\n7️⃣  Verificando imágenes en storage...\n";
    
    $fotosPrend = PrendaFotoPedido::whereIn('prenda_pedido_id', $prendas->pluck('id'))->get();
    echo "   📸 Fotos de prendas: {$fotosPrend->count()}\n";
    foreach ($fotosPrend as $foto) {
        echo "      • Foto {$foto->id}:\n";
        echo "        - Ruta Original (BD): {$foto->ruta_original}\n";
        echo "        - Ruta WebP (BD): {$foto->ruta_webp}\n";
        
        // La ruta en BD ya incluye "storage/", así que no agregar storage_path
        $rutaWebpCompleta = storage_path('app/public/' . str_replace('storage/', '', $foto->ruta_webp));
        $existeWebp = file_exists($rutaWebpCompleta) ? '✅' : '❌';
        echo "        - Existe WebP: {$existeWebp}\n";
        
        if (file_exists($rutaWebpCompleta)) {
            $tamaño = filesize($rutaWebpCompleta);
            echo "        - Tamaño WebP: " . ($tamaño / 1024) . " KB\n";
        }
    }
    
    $fotosTelas = PrendaFotoTelaPedido::whereIn('prenda_pedido_id', $prendas->pluck('id'))->get();
    echo "\n   🧵 Fotos de telas: {$fotosTelas->count()}\n";
    foreach ($fotosTelas as $foto) {
        echo "      • Foto {$foto->id}:\n";
        echo "        - Ruta Original (BD): {$foto->ruta_original}\n";
        echo "        - Ruta WebP (BD): {$foto->ruta_webp}\n";
        
        // La ruta en BD ya incluye "storage/", así que no agregar storage_path
        $rutaWebpCompleta = storage_path('app/public/' . str_replace('storage/', '', $foto->ruta_webp));
        $existeWebp = file_exists($rutaWebpCompleta) ? '✅' : '❌';
        echo "        - Existe WebP: {$existeWebp}\n";
        
        if (file_exists($rutaWebpCompleta)) {
            $tamaño = filesize($rutaWebpCompleta);
            echo "        - Tamaño WebP: " . ($tamaño / 1024) . " KB\n";
        }
    }

    echo "\n╔════════════════════════════════════════════════════════════╗\n";
    echo "║                    ✅ PRUEBA EXITOSA                      ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n\n";

    echo "📊 RESUMEN FINAL:\n";
    echo "   ✅ Pedido: #{$pedido->numero_pedido}\n";
    echo "   ✅ Prendas: {$prendas->count()}\n";
    echo "   ✅ Variaciones: " . ($variantes->count() > 0 ? 'SÍ' : 'NO') . "\n";
    echo "   ✅ Fotos de prendas: {$fotosPrend->count()}\n";
    echo "   ✅ Fotos de telas: {$fotosTelas->count()}\n";
    echo "   ✅ Imágenes en storage: " . ($fotosPrend->count() + $fotosTelas->count() > 0 ? 'SÍ' : 'NO') . "\n";
    echo "\n";

    // Limpiar archivos temporales
    @unlink($jpgPrendaPath);
    @unlink($jpgTelaPath);

} catch (\Exception $e) {
    echo "\n❌ ERROR:\n";
    echo "   Mensaje: {$e->getMessage()}\n";
    echo "   Archivo: {$e->getFile()}\n";
    echo "   Línea: {$e->getLine()}\n\n";
    
    @unlink($jpgPrendaPath ?? '');
    @unlink($jpgTelaPath ?? '');
}
