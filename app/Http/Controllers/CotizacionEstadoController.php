<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

            // Actualizar estado a APROBADA_CONTADOR
            $cotizacion->update([
                'estado' => 'APROBADA_CONTADOR',
                'fecha_aprobada_contador' => now()
            ]);

            Log::info('Cotización aprobada por contador', [
                'cotizacion_id' => $cotizacion->id,
                'nuevo_estado' => 'APROBADA_CONTADOR',
                'estado_anterior' => $cotizacion->getOriginal('estado')
            ]);

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

            $estadoAnterior = $cotizacion->estado;

            // Actualizar estado a APROBADA_POR_APROBADOR
            $cotizacion->update([
                'estado' => 'APROBADA_POR_APROBADOR'
            ]);

            Log::info('Cotización aprobada por aprobador', [
                'cotizacion_id' => $cotizacion->id,
                'estado_anterior' => $estadoAnterior,
                'nuevo_estado' => 'APROBADA_POR_APROBADOR'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cotización aprobada por aprobador exitosamente',
                'cotizacion' => $cotizacion
            ]);

        } catch (\Exception $e) {
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

            // Actualizar estado a ENVIADA
            $cotizacion->update([
                'estado' => 'ENVIADA'
            ]);

            Log::info('Cotización enviada exitosamente', [
                'cotizacion_id' => $cotizacion->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cotización enviada exitosamente',
                'cotizacion' => $cotizacion
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
                'usuario_id' => auth()->id()
            ]);

            // Validar que la cotización esté en estado ENVIADO A APROBADOR
            if ($cotizacion->estado !== 'ENVIADO A APROBADOR') {
                return response()->json([
                    'success' => false,
                    'message' => 'La cotización no puede ser rechazada desde su estado actual: ' . $cotizacion->estado
                ], 422);
            }

            // Obtener observaciones
            $observaciones = $request->input('observaciones', '');

            // Actualizar estado a EN_CORRECCION
            $cotizacion->update([
                'estado' => 'EN_CORRECCION'
            ]);

            Log::info('Cotización enviada a corrección', [
                'cotizacion_id' => $cotizacion->id,
                'nuevo_estado' => 'EN_CORRECCION',
                'observaciones' => $observaciones
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cotización reenviada a la asesora con observaciones',
                'cotizacion' => $cotizacion
            ]);

        } catch (\Exception $e) {
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
}
