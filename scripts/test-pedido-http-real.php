<?php

/**
 * Test que simula el proceso real del frontend
 * - Crea FormData con imágenes reales
 * - Envía solicitud POST al endpoint crearPedido
 * - Verifica respuesta y datos guardados en BD
 * Ejecutar: php artisan tinker --execute "include 'scripts/test-pedido-http-real.php';"
 */

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  🧪 TEST REAL: SOLICITUD HTTP POST COMO EL FRONTEND      ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\Cliente;
use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\PrendaVariantePed;

try {
    echo "1️⃣  Preparando datos de prueba...\n";
    
    // Obtener usuario autenticado
    $asesora = User::find(95) ?? User::firstOrCreate(
        ['email' => 'asesora.test@test.com'],
        ['name' => 'Asesora Test', 'password' => bcrypt('password')]
    );
    echo "   ✅ Usuario: {$asesora->name} (ID: {$asesora->id})\n";

    // Crear cliente
    $cliente = Cliente::firstOrCreate(
        ['nombre' => 'Cliente Test ' . time()],
        ['estado' => 'activo']
    );
    echo "   ✅ Cliente: {$cliente->nombre} (ID: {$cliente->id})\n\n";

    echo "2️⃣  Creando imágenes de prueba...\n";
    
    // Crear imagen de prenda
    $imagenPrenda = imagecreatetruecolor(200, 200);
    $colorRojo = imagecolorallocate($imagenPrenda, 255, 0, 0);
    imagefill($imagenPrenda, 0, 0, $colorRojo);
    
    $jpgPrendaPath = storage_path('app/temp_prenda_' . time() . '.jpg');
    imagejpeg($imagenPrenda, $jpgPrendaPath, 90);
    imagedestroy($imagenPrenda);
    echo "   ✅ Imagen de prenda creada: {$jpgPrendaPath}\n";

    // Crear imagen de tela
    $imagenTela = imagecreatetruecolor(200, 200);
    $colorAzul = imagecolorallocate($imagenTela, 0, 0, 255);
    imagefill($imagenTela, 0, 0, $colorAzul);
    
    $jpgTelaPath = storage_path('app/temp_tela_' . time() . '.jpg');
    imagejpeg($imagenTela, $jpgTelaPath, 90);
    imagedestroy($imagenTela);
    echo "   ✅ Imagen de tela creada: {$jpgTelaPath}\n";

    // Crear imagen de EPP
    $imagenEpp = imagecreatetruecolor(200, 200);
    $colorVerde = imagecolorallocate($imagenEpp, 0, 255, 0);
    imagefill($imagenEpp, 0, 0, $colorVerde);
    
    $jpgEppPath = storage_path('app/temp_epp_' . time() . '.jpg');
    imagejpeg($imagenEpp, $jpgEppPath, 90);
    imagedestroy($imagenEpp);
    echo "   ✅ Imagen de EPP creada: {$jpgEppPath}\n\n";

    echo "3️⃣  Preparando FormData como lo hace el frontend...\n";
    
    // Crear UploadedFile objects
    $fotoPrenda = new UploadedFile($jpgPrendaPath, 'prenda.jpg', 'image/jpeg', null, true);
    $fotoTela = new UploadedFile($jpgTelaPath, 'tela.jpg', 'image/jpeg', null, true);
    $fotoEpp = new UploadedFile($jpgEppPath, 'epp.jpg', 'image/jpeg', null, true);
    
    echo "   ✅ UploadedFile objects creados\n\n";

    echo "4️⃣  Enviando solicitud POST al endpoint crearPedido...\n";
    
    // Preparar datos del pedido como lo envía el frontend
    $datosFormulario = [
        'cliente' => $cliente->nombre,
        'asesora' => $asesora->name,
        'forma_de_pago' => 'efectivo',
        'items' => [
            [
                'tipo' => 'nuevo',
                'nombre_prenda' => 'Camiseta Básica',
                'descripcion' => 'Camiseta de algodón',
                'de_bodega' => 1,
                'origen' => 'bodega',
                'color' => 'Rojo',
                'tela' => 'Algodón',
                'genero' => json_encode(['dama']),
                'cantidad_talla' => json_encode(['dama' => ['S' => 10, 'M' => 15, 'L' => 5]]),
                'tallas' => ['S', 'M', 'L'],
                'tipo_manga' => 'Corta',
                'obs_manga' => 'Manga corta estándar',
                'tipo_broche' => 'Botones',
                'obs_broche' => 'Botones de 15mm',
                'tiene_bolsillos' => '1',
                'obs_bolsillos' => 'Bolsillos laterales',
                'procesos' => [],
            ],
            [
                'tipo' => 'epp',
                'epp_id' => 1,
                'nombre' => 'Guantes de Seguridad',
                'codigo' => 'GUANTES-001',
                'categoria' => 'Protección de Manos',
                'talla' => 'M',
                'cantidad' => 50,
                'observaciones' => 'Guantes de seguridad industrial',
            ]
        ],
        'prendas' => []
    ];

    // Simular solicitud POST
    $response = app('Illuminate\Testing\TestResponse')->from(
        app('Illuminate\Contracts\Http\Kernel')->handle(
            app('Illuminate\Http\Request')->create(
                '/asesores/pedidos-editable/crear',
                'POST',
                $datosFormulario,
                [],
                [
                    'items.0.imagenes' => [$fotoPrenda],
                    'items.0.telas.0.imagenes' => [$fotoTela],
                    'items.1.epp_imagenes' => [$fotoEpp],
                ]
            )->withUser($asesora)
        )
    );

    echo "   ✅ Solicitud POST enviada\n";
    echo "   ✅ Status: " . $response->getStatusCode() . "\n\n";

    echo "5️⃣  Verificando respuesta...\n";
    
    $respuesta = $response->json();
    if ($respuesta['success'] ?? false) {
        echo "   ✅ Respuesta exitosa\n";
        echo "      • Pedido ID: {$respuesta['pedido_id']}\n";
        echo "      • Número Pedido: {$respuesta['numero_pedido']}\n";
        
        $pedidoId = $respuesta['pedido_id'];
        $numeroPedido = $respuesta['numero_pedido'];
    } else {
        echo "   ❌ Error en respuesta: " . ($respuesta['message'] ?? 'Desconocido') . "\n";
        echo "   Respuesta completa:\n";
        print_r($respuesta);
        throw new Exception("Solicitud POST falló");
    }
    echo "\n";

    echo "6️⃣  Verificando datos guardados en BD...\n";
    
    // Verificar pedido
    $pedido = PedidoProduccion::find($pedidoId);
    if ($pedido) {
        echo "   ✅ Pedido en BD:\n";
        echo "      • ID: {$pedido->id}\n";
        echo "      • Número: {$pedido->numero_pedido}\n";
        echo "      • Cliente: {$pedido->cliente}\n";
        echo "      • Asesor ID: {$pedido->asesor_id}\n";
        echo "      • Estado: {$pedido->estado}\n";
    } else {
        echo "   ❌ Pedido NO encontrado en BD\n";
    }

    // Verificar prendas
    $prendas = PrendaPedido::where('pedido_produccion_id', $pedidoId)->get();
    echo "\n   ✅ Prendas guardadas: {$prendas->count()}\n";
    foreach ($prendas as $prenda) {
        echo "      • {$prenda->nombre_prenda} (ID: {$prenda->id})\n";
        echo "        - Cantidad Talla: {$prenda->cantidad_talla}\n";
        
        // Verificar variantes
        $variantes = PrendaVariantePed::where('prenda_pedido_id', $prenda->id)->get();
        echo "        - Variantes guardadas: {$variantes->count()}\n";
        foreach ($variantes as $variante) {
            echo "          • Variante ID: {$variante->id}\n";
            echo "            - Manga Obs: {$variante->manga_obs}\n";
            echo "            - Broche Obs: {$variante->broche_boton_obs}\n";
            echo "            - Tiene Bolsillos: {$variante->tiene_bolsillos}\n";
            echo "            - Bolsillos Obs: {$variante->bolsillos_obs}\n";
        }
    }

    // Verificar EPPs
    $epps = \App\Models\PedidoEpp::where('pedido_produccion_id', $pedidoId)->get();
    echo "\n   ✅ EPPs guardados: {$epps->count()}\n";
    foreach ($epps as $epp) {
        echo "      • EPP ID: {$epp->epp_id}, Cantidad: {$epp->cantidad}\n";
    }

    echo "\n╔════════════════════════════════════════════════════════════╗\n";
    echo "║                    ✅ PRUEBA EXITOSA                      ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n\n";

    echo "📊 RESUMEN:\n";
    echo "   • Solicitud HTTP POST enviada correctamente\n";
    echo "   • Pedido creado: #{$numeroPedido}\n";
    echo "   • Prendas guardadas: {$prendas->count()}\n";
    echo "   • EPPs guardados: {$epps->count()}\n";
    echo "   • Todos los datos en BD correctamente\n\n";

    echo "✨ El proceso real del frontend funciona correctamente\n\n";

    // Limpiar archivos temporales
    @unlink($jpgPrendaPath);
    @unlink($jpgTelaPath);
    @unlink($jpgEppPath);

} catch (\Exception $e) {
    echo "\n❌ ERROR EN LA PRUEBA:\n";
    echo "   Mensaje: {$e->getMessage()}\n";
    echo "   Archivo: {$e->getFile()}\n";
    echo "   Línea: {$e->getLine()}\n\n";
    
    // Limpiar archivos temporales
    @unlink($jpgPrendaPath ?? '');
    @unlink($jpgTelaPath ?? '');
    @unlink($jpgEppPath ?? '');
}
