<?php

namespace App\Application\UseCases\RegistroOrden;

use App\Infrastructure\Repositories\PedidoProduccionRepository;
use App\Exceptions\GetDescripcionPrendasException;

/**
 * GetDescripcionPrendasUseCase
 * 
 * Obtener descripción de prendas de un pedido
 * Cumple DDD: Application Layer - UseCase
 * 
 * Nota: Las excepciones son manejadas por el Handler que renderiza
 * respuestas JSON automáticamente. El UseCase solo lanza excepciones.
 */
class GetDescripcionPrendasUseCase
{
    private PedidoProduccionRepository $pedidoRepository;

    public function __construct(PedidoProduccionRepository $pedidoRepository)
    {
        $this->pedidoRepository = $pedidoRepository;
    }

    /**
     * Ejecutar use case
     * GET /registros/{pedido}/descripcion-prendas
     * 
     * @param string $pedido ID o número de pedido
     * @return array ['descripcion_prendas' => string, 'numero_pedido' => string, 'orden_id' => int]
     * @throws GetDescripcionPrendasException
     */
    public function execute(string $pedido): array
    {
        // Validar entrada
        if (empty($pedido)) {
            throw GetDescripcionPrendasException::pedidoInvalido();
        }

        try {
            $pedidoModel = $this->pedidoRepository->obtenerPorIdONumero($pedido);

            if (!$pedidoModel) {
                throw GetDescripcionPrendasException::ordenNoEncontrada($pedido);
            }

            $descripcionPrendas = $pedidoModel->descripcion_prendas ?? '';

            \Log::info('[GetDescripcionPrendasUseCase] Descripción obtenida', [
                'pedido' => $pedido,
                'numero_pedido' => $pedidoModel->numero_pedido,
                'orden_id' => $pedidoModel->id,
                'longitud' => strlen($descripcionPrendas)
            ]);

            return [
                'descripcion_prendas' => $descripcionPrendas,
                'numero_pedido' => $pedidoModel->numero_pedido,
                'orden_id' => $pedidoModel->id
            ];
        } catch (\Exception $e) {
            // Si es excepción personalizada, re-lanzar directamente
            if ($e instanceof GetDescripcionPrendasException) {
                throw $e;
            }

            \Log::error('[GetDescripcionPrendasUseCase] Error: ' . $e->getMessage(), [
                'pedido' => $pedido,
                'trace' => $e->getTraceAsString()
            ]);

            throw GetDescripcionPrendasException::errorConsulta($e);
        }
    }
}
