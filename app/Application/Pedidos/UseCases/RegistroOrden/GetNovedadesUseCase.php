<?php

namespace App\Application\Pedidos\UseCases\RegistroOrden;

use App\Models\PedidoProduccion;
use App\Exceptions\GetNovedadesException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * GetNovedadesUseCase
 * 
 * UseCase: Obtener novedades de un pedido
 * Capa: Application
 * Responsabilidad: Recuperar novedades/notas de un pedido específico
 * 
 * Nota: Las excepciones son manejadas por el Handler que renderiza
 * respuestas JSON automáticamente. El UseCase solo lanza excepciones.
 */
class GetNovedadesUseCase
{
    /**
     * Ejecutar obtención de novedades
     * 
     * @param string $numeroPedido
     * @return array ['numero_pedido' => string, 'novedades' => string, 'tiene_novedades' => bool]
     * @throws GetNovedadesException
     */
    public function execute(string $numeroPedido): array
    {
        // Validar entrada
        if (empty($numeroPedido)) {
            throw GetNovedadesException::numeroPedidoInvalido();
        }

        try {
            // Buscar por numero_pedido (no por id)
            $pedido = PedidoProduccion::where('numero_pedido', $numeroPedido)
                ->firstOrFail();

            \Log::info('[GetNovedadesUseCase] Novedades obtenidas', [
                'numero_pedido' => $numeroPedido,
                'pedido_id' => $pedido->id,
                'novedades_length' => strlen($pedido->novedades ?? '')
            ]);

            return [
                'numero_pedido' => $numeroPedido,
                'novedades' => $pedido->novedades ?? '',
                'tiene_novedades' => !empty($pedido->novedades)
            ];
        } catch (\Exception $e) {
            // Diferenciar tipo de excepción y convertir a personalizada
            if ($e instanceof ModelNotFoundException) {
                throw GetNovedadesException::pedidoNoEncontrado($numeroPedido);
            }

            \Log::error('[GetNovedadesUseCase] Error: ' . $e->getMessage(), [
                'numero_pedido' => $numeroPedido,
                'trace' => $e->getTraceAsString()
            ]);
            
            throw GetNovedadesException::errorConsulta($e);
        }
    }
}

