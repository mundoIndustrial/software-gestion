<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use App\Services\CotizacionEstadoService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CotizacionEstadoController extends Controller
{
    public function __construct(
        private CotizacionEstadoService $estadoService
    ) {}

    /**
     * Enviar cotización a contador
     * BORRADOR → ENVIADA_CONTADOR
     * POST /cotizaciones/{id}/enviar
     */
    public function enviar(Request $request, Cotizacion $cotizacion): JsonResponse
    {
        try {
            // Validar que sea la asesor dueña de la cotización
            if ($cotizacion->user_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para enviar esta cotización'
                ], 403);
            }

            $this->estadoService->enviarACOntador($cotizacion);

            return response()->json([
                'success' => true,
                'message' => 'Cotización enviada a contador exitosamente',
                'cotizacion' => [
                    'id' => $cotizacion->id,
                    'estado' => $cotizacion->estado,
                    'numero_cotizacion' => $cotizacion->numero_cotizacion,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Aprobar cotización como contador
     * ENVIADA_CONTADOR → APROBADA_CONTADOR (asigna número vía Job)
     * EN_CORRECCION → APROBADA_CONTADOR (re-envía al aprobador)
     * POST /cotizaciones/{id}/aprobar-contador
     */
    public function aprobarContador(Request $request, Cotizacion $cotizacion): JsonResponse
    {
        try {
            $user = auth()->user();
            
            // Debug: Retornar información del usuario y permisos
            \Log::info('🔍 Debug Contador Aprobar:', [
                'user_id' => $user?->id,
                'user_name' => $user?->name,
                'roles_ids' => $user?->roles_ids,
                'roles' => $user?->roles()->pluck('name')->toArray(),
                'hasRole_contador' => $user?->hasRole('contador'),
                'hasRole_Contador' => $user?->hasRole('Contador'),
            ]);
            
            // Validar que sea Contador (sin usar authorize)
            if (!$user || (!$user->hasRole('contador') && !$user->hasRole('admin'))) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para aprobar cotizaciones'
                ], 403);
            }

            // Si la cotización está en EN_CORRECCION (fue enviada por aprobador para corregir)
            // Cambiarla a APROBADA_CONTADOR para enviarla de vuelta al aprobador
            if ($cotizacion->estado === 'EN_CORRECCION') {
                $this->estadoService->aprobarCotizacionCorregida($cotizacion);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Cotización corregida enviada al aprobador para revisión final',
                    'cotizacion' => [
                        'id' => $cotizacion->id,
                        'estado' => $cotizacion->estado,
                    ]
                ]);
            }

            // Flujo normal: ENVIADA_CONTADOR → APROBADA_CONTADOR
            $this->estadoService->aprobarComoContador($cotizacion);

            return response()->json([
                'success' => true,
                'message' => 'Cotización aprobada por contador. Se está asignando número...',
                'cotizacion' => [
                    'id' => $cotizacion->id,
                    'estado' => $cotizacion->estado,
                    'numero_cotizacion' => $cotizacion->numero_cotizacion,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Aprobar cotización como aprobador de cotizaciones
     * APROBADA_CONTADOR → APROBADA_COTIZACIONES
     * POST /cotizaciones/{id}/aprobar-aprobador
     */
    public function aprobarAprobador(Request $request, Cotizacion $cotizacion): JsonResponse
    {
        try {
            $user = auth()->user();
            
            // Validar que sea Aprobador_Cotizaciones
            if (!$user || (!$user->hasRole('aprobador_cotizaciones') && !$user->hasRole('admin'))) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para aprobar cotizaciones'
                ], 403);
            }

            $this->estadoService->aprobarComoAprobador($cotizacion);

            return response()->json([
                'success' => true,
                'message' => 'Cotización aprobada. Ya puede crear su pedido.',
                'cotizacion' => [
                    'id' => $cotizacion->id,
                    'estado' => $cotizacion->estado,
                    'numero_cotizacion' => $cotizacion->numero_cotizacion,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Obtener historial de cambios de estado
     * GET /cotizaciones/{id}/historial
     */
    public function historial(Cotizacion $cotizacion): JsonResponse
    {
        try {
            $historial = $this->estadoService->obtenerHistorial($cotizacion);

            return response()->json([
                'success' => true,
                'data' => $historial->map(fn($cambio) => [
                    'id' => $cambio->id,
                    'estado_anterior' => $cambio->estado_anterior,
                    'estado_nuevo' => $cambio->estado_nuevo,
                    'usuario_nombre' => $cambio->usuario_nombre,
                    'rol_usuario' => $cambio->rol_usuario,
                    'razon_cambio' => $cambio->razon_cambio,
                    'fecha' => $cambio->created_at->format('d/m/Y h:i A'),
                    'datos_adicionales' => $cambio->datos_adicionales,
                ])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Obtener seguimiento de cotización (para Asesor)
     * GET /cotizaciones/{id}/seguimiento
     */
    public function seguimiento(Request $request, Cotizacion $cotizacion): JsonResponse
    {
        try {
            // Validar que sea la asesor dueña o un rol permitido
            if ($cotizacion->user_id !== $request->user()->id && !$request->user()->hasRole(['contador', 'aprobador_cotizaciones', 'admin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para ver el seguimiento'
                ], 403);
            }

            $estadoEnum = \App\Enums\EstadoCotizacion::tryFrom($cotizacion->estado);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $cotizacion->id,
                    'numero_cotizacion' => $cotizacion->numero_cotizacion,
                    'cliente' => $cotizacion->cliente,
                    'estado' => $cotizacion->estado,
                    'estado_label' => $estadoEnum?->label() ?? 'Desconocido',
                    'estado_color' => $estadoEnum?->color() ?? 'gray',
                    'estado_icono' => $estadoEnum?->icon() ?? 'question',
                    'fecha_envio' => $cotizacion->fecha_envio?->format('d/m/Y h:i A'),
                    'aprobada_por_contador_en' => $cotizacion->aprobada_por_contador_en?->format('d/m/Y h:i A'),
                    'aprobada_por_aprobador_en' => $cotizacion->aprobada_por_aprobador_en?->format('d/m/Y h:i A'),
                    'historial' => $this->estadoService->obtenerHistorial($cotizacion)->map(fn($cambio) => [
                        'estado_anterior' => $cambio->estado_anterior,
                        'estado_nuevo' => $cambio->estado_nuevo,
                        'usuario_nombre' => $cambio->usuario_nombre,
                        'fecha' => $cambio->created_at->format('d/m/Y h:i A'),
                    ])
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
