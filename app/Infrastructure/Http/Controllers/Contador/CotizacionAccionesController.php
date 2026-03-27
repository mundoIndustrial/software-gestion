<?php

namespace App\Infrastructure\Http\Controllers\Contador;

use App\Events\CotizacionEstadoCambiado;
use App\Http\Controllers\Controller;
use App\Models\Cotizacion;
use App\Services\ImagenCotizacionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

final class CotizacionAccionesController extends Controller
{
    public function destroy(int $id): JsonResponse
    {
        try {
            $cotizacion = Cotizacion::findOrFail($id);

            if ($cotizacion->prendasCotizaciones()->exists()) {
                $cotizacion->prendasCotizaciones()->delete();
            }

            if ($cotizacion->logoCotizacion()->exists()) {
                $cotizacion->logoCotizacion()->delete();
            }

            if ($cotizacion->pedidosProduccion()->exists()) {
                $cotizacion->pedidosProduccion()->delete();
            }

            if ($cotizacion->historial()->exists()) {
                $cotizacion->historial()->delete();
            }

            $imagenService = new ImagenCotizacionService();
            $imagenService->eliminarTodasLasImagenes($id);

            if (Storage::disk('public')->exists("cotizaciones/{$id}")) {
                Storage::disk('public')->deleteDirectory("cotizaciones/{$id}");
            }

            $cotizacion->delete();

            return response()->json([
                'success' => true,
                'message' => 'Cotizacion, imagenes y registros relacionados eliminados correctamente',
            ]);
        } catch (\Throwable $e) {
            Log::error('Error al eliminar cotizacion', [
                'cotizacion_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la cotizacion: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function cambiarEstado(int $id, Request $request): JsonResponse
    {
        try {
            $cotizacion = Cotizacion::findOrFail($id);

            $request->validate([
                'estado' => 'required|in:ENVIADA_CONTADOR,APROBADA_COTIZACIONES,FINALIZADA',
            ]);

            $estadoAnterior = (string) $cotizacion->estado;
            $cotizacion->estado = (string) $request->input('estado');
            $cotizacion->save();

            broadcast(new CotizacionEstadoCambiado(
                $cotizacion->id,
                $cotizacion->estado,
                $estadoAnterior,
                $cotizacion->asesor_id,
                $cotizacion->toArray()
            ))->toOthers();

            return response()->json([
                'success' => true,
                'message' => 'Estado actualizado correctamente',
                'estado' => $cotizacion->estado,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar el estado: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function cotizacionesPendientesCount(): JsonResponse
    {
        try {
            $count = Cotizacion::where('estado', 'ENVIADA_CONTADOR')
                ->where(function ($q) {
                    $q->whereNull('tipo_cotizacion_id')
                        ->orWhereHas('tipoCotizacion', function ($tq) {
                            $tq->where('codigo', '!=', 'EPP');
                        });
                })
                ->count();

            return response()->json([
                'success' => true,
                'count' => $count,
                'message' => $count > 0
                    ? "Hay {$count} cotizacion(es) pendiente(s) por revisar"
                    : 'No hay cotizaciones pendientes',
            ]);
        } catch (\Throwable $e) {
            Log::error('Error al obtener contador de cotizaciones pendientes', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'count' => 0,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

