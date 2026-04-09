<?php

namespace App\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Application\UseCases\RecibosNovedades\ObtenerNovedadesReciboUseCase;
use App\Application\UseCases\RecibosNovedades\GuardarNovedadesReciboUseCase;
use App\Application\UseCases\RecibosNovedades\ActualizarNovedadReciboUseCase;
use App\Application\UseCases\RecibosNovedades\EliminarNovedadReciboUseCase;
use App\Application\UseCases\RecibosNovedades\ObtenerConsolidadoNovedadesUseCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RecibosNovedadesController extends Controller
{
    public function __construct(
        private readonly ObtenerNovedadesReciboUseCase $obtenerNovedadesUseCase,
        private readonly GuardarNovedadesReciboUseCase $guardarNovedadesUseCase,
        private readonly ActualizarNovedadReciboUseCase $actualizarNovedadUseCase,
        private readonly EliminarNovedadReciboUseCase $eliminarNovedadUseCase,
        private readonly ObtenerConsolidadoNovedadesUseCase $obtenerConsolidadoUseCase,
    ) {}

    /**
     * Obtener novedades de prendas para un recibo específico
     * Para revisor_entregas: filtra solo novedades creadas por usuarios con rol 'vista-costura'
     * Para otros roles: muestra todas las novedades
     */
    public function index(Request $request, $pedidoId, $numeroRecibo)
    {
        try {
            $novedadesDb = $this->obtenerNovedadesUseCase->execute($pedidoId, $numeroRecibo);
            
            // Solo aplicar filtro si el usuario es revisor_entregas
            $usuarioActual = auth()->user();
            $esRevisorEntregas = $usuarioActual && $usuarioActual->hasRole('revisor_entregas');
            
            Log::info('[RecibosNovedadesController] Debug filtro novedades', [
                'usuario_id' => $usuarioActual?->id,
                'usuario_rol' => $usuarioActual?->role?->name,
                'es_revisor_entregas' => $esRevisorEntregas,
                'novedades_totales' => count($novedadesDb)
            ]);
            
            $novedades = [];
            foreach ($novedadesDb as $novedad) {
                // Si es revisor_entregas, filtrar solo novedades del rol 'vista-costura'
                if ($esRevisorEntregas) {
                    $rolUsuario = $novedad->creadoPor && $novedad->creadoPor->role ? $novedad->creadoPor->role->name : null;
                    if ($rolUsuario !== 'vista-costura') {
                        continue;
                    }
                }
                
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
            
            $result = $this->guardarNovedadesUseCase->execute(
                $pedidoId,
                $numeroRecibo,
                $request->novedades,
                $request->tipo_novedad,
                auth()->id(),
                $request->prendas_ids
            );
            
            Log::info('Novedades de recibo creadas', [
                'pedido_id' => $pedidoId,
                'numero_recibo' => $numeroRecibo,
                'cantidad' => $result['novedades_creadas']
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Novedades guardadas correctamente',
                'data' => $result
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error guardando novedades de recibo', [
                'pedido_id' => $pedidoId,
                'numero_recibo' => $numeroRecibo,
                'error' => $e->getMessage()
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
                'tipo_novedad' => 'nullable|in:observacion,problema,cambio,aprobacion,rechazo,correccion',
                'estado_novedad' => 'nullable|in:activa,resuelta,pendiente',
                'notas_adicionales' => 'nullable|string|max:2000'
            ]);
            
            $novedad = $this->actualizarNovedadUseCase->execute(
                $novedadId,
                $request->novedad_texto,
                $request->tipo_novedad,
                $request->estado_novedad,
                $request->notas_adicionales
            );
            
            Log::info('Novedad de recibo actualizada', [
                'novedad_id' => $novedadId,
                'usuario_id' => auth()->id()
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
            ], $e->getCode() === 403 ? 403 : 500);
        }
    }
    
    /**
     * Eliminar una novedad
     */
    public function destroy($novedadId)
    {
        try {
            $this->eliminarNovedadUseCase->execute($novedadId);
            
            Log::info('Novedad de recibo eliminada', [
                'novedad_id' => $novedadId,
                'usuario_id' => auth()->id()
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
            $data = $this->obtenerConsolidadoUseCase->execute($pedidoId, $numeroRecibo);

            return response()->json([
                'success' => true,
                'data' => $data
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