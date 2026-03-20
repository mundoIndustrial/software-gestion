<?php

namespace App\Application\SupervisorPedidos\UseCases;

use App\Domain\SupervisorPedidos\Repositories\OrderRepository;
use App\Domain\SupervisorPedidos\Repositories\ReceiptRepository;
use App\Domain\SupervisorPedidos\ValueObjects\OrderId;
use App\Domain\SupervisorPedidos\ValueObjects\PrendaId;
use App\Domain\SupervisorPedidos\ValueObjects\ReceiptType;
use App\Application\SupervisorPedidos\DTOs\ActivateReceiptRequest;
use App\Application\SupervisorPedidos\DTOs\ActivateReceiptResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ActivateSewingReceiptUseCase
{
    private OrderRepository $orderRepository;
    private ReceiptRepository $receiptRepository;

    public function __construct(
        OrderRepository $orderRepository,
        ReceiptRepository $receiptRepository
    ) {
        $this->orderRepository = $orderRepository;
        $this->receiptRepository = $receiptRepository;
    }

    public function execute(ActivateReceiptRequest $request): ActivateReceiptResponse
    {
        try {
            $orderId = new OrderId($request->getOrderId());
            $prendaId = new PrendaId($request->getPrendaId());
            $receiptType = new ReceiptType('COSTURA');

            $order = $this->orderRepository->findById($orderId);
            if (!$order) {
                throw new \RuntimeException("Pedido #{$request->getOrderId()} no encontrado");
            }

            if (!$order->isApproved()) {
                throw new \DomainException('Solo se pueden activar recibos en órdenes aprobadas');
            }

            $existingReceipt = $this->receiptRepository->findByOrderAndPrenda(
                $orderId,
                $prendaId,
                $receiptType
            );

            if ($existingReceipt && $existingReceipt->isActive()) {
                return new ActivateReceiptResponse(
                    true,
                    'Recibo de costura ya está activo',
                    $existingReceipt->getReceiptNumber(),
                    $existingReceipt->getId()
                );
            }

            // Generar consecutivo
            $newReceiptNumber = $this->generateReceiptNumber();
            
            Log::info("Recibo de COSTURA activado", [
                'order_id' => $orderId->value(),
                'prenda_id' => $prendaId->value(),
                'receipt_number' => $newReceiptNumber,
            ]);

            return new ActivateReceiptResponse(
                true,
                'Recibo COSTURA activado correctamente',
                $newReceiptNumber
            );

        } catch (\Exception $e) {
            Log::error('Error in ActivateSewingReceipt: ' . $e->getMessage());
            throw $e;
        }
    }

    private function generateReceiptNumber(): string
    {
        $master = DB::table('consecutivos_recibos')
            ->where('tipo_recibo', 'COSTURA')
            ->where('activo', 1)
            ->lockForUpdate()
            ->first();

        if (!$master) {
            throw new \RuntimeException('No existe consecutivo maestro para COSTURA');
        }

        $newConsecutive = (int) $master->consecutivo_actual + 1;

        DB::table('consecutivos_recibos')
            ->where('id', $master->id)
            ->update([
                'consecutivo_actual' => $newConsecutive,
                'updated_at' => now(),
            ]);

        return (string) $newConsecutive;
    }
}
