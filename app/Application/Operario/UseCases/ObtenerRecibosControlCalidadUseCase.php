<?php

namespace App\Application\Operario\UseCases;

use App\Domain\Operario\Repositories\ProcesoPrendaRepository;
use App\Domain\Operario\Repositories\ConsecutivoReciboPedidoRepository;
use App\Domain\Operario\Repositories\ReciboDistribucionReadRepository;
use App\Domain\Operario\Repositories\PedidoProduccionOperarioReadRepository;
use App\Domain\Pedidos\Repositories\PrendaPedidoReadRepository;
use Illuminate\Support\Facades\Auth;

class ObtenerRecibosControlCalidadUseCase
{
    public function __construct(
        private readonly ProcesoPrendaRepository $procesoPrendaRepository,
        private readonly ConsecutivoReciboPedidoRepository $reciboRepository,
        private readonly ReciboDistribucionReadRepository $distribucionRepository,
        private readonly PedidoProduccionOperarioReadRepository $pedidoRepository,
        private readonly PrendaPedidoReadRepository $prendaRepository,
    ) {}

    /**
     * @return array{status:int,payload:array<string,mixed>}
     */
    public function execute(string $tipoRecibo): array
    {
        $usuario = Auth::user();

        if (!$usuario || !$usuario->hasRole('vista-costura')) {
            \Log::warning('[ObtenerRecibosControlCalidadUseCase] Acceso denegado', [
                'usuario_id' => $usuario?->id,
                'tipo_recibo' => $tipoRecibo,
            ]);

            return [
                'status' => 403,
                'payload' => [
                    'success' => false,
                    'message' => 'No tienes permiso para ver esta información',
                ],
            ];
        }

        try {
            $tipoRecibo = strtoupper(trim((string) $tipoRecibo));

            \Log::info('[ObtenerRecibosControlCalidadUseCase] Iniciando búsqueda', [
                'tipo_recibo_solicitado' => $tipoRecibo,
                'usuario_id' => $usuario->id,
            ]);

            // Obtener todos los procesos en Control de Calidad
            $procesosCC = $this->procesoPrendaRepository->findByProceso('Control de Calidad');

            \Log::info('[ObtenerRecibosControlCalidadUseCase] Procesos encontrados', [
                'total_procesos_cc' => $procesosCC->count(),
                'tipo_recibo' => $tipoRecibo,
            ]);

            if ($procesosCC->isEmpty()) {
                return [
                    'status' => 200,
                    'payload' => [
                        'success' => true,
                        'data' => [],
                        'total' => 0,
                        'mensaje' => 'No hay recibos en Control de Calidad',
                    ],
                ];
            }

            $recibosEnCC = [];
            $seenReciboIds = [];

            foreach ($procesosCC as $proceso) {
                $pedido = $this->pedidoRepository->findByNumeroWithPrendas((int) $proceso->numero_pedido);
                if (!$pedido) {
                    \Log::debug('[ObtenerRecibosControlCalidadUseCase] Pedido no encontrado para proceso CC', [
                        'numero_pedido' => $proceso->numero_pedido,
                        'proceso_id' => $proceso->id,
                    ]);
                    continue;
                }

                // Obtener el tipo_recibo del ConsecutivoReciboPedido
                $recibo = $this->reciboRepository->findActiveByPedidoConsecutivoTipo(
                    (int) $pedido->id,
                    (int) $proceso->numero_recibo,
                    $tipoRecibo
                );

                if (!$recibo) {
                    \Log::debug('[ObtenerRecibosControlCalidadUseCase] ConsecutivoReciboPedido no encontrado', [
                        'pedido_produccion_id' => $pedido->id,
                        'numero_pedido' => $pedido->numero_pedido ?? $proceso->numero_pedido,
                        'numero_recibo' => $proceso->numero_recibo,
                        'tipo_recibo' => $tipoRecibo,
                    ]);
                    continue;
                }

                $tipoReciboActual = strtoupper(trim((string) $recibo->tipo_recibo));

                // Filtrar por tipo solicitado (considera COSTURA y COSTURA-BODEGA como equivalentes)
                if ($tipoReciboActual === $tipoRecibo || 
                    ($tipoRecibo === 'COSTURA' && in_array($tipoReciboActual, ['COSTURA', 'COSTURA-BODEGA']))) {

                    if (isset($seenReciboIds[$recibo->id])) {
                        continue;
                    }
                    
                    $prenda = $this->prendaRepository->obtenerPorId((int) $proceso->prenda_pedido_id);

                    if ($pedido && $prenda) {
                        $recibosEnCC[] = [
                            'id' => $recibo->id,
                            'pedido_produccion_id' => (int) $pedido->id,
                            'numero_pedido' => $pedido->numero_pedido ?? $proceso->numero_pedido,
                            'prenda_id' => $prenda->id,
                            'nombre_prenda' => $prenda->nombre_prenda,
                            'cliente' => $pedido->cliente,
                            'tipo_recibo' => $tipoReciboActual,
                            'consecutivo_actual' => $recibo->consecutivo_actual,
                            'proceso_id' => $proceso->id,
                            'tiene_parciales' => false,
                        ];

                        $seenReciboIds[$recibo->id] = true;

                        \Log::debug('[ObtenerRecibosControlCalidadUseCase] Recibo agregado', [
                            'recibo_id' => $recibo->id,
                            'pedido_numero' => $pedido->numero_pedido,
                            'prenda_nombre' => $prenda->nombre_prenda,
                        ]);
                    }
                }
            }

            // Verificar parciales para cada recibo
            foreach ($recibosEnCC as &$reciboEnCC) {
                $parciales = $this->distribucionRepository->findParcialesConTallasParaRecibo(
                    pedidoProduccionId: (int) ($reciboEnCC['pedido_produccion_id'] ?? 0),
                    prendaId: (int) $reciboEnCC['prenda_id'],
                    tipoRecibo: $reciboEnCC['tipo_recibo'],
                    consecutivoOriginal: $reciboEnCC['consecutivo_actual']
                );

                $reciboEnCC['tiene_parciales'] = !$parciales->isEmpty();
            }

            \Log::info('[ObtenerRecibosControlCalidadUseCase] Búsqueda completada', [
                'tipo_recibo' => $tipoRecibo,
                'total_recibos' => count($recibosEnCC),
            ]);

            return [
                'status' => 200,
                'payload' => [
                    'success' => true,
                    'data' => $recibosEnCC,
                    'total' => count($recibosEnCC),
                ],
            ];

        } catch (\Exception $e) {
            \Log::error('[ObtenerRecibosControlCalidadUseCase] Error obteniendo recibos en CC', [
                'tipo_recibo' => $tipoRecibo,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'usuario_id' => $usuario->id,
            ]);

            return [
                'status' => 500,
                'payload' => [
                    'success' => false,
                    'message' => 'Error al obtener recibos: ' . $e->getMessage(),
                ],
            ];
        }
    }
}
