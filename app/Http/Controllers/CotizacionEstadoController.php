<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use App\Models\CotizacionAprobacion;
use App\Models\User;
use App\Models\Role;
use App\Events\CotizacionEstadoCambiado;
use App\Services\CotizacionEstadoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CotizacionEstadoController extends Controller
{
    /**
     * Aprobar cotización desde el contador
     */
    public function aprobarContador(Cotizacion $cotizacion)
    {
        try {
            Log::info('Aprobando cotización desde contador', [
                'cotizacion_id' => $cotizacion->id,
                'estado_actual' => $cotizacion->estado,
                'usuario_id' => auth()->id()
            ]);

            // Validar que la cotización esté en estado válido
            $estadosValidos = ['ENVIADA_CONTADOR', 'ENVIADO A CONTADOR', 'PENDIENTE', 'ENVIADA', 'ENVIADO A APROBADOR', 'EN_CORRECCION'];
            if (!in_array($cotizacion->estado, $estadosValidos)) {
                return response()->json([
                    'success' => false,
                    'message' => 'La cotización no puede ser aprobada desde su estado actual: ' . $cotizacion->estado
                ], 422);
            }
            
            // Si ya está aprobada, no hacer nada
            if ($cotizacion->estado === 'APROBADA_CONTADOR') {
                return response()->json([
                    'success' => true,
                    'message' => 'La cotización ya ha sido aprobada',
                    'cotizacion' => $cotizacion
                ]);
            }

            $estadoAnterior = $cotizacion->estado;

            // Actualizar estado a APROBADA_CONTADOR
            $cotizacion->update([
                'estado' => 'APROBADA_CONTADOR',
                'fecha_aprobada_contador' => now()
            ]);

            Log::info('Cotización aprobada por contador', [
                'cotizacion_id' => $cotizacion->id,
                'nuevo_estado' => 'APROBADA_CONTADOR',
                'estado_anterior' => $estadoAnterior
            ]);

            // Broadcast realtime para que desaparezca de "Pendientes" en Contador sin recargar
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
                'message' => 'Cotización aprobada exitosamente',
                'cotizacion' => $cotizacion
            ]);

        } catch (\Exception $e) {
            Log::error('Error al aprobar cotización', [
                'cotizacion_id' => $cotizacion->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al aprobar la cotización: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Aprobar cotización desde el aprobador
     */
    public function aprobarAprobador(Cotizacion $cotizacion)
    {
        try {
            Log::info('Aprobando cotización desde aprobador', [
                'cotizacion_id' => $cotizacion->id,
                'estado_actual' => $cotizacion->estado,
                'usuario_id' => auth()->id()
            ]);

            // Validar que la cotización esté en estados válidos para aprobar
            $estadosValidos = ['APROBADA', 'APROBADA_CONTADOR', 'APROBADA_POR_APROBADOR', 'ENVIADO A APROBADOR', 'ENVIADA A APROBADOR'];
            if (!in_array($cotizacion->estado, $estadosValidos)) {
                return response()->json([
                    'success' => false,
                    'message' => 'La cotización no está en estado válido para aprobar. Estado actual: ' . $cotizacion->estado
                ], 422);
            }

            // Verificar si el usuario ya aprobó esta cotización
            $yaAprobo = CotizacionAprobacion::where('cotizacion_id', $cotizacion->id)
                ->where('usuario_id', auth()->id())
                ->exists();

            if ($yaAprobo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya has aprobado esta cotización'
                ], 422);
            }

            DB::beginTransaction();

            // Registrar la aprobación del usuario actual
            CotizacionAprobacion::create([
                'cotizacion_id' => $cotizacion->id,
                'usuario_id' => auth()->id(),
                'fecha_aprobacion' => now()
            ]);

            // Contar cuántos usuarios tienen el rol aprobador_cotizaciones
            $rolAprobador = Role::where('name', 'aprobador_cotizaciones')->first();
            $totalAprobadores = $rolAprobador 
                ? User::whereJsonContains('roles_ids', $rolAprobador->id)->count()
                : 0;

            // Contar cuántas aprobaciones tiene esta cotización
            $aprobacionesActuales = CotizacionAprobacion::where('cotizacion_id', $cotizacion->id)->count();

            $estadoAnterior = $cotizacion->estado;
            $mensaje = '';

            // Si todos los aprobadores han aprobado, cambiar estado
            if ($aprobacionesActuales >= $totalAprobadores) {
                $cotizacion->update([
                    'estado' => 'APROBADA_POR_APROBADOR'
                ]);

                $mensaje = 'Cotización aprobada completamente. Todos los aprobadores han dado su visto bueno.';

                // Broadcast realtime para actualizar módulo Contador (Aprobadas) sin recargar
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

                Log::info('Cotización aprobada por todos los aprobadores', [
                    'cotizacion_id' => $cotizacion->id,
                    'estado_anterior' => $estadoAnterior,
                    'nuevo_estado' => 'APROBADA_POR_APROBADOR',
                    'total_aprobadores' => $totalAprobadores,
                    'aprobaciones' => $aprobacionesActuales
                ]);
            } else {
                $mensaje = "Tu aprobación ha sido registrada. Faltan " . ($totalAprobadores - $aprobacionesActuales) . " aprobación(es) más.";

                Log::info('Aprobación individual registrada', [
                    'cotizacion_id' => $cotizacion->id,
                    'usuario_id' => auth()->id(),
                    'aprobaciones_actuales' => $aprobacionesActuales,
                    'total_aprobadores' => $totalAprobadores,
                    'faltan' => $totalAprobadores - $aprobacionesActuales
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $mensaje,
                'cotizacion' => $cotizacion->fresh(),
                'aprobaciones_actuales' => $aprobacionesActuales,
                'total_aprobadores' => $totalAprobadores,
                'aprobacion_completa' => $aprobacionesActuales >= $totalAprobadores
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error al confirmar cotización', [
                'cotizacion_id' => $cotizacion->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al confirmar la cotización: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enviar cotización
     */
    public function enviar(Cotizacion $cotizacion)
    {
        try {
            Log::info('Enviando cotización', [
                'cotizacion_id' => $cotizacion->id,
                'usuario_id' => auth()->id()
            ]);

            // Validar que la cotización esté en estado BORRADOR
            if ($cotizacion->estado !== 'BORRADOR') {
                return response()->json([
                    'success' => false,
                    'message' => 'La cotización no está en estado borrador'
                ], 422);
            }

            // Enviar a contador (BORRADOR -> ENVIADA_CONTADOR) y emitir realtime
            app(CotizacionEstadoService::class)->enviarACOntador($cotizacion);

            Log::info('Cotización enviada exitosamente', [
                'cotizacion_id' => $cotizacion->id,
                'nuevo_estado' => $cotizacion->fresh()->estado,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cotización enviada exitosamente',
                'cotizacion' => $cotizacion->fresh()
            ]);

        } catch (\Exception $e) {
            Log::error('Error al enviar cotización', [
                'cotizacion_id' => $cotizacion->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al enviar la cotización: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rechazar cotización y enviar a corrección
     */
    public function rechazar(Cotizacion $cotizacion, Request $request)
    {
        try {
            Log::info('Rechazando cotización y enviando a corrección', [
                'cotizacion_id' => $cotizacion->id,
                'usuario_id' => auth()->id(),
                'estado_actual' => $cotizacion->estado
            ]);

            $estadoAnterior = $cotizacion->estado;

            // Validar que la cotización esté en estados válidos para rechazar
            $estadosValidos = ['ENVIADO A APROBADOR', 'APROBADA_CONTADOR'];
            if (!in_array($cotizacion->estado, $estadosValidos)) {
                return response()->json([
                    'success' => false,
                    'message' => 'La cotización no puede ser rechazada desde su estado actual: ' . $cotizacion->estado
                ], 422);
            }

            DB::beginTransaction();

            // Obtener observaciones
            $observaciones = $request->input('observaciones', '');

            // Si había aprobaciones pendientes, eliminarlas (un rechazo anula las aprobaciones)
            $aprobacionesEliminadas = CotizacionAprobacion::where('cotizacion_id', $cotizacion->id)->count();
            if ($aprobacionesEliminadas > 0) {
                CotizacionAprobacion::where('cotizacion_id', $cotizacion->id)->delete();
                
                Log::info('Aprobaciones eliminadas por rechazo', [
                    'cotizacion_id' => $cotizacion->id,
                    'cantidad_eliminadas' => $aprobacionesEliminadas
                ]);
            }

            // Actualizar estado a EN_CORRECCION y guardar observaciones en novedades
            $cotizacion->update([
                'estado' => 'EN_CORRECCION',
                'novedades' => $observaciones
            ]);

            DB::commit();

            Log::info('Cotización enviada a corrección', [
                'cotizacion_id' => $cotizacion->id,
                'nuevo_estado' => 'EN_CORRECCION',
                'observaciones' => $observaciones,
                'aprobaciones_eliminadas' => $aprobacionesEliminadas
            ]);

            // Broadcast realtime para que aparezca en /contador/por-revisar sin recargar
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
                'message' => 'Cotización enviada al contador para corrección',
                'cotizacion' => $cotizacion->fresh()
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error al rechazar cotización', [
                'cotizacion_id' => $cotizacion->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al rechazar la cotización: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Aprobar cotización final desde contador (APROBADA_POR_APROBADOR -> APROBADO_PARA_PEDIDO)
     * Solo se puede aprobar si la cotización está en estado APROBADA_POR_APROBADOR
     */
    public function aprobarParaPedido(Cotizacion $cotizacion)
    {
        try {
            Log::info('Aprobando cotización para pedido desde contador', [
                'cotizacion_id' => $cotizacion->id,
                'estado_actual' => $cotizacion->estado,
                'usuario_id' => auth()->id()
            ]);

            // Validar que la cotización esté en estado APROBADA_POR_APROBADOR
            if ($cotizacion->estado !== 'APROBADA_POR_APROBADOR') {
                return response()->json([
                    'success' => false,
                    'message' => 'La cotización debe estar en estado APROBADA_POR_APROBADOR para poder aprobarla para pedido. Estado actual: ' . $cotizacion->estado
                ], 422);
            }

            // Guardar estado anterior para el evento
            $estadoAnterior = $cotizacion->estado;

            // Actualizar estado a APROBADO_PARA_PEDIDO
            $cotizacion->update([
                'estado' => 'APROBADO_PARA_PEDIDO',
                'fecha_aprobada_para_pedido' => now()
            ]);

            Log::info('Cotización aprobada para pedido', [
                'cotizacion_id' => $cotizacion->id,
                'nuevo_estado' => 'APROBADO_PARA_PEDIDO',
                'estado_anterior' => $estadoAnterior
            ]);

            // Disparar evento de broadcast en tiempo real
            broadcast(new CotizacionEstadoCambiado(
                $cotizacion->id,
                'APROBADO_PARA_PEDIDO',
                $estadoAnterior,
                $cotizacion->asesor_id,
                $cotizacion->toArray()
            ))->toOthers();

            return response()->json([
                'success' => true,
                'message' => 'Cotización aprobada para pedido exitosamente',
                'cotizacion' => $cotizacion->fresh()
            ]);

        } catch (\Exception $e) {
            Log::error('Error al aprobar cotización para pedido', [
                'cotizacion_id' => $cotizacion->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al aprobar la cotización para pedido: ' . $e->getMessage()
            ], 500);
        }
    }
}
