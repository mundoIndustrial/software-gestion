<?php

namespace App\Application\Operario\UseCases;

use App\Domain\Operario\Repositories\ReciboDistribucionReadRepository;
use Illuminate\Support\Facades\Auth;

class ObtenerDistribucionControlCalidadUseCase
{
    public function __construct(
        private readonly ReciboDistribucionReadRepository $distribucionRepository,
    ) {}

    /**
     * @return array{status:int,payload:array<string,mixed>}
     */
    public function execute(int $idRecibo): array
    {
        $usuario = Auth::user();

        if (!$usuario || !$usuario->hasRole('vista-costura')) {
            \Log::warning('[ObtenerDistribucionControlCalidadUseCase] Acceso denegado', [
                'usuario_id' => $usuario?->id,
                'recibo_id' => $idRecibo,
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
            \Log::info('[ObtenerDistribucionControlCalidadUseCase] Iniciando búsqueda', [
                'recibo_id' => $idRecibo,
                'usuario_id' => $usuario->id,
            ]);

            // Obtener el recibo con sus parciales
            $recibo = $this->distribucionRepository->findReciboById((int) $idRecibo);

            if (!$recibo) {
                \Log::warning('[ObtenerDistribucionControlCalidadUseCase] Recibo no encontrado', [
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

            // Obtener todos los parciales del recibo
            $parciales = $this->distribucionRepository->findParcialesConTallasParaRecibo(
                pedidoProduccionId: (int) $recibo->pedido_produccion_id,
                prendaId: (int) $recibo->prenda_id,
                tipoRecibo: (string) $recibo->tipo_recibo,
                consecutivoOriginal: $recibo->consecutivo_actual
            );

            \Log::info('[ObtenerDistribucionControlCalidadUseCase] Parciales encontrados', [
                'recibo_id' => $idRecibo,
                'total_parciales' => $parciales->count(),
            ]);

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

            // Obtener número de pedido desde pedido_produccion_id
            $numeroPedido = \DB::table('pedidos_produccion')
                ->where('id', (int) $recibo->pedido_produccion_id)
                ->value('numero_pedido');

            // Obtener IDs de parciales que están en Control de Calidad (desde procesos_prenda)
            $parcialesEnCCIds = \DB::table('procesos_prenda')
                ->whereIn('numero_recibo_parcial', $parciales->pluck('consecutivo_parcial')->toArray())
                ->where('numero_pedido', $numeroPedido)
                ->whereRaw('LOWER(TRIM(proceso)) IN (?, ?)', ['control calidad', 'control de calidad'])
                ->whereNull('deleted_at')
                ->distinct()
                ->pluck('numero_recibo_parcial')
                ->toArray();

            \Log::info('[ObtenerDistribucionControlCalidadUseCase] DEBUG - Parciales en procesos_prenda', [
                'recibo_id' => $idRecibo,
                'numero_pedido' => $numeroPedido,
                'parciales_consecutivos' => $parciales->pluck('consecutivo_parcial')->toArray(),
                'parciales_en_cc_ids' => $parcialesEnCCIds,
            ]);

            // Filtrar solo los parciales que estén en Control de Calidad según procesos_prenda
            $parcialesCC = $parciales->filter(function ($parcial) use ($parcialesEnCCIds) {
                return in_array((string) $parcial->consecutivo_parcial, array_map('strval', $parcialesEnCCIds));
            });

            \Log::info('[ObtenerDistribucionControlCalidadUseCase] Parciales en CC filtrados', [
                'recibo_id' => $idRecibo,
                'total_parciales_cc' => $parcialesCC->count(),
                'total_parciales' => $parciales->count(),
            ]);

            $parcialesInfo = $parcialesCC->map(function ($parcial) {
                return [
                    'id' => $parcial->id,
                    'pedido_numero' => $parcial->pedido_produccion_id,
                    'prenda_id' => $parcial->prenda_pedido_id,
                    'nombre_prenda' => $parcial->nombre_prenda ?? 'Sin nombre',
                    'cliente' => $parcial->cliente ?? 'Sin cliente',
                    'consecutivo_parcial' => $parcial->consecutivo_parcial,
                    'tipo_recibo' => $parcial->tipo_recibo,
                    'area' => $parcial->area,
                    'tallas' => $parcial->tallas?->map(fn($t) => [
                        'id' => $t->id,
                        'talla' => $t->talla,
                        'cantidad' => $t->cantidad,
                    ])->toArray() ?? [],
                ];
            })->values();

            return [
                'status' => 200,
                'payload' => [
                    'success' => true,
                    'parciales' => $parcialesInfo->toArray(),
                    'total_parciales' => $parcialesInfo->count(),
                    'recibo_info' => [
                        'id' => $recibo->id,
                        'tipo_recibo' => $recibo->tipo_recibo,
                        'consecutivo_actual' => $recibo->consecutivo_actual,
                    ],
                ],
            ];

        } catch (\Exception $e) {
            \Log::error('[ObtenerDistribucionControlCalidadUseCase] Error obteniendo distribución en CC', [
                'recibo_id' => $idRecibo,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'usuario_id' => $usuario->id,
            ]);

            return [
                'status' => 500,
                'payload' => [
                    'success' => false,
                    'message' => 'Error al obtener distribución: ' . $e->getMessage(),
                ],
            ];
        }
    }
}
