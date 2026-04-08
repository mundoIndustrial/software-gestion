<?php

namespace App\Application\Services\Asesores;

final class PedidosProduccionViewApplicationFacadeService
{
    public function __construct(
        private readonly ObtenerDatosCotizacionService $obtenerDatosCotizacionService,
        private readonly ObtenerDatosFacturaService $obtenerDatosFacturaService,
        private readonly ObtenerDatosPrendaPedidoService $obtenerDatosPrendaPedidoService,
        private readonly ObtenerPrendaCompletaDesdeCotizacionService $obtenerPrendaCompletaDesdeCotizacionService
    ) {
    }

    public function resolverDatosCotizacion(int $cotizacionId, int $usuarioId): array
    {
        $datos = $this->obtenerDatosCotizacionService->obtenerParaAsesor($cotizacionId, $usuarioId);

        if ($datos === null) {
            return [
                'status' => 404,
                'payload' => [
                    'error' => 'cotizacion no encontrada o no tienes permisos para acceder a ella',
                ],
            ];
        }

        return [
            'status' => 200,
            'payload' => [
                'error' => null,
                'prendas' => $datos['prendas'],
                'logo' => $datos['logo'],
                'tiene_prendas' => $datos['tiene_prendas'],
                'tiene_logo' => $datos['tiene_logo'],
            ],
        ];
    }

    public function obtenerDatosEdicion(int $pedidoId): array
    {
        $datos = $this->obtenerDatosFacturaService->obtener($pedidoId);

        return [
            'status' => 200,
            'payload' => [
                'success' => true,
                'datos' => $datos,
            ],
            'meta' => [
                'prendas_count' => count($datos['prendas'] ?? []),
            ],
        ];
    }

    public function resolverDatosUnaPrenda(int $pedidoId, int $prendaId): array
    {
        $datos = $this->obtenerDatosPrendaPedidoService->obtenerParaEdicion($pedidoId, $prendaId);

        if ($datos === null) {
            return [
                'status' => 404,
                'payload' => [
                    'success' => false,
                    'message' => 'Prenda no encontrada',
                ],
            ];
        }

        return [
            'status' => 200,
            'payload' => [
                'success' => true,
                'prenda' => $datos,
            ],
        ];
    }

    public function resolverPrendaCompleta(int $cotizacionId, int $prendaId): array
    {
        $resultado = $this->obtenerPrendaCompletaDesdeCotizacionService->obtener($cotizacionId, $prendaId);
        $status = $resultado['status'] ?? null;

        if ($status === 'cotizacion_no_encontrada') {
            return [
                'status' => 404,
                'payload' => ['error' => 'cotizacion no encontrada'],
            ];
        }

        if ($status === 'prenda_no_encontrada') {
            return [
                'status' => 404,
                'payload' => ['error' => 'Prenda no encontrada'],
            ];
        }

        if ($status === 'ok') {
            return [
                'status' => 200,
                'payload' => array_merge(['success' => true], $resultado['data']),
            ];
        }

        return [
            'status' => 500,
            'payload' => ['error' => 'Error al obtener prenda completa'],
        ];
    }
}

