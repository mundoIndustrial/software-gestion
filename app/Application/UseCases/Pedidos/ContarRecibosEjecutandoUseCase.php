<?php

namespace App\Application\UseCases\Pedidos;

use App\Domain\Pedidos\Repositories\RecibosRepository;
use App\Models\PedidoProduccion;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * UseCase: ContarRecibosEjecutandoUseCase
 * 
 * Responsabilidad: Obtener recibos de COSTURA en ejecución (área Corte)
 *              Excluye recibos ya vistos por el usuario autenticado
 * Entrada: User ID (del auth)
 * Salida: Array con total y lista de recibos enriquecidos
 * 
 * Endpoint: GET /api/recibos-costura/ejecutando-corte
 */
class ContarRecibosEjecutandoUseCase
{
    public function __construct(
        private RecibosRepository $recibosRepository,
    ) {}

    /**
     * Ejecutar el caso de uso
     */
    public function execute(int $userId): array
    {
        try {
            Log::info('[ContarRecibosEjecutandoUseCase] Iniciando búsqueda de recibos en ejecución', [
                'user_id' => $userId,
            ]);

            // 1. Obtener recibos COSTURA en ejecución (área Corte) excluyendo vistos
            $recibos = $this->recibosRepository->obtenerRecibosEjecutandoCorte($userId);

            Log::info('[ContarRecibosEjecutandoUseCase] Recibos encontrados', [
                'cantidad' => $recibos->count(),
            ]);

            // 2. Enriquecer con información del pedido
            $recibosConInfo = $recibos->map(function ($recibo) {
                try {
                    $pedido = PedidoProduccion::find($recibo->pedido_produccion_id);
                    
                    return [
                        'id' => $recibo->id,
                        'numero_recibo' => $recibo->numero_recibo,
                        'cliente' => $pedido ? $pedido->cliente : '-',
                        'pedido_id' => $pedido ? $pedido->numero_pedido : '-',
                        'fecha' => Carbon::parse($recibo->created_at)->format('d/m/Y H:i'),
                    ];
                } catch (\Exception $e) {
                    Log::warning('[ContarRecibosEjecutandoUseCase] Error enriqueciendo recibo', [
                        'recibo_id' => $recibo->id,
                        'error' => $e->getMessage(),
                    ]);

                    return [
                        'id' => $recibo->id,
                        'numero_recibo' => $recibo->numero_recibo,
                        'cliente' => '-',
                        'pedido_id' => '-',
                        'fecha' => Carbon::parse($recibo->created_at)->format('d/m/Y H:i'),
                    ];
                }
            });

            // 3. Retornar respuesta
            return [
                'success' => true,
                'total' => $recibosConInfo->count(),
                'recibos' => $recibosConInfo->values()->toArray(),
                'http_code' => 200,
            ];

        } catch (\Exception $e) {
            Log::error('[ContarRecibosEjecutandoUseCase] Error', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Error al contar recibos de costura',
                'total' => 0,
                'recibos' => [],
                'http_code' => 500,
            ];
        }
    }
}
