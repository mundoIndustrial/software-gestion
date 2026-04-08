<?php

namespace App\Application\SupervisorPedidos\UseCases;

use App\Application\SupervisorPedidos\DTOs\GetReceiptDetailsResponse;
use App\Domain\SupervisorPedidos\Repositories\ReceiptRepository;

class GetReceiptDetailsUseCase
{
    public function __construct(
        private ReceiptRepository $receiptRepository
    ) {}

    /**
     * Obtener detalles de un recibo específico
     */
    public function execute(int $receiptId): GetReceiptDetailsResponse
    {
        try {
            // Usar el repositorio para obtener todos los detalles
            $recibo = $this->receiptRepository->findByIdWithDetails($receiptId);

            if (!$recibo) {
                throw new \DomainException('Recibo no encontrado');
            }

            $detalles = [
                'id' => $recibo['id'],
                'nombre_prenda' => $recibo['nombre_prenda'] ?? 'N/A',
                'tipo_recibo' => $recibo['tipo_recibo'],
                'estado' => $recibo['estado'] ?? 'PENDIENTE',
                'observaciones' => $recibo['observaciones'],
                'numero_recibo' => $recibo['consecutivo_actual'],
                'cliente' => $recibo['cliente'],
                'asesor' => $recibo['asesor'],
                'fecha_creacion' => $recibo['fecha_creacion'],
                'tallas' => $recibo['tallas'] ?? [],
                'imagenes' => $recibo['imagenes'] ?? []
            ];

            return new GetReceiptDetailsResponse(
                success: true,
                message: 'Detalles del recibo obtenidos',
                details: $detalles
            );

        } catch (\Exception $e) {
            throw new \DomainException('Error al obtener detalles del recibo: ' . $e->getMessage());
        }
    }
}
