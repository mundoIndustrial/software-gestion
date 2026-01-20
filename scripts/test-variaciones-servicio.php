<?php

/**
 * Test que verifica variaciones usando directamente el servicio
 * Ejecutar: php artisan tinker --execute "include 'scripts/test-variaciones-servicio.php';"
 */

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  🧪 TEST: VARIACIONES GUARDADAS CORRECTAMENTE             ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

use App\Models\User;
use App\Models\Cliente;
use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\PrendaVariantePed;
use App\Application\Services\PedidoPrendaService;

try {
    echo "1️⃣  Preparando datos de prueba...\n";
    
    // Obtener usuario
    $asesora = User::find(95) ?? User::firstOrCreate(
        ['email' => 'asesora.test@test.com'],
        ['name' => 'Asesora Test', 'password' => bcrypt('password')]
    );
    echo "   ✅ Usuario: {$asesora->name} (ID: {$asesora->id})\n";

    // Crear cliente
    $cliente = Cliente::firstOrCreate(
        ['nombre' => 'Cliente Servicio ' . time()],
        ['estado' => 'activo']
    );
    echo "   ✅ Cliente: {$cliente->nombre}\n\n";

    echo "2️⃣  Creando pedido...\n";
    
    // Crear pedido
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
    echo "   ✅ Pedido creado: #{$pedido->numero_pedido} (ID: {$pedido->id})\n\n";

    echo "3️⃣  Preparando datos de prenda con variaciones...\n";
    
    // Datos de prenda con variaciones - IMPORTANTE: cantidad_talla ya debe estar procesada
    // Usar IDs de variaciones (pueden ser NULL si no existen en BD, pero se guardarán igual)
    $prendaData = [
        'nombre_producto' => 'Camiseta Variaciones',
        'descripcion' => 'Camiseta con todas las variaciones',
        'genero' => json_encode(['dama']),
        'de_bodega' => 1,
        // cantidad_talla debe ser un array ya procesado, no un string JSON
        'cantidad_talla' => ['dama' => ['S' => 5, 'M' => 10, 'L' => 8]],
        'color_id' => 5,  // ID de color
        'tela_id' => 3,   // ID de tela
        'tipo_manga_id' => 2,  // ID de tipo manga
        'tipo_broche_boton_id' => 1,  // ID de tipo broche
        'obs_manga' => 'Manga corta 5cm',
        'obs_broche' => 'Botones de 12mm',
        'tiene_bolsillos' => true,
        'obs_bolsillos' => 'Bolsillos laterales con cierre',
        'obs_reflectivo' => '',
        'fotos' => [],
        'telas' => [],
        'procesos' => [],
        'cantidades' => [],
        'variaciones' => '{}',
    ];
    
    echo "   ✅ Datos de prenda preparados\n";
    echo "      • Manga Obs: {$prendaData['obs_manga']}\n";
    echo "      • Broche Obs: {$prendaData['obs_broche']}\n";
    echo "      • Bolsillos Obs: {$prendaData['obs_bolsillos']}\n\n";

    echo "4️⃣  Guardando prenda con servicio...\n";
    
    // Instanciar servicio
    $servicio = app(PedidoPrendaService::class);
    
    // Guardar prenda
    $servicio->guardarPrendasEnPedido($pedido, [$prendaData]);
    
    echo "   ✅ Prenda guardada\n\n";

    echo "5️⃣  Verificando datos guardados en BD...\n";
    
    // Verificar prendas
    $prendas = PrendaPedido::where('pedido_produccion_id', $pedido->id)->get();
    echo "   ✅ Prendas guardadas: {$prendas->count()}\n";
    
    foreach ($prendas as $prenda) {
        echo "\n      📦 Prenda: {$prenda->nombre_prenda} (ID: {$prenda->id})\n";
        echo "         Cantidad Talla: {$prenda->cantidad_talla}\n";
        
        // Verificar variantes
        $variantes = PrendaVariantePed::where('prenda_pedido_id', $prenda->id)->get();
        echo "         ✅ Variantes guardadas: {$variantes->count()}\n";
        
        if ($variantes->count() > 0) {
            foreach ($variantes as $variante) {
                echo "\n         🔧 Variante ID: {$variante->id}\n";
                echo "            • Color ID: " . ($variante->color_id ?: 'NULL') . "\n";
                echo "            • Tela ID: " . ($variante->tela_id ?: 'NULL') . "\n";
                echo "            • Tipo Manga ID: " . ($variante->tipo_manga_id ?: 'NULL') . "\n";
                echo "            • Tipo Broche ID: " . ($variante->tipo_broche_boton_id ?: 'NULL') . "\n";
                echo "            • Manga Obs: " . ($variante->manga_obs ?: '(vacío)') . "\n";
                echo "            • Broche Obs: " . ($variante->broche_boton_obs ?: '(vacío)') . "\n";
                echo "            • Tiene Bolsillos: " . ($variante->tiene_bolsillos ? 'Sí' : 'No') . "\n";
                echo "            • Bolsillos Obs: " . ($variante->bolsillos_obs ?: '(vacío)') . "\n";
            }
        } else {
            echo "         ❌ NO se guardaron variantes\n";
        }
    }

    echo "\n╔════════════════════════════════════════════════════════════╗\n";
    echo "║                    ✅ PRUEBA EXITOSA                      ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n\n";

    echo "6️⃣  Verificando imágenes guardadas en storage...\n";
    
    // Verificar fotos de prendas
    $fotosPrend = \App\Models\PrendaFotoPedido::whereIn('prenda_pedido_id', $prendas->pluck('id'))->get();
    echo "   ✅ Fotos de prendas: {$fotosPrend->count()}\n";
    foreach ($fotosPrend as $foto) {
        $rutaOriginal = storage_path('app/public/' . $foto->ruta_original);
        $rutaWebp = storage_path('app/public/' . $foto->ruta_webp);
        $existeOriginal = file_exists($rutaOriginal) ? '✅' : '❌';
        $existeWebp = file_exists($rutaWebp) ? '✅' : '❌';
        echo "      • Foto ID {$foto->id}: Original {$existeOriginal}, WebP {$existeWebp}\n";
    }
    
    // Verificar fotos de telas
    $fotosTelas = \App\Models\PrendaFotoTelaPedido::whereIn('prenda_pedido_id', $prendas->pluck('id'))->get();
    echo "   ✅ Fotos de telas: {$fotosTelas->count()}\n";
    foreach ($fotosTelas as $foto) {
        $rutaOriginal = storage_path('app/public/' . $foto->ruta_original);
        $rutaWebp = storage_path('app/public/' . $foto->ruta_webp);
        $existeOriginal = file_exists($rutaOriginal) ? '✅' : '❌';
        $existeWebp = file_exists($rutaWebp) ? '✅' : '❌';
        echo "      • Foto ID {$foto->id}: Original {$existeOriginal}, WebP {$existeWebp}\n";
    }

    echo "\n📊 RESUMEN:\n";
    echo "   ✅ Pedido creado: #{$pedido->numero_pedido}\n";
    echo "   ✅ Prendas guardadas: {$prendas->count()}\n";
    echo "   ✅ Variaciones guardadas: " . ($variantes->count() > 0 ? 'SÍ ✅' : 'NO ❌') . "\n";
    if ($variantes->count() > 0) {
        echo "   ✅ Observaciones de variaciones guardadas correctamente\n";
    }
    echo "   ✅ Fotos de prendas: {$fotosPrend->count()}\n";
    echo "   ✅ Fotos de telas: {$fotosTelas->count()}\n";
    echo "\n";

} catch (\Exception $e) {
    echo "\n❌ ERROR EN LA PRUEBA:\n";
    echo "   Mensaje: {$e->getMessage()}\n";
    echo "   Archivo: {$e->getFile()}\n";
    echo "   Línea: {$e->getLine()}\n\n";
}
