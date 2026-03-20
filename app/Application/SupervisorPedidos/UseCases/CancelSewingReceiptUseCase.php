<?php

namespace App\Application\SupervisorPedidos\UseCases;

use App\Application\SupervisorPedidos\DTOs\CancelReceiptRequest;
use App\Application\SupervisorPedidos\DTOs\CancelReceiptResponse;
use App\Domain\SupervisorPedidos\Repositories\ReceiptRepository;
use App\Domain\SupervisorPedidos\Exceptions\ReceiptNotFoundException;
use App\Domain\SupervisorPedidos\Exceptions\InvalidOperationException;

class CancelSewingReceiptUseCase
{
    public function __construct(
        private ReceiptRepository $receiptRepository
    ) {}

    /**
     * Ejecutar anulación de recibo de costura
     */
    public function execute(CancelReceiptRequest $request): CancelReceiptResponse
    {
        try {
            // Buscar el recibo activo
            $recibo = $this->receiptRepository->findActiveBySewingType(
                $request->getPedidoId(),
                $request->getPrendaId()
            );

            if (!$recibo) {
                throw new ReceiptNotFoundException(
                    'No se encontró un recibo de costura activo para esta prenda'
                );
            }

            // Anular el recibo
            $resultado = $this->receiptRepository->cancel(
                $recibo['id'],
                $request->getNotes()
            );

            return new CancelReceiptResponse(
                success: true,
                message: 'Recibo COSTURA anulado correctamente',
                receiptId: $recibo['id'],
                consecutive: $recibo['consecutivo_actual'],
                data: $resultado
            );
        } catch (ReceiptNotFoundException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new InvalidOperationException(
                'Error al anular recibo de costura: ' . $e->getMessage()
            );
        }
    }
}
