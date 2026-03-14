<?php

namespace Tests\Feature\UseCases;

use Tests\TestCase;
use App\Models\User;
use App\Models\PedidoProduccion;
use App\Models\PedidoEpp;
use App\Models\Epp;
use App\Domain\Pedidos\Repositories\PedidoProduccionRepository;
use App\Domain\Pedidos\Services\PedidoImagenesService;
use App\Application\UseCases\Pedidos\ActualizarBorradorUseCase;
use App\Application\UseCases\Pedidos\ActualizarBorradorInput;
use Illuminate\Http\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * ActualizarBorradorUseCaseTest
 * 
 * Suite de tests para ActualizarBorradorUseCase
 * 
 * Valida:
 * - Seguridad: Solo asesor propietario puede actualizar
 * - Actualización: Datos básicos se actualizan correctamente
 * - Validación JSON: Rechaza JSON inválido
 * - Transaccionalidad: Rollback si hay error
 * 
 * @package Tests\Feature\UseCases
 */
class ActualizarBorradorUseCaseTest extends TestCase
{
    use RefreshDatabase;

    private ActualizarBorradorUseCase $useCase;
    private PedidoProduccionRepository $repository;
    private PedidoImagenesService $imagenService;
    private User $asesor;
    private PedidoProduccion $pedido;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear asesor
        $this->asesor = User::factory()->create(['name' => 'Asesor Test']);

        // Crear pedido de prueba
        $this->pedido = PedidoProduccion::create([
            'asesor_id' => $this->asesor->id,
            'numero_pedido' => 'BORR-001',
            'cliente' => 'Cliente Test',
            'estado' => 'Borrador',
            'forma_de_pago' => 'Contado',
            'observaciones' => 'Observación inicial',
        ]);

        // Obtener servicios
        $this->repository = app(PedidoProduccionRepository::class);
        $this->imagenService = app(PedidoImagenesService::class);
        $this->useCase = app(ActualizarBorradorUseCase::class);
    }

    /**
     * TEST 1: Actualización exitosa de datos básicos
     * 
     * Verifica que los datos se actualicen correctamente
     */
    public function test_actualizar_borrador_exitoso()
    {
        // Preparar datos
        $datosFrontend = [
            'cliente' => 'Cliente Actualizado',
            'forma_de_pago' => 'Crédito 30 días',
            'observaciones' => 'Nuevas observaciones',
            'epps' => [],
            'prendas' => [],
        ];

        $request = Request::create('/', 'POST', [
            'pedido' => json_encode($datosFrontend)
        ]);

        $input = new ActualizarBorradorInput(
            pedidoId: $this->pedido->id,
            asesorId: $this->asesor->id,
            request: $request,
            pedidoJSON: json_encode($datosFrontend),
            datosFrontend: $datosFrontend,
        );

        // Ejecutar
        $output = $this->useCase->ejecutar($input);

        // Validaciones
        $this->assertTrue($output->success);
        $this->assertStringContainsString('exitosamente', $output->message);
        $this->assertEquals($this->pedido->id, $output->pedido_id);

        // Verificar en BD
        $pedidoActualizado = PedidoProduccion::find($this->pedido->id);
        $this->assertEquals('Cliente Actualizado', $pedidoActualizado->cliente);
        $this->assertEquals('Crédito 30 días', $pedidoActualizado->forma_de_pago);
        $this->assertEquals('Nuevas observaciones', $pedidoActualizado->observaciones);
    }

    /**
     * TEST 2: Error de seguridad - Asesor no autorizado
     * 
     * Verifica que un asesor diferente NO pueda actualizar
     */
    public function test_actualizar_borrador_asesor_no_autorizado()
    {
        // Crear otro asesor
        $otroAsesor = User::factory()->create(['name' => 'Otro Asesor']);

        // Preparar datos
        $datosFrontend = [
            'cliente' => 'Cliente Nuevo',
            'forma_de_pago' => 'Contado',
            'observaciones' => '',
            'epps' => [],
            'prendas' => [],
        ];

        $request = Request::create('/', 'POST', [
            'pedido' => json_encode($datosFrontend)
        ]);

        $input = new ActualizarBorradorInput(
            pedidoId: $this->pedido->id,
            asesorId: $otroAsesor->id,  // Asesor diferente
            request: $request,
            pedidoJSON: json_encode($datosFrontend),
            datosFrontend: $datosFrontend,
        );

        // Ejecutar
        $output = $this->useCase->ejecutar($input);

        // Validaciones
        $this->assertFalse($output->success);
        $this->assertStringContainsString('no tienes permiso', $output->message);
    }

    /**
     * TEST 3: Validación JSON - Objeto no serializable
     * 
     * Verifica que el UseCase rechace objetos en JSON
     */
    public function test_actualizar_borrador_json_invalido()
    {
        // Preparar datos con estructura inválida
        $datosFrontend = [
            'cliente' => 'Cliente Test',
            'forma_de_pago' => 'Contado',
            'observaciones' => '',
            'epps' => [],
            'prendas' => [
                [
                    'nombre' => 'Prenda Test',
                    'imagenes' => [
                        ['uid' => '123', 'objeto' => (object)['file' => 'test']] // Objeto incrustado
                    ]
                ]
            ]
        ];

        $request = Request::create('/', 'POST', [
            'pedido' => json_encode($datosFrontend)
        ]);

        $input = new ActualizarBorradorInput(
            pedidoId: $this->pedido->id,
            asesorId: $this->asesor->id,
            request: $request,
            pedidoJSON: json_encode($datosFrontend),
            datosFrontend: $datosFrontend,
        );

        // Ejecutar
        $output = $this->useCase->ejecutar($input);

        // Validaciones
        $this->assertFalse($output->success);
        $this->assertStringContainsString('Error', $output->message);
    }

    /**
     * TEST 4: Actualización with EPPs
     * 
     * Verifica que se actualicen cantidad y observaciones de EPPs
     */
    public function test_actualizar_borrador_con_epps()
    {
        // Crear EPP y asignarlo al pedido
        $epp = Epp::create([
            'nombre_completo' => 'EPP Test',
            'activo' => true,
        ]);

        $pedidoEpp = PedidoEpp::create([
            'pedido_produccion_id' => $this->pedido->id,
            'epp_id' => $epp->id,
            'cantidad' => 5,
            'observaciones' => 'Observación antigua',
        ]);

        // Preparar datos
        $datosFrontend = [
            'cliente' => $this->pedido->cliente,
            'forma_de_pago' => $this->pedido->forma_de_pago,
            'observaciones' => $this->pedido->observaciones,
            'epps' => [
                [
                    'epp_id' => $epp->id,
                    'cantidad' => 10,
                    'observaciones' => 'Observación nueva',
                ]
            ],
            'prendas' => [],
        ];

        $request = Request::create('/', 'POST', [
            'pedido' => json_encode($datosFrontend)
        ]);

        $input = new ActualizarBorradorInput(
            pedidoId: $this->pedido->id,
            asesorId: $this->asesor->id,
            request: $request,
            pedidoJSON: json_encode($datosFrontend),
            datosFrontend: $datosFrontend,
        );

        // Ejecutar
        $output = $this->useCase->ejecutar($input);

        // Validaciones
        $this->assertTrue($output->success);

        // Verificar EPP actualizado
        $eppActualizado = PedidoEpp::find($pedidoEpp->id);
        $this->assertEquals(10, $eppActualizado->cantidad);
        $this->assertEquals('Observación nueva', $eppActualizado->observaciones);
    }

    /**
     * TEST 5: Pedido no encontrado
     * 
     * Verifica que se retorne error si el pedido no existe
     */
    public function test_actualizar_borrador_pedido_no_existe()
    {
        // Usar ID inválido
        $datosFrontend = [
            'cliente' => 'Cliente Test',
            'forma_de_pago' => 'Contado',
            'observaciones' => '',
            'epps' => [],
            'prendas' => [],
        ];

        $request = Request::create('/', 'POST', [
            'pedido' => json_encode($datosFrontend)
        ]);

        $input = new ActualizarBorradorInput(
            pedidoId: 99999,  // ID que no existe
            asesorId: $this->asesor->id,
            request: $request,
            pedidoJSON: json_encode($datosFrontend),
            datosFrontend: $datosFrontend,
        );

        // Ejecutar
        $output = $this->useCase->ejecutar($input);

        // Validaciones
        $this->assertFalse($output->success);
        $this->assertStringContainsString('no encontrado', $output->message);
    }

    /**
     * TEST 6: Campos vacíos permitidos
     * 
     * Verifica que campos vacíos/null se actualicen correctamente
     */
    public function test_actualizar_borrador_campos_vacios()
    {
        // Preparar datos con campos vacíos
        $datosFrontend = [
            'cliente' => '',
            'forma_de_pago' => '',
            'observaciones' => '',
            'epps' => [],
            'prendas' => [],
        ];

        $request = Request::create('/', 'POST', [
            'pedido' => json_encode($datosFrontend)
        ]);

        $input = new ActualizarBorradorInput(
            pedidoId: $this->pedido->id,
            asesorId: $this->asesor->id,
            request: $request,
            pedidoJSON: json_encode($datosFrontend),
            datosFrontend: $datosFrontend,
        );

        // Ejecutar
        $output = $this->useCase->ejecutar($input);

        // Validaciones
        $this->assertTrue($output->success);

        // Verificar que se actualizó (incluso si está vacío)
        $pedidoActualizado = PedidoProduccion::find($this->pedido->id);
        $this->assertEquals('', $pedidoActualizado->cliente);
        $this->assertEquals('', $pedidoActualizado->forma_de_pago);
    }
}
