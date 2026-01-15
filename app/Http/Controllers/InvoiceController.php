<?php

namespace App\Http\Controllers;

use App\Domain\Ordenes\Entities\Orden;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    /**
     * Muestra la factura de una orden
     * 
     * @param string $numeroPedido - Número del pedido
     * @return \Illuminate\View\View
     */
    public function show($numeroPedido)
    {
        // Buscar la orden por número de pedido
        $orden = Orden::where('numero_pedido', $numeroPedido)
            ->with(['prendas', 'asesor', 'supervisor'])
            ->firstOrFail();

        return view('invoices.show', ['orden' => $orden]);
    }

    /**
     * Descarga la factura en PDF
     * 
     * @param string $numeroPedido - Número del pedido
     * @return \Illuminate\Http\Response
     */
    public function download($numeroPedido)
    {
        $orden = Orden::where('numero_pedido', $numeroPedido)
            ->with(['prendas', 'asesor', 'supervisor'])
            ->firstOrFail();

        // Aquí puedes usar Dompdf o similar para generar PDF
        // return PDF::loadView('invoices.pdf', ['orden' => $orden])->download("factura-{$numeroPedido}.pdf");
        
        // Por ahora retornamos la vista
        return view('invoices.show', ['orden' => $orden]);
    }

    /**
     * Muestra una vista preliminar para impresión
     * 
     * @param string $numeroPedido - Número del pedido
     * @return \Illuminate\View\View
     */
    public function preview($numeroPedido)
    {
        $orden = Orden::where('numero_pedido', $numeroPedido)
            ->with(['prendas', 'asesor', 'supervisor'])
            ->firstOrFail();

        return view('invoices.preview', ['orden' => $orden]);
    }
}
