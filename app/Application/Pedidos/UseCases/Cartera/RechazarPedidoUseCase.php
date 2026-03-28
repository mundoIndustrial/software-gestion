<?php

namespace App\Application\Pedidos\UseCases\Cartera;

use App\Infrastructure\Repositories\CarteraPedidosRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class RechazarPedidoUseCase
{
    private CarteraPedidosRepository $repository;

    public function __construct(CarteraPedidosRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Ejecutar caso de uso
     */
    public function execute(int $pedidoId, string $motivo): array
    {
        try {
            Log::info('[CARTERA] RechazarPedidoUseCase iniciado', [
                'pedido_id' => $pedidoId,
                'usuario_id' => auth()->id()
            ]);

            $pedido = $this->repository->obtenerPedido($pedidoId);

            if (!$pedido) {
                return [
                    'success' => false,
                    'message' => 'Pedido no encontrado'
                ];
            }

            // Construir novedad
            $usuario = auth()->check() ? (auth()->user()->name ?? auth()->user()->email ?? 'Usuario Cartera') : 'Usuario Cartera';
            $fechaHora = Carbon::now()->format('d-m-Y h:i:s A');
            $novedadRechazo = "[{$usuario} - {$fechaHora}] RECHAZADO POR CARTERA: {$motivo}";

            $novedadesActuales = $pedido->novedades ?? '';
            $novedadesNuevas = !empty($novedadesActuales) 
                ? $novedadesActuales . "\n\n" . $novedadRechazo
                : $novedadRechazo;

            $usuarioId = auth()->check() ? auth()->user()->id : null;

            // Rechazar pedido
            $pedidoRechazado = $this->repository->rechazarPedido(
                $pedido,
                $motivo,
                $novedadesNuevas,
                $usuarioId
            );

            Log::info('[CARTERA] Pedido rechazado exitosamente', [
                'pedido_id' => $pedidoRechazado->id,
                'motivo' => $motivo
            ]);

            return [
                'success' => true,
                'message' => 'Pedido rechazado correctamente',
                'numero_pedido' => $pedidoRechazado->numero_pedido
            ];
        } catch (\Exception $e) {
            Log::error('[CARTERA] Error en RechazarPedidoUseCase: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al rechazar: ' . $e->getMessage()
            ];
        }
    }
}

