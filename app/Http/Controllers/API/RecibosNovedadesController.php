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
            
            $prendas = $pedido->prendas;
            foreach ($prendas as $prenda) {
                if ($prenda->novedadesRecibo) {
                    foreach ($prenda->novedadesRecibo as $novedad) {
                        $novedades[] = [
                            'id' => $novedad->id,
                            'prenda_id' => $prenda->id,
                            'prenda_nombre' => $prenda->nombre_prenda,
                            'numero_recibo' => $novedad->numero_recibo,
                            'novedad_texto' => $novedad->novedad_texto,
                            'tipo_novedad' => $novedad->tipo_novedad,
                            'estado_novedad' => $novedad->estado_novedad,
                            'notas_adicionales' => $novedad->notas_adicionales,
                            'creado_por' => $novedad->creado_por, // ID del usuario, no el nombre
                            'creado_por_nombre' => $novedad->creadoPor ? $novedad->creadoPor->name : null, // Nombre para mostrar
                            'creado_por_rol' => $novedad->creadoPor && $novedad->creadoPor->role ? $novedad->creadoPor->role->name : null, // Rol del usuario
                            'creado_en' => \Carbon\Carbon::parse($novedad->creado_en)->format('d/m/Y H:i'),
                            'editado' => $novedad->editado,
                            'editado_en' => $novedad->editado_en ? \Carbon\Carbon::parse($novedad->editado_en)->format('d/m/Y H:i') : null,
                            'editado_por' => $novedad->editado_por,
                            'editado_por_nombre' => $novedad->editadoPor ? $novedad->editadoPor->name : null,
                            'resuelto_por' => $novedad->resueltoPor ? $novedad->resueltoPor->name : null,
                            'fecha_resolucion' => $novedad->fecha_resolucion ? \Carbon\Carbon::parse($novedad->fecha_resolucion)->format('d/m/Y H:i') : null,
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
