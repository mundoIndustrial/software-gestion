<?php

namespace App\Infrastructure\Services\Bodega;

use App\Domain\Bodega\Services\BodegaPedidoServiceContract;
use Illuminate\Http\Request;

class BodegaPedidoService implements BodegaPedidoServiceContract
{
    public function __construct(
        private BodegaPedidoConsultaService $consultaService,
        private BodegaPedidoPersistenciaService $persistenciaService
    ) {
    }

    public function obtenerPedidosPaginados(Request $request): array
    {
        return $this->consultaService->obtenerPedidosPaginados($request);
    }

    public function obtenerPedidosAnuladosPaginados(Request $request): array
    {
        return $this->consultaService->obtenerPedidosAnuladosPaginados($request);
    }

    public function obtenerPedidosEntregadosPaginados(Request $request): array
    {
        return $this->consultaService->obtenerPedidosEntregadosPaginados($request);
    }

    public function obtenerDetallePedido(int $pedidoId, bool $paraDespacho = false): array
    {
        return $this->consultaService->obtenerDetallePedido($pedidoId, $paraDespacho);
    }

    public function obtenerDatosFactura(int $id): array
    {
        return $this->consultaService->obtenerDatosFactura($id);
    }

    public function guardarDetalles(array $validatedData): array
    {
        return $this->persistenciaService->guardarDetalles($validatedData);
    }

    public function registrarEntregaPrenda(array $datosPrenda, int $pedidoProduccionId): array
    {
        return $this->persistenciaService->registrarEntregaPrenda($datosPrenda, $pedidoProduccionId);
    }

    public function registrarEntregasMasivas(int $pedidoProduccionId, array $prendasEntregadas): array
    {
        return $this->persistenciaService->registrarEntregasMasivas($pedidoProduccionId, $prendasEntregadas);
    }

    public function call(string $method, array $arguments = []): mixed
    {
        if (!method_exists($this, $method)) {
            throw new \BadMethodCallException("Method {BodegaPedidoService}::$method does not exist");
        }

        return $this->{$method}(...$arguments);
    }
}
