<?php

namespace Tests\Feature\Services\Pedidos;

use App\Infrastructure\Services\Pedidos\PedidoLifecycleService;
use App\Infrastructure\Services\Pedidos\PedidoSequenceService;
use App\Models\PedidoProduccion;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery as m;
use Tests\TestCase;

class PedidoLifecycleServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }

    public function test_obtener_borrador_por_id_retorna_solo_borradores(): void
    {
        $sequenceService = m::mock(PedidoSequenceService::class);
        $service = new PedidoLifecycleService($sequenceService);
        $asesor = User::factory()->create();

        $borrador = PedidoProduccion::create([
            'asesor_id' => $asesor->id,
            'numero_pedido' => null,
            'cliente' => 'Cliente borrador',
            'estado' => 'Borrador',
            'forma_de_pago' => 'Contado',
            'observaciones' => 'Borrador valido',
        ]);

        $pedidoReal = PedidoProduccion::create([
            'asesor_id' => $asesor->id,
            'numero_pedido' => 1001,
            'cliente' => 'Cliente real',
            'estado' => 'pendiente_cartera',
            'forma_de_pago' => 'Contado',
            'observaciones' => 'Pedido real',
        ]);

        $this->assertSame($borrador->id, $service->obtenerBorradorPorId($borrador->id)?->id);
        $this->assertNull($service->obtenerBorradorPorId($pedidoReal->id));
        $this->assertNull($service->obtenerBorradorPorId(999999));
    }
}
