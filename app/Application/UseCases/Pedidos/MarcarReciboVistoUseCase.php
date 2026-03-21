<?php

namespace App\Application\UseCases\Pedidos;

use App\Domain\Pedidos\Repositories\RecibosRepository;
use Illuminate\Support\Facades\Log;

/**
 * UseCase: MarcarReciboVistoUseCase
 * 
 * Responsabilidad: Marcar un recibo de COSTURA como visto por el usuario autenticado
 * Entrada: User ID + Recibo ID
 * Salida: Success response json
 * 
 * Endpoint: POST /api/recibos-costura/{reciboId}/marcar-visto
 */
class MarcarReciboVistoUseCase
{
    public function __construct(
        private RecibosRepository $recibosRepository,
    ) {}

    /**
     * Ejecutar el caso de uso
     */
    public function execute(int $reciboId, int $userId): array
    {
        try {
            Log::info('[MarcarReciboVistoUseCase] Marcando recibo como visto', [
                'recibo_id' => $reciboId,
                'user_id' => $userId,
            ]);

            // 1. Validar que el recibo existe
            $recibo = $this->recibosRepository->obtenerReciboCostura($reciboId);
            
            if (!$recibo) {
                Log::warning('[MarcarReciboVistoUseCase] Recibo no encontrado', [
                    'recibo_id' => $reciboId,
                ]);

                return [
                    'success' => false,
                    'message' => 'Recibo no encontrado',
                    'http_code' => 404,
                ];
            }

            // 2. Marcar como visto via repository
            $this->recibosRepository->marcarReciboVisto($reciboId, $userId, 'COSTURA');

            Log::info('[MarcarReciboVistoUseCase] Recibo marcado como visto', [
                'recibo_id' => $reciboId,
                'user_id' => $userId,
                'numero_recibo' => $recibo->consecutivo_actual,
            ]);

            // 3. Retornar respuesta
            return [
                'success' => true,
                'message' => 'Recibo marcado como visto',
                'recibo_id' => $reciboId,
                'http_code' => 200,
            ];

        } catch (\Exception $e) {
            Log::error('[MarcarReciboVistoUseCase] Error', [
                'recibo_id' => $reciboId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Error al marcar recibo',
                'http_code' => 500,
            ];
        }
    }
}
