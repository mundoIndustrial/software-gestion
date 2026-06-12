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

        // Permitir a cualquier usuario autenticado
        // El middleware 'control-calidad-access' ya valida el acceso en el controlador
        if (!$usuario) {
            \Log::warning('[ObtenerDistribucionControlCalidadUseCase] Usuario no autenticado', [
                'recibo_id' => $idRecibo,
            ]);

            return [
                'status' => 403,
                'payload' => [
                    'success' => false,
                    'message' => 'No autenticado',
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
            // Obtener número de pedido desde pedido_produccion_id
            $numeroPedido = \DB::table('pedidos_produccion')
                ->where('id', (int) $recibo->pedido_produccion_id)
                ->value('numero_pedido');

            $consecutivosParcialesNormalizados = $parciales
                ->pluck('consecutivo_parcial')
                ->map(fn ($valor) => $this->normalizarConsecutivoParcialValor($valor))
                ->filter(fn (string $valor) => $valor !== '')
                ->values()
                ->all();

            $parcialesEnCCIds = \DB::table('procesos_prenda')
                ->where('numero_pedido', $numeroPedido)
                ->whereRaw('LOWER(TRIM(proceso)) IN (?, ?)', ['control calidad', 'control de calidad'])
                ->whereNull('deleted_at')
                ->get(['numero_recibo_parcial'])
                ->map(fn ($row) => $this->normalizarConsecutivoParcialValor($row->numero_recibo_parcial ?? null))
                ->filter(fn (string $valor) => $valor !== '')
                ->values()
                ->all();

            \Log::info('[ObtenerDistribucionControlCalidadUseCase] DEBUG - Parciales en procesos_prenda', [
                'recibo_id' => $idRecibo,
                'numero_pedido' => $numeroPedido,
                'parciales_consecutivos' => $parciales->pluck('consecutivo_parcial')->toArray(),
                'parciales_en_cc_ids' => $parcialesEnCCIds,
            ]);

            // Filtrar solo los parciales que estén en Control de Calidad según procesos_prenda
                        $parcialesCC = $parciales->filter(function ($parcial) use ($parcialesEnCCIds) {
                $consecutivoNormalizado = $this->normalizarConsecutivoParcialValor($parcial->consecutivo_parcial ?? null);

                if ($consecutivoNormalizado !== '' && in_array($consecutivoNormalizado, $parcialesEnCCIds, true)) {
                    return true;
                }

                return \DB::table('prenda_recibo_completado')
                    ->where('id_parcial', (int) $parcial->id)
                    ->whereRaw('LOWER(TRIM(area)) IN (?, ?)', ['control calidad', 'control de calidad'])
                    ->exists();
            });

            \Log::info('[ObtenerDistribucionControlCalidadUseCase] Parciales en CC filtrados', [
                'recibo_id' => $idRecibo,
                'total_parciales_cc' => $parcialesCC->count(),
                'total_parciales' => $parciales->count(),
            ]);

            $parcialesInfo = $parcialesCC->map(function ($parcial) {
                // Verificar si el parcial está completado en control de calidad
                $estaCompletado = \DB::table('prenda_recibo_completado')
                    ->where('id_parcial', $parcial->id)
                    ->where('area', 'Control de Calidad')
                    ->exists();

                return [
                    'id' => $parcial->id,
                    'pedido_numero' => $parcial->pedido_produccion_id,
                    'prenda_id' => $parcial->prenda_pedido_id,
                    'nombre_prenda' => $parcial->nombre_prenda ?? 'Sin nombre',
                    'cliente' => $parcial->cliente ?? 'Sin cliente',
                    'consecutivo_parcial' => $parcial->consecutivo_parcial,
                    'consecutivo_original' => $parcial->consecutivo_original,
                    'tipo_recibo' => $parcial->tipo_recibo,
                    'area' => $parcial->area,
                    'encargado' => $parcial->encargado ?? 'Sin asignar',
                    'estado_proceso' => 'En Progreso',
                    'completado_area' => $estaCompletado,
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

    private function normalizarConsecutivoParcialValor(mixed $valor): string
    {
        $texto = trim((string) $valor);

        if ($texto === '') {
            return '';
        }

        if (is_numeric($texto)) {
            $numero = (float) $texto;

            if (floor($numero) === $numero) {
                return (string) (int) $numero;
            }

            return rtrim(rtrim(number_format($numero, 2, '.', ''), '0'), '.');
        }

        return $texto;
    }
}