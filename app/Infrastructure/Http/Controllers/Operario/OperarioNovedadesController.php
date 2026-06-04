<?php

namespace App\Infrastructure\Http\Controllers\Operario;

use App\Application\Operario\UseCases\ActualizarNovedadReciboUseCase;
use App\Application\Operario\UseCases\CrearNovedadReciboUseCase;
use App\Application\Operario\UseCases\EliminarNovedadReciboUseCase;
use App\Application\Operario\UseCases\ObtenerNovedadesPrendaUseCase;
use App\Http\Controllers\Controller;
use App\Models\NovedadEntrega;
use App\Models\User;
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
     * API: Obtener novedades de bodega.
     * GET /operario/api/novedades/bodega/{reciboId}/{prendaBodegaId}
     */
    public function obtenerNovedadesBodega($reciboId, $prendaBodegaId)
    {
        try {
            $novedades = NovedadEntrega::query()
                ->where('consecutivo_recibo_id', (int) $reciboId)
                ->where('prenda_bodega_id', (int) $prendaBodegaId)
                ->orderByDesc('created_at')
                ->get()
                ->map(function (NovedadEntrega $novedad) {
                    $usuario = User::find((int) $novedad->usuario_id);

                    return [
                        'id' => $novedad->id,
                        'observaciones' => (string) ($novedad->observaciones ?? ''),
                        'tipo_novedad' => 'observacion',
                        'created_at' => optional($novedad->created_at)->format('d/m/Y H:i'),
                        'creado_en' => optional($novedad->created_at)->format('d/m/Y H:i'),
                        'usuario_nombre' => $usuario?->name ?? 'Usuario Desconocido',
                        'usuario_rol' => $usuario?->getRoleNames()->first() ? strtoupper((string) $usuario->getRoleNames()->first()) : 'USUARIO',
                        'es_mia' => auth()->check() && (int) $novedad->usuario_id === (int) auth()->id(),
                    ];
                })
                ->values()
                ->all();

            return response()->json([
                'success' => true,
                'novedades' => $novedades,
            ]);
        } catch (\Exception $e) {
            \Log::error('[OperarioNovedadesController] Error obteniendo novedades de bodega: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener novedades de bodega',
            ], 500);
        }
    }

    /**
     * API: Crear novedad de bodega.
     * POST /operario/api/novedades/bodega/crear
     */
    public function crearNovedadBodega(Request $request)
    {
        try {
            $request->validate([
                'recibo_id' => 'required|numeric',
                'prenda_bodega_id' => 'required|numeric',
                'observaciones' => 'required|string|min:5',
            ]);

            $novedad = NovedadEntrega::create([
                'consecutivo_recibo_id' => (int) $request->recibo_id,
                'prenda_bodega_id' => (int) $request->prenda_bodega_id,
                'usuario_id' => (int) auth()->id(),
                'encargado' => (string) (auth()->user()->name ?? 'Sistema'),
                'observaciones' => (string) $request->observaciones,
                'area' => 'Costura',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Novedad registrada correctamente',
                'data' => [
                    'id' => $novedad->id,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('[OperarioNovedadesController] Error creando novedad de bodega: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al crear novedad de bodega',
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
