<?php

namespace Tests\Unit\Application\SupervisorPedidos\UseCases;

use App\Application\SupervisorPedidos\DTOs\GetPendingSewingReceiptsRequest;
use App\Application\SupervisorPedidos\UseCases\GetPendingSewingReceiptsUseCase;
use App\Domain\SupervisorPedidos\Repositories\ReceiptRepository;
use Illuminate\Support\Collection;
use Mockery as m;
use Tests\TestCase;

class GetPendingSewingReceiptsUseCaseTest extends TestCase
{
    private ReceiptRepository $receiptRepository;
    private GetPendingSewingReceiptsUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->receiptRepository = m::mock(ReceiptRepository::class);
        $this->useCase = new GetPendingSewingReceiptsUseCase($this->receiptRepository);
    }

    protected function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }

    public function test_aplica_filtros_y_formatea_prendas_desde_repositorio(): void
    {
        $request = new GetPendingSewingReceiptsRequest(
            numeroRecibo: 'R-1, R-2',
            cliente: 'ACME',
            asesor: 'Laura',
            prendas: 'Polo, Camisa',
            fechaCreacion: ' 2026-03-10 '
        );

        $this->receiptRepository
            ->shouldReceive('findPendingSewingReceipts')
            ->once()
            ->with([
                'numero_recibo' => ['R-1', 'R-2'],
                'cliente' => ['ACME'],
                'asesor' => ['Laura'],
                'prendas' => ['Polo', 'Camisa'],
                'fecha_creacion' => '2026-03-10',
            ])
            ->andReturn([
                (object) [
                    'fecha_creacion' => '2026-03-10 12:00:00',
                    'numero_recibo' => 'R-1',
                    'cliente' => 'ACME',
                    'area' => 'COSTURA',
                    'pedido_id' => 123,
                    'asesor' => 'Laura',
                    'color_costura' => 'Rojo',
                    'prenda_id' => 9,
                ],
                (object) [
                    'fecha_creacion' => '2026-03-11 12:00:00',
                    'numero_recibo' => 'R-2',
                    'cliente' => 'ACME',
                    'area' => 'COSTURA',
                    'pedido_id' => 124,
                    'asesor' => 'Laura',
                    'color_costura' => null,
                    'prenda_id' => null,
                ],
            ]);

        $this->receiptRepository
            ->shouldReceive('findGarmentsWithColorsByPrendaId')
            ->once()
            ->with(9)
            ->andReturn([(object) ['nombre_prenda' => 'Polo', 'color_nombre' => 'Azul']]);

        $this->receiptRepository
            ->shouldReceive('findGarmentsWithoutColorsByPrendaId')
            ->once()
            ->with(9)
            ->andReturn([(object) ['nombre_prenda' => 'Polo', 'tela' => 'Algodon']]);

        $response = $this->useCase->execute($request);
        $items = $response->getReceipts();

        $this->assertCount(2, $items);
        $this->assertInstanceOf(Collection::class, $items[0]['prendas']);
        $this->assertCount(2, $items[0]['prendas']);
        $this->assertInstanceOf(Collection::class, $items[1]['prendas']);
        $this->assertCount(0, $items[1]['prendas']);
    }
}
