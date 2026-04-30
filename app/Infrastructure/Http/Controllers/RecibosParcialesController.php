<?php

namespace App\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecibosParcialesController extends Controller
{
    private const TIPO_RECIBO_NO_PERMITIDO_EN_ANEXOS = 'COSTURA-BODEGA';

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
                'tallas.*.color_nombre' => 'nullable|string|max:100',
                'notas' => 'nullable|string|max:1000',
            ]);

            Log::info('[RecibosParcialesController@store] Datos recibidos:', $validated);

            $tipoReciboDB = strtoupper($validated['tipo_proceso']);
            if ($tipoReciboDB === self::TIPO_RECIBO_NO_PERMITIDO_EN_ANEXOS) {
                return response()->json([
                    'success' => false,
                    'message' => 'El tipo COSTURA-BODEGA no aplica para anexos',
                ], 422);
            }

            // Regla de negocio:
            // No permitir crear anexos de COSTURA cuando el recibo base de COSTURA
            // ya está aprobado/activo con consecutivo (hasta que se anule).
            if ($tipoReciboDB === 'COSTURA') {
                $reciboBaseCosturaActivo = DB::table('consecutivos_recibos_pedidos')
                    ->where('pedido_produccion_id', $validated['pedido_id'])
                    ->where('prenda_id', $validated['prenda_id'])
                    ->whereIn('tipo_recibo', ['COSTURA', 'COSTURA-BODEGA'])
                    ->where('origen_recibo', 'BASE')
                    ->where(function ($q) {
                        $q->where('activo', 1)
                          ->orWhereRaw('UPPER(COALESCE(estado, "")) = "APROBADO"');
                    })
                    ->whereNotNull('consecutivo_actual')
                    ->whereRaw('UPPER(COALESCE(estado, "")) <> "ANULADO"')
                    ->exists();

                if ($reciboBaseCosturaActivo) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No se puede generar anexo de COSTURA porque el recibo base ya está aprobado con consecutivo. Primero debes anular el recibo de COSTURA.',
                    ], 422);
                }
            }

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
                        'color_nombre' => $talla['color_nombre'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    Log::info('[RecibosParcialesController@store] Talla parcial creada:', [
                        'talla' => $talla['talla'],
                        'cantidad' => $talla['cantidad'],
                        'color_nombre' => $talla['color_nombre'] ?? null,
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
            // Formato enriquecido por color: {CABALLERO: {M: [{color,cantidad}]}}
            $tallasFormatoColores = [
                'DAMA' => [],
                'CABALLERO' => [],
                'UNISEX' => []
            ];
            foreach ($tallas as $talla) {
                $genero = $talla->genero ?? 'CABALLERO';
                if (!isset($tallasPorGenero[$genero])) {
                    $tallasPorGenero[$genero] = [];
                }
                $labelColor = isset($talla->color_nombre) && $talla->color_nombre ? (' (' . $talla->color_nombre . ')') : '';
                $tallasPorGenero[$genero][] = $talla->talla . $labelColor . '-' . $talla->cantidad;

                // Formato para Formatters
                $generoKey = strtoupper($genero);
                if (!isset($tallasFormato[$generoKey])) {
                    $tallasFormato[$generoKey] = [];
                }
                $tallasFormato[$generoKey][$talla->talla] = (int) $talla->cantidad;

                // Formato enriquecido por color
                $tallaKey = (string) $talla->talla;
                $colorNombre = isset($talla->color_nombre) && $talla->color_nombre ? (string) $talla->color_nombre : 'SIN COLOR';
                if (!isset($tallasFormatoColores[$generoKey])) {
                    $tallasFormatoColores[$generoKey] = [];
                }
                if (!isset($tallasFormatoColores[$generoKey][$tallaKey])) {
                    $tallasFormatoColores[$generoKey][$tallaKey] = [];
                }
                $tallasFormatoColores[$generoKey][$tallaKey][] = [
                    'color' => strtoupper($colorNombre),
                    'cantidad' => (int) $talla->cantidad
                ];
            }

            // Formato: "CABALLERO: M-1, S-1"
            $descripcionTallas = '';
            foreach ($tallasPorGenero as $genero => $tallas_str) {
                $descripcionTallas .= '<strong>' . strtoupper($genero) . ':</strong> ' . implode(', ', $tallas_str) . '<br>';
            }

            // === NUEVO: Detalles por talla (observaciones/ubicaciones) filtrados por tallas del anexo ===
            // Fuente canónica: pedidos_procesos_prenda_tallas (+ pedidos_procesos_prenda_talla_colores si aplica)
            $tallasDetalle = [];
            try {
                $tipoReciboDb = strtoupper((string) ($parcial->tipo_recibo ?? ''));
                $prendaPedidoId = (int) ($parcial->prenda_pedido_id ?? 0);

                // Resolver el proceso original asociado a esta prenda y tipo de recibo
                // Se soportan matches por nombre o slug del tipo de proceso.
                $proceso = DB::table('pedidos_procesos_prenda_detalles as ppd')
                    ->join('tipos_procesos as tp', 'tp.id', '=', 'ppd.tipo_proceso_id')
                    ->where('ppd.prenda_pedido_id', $prendaPedidoId)
                    ->where(function ($q) use ($tipoReciboDb) {
                        $q->whereRaw('UPPER(tp.nombre) = ?', [$tipoReciboDb])
                          ->orWhereRaw('UPPER(tp.slug) = ?', [$tipoReciboDb]);
                    })
                    ->select('ppd.id as proceso_prenda_detalle_id', 'ppd.modo_tallas')
                    ->first();

                if ($proceso && $proceso->proceso_prenda_detalle_id) {
                    $modoTallas = (string) ($proceso->modo_tallas ?? 'generico');

                    // Indexar tallas base del proceso por genero+talla
                    $tallasProceso = DB::table('pedidos_procesos_prenda_tallas')
                        ->where('proceso_prenda_detalle_id', (int) $proceso->proceso_prenda_detalle_id)
                        ->get(['id', 'genero', 'talla', 'ubicaciones', 'observaciones']);

                    $indexTallasProceso = [];
                    foreach ($tallasProceso as $tpRow) {
                        $g = strtoupper((string) ($tpRow->genero ?? ''));
                        $t = strtoupper((string) ($tpRow->talla ?? ''));
                        if (!$g || !$t) continue;
                        $indexTallasProceso[$g . '|' . $t] = $tpRow;
                    }

                    foreach ($tallas as $tallaParcial) {
                        $genero = strtoupper((string) ($tallaParcial->genero ?? 'CABALLERO'));
                        $tallaKey = strtoupper((string) ($tallaParcial->talla ?? ''));
                        $colorNombre = isset($tallaParcial->color_nombre) && $tallaParcial->color_nombre
                            ? (string) $tallaParcial->color_nombre
                            : null;

                        $base = $indexTallasProceso[$genero . '|' . $tallaKey] ?? null;
                        if (!$base) {
                            continue;
                        }

                        $ubicaciones = [];
                        if ($base->ubicaciones) {
                            if (is_array($base->ubicaciones)) $ubicaciones = $base->ubicaciones;
                            else if (is_string($base->ubicaciones)) $ubicaciones = json_decode($base->ubicaciones, true) ?? [];
                        }

                        $observaciones = (string) ($base->observaciones ?? '');

                        // Si el anexo especifica color y el modo es especifico, priorizar detalles por color
                        if ($colorNombre && strtolower($modoTallas) === 'especifico') {
                            $rowColor = DB::table('pedidos_procesos_prenda_talla_colores')
                                ->where('pedidos_procesos_prenda_talla_id', (int) $base->id)
                                ->whereRaw('UPPER(color_nombre) = ?', [strtoupper($colorNombre)])
                                ->first(['ubicaciones', 'observaciones']);

                            if ($rowColor) {
                                $ubicacionesColor = [];
                                if ($rowColor->ubicaciones) {
                                    if (is_array($rowColor->ubicaciones)) $ubicacionesColor = $rowColor->ubicaciones;
                                    else if (is_string($rowColor->ubicaciones)) $ubicacionesColor = json_decode($rowColor->ubicaciones, true) ?? [];
                                }

                                $ubicaciones = $ubicacionesColor;
                                $observaciones = (string) ($rowColor->observaciones ?? '');
                            }
                        }

                        $tallasDetalle[] = [
                            'genero' => $genero,
                            'talla' => $tallaKey,
                            'cantidad' => (int) ($tallaParcial->cantidad ?? 0),
                            'color_nombre' => $colorNombre,
                            'ubicaciones' => $ubicaciones,
                            'observaciones' => $observaciones,
                        ];
                    }
                }
            } catch (\Exception $e) {
                Log::warning('[RecibosParcialesController@show] No se pudieron construir tallas_detalle para anexo', [
                    'id' => $id,
                    'message' => $e->getMessage(),
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'parcial' => $parcial,
                    'tallas' => $tallas,
                    'tallas_formato' => $tallasFormato, // {CABALLERO: {M: 4, S: 1}} para Formatters
                    'tallas_formato_colores' => $tallasFormatoColores, // {CABALLERO: {M: [{color,cantidad}]}}
                    'tallas_detalle' => $tallasDetalle,
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
                $tipoReciboParcial = strtoupper((string) ($parcial->tipo_recibo ?? ''));
                $tipoRecibo = $this->resolverTipoReciboAnexo($tipoReciboParcial);

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
                        'fecha_activacion' => now(),
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
                        'origen_recibo' => 'ANEXO',
                        'consecutivo_inicial' => $nuevoConsecutivo,
                        'consecutivo_actual' => $nuevoConsecutivo,
                        'activo' => 1,
                        'notas' => "Generado al activar anexo (parcial_id:{$id})",
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                } else {
                    // Curación defensiva: si había registro legado del anexo marcado como BASE,
                    // normalizarlo a ANEXO para evitar que contamine el recibo base.
                    DB::table('consecutivos_recibos_pedidos')
                        ->where('pedido_produccion_id', $parcial->pedido_produccion_id)
                        ->where('tipo_recibo', $tipoRecibo)
                        ->where('prenda_id', $parcial->prenda_pedido_id)
                        ->where('notas', 'LIKE', '%parcial_id:' . $id . '%')
                        ->update([
                            'origen_recibo' => 'ANEXO',
                            'updated_at' => now(),
                        ]);
                }

                $consecutivoGenerado = $nuevoConsecutivo;

                Log::info('[RecibosParcialesController@activar] Recibo parcial activado', [
                    'parcial_id' => $id,
                    'tipo_recibo_parcial' => $tipoReciboParcial,
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
            $tipoReciboParcial = strtoupper((string)($parcial->tipo_recibo ?? ''));
            $tipoRecibo = $this->resolverTipoReciboAnexo($tipoReciboParcial);
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
                        'area' => 'ANULADO',
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

    /**
     * Anula un recibo normal (BORDADO, ESTAMPADO, DTF, SUBLIMADO, etc.)
     * usando el consecutivo_recibo_id
     * POST /api/recibos/{reciboId}/anular
     */
    public function anularRecebitoGeneral(int $reciboId)
    {
        try {
            // Validar permisos
            if (!auth()->user()->hasRole(['supervisor_pedidos', 'admin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para anular recibos'
                ], 403);
            }

            // Obtener el recibo
            $recibo = DB::table('consecutivos_recibos_pedidos')
                ->where('id', $reciboId)
                ->first();

            if (!$recibo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Recibo no encontrado'
                ], 404);
            }

            DB::beginTransaction();

            try {
                // Anular el recibo
                DB::table('consecutivos_recibos_pedidos')
                    ->where('id', $reciboId)
                    ->update([
                        'estado' => 'ANULADO',
                        'area' => 'ANULADO',
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
                'message' => 'Recibo anulado correctamente',
                'data' => [
                    'id' => (int) $reciboId,
                    'estado' => 'ANULADO',
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('[RecibosParcialesController@anularRecebitoGeneral] Error:', [
                'message' => $e->getMessage(),
                'reciboId' => $reciboId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al anular recibo',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Los anexos deben usar su tipo real (COSTURA, REFLECTIVO, etc.) y no mezclar
     * con flujos de COSTURA-BODEGA.
     */
    private function resolverTipoReciboAnexo(string $tipoReciboParcial): string
    {
        $tipoRecibo = strtoupper(trim($tipoReciboParcial));

        if ($tipoRecibo === '' || $tipoRecibo === self::TIPO_RECIBO_NO_PERMITIDO_EN_ANEXOS) {
            throw new \DomainException('Tipo de recibo de anexo invalido: ' . ($tipoRecibo ?: 'VACIO'));
        }

        return $tipoRecibo;
    }
}
