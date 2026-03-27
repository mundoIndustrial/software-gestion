<?php

namespace App\Infrastructure\Http\Controllers\Facturacion;

use App\Domain\Pedidos\Repositories\PedidoProduccionReadRepository;
use App\Http\Controllers\Controller;
use App\Models\PedidoProduccion;

final class InvoiceController extends Controller
{
    public function __construct(
        private readonly PedidoProduccionReadRepository $repository
    ) {
    }

    public function show($numeroPedido)
    {
        $pedido = PedidoProduccion::where('numero_pedido', $numeroPedido)
            ->with(['asesor', 'prendas'])
            ->firstOrFail();

        $datosFactura = $this->repository->obtenerDatosFactura($pedido->id);
        $datosFactura = $this->normalizarDatosFactura($datosFactura);

        return view('invoices.show', [
            'orden' => $datosFactura,
            'pedido' => $pedido,
        ]);
    }

    public function download($numeroPedido)
    {
        $pedido = PedidoProduccion::where('numero_pedido', $numeroPedido)
            ->with(['asesor', 'prendas'])
            ->firstOrFail();

        $datosFactura = $this->repository->obtenerDatosFactura($pedido->id);
        $datosFactura = $this->normalizarDatosFactura($datosFactura);

        return view('invoices.show', [
            'orden' => $datosFactura,
            'pedido' => $pedido,
        ]);
    }

    public function preview($numeroPedido)
    {
        $pedido = PedidoProduccion::where('numero_pedido', $numeroPedido)
            ->with(['asesor', 'prendas'])
            ->firstOrFail();

        $datosFactura = $this->repository->obtenerDatosFactura($pedido->id);
        $datosFactura = $this->normalizarDatosFactura($datosFactura);

        return view('invoices.preview', [
            'orden' => $datosFactura,
            'pedido' => $pedido,
        ]);
    }

    private function normalizarDatosFactura(array $datosFactura): object
    {
        $datos = (object) $datosFactura;

        if (isset($datos->prendas)) {
            $datos->prendas = collect($datos->prendas)->map(fn ($p) => (object) $p)->all();
            foreach ($datos->prendas as $prenda) {
                if (isset($prenda->variantes)) {
                    $prenda->variantes = collect($prenda->variantes)->map(fn ($v) => (object) $v)->all();
                }
            }
        }

        return $datos;
    }
}

