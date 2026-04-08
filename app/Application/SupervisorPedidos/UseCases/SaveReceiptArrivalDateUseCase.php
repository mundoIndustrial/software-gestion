<?php

namespace App\Application\SupervisorPedidos\UseCases;

use App\Application\SupervisorPedidos\DTOs\SaveReceiptArrivalDateRequest;
use App\Application\SupervisorPedidos\DTOs\SaveReceiptArrivalDateResponse;
use App\Domain\SupervisorPedidos\Repositories\ReceiptRepository;

class SaveReceiptArrivalDateUseCase
{
    public function __construct(
        private ReceiptRepository $receiptRepository
    ) {}

    /**
     * Guardar fecha de llegada de un recibo
     */
    public function execute(SaveReceiptArrivalDateRequest $request): SaveReceiptArrivalDateResponse
    {
        try {
            // Validar que el recibo exista
            $recibo = $this->receiptRepository->findById($request->getReceiptId());
            
            if (!$recibo) {
                throw new \DomainException('Recibo no encontrado');
            }

            // Guardar la fecha de llegada
            $resultado = $this->receiptRepository->saveArrivalDate(
                $request->getReceiptId(),
                $request->getArrivalDate()
            );

            return new SaveReceiptArrivalDateResponse(
                success: true,
                message: 'Fecha de llegada actualizada',
                arrivalDate: $resultado['fecha_llegada'] ?? null,
                data: $resultado
            );
        } catch (\Exception $e) {
            throw new \DomainException('Error al guardar fecha de llegada: ' . $e->getMessage());
        }
    }
}
