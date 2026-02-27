<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PrendaPedidoNovedadRecibo;
use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\ConsecutivoReciboPedido;
use App\Models\ProcesoPrenda;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecibosNovedadesController extends Controller
{
    /**
     * Obtener novedades de prendas para un recibo específico
     */
    public function index(Request $request, $pedidoId, $numeroRecibo)
    {
        try {
            $pedido = PedidoProduccion::findOrFail($pedidoId);
            
            // Buscar la prenda específica del recibo
            $recibo = \App\Models\ConsecutivoReciboPedido::where('pedido_produccion_id', $pedido->id)
                ->where('consecutivo_actual', $numeroRecibo)
                ->where('activo', 1)
                ->first();
            
            // Obtener novedades filtradas por numero_recibo y prenda del recibo
            $query = PrendaPedidoNovedadRecibo::where('numero_recibo', $numeroRecibo);
            
            if ($recibo && $recibo->prenda_id) {
                $query->where('prenda_pedido_id', $recibo->prenda_id);
            } else {
                // Fallback: buscar para cualquier prenda del pedido
                $prendasIds = $pedido->prendas->pluck('id')->toArray();
                $query->whereIn('prenda_pedido_id', $prendasIds);
            }
            
            $novedadesDb = $query->orderBy('creado_en', 'desc')->get();
            
            $novedades = [];
            foreach ($novedadesDb as $novedad) {
                $prenda = $novedad->prendaPedido;
                $novedades[] = [
                    'id' => $novedad->id,
                    'prenda_id' => $novedad->prenda_pedido_id,
                    'prenda_nombre' => $prenda ? $prenda->nombre_prenda : null,
                    'numero_recibo' => $novedad->numero_recibo,
                    'novedad_texto' => $novedad->novedad_texto,
                    'tipo_novedad' => $novedad->tipo_novedad,
                    'estado_novedad' => $novedad->estado_novedad,
                    'notas_adicionales' => $novedad->notas_adicionales,
                    'creado_por' => $novedad->creado_por,
                    'creado_por_nombre' => $novedad->creadoPor ? $novedad->creadoPor->name : null,
                    'creado_por_rol' => $novedad->creadoPor && $novedad->creadoPor->role ? $novedad->creadoPor->role->name : null,
                    'creado_en' => \Carbon\Carbon::parse($novedad->creado_en)->format('d/m/Y H:i'),
                    'editado' => $novedad->editado,
                    'editado_en' => $novedad->editado_en ? \Carbon\Carbon::parse($novedad->editado_en)->format('d/m/Y H:i') : null,
                    'editado_por' => $novedad->editado_por,
                    'editado_por_nombre' => $novedad->editadoPor ? $novedad->editadoPor->name : null,
                    'resuelto_por' => $novedad->resueltoPor ? $novedad->resueltoPor->name : null,
                    'fecha_resolucion' => $novedad->fecha_resolucion ? \Carbon\Carbon::parse($novedad->fecha_resolucion)->format('d/m/Y H:i') : null,
                ];
            }
            
            return response()->json([
                'success' => true,
                'data' => $novedades,
                'pedido_id' => $pedidoId,
                'numero_recibo' => $numeroRecibo,
                'total' => count($novedades)
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error obteniendo novedades de recibo', [
                'pedido_id' => $pedidoId,
                'numero_recibo' => $numeroRecibo,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las novedades: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Guardar novedades para prendas de un recibo
     */
    public function store(Request $request, $pedidoId, $numeroRecibo)
    {
        try {
            $request->validate([
                'novedades' => 'required|string|max:5000',
                'tipo_novedad' => 'required|in:observacion,problema,cambio,aprobacion,rechazo,correccion',
                'prendas_ids' => 'nullable|array',
                'prendas_ids.*' => 'integer|exists:prendas_pedido,id'
            ]);
            
            $pedido = PedidoProduccion::findOrFail($pedidoId);
            $usuarioId = Auth::id();
            
            // Si no se especifican prendas, buscar la prenda del recibo específico
            $prendasIds = $request->prendas_ids;
            if (empty($prendasIds)) {
                // Buscar el consecutivo de recibo para obtener la prenda específica
                $recibo = \App\Models\ConsecutivoReciboPedido::where('pedido_produccion_id', $pedido->id)
                    ->where('consecutivo_actual', $numeroRecibo)
                    ->where('activo', 1)
                    ->first();
                
                if ($recibo && $recibo->prenda_id) {
                    // Usar solo la prenda asociada al recibo
                    $prendasIds = [$recibo->prenda_id];
                } else {
                    // Fallback: usar la primera prenda del pedido (solo 1)
                    $primeraPrenda = $pedido->prendas->first();
                    $prendasIds = $primeraPrenda ? [$primeraPrenda->id] : [];
                }
            }
            
            DB::beginTransaction();
            
            $novedadesCreadas = [];
            
            foreach ($prendasIds as $prendaId) {
                $novedad = PrendaPedidoNovedadRecibo::create([
                    'prenda_pedido_id' => $prendaId,
                    'numero_recibo' => $numeroRecibo,
                    'novedad_texto' => $request->novedades,
                    'tipo_novedad' => $request->tipo_novedad,
                    'creado_por' => $usuarioId,
                    'estado_novedad' => PrendaPedidoNovedadRecibo::ESTADO_ACTIVA,
                ]);
                
                $novedadesCreadas[] = $novedad;
            }
            
            DB::commit();
            
            Log::info('Novedades de recibo creadas', [
                'pedido_id' => $pedidoId,
                'numero_recibo' => $numeroRecibo,
                'prendas_ids' => $prendasIds,
                'usuario_id' => $usuarioId,
                'cantidad' => count($novedadesCreadas)
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Novedades guardadas correctamente',
                'data' => [
                    'novedades_creadas' => count($novedadesCreadas),
                    'prendas_afectadas' => $prendasIds
                ]
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error guardando novedades de recibo', [
                'pedido_id' => $pedidoId,
                'numero_recibo' => $numeroRecibo,
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar las novedades: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Actualizar una novedad existente
     */
    public function update(Request $request, $novedadId)
    {
        try {
            // Validación más flexible para edición
            $request->validate([
                'novedad_texto' => 'required|string|max:5000',
                'tipo_novedad' => 'nullable|in:observacion,problema,cambio,aprobacion,rechazo,correccion',
                'estado_novedad' => 'nullable|in:activa,resuelta,pendiente',
                'notas_adicionales' => 'nullable|string|max:2000'
            ]);
            
            $novedad = PrendaPedidoNovedadRecibo::findOrFail($novedadId);
            
            // Solo el autor puede editar
            if ($novedad->creado_por !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para editar esta novedad'
                ], 403);
            }
            
            $updateData = [
                'novedad_texto' => $request->novedad_texto,
                'editado' => true,
                'editado_en' => now(),
                'editado_por' => Auth::id(),
            ];
            
            // Campos opcionales
            if ($request->has('tipo_novedad')) {
                $updateData['tipo_novedad'] = $request->tipo_novedad;
            }
            if ($request->has('estado_novedad')) {
                $updateData['estado_novedad'] = $request->estado_novedad;
            }
            if ($request->has('notas_adicionales')) {
                $updateData['notas_adicionales'] = $request->notas_adicionales;
            }
            
            $novedad->update($updateData);
            
            Log::info('Novedad de recibo actualizada', [
                'novedad_id' => $novedadId,
                'usuario_id' => Auth::id(),
                'texto_actualizado' => $request->novedad_texto
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Novedad actualizada correctamente',
                'data' => $novedad->fresh(['creadoPor', 'resueltoPor'])
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación: ' . $e->getMessage(),
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            Log::error('Error actualizando novedad de recibo', [
                'novedad_id' => $novedadId,
                'usuario_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la novedad: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Eliminar una novedad
     */
    public function destroy($novedadId)
    {
        try {
            $novedad = PrendaPedidoNovedadRecibo::findOrFail($novedadId);
            
            $novedad->delete();
            
            Log::info('Novedad de recibo eliminada', [
                'novedad_id' => $novedadId,
                'usuario_id' => Auth::id(),
                'prenda_pedido_id' => $novedad->prenda_pedido_id,
                'numero_recibo' => $novedad->numero_recibo
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Novedad eliminada correctamente'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error eliminando novedad de recibo', [
                'novedad_id' => $novedadId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la novedad: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Cambiar el área de un recibo a Control Calidad
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

            // Buscar el recibo - primero intentar con prenda_id
            $recibo = ConsecutivoReciboPedido::where('pedido_produccion_id', $pedido->id)
                ->where('prenda_id', $request->prenda_id)
                ->whereRaw('UPPER(tipo_recibo) = ?', [strtoupper($request->tipo_recibo)])
                ->where('activo', 1)
                ->first();

            // Si no se encuentra con prenda_id, buscar por consecutivo_actual
            if (!$recibo) {
                $recibo = ConsecutivoReciboPedido::where('pedido_produccion_id', $pedido->id)
                    ->where('consecutivo_actual', $numeroRecibo)
                    ->whereRaw('UPPER(tipo_recibo) = ?', [strtoupper($request->tipo_recibo)])
                    ->where('activo', 1)
                    ->first();
            }

            // Último intento: buscar solo por pedido y tipo 
            if (!$recibo) {
                $recibo = ConsecutivoReciboPedido::where('pedido_produccion_id', $pedido->id)
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

            DB::commit();

            Log::info('Recibo enviado a Control Calidad', [
                'pedido_id' => $pedidoId,
                'numero_pedido' => $pedido->numero_pedido,
                'prenda_id' => $request->prenda_id,
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
                'proceso_id' => 'required|integer|exists:procesos_prenda,id'
            ]);

            $pedido = PedidoProduccion::findOrFail($pedidoId);
            
            // Buscar el recibo
            $recibo = ConsecutivoReciboPedido::where('pedido_produccion_id', $pedido->id)
                ->where('prenda_id', $prendaId)
                ->where('area', 'Control Calidad')
                ->where('activo', 1)
                ->first();

            if (!$recibo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Recibo no encontrado o no está en Control Calidad'
                ], 404);
            }

            DB::beginTransaction();

            // Obtener el proceso de Control Calidad a eliminar
            $procesoCC = ProcesoPrenda::findOrFail($request->proceso_id);
            
            // Buscar el proceso anterior más reciente (usando numero_pedido, NO id)
            $procesoPosterior = ProcesoPrenda::where('prenda_pedido_id', $prendaId)
                ->where('numero_pedido', $pedido->numero_pedido)
                ->where('proceso', '!=', 'Control de Calidad')
                ->whereNull('deleted_at')
                ->latest('fecha_inicio')
                ->first();

            // Actualizar solo el área del recibo al proceso anterior
            $areaAnterior = $procesoPosterior ? $procesoPosterior->proceso : 'Costura';

            $recibo->update([
                'area' => $areaAnterior
            ]);

            // Eliminar el proceso de Control Calidad
            $procesoCC->delete();

            DB::commit();

            Log::info('Proceso de Control Calidad deshecho', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'proceso_id' => $request->proceso_id,
                'area_anterior' => $areaAnterior,
                'usuario_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Control Calidad deshecho correctamente',
                'data' => [
                    'area_nueva' => $areaAnterior,
                    'proceso_anterior' => $procesoPosterior ? $procesoPosterior->proceso : null
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error deshaciendo Control Calidad', [
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
     * Obtener texto consolidado de novedades para mostrar en la tabla
     */
    public function getConsolidado($pedidoId, $numeroRecibo)
    {
        try {
            $pedido = PedidoProduccion::findOrFail($pedidoId);
            
            $novedadesTexto = '';
            
            if ($pedido->prendas && $pedido->prendas->count() > 0) {
                $novedadesArray = [];
                
                foreach ($pedido->prendas as $prenda) {
                    $novedadesPrenda = $prenda->novedadesRecibo()
                        ->where('numero_recibo', $numeroRecibo)
                        ->where('estado_novedad', PrendaPedidoNovedadRecibo::ESTADO_ACTIVA)
                        ->orderBy('creado_en', 'desc')
                        ->pluck('novedad_texto')
                        ->toArray();
                    
                    $novedadesArray = array_merge($novedadesArray, $novedadesPrenda);
                }
                
                if (!empty($novedadesArray)) {
                    $novedadesTexto = implode("\n", $novedadesArray);
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'novedades_texto' => $novedadesTexto,
                    'total_novedades' => count($novedadesArray ?? [])
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error obteniendo novedades consolidadas', [
                'pedido_id' => $pedidoId,
                'numero_recibo' => $numeroRecibo,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las novedades: ' . $e->getMessage()
            ], 500);
        }
    }
}