<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Cotizacion;

class GuardarProductosCompleteTest extends TestCase
{
    /**
     * Test: Guardar producto completo con prendas, fotos, telas, tallas y variantes
     */
    public function test_guardar_producto_completo()
    {
        // Crear una cotización
        $cotizacion = Cotizacion::create([
            'asesor_id' => 18,
            'numero_cotizacion' => 'COT-TEST-' . time(),
            'tipo' => 'P',
            'estado' => 'BORRADOR',
            'cliente_id' => 667,
            'tipo_venta' => 'M',
            'es_borrador' => false,
        ]);

        echo "\n✅ Cotización creada: " . $cotizacion->numero_cotizacion . "\n";

        // Simular datos de producto como vienen del frontend
        $productos = [
            [
                'nombre_producto' => 'CAMISA TEST',
                'descripcion' => 'Camisa de prueba',
                'cantidad' => 1,
                'tallas' => json_encode(['XS', 'S', 'M', 'L', 'XL']), // JSON string como viene del frontend
                'telas' => [
                    [
                        'color' => 'Azul',
                        'tela' => 'DRILL',
                        'referencia' => 'REF-001'
                    ]
                ],
                'variantes' => [
                    'genero_id' => 2,
                    'tipo_manga_id' => 1,
                    'tipo_broche_id' => 2,
                    'tiene_bolsillos' => true,
                    'tiene_reflectivo' => false,
                    'descripcion_adicional' => 'Prueba de variantes'
                ]
            ]
        ];

        // Usar el servicio para guardar productos
        $service = app(\App\Application\Services\CotizacionPrendaService::class);
        $service->guardarProductosEnCotizacion($cotizacion, $productos);

        // Verificar que todo se guardó correctamente
        $prenda = $cotizacion->prendas()->first();
        
        $this->assertNotNull($prenda, 'Prenda debe estar guardada');
        echo "\n✅ Prenda guardada: " . $prenda->nombre_producto . "\n";

        // Verificar tallas
        $tallas = $prenda->tallas()->get();
        $this->assertCount(5, $tallas, 'Debe haber 5 tallas');
        echo "✅ Tallas guardadas: " . $tallas->count() . "\n";
        foreach ($tallas as $talla) {
            echo "   - " . $talla->talla . "\n";
        }

        // Verificar telas (se guardan en prenda_tela_fotos_cot con las imágenes)
        $telaFotos = $prenda->telaFotos()->get();
        echo "✅ Fotos de telas guardadas: " . $telaFotos->count() . "\n";
        foreach ($telaFotos as $telaFoto) {
            echo "   - " . $telaFoto->ruta_webp . "\n";
        }

        // Verificar variantes
        $variantes = $prenda->variantes()->get();
        $this->assertCount(1, $variantes, 'Debe haber 1 variante');
        echo "✅ Variantes guardadas: " . $variantes->count() . "\n";
        foreach ($variantes as $variante) {
            echo "   - Género ID: " . $variante->genero_id . "\n";
            echo "   - Descripción: " . $variante->descripcion_adicional . "\n";
        }

        echo "\n✅ Test completado exitosamente\n";
    }
}
