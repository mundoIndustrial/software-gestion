#!/usr/bin/env php
<?php
/**
 * Test interactivo: Crear borrador y enviarlo como cotización
 * Simula el flujo completo desde UI
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Infrastructure\Http\Controllers\CotizacionController;
use Illuminate\Http\Request;

echo "\n=== TEST INTERACTIVO: ENVÍO CON FOTOS DE TELA ===\n\n";

// Datos para crear el draft
$dataTest = [
    'tipo' => 'borrador',
    'accion' => 'guardar',
    'es_borrador' => '1',
    'cliente' => 'TEST CLIENTE',
    'tipo_venta' => 'D',
    'tipo_cotizacion' => '1',
    'descripcion_logo' => '',
    'tecnicas' => '[]',
    'observaciones_tecnicas' => '',
    'ubicaciones' => '[]',
    'observaciones_generales' => '[]',
    'especificaciones' => json_encode([
        'disponibilidad' => [['valor' => 'Bodega', 'observacion' => '']],
        'forma_pago' => [],
        'regimen' => [],
        'se_ha_vendido' => [],
        'ultima_venta' => [],
        'flete' => [],
    ]),
    'prendas' => [
        [
            'nombre_producto' => 'CAMISA TEST',
            'descripcion' => 'Camisa de prueba',
            'cantidad' => '1',
            'tallas' => json_encode(['S', 'M', 'L']),
            'variantes' => [
                'genero_id' => '2',
                'genero' => 'Caballero',
                'color' => 'AZUL',
                'tipo_manga_id' => '4',
                'tipo_manga' => 'Manga 4',
                'obs_manga' => 'Normal',
                'tiene_bolsillos' => '0',
                'obs_bolsillos' => '',
                'tipo_broche_id' => '2',
                'obs_broche' => 'Botones',
                'tiene_reflectivo' => '0',
                'obs_reflectivo' => '',
                'telas_multiples' => json_encode([
                    [
                        'indice' => 0,
                        'color' => 'AZUL',
                        'tela' => 'TELA_AZUL',
                        'referencia' => 'REF_AZUL'
                    ]
                ]),
            ],
        ]
    ],
];

echo "📝 Creando cotización de prueba...\n";

// Usar el repositorio para crear
$cotizacionData = [];
$usuario = DB::table('usuarios')->where('id', 92)->first(); // User ID from logs

if (!$usuario) {
    echo "❌ No se encontró usuario 92\n";
    exit(1);
}

echo "✅ Usuario encontrado: {$usuario->email}\n\n";

echo "=== FIN TEST ===\n\n";
