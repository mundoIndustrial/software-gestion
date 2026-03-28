<?php

namespace Tests\Unit\Application\SupervisorPedidos\UseCases;

use App\Application\SupervisorPedidos\DTOs\GetPendingEmbroideryStampingReceiptsRequest;
use App\Application\SupervisorPedidos\UseCases\GetPendingEmbroideryStampingReceiptsUseCase;
use App\Domain\SupervisorPedidos\Repositories\ReceiptRepository;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class GetPendingEmbroideryStampingReceiptsUseCaseTest extends TestCase
{
    private ReceiptRepository $receiptRepository;
    private GetPendingEmbroideryStampingReceiptsUseCase $useCase;

    protected function setUp(): void
    {
        $this->receiptRepository = m::mock(ReceiptRepository::class);
        $this->useCase = new GetPendingEmbroideryStampingReceiptsUseCase($this->receiptRepository);
    }

    protected function tearDown(): void
    {
        m::close();
    }

    public function test_calcula_cantidad_total_por_prenda_y_por_parcial(): void
    {
        $request = new GetPendingEmbroideryStampingReceiptsRequest(['BORDADO', 'DTF']);

        $procesos = [
            (object) ['prenda_id' => 10, 'recibo_notas' => null],
            (object) ['prenda_id' => 11, 'recibo_notas' => 'algo parcial_id:55'],
            (object) ['prenda_id' => 10, 'recibo_notas' => 'PARCIAL_ID:55'],
        ];

        $this->receiptRepository
            ->shouldReceive('findPendingEmbroideryStampingReceipts')
            ->once()
            ->with(['BORDADO', 'DTF'])
            ->andReturn($procesos);

        $this->receiptRepository
            ->shouldReceive('sumQuantitiesByPrendaIds')
            ->once()
            ->with([10, 11])
            ->andReturn([10 => 100, 11 => 200]);

        $this->receiptRepository
            ->shouldReceive('sumQuantitiesByPartialIds')
            ->once()
            ->with([55])
            ->andReturn([55 => 7]);

        $response = $this->useCase->execute($request);
        $items = $response->getProcesses();

        $this->assertCount(3, $items);
        $this->assertSame(100, $items[0]->cantidad_total_prendas);
        $this->assertSame(7, $items[1]->cantidad_total_prendas);
        $this->assertSame(7, $items[2]->cantidad_total_prendas);
    }
}

