<?php

namespace App\Infrastructure\Http\Controllers\Cotizaciones;

use App\Events\CotizacionEstadoCambiado;
use App\Http\Controllers\Controller;
use App\Models\Cotizacion;
use App\Models\CotizacionAprobacion;
use App\Models\Role;
use App\Models\User;
use App\Services\CotizacionEstadoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class CotizacionEstadoController extends Controller
{
    public function __construct(
        private readonly CotizacionEstadoService $estadoService
    ) {
    }

    public function aprobarContador(Cotizacion $cotizacion)
    {
        try {
            $estadosValidos = ['ENVIADA_CONTADOR', 'ENVIADO A CONTADOR', 'PENDIENTE', 'ENVIADA', 'ENVIADO A APROBADOR', 'EN_CORRECCION'];
            if (!in_array($cotizacion->estado, $estadosValidos, true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'La cotizacion no puede ser aprobada desde su estado actual: ' . $cotizacion->estado,
                ], 422);
            }

            if ($cotizacion->estado === 'APROBADA_CONTADOR') {
                return response()->json([
                    'success' => true,
                    'message' => 'La cotizacion ya ha sido aprobada',
                    'cotizacion' => $cotizacion,
                ]);
            }

            $estadoAnterior = $cotizacion->estado;
            $cotizacion->update([
                'estado' => 'APROBADA_CONTADOR',
                'fecha_aprobada_contador' => now(),
            ]);

            try {
                $cotizacion->loadMissing(['cliente', 'asesor']);
                broadcast(new CotizacionEstadoCambiado(
                    $cotizacion->id,
                    'APROBADA_CONTADOR',
                    $estadoAnterior,
                    $cotizacion->asesor_id,
                    $cotizacion->toArray()
                ));
            } catch (\Exception $e) {
                Log::warning('No se pudo emitir broadcast CotizacionEstadoCambiado (aprobarContador)', [
                    'cotizacion_id' => $cotizacion->id,
                    'error' => $e->getMessage(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Cotizacion aprobada exitosamente',
                'cotizacion' => $cotizacion,
            ]);
        } catch (\Exception $e) {
            Log::error('Error al aprobar cotizacion', [
                'cotizacion_id' => $cotizacion->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al aprobar la cotizacion: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function aprobarAprobador(Cotizacion $cotizacion)
    {
        try {
            $estadosValidos = ['APROBADA', 'APROBADA_CONTADOR', 'APROBADA_POR_APROBADOR', 'ENVIADO A APROBADOR', 'ENVIADA A APROBADOR'];
            if (!in_array($cotizacion->estado, $estadosValidos, true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'La cotizacion no esta en estado valido para aprobar. Estado actual: ' . $cotizacion->estado,
                ], 422);
            }

            $yaAprobo = CotizacionAprobacion::where('cotizacion_id', $cotizacion->id)
                ->where('usuario_id', auth()->id())
                ->exists();

            if ($yaAprobo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya has aprobado esta cotizacion',
                ], 422);
            }

            DB::beginTransaction();

            CotizacionAprobacion::create([
                'cotizacion_id' => $cotizacion->id,
                'usuario_id' => auth()->id(),
                'fecha_aprobacion' => now(),
            ]);

            $rolAprobador = Role::where('name', 'aprobador_cotizaciones')->first();
            $totalAprobadores = $rolAprobador
                ? User::whereJsonContains('roles_ids', $rolAprobador->id)->count()
                : 0;

            $aprobacionesActuales = CotizacionAprobacion::where('cotizacion_id', $cotizacion->id)->count();
            $estadoAnterior = $cotizacion->estado;
            $mensaje = '';

            if ($aprobacionesActuales >= $totalAprobadores) {
                $cotizacion->update(['estado' => 'APROBADA_POR_APROBADOR']);
                $mensaje = 'Cotizacion aprobada completamente. Todos los aprobadores han dado su visto bueno.';

                try {
                    $cotizacion->loadMissing(['cliente', 'asesor']);
                    broadcast(new CotizacionEstadoCambiado(
                        $cotizacion->id,
                        'APROBADA_POR_APROBADOR',
                        $estadoAnterior,
                        $cotizacion->asesor_id,
                        $cotizacion->toArray()
                    ));
                } catch (\Exception $e) {
                    Log::warning('No se pudo emitir broadcast CotizacionEstadoCambiado (aprobarAprobador)', [
                        'cotizacion_id' => $cotizacion->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            } else {
                $mensaje = 'Tu aprobacion ha sido registrada. Faltan '
                    . ($totalAprobadores - $aprobacionesActuales) . ' aprobacion(es) mas.';
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $mensaje,
                'cotizacion' => $cotizacion->fresh(),
                'aprobaciones_actuales' => $aprobacionesActuales,
                'total_aprobadores' => $totalAprobadores,
                'aprobacion_completa' => $aprobacionesActuales >= $totalAprobadores,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al confirmar la cotizacion: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function enviar(Cotizacion $cotizacion)
    {
        try {
            if ($cotizacion->estado !== 'BORRADOR') {
                return response()->json([
                    'success' => false,
                    'message' => 'La cotizacion no esta en estado borrador',
                ], 422);
            }

            $this->estadoService->enviarACOntador($cotizacion);

            return response()->json([
                'success' => true,
                'message' => 'Cotizacion enviada exitosamente',
                'cotizacion' => $cotizacion->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar la cotizacion: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function rechazar(Cotizacion $cotizacion, Request $request)
    {
        try {
            $estadoAnterior = $cotizacion->estado;
            $estadosValidos = ['ENVIADO A APROBADOR', 'APROBADA_CONTADOR'];
            if (!in_array($cotizacion->estado, $estadosValidos, true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'La cotizacion no puede ser rechazada desde su estado actual: ' . $cotizacion->estado,
                ], 422);
            }

            DB::beginTransaction();

            $observaciones = $request->input('observaciones', '');
            if (CotizacionAprobacion::where('cotizacion_id', $cotizacion->id)->exists()) {
                CotizacionAprobacion::where('cotizacion_id', $cotizacion->id)->delete();
            }

            $cotizacion->update([
                'estado' => 'EN_CORRECCION',
                'novedades' => $observaciones,
            ]);

            DB::commit();

            try {
                $cotizacion->loadMissing(['cliente', 'asesor']);
                broadcast(new CotizacionEstadoCambiado(
                    $cotizacion->id,
                    'EN_CORRECCION',
                    $estadoAnterior,
                    $cotizacion->asesor_id,
                    $cotizacion->toArray()
                ));
            } catch (\Exception $e) {
                Log::warning('No se pudo emitir broadcast CotizacionEstadoCambiado (rechazar)', [
                    'cotizacion_id' => $cotizacion->id,
                    'error' => $e->getMessage(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Cotizacion enviada al contador para correccion',
                'cotizacion' => $cotizacion->fresh(),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al rechazar la cotizacion: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function aprobarParaPedido(Cotizacion $cotizacion)
    {
        try {
            if ($cotizacion->estado !== 'APROBADA_POR_APROBADOR') {
                return response()->json([
                    'success' => false,
                    'message' => 'La cotizacion debe estar en estado APROBADA_POR_APROBADOR. Estado actual: ' . $cotizacion->estado,
                ], 422);
            }

            $estadoAnterior = $cotizacion->estado;
            $cotizacion->update([
                'estado' => 'APROBADO_PARA_PEDIDO',
                'fecha_aprobada_para_pedido' => now(),
            ]);

            broadcast(new CotizacionEstadoCambiado(
                $cotizacion->id,
                'APROBADO_PARA_PEDIDO',
                $estadoAnterior,
                $cotizacion->asesor_id,
                $cotizacion->toArray()
            ))->toOthers();

            return response()->json([
                'success' => true,
                'message' => 'Cotizacion aprobada para pedido exitosamente',
                'cotizacion' => $cotizacion->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al aprobar la cotizacion para pedido: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function historial(Cotizacion $cotizacion)
    {
        try {
            $historial = $this->estadoService->obtenerHistorial($cotizacion);

            return response()->json([
                'success' => true,
                'data' => $historial->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'estado_anterior' => $item->estado_anterior ?? null,
                        'estado_nuevo' => $item->estado_nuevo ?? null,
                        'usuario_nombre' => $item->usuario_nombre ?? null,
                        'rol_usuario' => $item->rol_usuario ?? null,
                        'razon_cambio' => $item->razon_cambio ?? null,
                        'fecha' => $item->created_at?->format('Y-m-d H:i:s'),
                        'datos_adicionales' => $item->datos_adicionales ?? null,
                    ];
                }),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function seguimiento(Cotizacion $cotizacion)
    {
        try {
            $historial = $this->estadoService->obtenerHistorial($cotizacion);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $cotizacion->id,
                    'numero_cotizacion' => $cotizacion->numero_cotizacion,
                    'estado' => $cotizacion->estado,
                    'fecha_creacion' => $cotizacion->created_at?->format('Y-m-d H:i:s'),
                    'fecha_envio' => $cotizacion->fecha_envio,
                    'historial' => $historial->map(function ($item) {
                        return [
                            'estado_anterior' => $item->estado_anterior ?? null,
                            'estado_nuevo' => $item->estado_nuevo ?? null,
                            'usuario_nombre' => $item->usuario_nombre ?? null,
                            'fecha' => $item->created_at?->format('Y-m-d H:i:s'),
                        ];
                    }),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}

