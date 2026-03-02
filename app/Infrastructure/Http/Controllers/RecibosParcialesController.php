<?php

namespace App\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ConsecutivoReciboPedido;
use App\Models\PedidoProduccion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecibosParcialesController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Crea un registro de recibo parcial (sin duplicar prenda, solo asociación)
     * POST /api/recibos-parciales
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            // Validar permisos
            if (!auth()->user()->hasRole(['supervisor_pedidos', 'admin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para crear recibos parciales'
                ], 403);
            }

            $validated = $request->validate([
                'pedido_id' => 'required|integer|exists:pedidos_produccion,id',
                'prenda_id' => 'required|integer|exists:prendas_pedido,id',
                'tipo_proceso' => 'required|string',
                'tallas' => 'required|array|min:1',
                'tallas.*.talla' => 'required|string',
                'tallas.*.cantidad' => 'required|integer|min:1',
                'tallas.*.genero' => 'nullable|string',
                'notas' => 'nullable|string|max:1000',
            ]);

            Log::info('[RecibosParcialesController@store] Datos recibidos:', $validated);

            $tipoReciboDB = strtoupper($validated['tipo_proceso']);

            DB::beginTransaction();

            try {
                // 1. Verificar que la prenda pertenece al pedido
                $prenda = DB::table('prendas_pedido')
                    ->where('id', $validated['prenda_id'])
                    ->where('pedido_produccion_id', $validated['pedido_id'])
                    ->firstOrFail();

                Log::info('[RecibosParcialesController@store] Prenda verificada:', [
                    'prenda_id' => $prenda->id,
                    'pedido_id' => $validated['pedido_id'],
                ]);

                // 2. Crear registro en pedidos_parciales
                $parcialId = DB::table('pedidos_parciales')->insertGetId([
                    'pedido_produccion_id' => $validated['pedido_id'],
                    'prenda_pedido_id' => $validated['prenda_id'],
                    'tipo_recibo' => $tipoReciboDB,
                    'estado' => 'PENDIENTE',
                    'consecutivo_actual' => null,
                    'consecutivo_inicial' => null,
                    'activo' => 0,
                    'notas' => $validated['notas'] ?? null,
                    'created_by' => auth()->id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                Log::info('[RecibosParcialesController@store] Recibo parcial creado:', [
                    'id' => $parcialId,
                    'tipo_recibo' => $tipoReciboDB,
                ]);

                // 3. Crear registros de tallas en pedidos_parciales_tallas
                foreach ($validated['tallas'] as $talla) {
                    DB::table('pedidos_parciales_tallas')->insert([
                        'pedido_parcial_id' => $parcialId,
                        'talla' => $talla['talla'],
                        'cantidad' => $talla['cantidad'],
                        'genero' => $talla['genero'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    Log::info('[RecibosParcialesController@store] Talla parcial creada:', [
                        'talla' => $talla['talla'],
                        'cantidad' => $talla['cantidad'],
                    ]);
                }

                DB::commit();

                Log::info('[RecibosParcialesController@store] Recibo parcial completado:', [
                    'parcial_id' => $parcialId,
                    'tipo_recibo' => $tipoReciboDB,
                    'tallas_count' => count($validated['tallas']),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Recibo parcial creado exitosamente',
                    'data' => [
                        'id' => $parcialId,
                        'pedido_id' => $validated['pedido_id'],
                        'prenda_id' => $validated['prenda_id'],
                        'tipo_recibo' => $tipoReciboDB,
                        'estado' => 'PENDIENTE',
                        'tallas' => $validated['tallas'],
                        'usuario_id' => auth()->id(),
                        'usuario_nombre' => auth()->user()->name,
                    ],
                ], 201);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('[RecibosParcialesController@store] Validación fallida:', $e->errors());
            
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('[RecibosParcialesController@store] Error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al crear recibo parcial',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtiene los detalles de un recibo parcial
     * GET /api/recibos-parciales/{id}
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $parcial = DB::table('pedidos_parciales')
                ->where('id', $id)
                ->first();

            if (!$parcial) {
                return response()->json([
                    'success' => false,
                    'message' => 'Recibo parcial no encontrado',
                ], 404);
            }

            $tallas = DB::table('pedidos_parciales_tallas')
                ->where('pedido_parcial_id', $id)
                ->get();

            // Transformar tallas al formato para mostrar en descripción
            $tallasPorGenero = [];
            // Formato compatible con Formatters._agregarTallasFormato: {CABALLERO: {M: 4, S: 1}}
            $tallasFormato = [];
            foreach ($tallas as $talla) {
                $genero = $talla->genero ?? 'CABALLERO';
                if (!isset($tallasPorGenero[$genero])) {
                    $tallasPorGenero[$genero] = [];
                }
                $tallasPorGenero[$genero][] = $talla->talla . '-' . $talla->cantidad;

                // Formato para Formatters
                $generoKey = strtoupper($genero);
                if (!isset($tallasFormato[$generoKey])) {
                    $tallasFormato[$generoKey] = [];
                }
                $tallasFormato[$generoKey][$talla->talla] = (int) $talla->cantidad;
            }

            // Formato: "CABALLERO: M-1, S-1"
            $descripcionTallas = '';
            foreach ($tallasPorGenero as $genero => $tallas_str) {
                $descripcionTallas .= '<strong>' . strtoupper($genero) . ':</strong> ' . implode(', ', $tallas_str) . '<br>';
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'parcial' => $parcial,
                    'tallas' => $tallas,
                    'tallas_formato' => $tallasFormato, // {CABALLERO: {M: 4, S: 1}} para Formatters
                    'tallas_descripcion' => $descripcionTallas,
                    'total_tallas' => count($tallas),
                    'total_cantidad' => collect($tallas)->sum('cantidad'),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('[RecibosParcialesController@show] Error:', [
                'message' => $e->getMessage(),
                'id' => $id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener recibo',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Activa un recibo parcial y genera su consecutivo
     * POST /api/recibos-parciales/{id}/activar
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function activar($id)
    {
        try {
            // Validar permisos
            if (!auth()->user()->hasRole(['supervisor_pedidos', 'admin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para activar recibos'
                ], 403);
            }

            $parcial = DB::table('pedidos_parciales')
                ->where('id', $id)
                ->whereNull('deleted_at')
                ->first();

            if (!$parcial) {
                return response()->json([
                    'success' => false,
                    'message' => 'Recibo parcial no encontrado',
                ], 404);
            }

            if ($parcial->estado === 'APROBADO' && $parcial->consecutivo_actual) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este recibo ya fue activado (consecutivo: ' . $parcial->consecutivo_actual . ')',
                ], 422);
            }

            $consecutivoGenerado = null;

            DB::transaction(function () use ($parcial, $id, &$consecutivoGenerado) {
                $tipoRecibo = $parcial->tipo_recibo; // COSTURA, BORDADO, etc.

                // Obtener siguiente consecutivo de tabla maestra
                $registroMaestro = DB::table('consecutivos_recibos')
                    ->where('tipo_recibo', $tipoRecibo)
                    ->where('activo', 1)
                    ->lockForUpdate()
                    ->first();

                if (!$registroMaestro) {
                    throw new \Exception("No existe registro maestro de consecutivos para tipo: {$tipoRecibo}");
                }

                $nuevoConsecutivo = $registroMaestro->consecutivo_actual + 1;

                // Actualizar tabla maestra
                DB::table('consecutivos_recibos')
                    ->where('id', $registroMaestro->id)
                    ->update([
                        'consecutivo_actual' => $nuevoConsecutivo,
                        'updated_at' => now()
                    ]);

                // Actualizar el recibo parcial con consecutivo y estado
                DB::table('pedidos_parciales')
                    ->where('id', $id)
                    ->update([
                        'estado' => 'APROBADO',
                        'consecutivo_actual' => $nuevoConsecutivo,
                        'consecutivo_inicial' => $nuevoConsecutivo,
                        'activo' => 1,
                        'updated_at' => now()
                    ]);

                // También insertar en consecutivos_recibos_pedidos para que el sistema
                // de recibos lo encuentre al buscar consecutivos del pedido
                $existe = DB::table('consecutivos_recibos_pedidos')
                    ->where('pedido_produccion_id', $parcial->pedido_produccion_id)
                    ->where('tipo_recibo', $tipoRecibo)
                    ->where('prenda_id', $parcial->prenda_pedido_id)
                    ->where('notas', 'LIKE', '%parcial_id:' . $id . '%')
                    ->exists();

                if (!$existe) {
                    DB::table('consecutivos_recibos_pedidos')->insert([
                        'pedido_produccion_id' => $parcial->pedido_produccion_id,
                        'prenda_id' => $parcial->prenda_pedido_id,
                        'tipo_recibo' => $tipoRecibo,
                        'consecutivo_inicial' => $nuevoConsecutivo,
                        'consecutivo_actual' => $nuevoConsecutivo,
                        'activo' => 1,
                        'notas' => "Generado al activar anexo (parcial_id:{$id})",
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }

                $consecutivoGenerado = $nuevoConsecutivo;

                Log::info('[RecibosParcialesController@activar] Recibo parcial activado', [
                    'parcial_id' => $id,
                    'tipo_recibo' => $tipoRecibo,
                    'consecutivo' => $nuevoConsecutivo,
                    'pedido_id' => $parcial->pedido_produccion_id,
                    'prenda_id' => $parcial->prenda_pedido_id,
                    'usuario' => auth()->user()->name ?? 'sistema'
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Recibo activado correctamente',
                'data' => [
                    'consecutivo' => $consecutivoGenerado,
                    'estado' => 'APROBADO'
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('[RecibosParcialesController@activar] Error:', [
                'message' => $e->getMessage(),
                'id' => $id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al activar recibo: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Elimina un recibo parcial
     * DELETE /api/recibos-parciales/{id}
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            // Validar permisos
            if (!auth()->user()->hasRole(['supervisor_pedidos', 'admin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para eliminar recibos'
                ], 403);
            }

            $parcial = DB::table('pedidos_parciales')
                ->where('id', $id)
                ->first();

            if (!$parcial) {
                return response()->json([
                    'success' => false,
                    'message' => 'Recibo parcial no encontrado',
                ], 404);
            }

            DB::beginTransaction();

            try {
                // Eliminar tallas
                DB::table('pedidos_parciales_tallas')
                    ->where('pedido_parcial_id', $id)
                    ->delete();

                // Soft delete el parcial
                DB::table('pedidos_parciales')
                    ->where('id', $id)
                    ->update([
                        'deleted_at' => now(),
                        'updated_at' => now(),
                    ]);

                DB::commit();

                Log::info('[RecibosParcialesController@destroy] Recibo parcial eliminado:', [
                    'id' => $id,
                    'usuario_id' => auth()->id(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Recibo parcial eliminado',
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('[RecibosParcialesController@destroy] Error:', [
                'message' => $e->getMessage(),
                'id' => $id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar recibo',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Anula un recibo parcial
     * POST /api/recibos-parciales/{id}/anular
     */
    public function anular($id)
    {
        try {
            if (!auth()->user()->hasRole(['supervisor_pedidos', 'admin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para anular recibos'
                ], 403);
            }

            $parcial = DB::table('pedidos_parciales')
                ->where('id', $id)
                ->whereNull('deleted_at')
                ->first();

            if (!$parcial) {
                return response()->json([
                    'success' => false,
                    'message' => 'Recibo parcial no encontrado',
                ], 404);
            }

            // Solo permitir anular si ya estaba aprobado/activo
            if (strtoupper((string)($parcial->estado ?? '')) !== 'APROBADO') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden anular anexos en estado APROBADO',
                ], 422);
            }

            // Anulación: estado visible del recibo se controla en consecutivos_recibos_pedidos.
            // El parcial también debe quedar en estado ANULADO.
            $tipoRecibo = strtoupper((string)($parcial->tipo_recibo ?? ''));
            $notaNeedle = 'parcial_id:' . $id;

            $consecutivo = DB::table('consecutivos_recibos_pedidos')
                ->where('pedido_produccion_id', $parcial->pedido_produccion_id)
                ->where('tipo_recibo', $tipoRecibo)
                ->where('prenda_id', $parcial->prenda_pedido_id)
                ->where('notas', 'LIKE', '%' . $notaNeedle . '%')
                ->orderByDesc('id')
                ->first();

            if (!$consecutivo) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró el consecutivo del anexo para anular',
                ], 404);
            }

            DB::beginTransaction();

            try {
                DB::table('consecutivos_recibos_pedidos')
                    ->where('id', $consecutivo->id)
                    ->update([
                        'estado' => 'ANULADO',
                        'activo' => 0,
                        'updated_at' => now(),
                    ]);

                // Marcar parcial como ANULADO y no-activo
                DB::table('pedidos_parciales')
                    ->where('id', $id)
                    ->update([
                        'estado' => 'ANULADO',
                        'activo' => 0,
                        'updated_at' => now(),
                    ]);

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

            return response()->json([
                'success' => true,
                'message' => 'Anexo anulado correctamente',
                'data' => [
                    'id' => (int) $id,
                    'estado' => 'ANULADO',
                    'consecutivo_id' => (int) $consecutivo->id,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('[RecibosParcialesController@anular] Error:', [
                'message' => $e->getMessage(),
                'id' => $id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al anular anexo',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
