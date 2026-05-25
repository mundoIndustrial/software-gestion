<?php

namespace App\Application\Pedidos\UseCases\Cartera;

use App\Infrastructure\Repositories\CarteraPedidosRepository;
use App\Models\News;
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

            try {
                $descripcionNews = "Cartera rechazo el Pedido #{$pedidoRechazado->numero_pedido}. Motivo: {$motivo}";
                $duplicadaReciente = News::query()
                    ->where('event_type', 'pedido_rechazado_cartera')
                    ->where('pedido', $pedidoRechazado->numero_pedido)
                    ->where('description', $descripcionNews)
                    ->where('created_at', '>=', now()->subSeconds(30))
                    ->exists();

                if ($duplicadaReciente) {
                    Log::info('[CARTERA] News duplicada evitada', [
                        'pedido_id' => $pedidoRechazado->id,
                        'numero_pedido' => $pedidoRechazado->numero_pedido,
                    ]);
                } else {
                News::create([
                    'event_type' => 'pedido_rechazado_cartera',
                    'table_name' => 'pedidos_produccion',
                    'record_id' => $pedidoRechazado->id,
                    'description' => $descripcionNews,
                    'user_id' => $usuarioId,
                    'pedido' => $pedidoRechazado->numero_pedido,
                    'metadata' => [
                        'tipo' => 'pedido_rechazado_cartera',
                        'pedido_id' => $pedidoRechazado->id,
                        'motivo' => $motivo,
                    ],
                ]);
                }
            } catch (\Throwable $e) {
                Log::warning('[CARTERA] No se pudo crear News de rechazo', [
                    'pedido_id' => $pedidoRechazado->id,
                    'error' => $e->getMessage(),
                ]);
            }

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
