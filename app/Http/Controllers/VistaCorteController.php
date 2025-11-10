<?php

namespace App\Http\Controllers;

use App\Models\EntregaPedidoCorte;
use App\Models\TablaOriginal;
use Illuminate\Http\Request;

class VistaCorteController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->input('q', '');
        
        $registrosQuery = EntregaPedidoCorte::query();
        
        if ($query) {
            $registrosQuery->where('pedido', 'like', "%{$query}%");
        }
        
        $registros = $registrosQuery->orderBy('pedido', 'desc')
            ->orderBy('prenda', 'asc')
            ->paginate(50);
        
        return view('vista-corte.index', [
            'registros' => $registros,
            'query' => $query,
            'title' => 'Vista Corte - Pedidos',
            'icon' => 'fas fa-cut',
            'tipo' => 'pedido'
        ]);
    }
    
    public function search(Request $request)
    {
        $query = $request->input('q', '');
        
        $registrosQuery = EntregaPedidoCorte::query();
        
        if ($query) {
            $registrosQuery->where('pedido', 'like', "%{$query}%");
        }
        
        $registros = $registrosQuery->orderBy('pedido', 'desc')
            ->orderBy('prenda', 'asc')
            ->paginate(50);
        
        $html = view('vista-corte.partials.cards', [
            'registros' => $registros,
            'tipo' => 'pedido'
        ])->render();
        
        $info = "Mostrando {$registros->firstItem()}-{$registros->lastItem()} de {$registros->total()} registros";
        
        $pagination = $registros->hasPages() 
            ? $registros->appends(['q' => $query])->links()->render() 
            : '';
        
        return response()->json([
            'html' => $html,
            'info' => $info,
            'pagination' => $pagination
        ]);
    }
}
