<?php

namespace Tests\Feature;

use App\Models\Epp;
use App\Models\PedidoProduccion;
use App\Models\PedidoEpp;
use App\Models\PedidoEppImagen;
use App\Services\PedidoEppService;
use Tests\TestCase;

class GuardarEppTest extends TestCase
{
    /**
     * Test: Guardar EPP con imágenes en un pedido
     */
    public function test_guardar_epp_con_imagenes()
    {
        // Obtener un EPP existente
        $epp = Epp::first();
        if (!$epp) {
            $this->markTestSkipped('No hay EPP disponibles');
        }

        // Obtener un pedido existente
        $pedido = PedidoProduccion::first();
        if (!$pedido) {
            $this->markTestSkipped('No hay pedidos disponibles');
        }

        // Crear servicio
        $service = new PedidoEppService();

        // Datos del EPP a guardar
        $eppsData = [
            [
                'epp_id' => $epp->id,
                'cantidad' => 10,
                'tallas_medidas' => ['talla' => 'L', 'color' => 'Blanco'],
                'observaciones' => 'EPP de prueba',
                'imagenes' => [
                    [
                        'archivo' => '/storage/test-frente.jpg',
                        'principal' => true,
                        'orden' => 0
                    ],
                    [
                        'archivo' => '/storage/test-lateral.jpg',
                        'principal' => false,
                        'orden' => 1
                    ]
                ]
            ]
        ];

        // Guardar EPP
        $resultado = $service->guardarEppsDelPedido($pedido, $eppsData);

        // Verificar que se guardó
        $this->assertCount(1, $resultado);
        $pedidoEpp = $resultado[0];

        // Verificar en BD
        $this->assertDatabaseHas('pedido_epp', [
            'id' => $pedidoEpp->id,
            'pedido_produccion_id' => $pedido->id,
            'epp_id' => $epp->id,
            'cantidad' => 10
        ]);

        // Verificar imágenes
        $imagenes = PedidoEppImagen::where('pedido_epp_id', $pedidoEpp->id)->get();
        $this->assertCount(2, $imagenes);

        // Verificar que la imagen principal existe
        $this->assertTrue(
            $imagenes->where('principal', true)->count() > 0,
            'Debe haber al menos una imagen principal'
        );
    }
}
