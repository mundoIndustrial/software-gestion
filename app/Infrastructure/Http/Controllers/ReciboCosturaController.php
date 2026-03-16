<?php

namespace App\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ConsecutivoReciboPedido;
use App\Models\PedidoProduccion;
use App\Models\ProcesoPrenda;
use App\Models\Prenda;
use App\Models\User;
use App\Events\EncargadoCosturaAsignado;
use App\Events\ReciboAsignadoCosturero;
use App\Events\OperarioRecibosActualizados;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReciboCosturaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
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

            $pedido = PedidoProduccion::findOrFail($pedidoId);

            $recibo = ConsecutivoReciboPedido::where('pedido_produccion_id', $pedido->id)
                ->where('prenda_id', $prendaId)
                ->whereRaw('UPPER(tipo_recibo) = ?', [strtoupper($request->tipo_recibo)])
                ->whereRaw('LOWER(TRIM(area)) = ?', ['costura'])
                ->where('activo', 1)
                ->first();

            if (!$recibo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Recibo no encontrado o no está en Costura'
                ], 404);
            }

            DB::beginTransaction();

            $procesoCostura = ProcesoPrenda::where('prenda_pedido_id', $prendaId)
                ->where('numero_pedido', $pedido->numero_pedido)
                ->whereRaw('LOWER(TRIM(proceso)) = ?', ['costura'])
                ->whereNull('deleted_at')
                ->latest('fecha_inicio')
                ->first();

            if (!$procesoCostura) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró proceso de Costura'
                ], 404);
            }

            $procesoCostura->update([
                'encargado' => null,
                'estado_proceso' => 'Pendiente',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Encargado de Costura eliminado correctamente',
                'data' => [
                    'proceso_id' => $procesoCostura->id,
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
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

            // $pedidoId es el ID de la BD (pedidos_produccion.id)
            $pedido = PedidoProduccion::findOrFail($pedidoId);
            
            Log::info('[CC] Buscando recibo para cambiar área', [
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
                'prenda_id' => $request->prenda_id,
                'tipo_recibo' => $request->tipo_recibo,
                'numero_recibo' => $numeroRecibo
            ]);

            // Buscar el recibo ESPECÍFICO por prenda_id, tipo_recibo y estado activo
            // IMPORTANTE: Solo debe buscar este recibo, no todos de la prenda
            $recibo = ConsecutivoReciboPedido::where('pedido_produccion_id', $pedido->id)
                ->where('prenda_id', $request->prenda_id)
                ->whereRaw('UPPER(tipo_recibo) = ?', [strtoupper($request->tipo_recibo)])
                ->where('activo', 1)
                ->first();

            // Si no se encuentra, buscar por consecutivo como fallback
            if (!$recibo) {
                $recibo = ConsecutivoReciboPedido::where('pedido_produccion_id', $pedido->id)
                    ->where('consecutivo_actual', $numeroRecibo)
                    ->whereRaw('UPPER(tipo_recibo) = ?', [strtoupper($request->tipo_recibo)])
                    ->where('activo', 1)
                    ->first();
            }

            if (!$recibo) {
                // Log de diagnóstico: mostrar todos los recibos del pedido
                $todosRecibos = ConsecutivoReciboPedido::where('pedido_produccion_id', $pedido->id)->get();
                Log::error('[CC] Recibo no encontrado - diagnóstico', [
                    'pedido_id' => $pedido->id,
                    'prenda_id_buscado' => $request->prenda_id,
                    'tipo_buscado' => $request->tipo_recibo,
                    'recibos_existentes' => $todosRecibos->map(fn($r) => [
                        'id' => $r->id,
                        'prenda_id' => $r->prenda_id,
                        'tipo' => $r->tipo_recibo,
                        'activo' => $r->activo,
                        'area' => $r->area,
                    ])->toArray()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Recibo no encontrado'
                ], 404);
            }

            DB::beginTransaction();

            // Obtener el área anterior
            $areaPosterior = $recibo->area;

            // Crear el nuevo proceso en procesos_prenda
            // numero_pedido usa pedidos_produccion.numero_pedido (NO el id)
            $nuevoProceso = ProcesoPrenda::create([
                'numero_pedido' => $pedido->numero_pedido,
                'prenda_pedido_id' => $request->prenda_id,
                'numero_recibo' => $recibo->consecutivo_actual,
                'proceso' => 'Control de Calidad',
                'fecha_inicio' => now(),
                'encargado' => 'control',
                'estado_proceso' => 'En Progreso',
                'codigo_referencia' => 'CC-' . $recibo->consecutivo_actual . '-' . date('YmdHis')
            ]);

            // Actualizar solo el área del recibo a Control Calidad
            $recibo->update([
                'area' => 'Control Calidad'
            ]);

            // Notificar a los costureros que este recibo ya no está disponible
            try {
                broadcast(new \App\Events\ReciboPasadoControlCalidad(
                    $pedido->id,
                    $request->prenda_id,
                    $recibo->consecutivo_actual,
                    // Obtener nombre de la prenda
                    \App\Models\Prenda::find($request->prenda_id)?->nombre ?? 'Prenda desconocida',
                    $request->tipo_recibo
                ));
                
                Log::info('Broadcast enviado a costureros - recibo pasado a Control Calidad', [
                    'pedido_id' => $pedido->id,
                    'prenda_id' => $request->prenda_id,
                    'numero_recibo' => $recibo->consecutivo_actual
                ]);
            } catch (\Exception $e) {
                Log::warning('Error al enviar broadcast a costureros', [
                    'error' => $e->getMessage()
                ]);
            }

            DB::commit();

            Log::info('Recibo enviado a Control Calidad', [
                'pedido_id' => $pedidoId,
                'numero_pedido' => $pedido->numero_pedido,
                'prenda_id' => $request->prenda_id,
                'numero_recibo' => $recibo->consecutivo_actual,
                'proceso_id' => $nuevoProceso->id,
                'usuario_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Recibo enviado a Control Calidad correctamente',
                'data' => [
                    'proceso_id' => $nuevoProceso->id,
                    'proceso_nombre' => 'Control de Calidad',
                    'area_anterior' => $areaPosterior
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
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

            $pedido = PedidoProduccion::findOrFail($pedidoId);
            
            // Buscar el recibo ESPECÍFICO en Control Calidad por prenda_id y tipo_recibo
            $recibo = ConsecutivoReciboPedido::where('pedido_produccion_id', $pedido->id)
                ->where('prenda_id', $prendaId)
                ->whereRaw('UPPER(tipo_recibo) = ?', [strtoupper($request->tipo_recibo)])
                ->whereRaw('LOWER(TRIM(area)) IN (?, ?)', ['control calidad', 'control de calidad'])
                ->where('activo', 1)
                ->first();

            if (!$recibo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Recibo no encontrado o no está en Control Calidad'
                ], 404);
            }

            DB::beginTransaction();

            // Buscar el proceso de Control de Calidad más reciente a eliminar
            // Filtrar por numero_recibo para ser más específico
            $procesoCC = ProcesoPrenda::where('prenda_pedido_id', $prendaId)
                ->where('numero_pedido', $pedido->numero_pedido)
                ->where('proceso', 'Control de Calidad')
                ->where('numero_recibo', $recibo->consecutivo_actual)
                ->whereNull('deleted_at')
                ->latest('fecha_inicio')
                ->first();

            if (!$procesoCC) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró proceso de Control de Calidad para eliminar'
                ], 404);
            }

            // Buscar el proceso anterior más reciente (NO Control de Calidad y mismo recibo)
            $procesoPosterior = ProcesoPrenda::where('prenda_pedido_id', $prendaId)
                ->where('numero_pedido', $pedido->numero_pedido)
                ->where('numero_recibo', $recibo->consecutivo_actual)
                ->where('proceso', '!=', 'Control de Calidad')
                ->whereNull('deleted_at')
                ->latest('fecha_inicio')
                ->first();

            // Actualizar el área del recibo al proceso anterior
            $areaAnterior = $procesoPosterior ? $procesoPosterior->proceso : 'Costura';

            $recibo->update([
                'area' => $areaAnterior
            ]);

            // Eliminar el proceso de Control de Calidad permanentemente
            $procesoCC->forceDelete();

            DB::commit();

            Log::info('Proceso de Control de Calidad deshecho', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'proceso_id' => $procesoCC->id,
                'area_anterior' => $areaAnterior,
                'usuario_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Control de Calidad deshecho correctamente',
                'data' => [
                    'area_nueva' => $areaAnterior,
                    'proceso_anterior' => $procesoPosterior ? $procesoPosterior->proceso : null
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
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

            $pedido = PedidoProduccion::findOrFail($pedidoId);

            Log::info('[COSTURA] Buscando recibo para pasar a Costura', [
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
                'prenda_id' => $request->prenda_id,
                'tipo_recibo' => $request->tipo_recibo,
                'numero_recibo' => $numeroRecibo,
                'encargado' => $request->encargado
            ]);

            // Buscar el recibo específico
            $recibo = ConsecutivoReciboPedido::where('pedido_produccion_id', $pedido->id)
                ->where('prenda_id', $request->prenda_id)
                ->whereRaw('UPPER(tipo_recibo) = ?', [strtoupper($request->tipo_recibo)])
                ->where('activo', 1)
                ->first();

            // Fallback por consecutivo
            if (!$recibo) {
                $recibo = ConsecutivoReciboPedido::where('pedido_produccion_id', $pedido->id)
                    ->where('consecutivo_actual', $numeroRecibo)
                    ->whereRaw('UPPER(tipo_recibo) = ?', [strtoupper($request->tipo_recibo)])
                    ->where('activo', 1)
                    ->first();
            }

            if (!$recibo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Recibo no encontrado'
                ], 404);
            }

            DB::beginTransaction();

            $areaAnterior = $recibo->area;

            // Si ya existe proceso de Costura (ej. creado por cortador al completar),
            // NO crear uno nuevo ni actualizar área; solo asignar encargado.
            $procesoExistente = ProcesoPrenda::where('numero_pedido', $pedido->numero_pedido)
                ->where('prenda_pedido_id', $request->prenda_id)
                ->whereRaw('LOWER(TRIM(proceso)) = ?', ['costura'])
                ->whereNull('deleted_at')
                ->latest('fecha_inicio')
                ->first();

            if ($procesoExistente) {
                $procesoExistente->update([
                    'encargado' => $request->encargado,
                    'estado_proceso' => $procesoExistente->estado_proceso === 'Pendiente' ? 'En Progreso' : $procesoExistente->estado_proceso,
                ]);
                $nuevoProceso = $procesoExistente;
            } else {
                // Crear proceso de Costura en procesos_prenda
                $nuevoProceso = ProcesoPrenda::create([
                    'numero_pedido'     => $pedido->numero_pedido,
                    'prenda_pedido_id'  => $request->prenda_id,
                    'numero_recibo'     => $recibo->consecutivo_actual,
                    'proceso'           => 'Costura',
                    'fecha_inicio'      => now(),
                    'encargado'         => $request->encargado,
                    'estado_proceso'    => 'En Progreso',
                    'codigo_referencia' => 'COS-' . $recibo->consecutivo_actual . '-' . date('YmdHis')
                ]);

                // Actualizar área del recibo a Costura
                $recibo->update([
                    'area' => 'Costura'
                ]);
            }

            DB::commit();

            // Notificar a los cortadores que el recibo ya tiene encargado de costura
            broadcast(new EncargadoCosturaAsignado(
                $pedido->id,
                $request->prenda_id,
                $recibo->consecutivo_actual,
                $request->encargado,
                $nuevoProceso->id,
                $prenda->nombre_prenda ?? 'Prenda sin nombre',
                optional($nuevoProceso->updated_at)->toIso8601String(),
                (int) $recibo->id,
                (string) ($pedido->cliente ?? '-')
            ));

            // Notificar al costurero asignado que tiene un nuevo recibo
            broadcast(new ReciboAsignadoCosturero(
                $pedido->id,
                $request->prenda_id,
                $recibo->consecutivo_actual,
                $prenda->nombre_prenda ?? 'Prenda sin nombre',
                $request->encargado,
                $nuevoProceso->id,
                $request->encargado // El nombre del costurero asignado
            ));

            // Notificar al encargado si tiene rol costura-reflectivo o lider-reflectivo
            $encargadoNormalizado = strtolower(trim((string) $request->encargado));
            if ($encargadoNormalizado !== '') {
                $operarioAsignado = User::query()
                    ->whereRaw('LOWER(TRIM(name)) = ?', [$encargadoNormalizado])
                    ->first();

                if ($operarioAsignado && ($operarioAsignado->hasRole('costura-reflectivo') || $operarioAsignado->hasRole('lider-reflectivo'))) {
                    broadcast(new OperarioRecibosActualizados(
                        userId: (int) $operarioAsignado->id,
                        payload: [
                            'area' => 'Costura',
                            'accion' => 'asignado',
                            'numero_pedido' => (int) $pedido->id,
                            'prenda_id' => (int) $request->prenda_id,
                            'proceso_id' => (int) $nuevoProceso->id,
                            'tipo_recibo' => (string) $recibo->tipo_recibo,
                            'numero_recibo' => (int) $recibo->consecutivo_actual,
                            'encargado' => (string) $request->encargado,
                            'mensaje' => "Se te asignó el recibo #{$recibo->consecutivo_actual} de {$recibo->tipo_recibo}",
                        ]
                    ));

                    Log::info('[COSTURA] Broadcast a costura-reflectivo/lider-reflectivo (asignado)', [
                        'user_id' => $operarioAsignado->id,
                        'rol' => $operarioAsignado->roles->first()->name ?? 'sin rol',
                        'recibo' => $recibo->consecutivo_actual,
                        'tipo_recibo' => $recibo->tipo_recibo,
                    ]);
                }
            }

            // Si es un recibo REFLECTIVO, notificar a TODOS los usuarios con rol costura-reflectivo y lider-reflectivo
            // para que vean el recibo en su dashboard y se actualice el badge del encargado
            $tipoReciboUpper = strtoupper(trim((string) $recibo->tipo_recibo));
            
            Log::info('[COSTURA] Verificando tipo de recibo para broadcast', [
                'tipo_recibo_original' => $recibo->tipo_recibo,
                'tipo_recibo_upper' => $tipoReciboUpper,
            ]);
            
            // Para recibos REFLECTIVO: notificar a TODOS los costura-reflectivo y lider-reflectivo
            if ($tipoReciboUpper === 'REFLECTIVO') {
                $usuariosReflectivos = User::all()->filter(function($user) {
                    return $user->hasRole('costura-reflectivo') || $user->hasRole('lider-reflectivo');
                });

                Log::info('[COSTURA] Broadcast REFLECTIVO a todos los costura-reflectivo/lider-reflectivo', [
                    'total_usuarios' => $usuariosReflectivos->count(),
                    'usuario_ids' => $usuariosReflectivos->pluck('id')->toArray(),
                    'recibo' => $recibo->consecutivo_actual,
                    'encargado' => $request->encargado,
                ]);

                foreach ($usuariosReflectivos as $usuarioReflectivo) {
                    broadcast(new OperarioRecibosActualizados(
                        userId: (int) $usuarioReflectivo->id,
                        payload: [
                            'area' => 'Costura',
                            'accion' => 'recibo_asignado_reflectivo',
                            'numero_pedido' => (int) $pedido->id,
                            'prenda_id' => (int) $request->prenda_id,
                            'proceso_id' => (int) $nuevoProceso->id,
                            'tipo_recibo' => (string) $recibo->tipo_recibo,
                            'numero_recibo' => (int) $recibo->consecutivo_actual,
                            'encargado' => (string) $request->encargado,
                            'mensaje' => "El recibo #{$recibo->consecutivo_actual} de REFLECTIVO fue asignado a {$request->encargado}",
                        ]
                    ));
                    
                    Log::info('[COSTURA] Broadcast REFLECTIVO enviado a usuario', [
                        'user_id' => $usuarioReflectivo->id,
                        'user_name' => $usuarioReflectivo->name,
                    ]);
                }
            }
            
            // Para recibos COSTURA: notificar a lider-reflectivo SOLO si el encargado tiene rol costura-reflectivo
            if ($tipoReciboUpper === 'COSTURA' || $tipoReciboUpper === 'COSTURA-BODEGA') {
                // Verificar si el encargado asignado tiene rol costura-reflectivo
                $encargadoUsuario = User::query()
                    ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower(trim($request->encargado))])
                    ->first();
                
                $notificarLideres = $encargadoUsuario && $encargadoUsuario->hasRole('costura-reflectivo');
                
                if ($notificarLideres) {
                    $usuariosLiderReflectivo = User::all()->filter(function($user) {
                        return $user->hasRole('lider-reflectivo');
                    });

                    Log::info('[COSTURA] Broadcast COSTURA a lider-reflectivo (encargado es costura-reflectivo)', [
                        'total_usuarios' => $usuariosLiderReflectivo->count(),
                        'usuario_ids' => $usuariosLiderReflectivo->pluck('id')->toArray(),
                        'recibo' => $recibo->consecutivo_actual,
                        'encargado' => $request->encargado,
                        'encargado_tiene_rol_costura_reflectivo' => true,
                    ]);

                    foreach ($usuariosLiderReflectivo as $usuarioLider) {
                        broadcast(new OperarioRecibosActualizados(
                            userId: (int) $usuarioLider->id,
                            payload: [
                                'area' => 'Costura',
                                'accion' => 'recibo_asignado_costura',
                                'numero_pedido' => (int) $pedido->id,
                                'prenda_id' => (int) $request->prenda_id,
                                'proceso_id' => (int) $nuevoProceso->id,
                                'tipo_recibo' => (string) $recibo->tipo_recibo,
                                'numero_recibo' => (int) $recibo->consecutivo_actual,
                                'encargado' => (string) $request->encargado,
                                'mensaje' => "El recibo #{$recibo->consecutivo_actual} de COSTURA fue asignado a {$request->encargado}",
                            ]
                        ));
                        
                        Log::info('[COSTURA] Broadcast COSTURA enviado a lider-reflectivo', [
                            'user_id' => $usuarioLider->id,
                            'user_name' => $usuarioLider->name,
                        ]);
                    }
                } else {
                    Log::info('[COSTURA] NO se notifica a lider-reflectivo (encargado no tiene rol costura-reflectivo)', [
                        'recibo' => $recibo->consecutivo_actual,
                        'encargado' => $request->encargado,
                        'encargado_existe' => !empty($encargadoUsuario),
                        'encargado_tiene_rol_costura_reflectivo' => false,
                    ]);
                }
            }

            Log::info('Recibo enviado a Costura', [
                'pedido_id' => $pedidoId,
                'numero_pedido' => $pedido->numero_pedido,
                'prenda_id' => $request->prenda_id,
                'numero_recibo' => $recibo->consecutivo_actual,
                'proceso_id' => $nuevoProceso->id,
                'encargado' => $request->encargado,
                'usuario_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Recibo enviado a Costura correctamente',
                'data' => [
                    'proceso_id' => $nuevoProceso->id,
                    'proceso_nombre' => 'Costura',
                    'encargado' => $request->encargado,
                    'area_anterior' => $areaAnterior
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

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

            $pedido = PedidoProduccion::findOrFail($pedidoId);

            // Obtener la prenda para tener el nombre
            $prenda = Prenda::find($prendaId); // Usar el parámetro de ruta, no del request

            Log::info('[DESHACER-COSTURA] Buscando recibo', [
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
                'prenda_id' => $prendaId, // Usar el parámetro de ruta
                'tipo_recibo' => $request->tipo_recibo,
                'prenda_encontrada' => $prenda ? true : false
            ]);

            // Buscar recibo en área Corte o sin área específica
            $recibo = ConsecutivoReciboPedido::where('pedido_produccion_id', $pedido->id)
                ->where('prenda_id', $prendaId) // Usar el parámetro de ruta
                ->whereRaw('UPPER(tipo_recibo) = ?', [strtoupper($request->tipo_recibo)])
                ->where('activo', 1)
                ->first();

            Log::info('[DESHACER-COSTURA] Resultado búsqueda recibo', [
                'recibo_encontrado' => $recibo ? true : false,
                'recibo_id' => $recibo?->id,
                'recibo_numero' => $recibo?->consecutivo_actual,
                'recibo_area' => $recibo?->area
            ]);

            if (!$recibo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Recibo no encontrado o no está en Costura'
                ], 404);
            }

            DB::beginTransaction();

            // Buscar el proceso de Costura más reciente
            $procesoCostura = ProcesoPrenda::where('prenda_pedido_id', $prendaId)
                ->where('numero_pedido', $pedido->numero_pedido)
                ->where('proceso', 'Costura')
                ->where('numero_recibo', $recibo->consecutivo_actual)
                ->whereNull('deleted_at')
                ->latest('fecha_inicio')
                ->first();

            if (!$procesoCostura) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró proceso de Costura para limpiar encargado'
                ], 404);
            }

            // Limpiar solo el encargado del proceso (NO eliminar el proceso)
            $procesoCostura->update([
                'encargado' => null,
                'estado_proceso' => 'Pendiente' // Cambiar estado a Pendiente al quitar encargado
            ]);

            // NO restaurar área - mantener el recibo en Costura para que siga visible en vista-costura
            // El recibo permanecerá en área Costura pero sin encargado asignado

            DB::commit();

            Log::info('Encargado de Costura limpiado', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'proceso_id' => $procesoCostura->id,
                'area_mantenida' => 'Costura',
                'usuario_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Encargado de Costura eliminado correctamente',
                'data' => [
                    'area_nueva' => 'Costura', // Mantener area Costura
                    'proceso_anterior' => 'Costura'
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

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
