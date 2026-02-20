<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PrendaPedidoNovedadRecibo;
use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
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
            
            // Obtener todas las prendas del pedido y sus novedades para este recibo
            $novedades = [];
            
            if ($pedido->prendas && $pedido->prendas->count() > 0) {
                foreach ($pedido->prendas as $prenda) {
                    $novedadesPrenda = $prenda->novedadesRecibo()
                        ->where('numero_recibo', $numeroRecibo)
                        ->with(['creadoPor', 'resueltoPor'])
                        ->orderBy('creado_en', 'desc')
                        ->get();
                    
                    foreach ($novedadesPrenda as $novedad) {
                        $novedades[] = [
                            'id' => $novedad->id,
                            'prenda_id' => $prenda->id,
                            'prenda_nombre' => $prenda->nombre_prenda,
                            'numero_recibo' => $novedad->numero_recibo,
                            'novedad_texto' => $novedad->novedad_texto,
                            'tipo_novedad' => $novedad->tipo_novedad,
                            'estado_novedad' => $novedad->estado_novedad,
                            'notas_adicionales' => $novedad->notas_adicionales,
                            'creado_por' => $novedad->creadoPor ? $novedad->creadoPor->name : null,
                            'creado_en' => $novedad->creado_en->format('d/m/Y H:i'),
                            'resuelto_por' => $novedad->resueltoPor ? $novedad->resueltoPor->name : null,
                            'fecha_resolucion' => $novedad->fecha_resolucion ? $novedad->fecha_resolucion->format('d/m/Y H:i') : null,
                        ];
                    }
                }
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
            
            // Si no se especifican prendas, aplicar a todas las prendas del pedido
            $prendasIds = $request->prendas_ids;
            if (empty($prendasIds)) {
                $prendasIds = $pedido->prendas->pluck('id')->toArray();
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
            $request->validate([
                'novedad_texto' => 'required|string|max:5000',
                'tipo_novedad' => 'required|in:observacion,problema,cambio,aprobacion,rechazo,correccion',
                'estado_novedad' => 'required|in:activa,resuelta,pendiente',
                'notas_adicionales' => 'nullable|string|max:2000'
            ]);
            
            $novedad = PrendaPedidoNovedadRecibo::findOrFail($novedadId);
            
            $novedad->update([
                'novedad_texto' => $request->novedad_texto,
                'tipo_novedad' => $request->tipo_novedad,
                'estado_novedad' => $request->estado_novedad,
                'notas_adicionales' => $request->notas_adicionales,
            ]);
            
            // Si se marca como resuelta, registrar quién y cuándo
            if ($request->estado_novedad === PrendaPedidoNovedadRecibo::ESTADO_RESUELTA && !$novedad->resuelto_por) {
                $novedad->update([
                    'fecha_resolucion' => now(),
                    'resuelto_por' => Auth::id()
                ]);
            }
            
            Log::info('Novedad de recibo actualizada', [
                'novedad_id' => $novedadId,
                'usuario_id' => Auth::id(),
                'estado_anterior' => $novedad->getOriginal('estado_novedad'),
                'estado_nuevo' => $request->estado_novedad
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Novedad actualizada correctamente',
                'data' => $novedad->fresh(['creadoPor', 'resueltoPor'])
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error actualizando novedad de recibo', [
                'novedad_id' => $novedadId,
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
