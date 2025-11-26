<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== TEST: Crear Cotización con Variantes ===\n\n";

// Datos de prueba - similar a lo que envía el frontend
$datos = [
    'cliente' => 'Test Cliente ' . date('YmdHis'),
    'telefono' => '1234567890',
    'email' => 'test@test.com',
    'observaciones_generales' => [
        [
            'texto' => 'Aplicar en zona pecho',
            'tipo' => 'logo'
        ]
    ],
    'tecnicas' => [
        'bordado',
        'estampado'
    ],
    'productos' => [
        [
            'nombre_producto' => 'CAMISA PRUEBA',
            'cantidad' => 50,
            'tallas' => [
                'XS' => 5,
                'S' => 10,
                'M' => 15,
                'L' => 10,
                'XL' => 10
            ],
            'variantes' => [
                'color' => 'Azul',
                'tela' => 'Algodón',
                'tipo_manga_id' => 'CORTA',
                'tiene_bolsillos' => true,
                'tiene_reflectivo' => false,
                'obs_bolsillos' => 'Bolsillos laterales',
                'obs_broche' => '',
                'obs_reflectivo' => '',
                'genero' => 'Unisex'
            ]
        ]
    ]
];

// Simular el controlador
try {
    $controller = new \App\Http\Controllers\Asesores\CotizacionesController(
        app(\App\Services\CotizacionService::class),
        app(\App\Services\PrendaService::class),
        app(\App\Services\ImagenCotizacionService::class),
        app(\App\Services\PedidoService::class),
        app(\App\Services\FormatterService::class)
    );
    
    // Simular una request
    $request = \Illuminate\Http\Request::create('/api/cotizaciones/guardar', 'POST', $datos);
    $request->headers->set('Accept', 'application/json');
    
    // Mock Auth
    \Illuminate\Support\Facades\Auth::shouldReceive('id')->andReturn(1);
    
    // Llamar al método que guarda la cotización
    $response = $controller->guardar($request);
    
    echo "✅ Cotización creada exitosamente\n";
    echo "Response: " . json_encode($response->getData(), JSON_PRETTY_PRINT) . "\n";
    
    // Verificar que se guardaron las variantes
    $cotizacionId = $response->getData()->cotizacion_id;
    $prendas = DB::table('prendas_cotizaciones')->where('cotizacion_id', $cotizacionId)->get();
    foreach ($prendas as $prenda) {
        $variantes = DB::table('variantes_prenda')->where('prenda_cotizacion_id', $prenda->id)->count();
        echo "✓ Prenda {$prenda->id}: {$variantes} variantes guardadas\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Error al crear cotización:\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " (línea {$e->getLine()})\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
}
