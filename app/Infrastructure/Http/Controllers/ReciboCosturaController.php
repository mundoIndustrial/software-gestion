<?php

namespace App\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Application\Operario\DTOs\CambiarAreaControlCalidadCommandDTO;
use App\Application\Operario\DTOs\DeshacerControlCalidadCommandDTO;
use App\Application\Operario\DTOs\DeshacerCosturaCommandDTO;
use App\Application\Operario\DTOs\LimpiarEncargadoCosturaCommandDTO;
use App\Application\Operario\DTOs\PasarACosturaCommandDTO;
use App\Application\Operario\UseCases\CambiarAreaControlCalidadUseCase;
use App\Application\Operario\UseCases\DeshacerControlCalidadUseCase;
use App\Application\Operario\UseCases\DeshacerCosturaUseCase;
use App\Application\Operario\UseCases\LimpiarEncargadoCosturaUseCase;
use App\Application\Operario\UseCases\PasarACosturaUseCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReciboCosturaController extends Controller
{
    public function __construct(
        private readonly CambiarAreaControlCalidadUseCase $cambiarAreaControlCalidadUseCase,
        private readonly DeshacerControlCalidadUseCase    $deshacerControlCalidadUseCase,
        private readonly PasarACosturaUseCase             $pasarACosturaUseCase,
        private readonly DeshacerCosturaUseCase           $deshacerCosturaUseCase,
        private readonly LimpiarEncargadoCosturaUseCase    $limpiarEncargadoCosturaUseCase,
    ) {
        $this->middleware('auth');
    }

    public function limpiarEncargadoCostura(Request $request, $pedidoId, $prendaId)
    {
        try {
            if (!auth()->user()->hasRole('vista-costura')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para realizar esta acción'
                ], 403);
            }

            $request->validate([
                'tipo_recibo' => 'required|string'
            ]);

            $resultado = $this->limpiarEncargadoCosturaUseCase->execute(new LimpiarEncargadoCosturaCommandDTO(
                pedidoId: (int) $pedidoId,
                prendaId: (int) $prendaId,
                tipoRecibo: (string) $request->tipo_recibo,
            ));

            $payload = [
                'success' => $resultado->success,
                'message' => $resultado->message,
            ];
            if (!empty($resultado->data)) {
                $payload['data'] = $resultado->data;
            }

            return response()->json($payload, $resultado->statusCode);
        } catch (\Exception $e) {
            Log::error('Error limpiando encargado de Costura', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar encargado: ' . $e->getMessage()
            ], 500);
        }
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

            $resultado = $this->cambiarAreaControlCalidadUseCase->execute(new CambiarAreaControlCalidadCommandDTO(
                pedidoId: (int) $pedidoId,
                numeroRecibo: (int) $numeroRecibo,
                prendaId: (int) $request->prenda_id,
                tipoRecibo: (string) $request->tipo_recibo,
            ));

            $payload = [
                'success' => $resultado->success,
                'message' => $resultado->message,
            ];
            if (!empty($resultado->data)) {
                $payload['data'] = $resultado->data;
            }

            return response()->json($payload, $resultado->statusCode);

        } catch (\Exception $e) {
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

            $resultado = $this->deshacerControlCalidadUseCase->execute(new DeshacerControlCalidadCommandDTO(
                pedidoId: (int) $pedidoId,
                prendaId: (int) $prendaId,
                tipoRecibo: (string) $request->tipo_recibo,
            ));

            $payload = [
                'success' => $resultado->success,
                'message' => $resultado->message,
            ];
            if (!empty($resultado->data)) {
                $payload['data'] = $resultado->data;
            }

            return response()->json($payload, $resultado->statusCode);

        } catch (\Exception $e) {
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

    /**
     * Pasar recibo a Costura - crea proceso con encargado y actualiza área
     */
    public function pasarACostura(Request $request, $pedidoId, $numeroRecibo)
    {
        try {
            // Logging para debugging
            Log::info('[COSTURA] Datos recibidos:', [
                'request_all' => $request->all(),
                'pedidoId' => $pedidoId,
                'numeroRecibo' => $numeroRecibo,
                'prenda_id' => $request->input('prenda_id'),
                'encargado' => $request->input('encargado'),
                'tipo_recibo' => $request->input('tipo_recibo')
            ]);

            if (!auth()->user()->hasRole('vista-costura')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para realizar esta acción'
                ], 403);
            }

            $request->validate([
                'prenda_id' => 'required|integer|exists:prendas_pedido,id',
                'tipo_recibo' => 'required|string',
                'encargado' => 'required|string|max:100'
            ]);

            $resultado = $this->pasarACosturaUseCase->execute(new PasarACosturaCommandDTO(
                pedidoId: (int) $pedidoId,
                numeroRecibo: (int) $numeroRecibo,
                prendaId: (int) $request->prenda_id,
                tipoRecibo: (string) $request->tipo_recibo,
                encargado: (string) $request->encargado,
            ));

            $payload = [
                'success' => $resultado->success,
                'message' => $resultado->message,
            ];
            if (!empty($resultado->data)) {
                $payload['data'] = $resultado->data;
            }

            return response()->json($payload, $resultado->statusCode);

        } catch (\Exception $e) {
            Log::error('Error al pasar recibo a Costura', [
                'pedido_id' => $pedidoId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al pasar a Costura: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Deshacer el proceso de Costura - eliminar proceso y restaurar área anterior
     */
    public function deshacerCostura(Request $request, $pedidoId, $prendaId)
    {
        // Logging para debugging - mostrar todos los parámetros
        Log::info('[DESHACER-COSTURA] Parámetros recibidos', [
            'route_params' => func_get_args(),
            'request_all' => $request->all(),
            'pedidoId_param' => $pedidoId,
            'prendaId_param' => $prendaId,
            'request_prenda_id' => $request->prenda_id,
            'request_tipo_recibo' => $request->tipo_recibo
        ]);

        // Logging para debugging
        Log::info('[DESHACER-COSTURA] Iniciando proceso', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'pedido_id' => $pedidoId,
            'prenda_id' => $prendaId,
            'user_agent' => $request->userAgent(),
            'ip' => $request->ip(),
            'user_id' => auth()->id(),
            'tipo_recibo' => $request->tipo_recibo
        ]);

        try {
            if (!auth()->user()->hasRole('vista-costura')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para realizar esta acción'
                ], 403);
            }

            $request->validate([
                'tipo_recibo' => 'required|string'
            ]);

            $resultado = $this->deshacerCosturaUseCase->execute(new DeshacerCosturaCommandDTO(
                pedidoId: (int) $pedidoId,
                prendaId: (int) $prendaId,
                tipoRecibo: (string) $request->tipo_recibo,
            ));

            $payload = [
                'success' => $resultado->success,
                'message' => $resultado->message,
            ];
            if (!empty($resultado->data)) {
                $payload['data'] = $resultado->data;
            }

            return response()->json($payload, $resultado->statusCode);

        } catch (\Exception $e) {
            Log::error('Error al deshacer Costura', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al deshacer Costura: ' . $e->getMessage()
            ], 500);
        }
    }
}
