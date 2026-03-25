<?php

namespace App\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Application\Operario\DTOs\CambiarAreaControlCalidadCommandDTO;
use App\Application\Operario\DTOs\DeshacerControlCalidadCommandDTO;
use App\Application\Operario\DTOs\DeshacerCosturaCommandDTO;
use App\Application\Operario\DTOs\LimpiarEncargadoCosturaCommandDTO;
use App\Application\Operario\DTOs\PasarACosturaCommandDTO;
use App\Application\Operario\UseCases\CambiarAreaControlCalidadUseCase;
use App\Application\Operario\UseCases\DeshacerControlCalidadUseCase;
use App\Application\Operario\UseCases\DeshacerCosturaUseCase;
use App\Application\Operario\UseCases\LimpiarEncargadoCosturaUseCase;
use App\Application\Operario\UseCases\PasarACosturaUseCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\PedidoProduccion;
use App\Models\ConsecutivoReciboPedido;
use App\Models\ProcesoPrenda;

class ReciboCosturaController extends Controller
{
    public function __construct(
        private readonly CambiarAreaControlCalidadUseCase $cambiarAreaControlCalidadUseCase,
        private readonly DeshacerControlCalidadUseCase    $deshacerControlCalidadUseCase,
        private readonly PasarACosturaUseCase             $pasarACosturaUseCase,
        private readonly DeshacerCosturaUseCase           $deshacerCosturaUseCase,
        private readonly LimpiarEncargadoCosturaUseCase    $limpiarEncargadoCosturaUseCase,
    ) {
        $this->middleware('auth');
    }

    public function distribuirPorModulos(Request $request, $pedidoId, $numeroRecibo)
    {
        try {
            if (!auth()->user()->hasRole('vista-costura')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para realizar esta acción'
                ], 403);
            }

            $request->validate([
                'prenda_id' => 'required|integer|exists:prendas_pedido,id',
                'tipo_recibo' => 'required|string',
                'asignaciones' => 'required|array|min:1',
                'asignaciones.*.encargado' => 'required|string|max:100',
                'asignaciones.*.tallas' => 'required|array|min:1',
                'asignaciones.*.tallas.*.talla' => 'required|string|max:50',
                'asignaciones.*.tallas.*.cantidad' => 'required|integer|min:1',
                'asignaciones.*.tallas.*.color_nombre' => 'nullable|string|max:191',
            ]);

            $pedido = PedidoProduccion::findOrFail((int) $pedidoId);
            $prendaId = (int) $request->prenda_id;
            $tipoRecibo = (string) $request->tipo_recibo;
            $consecutivoOriginal = (int) $numeroRecibo;

            Log::info('[COSTURA][DISTRIBUIR] Solicitud recibida', [
                'pedido_id' => (int) $pedidoId,
                'numero_pedido' => $pedido->numero_pedido,
                'prenda_id' => $prendaId,
                'tipo_recibo' => $tipoRecibo,
                'consecutivo_original' => $consecutivoOriginal,
                'asignaciones_count' => count((array) $request->asignaciones),
            ]);

            $recibo = ConsecutivoReciboPedido::query()
                ->where('pedido_produccion_id', (int) $pedidoId)
                ->where('consecutivo_actual', $consecutivoOriginal)
                ->whereRaw('UPPER(TRIM(tipo_recibo)) = ?', [strtoupper(trim($tipoRecibo))])
                ->where('activo', 1)
                ->first();

            if (!$recibo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Recibo no encontrado'
                ], 404);
            }

            $resultado = DB::transaction(function () use ($pedido, $recibo, $pedidoId, $prendaId, $tipoRecibo, $consecutivoOriginal, $request) {
                $procesoPadre = ProcesoPrenda::query()
                    ->where('numero_pedido', $pedido->numero_pedido)
                    ->where('prenda_pedido_id', $prendaId)
                    ->whereRaw('LOWER(TRIM(proceso)) = ?', ['costura'])
                    ->where('numero_recibo', $consecutivoOriginal)
                    ->where(function ($query) {
                        $query->whereNull('numero_recibo_parcial')
                              ->orWhere('numero_recibo_parcial', 0);
                    })
                    ->whereNull('deleted_at')
                    ->orderByDesc('created_at')
                    ->first();

                Log::info('[COSTURA][DISTRIBUIR] Búsqueda proceso padre', [
                    'pedido_id' => $pedidoId,
                    'prenda_id' => $prendaId,
                    'numero_recibo' => $consecutivoOriginal,
                    'proceso_padre_encontrado' => $procesoPadre ? $procesoPadre->id : null,
                ]);

                if (!$procesoPadre) {
                    $procesoPadre = ProcesoPrenda::create([
                        'numero_pedido' => $pedido->numero_pedido,
                        'prenda_pedido_id' => $prendaId,
                        'numero_recibo' => $consecutivoOriginal,
                        'numero_recibo_parcial' => null,
                        'proceso' => 'Costura',
                        'fecha_inicio' => now(),
                        'encargado' => null,
                        'estado_proceso' => 'Pendiente',
                        'codigo_referencia' => 'COS-' . $consecutivoOriginal . '-' . date('YmdHis'),
                    ]);
                } else {
                    // Si ya existe, asegurarse de que el área del recibo esté en Costura
                    $recibo->area = 'Costura';
                    $recibo->save();
                }

                $maxParcialExistente = ProcesoPrenda::query()
                    ->where('numero_pedido', $pedido->numero_pedido)
                    ->where('prenda_pedido_id', $prendaId)
                    ->whereRaw('LOWER(TRIM(proceso)) = ?', ['costura'])
                    ->whereNull('numero_recibo')
                    ->whereNotNull('numero_recibo_parcial')
                    ->whereNull('deleted_at')
                    ->max('numero_recibo_parcial');

                $nextIndex = 1;
                if ($maxParcialExistente !== null) {
                    $maxFloat = (float) $maxParcialExistente;
                    $parteDecimal = $maxFloat - floor($maxFloat);
                    $nextIndex = (int) round($parteDecimal * 10) + 1;
                }

                $creados = [];

                foreach ((array) $request->asignaciones as $asig) {
                    $encargado = trim((string) ($asig['encargado'] ?? ''));
                    $tallas = (array) ($asig['tallas'] ?? []);
                    if ($encargado === '' || empty($tallas)) {
                        continue;
                    }

                    $consecutivoParcial = (float) ($consecutivoOriginal + ($nextIndex / 10));
                    $consecutivoParcialDb = number_format($consecutivoParcial, 2, '.', '');
                    $nextIndex++;

                    $procesoHijo = ProcesoPrenda::create([
                        'numero_pedido' => $pedido->numero_pedido,
                        'prenda_pedido_id' => $prendaId,
                        'numero_recibo' => null,
                        'numero_recibo_parcial' => $consecutivoParcialDb,
                        'proceso' => 'Costura',
                        'fecha_inicio' => now(),
                        'encargado' => $encargado,
                        'fecha_de_asignacion_encargado' => now(),
                        'estado_proceso' => 'En Progreso',
                        'codigo_referencia' => 'COS-' . $consecutivoParcialDb . '-' . date('YmdHis'),
                    ]);

                    $reciboParteId = DB::table('recibo_por_partes')->insertGetId([
                        'pedido_produccion_id' => (int) $pedidoId,
                        'prenda_pedido_id' => $prendaId,
                        'tipo_recibo' => $tipoRecibo,
                        'consecutivo_original' => $consecutivoOriginal,
                        'consecutivo_parcial' => $consecutivoParcialDb,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    foreach ($tallas as $t) {
                        $talla = trim((string) ($t['talla'] ?? ''));
                        $cantidad = (int) ($t['cantidad'] ?? 0);
                        $colorNombre = isset($t['color_nombre']) ? (string) $t['color_nombre'] : null;
                        if ($talla === '' || $cantidad <= 0) {
                            continue;
                        }

                        DB::table('recibos_por_partes_tallas')->insert([
                            'recibo_por_partes_id' => $reciboParteId,
                            'talla' => $talla,
                            'cantidad' => $cantidad,
                            'color_nombre' => $colorNombre,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    $creados[] = [
                        'proceso_id' => (int) $procesoHijo->id,
                        'numero_recibo' => null,
                        'numero_recibo_parcial' => $consecutivoParcialDb,
                        'encargado' => $encargado,
                    ];
                }

                return [
                    'proceso_padre_id' => (int) $procesoPadre->id,
                    'hijos' => $creados,
                    'recibo_id' => (int) $recibo->id,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Distribución del recibo guardada correctamente',
                'data' => $resultado,
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('[COSTURA][DISTRIBUIR] Error', [
                'pedido_id' => $pedidoId,
                'numero_recibo' => $numeroRecibo,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al distribuir por módulos: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function limpiarEncargadoCostura(Request $request, $pedidoId, $prendaId)
    {
        try {
            if (!auth()->user()->hasRole('vista-costura')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para realizar esta acción'
                ], 403);
            }

            $request->validate([
                'tipo_recibo' => 'required|string'
            ]);

            $resultado = $this->limpiarEncargadoCosturaUseCase->execute(new LimpiarEncargadoCosturaCommandDTO(
                pedidoId: (int) $pedidoId,
                prendaId: (int) $prendaId,
                tipoRecibo: (string) $request->tipo_recibo,
            ));

            $payload = [
                'success' => $resultado->success,
                'message' => $resultado->message,
            ];
            if (!empty($resultado->data)) {
                $payload['data'] = $resultado->data;
            }

            return response()->json($payload, $resultado->statusCode);
        } catch (\Exception $e) {
            Log::error('Error limpiando encargado de Costura', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar encargado: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cambiar área de recibo a Control Calidad
     */
    public function cambiarAreaControlCalidad(Request $request, $pedidoId, $numeroRecibo)
    {
        try {
            // Solo vista-costura puede hacer esto
            if (!auth()->user()->hasRole('vista-costura')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para realizar esta acción'
                ], 403);
            }

            $request->validate([
                'prenda_id' => 'required|integer|exists:prendas_pedido,id',
                'tipo_recibo' => 'required|string'
            ]);

            $resultado = $this->cambiarAreaControlCalidadUseCase->execute(new CambiarAreaControlCalidadCommandDTO(
                pedidoId: (int) $pedidoId,
                numeroRecibo: (int) $numeroRecibo,
                prendaId: (int) $request->prenda_id,
                tipoRecibo: (string) $request->tipo_recibo,
            ));

            $payload = [
                'success' => $resultado->success,
                'message' => $resultado->message,
            ];
            if (!empty($resultado->data)) {
                $payload['data'] = $resultado->data;
            }

            return response()->json($payload, $resultado->statusCode);

        } catch (\Exception $e) {
            Log::error('Error cambiando área de recibo a Control Calidad', [
                'pedido_id' => $pedidoId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar el área: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Deshacer el cambio a Control Calidad - eliminar proceso y restaurar área anterior
     */
    public function deshacerControlCalidad(Request $request, $pedidoId, $prendaId)
    {
        try {
            // Solo vista-costura puede hacer esto
            if (!auth()->user()->hasRole('vista-costura')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para realizar esta acción'
                ], 403);
            }

            $request->validate([
                'tipo_recibo' => 'required|string'
            ]);

            $resultado = $this->deshacerControlCalidadUseCase->execute(new DeshacerControlCalidadCommandDTO(
                pedidoId: (int) $pedidoId,
                prendaId: (int) $prendaId,
                tipoRecibo: (string) $request->tipo_recibo,
            ));

            $payload = [
                'success' => $resultado->success,
                'message' => $resultado->message,
            ];
            if (!empty($resultado->data)) {
                $payload['data'] = $resultado->data;
            }

            return response()->json($payload, $resultado->statusCode);

        } catch (\Exception $e) {
            Log::error('Error deshaciendo Control de Calidad', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al deshacer: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Pasar recibo a Costura - crea proceso con encargado y actualiza área
     */
    public function pasarACostura(Request $request, $pedidoId, $numeroRecibo)
    {
        try {
            // Logging para debugging
            Log::info('[COSTURA] Datos recibidos:', [
                'request_all' => $request->all(),
                'pedidoId' => $pedidoId,
                'numeroRecibo' => $numeroRecibo,
                'prenda_id' => $request->input('prenda_id'),
                'encargado' => $request->input('encargado'),
                'tipo_recibo' => $request->input('tipo_recibo')
            ]);

            if (!auth()->user()->hasRole('vista-costura')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para realizar esta acción'
                ], 403);
            }

            $request->validate([
                'prenda_id' => 'required|integer|exists:prendas_pedido,id',
                'tipo_recibo' => 'required|string',
                'encargado' => 'required|string|max:100'
            ]);

            $resultado = $this->pasarACosturaUseCase->execute(new PasarACosturaCommandDTO(
                pedidoId: (int) $pedidoId,
                numeroRecibo: (int) $numeroRecibo,
                prendaId: (int) $request->prenda_id,
                tipoRecibo: (string) $request->tipo_recibo,
                encargado: (string) $request->encargado,
            ));

            $payload = [
                'success' => $resultado->success,
                'message' => $resultado->message,
            ];
            if (!empty($resultado->data)) {
                $payload['data'] = $resultado->data;
            }

            return response()->json($payload, $resultado->statusCode);

        } catch (\Exception $e) {
            Log::error('Error al pasar recibo a Costura', [
                'pedido_id' => $pedidoId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al pasar a Costura: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Deshacer el proceso de Costura - eliminar proceso y restaurar área anterior
     */
    public function deshacerCostura(Request $request, $pedidoId, $prendaId)
    {
        // Logging para debugging - mostrar todos los parámetros
        Log::info('[DESHACER-COSTURA] Parámetros recibidos', [
            'route_params' => func_get_args(),
            'request_all' => $request->all(),
            'pedidoId_param' => $pedidoId,
            'prendaId_param' => $prendaId,
            'request_prenda_id' => $request->prenda_id,
            'request_tipo_recibo' => $request->tipo_recibo
        ]);

        // Logging para debugging
        Log::info('[DESHACER-COSTURA] Iniciando proceso', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'pedido_id' => $pedidoId,
            'prenda_id' => $prendaId,
            'user_agent' => $request->userAgent(),
            'ip' => $request->ip(),
            'user_id' => auth()->id(),
            'tipo_recibo' => $request->tipo_recibo
        ]);

        try {
            if (!auth()->user()->hasRole('vista-costura')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para realizar esta acción'
                ], 403);
            }

            $request->validate([
                'tipo_recibo' => 'required|string'
            ]);

            $resultado = $this->deshacerCosturaUseCase->execute(new DeshacerCosturaCommandDTO(
                pedidoId: (int) $pedidoId,
                prendaId: (int) $prendaId,
                tipoRecibo: (string) $request->tipo_recibo,
            ));

            $payload = [
                'success' => $resultado->success,
                'message' => $resultado->message,
            ];
            if (!empty($resultado->data)) {
                $payload['data'] = $resultado->data;
            }

            return response()->json($payload, $resultado->statusCode);

        } catch (\Exception $e) {
            Log::error('Error al deshacer Costura', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al deshacer Costura: ' . $e->getMessage()
            ], 500);
        }
    }
}
