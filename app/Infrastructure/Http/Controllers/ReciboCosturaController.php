<?php

namespace App\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ConsecutivoReciboPedido;
use App\Models\PedidoProduccion;
use App\Models\ProcesoPrenda;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReciboCosturaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
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

            // Eliminar el proceso de Control de Calidad
            $procesoCC->delete();

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
}
