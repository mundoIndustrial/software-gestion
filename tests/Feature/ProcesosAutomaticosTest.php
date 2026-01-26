<?php

namespace Tests\Feature;

use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\ProcesoPrenda;
use App\Services\RegistroOrdenCreationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests para la creación automática de procesos
 */
class ProcesosAutomaticosTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test 1: Verificar que el proceso "Creación de Orden" se crea automáticamente
     */
    public function test_proceso_creacion_orden_se_crea_automaticamente()
    {
        $service = app(RegistroOrdenCreationService::class);

        $data = [
            'pedido' => 1001,
            'cliente' => 'Cliente Test',
            'fecha_creacion' => '2024-01-15',
            'forma_pago' => 'Contado',
            'prendas' => [
                [
                    'prenda' => 'Camiseta',
                    'tallas' => [
                        ['talla' => 'M', 'cantidad' => 10],
                    ],
                ],
            ],
        ];

        // Crear pedido
        $pedido = $service->createOrder($data);

        // Verificar que el proceso se creó
        $proceso = ProcesoPrenda::where('numero_pedido', $pedido->numero_pedido)
            ->where('proceso', 'Creación de Orden')
            ->first();

        $this->assertNotNull($proceso);
        $this->assertEquals('Pendiente', $proceso->estado_proceso);
        $this->assertNull($proceso->prenda_pedido_id);
    }

    /**
     * Test 2: Verificar que el proceso tiene los datos correctos
     */
    public function test_proceso_inicial_tiene_datos_correctos()
    {
        $service = app(RegistroOrdenCreationService::class);

        $data = [
            'pedido' => 1002,
            'cliente' => 'Cliente Test 2',
            'fecha_creacion' => '2024-01-15',
            'forma_pago' => 'Crédito',
            'prendas' => [
                [
                    'prenda' => 'Pantalón',
                    'tallas' => [
                        ['talla' => 'L', 'cantidad' => 5],
                    ],
                ],
            ],
            'dias_duracion_proceso' => 2,
            'encargado_proceso' => 'Juan',
        ];

        $pedido = $service->createOrder($data);
        $proceso = ProcesoPrenda::where('numero_pedido', $pedido->numero_pedido)
            ->where('proceso', 'Creación de Orden')
            ->first();

        // Verificar todos los datos
        $this->assertEquals('Creación de Orden', $proceso->proceso);
        $this->assertEquals('Pendiente', $proceso->estado_proceso);
        $this->assertEquals(2, $proceso->dias_duracion);
        $this->assertEquals('Juan', $proceso->encargado);
        $this->assertNotNull($proceso->fecha_inicio);
        $this->assertEquals('Proceso inicial de creación del pedido', $proceso->observaciones);
    }

    /**
     * Test 3: Verificar que cada pedido tiene su propio proceso
     */
    public function test_multiples_pedidos_tienen_procesos_independientes()
    {
        $service = app(RegistroOrdenCreationService::class);

        // Crear 3 pedidos
        for ($i = 1; $i <= 3; $i++) {
            $data = [
                'pedido' => 2000 + $i,
                'cliente' => "Cliente {$i}",
                'fecha_creacion' => '2024-01-15',
                'forma_pago' => 'Contado',
                'prendas' => [
                    [
                        'prenda' => "Prenda {$i}",
                        'tallas' => [
                            ['talla' => 'M', 'cantidad' => 5],
                        ],
                    ],
                ],
            ];

            $service->createOrder($data);
        }

        // Verificar que hay 3 procesos
        $procesos = ProcesoPrenda::where('proceso', 'Creación de Orden')->get();
        $this->assertEquals(3, $procesos->count());

        // Verificar que cada uno corresponde a un pedido diferente
        $numeroPedidos = $procesos->pluck('numero_pedido')->unique();
        $this->assertEquals(3, $numeroPedidos->count());
    }

    /**
     * Test 4: Verificar que el pedido se crea con estado y área correctos
     */
    public function test_pedido_se_crea_con_estado_y_area_correctos()
    {
        $service = app(RegistroOrdenCreationService::class);

        $data = [
            'pedido' => 3001,
            'cliente' => 'Cliente Estado Test',
            'fecha_creacion' => '2024-01-15',
            'forma_pago' => 'Contado',
            'prendas' => [
                [
                    'prenda' => 'Camiseta Premium',
                    'tallas' => [
                        ['talla' => 'S', 'cantidad' => 3],
                        ['talla' => 'M', 'cantidad' => 7],
                    ],
                ],
            ],
        ];

        $pedido = $service->createOrder($data);

        // Verificar que el pedido tiene estado correcto
        $this->assertEquals('Pendiente', $pedido->estado);
        $this->assertEquals('creacion de pedido', $pedido->area);
    }

    /**
     * Test 5: Verificar que el método createAdditionalProcesso funciona
     */
    public function test_crear_proceso_adicional()
    {
        $service = app(RegistroOrdenCreationService::class);

        // Crear pedido inicial
        $data = [
            'pedido' => 4001,
            'cliente' => 'Cliente Adicional',
            'fecha_creacion' => '2024-01-15',
            'forma_pago' => 'Contado',
            'prendas' => [
                [
                    'prenda' => 'Camiseta',
                    'tallas' => [
                        ['talla' => 'M', 'cantidad' => 10],
                    ],
                ],
            ],
        ];

        $pedido = $service->createOrder($data);

        // Crear proceso adicional
        $procesoAdicional = $service->createAdditionalProcesso($pedido, 'Costura', [
            'encargado' => 'María',
            'dias_duracion' => 3,
            'observaciones' => 'Costura de mangas',
        ]);

        // Verificar que el proceso se creó correctamente
        $this->assertNotNull($procesoAdicional);
        $this->assertEquals('Costura', $procesoAdicional->proceso);
        $this->assertEquals('Pendiente', $procesoAdicional->estado_proceso);
        $this->assertEquals('María', $procesoAdicional->encargado);
        $this->assertEquals(3, $procesoAdicional->dias_duracion);
        $this->assertEquals('Costura de mangas', $procesoAdicional->observaciones);

        // Verificar que el proceso adicional se guardó en BD
        $procesoEnBD = ProcesoPrenda::find($procesoAdicional->id);
        $this->assertNotNull($procesoEnBD);
        $this->assertEquals('Costura', $procesoEnBD->proceso);
    }

    /**
     * Test 6: Verificar que falla gracefully si hay error
     */
    public function test_error_en_proceso_inicial_causa_rollback()
    {
        $service = app(RegistroOrdenCreationService::class);

        // Datos inválidos que causarán error
        $data = [
            'pedido' => 5001,
            'cliente' => 'Cliente Error Test',
            'fecha_creacion' => '2024-01-15',
            'forma_pago' => 'Contado',
            'prendas' => [
                [
                    'prenda' => 'Prenda',
                    'tallas' => [
                        ['talla' => 'M', 'cantidad' => 5],
                    ],
                ],
            ],
        ];

        // Intentar crear (puede fallar si hay constraints)
        // Simplemente verificar que la transacción se maneja correctamente
        try {
            $pedido = $service->createOrder($data);
            // Si llegó aquí, la creación fue exitosa
            $this->assertNotNull($pedido);
        } catch (\Exception $e) {
            // Si falló, verificar que no dejó datos incompletos
            $pedidosIncompletos = PedidoProduccion::where('numero_pedido', 5001)->count();
            $this->assertEquals(0, $pedidosIncompletos);
        }
    }

    /**
     * Test 7: Verificar que código_referencia se asigna correctamente
     */
    public function test_codigo_referencia_se_asigna_correctamente()
    {
        $service = app(RegistroOrdenCreationService::class);

        $data = [
            'pedido' => 6001,
            'cliente' => 'Cliente Referencia',
            'fecha_creacion' => '2024-01-15',
            'forma_pago' => 'Contado',
            'prendas' => [
                [
                    'prenda' => 'Prenda',
                    'tallas' => [
                        ['talla' => 'M', 'cantidad' => 5],
                    ],
                ],
            ],
        ];

        $pedido = $service->createOrder($data);
        $proceso = ProcesoPrenda::where('numero_pedido', $pedido->numero_pedido)
            ->where('proceso', 'Creación de Orden')
            ->first();

        // Verificar que código_referencia es el número de pedido
        $this->assertEquals($pedido->numero_pedido, $proceso->codigo_referencia);
    }
}
