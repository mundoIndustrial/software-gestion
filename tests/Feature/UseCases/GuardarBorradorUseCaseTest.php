<?php

namespace Tests\Feature\UseCases;

use Tests\TestCase;
use App\Models\User;
use App\Models\Cliente;
use App\Models\Epp;
use App\Models\EppCategoria;
use App\Application\Pedidos\UseCases\GuardarBorradorUseCase;
use App\Application\Pedidos\UseCases\GuardarBorradorInput;
use Illuminate\Http\Request;
use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * GuardarBorradorUseCaseTest
 * 
 * Suite de tests para GuardarBorradorUseCase
 * 
 * Valida:
 * - Creación de cliente automática
 * - Creación de pedido borrador
 * - Normalización de datos
 * - Validación transaccional
 * 
 * @package Tests\Feature\UseCases
 */
class GuardarBorradorUseCaseTest extends TestCase
{
    use DatabaseTransactions;

    private GuardarBorradorUseCase $useCase;
    private User $asesor;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear asesor
        $this->asesor = User::factory()->create(['name' => 'Asesor Test']);

        // Obtener servicio
        $this->useCase = app(GuardarBorradorUseCase::class);
    }

    /**
     * TEST 1: Guardar borrador exitoso
     * 
     * Verifica que el borrador se guarde correctamente
     */
    public function test_guardar_borrador_exitoso()
    {
        // Preparar datos
        $datosFrontend = [
            'cliente' => 'Nuevo Cliente',
            'forma_de_pago' => 'Contado',
            'observaciones' => 'Test observación',
            'epps' => [],
            'prendas' => [],
        ];

        $request = Request::create('/', 'POST', [
            'pedido' => json_encode($datosFrontend)
        ]);

        $input = GuardarBorradorInput::fromRequest($request, $this->asesor->id);

        // Ejecutar
        $output = $this->useCase->ejecutar($input);

        // Validaciones
        $this->assertTrue($output->success);
        $this->assertStringContainsString('exitosamente', $output->message);
        $this->assertNotNull($output->pedido_id);

        // Verificar que se creó el cliente
        $cliente = Cliente::where('nombre', 'Nuevo Cliente')->first();
        $this->assertNotNull($cliente);
    }

    /**
     * TEST 2: Cliente existente se reutiliza
     * 
     * Verifica que se reutilice un cliente existente
     */
    public function test_guardar_borrador_cliente_existente()
    {
        // Crear cliente previamente
        $clienteExistente = Cliente::create(['nombre' => 'Cliente Existente']);

        // Preparar datos
        $datosFrontend = [
            'cliente' => 'Cliente Existente',
            'forma_de_pago' => 'Contado',
            'observaciones' => '',
            'epps' => [],
            'prendas' => [],
        ];

        $request = Request::create('/', 'POST', [
            'pedido' => json_encode($datosFrontend)
        ]);

        $input = GuardarBorradorInput::fromRequest($request, $this->asesor->id);

        // Ejecutar
        $output = $this->useCase->ejecutar($input);

        // Validaciones
        $this->assertTrue($output->success);

        // Verificar que use el cliente existente
        $clientesConNombre = Cliente::where('nombre', 'Cliente Existente')->get();
        $this->assertEquals(1, $clientesConNombre->count());
    }

    /**
     * TEST 3: JSON inválido es rechazado
     * 
     * Verifica que se rechace JSON malformado
     */
    public function test_guardar_borrador_json_invalido()
    {
        // Crear request con JSON inválido
        $request = Request::create('/', 'POST', [
            'pedido' => 'NO ES JSON VÁLIDO'
        ]);

        // Intentar crear input
        $this->expectException(\Exception::class);
        GuardarBorradorInput::fromRequest($request, $this->asesor->id);
    }

    /**
     * TEST 4: Creación con múltiples EPPs
     * 
     * Verifica que se creen múltiples EPPs correctamente
     */
    public function test_guardar_borrador_con_epps()
    {
        $categoria = EppCategoria::create([
            'codigo' => 'CAT_TEST',
            'nombre' => 'Categoria Test',
            'descripcion' => 'Categoria para pruebas',
            'activo' => true,
        ]);

        $epp1 = Epp::create([
            'nombre_completo' => 'EPP 1',
            'activo' => true,
            'categoria_id' => $categoria->id,
        ]);

        $epp2 = Epp::create([
            'nombre_completo' => 'EPP 2',
            'activo' => true,
            'categoria_id' => $categoria->id,
        ]);

        // Preparar datos con EPPs
        $datosFrontend = [
            'cliente' => 'Cliente con EPPs',
            'forma_de_pago' => 'Crédito 30 días',
            'observaciones' => 'Test EPPs',
            'epps' => [
                ['epp_id' => $epp1->id, 'nombre' => 'EPP 1', 'cantidad' => 5],
                ['epp_id' => $epp2->id, 'nombre' => 'EPP 2', 'cantidad' => 10],
            ],
            'prendas' => [],
        ];

        $request = Request::create('/', 'POST', [
            'pedido' => json_encode($datosFrontend)
        ]);

        $input = GuardarBorradorInput::fromRequest($request, $this->asesor->id);

        // Ejecutar
        $output = $this->useCase->ejecutar($input);

        // Validaciones
        $this->assertTrue($output->success);
        $this->assertNotNull($output->pedido_id);
    }

    /**
     * TEST 5: Borrador se crea con estado correcto
     * 
     * Verifica que el estado sea 'Borrador'
     */
    public function test_guardar_borrador_estado_correcto()
    {
        // Preparar datos
        $datosFrontend = [
            'cliente' => 'Cliente Estado Test',
            'forma_de_pago' => 'Contado',
            'observaciones' => '',
            'epps' => [],
            'prendas' => [],
        ];

        $request = Request::create('/', 'POST', [
            'pedido' => json_encode($datosFrontend)
        ]);

        $input = GuardarBorradorInput::fromRequest($request, $this->asesor->id);

        // Ejecutar
        $output = $this->useCase->ejecutar($input);

        // Validaciones
        $this->assertTrue($output->success);

        // Verificar estado
        $pedido = \App\Models\PedidoProduccion::find($output->pedido_id);
        $this->assertEquals('Borrador', $pedido->estado);
    }

    /**
     * TEST 6: Asesor se asigna correctamente
     * 
     * Verifica que el asesor sea el autenticado
     */
    public function test_guardar_borrador_asesor_correcto()
    {
        // Preparar datos
        $datosFrontend = [
            'cliente' => 'Cliente Asesor Test',
            'forma_de_pago' => 'Contado',
            'observaciones' => '',
            'epps' => [],
            'prendas' => [],
        ];

        $request = Request::create('/', 'POST', [
            'pedido' => json_encode($datosFrontend)
        ]);

        $input = GuardarBorradorInput::fromRequest($request, $this->asesor->id);

        // Ejecutar
        $output = $this->useCase->ejecutar($input);

        // Validaciones
        $this->assertTrue($output->success);

        // Verificar asesor
        $pedido = \App\Models\PedidoProduccion::find($output->pedido_id);
        $this->assertEquals($this->asesor->id, $pedido->asesor_id);
    }

    /**
     * TEST 7: Sin número de pedido por ser borrador
     * 
     * Verifica que el número de pedido esté NULL
     */
    public function test_guardar_borrador_sin_numero()
    {
        // Preparar datos
        $datosFrontend = [
            'cliente' => 'Cliente Sin Número',
            'forma_de_pago' => 'Contado',
            'observaciones' => '',
            'epps' => [],
            'prendas' => [],
        ];

        $request = Request::create('/', 'POST', [
            'pedido' => json_encode($datosFrontend)
        ]);

        $input = GuardarBorradorInput::fromRequest($request, $this->asesor->id);

        // Ejecutar
        $output = $this->useCase->ejecutar($input);

        // Validaciones
        $this->assertTrue($output->success);
        $this->assertNull($output->numero_pedido);

        // Verificar que número_pedido sea NULL en BD
        $pedido = \App\Models\PedidoProduccion::find($output->pedido_id);
        $this->assertNull($pedido->numero_pedido);
    }
}

