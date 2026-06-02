<?php

namespace Tests\Unit\Application\SupervisorPedidos\UseCases;

use App\Application\SupervisorPedidos\UseCases\ActivateSewingReceiptUseCase;
use App\Domain\SupervisorPedidos\Entities\Order;
use App\Domain\SupervisorPedidos\Repositories\OrderRepository;
use App\Domain\SupervisorPedidos\Repositories\ReceiptRepository;
use Mockery as m;
use Tests\TestCase;

class ActivateSewingReceiptUseCaseTest extends TestCase
{
    private OrderRepository $orderRepository;
    private ReceiptRepository $receiptRepository;
    private ActivateSewingReceiptUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orderRepository = m::mock(OrderRepository::class);
        $this->receiptRepository = m::mock(ReceiptRepository::class);
        $this->useCase = new ActivateSewingReceiptUseCase($this->orderRepository, $this->receiptRepository);
    }

    protected function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }

    public function test_crea_y_retorna_el_recibo_de_costura_al_activarlo(): void
    {
        $order = m::mock(Order::class);
        $order->shouldReceive('isApproved')
            ->once()
            ->andReturn(true);

        $this->orderRepository
            ->shouldReceive('findById')
            ->once()
            ->andReturn($order);

        $this->receiptRepository
            ->shouldReceive('findByOrderAndPrenda')
            ->once()
            ->andReturn(null);

        $this->receiptRepository
            ->shouldReceive('activateSewingReceipt')
            ->once()
            ->with(1645, 2035)
            ->andReturn([
                'id' => 987,
                'consecutivo_actual' => '533',
                'activo' => 1,
            ]);

        $response = $this->useCase->execute(
            new \App\Application\SupervisorPedidos\DTOs\ActivateReceiptRequest(1645, 2035)
        );

        $this->assertSame([
            'success' => true,
            'message' => 'Recibo COSTURA activado correctamente',
            'data' => [
                'consecutivo' => '533',
                'id' => 987,
            ],
        ], $response->toArray());
    }

    public function test_devuelve_el_recibo_existente_si_ya_esta_activo(): void
    {
        $order = m::mock(Order::class);
        $order->shouldReceive('isApproved')
            ->once()
            ->andReturn(true);

        $existingReceipt = new class {
            public function isActive(): bool
            {
                return true;
            }

            public function getReceiptNumber(): string
            {
                return '533';
            }

            public function getId(): int
            {
                return 987;
            }
        };

        $this->orderRepository
            ->shouldReceive('findById')
            ->once()
            ->andReturn($order);

        $this->receiptRepository
            ->shouldReceive('findByOrderAndPrenda')
            ->once()
            ->andReturn($existingReceipt);

        $this->receiptRepository
            ->shouldNotReceive('activateSewingReceipt');

        $response = $this->useCase->execute(
            new \App\Application\SupervisorPedidos\DTOs\ActivateReceiptRequest(1645, 2035)
        );

        $this->assertSame([
            'success' => true,
            'message' => 'Recibo de costura ya está activo',
            'data' => [
                'consecutivo' => '533',
                'id' => 987,
            ],
        ], $response->toArray());
    }
}
