<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Application\Pedidos\DTOs\ActualizarPrendaCompletaDTO;
use App\Application\Pedidos\UseCases\ActualizarPrendaCompletaUseCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Test: Verificar que la novedad se guarda correctamente
 * 
 * Objetivo: Asegurar que cuando se actualiza una prenda con novedad,
 * esta se guarda en pedidos_produccion.novedades
 */
class ActualizarPrendaNovedadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Test: Novedad se guarda en pedido_produccion
     * 
     * @test
     */
    public function test_novedad_se_guarda_en_pedido_produccion()
    {
        // 1. ARRANCAR: Crear un pedido y una prenda
        $pedido = PedidoProduccion::factory()->create([
            'numero_pedido' => 100043,
            'novedades' => 'Novedades iniciales',
        ]);

        $prenda = PrendaPedido::factory()->create([
            'pedido_produccion_id' => $pedido->id,
            'nombre_prenda' => 'Prenda Test',
        ]);

        // 2. ACT: Preparar DTO con novedad
        $novedad = '[TEST USER | ASESOR] 27/01/2026 11:00:27 - Se cambió el color a rojo';
        $dto = new ActualizarPrendaCompletaDTO(
            prendaId: $prenda->id,
            nombrePrenda: 'Prenda Test Actualizada',
            novedad: $novedad
        );

        // 3. ACT: Ejecutar Use Case
        $useCase = app(ActualizarPrendaCompletaUseCase::class);
        $prendaActualizada = $useCase->ejecutar($dto);

        // 4. ASSERT: Verificar que la novedad fue guardada
        $pedidoActualizado = PedidoProduccion::find($pedido->id);
        
        $this->assertNotNull($pedidoActualizado);
        $this->assertStringContainsString('[TEST USER | ASESOR]', $pedidoActualizado->novedades);
        $this->assertStringContainsString('Se cambió el color a rojo', $pedidoActualizado->novedades);
        $this->assertStringContainsString('Novedades iniciales', $pedidoActualizado->novedades);
        
        // Verificar que se mantiene el historial (dos saltos de línea entre novedades)
        $this->assertStringContainsString("Novedades iniciales\n\n[TEST USER | ASESOR]", $pedidoActualizado->novedades);
    }

    /**
     * Test: Sin novedad, no se modifica nada
     * 
     * @test
     */
    public function test_sin_novedad_no_modifica_pedido()
    {
        // 1. ARRANCAR
        $pedido = PedidoProduccion::factory()->create([
            'numero_pedido' => 100044,
            'novedades' => 'Novedades iniciales',
        ]);

        $prenda = PrendaPedido::factory()->create([
            'pedido_produccion_id' => $pedido->id,
        ]);

        // 2. ACT: DTO sin novedad
        $dto = new ActualizarPrendaCompletaDTO(
            prendaId: $prenda->id,
            nombrePrenda: 'Actualizado',
            novedad: null
        );

        $useCase = app(ActualizarPrendaCompletaUseCase::class);
        $useCase->ejecutar($dto);

        // 3. ASSERT: Las novedades no cambian
        $pedidoActualizado = PedidoProduccion::find($pedido->id);
        $this->assertEquals('Novedades iniciales', $pedidoActualizado->novedades);
    }

    /**
     * Test: Múltiples novedades se concatenan
     * 
     * @test
     */
    public function test_multiples_novedades_se_concatenan()
    {
        // 1. ARRANCAR
        $pedido = PedidoProduccion::factory()->create([
            'numero_pedido' => 100045,
            'novedades' => null,
        ]);

        $prenda = PrendaPedido::factory()->create([
            'pedido_produccion_id' => $pedido->id,
        ]);

        // 2. ACT: Primera novedad
        $dto1 = new ActualizarPrendaCompletaDTO(
            prendaId: $prenda->id,
            nombrePrenda: 'Prenda',
            novedad: 'Primera novedad'
        );
        app(ActualizarPrendaCompletaUseCase::class)->ejecutar($dto1);

        // 3. ACT: Segunda novedad
        $dto2 = new ActualizarPrendaCompletaDTO(
            prendaId: $prenda->id,
            nombrePrenda: 'Prenda',
            novedad: 'Segunda novedad'
        );
        app(ActualizarPrendaCompletaUseCase::class)->ejecutar($dto2);

        // 4. ASSERT: Ambas novedades están presentes
        $pedidoActualizado = PedidoProduccion::find($pedido->id);
        $this->assertStringContainsString('Primera novedad', $pedidoActualizado->novedades);
        $this->assertStringContainsString('Segunda novedad', $pedidoActualizado->novedades);
        $this->assertStringContainsString("Primera novedad\n\nSegunda novedad", $pedidoActualizado->novedades);
    }
}
