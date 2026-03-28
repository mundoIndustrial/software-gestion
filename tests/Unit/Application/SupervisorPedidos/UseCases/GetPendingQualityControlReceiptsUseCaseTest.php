<?php

namespace Tests\Unit\Application\SupervisorPedidos\UseCases;

use App\Application\SupervisorPedidos\DTOs\GetPendingSewingReceiptsRequest;
use App\Application\SupervisorPedidos\UseCases\GetPendingQualityControlReceiptsUseCase;
use App\Domain\SupervisorPedidos\Repositories\ReceiptRepository;
use Illuminate\Support\Collection;
use Mockery as m;
use Tests\TestCase;

class GetPendingQualityControlReceiptsUseCaseTest extends TestCase
{
    private ReceiptRepository $receiptRepository;
    private GetPendingQualityControlReceiptsUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->receiptRepository = m::mock(ReceiptRepository::class);
        $this->useCase = new GetPendingQualityControlReceiptsUseCase($this->receiptRepository);
    }

    protected function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }

    public function test_consulta_recibos_control_calidad_y_formatea_prendas(): void
    {
        $request = new GetPendingSewingReceiptsRequest(
            numeroRecibo: '',
            cliente: null,
            asesor: 'Carlos',
            prendas: null,
            fechaCreacion: null
        );

        $this->receiptRepository
            ->shouldReceive('findPendingQualityControlReceipts')
            ->once()
            ->with([
                'numero_recibo' => [],
                'cliente' => [],
                'asesor' => ['Carlos'],
                'prendas' => [],
                'fecha_creacion' => null,
            ])
            ->andReturn([
                (object) [
                    'fecha_creacion' => '2026-03-12 08:00:00',
                    'numero_recibo' => 'QC-100',
                    'cliente' => 'Industria SAS',
                    'area' => 'Control de Calidad',
                    'pedido_id' => 500,
                    'asesor' => 'Carlos',
                    'color_costura' => 'Negro',
                    'prenda_id' => 77,
                ],
            ]);

        $this->receiptRepository
            ->shouldReceive('findGarmentsWithColorsByPrendaId')
            ->once()
            ->with(77)
            ->andReturn([(object) ['nombre_prenda' => 'Chaqueta', 'color_nombre' => 'Negro']]);

        $this->receiptRepository
            ->shouldReceive('findGarmentsWithoutColorsByPrendaId')
            ->once()
            ->with(77)
            ->andReturn([]);

        $response = $this->useCase->execute($request);
        $items = $response->getReceipts();

        $this->assertCount(1, $items);
        $this->assertSame('QC-100', $items[0]['numero_recibo']);
        $this->assertInstanceOf(Collection::class, $items[0]['prendas']);
        $this->assertCount(1, $items[0]['prendas']);
    }
}
