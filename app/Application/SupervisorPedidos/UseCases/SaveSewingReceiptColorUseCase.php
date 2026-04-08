<?php

namespace App\Application\SupervisorPedidos\UseCases;

use App\Application\SupervisorPedidos\DTOs\SaveSewingReceiptColorRequest;
use App\Application\SupervisorPedidos\DTOs\SaveSewingReceiptColorResponse;
use App\Domain\SupervisorPedidos\Repositories\ReceiptRepository;

class SaveSewingReceiptColorUseCase
{
    public function __construct(
        private ReceiptRepository $receiptRepository
    ) {}

    public function execute(SaveSewingReceiptColorRequest $request): SaveSewingReceiptColorResponse
    {
        try {
            // Guardar el color en la BD mediante el repositorio
            $updated = $this->receiptRepository->updateSewingReceiptColor(
                $request->getReceiptNumber(),
                $request->getColor()
            );

            if ($updated > 0) {
                return new SaveSewingReceiptColorResponse(
                    success: true,
                    message: 'Color guardado correctamente',
                    receiptNumber: $request->getReceiptNumber()
                );
            }

            throw new \DomainException('No se encontró el recibo con el número especificado');

        } catch (\Throwable $e) {
            throw new \DomainException('Error al guardar el color de costura: ' . $e->getMessage());
        }
    }
}
