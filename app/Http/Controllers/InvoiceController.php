<?php

namespace App\Http\Controllers;

use App\Models\PedidoProduccion;
use App\Domain\Pedidos\Repositories\PedidoProduccionRepository;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    private PedidoProduccionRepository $repository;

    public function __construct(PedidoProduccionRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Muestra la factura de una orden
     * 
     * @param string $numeroPedido - Número del pedido
     * @return \Illuminate\View\View
     */
    public function show($numeroPedido)
    {
        // Buscar el pedido por número de pedido
        $pedido = PedidoProduccion::where('numero_pedido', $numeroPedido)
            ->with(['asesor', 'prendas'])
            ->firstOrFail();

        // Obtener datos completos de la factura con variantes
        $datosFactura = $this->repository->obtenerDatosFactura($pedido->id);
        
        // Convertir array a objeto compatible con la vista
        $datosFactura = (object) $datosFactura;
        if (isset($datosFactura->prendas)) {
            $datosFactura->prendas = collect($datosFactura->prendas)->map(fn($p) => (object)$p)->all();
        }
        if (isset($datosFactura->prendas)) {
            foreach ($datosFactura->prendas as $prenda) {
                if (isset($prenda->variantes)) {
                    $prenda->variantes = collect($prenda->variantes)->map(fn($v) => (object)$v)->all();
                }
            }
        }

        return view('invoices.show', [
            'orden' => $datosFactura,
            'pedido' => $pedido
        ]);
    }

    /**
     * Descarga la factura en PDF
     * 
     * @param string $numeroPedido - Número del pedido
     * @return \Illuminate\Http\Response
     */
    public function download($numeroPedido)
    {
        // Buscar el pedido por número de pedido
        $pedido = PedidoProduccion::where('numero_pedido', $numeroPedido)
            ->with(['asesor', 'prendas'])
            ->firstOrFail();

        // Obtener datos completos de la factura con variantes
        $datosFactura = $this->repository->obtenerDatosFactura($pedido->id);
        
        // Convertir array a objeto compatible con la vista
        $datosFactura = (object) $datosFactura;
        if (isset($datosFactura->prendas)) {
            $datosFactura->prendas = collect($datosFactura->prendas)->map(fn($p) => (object)$p)->all();
        }
        if (isset($datosFactura->prendas)) {
            foreach ($datosFactura->prendas as $prenda) {
                if (isset($prenda->variantes)) {
                    $prenda->variantes = collect($prenda->variantes)->map(fn($v) => (object)$v)->all();
                }
            }
        }

        // Aquí puedes usar Dompdf o similar para generar PDF
        // return PDF::loadView('invoices.pdf', ['orden' => $datosFactura])->download("factura-{$numeroPedido}.pdf");
        
        // Por ahora retornamos la vista
        return view('invoices.show', [
            'orden' => $datosFactura,
            'pedido' => $pedido
        ]);
    }

    /**
     * Muestra una vista preliminar para impresión
     * 
     * @param string $numeroPedido - Número del pedido
     * @return \Illuminate\View\View
     */
    public function preview($numeroPedido)
    {
        // Buscar el pedido por número de pedido
        $pedido = PedidoProduccion::where('numero_pedido', $numeroPedido)
            ->with(['asesor', 'prendas'])
            ->firstOrFail();

        // Obtener datos completos de la factura con variantes
        $datosFactura = $this->repository->obtenerDatosFactura($pedido->id);
        
        // Convertir array a objeto compatible con la vista
        $datosFactura = (object) $datosFactura;
        if (isset($datosFactura->prendas)) {
            $datosFactura->prendas = collect($datosFactura->prendas)->map(fn($p) => (object)$p)->all();
        }
        if (isset($datosFactura->prendas)) {
            foreach ($datosFactura->prendas as $prenda) {
                if (isset($prenda->variantes)) {
                    $prenda->variantes = collect($prenda->variantes)->map(fn($v) => (object)$v)->all();
                }
            }
        }

        return view('invoices.preview', [
            'orden' => $datosFactura,
            'pedido' => $pedido
        ]);
    }
}
