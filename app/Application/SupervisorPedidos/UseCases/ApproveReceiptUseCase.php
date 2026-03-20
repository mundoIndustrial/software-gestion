<?php

namespace App\Application\SupervisorPedidos\UseCases;

use App\Application\SupervisorPedidos\DTOs\ApproveReceiptRequest;
use App\Application\SupervisorPedidos\DTOs\ApproveReceiptResponse;
use App\Domain\SupervisorPedidos\Repositories\ReceiptRepository;

class ApproveReceiptUseCase
{
    public function __construct(
        private ReceiptRepository $receiptRepository
    ) {}

    /**
     * Aprobar un recibo
     */
    public function execute(ApproveReceiptRequest $request): ApproveReceiptResponse
    {
        try {
            // Usar el repositorio para aprobar el recibo
            $resultado = $this->receiptRepository->approve($request->getReceiptId());

            if (!$resultado) {
                throw new \DomainException('Recibo no encontrado');
            }

            \Log::info('Recibo aprobado', [
                'recibo_id' => $resultado['id'],
                'tipo_recibo' => $resultado['tipo_recibo'] ?? 'N/A',
                'usuario' => auth()->user()?->name ?? 'N/A',
                'fecha_aprobacion' => now(),
                'procesos_actualizados' => $resultado['procesos_actualizados'] ?? 0
            ]);

            return new ApproveReceiptResponse(
                success: true,
                message: 'Recibo aprobado correctamente',
                receiptId: $resultado['id'],
                processesUpdated: $resultado['procesos_actualizados'] ?? 0
            );

        } catch (\Exception $e) {
            throw new \DomainException('Error al aprobar recibo: ' . $e->getMessage());
        }
    }
}
