<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Http\UploadedFile;

class DebugProductosTest extends TestCase
{
    /**
     * Test: Simular envío de cotización con productos desde frontend
     */
    public function test_enviar_cotizacion_con_productos()
    {
        // Autenticar como usuario asesor
        $user = \App\Models\User::find(18); // Usuario de prueba
        $this->actingAs($user);

        // Simular datos como vienen del frontend
        $response = $this->post('/asesores/cotizaciones/guardar', [
            'tipo' => 'enviada',
            'cliente' => 'CLIENTE TEST',
            'tipo_venta' => 'M',
            'tipo_cotizacion' => 'P',
            'especificaciones' => json_encode([
                'disponibilidad' => ['Bodega'],
                'forma_pago' => ['Crédito'],
            ]),
            'tecnicas' => json_encode(['BORDADO']),
            'observaciones_tecnicas' => 'Test observaciones',
            'ubicaciones' => json_encode([]),
            'observaciones_generales' => json_encode([]),
            
            // Productos con estructura correcta
            'productos' => [
                [
                    'nombre_producto' => 'CAMISA TEST DEBUG',
                    'descripcion' => 'Descripción de prueba',
                    'cantidad' => 1,
                    'tallas' => json_encode(['XS', 'S', 'M']),
                    'variantes' => [
                        'genero_id' => 2,
                        'tipo_manga_id' => 1,
                        'tiene_bolsillos' => true,
                        'descripcion_adicional' => 'Test variantes'
                    ]
                ]
            ]
        ]);

        echo "\n📊 Response Status: " . $response->status() . "\n";
        echo "📊 Response Data: " . json_encode($response->json(), JSON_PRETTY_PRINT) . "\n";

        // Verificar que la cotización se creó
        $this->assertTrue($response->json()['success'] ?? false, 'Cotización debe crearse exitosamente');

        // Verificar que la prenda se guardó
        $cotizacionId = $response->json()['data']['id'] ?? null;
        if ($cotizacionId) {
            $cotizacion = \App\Models\Cotizacion::find($cotizacionId);
            $prenda = $cotizacion->prendas()->first();
            
            echo "\n✅ Cotización creada: " . $cotizacion->numero_cotizacion . "\n";
            
            if ($prenda) {
                echo "✅ Prenda guardada: " . $prenda->nombre_producto . "\n";
                echo "   - Tallas: " . $prenda->tallas()->count() . "\n";
                echo "   - Variantes: " . $prenda->variantes()->count() . "\n";
            } else {
                echo "❌ No se guardó la prenda\n";
            }
        }

        $this->assertTrue(true);
    }
}
