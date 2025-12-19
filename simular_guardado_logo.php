<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PedidoProduccion;
use App\Models\LogoCotizacion;
use App\Models\LogoPedido;
use App\Models\LogoPedidoImagen;

echo "\n====================================\n";
echo "  SIMULACIÓN DE GUARDADO LOGO\n";
echo "====================================\n\n";

// 1. Obtener datos existentes
$pedido = PedidoProduccion::first();
$logoCotizacion = LogoCotizacion::first();

if (!$pedido || !$logoCotizacion) {
    echo "❌ No hay datos de prueba disponibles\n";
    exit(1);
}

echo "📦 Datos base:\n";
echo "   Pedido ID: " . $pedido->id . "\n";
echo "   Logo Cotización ID: " . $logoCotizacion->id . "\n\n";

// 2. Simular datos del formulario
$datosFormulario = [
    'pedido_id' => $pedido->id,
    'logo_cotizacion_id' => $logoCotizacion->id,
    'descripcion' => 'Test de LOGO pedido - ' . date('Y-m-d H:i:s'),
    'tecnicas' => [
        ['nombre' => 'Bordado', 'puntos' => 5000],
        ['nombre' => 'Serigrafía', 'puntos' => 3000],
    ],
    'observaciones_tecnicas' => 'Observación de prueba para técnicas',
    'ubicaciones' => [
        [
            'nombre' => 'CAMISA',
            'posicion' => 'PECHO',
            'observaciones' => 'Observación de pecho',
        ],
        [
            'nombre' => 'JEAN_SUDADERA',
            'posicion' => 'PIERNA IZQUIERDA',
            'observaciones' => 'Observación de pierna',
        ],
    ],
    'fotos' => [
        [
            'existing' => true,
            'id' => 1,
            'nombre' => 'foto_original.jpg',
            'url' => 'https://example.com/logo.jpg',
            'ruta_original' => 'logo_cotizaciones/1/logo.jpg',
            'tipo' => 'image/jpeg',
            'tamaño' => 125000,
        ]
    ]
];

echo "📝 Datos simulados del formulario:\n";
echo "   Descripción: " . $datosFormulario['descripcion'] . "\n";
echo "   Técnicas: " . count($datosFormulario['tecnicas']) . "\n";
echo "   Ubicaciones: " . count($datosFormulario['ubicaciones']) . "\n";
echo "   Fotos: " . count($datosFormulario['fotos']) . "\n\n";

// 3. Crear LogoPedido
echo "💾 Creando LogoPedido...\n";

try {
    // Generar número
    $numeroPedido = LogoPedido::generarNumeroPedido();
    echo "   Número generado: " . $numeroPedido . "\n";
    
    // Crear registro
    $logoPedido = LogoPedido::create([
        'pedido_id' => $datosFormulario['pedido_id'],
        'logo_cotizacion_id' => $datosFormulario['logo_cotizacion_id'],
        'numero_pedido' => $numeroPedido,
        'descripcion' => $datosFormulario['descripcion'],
        'tecnicas' => $datosFormulario['tecnicas'],
        'observaciones_tecnicas' => $datosFormulario['observaciones_tecnicas'],
        'ubicaciones' => $datosFormulario['ubicaciones'],
    ]);
    
    echo "   ✅ LogoPedido creado con ID: " . $logoPedido->id . "\n\n";
    
    // 4. Crear referencias de imágenes
    echo "💾 Creando referencias de imágenes...\n";
    
    foreach ($datosFormulario['fotos'] as $index => $foto) {
        $imagen = LogoPedidoImagen::create([
            'logo_pedido_id' => $logoPedido->id,
            'nombre_archivo' => $foto['nombre'],
            'url' => $foto['url'],
            'ruta_original' => $foto['ruta_original'],
            'ruta_webp' => null,
            'tipo_archivo' => $foto['tipo'],
            'tamaño_archivo' => $foto['tamaño'],
            'orden' => $index + 1,
        ]);
        
        echo "   ✅ Imagen #" . ($index + 1) . " creada con ID: " . $imagen->id . "\n";
    }
    
    echo "\n";
    
    // 5. Verificar que se guardó todo
    echo "✅ VERIFICACIÓN FINAL:\n";
    echo "   LogoPedido ID: " . $logoPedido->id . "\n";
    echo "   Número Pedido: " . $logoPedido->numero_pedido . "\n";
    echo "   Pedido Producción: " . $logoPedido->pedido_id . "\n";
    echo "   Logo Cotización: " . $logoPedido->logo_cotizacion_id . "\n";
    echo "   Descripción guardada: " . $logoPedido->descripcion . "\n";
    echo "   Técnicas guardadas: " . count($logoPedido->tecnicas ?? []) . "\n";
    echo "   Ubicaciones guardadas: " . count($logoPedido->ubicaciones ?? []) . "\n";
    echo "   Imágenes guardadas: " . $logoPedido->imagenes()->count() . "\n";
    
    echo "\n📷 Detalle de imágenes:\n";
    foreach ($logoPedido->imagenes as $img) {
        echo "   - Orden " . $img->orden . ": " . $img->nombre_archivo . "\n";
        echo "     URL: " . $img->url . "\n";
        echo "     Ruta: " . $img->ruta_original . "\n";
    }
    
    echo "\n✅ SIMULACIÓN COMPLETADA EXITOSAMENTE\n\n";
    
} catch (\Throwable $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
