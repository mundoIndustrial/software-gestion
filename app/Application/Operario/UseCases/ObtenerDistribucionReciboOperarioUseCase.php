<?php

namespace App\Application\Operario\UseCases;

use App\Domain\Operario\Repositories\ReciboDistribucionReadRepository;
use Illuminate\Support\Facades\Auth;

class ObtenerDistribucionReciboOperarioUseCase
{
    public function __construct(
        private readonly ReciboDistribucionReadRepository $readRepository,
    ) {}

    /**
     * @return array{status:int,payload:array<string,mixed>}
     */
    public function execute(int $idRecibo): array
    {
        $usuario = Auth::user();

        if (!$usuario || !$usuario->hasRole('vista-costura')) {
            return [
                'status' => 403,
                'payload' => [
                    'success' => false,
                    'message' => 'No tienes permiso para ver esta información',
                ],
            ];
        }

        \Log::info('[ObtenerDistribucionReciboOperarioUseCase] Iniciando búsqueda', [
            'recibo_id' => $idRecibo,
            'usuario' => $usuario->id,
        ]);

        $recibo = $this->readRepository->findReciboById((int) $idRecibo);
        if (!$recibo) {
            \Log::warning('[ObtenerDistribucionReciboOperarioUseCase] Recibo no encontrado', [
                'recibo_id' => $idRecibo,
            ]);

            return [
                'status' => 404,
                'payload' => [
                    'success' => false,
                    'message' => 'Recibo no encontrado',
                ],
            ];
        }

        $parciales = $this->readRepository->findParcialesConTallasParaRecibo(
            pedidoProduccionId: (int) $recibo->pedido_produccion_id,
            prendaId: (int) $recibo->prenda_id,
            tipoRecibo: (string) $recibo->tipo_recibo,
            consecutivoOriginal: $recibo->consecutivo_actual
        );

        $numeroPedido = $this->readRepository->findNumeroPedidoByPedidoProduccionId((int) $recibo->pedido_produccion_id);

        if ($parciales->isEmpty()) {
            return [
                'status' => 200,
                'payload' => [
                    'success' => true,
                    'parciales' => [],
                    'mensaje' => 'No hay parciales creados para este recibo',
                    'total_parciales' => 0,
                ],
            ];
        }

        $parcialesInfo = $parciales->map(function ($parcial) use ($numeroPedido) {
            $proceso = null;
            if ($numeroPedido) {
                $proceso = $this->readRepository->findProcesoParcial(
                    numeroPedido: (int) $numeroPedido,
                    prendaId: (int) $parcial->prenda_pedido_id,
                    consecutivoParcial: $parcial->consecutivo_parcial
                );
            }

            $encargado = $proceso->encargado ?? $parcial->encargado ?? 'SIN ASIGNAR';
            $area = $proceso->proceso ?? $parcial->area ?? 'SIN ASIGNAR';

            $estaCompletado = $this->readRepository->estaCompletadoParcialEnCostura((int) $parcial->id);

            return [
                'id' => $parcial->id,
                'area' => $area,
                'encargado' => $encargado,
                'tipo_recibo' => $parcial->tipo_recibo,
                'consecutivo_parcial' => (float) $parcial->consecutivo_parcial,
                'consecutivo_original' => (float) $parcial->consecutivo_original,
                'proceso_estado' => $estaCompletado
                    ? 'COMPLETADO'
                    : (($proceso->estado_proceso ?? 'En Progreso') ?: 'En Progreso'),
                'fecha_asignacion' => $proceso->fecha_de_asignacion_encargado ?? null,
                'observaciones' => $proceso->observaciones ?? '',
                'pedido_produccion_id' => $parcial->pedido_produccion_id,
                'prenda_pedido_id' => $parcial->prenda_pedido_id,
                'numero_pedido' => $numeroPedido,
                'tallas' => ($parcial->tallas ?? collect())->map(function ($talla) {
                    return [
                        'id' => $talla->id,
                        'talla' => $talla->talla,
                        'cantidad' => $talla->cantidad,
                        'color_nombre' => $talla->color_nombre,
                    ];
                })->toArray(),
            ];
        })->sortBy('area')->values();

        return [
            'status' => 200,
            'payload' => [
                'success' => true,
                'recibo' => [
                    'id' => $recibo->id,
                    'consecutivo' => $recibo->consecutivo_actual,
                    'tipo_recibo' => $recibo->tipo_recibo,
                    'area_actual' => $recibo->area,
                    'numero_pedido' => $numeroPedido,
                ],
                'parciales' => $parcialesInfo,
                'total_parciales' => $parcialesInfo->count(),
            ],
        ];
    }
}

