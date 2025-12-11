<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Http\UploadedFile;

class VerificarCotizacionCompleteTest extends TestCase
{
    /**
     * Test: Enviar cotización COMPLETA con todos los datos
     */
    public function test_enviar_cotizacion_completa_con_archivos()
    {
        $user = \App\Models\User::find(18);
        $this->actingAs($user);

        // Crear archivos de prueba
        $fotosPrenda = [
            UploadedFile::fake()->image('foto1.jpg'),
            UploadedFile::fake()->image('foto2.jpg'),
            UploadedFile::fake()->image('foto3.jpg'),
        ];

        $fotosTelas = [
            UploadedFile::fake()->image('tela1.jpg'),
            UploadedFile::fake()->image('tela2.jpg'),
        ];

        $fotosLogo = [
            UploadedFile::fake()->image('logo1.jpg'),
            UploadedFile::fake()->image('logo2.jpg'),
        ];

        // Enviar cotización
        $response = $this->post('/asesores/cotizaciones/guardar', [
            'tipo' => 'enviada',
            'cliente' => 'CLIENTE COMPLETO TEST',
            'tipo_venta' => 'M',
            'tipo_cotizacion' => 'P',
            'especificaciones' => json_encode(['disponibilidad' => ['Bodega']]),
            'tecnicas' => json_encode(['BORDADO']),
            'observaciones_tecnicas' => 'Test observaciones',
            'ubicaciones' => json_encode([]),
            'observaciones_generales' => json_encode([]),
            
            // Productos con archivos
            'productos' => [
                [
                    'nombre_producto' => 'CAMISA COMPLETA',
                    'descripcion' => 'Descripción completa',
                    'cantidad' => 1,
                    'tallas' => json_encode(['XS', 'S', 'M']),
                    'variantes' => [
                        'genero_id' => 2,
                        'tipo_manga_id' => 1,
                        'tipo_broche_id' => 2,
                        'tiene_bolsillos' => true,
                        'obs_bolsillos' => 'Con bolsillos',
                        'tiene_reflectivo' => true,
                        'obs_reflectivo' => 'Con reflectivo',
                        'descripcion_adicional' => 'Descripción de variantes'
                    ]
                ]
            ],
            
            // Archivos de prenda
            'productos.0.fotos' => $fotosPrenda,
            'productos.0.telas' => $fotosTelas,
            
            // Archivos de logo
            'logo.imagenes' => $fotosLogo,
            'logo.descripcion' => 'Logo de prueba'
        ]);

        echo "\n📊 Response Status: " . $response->status() . "\n";
        
        if ($response->status() === 201) {
            $cotizacionId = $response->json()['data']['id'];
            echo "✅ Cotización creada: " . $response->json()['data']['numero_cotizacion'] . "\n";
            
            // Verificar prendas
            $cotizacion = \App\Models\Cotizacion::find($cotizacionId);
            $prenda = $cotizacion->prendas()->first();
            
            if ($prenda) {
                echo "\n✅ Prenda guardada: " . $prenda->nombre_producto . "\n";
                echo "   - Fotos de prenda: " . $prenda->fotos()->count() . "\n";
                echo "   - Fotos de telas: " . $prenda->telaFotos()->count() . "\n";
                echo "   - Tallas: " . $prenda->tallas()->count() . "\n";
                echo "   - Variantes: " . $prenda->variantes()->count() . "\n";
                
                // Verificar variante
                $variante = $prenda->variantes()->first();
                if ($variante) {
                    echo "\n✅ Variante guardada:\n";
                    echo "   - Género ID: " . $variante->genero_id . "\n";
                    echo "   - Tipo Manga ID: " . $variante->tipo_manga_id . "\n";
                    echo "   - Tipo Broche ID: " . $variante->tipo_broche_id . "\n";
                    echo "   - Tiene Bolsillos: " . ($variante->tiene_bolsillos ? 'SÍ' : 'NO') . "\n";
                    echo "   - Obs Bolsillos: " . $variante->obs_bolsillos . "\n";
                    echo "   - Tiene Reflectivo: " . ($variante->tiene_reflectivo ? 'SÍ' : 'NO') . "\n";
                    echo "   - Obs Reflectivo: " . $variante->obs_reflectivo . "\n";
                }
            }
            
            // Verificar logo
            $logo = \App\Models\LogoCotizacion::where('cotizacion_id', $cotizacionId)->first();
            if ($logo) {
                echo "\n✅ Logo guardado:\n";
                echo "   - Descripción: " . $logo->descripcion . "\n";
                echo "   - Fotos: " . $logo->fotos()->count() . "\n";
            } else {
                echo "\n❌ Logo NO se guardó\n";
            }
        } else {
            echo "❌ Error: " . json_encode($response->json()) . "\n";
        }

        $this->assertTrue($response->status() === 201);
    }
}
