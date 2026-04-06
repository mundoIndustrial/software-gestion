<?php

namespace App\Infrastructure\Http\Controllers\Despacho;

use App\Application\Services\Asesores\ObservacionesDespachoApplicationService;
use App\Events\ObservacionDespachoCreada;
use App\Http\Controllers\Controller;
use App\Models\BodegaNota;
use App\Models\PedidoObservacionesDespacho;
use App\Models\PedidoProduccion;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DespachoObservacionesController extends Controller
{
    public function __construct(
        private readonly ObservacionesDespachoApplicationService $service,
    ) {
    }

    public function resumenObservaciones(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pedido_ids' => 'required|array',
            'pedido_ids.*' => 'integer',
        ]);

        $pedidoIds = $validated['pedido_ids'];

        $resumenDespacho = PedidoObservacionesDespacho::query()
            ->whereIn('pedido_produccion_id', $pedidoIds)
            ->selectRaw('pedido_produccion_id, COUNT(*) as total')
            ->groupBy('pedido_produccion_id')
            ->pluck('total', 'pedido_produccion_id')
            ->toArray();

        $resumenBodega = BodegaNota::query()
            ->whereIn('pedido_produccion_id', $pedidoIds)
            ->selectRaw('pedido_produccion_id, COUNT(*) as total')
            ->groupBy('pedido_produccion_id')
            ->pluck('total', 'pedido_produccion_id')
            ->toArray();

        $resultado = [];
        foreach ($pedidoIds as $pedidoId) {
            $total = (int) ($resumenDespacho[$pedidoId] ?? 0) + (int) ($resumenBodega[$pedidoId] ?? 0);
            $resultado[$pedidoId] = [
                'unread' => $total,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $resultado,
        ]);
    }

    public function marcarLeidas(Request $request, PedidoProduccion $pedido): JsonResponse
    {
        $usuario = auth()->user();
        $usuarioId = $usuario?->id;

        PedidoObservacionesDespacho::query()
            ->where('pedido_produccion_id', $pedido->id)
            ->whereNull('visto_at')
            ->where(function ($q) use ($usuarioId) {
                $q->whereNull('usuario_id')
                    ->orWhere('usuario_id', '!=', $usuarioId);
            })
            ->update(['visto_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Observaciones marcadas como leidas',
        ]);
    }

    public function obtenerObservaciones(PedidoProduccion $pedido): JsonResponse
    {
        $observaciones = $this->service->obtenerObservacionesUnificadas((int) $pedido->id);
        
        // Agregar el campo de novedades del pedido si existe
        if ($pedido->novedades) {
            // Separar múltiples novedades por salto doble
            $cambios = array_filter(
                array_map('trim', explode("\n\n", $pedido->novedades)),
                fn($c) => !empty($c)
            );
            
            foreach ($cambios as $cambio) {
                $cambio = trim($cambio);
                if (empty($cambio)) {
                    continue;
                }
                
                // Parsear nombre del asesor y fecha del mensaje
                $nombreAsesor = null;
                $rol = 'Asesor';
                $fecha = $pedido->created_at;
                $contenidoLimpio = $cambio;
                
                // Intenta parsear formato: "Rol-Nombre-FechaHora - Contenido"
                if (preg_match('/^([^-]+)-([^-]+)-(.+?)\s*-\s+(.+)$/is', $cambio, $matches)) {
                    $rol = trim($matches[1]);
                    $nombreAsesor = trim($matches[2]);
                    $fechaTexto = trim($matches[3] ?? '');
                    $contenidoLimpio = trim($matches[4]);
                    
                    // Intentar parsear la fecha
                    if (!empty($fechaTexto)) {
                        try {
                            $fecha = Carbon::createFromFormat('d/m/Y, g:i:s a', $fechaTexto);
                            if (!$fecha) {
                                $fecha = Carbon::createFromFormat('d/m/Y g:i a', $fechaTexto);
                            }
                            if (!$fecha) {
                                $fecha = Carbon::createFromFormat('d/m/Y H:i:s', $fechaTexto);
                            }
                            if (!$fecha) {
                                $fecha = $pedido->created_at;
                            }
                        } catch (\Exception $e) {
                            $fecha = $pedido->created_at;
                        }
                    }
                } elseif (preg_match('/^(.+?)\n\(([^)]+)\)$/', $cambio, $matches)) {
                    // Formato nuevo con información de asesor en paréntesis al final
                    $contenidoLimpio = trim($matches[1]);
                    $datosAsesor = trim($matches[2]);
                    
                    // Parsear "Asesor - fecha" o "Asesor (Rol) - fecha"
                    if (preg_match('/^(.+?)\s*(?:\(([^)]+)\))?\s*-\s*(.+)$/', $datosAsesor, $asesoresMatches)) {
                        $nombreAsesor = trim($asesoresMatches[1]);
                        $rol = !empty($asesoresMatches[2]) ? trim($asesoresMatches[2]) : 'Asesor';
                        $fechaTexto = trim($asesoresMatches[3]);
                        
                        // Intentar parsear la fecha
                        try {
                            $fecha = Carbon::createFromFormat('d/m/Y, g:i:s a', $fechaTexto);
                            if (!$fecha) {
                                $fecha = Carbon::createFromFormat('d/m/Y H:i:s', $fechaTexto);
                            }
                            if (!$fecha) {
                                $fecha = $pedido->created_at;
                            }
                        } catch (\Exception $e) {
                            $fecha = $pedido->created_at;
                        }
                    }
                } else {
                    // Formato antiguo sin información de asesor - buscar en auditoría
                    $nombreAsesor = $this->buscarUsuarioDeAuditoriaEpp($pedido->id);
                }
                
                // Si todavía no tiene nombre, usar "Sistema"
                if (!$nombreAsesor) {
                    $nombreAsesor = 'Sistema';
                }
                
                // Convertir fecha a ISO 8601
                $fechaIso = $fecha;
                if ($fecha instanceof Carbon) {
                    $fechaIso = $fecha->toIso8601String();
                } elseif (is_object($fecha)) {
                    $fechaIso = $fecha->format('c');
                }
                
                $novedadItem = [
                    'source' => 'pedido',
                    'id' => 'pedido-novedad-' . md5($cambio),
                    'contenido' => $contenidoLimpio,
                    'usuario_nombre' => $nombreAsesor,
                    'usuario_rol' => $rol,
                    'created_at' => $fechaIso,
                    'updated_at' => $fechaIso,
                ];
                
                array_unshift($observaciones, $novedadItem);
            }
        }
        
        return response()->json([
            'success' => true,
            'data' => $observaciones,
        ]);
    }

    /**
     * Busca en la auditoría el usuario que hizo cambios de EPP para un pedido
     */
    private function buscarUsuarioDeAuditoriaEpp(int $pedidoId): ?string
    {
        try {
            // Buscar cambios de EPP en auditoría
            $cambio = DB::table('pedidos_auditoria')
                ->where('pedidos_produccion_id', $pedidoId)
                ->where(function ($query) {
                    $query->where('tipo_cambio', 'like', '%EPP%')
                        ->orWhere('detalles', 'like', '%EPP%');
                })
                ->latest('created_at')
                ->first();
            
            if ($cambio && $cambio->usuario_id) {
                // Obtener nombre del usuario desde tabla users
                $usuario = DB::table('users')->where('id', $cambio->usuario_id)->first();
                if ($usuario) {
                    return $usuario->name;
                }
            }
        } catch (\Exception $e) {
            // Si hay error, simplemente retorna null
        }
        
        return null;
    }

    public function guardarObservacion(Request $request, PedidoProduccion $pedido): JsonResponse
    {
        $validated = $request->validate([
            'contenido' => 'required|string|max:5000',
        ]);

        $row = $this->service->guardar(
            (int) $pedido->id,
            (string) $validated['contenido'],
            auth()->user(),
            $request->ip(),
        );

        broadcast(new ObservacionDespachoCreada($row, 'created'))->toOthers();

        return response()->json([
            'success' => true,
            'message' => 'Observacion guardada exitosamente',
            'data' => $this->service->mapPayload($row),
        ]);
    }

    public function actualizarObservacion(Request $request, PedidoProduccion $pedido, string $observacionId): JsonResponse
    {
        $validated = $request->validate([
            'contenido' => 'required|string|max:5000',
        ]);

        try {
            $row = $this->service->actualizar(
                (int) $pedido->id,
                $observacionId,
                (string) $validated['contenido'],
                auth()->user(),
            );
        } catch (NotFoundHttpException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Observacion no encontrada',
            ], 404);
        } catch (AccessDeniedHttpException $e) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para editar esta observacion',
            ], 403);
        }

        broadcast(new ObservacionDespachoCreada($row, 'updated'))->toOthers();

        return response()->json([
            'success' => true,
            'message' => 'Observacion actualizada correctamente',
            'data' => $this->service->mapPayload($row),
        ]);
    }

    public function eliminarObservacion(Request $request, PedidoProduccion $pedido, string $observacionId): JsonResponse
    {
        try {
            $row = $this->service->eliminar((int) $pedido->id, $observacionId, auth()->user());
        } catch (NotFoundHttpException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Observacion no encontrada',
            ], 404);
        } catch (AccessDeniedHttpException $e) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para eliminar esta observacion',
            ], 403);
        }

        broadcast(new ObservacionDespachoCreada($row, 'deleted'))->toOthers();

        return response()->json([
            'success' => true,
            'message' => 'Observacion eliminada correctamente',
        ]);
    }

    /**
     * Marcar observaciones de un pedido como vistas (para badges)
     */
    public function marcarObservacionesComoVistas($pedidoId)
    {
        try {
            $updated = DB::table('pedido_observaciones_despacho')
                ->where('pedido_produccion_id', $pedidoId)
                ->whereNull('visto_at')
                ->update(['visto_at' => now()]);

            \Log::info('[DespachoController] Observaciones marcadas como vistas', [
                'pedido_id' => $pedidoId,
                'updated_count' => $updated,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Observaciones marcadas como vistas',
                'updated_count' => $updated,
            ]);
        } catch (\Exception $e) {
            \Log::error('[DespachoController] Error marcando observaciones como vistas', [
                'pedido_id' => $pedidoId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al marcar observaciones como vistas',
            ], 500);
        }
    }
}
