<?php

namespace App\Application\Bodega\Services;

use Illuminate\Http\JsonResponse;

class PedidoConsultasService
{
    public function __construct(
        private BodegaPedidoService $bodegaPedidoService
    ) {}

    public function obtenerDatosFactura(int $id): JsonResponse
    {
        try {
            $resultado = $this->bodegaPedidoService->obtenerDatosFactura($id);
            return response()->json($resultado);

        } catch (\Exception $e) {
            \Log::error('Error en obtenerDatosFactura: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener datos: ' . $e->getMessage()
            ], 400);
        }
    }

    public function obtenerDatosHomologacion(int $eppId): JsonResponse
    {
        try {
            $eppNuevo = \App\Models\PedidoEpp::with('epp')->find($eppId);

            if (!$eppNuevo) {
                return response()->json([
                    'success' => false,
                    'message' => 'EPP no encontrado'
                ], 404);
            }

            $eppAnterior = null;
            if ($eppNuevo->homologado_de) {
                $eppAnterior = \App\Models\PedidoEpp::withTrashed()
                    ->with('epp')
                    ->find($eppNuevo->homologado_de);
            }

            if (!$eppAnterior) {
                return response()->json([
                    'success' => false,
                    'message' => 'EPP anterior no encontrado. Este EPP no fue homologado.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'epp_anterior' => [
                        'id' => $eppAnterior->id,
                        'nombre' => $eppAnterior->epp->nombre_completo ?? $eppAnterior->epp->nombre ?? 'EPP Sin nombre',
                        'cantidad' => $eppAnterior->cantidad,
                        'observaciones' => $eppAnterior->observaciones,
                        'deleted_at' => $eppAnterior->deleted_at,
                    ],
                    'epp_nuevo' => [
                        'id' => $eppNuevo->id,
                        'nombre' => $eppNuevo->epp->nombre_completo ?? $eppNuevo->epp->nombre ?? 'EPP Sin nombre',
                        'cantidad' => $eppNuevo->cantidad,
                        'observaciones' => $eppNuevo->observaciones,
                        'created_at' => $eppNuevo->created_at,
                    ],
                    'cambios' => [
                        'epp_cambio' => $eppAnterior->epp->nombre_completo !== $eppNuevo->epp->nombre_completo,
                        'cantidad_cambio' => $eppAnterior->cantidad !== $eppNuevo->cantidad,
                        'observaciones_cambio' => $eppAnterior->observaciones !== $eppNuevo->observaciones,
                        'cantidad_anterior' => $eppAnterior->cantidad,
                        'cantidad_nueva' => $eppNuevo->cantidad,
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Error en obtenerDatosHomologacion: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener datos de homologación'
            ], 500);
        }
    }
}
