<?php

namespace App\Infrastructure\Http\Controllers\Operario;

use App\Application\Operario\UseCases\ActualizarNovedadReciboUseCase;
use App\Application\Operario\UseCases\CrearNovedadReciboUseCase;
use App\Application\Operario\UseCases\EliminarNovedadReciboUseCase;
use App\Application\Operario\UseCases\ObtenerNovedadesPrendaUseCase;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class OperarioNovedadesController extends Controller
{
    public function __construct(
        private CrearNovedadReciboUseCase $crearNovedadReciboUseCase,
        private ObtenerNovedadesPrendaUseCase $obtenerNovedadesPrendaUseCase,
        private EliminarNovedadReciboUseCase $eliminarNovedadReciboUseCase,
        private ActualizarNovedadReciboUseCase $actualizarNovedadReciboUseCase,
    ) {}

    /**
     * Obtener novedades existentes de un pedido.
     */
    public function obtenerNovedades($numeroPedido)
    {
        try {
            $proceso = \App\Models\ProcesoPrenda::where('numero_pedido', $numeroPedido)->first();
            $novedades = $proceso?->novedades ?? '';

            return response()->json([
                'success' => true,
                'novedades' => $novedades
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener novedades: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Crear novedad de prenda/recibo.
     */
    public function crearNovedad(Request $request)
    {
        try {
            $request->validate([
                'numero_pedido' => 'required|numeric',
                'prenda_id' => 'required|numeric',
                'numero_recibo' => 'required|string',
                'novedad_texto' => 'required|string|min:5',
                'tipo_novedad' => 'required|in:observacion,problema,cambio,correccion,aprobacion,rechazo'
            ]);

            $result = $this->crearNovedadReciboUseCase->execute($request);

            return response()->json([
                'success' => (bool) ($result['success'] ?? false),
                'message' => (string) ($result['message'] ?? ''),
            ], (int) ($result['status'] ?? 200));
        } catch (\Exception $e) {
            \Log::error('[OperarioNovedadesController] Error creando novedad: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al crear novedad: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Obtener novedades de una prenda.
     */
    public function obtenerNovedadesPrenda($numeroPedido, $prendaId)
    {
        try {
            $result = $this->obtenerNovedadesPrendaUseCase->execute((int) $prendaId);

            return response()->json([
                'success' => (bool) ($result['success'] ?? false),
                'novedades' => $result['novedades'] ?? [],
            ], (int) ($result['status'] ?? 200));
        } catch (\Exception $e) {
            \Log::error('[OperarioNovedadesController] Error obteniendo novedades: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener novedades'
            ], 500);
        }
    }

    /**
     * API: Eliminar novedad.
     */
    public function eliminarNovedad($id)
    {
        try {
            $result = $this->eliminarNovedadReciboUseCase->execute((int) $id);

            return response()->json([
                'success' => (bool) ($result['success'] ?? false),
                'message' => (string) ($result['message'] ?? ''),
            ], (int) ($result['status'] ?? 200));
        } catch (\Exception $e) {
            \Log::error('[OperarioNovedadesController] Error eliminando novedad: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar novedad'
            ], 500);
        }
    }

    /**
     * API: Actualizar novedad.
     */
    public function actualizarNovedad(Request $request, $id)
    {
        try {
            $request->validate([
                'novedad_texto' => 'required|string|min:5',
                'tipo_novedad' => 'required|in:observacion,problema,cambio,correccion,aprobacion,rechazo'
            ]);

            $result = $this->actualizarNovedadReciboUseCase->execute($request, (int) $id);

            return response()->json([
                'success' => (bool) ($result['success'] ?? false),
                'message' => (string) ($result['message'] ?? ''),
            ], (int) ($result['status'] ?? 200));
        } catch (\Exception $e) {
            \Log::error('[OperarioNovedadesController] Error actualizando novedad: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar novedad: ' . $e->getMessage()
            ], 500);
        }
    }
}
