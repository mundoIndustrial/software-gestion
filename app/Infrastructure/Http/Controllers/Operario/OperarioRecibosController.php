<?php

namespace App\Infrastructure\Http\Controllers\Operario;

use App\Application\Operario\UseCases\CompletarReciboCorteSobremedidaUseCase;
use App\Application\Operario\UseCases\CompletarReciboOperarioUseCase;
use App\Application\Operario\UseCases\DeshacerReciboOperarioUseCase;
use App\Application\Operario\UseCases\ObtenerDistribucionReciboOperarioUseCase;
use App\Http\Controllers\Controller;
use App\Infrastructure\Repositories\Operario\OperarioObservacionesRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OperarioRecibosController extends Controller
{
    public function __construct(
        private CompletarReciboOperarioUseCase $completarReciboOperarioUseCase,
        private CompletarReciboCorteSobremedidaUseCase $completarReciboCorteSobremedidaUseCase,
        private DeshacerReciboOperarioUseCase $deshacerReciboOperarioUseCase,
        private ObtenerDistribucionReciboOperarioUseCase $obtenerDistribucionReciboOperarioUseCase,
        private OperarioObservacionesRepository $operarioObservacionesRepository,
    ) {}

    /**
     * GET /operario/api/recibos/{idRecibo}/distribucion
     */
    public function obtenerDistribucionRecibo(Request $request, $idRecibo): JsonResponse
    {
        try {
            $result = $this->obtenerDistribucionReciboOperarioUseCase->execute((int) $idRecibo);
            return response()->json($result['payload'] ?? [], (int) ($result['status'] ?? 200));
        } catch (\Exception $e) {
            \Log::error('[OperarioRecibosController] Error obteniendo distribución: ' . $e->getMessage(), [
                'recibo_id' => $idRecibo,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener distribución: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /operario/api/recibos-procesos/observacion
     * Obtiene observacion de proceso por pedido + prenda + tipo.
     */
    public function obtenerObservacionReciboProceso(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pedido_id' => 'required|integer|exists:pedidos_produccion,id',
            'prenda_id' => 'nullable|integer',
            'parcial_id' => 'nullable|integer',
            'tipo_proceso' => 'required|string|max:100',
        ]);

        $pedidoId = (int) $validated['pedido_id'];
        $prendaId = (int) ($validated['prenda_id'] ?? 0);
        $parcialId = (int) ($validated['parcial_id'] ?? 0);
        $tipoProceso = $this->normalizarTipoProceso((string) $validated['tipo_proceso']);
        $prendaIdsCandidatas = [];
        if ($prendaId > 0) {
            $prendaIdsCandidatas[] = $prendaId;
        }
        if ($parcialId > 0) {
            $prendaParcialId = $this->operarioObservacionesRepository->obtenerPrendaParcialId($parcialId, $pedidoId);
            if ($prendaParcialId > 0) {
                $prendaIdsCandidatas[] = $prendaParcialId;
            }
        }
        $prendaIdsCandidatas = array_values(array_unique(array_filter($prendaIdsCandidatas, fn($id) => (int) $id > 0)));

        $row = null;
        foreach ($this->tiposProcesoCandidatos($tipoProceso) as $tipoCandidato) {
            if (!empty($prendaIdsCandidatas)) {
                foreach ($prendaIdsCandidatas as $prendaCandidataId) {
                    $row = $this->operarioObservacionesRepository->buscarObservacionPorPedidoPrendaYProceso(
                        $pedidoId,
                        (int) $prendaCandidataId,
                        $tipoCandidato
                    );

                    if ($row) {
                        break 2;
                    }
                }
            } else {
                $row = $this->operarioObservacionesRepository->buscarObservacionPorPedidoYProceso(
                    $pedidoId,
                    $tipoCandidato
                );
                if ($row) {
                    break;
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'pedido_id' => $pedidoId,
                'prenda_id' => (int) ($row->prenda_pedido_id ?? $prendaId),
                'tipo_proceso' => $tipoProceso,
                'observacion' => $row?->observacion,
                'updated_at' => $row?->updated_at,
            ],
        ]);
    }

    /**
     * API: Completar recibo (normal o parcial)
     * POST /operario/api/recibos/{idRecibo}/completar
     */
    public function completarRecibo(Request $request, $idRecibo): JsonResponse
    {
        try {
            $esParcial = (bool) ($request->boolean('es_parcial')
                || $request->boolean('esParcial'));

            $result = $this->completarReciboOperarioUseCase->execute((int) $idRecibo, $esParcial);

            return response()->json([
                'success' => (bool) $result->success,
                'message' => (string) $result->message,
                'data' => $result->data,
            ], (int) $result->statusCode);
        } catch (\Exception $e) {
            \Log::error('[OperarioRecibosController] Error al completar recibo', [
                'id_recibo' => (int) $idRecibo,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al completar el recibo: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: Deshacer completado de recibo (normal o parcial)
     * DELETE /operario/api/recibos/{idRecibo}/deshacer
     */
    public function deshacerRecibo(Request $request, $idRecibo): JsonResponse
    {
        try {
            $esParcial = (bool) ($request->boolean('es_parcial')
                || $request->boolean('esParcial'));

            $result = $this->deshacerReciboOperarioUseCase->execute((int) $idRecibo, $esParcial);

            return response()->json([
                'success' => (bool) $result->success,
                'message' => (string) $result->message,
                'data' => $result->data,
            ], (int) $result->statusCode);
        } catch (\Exception $e) {
            \Log::error('[OperarioRecibosController] Error al deshacer recibo', [
                'id_recibo' => (int) $idRecibo,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al deshacer el recibo: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function deshacerParcial(Request $request, $id): JsonResponse
    {
        try {
            $usuario = Auth::user();
            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            $parcial = \App\Models\ReciboPorPartes::with(['pedido', 'prenda'])->findOrFail($id);
            DB::beginTransaction();

            try {
                $numeroPedido = $parcial->pedido?->numero_pedido;
                $prendaPedidoId = $parcial->prenda_pedido_id;
                $numeroReciboParcial = $parcial->consecutivo_parcial;
                $esBodega = strtoupper(trim((string) ($parcial->tipo_recibo ?? ''))) === 'CORTE-PARA-BODEGA';

                \Log::info('[DeshacerParcial] Iniciando eliminación', [
                    'parcial_id' => $id,
                    'numero_pedido' => $numeroPedido,
                    'prenda_pedido_id' => $prendaPedidoId,
                    'numero_recibo_parcial' => $numeroReciboParcial
                ]);

                $tallasEliminadas = \App\Models\ReciboPorPartesTalla::where('recibo_por_partes_id', $id)->delete();
                \Log::info('[DeshacerParcial] Tallas eliminadas', ['count' => $tallasEliminadas]);

                $procesosParcialQuery = \App\Models\ProcesoPrenda::withTrashed()
                    ->where('numero_pedido', $numeroPedido)
                    ->where('numero_recibo_parcial', $numeroReciboParcial);

                if ($esBodega) {
                    $procesosParcialQuery->where('prenda_bodega_id', $prendaPedidoId);
                } else {
                    $procesosParcialQuery->where('prenda_pedido_id', $prendaPedidoId);
                }

                $procesosParcial = $procesosParcialQuery->get();
                $procesosEliminados = 0;
                foreach ($procesosParcial as $procesoParcial) {
                    $procesoParcial->forceDelete();
                    $procesosEliminados++;
                }
                \Log::info('[DeshacerParcial] Procesos eliminados', ['count' => $procesosEliminados]);

                $parcialEliminado = $parcial->delete();
                \Log::info('[DeshacerParcial] Parcial eliminado', ['deleted' => $parcialEliminado]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Parcial eliminado correctamente',
                    'deleted' => [
                        'tallas' => $tallasEliminadas,
                        'procesos' => $procesosEliminados,
                        'parcial' => $parcialEliminado
                    ]
                ], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error('[DeshacerParcial] Error durante eliminación', [
                    'parcial_id' => $id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Parcial no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar parcial: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Completar recibo en área Corte desde pestaña sobremedida
     * POST /operario/api/recibos/{idRecibo}/completar-corte-sobremedida
     */
    public function completarReciboCorteSobremedida(Request $request, $idRecibo): JsonResponse
    {
        try {
            $result = $this->completarReciboCorteSobremedidaUseCase->execute((int) $idRecibo);

            return response()->json([
                'success' => (bool) $result->success,
                'message' => (string) $result->message,
                'data' => $result->data,
            ], (int) $result->statusCode);
        } catch (\Exception $e) {
            \Log::error('[OperarioRecibosController] Error al completar recibo en Corte (sobremedida)', [
                'id_recibo' => (int) $idRecibo,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al completar el recibo: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PATCH /operario/api/parciales/{id}/anular
     * Anula un parcial (cambia su estado a Anulado)
     */
    public function anularParcial(Request $request, $id): JsonResponse
    {
        try {
            $usuario = Auth::user();
            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            $parcial = \App\Models\ReciboPorPartes::findOrFail($id);

            DB::beginTransaction();

            try {
                \Log::info('[AnularParcial] Iniciando anulación', [
                    'parcial_id' => $id,
                    'estado_actual' => $parcial->estado,
                ]);

                // Cambiar el estado a 'Anulado'
                $parcial->update([
                    'estado' => 'Anulado',
                    'updated_at' => now(),
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Parcial anulado correctamente',
                    'data' => [
                        'id' => $parcial->id,
                        'estado' => $parcial->estado,
                    ]
                ], 200);

            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error('[AnularParcial] Error durante anulación', [
                    'parcial_id' => $id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Parcial no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al anular parcial: ' . $e->getMessage()
            ], 500);
        }
    }

    private function normalizarTipoProceso(string $tipoProceso): string
    {
        return mb_strtoupper(trim($tipoProceso), 'UTF-8');
    }

    /**
     * Los parciales se tratan como COSTURA dentro de operario.
     *
     * @return string[]
     */
    private function tiposProcesoCandidatos(string $tipoProceso): array
    {
        $tipo = $this->normalizarTipoProceso($tipoProceso);

        if ($tipo === 'PARCIAL' || $tipo === 'COSTURA' || $tipo === 'COSTURA-BODEGA') {
            return ['COSTURA'];
        }

        return [$tipo];
    }
}
